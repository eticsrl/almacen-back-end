<?php

namespace Tests\Feature;

use App\Models\ServicePersonal;
use App\Models\DocumentType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServicePersonalTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /**
     * Test listing all service personals
     */
    public function test_can_list_service_personals(): void
    {
        ServicePersonal::factory()->count(5)->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/servicePersonals');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'message'
            ]);
    }

    /**
     * Test creating a service personal
     */
    public function test_can_create_service_personal(): void
    {
        $data = [
            'apellidos_nombres' => 'Juan Pérez',
            'estado' => true,
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/servicePersonals', $data);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data',
                'message'
            ]);

        $this->assertDatabaseHas('service_personals', ['apellidos_nombres' => 'JUAN PÉREZ']);
    }

    /**
     * Test validation on create
     */
    public function test_create_service_personal_requires_apellidos_nombres(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/servicePersonals', [
                'estado' => true,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['apellidos_nombres']);
    }

    /**
     * Test showing a service personal
     */
    public function test_can_show_service_personal(): void
    {
        $servicePersonal = ServicePersonal::factory()->create();

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/servicePersonals/{$servicePersonal->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'message'
            ]);
    }

    /**
     * Test updating a service personal
     */
    public function test_can_update_service_personal(): void
    {
        $servicePersonal = ServicePersonal::factory()->create();

        $data = [
            'apellidos_nombres' => 'Pedro López',
            'estado' => false,
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/servicePersonals/{$servicePersonal->id}", $data);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'message'
            ]);

        $this->assertDatabaseHas('service_personals', [
            'id' => $servicePersonal->id,
            'estado' => false,
        ]);
    }

    /**
     * Test deleting a service personal
     */
    public function test_can_delete_service_personal(): void
    {
        $servicePersonal = ServicePersonal::factory()->create();

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/servicePersonals/{$servicePersonal->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertSoftDeleted('service_personals', ['id' => $servicePersonal->id]);
    }

    /**
     * Test service personal has correct relationships
     */
    public function test_service_personal_has_document_type(): void
    {
        $documentType = DocumentType::factory()->create();
        $servicePersonal = ServicePersonal::factory()->create([
            'id_service' => $documentType->id,
        ]);

        $this->assertNotNull($servicePersonal->documentType);
        $this->assertEquals($documentType->id, $servicePersonal->documentType->id);
    }
}
