<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_admin_can_list_users(): void
    {
        User::factory()->count(3)->create();
        $response = $this->actingAs($this->admin)->getJson('/users');
        $response->assertOk();
        $this->assertCount(4, $response->json());
    }

    public function test_admin_can_create_user(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/users', [
            'name' => 'New User',
            'email' => 'new@test.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'kasir',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', ['email' => 'new@test.com', 'role' => 'kasir']);
    }

    public function test_kasir_cannot_access_user_management(): void
    {
        $kasir = User::factory()->create(['role' => 'kasir']);
        $response = $this->actingAs($kasir)->getJson('/users');
        $response->assertStatus(403);
    }
}
