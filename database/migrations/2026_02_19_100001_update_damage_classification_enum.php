<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Expand enum to include both old and new values
        DB::statement("ALTER TABLE beneficiaries MODIFY classify_extent_of_damaged_house ENUM('Totally Damaged', 'Partially Damaged', 'Totally Damaged (Severely)', 'Partially Damaged (Slightly)') NOT NULL");

        // Step 2: Migrate existing data
        DB::table('beneficiaries')
            ->where('classify_extent_of_damaged_house', 'Totally Damaged')
            ->update(['classify_extent_of_damaged_house' => 'Totally Damaged (Severely)']);

        DB::table('beneficiaries')
            ->where('classify_extent_of_damaged_house', 'Partially Damaged')
            ->update(['classify_extent_of_damaged_house' => 'Partially Damaged (Slightly)']);

        // Step 3: Shrink enum to only new values
        DB::statement("ALTER TABLE beneficiaries MODIFY classify_extent_of_damaged_house ENUM('Totally Damaged (Severely)', 'Partially Damaged (Slightly)') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE beneficiaries MODIFY classify_extent_of_damaged_house ENUM('Totally Damaged (Severely)', 'Partially Damaged (Slightly)', 'Totally Damaged', 'Partially Damaged') NOT NULL");

        DB::table('beneficiaries')
            ->where('classify_extent_of_damaged_house', 'Totally Damaged (Severely)')
            ->update(['classify_extent_of_damaged_house' => 'Totally Damaged']);

        DB::table('beneficiaries')
            ->where('classify_extent_of_damaged_house', 'Partially Damaged (Slightly)')
            ->update(['classify_extent_of_damaged_house' => 'Partially Damaged']);

        DB::statement("ALTER TABLE beneficiaries MODIFY classify_extent_of_damaged_house ENUM('Totally Damaged', 'Partially Damaged') NOT NULL");
    }
};
