<?php

namespace TCG\Voyager\Tests\Unit;

use Illuminate\Http\UploadedFile;
use TCG\Voyager\Models\Media;
use TCG\Voyager\Models\Post;
use TCG\Voyager\Services\MediaService;
use TCG\Voyager\Tests\TestCase;

class MediaServiceTest extends TestCase
{
    protected $mediaService;

    public function setUp(): void
    {
        parent::setUp();
        $this->mediaService = new MediaService();
    }

    public function testCreateMediaFromFile()
    {
        $post = Post::create([
            'title' => 'Test Post',
            'slug' => 'test-post',
            'body' => 'Test body',
        ]);

        $file = UploadedFile::fake()->image('test.jpg');

        $media = $this->mediaService->createFromFile($post, $file, 'featured');

        $this->assertNotNull($media->id);
        $this->assertEquals($post->id, $media->model_id);
        $this->assertEquals(Post::class, $media->model_type);
        $this->assertEquals('featured', $media->collection_name);
        $this->assertEquals('test.jpg', $media->file_name);
    }

    public function testDeleteMedia()
    {
        $post = Post::create([
            'title' => 'Test Post',
            'slug' => 'test-post',
            'body' => 'Test body',
        ]);

        $file = UploadedFile::fake()->image('test.jpg');
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

        $file = UploadedFile::fake()->image('test.jpg');
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

        $file1 = UploadedFile::fake()->image('test1.jpg');
        $file2 = UploadedFile::fake()->image('test2.jpg');
        $file3 = UploadedFile::fake()->image('test3.jpg');

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

        $file1 = UploadedFile::fake()->image('test1.jpg');
        $file2 = UploadedFile::fake()->image('test2.jpg');

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

        $file1 = UploadedFile::fake()->image('featured.jpg');
        $file2 = UploadedFile::fake()->image('gallery1.jpg');
        $file3 = UploadedFile::fake()->image('gallery2.jpg');

        $featured = $this->mediaService->createFromFile($post, $file1, 'featured');
        $gallery1 = $this->mediaService->createFromFile($post, $file2, 'gallery');
        $gallery2 = $this->mediaService->createFromFile($post, $file3, 'gallery');

        $this->assertEquals(0, $featured->order);
        $this->assertEquals(0, $gallery1->order);
        $this->assertEquals(1, $gallery2->order);
    }
}
