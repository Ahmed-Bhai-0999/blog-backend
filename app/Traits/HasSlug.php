<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait HasSlug
{
    protected static function bootHasSlug(): void
    {
        static::creating(function (Model $model) {

            if (!empty($model->slug)) {
                return;
            }

            $field = $model->slugSource ?? 'name';

            if (!isset($model->{$field})) {
                return;
            }

            $slug = Str::slug($model->{$field});

            $originalSlug = $slug;
            $counter = 1;

            while (
                static::where('slug', $slug)->exists()
            ) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }

            $model->slug = $slug;
        });

        static::updating(function (Model $model) {

            if (!$model->isDirty($model->slugSource ?? 'name')) {
                return;
            }

            $field = $model->slugSource ?? 'name';

            $slug = Str::slug($model->{$field});

            $originalSlug = $slug;
            $counter = 1;

            while (
                static::where('slug', $slug)
                    ->where('id', '!=', $model->id)
                    ->exists()
            ) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }

            $model->slug = $slug;
        });
    }
}