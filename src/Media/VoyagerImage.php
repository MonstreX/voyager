<?php

namespace TCG\Voyager\Media;

class VoyagerImage
{
    public static function make($source)
    {
        return ImageProcessor::make($source);
    }
}
