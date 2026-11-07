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
     * University and status take one value or a list of them; an empty list means all.
     *
     * @param  array{date_from?: ?string, date_to?: ?string, university_id?: string|int|array<int, string|int>|null, method?: ?string, status?: string|array<int, string>|null, only_new?: bool}  $filters
     */
    public static function query(array $filters): Builder
    {
        $from = filled($filters['date_from'] ?? null) ? Carbon::parse($filters['date_from'])->startOfDay() : null;
        $to = filled($filters['date_to'] ?? null) ? Carbon::parse($filters['date_to'])->endOfDay() : null;
        $universityIds = self::listOf($filters['university_id'] ?? null);
        $statuses = self::listOf($filters['status'] ?? null);

        // No status chosen: every paid status, plus unpaid on-site groups unless a date range is set
        // (they have no payment date to fall inside it).
        $paidStatuses = $statuses === []
            ? PaymentStatus::paid()
            : array_map(PaymentStatus::from(...), array_values(array_intersect($statuses, ['approved', 'onsite_paid'])));
        $includesOnsitePending = $statuses === [] ? (! $from && ! $to) : in_array('onsite_pending', $statuses, true);

        return Participant::query()
            // Board-game-only entrants pay nothing, so there is nothing to submit to finance.
            ->where('fee_amount', '>', 0)
            ->whereHas('group', function (Builder $g) use ($filters, $from, $to, $universityIds, $paidStatuses, $includesOnsitePending) {
                $g->when($universityIds !== [], fn ($q) => $q->whereIn('university_id', $universityIds))
                    ->when($filters['method'] ?? null, fn ($q, $m) => $q->where('payment_method', $m));

                $paid = fn ($q) => $q->whereIn('payment_status', $paidStatuses)
                    ->when($from || $to, fn ($q) => $q->where(function ($w) use ($from, $to) {
                        $w->where(fn ($a) => $a->where('payment_status', PaymentStatus::Approved)
                            ->when($from, fn ($x) => $x->where('approved_at', '>=', $from))
                            ->when($to, fn ($x) => $x->where('approved_at', '<=', $to)))
                            ->orWhere(fn ($o) => $o->where('payment_status', PaymentStatus::OnsitePaid)
                                ->when($from, fn ($x) => $x->where('onsite_received_at', '>=', $from))
                                ->when($to, fn ($x) => $x->where('onsite_received_at', '<=', $to)));
                    }));
                $onsitePending = fn ($q) => $q->where('payment_method', PaymentMethod::Onsite)->where('payment_status', PaymentStatus::Pending);

                $g->where(fn ($w) => $w
                    ->when($paidStatuses !== [], fn ($w) => $w->where($paid))
                    ->when($includesOnsitePending, fn ($w) => $w->orWhere($onsitePending))
                    ->when($paidStatuses === [] && ! $includesOnsitePending, fn ($w) => $w->whereRaw('1 = 0')));
            })
            ->when($filters['only_new'] ?? true, fn ($q) => $q->whereDoesntHave('financeSubmissions'));
    }

    /**
     * @return array<int, string>
     */
    private static function listOf(mixed $value): array
    {
        return array_values(array_filter(array_map('strval', (array) $value), fn (string $item): bool => $item !== ''));
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
