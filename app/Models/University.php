<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class University extends Model
{
    protected $fillable = ['code', 'name', 'sort_order'];

    public function group(): HasOne
    {
        return $this->hasOne(RegistrationGroup::class);
    }

    public function coordinators(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
