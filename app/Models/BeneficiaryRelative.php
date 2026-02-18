<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BeneficiaryRelative extends Model
{
    /** @use HasFactory<\Database\Factories\BeneficiaryRelativeFactory> */
    use HasFactory;

    protected $fillable = [
        'beneficiary_id',
        'last_name',
        'first_name',
        'middle_name',
        'birth_date',
        'relationship',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
        ];
    }

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class);
    }
}
