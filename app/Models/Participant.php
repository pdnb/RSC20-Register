<?php

namespace App\Models;

use App\Enums\Section;
use App\Observers\ParticipantObserver;
use App\Support\ThaiId;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[ObservedBy(ParticipantObserver::class)]
class Participant extends Model
{
    public const TITLES = ['นาย', 'นาง', 'นางสาว', 'ดร.', 'ผศ.', 'ผศ.ดร.', 'รศ.', 'รศ.ดร.', 'ศ.', 'ศ.ดร.'];

    public const FOOD_TYPES = ['อาหารทั่วไป', 'อาหารมังสวิรัติ', 'อาหารเจ', 'อาหารฮาลาล'];

    public const SHIRT_SIZES = ['SS', 'S', 'M', 'L', 'XL', '2XL', '3XL', '4XL', '5XL'];

    /** Section 2 position → participant type code */
    public const MEMBER_POSITIONS = ['บุคลากร' => ParticipantType::STAFF, 'นักศึกษา' => ParticipantType::STUDENT];

    protected $fillable = [
        'registration_group_id', 'participant_type_id', 'section', 'position', 'title', 'full_name',
        'mobile_phone', 'national_id', 'food_type', 'food_allergy', 'shirt_size', 'badge_photo_path',
        'fee_amount', 'receipt_name', 'receipt_address', 'receipt_tax_id', 'receipt_no',
        'checked_in_at', 'checked_in_by',
    ];

    protected $hidden = ['national_id', 'national_id_hash', 'receipt_tax_id'];

    protected function casts(): array
    {
        return [
            'section' => Section::class,
            'national_id' => 'encrypted',
            'receipt_tax_id' => 'encrypted',
            'fee_amount' => 'decimal:2',
            'checked_in_at' => 'datetime',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(RegistrationGroup::class, 'registration_group_id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(ParticipantType::class, 'participant_type_id');
    }

    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }

    public function financeSubmissions(): BelongsToMany
    {
        return $this->belongsToMany(FinanceSubmission::class);
    }

    public function displayName(): string
    {
        return $this->title.$this->full_name;
    }

    public function maskedNationalId(): string
    {
        return ThaiId::mask($this->national_id);
    }
}
