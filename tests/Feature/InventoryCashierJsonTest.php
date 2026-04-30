<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\InventoryStockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InventoryCashierJsonTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_put_json_updates_inventory_metadata_without_changing_quantity(): void
    {
        $user = new User;
        $user->name = 'Cashier Tester';
        $user->email = 'cashier@test.local';
        $user->password = Hash::make('password');
        $user->role = 'cashier';
        $user->save();

        $inv = new Inventory([
            'item_name' => 'ewer',
            'part_number' => '334',
            'quantity' => 4,
            'selling' => '5454.00',
            'acquisition_price' => '55.00',
            'supplier' => 'grgd',
        ]);
        $inv->save();

        $response = $this->actingAs($user)->putJson("/cashier/inventory/{$inv->id}", [
            'item_name' => 'ewer-renamed',
            'part_number' => '334',
            'selling' => '5454.00',
            'acquisition_price' => '55.00',
            'supplier' => 'grgd',
        ]);

        $response->assertSuccessful();
        $response->assertJsonFragment(['item_name' => 'ewer-renamed', 'quantity' => 4]);
    }

    public function test_add_stock_creates_movement_and_increases_quantity(): void
    {
        $user = User::query()->create([
            'name' => 'Cashier A',
            'email' => 'c1@test.local',
            'password' => Hash::make('password'),
            'role' => 'cashier',
        ]);

        $inv = Inventory::query()->create([
            'item_name' => 'Bolt',
            'part_number' => 'B1',
            'quantity' => 10,
            'selling' => '10.00',
            'acquisition_price' => '5.00',
            'supplier' => 'S',
        ]);

        $res = $this->actingAs($user)->postJson("/cashier/inventory/{$inv->id}/stock-add", [
            'quantity' => 3,
            'note' => 'Delivery',
        ]);

        $res->assertSuccessful();
        $this->assertDatabaseHas('inventories', ['id' => $inv->id, 'quantity' => 13]);
        $this->assertDatabaseHas('inventory_stock_movements', [
            'inventory_id' => $inv->id,
            'user_id' => $user->id,
            'direction' => InventoryStockMovement::DIRECTION_ADD,
            'quantity' => 3,
            'quantity_before' => 10,
            'quantity_after' => 13,
            'note' => 'Delivery',
        ]);
    }

    public function test_remove_stock_requires_reason(): void
    {
        $user = User::query()->create([
            'name' => 'Cashier B',
            'email' => 'c2@test.local',
            'password' => Hash::make('password'),
            'role' => 'cashier',
        ]);

        $inv = Inventory::query()->create([
            'item_name' => 'Nut',
            'part_number' => 'N1',
            'quantity' => 5,
            'selling' => '1.00',
        ]);

        $this->actingAs($user)->postJson("/cashier/inventory/{$inv->id}/stock-remove", [
            'quantity' => 2,
        ])->assertUnprocessable();
    }

    public function test_remove_stock_cannot_exceed_available(): void
    {
        $user = User::query()->create([
            'name' => 'Cashier C',
            'email' => 'c3@test.local',
            'password' => Hash::make('password'),
            'role' => 'cashier',
        ]);

        $inv = Inventory::query()->create([
            'item_name' => 'Washer',
            'part_number' => 'W1',
            'quantity' => 2,
            'selling' => '1.00',
        ]);

        $this->actingAs($user)->postJson("/cashier/inventory/{$inv->id}/stock-remove", [
            'quantity' => 5,
            'reason' => 'Test',
        ])->assertUnprocessable();

        $this->assertDatabaseHas('inventories', ['id' => $inv->id, 'quantity' => 2]);
    }

    public function test_store_creates_initial_movement_when_quantity_positive(): void
    {
        $user = User::query()->create([
            'name' => 'Cashier D',
            'email' => 'c4@test.local',
            'password' => Hash::make('password'),
            'role' => 'cashier',
        ]);

        $this->actingAs($user)->postJson('/cashier/inventory', [
            'item_name' => 'New part',
            'part_number' => 'NP1',
            'quantity' => 7,
            'selling' => '100.00',
        ])->assertSuccessful();

        $inv = Inventory::query()->where('part_number', 'NP1')->firstOrFail();
        $this->assertSame(7, (int) $inv->quantity);
        $this->assertDatabaseHas('inventory_stock_movements', [
            'inventory_id' => $inv->id,
            'user_id' => $user->id,
            'direction' => InventoryStockMovement::DIRECTION_ADD,
            'quantity' => 7,
            'quantity_before' => 0,
            'quantity_after' => 7,
            'note' => 'Initial stock',
        ]);
    }

    public function test_stock_activity_log_returns_paginated_json(): void
    {
        $user = User::query()->create([
            'name' => 'Cashier E',
            'email' => 'c5@test.local',
            'password' => Hash::make('password'),
            'role' => 'cashier',
        ]);

        $inv = Inventory::query()->create([
            'item_name' => 'Filter',
            'part_number' => 'F1',
            'quantity' => 1,
            'selling' => '50.00',
        ]);

        InventoryStockMovement::query()->create([
            'inventory_id' => $inv->id,
            'user_id' => $user->id,
            'direction' => InventoryStockMovement::DIRECTION_ADD,
            'quantity' => 1,
            'quantity_before' => 0,
            'quantity_after' => 1,
            'reason' => null,
            'note' => 'Init',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($user)->getJson('/cashier/inventory/stock-activity');

        $response->assertSuccessful();
        $response->assertJsonStructure([
            'data' => [
                '*' => ['item_name', 'part_number', 'direction', 'user_name', 'quantity'],
            ],
            'current_page',
            'last_page',
        ]);
        $response->assertJsonFragment(['item_name' => 'Filter']);
    }

    public function test_stock_activity_log_search_filters_by_part_or_user(): void
    {
        $user = User::query()->create([
            'name' => 'Log Searcher',
            'email' => 'logsearch@test.local',
            'password' => Hash::make('password'),
            'role' => 'cashier',
        ]);

        $invAlpha = Inventory::query()->create([
            'item_name' => 'Alpha Widget',
            'part_number' => 'ALPHA-PN',
            'quantity' => 1,
            'selling' => '10.00',
        ]);
        $invBeta = Inventory::query()->create([
            'item_name' => 'Beta Bolt',
            'part_number' => 'BETA-PN',
            'quantity' => 2,
            'selling' => '11.00',
        ]);

        foreach ([$invAlpha, $invBeta] as $inv) {
            InventoryStockMovement::query()->create([
                'inventory_id' => $inv->id,
                'user_id' => $user->id,
                'direction' => InventoryStockMovement::DIRECTION_ADD,
                'quantity' => 1,
                'quantity_before' => 0,
                'quantity_after' => 1,
                'reason' => null,
                'note' => 'x',
                'created_at' => now(),
            ]);
        }

        $this->actingAs($user)->getJson('/cashier/inventory/stock-activity?q=BETA-PN')
            ->assertSuccessful()
            ->assertJsonFragment(['part_number' => 'BETA-PN'])
            ->assertJsonMissingPath('data.1');

        $this->actingAs($user)->getJson('/cashier/inventory/stock-activity?q='.urlencode('Log Searcher'))
            ->assertSuccessful()
            ->assertJsonFragment(['user_name' => 'Log Searcher']);
    }
}
