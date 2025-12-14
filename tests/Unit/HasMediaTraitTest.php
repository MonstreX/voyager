<?php

namespace TCG\Voyager\Tests\Unit;

use Illuminate\Http\UploadedFile;
use TCG\Voyager\Models\Media;
use TCG\Voyager\Models\Post;
use TCG\Voyager\Services\MediaService;
use TCG\Voyager\Tests\TestCase;
use TCG\Voyager\Traits\HasMedia;

class HasMediaTraitTest extends TestCase
{
    protected $mediaService;

    public function setUp(): void
    {
        parent::setUp();
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

        $file1 = UploadedFile::fake()->image('test1.jpg');
        $file2 = UploadedFile::fake()->image('test2.jpg');

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

        $file1 = UploadedFile::fake()->image('featured.jpg');
        $file2 = UploadedFile::fake()->image('gallery.jpg');

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

        $file1 = UploadedFile::fake()->image('test1.jpg');
        $file2 = UploadedFile::fake()->image('test2.jpg');

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

        $file = UploadedFile::fake()->image('test.jpg');
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

        $file1 = UploadedFile::fake()->image('test1.jpg');
        $file2 = UploadedFile::fake()->image('test2.jpg');
        $file3 = UploadedFile::fake()->image('test3.jpg');

        $media1 = $this->mediaService->createFromFile($post, $file1, 'gallery');
        $media2 = $this->mediaService->createFromFile($post, $file2, 'gallery');
        $media3 = $this->mediaService->createFromFile($post, $file3, 'gallery');

        $media = $post->getMedia('gallery');

        $this->assertEquals($media1->id, $media[0]->id);
        $this->assertEquals($media2->id, $media[1]->id);
        $this->assertEquals($media3->id, $media[2]->id);
    }
}
