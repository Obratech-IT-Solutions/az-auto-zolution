<?php

namespace App\Providers;

use App\Models\Inventory;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Authenticate::redirectUsing(fn () => route('login'));

        RedirectIfAuthenticated::redirectUsing(function () {
            $user = Auth::user();
            $isAdmin = $user && (($user->role ?? '') === 'admin');

            return route($isAdmin ? 'admin.home' : 'cashier.home');
        });

        Paginator::useBootstrap();
        // Low-stock alert: total count + capped preview (avoid loading thousands of rows on every view)
        $previewLimit = 200;
        View::composer('*', function ($view) use ($previewLimit) {
            $lowStockCount = Inventory::query()->where('quantity', '<=', 5)->count();
            $lowStockItems = $lowStockCount === 0
                ? collect()
                : Inventory::query()
                    ->where('quantity', '<=', 5)
                    ->orderBy('quantity')
                    ->limit($previewLimit)
                    ->get(['id', 'item_name', 'quantity']);
            $view->with('lowStockCount', $lowStockCount);
            $view->with('lowStockItems', $lowStockItems);
        });
    }
}
