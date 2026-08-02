<?php

namespace App\Support\Media;

use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

class ModelPathGenerator implements PathGenerator
{
    public function getPath(Media $media): string
    {
        return class_basename($media->model_type).'/';
    }

    public function getPathForConversions(Media $media): string
    {
        return class_basename($media->model_type).'/conversions/';
    }

    public function getPathForResponsiveImages(Media $media): string
    {
        return class_basename($media->model_type).'/responsive/';
    }
}
