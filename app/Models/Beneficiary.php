<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Beneficiary extends Model
{
    /** @use HasFactory<\Database\Factories\BeneficiaryFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'offline_id',
        'timestamp',
        'province',
        'municipality',
        'barangay',
        'purok',
        'last_name',
        'first_name',
        'middle_name',
        'extension_name',
        'sex',
        'birth_date',
        'classify_extent_of_damaged_house',
        'nhts_pr_classification',
        'applicable_sector',
        'civil_status',
        'living_with_father',
        'father_last_name',
        'father_first_name',
        'father_middle_name',
        'father_extension_name',
        'father_birth_date',
        'living_with_mother',
        'mother_last_name',
        'mother_first_name',
        'mother_middle_name',
        'mother_birth_date',
        'living_with_siblings',
        'living_with_spouse',
        'spouse_last_name',
        'spouse_first_name',
        'spouse_middle_name',
        'spouse_extension_name',
        'spouse_birth_date',
        'living_with_children',
        'living_with_relatives',
    ];

    protected function casts(): array
    {
        return [
            'timestamp' => 'date',
            'birth_date' => 'date',
            'father_birth_date' => 'date',
            'mother_birth_date' => 'date',
            'spouse_birth_date' => 'date',
            'living_with_father' => 'boolean',
            'living_with_mother' => 'boolean',
            'living_with_siblings' => 'boolean',
            'living_with_spouse' => 'boolean',
            'living_with_children' => 'boolean',
            'living_with_relatives' => 'boolean',
            'applicable_sector' => 'array',
        ];
    }

    public function siblings(): HasMany
    {
        return $this->hasMany(BeneficiarySibling::class);
    }

    public function children(): HasMany
    {
        return $this->hasMany(BeneficiaryChild::class);
    }

    public function relatives(): HasMany
    {
        return $this->hasMany(BeneficiaryRelative::class);
    }
}
