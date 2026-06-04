<?php

use App\Http\Controllers\AuthController;
use App\Livewire\Clients\ClientList;
use App\Livewire\Dashboard;
use App\Livewire\Notifications\NotificationList;
use App\Livewire\Reports\ReportsPage;
use App\Livewire\Settings\SettingsPage;
use App\Livewire\Shipments\ShipmentCreate;
use App\Livewire\Shipments\ShipmentList;
use App\Livewire\Shipments\ShipmentSearch;
use App\Livewire\Shipments\ShipmentShow;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::view('/snake', 'welcome')->name('snake');

// Auth
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Application
Route::middleware(['auth'])->prefix('app')->name('app.')->group(function () {
    Route::get('/',          Dashboard::class)->name('dashboard');
    Route::get('/shipments', ShipmentList::class)->name('shipments.index');
    Route::get('/shipments/new', ShipmentCreate::class)->name('shipments.create');
    Route::get('/shipments/search', ShipmentSearch::class)->name('shipments.search');
    Route::get('/shipments/{shipment}', ShipmentShow::class)->name('shipments.show');
    Route::get('/clients',   ClientList::class)->name('clients.index');
    // (also linked from Settings page)
    Route::get('/reports',       ReportsPage::class)->name('reports.index');
    Route::get('/settings',      SettingsPage::class)->name('settings.index');
    Route::get('/notifications', NotificationList::class)->name('notifications.index');
});
