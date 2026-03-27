<?php

use App\Http\Controllers\CrudController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('{table}')->group(function () {
    Route::get('/', [CrudController::class, 'index'])->name('crud.index');
    Route::get('/create', [CrudController::class, 'create'])->name('crud.create');
    Route::post('/', [CrudController::class, 'store'])->name('crud.store');
    Route::get('/edit/{id}', [CrudController::class, 'edit'])->name('crud.edit');
    Route::post('/update/{id}', [CrudController::class, 'update'])->name('crud.update');
    Route::get('/delete/{id}', [CrudController::class, 'destroy'])->name('crud.destroy');
});

// email-check
Route::get('/email/verification', [CrudController::class, 'checkEmail'])->name('crud.checkEmail');
