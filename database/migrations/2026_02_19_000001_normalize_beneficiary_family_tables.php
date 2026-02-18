<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beneficiary_siblings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('beneficiary_id')->constrained()->cascadeOnDelete();
            $table->string('last_name');
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->date('birth_date');
            $table->timestamps();
        });

        Schema::create('beneficiary_children', function (Blueprint $table) {
            $table->id();
            $table->foreignId('beneficiary_id')->constrained()->cascadeOnDelete();
            $table->string('last_name');
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->date('birth_date');
            $table->timestamps();
        });

        Schema::create('beneficiary_relatives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('beneficiary_id')->constrained()->cascadeOnDelete();
            $table->string('last_name');
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->date('birth_date');
            $table->string('relationship');
            $table->timestamps();
        });

        // Migrate existing JSON data into new tables
        DB::table('beneficiaries')->orderBy('id')->chunk(100, function ($beneficiaries) {
            foreach ($beneficiaries as $row) {
                $siblings = json_decode($row->siblings, true) ?? [];
                foreach ($siblings as $sibling) {
                    DB::table('beneficiary_siblings')->insert([
                        'beneficiary_id' => $row->id,
                        'last_name' => $sibling['last_name'],
                        'first_name' => $sibling['first_name'],
                        'middle_name' => $sibling['middle_name'] ?? null,
                        'birth_date' => $sibling['birth_date'],
                        'created_at' => $row->created_at,
                        'updated_at' => $row->updated_at,
                    ]);
                }

                $children = json_decode($row->children, true) ?? [];
                foreach ($children as $child) {
                    DB::table('beneficiary_children')->insert([
                        'beneficiary_id' => $row->id,
                        'last_name' => $child['last_name'],
                        'first_name' => $child['first_name'],
                        'middle_name' => $child['middle_name'] ?? null,
                        'birth_date' => $child['birth_date'],
                        'created_at' => $row->created_at,
                        'updated_at' => $row->updated_at,
                    ]);
                }

                $relatives = json_decode($row->relatives, true) ?? [];
                foreach ($relatives as $relative) {
                    DB::table('beneficiary_relatives')->insert([
                        'beneficiary_id' => $row->id,
                        'last_name' => $relative['last_name'],
                        'first_name' => $relative['first_name'],
                        'middle_name' => $relative['middle_name'] ?? null,
                        'birth_date' => $relative['birth_date'],
                        'relationship' => $relative['relationship'],
                        'created_at' => $row->created_at,
                        'updated_at' => $row->updated_at,
                    ]);
                }
            }
        });

        // Drop old JSON columns and count columns
        Schema::table('beneficiaries', function (Blueprint $table) {
            $table->dropColumn(['siblings', 'siblings_count', 'children', 'children_count', 'relatives', 'relatives_count']);
        });

        // Add indexes for search
        Schema::table('beneficiaries', function (Blueprint $table) {
            $table->index(['last_name', 'first_name']);
            $table->index('municipality');
            $table->index('barangay');
        });
    }

    public function down(): void
    {
        Schema::table('beneficiaries', function (Blueprint $table) {
            $table->dropIndex(['last_name', 'first_name']);
            $table->dropIndex(['municipality']);
            $table->dropIndex(['barangay']);
        });

        Schema::table('beneficiaries', function (Blueprint $table) {
            $table->unsignedTinyInteger('siblings_count')->default(0);
            $table->json('siblings')->nullable();
            $table->unsignedTinyInteger('children_count')->default(0);
            $table->json('children')->nullable();
            $table->unsignedTinyInteger('relatives_count')->default(0);
            $table->json('relatives')->nullable();
        });

        Schema::dropIfExists('beneficiary_relatives');
        Schema::dropIfExists('beneficiary_children');
        Schema::dropIfExists('beneficiary_siblings');
    }
};
