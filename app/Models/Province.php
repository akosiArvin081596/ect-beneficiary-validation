<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Province extends Model
{
    public $timestamps = false;

    protected $fillable = ['name', 'psgc_code'];

    public function municipalities(): HasMany
    {
        return $this->hasMany(Municipality::class);
    }
}
