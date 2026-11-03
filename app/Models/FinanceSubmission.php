<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class FinanceSubmission extends Model
{
    protected $fillable = ['submission_no', 'date_from', 'date_to', 'filters', 'item_count', 'total_amount', 'created_by'];

    protected function casts(): array
    {
        return [
            'date_from' => 'date',
            'date_to' => 'date',
            'filters' => 'array',
            'total_amount' => 'decimal:2',
        ];
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(Participant::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
