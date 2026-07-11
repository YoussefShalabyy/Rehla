<?php

namespace Tests\Feature\Media;

use App\Enums\UserRole;
use App\Models\Listing;
use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaServiceTest extends TestCase
{
    use RefreshDatabase;

    private User    $admin;
    private User    $customer;
    private Listing $listing;

    protected function setUp(): void
    {
        parent::setUp();

        if (!extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is not installed — skipping media upload tests.');
        }

        Storage::fake('local');

        $this->admin    = User::factory()->create(['role' => UserRole::Admin]);
        $this->customer = User::factory()->create(['role' => UserRole::Customer]);
        $this->listing  = Listing::factory()->create(['created_by' => $this->admin->id]);
    }

    public function test_admin_can_upload_image_to_listing(): void
    {
        $file = UploadedFile::fake()->image('photo.jpg', 100, 100);

        $response = $this->actingAs($this->admin)->postJson("/api/v1/admin/listings/{$this->listing->uuid}/media", [
            'file' => $file,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.is_primary', true);

        $this->assertDatabaseHas('media', [
            'entity_type' => 'listing',
            'entity_id'   => $this->listing->id,
            'is_primary'  => true,
        ]);
    }

    public function test_customer_cannot_upload_image_to_listing(): void
    {
        $file = UploadedFile::fake()->image('photo.jpg');

        $response = $this->actingAs($this->customer)->postJson("/api/v1/admin/listings/{$this->listing->uuid}/media", [
            'file' => $file,
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_set_primary_image(): void
    {
        // Upload 2 images
        $this->actingAs($this->admin)->postJson("/api/v1/admin/listings/{$this->listing->uuid}/media", ['file' => UploadedFile::fake()->image('photo1.jpg')]);
        $res2 = $this->actingAs($this->admin)->postJson("/api/v1/admin/listings/{$this->listing->uuid}/media", ['file' => UploadedFile::fake()->image('photo2.jpg')]);

        $uuid2 = $res2->json('data.uuid');

        $response = $this->actingAs($this->admin)->putJson("/api/v1/admin/media/{$uuid2}/primary");

        $response->assertStatus(200);

        $media2 = Media::where('uuid', $uuid2)->first();
        $this->assertTrue($media2->is_primary);
    }

    public function test_admin_can_delete_media(): void
    {
        $res  = $this->actingAs($this->admin)->postJson("/api/v1/admin/listings/{$this->listing->uuid}/media", ['file' => UploadedFile::fake()->image('photo1.jpg')]);
        $uuid = $res->json('data.uuid');

        $response = $this->actingAs($this->admin)->deleteJson("/api/v1/admin/media/{$uuid}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('media', ['uuid' => $uuid]);
    }

    public function test_admin_can_reorder_media(): void
    {
        $res1 = $this->actingAs($this->admin)->postJson("/api/v1/admin/listings/{$this->listing->uuid}/media", ['file' => UploadedFile::fake()->image('photo1.jpg')]);
        $res2 = $this->actingAs($this->admin)->postJson("/api/v1/admin/listings/{$this->listing->uuid}/media", ['file' => UploadedFile::fake()->image('photo2.jpg')]);

        $uuid1 = $res1->json('data.uuid');
        $uuid2 = $res2->json('data.uuid');

        $response = $this->actingAs($this->admin)->putJson("/api/v1/admin/listings/{$this->listing->uuid}/media/reorder", [
            'uuids' => [$uuid2, $uuid1],
        ]);

        $response->assertStatus(200);
    }
}
