<?php

use App\Http\Controllers\BeneficiaryController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('beneficiaries', [BeneficiaryController::class, 'index'])->name('beneficiaries.index');
    Route::get('beneficiaries/create', [BeneficiaryController::class, 'create'])->name('beneficiaries.create');
    Route::post('beneficiaries/offline-sync', [BeneficiaryController::class, 'offlineSync'])->name('beneficiaries.offline-sync');
    Route::post('beneficiaries', [BeneficiaryController::class, 'store'])->name('beneficiaries.store');
});

require __DIR__.'/settings.php';
