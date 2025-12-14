<?php

namespace TCG\Voyager\Tests\Unit;

use Illuminate\Http\UploadedFile;
use TCG\Voyager\Models\Media;
use TCG\Voyager\Models\Post;
use TCG\Voyager\Models\Role;
use TCG\Voyager\Models\User;
use TCG\Voyager\Services\MediaService;
use TCG\Voyager\Tests\TestCase;

class MediaServiceTest extends TestCase
{
    protected $mediaService;
    protected $user;

    public function setUp(): void
    {
        parent::setUp();

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

        $this->mediaService = new MediaService();
    }

    public function testCreateMediaFromFile()
    {
        $post = Post::create([
            'title' => 'Test Post',
            'slug' => 'test-post',
            'body' => 'Test body',
        ]);

        $file = UploadedFile::fake()->create('test.bin', 10, 'application/octet-stream');

        $media = $this->mediaService->createFromFile($post, $file, 'featured');

        $this->assertNotNull($media->id);
        $this->assertEquals($post->id, $media->model_id);
        $this->assertEquals(Post::class, $media->model_type);
        $this->assertEquals('featured', $media->collection_name);
        $this->assertEquals('test.bin', $media->file_name);
    }

    public function testDeleteMedia()
    {
        $post = Post::create([
            'title' => 'Test Post',
            'slug' => 'test-post',
            'body' => 'Test body',
        ]);

        $file = UploadedFile::fake()->create('test.bin', 10, 'application/octet-stream');
        $media = $this->mediaService->createFromFile($post, $file, 'featured');

        $mediaId = $media->id;

        $this->mediaService->deleteMedia($media);

        $this->assertNull(Media::find($mediaId));
    }

    public function testUpdateMediaProps()
    {
        $post = Post::create([
            'title' => 'Test Post',
            'slug' => 'test-post',
            'body' => 'Test body',
        ]);

        $file = UploadedFile::fake()->create('test.bin', 10, 'application/octet-stream');
        $media = $this->mediaService->createFromFile($post, $file, 'featured');

        $this->mediaService->updateMediaProps($media, [
            'title' => 'My Image Title',
            'alt' => 'My Image Alt Text',
        ]);

        $media->refresh();

        $this->assertEquals('My Image Title', $media->prop('title'));
        $this->assertEquals('My Image Alt Text', $media->prop('alt'));
    }

    public function testReorderCollection()
    {
        $post = Post::create([
            'title' => 'Test Post',
            'slug' => 'test-post',
            'body' => 'Test body',
        ]);

        $file1 = UploadedFile::fake()->create('test1.bin', 10, 'application/octet-stream');
        $file2 = UploadedFile::fake()->create('test2.bin', 10, 'application/octet-stream');
        $file3 = UploadedFile::fake()->create('test3.bin', 10, 'application/octet-stream');

        $media1 = $this->mediaService->createFromFile($post, $file1, 'gallery');
        $media2 = $this->mediaService->createFromFile($post, $file2, 'gallery');
        $media3 = $this->mediaService->createFromFile($post, $file3, 'gallery');

        $this->mediaService->reorderCollection($post, 'gallery', [
            $media3->id,
            $media1->id,
            $media2->id,
        ]);

        $media1->refresh();
        $media2->refresh();
        $media3->refresh();

        $this->assertEquals(1, $media1->order);
        $this->assertEquals(2, $media2->order);
        $this->assertEquals(0, $media3->order);
    }

    public function testGetMediaOrder()
    {
        $post = Post::create([
            'title' => 'Test Post',
            'slug' => 'test-post',
            'body' => 'Test body',
        ]);

        $file1 = UploadedFile::fake()->create('test1.bin', 10, 'application/octet-stream');
        $file2 = UploadedFile::fake()->create('test2.bin', 10, 'application/octet-stream');

        $media1 = $this->mediaService->createFromFile($post, $file1, 'gallery');
        $media2 = $this->mediaService->createFromFile($post, $file2, 'gallery');

        $this->assertEquals(0, $media1->order);
        $this->assertEquals(1, $media2->order);
    }

    public function testMultipleCollections()
    {
        $post = Post::create([
            'title' => 'Test Post',
            'slug' => 'test-post',
            'body' => 'Test body',
        ]);

        $file1 = UploadedFile::fake()->create('featured.bin', 10, 'application/octet-stream');
        $file2 = UploadedFile::fake()->create('gallery1.bin', 10, 'application/octet-stream');
        $file3 = UploadedFile::fake()->create('gallery2.bin', 10, 'application/octet-stream');

        $featured = $this->mediaService->createFromFile($post, $file1, 'featured');
        $gallery1 = $this->mediaService->createFromFile($post, $file2, 'gallery');
        $gallery2 = $this->mediaService->createFromFile($post, $file3, 'gallery');

        $this->assertEquals(0, $featured->order);
        $this->assertEquals(0, $gallery1->order);
        $this->assertEquals(1, $gallery2->order);
    }
}
