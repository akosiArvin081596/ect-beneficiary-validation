<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('beneficiaries', function (Blueprint $table) {
            $table->enum('civil_status', ['Single', 'Married', 'Common Law', 'Widowed', 'Separated', 'Annulled'])->change();
        });
    }

    public function down(): void
    {
        Schema::table('beneficiaries', function (Blueprint $table) {
            $table->enum('civil_status', ['Single', 'Married', 'Widowed', 'Separated', 'Annulled'])->change();
        });
    }
};
