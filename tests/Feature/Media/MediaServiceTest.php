<?php

namespace Tests\Feature\Media;

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

    private User $owner;
    private User $stranger;
    private Listing $listing;

    protected function setUp(): void
    {
        parent::setUp();
        
        Storage::fake('local');
        
        $this->owner = User::factory()->create(['role' => 'provider']);
        $this->stranger = User::factory()->create(['role' => 'provider']);
        $this->listing = Listing::factory()->create(['owner_id' => $this->owner->id]);
    }

    public function test_owner_can_upload_image_to_their_listing()
    {
        $file = UploadedFile::fake()->image('photo.jpg', 100, 100);

        $response = $this->actingAs($this->owner)->postJson("/api/v1/owner/listings/{$this->listing->uuid}/media", [
            'file' => $file,
        ]);

        $response->assertStatus(201)
                 ->assertJsonPath('data.is_primary', true)
                 ->assertJsonPath('data.order', 1);

        $this->assertDatabaseHas('media', [
            'entity_type' => 'listing',
            'entity_id' => $this->listing->id,
            'is_primary' => true,
            'order' => 1,
        ]);
    }

    public function test_stranger_cannot_upload_image_to_someone_elses_listing()
    {
        $file = UploadedFile::fake()->image('photo.jpg');

        $response = $this->actingAs($this->stranger)->postJson("/api/v1/owner/listings/{$this->listing->uuid}/media", [
            'file' => $file,
        ]);

        $response->assertStatus(403);
    }

    public function test_second_uploaded_image_is_not_primary()
    {
        $file1 = UploadedFile::fake()->image('photo1.jpg');
        $this->actingAs($this->owner)->postJson("/api/v1/owner/listings/{$this->listing->uuid}/media", ['file' => $file1]);

        $file2 = UploadedFile::fake()->image('photo2.jpg');
        $response = $this->actingAs($this->owner)->postJson("/api/v1/owner/listings/{$this->listing->uuid}/media", ['file' => $file2]);

        $response->assertStatus(201)
                 ->assertJsonPath('data.is_primary', false)
                 ->assertJsonPath('data.order', 2);
    }

    public function test_owner_can_set_primary_image()
    {
        // Upload 2 images
        $this->actingAs($this->owner)->postJson("/api/v1/owner/listings/{$this->listing->uuid}/media", ['file' => UploadedFile::fake()->image('photo1.jpg')]);
        $res2 = $this->actingAs($this->owner)->postJson("/api/v1/owner/listings/{$this->listing->uuid}/media", ['file' => UploadedFile::fake()->image('photo2.jpg')]);

        $uuid2 = $res2->json('data.uuid');

        // Set second image as primary
        $response = $this->actingAs($this->owner)->putJson("/api/v1/owner/media/{$uuid2}/primary");

        $response->assertStatus(200);

        // Assert only the second is primary
        $media2 = Media::where('uuid', $uuid2)->first();
        $this->assertTrue($media2->is_primary);

        $media1 = Media::where('uuid', '!=', $uuid2)->first();
        $this->assertFalse($media1->is_primary);
    }

    public function test_owner_can_delete_media()
    {
        $res = $this->actingAs($this->owner)->postJson("/api/v1/owner/listings/{$this->listing->uuid}/media", ['file' => UploadedFile::fake()->image('photo1.jpg')]);
        $uuid = $res->json('data.uuid');

        $response = $this->actingAs($this->owner)->deleteJson("/api/v1/owner/media/{$uuid}");
        
        $response->assertStatus(200);
        $this->assertDatabaseMissing('media', ['uuid' => $uuid]);
    }

    public function test_owner_can_reorder_media()
    {
        $res1 = $this->actingAs($this->owner)->postJson("/api/v1/owner/listings/{$this->listing->uuid}/media", ['file' => UploadedFile::fake()->image('photo1.jpg')]);
        $res2 = $this->actingAs($this->owner)->postJson("/api/v1/owner/listings/{$this->listing->uuid}/media", ['file' => UploadedFile::fake()->image('photo2.jpg')]);

        $uuid1 = $res1->json('data.uuid');
        $uuid2 = $res2->json('data.uuid');

        $response = $this->actingAs($this->owner)->putJson("/api/v1/owner/listings/{$this->listing->uuid}/media/reorder", [
            'uuids' => [$uuid2, $uuid1] // reverse order
        ]);

        $response->assertStatus(200);

        $this->assertEquals(2, Media::where('uuid', $uuid1)->first()->order);
        $this->assertEquals(1, Media::where('uuid', $uuid2)->first()->order);
    }
}
