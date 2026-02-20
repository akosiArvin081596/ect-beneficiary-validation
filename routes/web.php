<?php

use App\Http\Controllers\BeneficiaryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DataCleansingController;
use App\Http\Controllers\MasterlistController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

Route::get('dashboard', DashboardController::class)->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('beneficiaries', [BeneficiaryController::class, 'index'])->name('beneficiaries.index');
    Route::get('beneficiaries/create', [BeneficiaryController::class, 'create'])->name('beneficiaries.create');
    Route::post('beneficiaries/offline-sync', [BeneficiaryController::class, 'offlineSync'])->name('beneficiaries.offline-sync');
    Route::post('beneficiaries', [BeneficiaryController::class, 'store'])->name('beneficiaries.store');
});

Route::middleware(['auth', 'verified', 'admin'])->group(function () {
    Route::get('data-cleansing', [DataCleansingController::class, 'index'])->name('data-cleansing.index');
    Route::delete('data-cleansing/{beneficiary}', [DataCleansingController::class, 'destroy'])->name('data-cleansing.destroy');
    Route::post('data-cleansing/merge', [DataCleansingController::class, 'merge'])->name('data-cleansing.merge');
    Route::post('data-cleansing/merge-all', [DataCleansingController::class, 'mergeAll'])->name('data-cleansing.merge-all');

    Route::get('masterlist', [MasterlistController::class, 'index'])->name('masterlist.index');
    Route::get('masterlist/export-csv', [MasterlistController::class, 'exportCsv'])->name('masterlist.export-csv');
    Route::get('masterlist/{beneficiary}', [MasterlistController::class, 'show'])->name('masterlist.show');
});

require __DIR__.'/settings.php';
