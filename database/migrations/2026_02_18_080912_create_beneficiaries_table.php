<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beneficiaries', function (Blueprint $table) {
            $table->id();

            // Basic info
            $table->date('timestamp');
            $table->string('province');
            $table->string('municipality');
            $table->string('barangay');
            $table->string('purok')->nullable();
            $table->string('last_name');
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('extension_name')->nullable();
            $table->enum('sex', ['Male', 'Female']);
            $table->date('birth_date');
            $table->enum('classify_extent_of_damaged_house', ['Totally Damaged', 'Partially Damaged']);
            $table->enum('nhts_pr_classification', ['Poor', 'Near Poor', 'Not Poor']);
            $table->json('applicable_sector')->nullable();
            $table->enum('civil_status', ['Single', 'Married', 'Widowed', 'Separated', 'Annulled']);

            // Father
            $table->boolean('living_with_father')->default(false);
            $table->string('father_last_name')->nullable();
            $table->string('father_first_name')->nullable();
            $table->string('father_middle_name')->nullable();
            $table->string('father_extension_name')->nullable();
            $table->date('father_birth_date')->nullable();

            // Mother
            $table->boolean('living_with_mother')->default(false);
            $table->string('mother_last_name')->nullable();
            $table->string('mother_first_name')->nullable();
            $table->string('mother_middle_name')->nullable();
            $table->date('mother_birth_date')->nullable();

            // Siblings
            $table->boolean('living_with_siblings')->default(false);
            $table->unsignedTinyInteger('siblings_count')->default(0);
            $table->json('siblings')->nullable();

            // Spouse
            $table->boolean('living_with_spouse')->default(false);
            $table->string('spouse_last_name')->nullable();
            $table->string('spouse_first_name')->nullable();
            $table->string('spouse_middle_name')->nullable();
            $table->string('spouse_extension_name')->nullable();
            $table->date('spouse_birth_date')->nullable();

            // Children (18+)
            $table->boolean('living_with_children')->default(false);
            $table->unsignedTinyInteger('children_count')->default(0);
            $table->json('children')->nullable();

            // Relatives
            $table->boolean('living_with_relatives')->default(false);
            $table->unsignedTinyInteger('relatives_count')->default(0);
            $table->json('relatives')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beneficiaries');
    }
};
