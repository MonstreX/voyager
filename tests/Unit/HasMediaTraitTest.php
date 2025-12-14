<?php

namespace TCG\Voyager\Tests\Unit;

use Illuminate\Http\UploadedFile;
use TCG\Voyager\Models\Media;
use TCG\Voyager\Models\Post;
use TCG\Voyager\Models\Role;
use TCG\Voyager\Models\User;
use TCG\Voyager\Services\MediaService;
use TCG\Voyager\Tests\TestCase;
use TCG\Voyager\Traits\HasMedia;

class HasMediaTraitTest extends TestCase
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

    public function testPostCanHaveMedia()
    {
        $post = Post::create([
            'title' => 'Test Post',
            'slug' => 'test-post',
            'body' => 'Test body',
        ]);

        $this->assertTrue(method_exists($post, 'media'));
        $this->assertTrue(method_exists($post, 'getMedia'));
        $this->assertTrue(method_exists($post, 'getFirstMedia'));
    }

    public function testGetMediaReturnsCollection()
    {
        $post = Post::create([
            'title' => 'Test Post',
            'slug' => 'test-post',
            'body' => 'Test body',
        ]);

        $file1 = UploadedFile::fake()->create('test1.bin', 10, 'application/octet-stream');
        $file2 = UploadedFile::fake()->create('test2.bin', 10, 'application/octet-stream');

        $this->mediaService->createFromFile($post, $file1, 'gallery');
        $this->mediaService->createFromFile($post, $file2, 'gallery');

        $media = $post->getMedia('gallery');

        $this->assertCount(2, $media);
    }

    public function testGetMediaFiltersByCollection()
    {
        $post = Post::create([
            'title' => 'Test Post',
            'slug' => 'test-post',
            'body' => 'Test body',
        ]);

        $file1 = UploadedFile::fake()->create('featured.bin', 10, 'application/octet-stream');
        $file2 = UploadedFile::fake()->create('gallery.bin', 10, 'application/octet-stream');

        $this->mediaService->createFromFile($post, $file1, 'featured');
        $this->mediaService->createFromFile($post, $file2, 'gallery');

        $featured = $post->getMedia('featured');
        $gallery = $post->getMedia('gallery');

        $this->assertCount(1, $featured);
        $this->assertCount(1, $gallery);
    }

    public function testGetFirstMediaReturnsFirstItem()
    {
        $post = Post::create([
            'title' => 'Test Post',
            'slug' => 'test-post',
            'body' => 'Test body',
        ]);

        $file1 = UploadedFile::fake()->create('test1.bin', 10, 'application/octet-stream');
        $file2 = UploadedFile::fake()->create('test2.bin', 10, 'application/octet-stream');

        $media1 = $this->mediaService->createFromFile($post, $file1, 'gallery');
        $this->mediaService->createFromFile($post, $file2, 'gallery');

        $first = $post->getFirstMedia('gallery');

        $this->assertEquals($media1->id, $first->id);
    }

    public function testGetFirstMediaReturnsNull()
    {
        $post = Post::create([
            'title' => 'Test Post',
            'slug' => 'test-post',
            'body' => 'Test body',
        ]);

        $first = $post->getFirstMedia('nonexistent');

        $this->assertNull($first);
    }

    public function testGetFirstMediaUrlReturnsUrl()
    {
        $post = Post::create([
            'title' => 'Test Post',
            'slug' => 'test-post',
            'body' => 'Test body',
        ]);

        $file = UploadedFile::fake()->create('test.bin', 10, 'application/octet-stream');
        $this->mediaService->createFromFile($post, $file, 'featured');

        $url = $post->getFirstMediaUrl('featured');

        $this->assertNotNull($url);
        $this->assertStringContainsString('storage', $url);
    }

    public function testGetFirstMediaUrlReturnsFallback()
    {
        $post = Post::create([
            'title' => 'Test Post',
            'slug' => 'test-post',
            'body' => 'Test body',
        ]);

        $url = $post->getFirstMediaUrl('nonexistent', 'https://example.com/fallback.jpg');

        $this->assertEquals('https://example.com/fallback.jpg', $url);
    }

    public function testMediaRelationship()
    {
        $post = Post::create([
            'title' => 'Test Post',
            'slug' => 'test-post',
            'body' => 'Test body',
        ]);

        $file = UploadedFile::fake()->image('test.jpg');
        $this->mediaService->createFromFile($post, $file, 'featured');

        $media = $post->media()->first();

        $this->assertInstanceOf(Media::class, $media);
        $this->assertEquals($post->id, $media->model_id);
        $this->assertEquals(Post::class, $media->model_type);
    }

    public function testMediaOrderedByOrder()
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

        $media = $post->getMedia('gallery');

        $this->assertEquals($media1->id, $media[0]->id);
        $this->assertEquals($media2->id, $media[1]->id);
        $this->assertEquals($media3->id, $media[2]->id);
    }

    public function testDeletingModelRemovesMediaAndFiles()
    {
        $post = Post::create([
            'title' => 'Test Post',
            'slug' => 'test-post',
            'body' => 'Test body',
        ]);

        $file = UploadedFile::fake()->create('test-delete.bin', 10, 'application/octet-stream');
        $media = $this->mediaService->createFromFile($post, $file, 'featured');

        $this->assertTrue(\Illuminate\Support\Facades\Storage::disk('public')->exists($media->path));

        $post->delete();

        $this->assertDatabaseMissing('media', ['id' => $media->id]);
        $this->assertFalse(\Illuminate\Support\Facades\Storage::disk('public')->exists($media->path));
    }
}
