<?php

use App\Http\Controllers\Admin\EmailEmployeeController;
use App\Http\Controllers\Admin\GrossSalesReportController;
use App\Http\Controllers\Admin\IncomeAnalysisReportController;
use App\Http\Controllers\Admin\LaborSummaryController;
use App\Http\Controllers\Admin\MaterialSummaryController;
use App\Http\Controllers\Admin\SalesReportController;
use App\Http\Controllers\Admin\TrendsController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Cashier\AppointmentController;
use App\Http\Controllers\Cashier\ARCashDepositController;
use App\Http\Controllers\Cashier\ExpensesController;
use App\Http\Controllers\Cashier\HistoryController;
use App\Http\Controllers\Cashier\HomeController;
use App\Http\Controllers\Cashier\InvoiceController;
use App\Http\Controllers\Cashier\QuotationController;
use App\Http\Controllers\Cashier\ServiceOrderController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\VehicleController;
use App\Http\Middleware\RoleMiddleware;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Root: authenticated users → their dashboard (avoids / ↔ /login loops with RedirectIfAuthenticated)
Route::get('/', function () {
    if (Auth::check()) {
        $user = Auth::user();

        if (! $user->hasValidStaffRole()) {
            Auth::logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();

            return redirect()->route('login');
        }

        return $user->normalizedRole() === User::ROLE_ADMIN
             ? redirect()->route('admin.home')
             : redirect()->route('cashier.home');
    }

    return redirect()->route('login');
});

/*
|--------------------------------------------------------------------------
| Register custom middleware alias
|--------------------------------------------------------------------------
*/
Route::aliasMiddleware('role', RoleMiddleware::class);

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [LoginController::class, 'login']);
});

Route::post('logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Admin Dashboard (only admin users)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        // Labor Summary
        Route::get('labor-summary', [LaborSummaryController::class, 'index'])
            ->name('labor-summary');
        Route::get('labor-summary/export/pdf', [LaborSummaryController::class, 'exportPDF'])
            ->name('labor-summary.export');

        // Material Summary
        Route::get('material-summary', [MaterialSummaryController::class, 'index'])
            ->name('material-summary');
        Route::get('material-summary/export/pdf', [MaterialSummaryController::class, 'exportPDF'])
            ->name('material-summary.export');

        // Trends
        Route::get('trends', [TrendsController::class, 'index'])
            ->name('trends');
        // Admin Dashboard
        Route::view('home', 'admin.home')->name('home');

        // Inventory page
        Route::get('inventory', [\App\Http\Controllers\Admin\InventoryController::class, 'index'])
            ->name('inventory');

        // if you want store/update/delete via admin too, add:
        Route::post('inventory', [\App\Http\Controllers\Admin\InventoryController::class, 'store'])->name('inventory.store');
        Route::put('inventory/{inventory}', [\App\Http\Controllers\Admin\InventoryController::class, 'update'])->name('inventory.update');
        Route::delete('inventory/{inventory}', [\App\Http\Controllers\Admin\InventoryController::class, 'destroy'])->name('inventory.destroy');

        // Invoice History
        Route::get('invoices', [\App\Http\Controllers\Admin\InvoiceHistoryController::class, 'index'])
            ->name('invoices');
        Route::get('invoices/{id}', [\App\Http\Controllers\Admin\InvoiceHistoryController::class, 'show'])
            ->name('invoices.view');
        Route::delete('invoices/{id}', [\App\Http\Controllers\Admin\InvoiceHistoryController::class, 'destroy'])
            ->name('invoices.destroy');

        // Sales Report
        Route::get('sales-report', [SalesReportController::class, 'index'])->name('sales-report');
        Route::get('sales-report/export', [SalesReportController::class, 'export'])->name('sales-report.export');

        // Gross Sales Report (FIXED)
        Route::get('gross-sales-report', [GrossSalesReportController::class, 'index'])->name('gross-sales-report');
        Route::get('gross-sales-report/export', [GrossSalesReportController::class, 'export'])->name('gross-sales-report.export');

        // Other Reports
        Route::view('income-analysis-report', 'admin.income-analysis-report')->name('income-analysis-report');
        Route::get('discount-report', [App\Http\Controllers\Admin\DiscountReportController::class, 'index'])->name('discount-report');

        Route::get('income-analysis-report', [IncomeAnalysisReportController::class, 'index'])->name('income-analysis-report');

        Route::get('email-employee', [EmailEmployeeController::class, 'index'])->name('email-employee');
        Route::post('user/store', [EmailEmployeeController::class, 'storeUser'])->name('user.store');
        Route::post('technician/store', [EmailEmployeeController::class, 'storeTechnician'])->name('technician.store');
        Route::put('user/{id}', [EmailEmployeeController::class, 'updateUser'])->name('user.update');
        Route::put('technician/{id}', [EmailEmployeeController::class, 'updateTechnician'])->name('technician.update');
        Route::delete('user/{id}', [EmailEmployeeController::class, 'destroyUser'])->name('user.delete');
        Route::delete('technician/{id}', [EmailEmployeeController::class, 'destroyTechnician'])->name('technician.delete');

    });
