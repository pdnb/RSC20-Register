<?php

namespace App\Models;

use App\Enums\Attendance;
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

    /** Stored code → label (M- = men's polo, F- = women's polo) */
    public const SHIRT_SIZES = [
        'M-SS' => 'เสื้อโปโลชาย SS',
        'M-S' => 'เสื้อโปโลชาย S',
        'M-M' => 'เสื้อโปโลชาย M',
        'M-L' => 'เสื้อโปโลชาย L',
        'M-XL' => 'เสื้อโปโลชาย XL',
        'M-2XL' => 'เสื้อโปโลชาย 2XL',
        'M-3XL' => 'เสื้อโปโลชาย 3XL',
        'M-4XL' => 'เสื้อโปโลชาย 4XL',
        'M-5XL' => 'เสื้อโปโลชาย 5XL',
        'F-SS' => 'เสื้อโปโลหญิง SS',
        'F-S' => 'เสื้อโปโลหญิง S',
        'F-M' => 'เสื้อโปโลหญิง M',
        'F-L' => 'เสื้อโปโลหญิง L',
        'F-XL' => 'เสื้อโปโลหญิง XL',
        'F-2XL' => 'เสื้อโปโลหญิง 2XL',
        'F-3XL' => 'เสื้อโปโลหญิง 3XL',
    ];

    /** Section 2 position → participant type code */
    public const MEMBER_POSITIONS = ['บุคลากร' => ParticipantType::STAFF, 'นักศึกษา' => ParticipantType::STUDENT];

    /** Board game team size per university, by participant type code (1 staff + 6 students). */
    public const BOARD_GAME_LIMITS = [ParticipantType::STAFF => 1, ParticipantType::STUDENT => 6];

    protected $fillable = [
        'registration_group_id', 'participant_type_id', 'section', 'attendance', 'position', 'title', 'full_name',
        'mobile_phone', 'national_id', 'food_type', 'food_allergy', 'shirt_size', 'badge_photo_path',
        'fee_amount', 'receipt_name', 'receipt_address', 'receipt_tax_id', 'receipt_no',
        'checked_in_at', 'checked_in_by',
    ];

    protected $attributes = [
        'attendance' => 'conference',
    ];

    protected $hidden = ['national_id', 'national_id_hash', 'receipt_tax_id'];

    protected function casts(): array
    {
        return [
            'section' => Section::class,
            'attendance' => Attendance::class,
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

    public function shirtSizeLabel(): string
    {
        if ($this->shirt_size === null) {
            return '-';
        }

        return self::SHIRT_SIZES[$this->shirt_size] ?? $this->shirt_size;
    }

    /** Board-game-only entrants pay no fee and get no shirt, but they still choose their food. */
    public function attendsConference(): bool
    {
        return $this->attendance->includesConference();
    }

    /** No receipt info of its own: the group's shared receipt info applies. */
    public function usesGroupReceipt(): bool
    {
        return $this->receipt_name === null;
    }

    public function receiptName(): ?string
    {
        return $this->usesGroupReceipt() ? $this->group->receipt_name : $this->receipt_name;
    }

    public function receiptAddress(): ?string
    {
        return $this->usesGroupReceipt() ? $this->group->receipt_address : $this->receipt_address;
    }

    public function receiptTaxId(): ?string
    {
        return $this->usesGroupReceipt() ? $this->group->receipt_tax_id : $this->receipt_tax_id;
    }

    public function maskedNationalId(): string
    {
        return ThaiId::mask($this->national_id);
    }
}
