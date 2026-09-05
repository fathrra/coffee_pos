<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_get_settings_returns_defaults_when_table_is_empty(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'kasir']))
            ->getJson('/settings')
            ->assertOk()
            ->assertJson([
                'store_name' => 'CoffeePOS',
                'store_address' => '',
                'receipt_footer' => 'Terima kasih atas kunjungan Anda!',
                'default_tax' => 0,
            ]);
    }

    public function test_admin_updates_settings_and_values_are_persisted(): void
    {
        $this->actingAs($this->admin)->postJson('/settings', [
            'store_name' => 'Kopi Senja',
            'store_address' => 'Jl. Melati No. 10',
            'receipt_footer' => 'Sampai jumpa!',
            'default_tax' => 11,
        ])->assertOk();

        $this->assertSame('Kopi Senja', Setting::get('store_name'));
        $this->assertSame('Jl. Melati No. 10', Setting::get('store_address'));
        $this->assertSame('Sampai jumpa!', Setting::get('receipt_footer'));
        $this->assertSame('11', Setting::get('default_tax'));

        $this->actingAs($this->admin)->getJson('/settings')
            ->assertOk()
            ->assertJson([
                'store_name' => 'Kopi Senja',
                'store_address' => 'Jl. Melati No. 10',
                'receipt_footer' => 'Sampai jumpa!',
                'default_tax' => '11',
            ]);
    }

    public function test_kasir_cannot_update_settings_but_can_read_them(): void
    {
        $kasir = User::factory()->create(['role' => 'kasir']);

        $this->actingAs($kasir)->postJson('/settings', [
            'store_name' => 'Hacked',
            'store_address' => '',
            'receipt_footer' => '',
            'default_tax' => 0,
        ])->assertStatus(403);

        $this->assertSame(0, Setting::count());

        $this->actingAs($kasir)->getJson('/settings')->assertOk();
    }

    public function test_updating_settings_requires_store_name_and_valid_tax(): void
    {
        $this->actingAs($this->admin)->postJson('/settings', [
            'store_name' => '',
            'store_address' => '',
            'receipt_footer' => '',
            'default_tax' => 150,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['store_name', 'default_tax']);
    }
}
