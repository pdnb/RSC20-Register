<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RegistrationGroup extends Model
{
    protected $fillable = [
        'university_id', 'coordinator_id', 'payment_method', 'payment_status', 'total_amount', 'paid_amount',
        'slip_path', 'slip_reference', 'transferred_at', 'slip_submitted_at', 'reject_reason',
        'approved_by', 'approved_at', 'onsite_received_by', 'onsite_received_at', 'pdpa_accepted_at',
        'receipt_name', 'receipt_address', 'receipt_tax_id',
    ];

    protected $hidden = ['receipt_tax_id'];

    protected $attributes = [
        'payment_status' => 'pending',
        'total_amount' => 0,
    ];

    protected function casts(): array
    {
        return [
            'payment_method' => PaymentMethod::class,
            'payment_status' => PaymentStatus::class,
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'transferred_at' => 'datetime',
            'slip_submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'onsite_received_at' => 'datetime',
            'pdpa_accepted_at' => 'datetime',
            'receipt_tax_id' => 'encrypted',
        ];
    }

    public function university(): BelongsTo
    {
        return $this->belongsTo(University::class);
    }

    public function coordinator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coordinator_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function onsiteReceiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'onsite_received_by');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }

    public function isPaid(): bool
    {
        return $this->payment_status->isPaid();
    }

    /** Coordinators can change the list until payment is confirmed, while registration is open. */
    public function isEditableByCoordinator(): bool
    {
        return ! $this->isPaid() && Setting::registrationOpen();
    }

    /** Shared receipt info that participants use unless they enter their own. */
    public function hasReceiptInfo(): bool
    {
        return filled($this->receipt_name) && filled($this->receipt_address) && filled($this->receipt_tax_id);
    }

    public function paidAt(): ?CarbonInterface
    {
        return $this->payment_status === PaymentStatus::OnsitePaid ? $this->onsite_received_at : $this->approved_at;
    }
}
