<?php

namespace TCG\Voyager\Tests\Unit;

use TCG\Voyager\Media\ImageProcessor;
use TCG\Voyager\Tests\TestCase;

class ImageProcessingTest extends TestCase
{
    public function testImageProcessorCanLoadImage()
    {
        $image = ImageProcessor::make(__DIR__ . '/../temp/test.jpg');

        $this->assertNotNull($image);
    }

    public function testImageProcessorCanGetDimensions()
    {
        $image = ImageProcessor::make(__DIR__ . '/../temp/test.jpg');

        $this->assertGreaterThan(0, $image->width());
        $this->assertGreaterThan(0, $image->height());
    }

    public function testImageProcessorCanResize()
    {
        $image = ImageProcessor::make(__DIR__ . '/../temp/test.jpg');
        $originalWidth = $image->width();

        $image->resize(100, 100);

        $this->assertLessThan($originalWidth, $image->width());
    }

    public function testImageProcessorCanCrop()
    {
        $image = ImageProcessor::make(__DIR__ . '/../temp/test.jpg');

        $image->crop(50, 50, 0, 0);

        $this->assertEquals(50, $image->width());
        $this->assertEquals(50, $image->height());
    }

    public function testImageProcessorCanScale()
    {
        $image = ImageProcessor::make(__DIR__ . '/../temp/test.jpg');
        $originalWidth = $image->width();

        $image->scale(50);

        $this->assertLessThan($originalWidth, $image->width());
    }

    public function testImageProcessorCanEncode()
    {
        $image = ImageProcessor::make(__DIR__ . '/../temp/test.jpg');

        $encoded = $image->encode();

        $this->assertNotNull($encoded->encoded);
        $this->assertNotEmpty($encoded->encoded);
    }
}
