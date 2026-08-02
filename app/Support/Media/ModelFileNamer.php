<?php

namespace App\Support\Media;

use Illuminate\Support\Str;
use Spatie\MediaLibrary\Conversions\Conversion;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\FileNamer\FileNamer;

class ModelFileNamer extends FileNamer
{
    public function originalFileName(string $fileName): string
    {
        return pathinfo($fileName, PATHINFO_FILENAME);
    }

    public function conversionFileName(string $fileName, Conversion $conversion): string
    {
        return $this->originalFileName($fileName).'-'.$conversion->getName();
    }

    public function responsiveFileName(string $fileName): string
    {
        return $this->originalFileName($fileName);
    }

    public function getFileName(Media $media): string
    {
        $model = Str::lower(class_basename($media->model_type));

        return $model.'-'.$media->id;
    }
}