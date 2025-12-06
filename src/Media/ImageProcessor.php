<?php

namespace TCG\Voyager\Media;

use RuntimeException;

class ImageProcessor
{
    /** @var resource|\GdImage|null */
    private $imageResource = null;

    /** @var int */
    private $originalWidth = 0;
    /** @var int */
    private $originalHeight = 0;

    /** @var int */
    private $currentWidth = 0;
    /** @var int */
    private $currentHeight = 0;

    /** @var string */
    private $mimeType = '';

    /** @var string */
    private $format = '';
    /** @var int */
    private $quality = 75;

    /** @var string|null */
    public $encoded = null;

    /**
     * Factory method to create instance
     * @param mixed $source
     * @return self
     */
    public static function make($source)
    {
        $instance = new self();
        return $instance->read($source);
    }

    /**
     * Read image from file path or binary string
     * @param string|mixed $source
     * @return self
     */
    public function read($source)
    {
        // Handle UploadedFile / SplFileInfo
        if (is_object($source) && method_exists($source, 'getRealPath')) {
            $source = $source->getRealPath();
        }

        if (is_string($source) && @is_file($source)) {
            $this->mimeType = mime_content_type($source);
            $content = file_get_contents($source);
        } else {
            // Assume string data (binary)
            $content = $source;
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            // Check if content is not empty to avoid warning
            if (!empty($content)) {
                $this->mimeType = $finfo->buffer($content);
            }
        }
        
        if (empty($content)) {
             throw new RuntimeException("Image content is empty");
        }

        try {
            $this->imageResource = imagecreatefromstring($content);
            if ($this->mimeType === 'image/png' || $this->mimeType === 'image/webp') {
                imagealphablending($this->imageResource, false);
                imagesavealpha($this->imageResource, true);
            }
        } catch (\Exception $e) {
            throw new RuntimeException("Failed to read image: " . $e->getMessage());
        }

        if (!$this->imageResource) {
            throw new RuntimeException("Failed to create GD image resource");
        }

        $this->originalWidth = imagesx($this->imageResource);
        $this->originalHeight = imagesy($this->imageResource);
        $this->currentWidth = $this->originalWidth;
        $this->currentHeight = $this->originalHeight;

        return $this;
    }

    /**
     * Resize image
     * @param int|null $width
     * @param int|null $height
     * @param callable|null $callback
     * @return self
     */
    public function resize($width, $height, $callback = null)
    {
        // Simple emulation of Intervention Constraint
        // We handle basic aspect ratio preservation if one dimension is null
        
        // If a callback is provided, we can't fully emulate Intervention's Constraint object 
        // without creating a dummy class. For now, we rely on null checks.

        return $this->scale($width, $height);
    }

    /**
     * Scale image preserving aspect ratio
     * @param int|null $width
     * @param int|null $height
     * @return self
     */
    public function scale($width = null, $height = null)
    {
        if (!$this->imageResource) {
            throw new RuntimeException("No image loaded");
        }

        // Sanitize inputs (handle strings from JSON/Request)
        if ($width === 'null') $width = null;
        if ($height === 'null') $height = null;

        if ($width !== null) $width = (int)$width;
        if ($height !== null) $height = (int)$height;

        // Treat 0 as null (auto calculate)
        if ($width === 0) $width = null;
        if ($height === 0) $height = null;

        if ($width === null && $height === null) {
            return $this;
        }

        $srcWidth = $this->currentWidth;
        $srcHeight = $this->currentHeight;

        if ($width && !$height) {
            // Scale by width, preserve aspect
            $height = (int)($width * $srcHeight / $srcWidth);
        } elseif ($height && !$width) {
            // Scale by height, preserve aspect
            $width = (int)($height * $srcWidth / $srcHeight);
        }

        // Ensure strictly int for imagescale
        $scaledImage = imagescale($this->imageResource, (int)$width, (int)$height);
        if (!$scaledImage) {
            throw new RuntimeException("Failed to scale image");
        }
        
        // Preserve alpha
        imagealphablending($scaledImage, false);
        imagesavealpha($scaledImage, true);

        if ($this->imageResource !== $scaledImage) {
             imagedestroy($this->imageResource);
        }
        
        $this->imageResource = $scaledImage;
        $this->currentWidth = $width;
        $this->currentHeight = $height;

        return $this;
    }

    /**
     * Fit (Crop + Resize)
     * @param int $width
     * @param int $height
     * @param callable|null $callback
     * @param string $position
     * @return self
     */
    public function fit($width, $height = null, $callback = null, $position = 'center')
    {
        if (!$height) $height = $width;
        
        // Logic similar to 'cover' but with position support
        // For simplicity, we implement center crop (default)
        // 'cover' logic from ave.package implements center crop
        
        return $this->cover($width, $height);
    }

