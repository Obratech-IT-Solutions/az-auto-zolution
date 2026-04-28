<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InventoryCashierJsonTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_put_json_updates_inventory(): void
    {
        $user = new User();
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
            'item_name' => 'ewer',
            'part_number' => '334',
            'quantity' => 4,
            'selling' => '5454.00',
            'acquisition_price' => '55.00',
            'supplier' => 'grgd',
        ]);

        $response->assertSuccessful();
        $response->assertJsonFragment(['item_name' => 'ewer']);
    }
}