/*
|--------------------------------------------------------------------------
| Cashier Pages & CRUD (only cashier users)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:cashier'])
    ->prefix('cashier')
    ->name('cashier.')
    ->group(function () {

        Route::get('ajax/serviceorder/clients', [ServiceOrderController::class, 'ajaxClients'])
            ->name('serviceorder.ajax.clients');

        Route::get('ajax/quotation/clients', [QuotationController::class, 'ajaxSearch'])->name('quotation.ajax.clients');
        Route::get('ajax/quotation/parts', [QuotationController::class, 'ajaxParts'])->name('quotation.ajax.parts');

        // Dashboard
        Route::get('home', [HomeController::class, 'index'])->name('home');
        Route::get('dashboard', [HomeController::class, 'index'])->name('dashboard');

        // Appointment CRUD
        // Appointment CRUD
        Route::get('appointment', [AppointmentController::class, 'index'])->name('appointment.index');
        Route::get('appointment/create', [AppointmentController::class, 'create'])->name('appointment.create');
        Route::post('appointment', [AppointmentController::class, 'store'])->name('appointment.store');
        Route::get('appointment/{id}/edit', [AppointmentController::class, 'edit'])->name('appointment.edit');
        Route::put('appointment/{id}', [AppointmentController::class, 'update'])->name('appointment.update');
        Route::delete('appointment/{id}', [AppointmentController::class, 'destroy'])->name('appointment.destroy');
        Route::get('appointment/{id}/view', [AppointmentController::class, 'view'])->name('appointment.view');

        // Quotation CRUD
        Route::resource('quotation', QuotationController::class)
            ->except(['destroy']);
        Route::get('quotation/{id}/view', [QuotationController::class, 'view'])->name('quotation.view');

        // History
        Route::get('history', [HistoryController::class, 'index'])->name('history');
        Route::get('history/{id}/view', [HistoryController::class, 'view'])->name('history.view');

        // Service Order CRUD
        Route::resource('serviceorder', ServiceOrderController::class)
            ->except(['destroy', 'show'])
            ->names([
                'index' => 'serviceorder.index',
                'create' => 'serviceorder.create',
                'store' => 'serviceorder.store',
                'edit' => 'serviceorder.edit',
                'update' => 'serviceorder.update',
            ]);

        Route::delete('serviceorder/{id}', [ServiceOrderController::class, 'destroy'])
            ->name('serviceorder.destroy');
        Route::get('serviceorder/{id}/view', [ServiceOrderController::class, 'view'])->name('serviceorder.view');
        Route::get('service-order', [ServiceOrderController::class, 'index'])->name('service-order');

        // Invoice CRUD
        Route::resource('invoice', InvoiceController::class)
            ->except(['destroy', 'show'])
            ->names([
                'index' => 'invoice.index',
                'create' => 'invoice.create',
                'store' => 'invoice.store',
                'edit' => 'invoice.edit',
                'update' => 'invoice.update',
            ]);
        Route::get('invoice/{id}/view', [InvoiceController::class, 'view'])->name('invoice.view');
        Route::get('invoice/live-search', [InvoiceController::class, 'liveSearch'])->name('invoice.liveSearch');
        Route::view('invoice-blank', 'cashier.invoice-blank')->name('invoice-blank');

        Route::get('ajax/clients', [InvoiceController::class, 'ajaxClients'])->name('ajax.clients');
        Route::get('ajax/vehicles', [InvoiceController::class, 'ajaxVehicles'])->name('ajax.vehicles');
        Route::get('ajax/invoice/parts', [InvoiceController::class, 'ajaxParts'])->name('invoice.ajax.parts');

        // Inventory CRUD (static paths before {inventory})
        Route::get('inventory/stock-activity', [InventoryController::class, 'allStockMovements'])
            ->name('inventory.stock.activity');
        Route::resource('inventory', InventoryController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->names([
                'index' => 'inventory.index',
                'store' => 'inventory.store',
                'update' => 'inventory.update',
                'destroy' => 'inventory.destroy',
            ]);
        Route::post('inventory/{inventory}/stock-add', [InventoryController::class, 'addStock'])
            ->name('inventory.stock.add');
        Route::post('inventory/{inventory}/stock-remove', [InventoryController::class, 'removeStock'])
            ->name('inventory.stock.remove');
        Route::get('inventory/{inventory}/stock-movements', [InventoryController::class, 'stockMovements'])
            ->name('inventory.stock.movements');

        // Clients CRUD (AJAX)
        Route::resource('clients', ClientController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->names([
                'index' => 'clients.index',
                'store' => 'clients.store',
                'update' => 'clients.update',
                'destroy' => 'clients.destroy',
            ]);
        Route::get('clients/{id}/vehicles', [ClientController::class, 'vehicles']);

        // Vehicles CRUD (AJAX)
        Route::resource('vehicles', VehicleController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->names([
                'index' => 'vehicles.index',
                'store' => 'vehicles.store',
                'update' => 'vehicles.update',
                'destroy' => 'vehicles.destroy',
            ]);

        // Expenses
        Route::resource('expenses', ExpensesController::class)
            ->names([
                'index' => 'expenses.index',
                'create' => 'expenses.create',
                'store' => 'expenses.store',
                'edit' => 'expenses.edit',
                'update' => 'expenses.update',
                'destroy' => 'expenses.destroy',
            ]);

        // AR & Cash Deposit
        Route::get('ar-cashdeposit', [ARCashDepositController::class, 'index'])->name('ar-cashdeposit.index');
        Route::post('ar-cashdeposit/store-ar', [ARCashDepositController::class, 'storeAR'])->name('ar-cashdeposit.storeAR');
        Route::put('ar-cashdeposit/update-ar/{id}', [ARCashDepositController::class, 'updateAR'])->name('ar-cashdeposit.updateAR');
        Route::delete('ar-cashdeposit/destroy-ar/{id}', [ARCashDepositController::class, 'destroyAR'])->name('ar-cashdeposit.destroyAR');
        Route::post('ar-cashdeposit/store-cashdeposit', [ARCashDepositController::class, 'storeCashDeposit'])->name('ar-cashdeposit.storeCashDeposit');
        Route::put('ar-cashdeposit/update-cashdeposit/{id}', [ARCashDepositController::class, 'updateCashDeposit'])->name('ar-cashdeposit.updateCashDeposit');
        Route::delete('ar-cashdeposit/destroy-cashdeposit/{id}', [ARCashDepositController::class, 'destroyCashDeposit'])->name('ar-cashdeposit.destroyCashDeposit');

    });
