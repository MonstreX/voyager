<?php

namespace TCG\Voyager\Tests\Feature;

use Illuminate\Http\UploadedFile;
use TCG\Voyager\Models\Media;
use TCG\Voyager\Models\Post;
use TCG\Voyager\Models\Role;
use TCG\Voyager\Models\User;
use TCG\Voyager\Services\MediaService;
use TCG\Voyager\Tests\TestCase;

class MediaControllerTest extends TestCase
{
    protected $mediaService;
    protected $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->mediaService = new MediaService();

        $role = Role::first() ?: Role::create([
            'name' => 'admin',
            'display_name' => 'Administrator',
        ]);

        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
        ]);

        $this->actingAs($this->user);
    }

    public function testUploadImageEndpoint()
    {
        $post = Post::create([
            'title' => 'Test Post',
            'slug' => 'test-post',
            'body' => 'Test body',
        ]);

        $file = UploadedFile::fake()->create('test.bin', 10, 'application/octet-stream');

        $response = $this->post('/admin/api/media/upload', [
            'file' => $file,
            'model_type' => Post::class,
            'model_id' => $post->id,
            'collection_name' => 'featured',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['status', 'media']);
        $this->assertEquals('success', $response->json('status'));
    }

    public function testUploadCreatesMediaRecord()
    {
        $post = Post::create([
            'title' => 'Test Post',
            'slug' => 'test-post',
            'body' => 'Test body',
        ]);

        $file = UploadedFile::fake()->create('test.bin', 10, 'application/octet-stream');

        $response = $this->post('/admin/api/media/upload', [
            'file' => $file,
            'model_type' => Post::class,
            'model_id' => $post->id,
            'collection_name' => 'featured',
        ]);

        $this->assertDatabaseHas('media', [
            'model_type' => Post::class,
            'model_id' => $post->id,
            'collection_name' => 'featured',
        ]);
    }

    public function testDeleteMediaEndpoint()
    {
        $post = Post::create([
            'title' => 'Test Post',
            'slug' => 'test-post',
            'body' => 'Test body',
        ]);

        $file = UploadedFile::fake()->create('test.bin', 10, 'application/octet-stream');
        $media = $this->mediaService->createFromFile($post, $file, 'featured');

        $response = $this->delete("/admin/api/media/{$media->id}");

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);
    }

    public function testDeleteRemovesMediaRecord()
    {
        $post = Post::create([
            'title' => 'Test Post',
            'slug' => 'test-post',
            'body' => 'Test body',
        ]);

        $file = UploadedFile::fake()->create('test.jpg', 10, 'image/jpeg');
        $media = $this->mediaService->createFromFile($post, $file, 'featured');

        $mediaId = $media->id;

        $this->delete("/admin/api/media/{$media->id}");

        $this->assertDatabaseMissing('media', [
            'id' => $mediaId,
        ]);
    }

    public function testUpdatePropsEndpoint()
    {
        $post = Post::create([
            'title' => 'Test Post',
            'slug' => 'test-post',
            'body' => 'Test body',
        ]);

        $file = UploadedFile::fake()->image('test.jpg');
        $media = $this->mediaService->createFromFile($post, $file, 'featured');

        $response = $this->post("/admin/api/media/{$media->id}/props", [
            'props' => [
                'title' => 'New Title',
                'alt' => 'New Alt',
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);
    }

    public function testUpdatePropsUpdatesDatabase()
    {
        $post = Post::create([
            'title' => 'Test Post',
            'slug' => 'test-post',
            'body' => 'Test body',
        ]);

        $file = UploadedFile::fake()->image('test.jpg');
        $media = $this->mediaService->createFromFile($post, $file, 'featured');

        $this->post("/admin/api/media/{$media->id}/props", [
            'props' => [
                'title' => 'New Title',
                'alt' => 'New Alt',
            ],
        ]);

        $media->refresh();

        $this->assertEquals('New Title', $media->prop('title'));
        $this->assertEquals('New Alt', $media->prop('alt'));
    }

    public function testReorderEndpoint()
    {
        $post = Post::create([
            'title' => 'Test Post',
            'slug' => 'test-post',
            'body' => 'Test body',
        ]);

        $file1 = UploadedFile::fake()->create('test1.jpg', 10, 'image/jpeg');
        $file2 = UploadedFile::fake()->create('test2.jpg', 10, 'image/jpeg');

        $media1 = $this->mediaService->createFromFile($post, $file1, 'gallery');
        $media2 = $this->mediaService->createFromFile($post, $file2, 'gallery');

        $response = $this->post('/admin/api/media/reorder', [
            'model_type' => Post::class,
            'model_id' => $post->id,
            'collection_name' => 'gallery',
            'order' => [$media2->id, $media1->id],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);
    }

    public function testReorderUpdatesOrder()
    {
        $post = Post::create([
            'title' => 'Test Post',
            'slug' => 'test-post',
            'body' => 'Test body',
        ]);

        $file1 = UploadedFile::fake()->create('test1.jpg', 10, 'image/jpeg');
        $file2 = UploadedFile::fake()->create('test2.jpg', 10, 'image/jpeg');

        $media1 = $this->mediaService->createFromFile($post, $file1, 'gallery');
        $media2 = $this->mediaService->createFromFile($post, $file2, 'gallery');

        $this->post('/admin/api/media/reorder', [
            'model_type' => Post::class,
            'model_id' => $post->id,
            'collection_name' => 'gallery',
            'order' => [$media2->id, $media1->id],
        ]);

        $media1->refresh();
        $media2->refresh();

        $this->assertEquals(1, $media1->order);
        $this->assertEquals(0, $media2->order);
    }

    public function testUploadWithoutFile()
    {
        $post = Post::create([
            'title' => 'Test Post',
            'slug' => 'test-post',
            'body' => 'Test body',
        ]);

        $response = $this->post('/admin/api/media/upload', [
            'model_type' => Post::class,
            'model_id' => $post->id,
        ]);

        $response->assertStatus(422);
    }

    public function testUploadWithInvalidModel()
    {
        $file = UploadedFile::fake()->image('test.jpg');

        $response = $this->post('/admin/api/media/upload', [
            'file' => $file,
            'model_type' => 'InvalidModel',
            'model_id' => 999,
        ]);

        $response->assertStatus(400);
    }
}
