<?php

namespace TCG\Voyager\Tests\Unit;

use TCG\Voyager\Models\Media;
use TCG\Voyager\Tests\TestCase;

class MediaModelTest extends TestCase
{
    public function testMediaCanBeCreated()
    {
        $media = Media::create([
            'model_type' => 'TestModel',
            'model_id' => 1,
            'collection_name' => 'test',
            'file_name' => 'test.jpg',
            'path' => 'media/test.jpg',
            'disk' => 'public',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
        ]);

        $this->assertNotNull($media->id);
        $this->assertEquals('test.jpg', $media->file_name);
        $this->assertEquals('media/test.jpg', $media->path);
    }

    public function testMediaCanSetAndGetProps()
    {
        $media = Media::create([
            'model_type' => 'TestModel',
            'model_id' => 1,
            'collection_name' => 'test',
            'file_name' => 'test.jpg',
            'path' => 'media/test.jpg',
            'disk' => 'public',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
        ]);

        $media->setProp('title', 'Test Title');
        $media->setProp('alt', 'Test Alt');
        $media->save();

        $media->refresh();

        $this->assertEquals('Test Title', $media->prop('title'));
        $this->assertEquals('Test Alt', $media->prop('alt'));
    }

    public function testMediaCanDetectImageType()
    {
        $imageMedia = Media::create([
            'model_type' => 'TestModel',
            'model_id' => 1,
            'collection_name' => 'test',
            'file_name' => 'test.jpg',
            'path' => 'media/test.jpg',
            'disk' => 'public',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
        ]);

        $this->assertTrue($imageMedia->isImage());

        $pdfMedia = Media::create([
            'model_type' => 'TestModel',
            'model_id' => 2,
            'collection_name' => 'test',
            'file_name' => 'test.pdf',
            'path' => 'media/test.pdf',
            'disk' => 'public',
            'mime_type' => 'application/pdf',
            'size' => 2048,
        ]);

        $this->assertFalse($pdfMedia->isImage());
    }

    public function testMediaCanFormatSize()
    {
        $media = Media::create([
            'model_type' => 'TestModel',
            'model_id' => 1,
            'collection_name' => 'test',
            'file_name' => 'test.jpg',
            'path' => 'media/test.jpg',
            'disk' => 'public',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
        ]);

        $this->assertStringContainsString('KB', $media->sizeForHumans());
    }
}
