<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ParticipantType extends Model
{
    public const EXECUTIVE = 'executive';

    public const STAFF = 'staff';

    public const STUDENT = 'student';

    protected $fillable = ['code', 'name_th', 'fee', 'sort_order'];

    protected function casts(): array
    {
        return ['fee' => 'decimal:2'];
    }

    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }

    public static function byCode(string $code): self
    {
        return static::query()->where('code', $code)->firstOrFail();
    }
}