    /**
     * Cover (Smart Crop)
     * @param int $width
     * @param int $height
     * @return self
     */
    public function cover($width, $height)
    {
        if (!$this->imageResource) return $this;

        $srcWidth = $this->currentWidth;
        $srcHeight = $this->currentHeight;
        $srcAspect = $srcWidth / $srcHeight;
        $dstAspect = $width / $height;

        if ($srcAspect > $dstAspect) {
            // Source wider
            $newHeight = $height;
            $newWidth = (int)($height * $srcAspect);
        } else {
            // Source taller
            $newWidth = $width;
            $newHeight = (int)($width / $srcAspect);
        }

        $this->scale($newWidth, $newHeight);

        // Center crop
        $x = (int)(($newWidth - $width) / 2);
        $y = (int)(($newHeight - $height) / 2);

        return $this->crop($width, $height, $x, $y);
    }

    /**
     * Crop image
     * @param int $width
     * @param int $height
     * @param int|null $x
     * @param int|null $y
     * @return self
     */
    public function crop($width, $height, $x = null, $y = null)
    {
        if (!$this->imageResource) return $this;

        // If x, y are null, Intervention defaults to center? No, crop defaults to 0,0 usually unless 'center' logic used.
        // But Voyager passes explicit x,y in `VoyagerMediaController::crop`.
        
        $x = $x ?? 0;
        $y = $y ?? 0;

        $croppedImage = imagecrop($this->imageResource, [
            'x' => $x,
            'y' => $y,
            'width' => $width,
            'height' => $height,
        ]);

        if ($croppedImage) {
            imagedestroy($this->imageResource);
            $this->imageResource = $croppedImage;
            $this->currentWidth = $width;
            $this->currentHeight = $height;
            
             // Preserve alpha
            imagealphablending($this->imageResource, false);
            imagesavealpha($this->imageResource, true);
        }

        return $this;
    }

    /**
     * Correct orientation based on EXIF
     * @return self
     */
    public function orientate()
    {
        // Require direct file path? Intervention reads from binary too?
        // GD's imagecreatefromstring doesn't keep EXIF. 
        // We would need the original path. 
        // Since we pass content or path to `make`, we might lose the path if we read content immediately.
        // BUT: For uploaded files, standard GD doesn't handle EXIF rotation automatically.
        // Implementing full EXIF rotation without 'exif' extension or external library is hard.
        // We will skip this for now or implement basic check if possible.
        
        return $this;
    }

    /**
     * Insert watermark
     * @param mixed $source
     * @param string $position
     * @param int $x
     * @param int $y
     * @return self
     */
    public function insert($source, $position = 'top-left', $x = 0, $y = 0)
    {
        $watermark = self::make($source);
        
        $wWidth = $watermark->width();
        $wHeight = $watermark->height();
        
        // Calculate position
        switch ($position) {
            case 'top-left': $pX = 0; $pY = 0; break;
            case 'top-right': $pX = $this->currentWidth - $wWidth; $pY = 0; break;
            case 'bottom-right': $pX = $this->currentWidth - $wWidth; $pY = $this->currentHeight - $wHeight; break;
            case 'bottom-left': $pX = 0; $pY = $this->currentHeight - $wHeight; break;
            case 'center': 
                $pX = ($this->currentWidth - $wWidth) / 2;
                $pY = ($this->currentHeight - $wHeight) / 2;
                break;
            default: $pX = 0; $pY = 0;
        }
        
        $pX += $x;
        $pY += $y;

        // Copy watermark onto image
        // imagecopy preserves alpha?
        imagecopy($this->imageResource, $watermark->imageResource, (int)$pX, (int)$pY, 0, 0, $wWidth, $wHeight);

        return $this;
    }

    public function width()
    {
        return $this->currentWidth;
    }

    public function height()
    {
        return $this->currentHeight;
    }

    /**
     * Encode image
     * @param string|null $format
     * @param int|null $quality
     * @return self
     */
    public function encode($format = null, $quality = null)
    {
        if (!$this->imageResource) {
            throw new RuntimeException("No image loaded");
        }

        $format = strtolower($format ?? $this->format ?? 'jpeg');
        // Map jpg to jpeg
        if ($format === 'jpg') $format = 'jpeg';
        
        if ($quality !== null) {
            $this->quality = max(1, min(100, $quality));
        }

        ob_start();
        switch ($format) {
            case 'png':
                imagepng($this->imageResource);
                break;
            case 'gif':
                imagegif($this->imageResource);
                break;
            case 'webp':
                if (function_exists('imagewebp')) {
                    imagewebp($this->imageResource, null, $this->quality);
                } else {
                    imagejpeg($this->imageResource, null, $this->quality); // Fallback
                }
                break;
            case 'jpeg':
            default:
                imagejpeg($this->imageResource, null, $this->quality);
                break;
        }
        $this->encoded = ob_get_clean();

        return $this;
    }

    public function __toString()
    {
        return (string)($this->encoded ?: $this->encode()->encoded);
    }
    
    public function __destruct()
    {
        if ($this->imageResource && is_resource($this->imageResource)) {
            imagedestroy($this->imageResource);
        } elseif ($this->imageResource instanceof \GdImage) {
            imagedestroy($this->imageResource);
        }
    }
    
    // Getter for protected property imageResource (for insert/watermark usage)
    public function __get($name) {
        if ($name === 'imageResource') return $this->imageResource;
        return null;
    }
}
