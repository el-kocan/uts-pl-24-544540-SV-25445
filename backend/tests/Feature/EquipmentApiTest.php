<?php

namespace Tests\Feature;

use App\Models\Equipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EquipmentApiTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================
    // Helpers
    // =========================================================

    /**
     * Create a user and get a Sanctum token for them.
     */
    private function getAuthToken(): array
    {
        $user  = User::factory()->create([
            'email'    => 'admin@ugm.ac.id',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email'    => 'admin@ugm.ac.id',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)->assertJsonStructure(['token']);

        return [
            'user'  => $user,
            'token' => $response->json('token'),
        ];
    }

    /**
     * Make authenticated JSON request headers.
     */
    private function authHeaders(string $token): array
    {
        return [
            'Authorization' => "Bearer {$token}",
            'Accept'        => 'application/json',
        ];
    }

    // =========================================================
    // Test Group 1: Unauthenticated access returns 401
    // =========================================================

    /** @test */
    public function test_unauthenticated_access_to_equipments_returns_401(): void
    {
        // Access without token — should return 401
        $this->getJson('/api/equipments')
            ->assertStatus(401);

        $this->postJson('/api/equipments', [])
            ->assertStatus(401);
    }

    /** @test */
    public function test_access_without_accept_json_header_returns_json_401(): void
    {
        // /api/* routes must return JSON 401 even without Accept: application/json header
        // because of shouldRenderJsonWhen configuration in bootstrap/app.php
        $response = $this->withHeaders([
            'Accept' => 'text/html',
        ])->getJson('/api/equipments');

        $response->assertStatus(401);
        $this->assertStringContainsString('application/json', $response->headers->get('Content-Type'));
    }

    // =========================================================
    // Test Group 2: Login & Create (with validation)
    // =========================================================

    /** @test */
    public function test_login_with_valid_credentials_returns_200_with_token(): void
    {
        User::factory()->create([
            'email'    => 'admin@ugm.ac.id',
            'password' => bcrypt('password123'),
        ]);

        $this->postJson('/api/login', [
            'email'    => 'admin@ugm.ac.id',
            'password' => 'password123',
        ])->assertStatus(200)
          ->assertJsonStructure(['message', 'token', 'user']);
    }

    /** @test */
    public function test_login_with_invalid_credentials_returns_401(): void
    {
        User::factory()->create(['email' => 'admin@ugm.ac.id']);

        $this->postJson('/api/login', [
            'email'    => 'admin@ugm.ac.id',
            'password' => 'wrongpassword',
        ])->assertStatus(401);
    }

    /** @test */
    public function test_create_equipment_with_valid_data_returns_201(): void
    {
        ['token' => $token] = $this->getAuthToken();

        $this->postJson('/api/equipments', [
            'name'          => 'Mikroskop Olympus',
            'category'      => 'Optik',
            'serial_number' => 'OLY-2024-001',
            'condition'     => 'baik',
            'quantity'      => 3,
        ], $this->authHeaders($token))
            ->assertStatus(201)
            ->assertJsonStructure(['data' => ['id', 'name', 'serial_number']]);
    }

    /** @test */
    public function test_create_equipment_with_invalid_data_returns_422(): void
    {
        ['token' => $token] = $this->getAuthToken();

        // Missing required fields
        $this->postJson('/api/equipments', [
            'name' => '',
        ], $this->authHeaders($token))
            ->assertStatus(422)
            ->assertJsonStructure(['errors']);
    }

    /** @test */
    public function test_create_equipment_with_duplicate_serial_returns_422(): void
    {
        ['token' => $token] = $this->getAuthToken();

        Equipment::factory()->create(['serial_number' => 'DUPE-001']);

        $this->postJson('/api/equipments', [
            'name'          => 'Test Equipment',
            'category'      => 'Test',
            'serial_number' => 'DUPE-001',
            'condition'     => 'baik',
            'quantity'      => 1,
        ], $this->authHeaders($token))
            ->assertStatus(422);
    }

    /** @test */
    public function test_create_equipment_with_invalid_condition_returns_422(): void
    {
        ['token' => $token] = $this->getAuthToken();

        $this->postJson('/api/equipments', [
            'name'          => 'Test',
            'category'      => 'Test',
            'serial_number' => 'TEST-001',
            'condition'     => 'kondisi_tidak_valid',
            'quantity'      => 1,
        ], $this->authHeaders($token))
            ->assertStatus(422);
    }

    // =========================================================
    // Test Group 3: Read, Update, Delete
    // =========================================================

    /** @test */
    public function test_get_all_equipments_returns_200_with_list(): void
    {
        ['token' => $token] = $this->getAuthToken();

        Equipment::factory()->count(3)->create();

        $this->getJson('/api/equipments', $this->authHeaders($token))
            ->assertStatus(200)
            ->assertJsonStructure(['data']);
    }

    /** @test */
    public function test_get_single_equipment_returns_200(): void
    {
        ['token' => $token] = $this->getAuthToken();

        $equipment = Equipment::factory()->create();

        $this->getJson("/api/equipments/{$equipment->id}", $this->authHeaders($token))
            ->assertStatus(200)
            ->assertJsonPath('data.id', $equipment->id);
    }

    /** @test */
    public function test_get_nonexistent_equipment_returns_404(): void
    {
        ['token' => $token] = $this->getAuthToken();

        $this->getJson('/api/equipments/99999', $this->authHeaders($token))
            ->assertStatus(404);
    }

    /** @test */
    public function test_update_equipment_returns_200(): void
    {
        ['token' => $token] = $this->getAuthToken();

        $equipment = Equipment::factory()->create(['serial_number' => 'ORIG-001']);

        $this->putJson("/api/equipments/{$equipment->id}", [
            'name'          => 'Updated Name',
            'category'      => 'Updated Cat',
            'serial_number' => 'ORIG-001',
            'condition'     => 'rusak_ringan',
            'quantity'      => 10,
        ], $this->authHeaders($token))
            ->assertStatus(200)
            ->assertJsonPath('data.condition', 'rusak_ringan');
    }

    /** @test */
    public function test_update_equipment_with_invalid_data_returns_422(): void
    {
        ['token' => $token] = $this->getAuthToken();

        $equipment = Equipment::factory()->create();

        $this->putJson("/api/equipments/{$equipment->id}", [
            'name'     => '',
            'quantity' => -1,
        ], $this->authHeaders($token))
            ->assertStatus(422);
    }

    /** @test */
    public function test_delete_equipment_returns_200(): void
    {
        ['token' => $token] = $this->getAuthToken();

        $equipment = Equipment::factory()->create();

        $this->deleteJson("/api/equipments/{$equipment->id}", [], $this->authHeaders($token))
            ->assertStatus(200)
            ->assertJsonStructure(['message']);

        $this->assertDatabaseMissing('equipments', ['id' => $equipment->id]);
    }

    /** @test */
    public function test_logout_revokes_token_and_returns_200(): void
    {
        $user = User::factory()->create();

        // Create a real persisted token (not TransientToken)
        $tokenResult = $user->createToken('test-token');
        $plainToken  = $tokenResult->plainTextToken;
        $tokenId     = $tokenResult->accessToken->id;

        // Logout should succeed and return 200
        $this->withToken($plainToken)
            ->postJson('/api/logout')
            ->assertStatus(200)
            ->assertJsonStructure(['message']);

        // Verify token was actually deleted from database
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $tokenId]);
    }
}
