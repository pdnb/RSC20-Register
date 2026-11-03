<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\AuditLog;
use App\Models\FinanceSubmission;
use App\Models\Participant;
use App\Models\User;
use App\Support\Images;
use App\Support\ThaiBaht;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Report sent to the finance office (กองคลัง) so it can issue one receipt per person.
 * Covers paid participants and pay-at-venue groups not paid yet, so the office can prepare their
 * receipts before the event. Each participant can be submitted once.
 */
class FinanceReport
{
    /** Status filter options; an empty value means all of them. */
    public const STATUSES = [
        'approved' => 'ชำระแล้ว (อนุมัติสลิป)',
        'onsite_paid' => 'ชำระหน้างานแล้ว',
        'onsite_pending' => 'รอชำระหน้างาน',
    ];

    /**
     * @param  array{date_from?: ?string, date_to?: ?string, university_id?: ?string, method?: ?string, status?: ?string, only_new?: bool}  $filters
     */
    public static function query(array $filters): Builder
    {
        $from = filled($filters['date_from'] ?? null) ? Carbon::parse($filters['date_from'])->startOfDay() : null;
        $to = filled($filters['date_to'] ?? null) ? Carbon::parse($filters['date_to'])->endOfDay() : null;
        $status = $filters['status'] ?? null;

        return Participant::query()
            ->whereHas('group', function (Builder $g) use ($filters, $from, $to, $status) {
                $g->when($filters['university_id'] ?? null, fn ($q, $id) => $q->where('university_id', $id))
                    ->when($filters['method'] ?? null, fn ($q, $m) => $q->where('payment_method', $m));

                $paid = fn ($q) => $q->whereIn('payment_status', $status ? [PaymentStatus::from($status)] : PaymentStatus::paid())
                    ->when($from || $to, fn ($q) => $q->where(function ($w) use ($from, $to) {
                        $w->where(fn ($a) => $a->where('payment_status', PaymentStatus::Approved)
                            ->when($from, fn ($x) => $x->where('approved_at', '>=', $from))
                            ->when($to, fn ($x) => $x->where('approved_at', '<=', $to)))
                            ->orWhere(fn ($o) => $o->where('payment_status', PaymentStatus::OnsitePaid)
                                ->when($from, fn ($x) => $x->where('onsite_received_at', '>=', $from))
                                ->when($to, fn ($x) => $x->where('onsite_received_at', '<=', $to)));
                    }));
                $onsitePending = fn ($q) => $q->where('payment_method', PaymentMethod::Onsite)->where('payment_status', PaymentStatus::Pending);

                // Unpaid on-site groups have no payment date, so a date range leaves them out of "all statuses".
                match ($status) {
                    'onsite_pending' => $g->where($onsitePending),
                    'approved', 'onsite_paid' => $g->where($paid),
                    default => $g->where(fn ($w) => $w->where($paid)->when(! $from && ! $to, fn ($w) => $w->orWhere($onsitePending))),
                };
            })
            ->when($filters['only_new'] ?? true, fn ($q) => $q->whereDoesntHave('financeSubmissions'));
    }

    public static function create(array $filters, User $user): FinanceSubmission
    {
        return DB::transaction(function () use ($filters, $user) {
            $participants = self::query($filters)->lockForUpdate()->get();
            abort_if($participants->isEmpty(), 422, 'ไม่มีรายการตามเงื่อนไขที่เลือก');

            $next = (int) FinanceSubmission::query()->lockForUpdate()->count() + 1;
            $submission = FinanceSubmission::query()->create([
                'submission_no' => sprintf('RSC20-FIN-%03d', $next),
                'date_from' => $filters['date_from'] ?: null,
                'date_to' => $filters['date_to'] ?: null,
                'filters' => $filters,
                'item_count' => $participants->count(),
                'total_amount' => $participants->sum('fee_amount'),
                'created_by' => $user->id,
            ]);
            $submission->participants()->attach($participants->modelKeys());

            AuditLog::record('finance.submission_created', $submission, ['items' => $participants->count()]);

            return $submission;
        });
    }

    /**
     * Everything the PDF and Excel versions render.
     *
     * @return array<string, mixed>
     */
    public static function data(FinanceSubmission $submission): array
    {
        $participants = $submission->participants()
            ->with(['group.university', 'group.approver', 'group.onsiteReceiver', 'type'])
            ->get()
            ->sortBy([fn ($a, $b) => $a->group->university->sort_order <=> $b->group->university->sort_order, ['id', 'asc']])
            ->values();

        // One appendix entry per transfer slip; a slip covers every person of its group.
        $evidence = $participants->groupBy('registration_group_id')->values()->map(function (Collection $people, int $i) {
            $group = $people->first()->group;

            return [
                'no' => $i + 1,
                'group' => $group,
                'participants' => $people,
                'amount' => (float) $people->sum('fee_amount'),
                'is_transfer' => $group->payment_method === PaymentMethod::Transfer,
                'slip_path' => $group->payment_method === PaymentMethod::Transfer ? $group->slip_path : null,
            ];
        });
        $evidenceNo = $evidence->mapWithKeys(fn ($e) => [$e['group']->id => $e['no']]);

        $summary = $participants->groupBy(fn ($p) => $p->type->name_th.'|'.$p->group->payment_method?->label())
            ->map(fn ($ps, $key) => [
                'type' => explode('|', $key)[0],
                'method' => explode('|', $key)[1],
                'count' => $ps->count(),
                'amount' => (float) $ps->sum('fee_amount'),
            ])->values();

        $total = (float) $participants->sum('fee_amount');

        return [
            'submission' => $submission,
            'participants' => $participants,
            'evidence' => $evidence,
            'evidenceNo' => $evidenceNo,
            'summary' => $summary,
            'total' => $total,
            'totalText' => ThaiBaht::text($total),
        ];
    }

    /** Slip resized for embedding (PDF page or Excel drawing); returns a temp file path. */
    public static function slipThumbnail(string $path, int $max): ?string
    {
        if (! Storage::disk('local')->exists($path)) {
            return null;
        }

        $tmp = tempnam(sys_get_temp_dir(), 'slip').'.jpg';
        file_put_contents($tmp, Images::fit(Storage::disk('local')->path($path), $max, 75));

        return $tmp;
    }
}
