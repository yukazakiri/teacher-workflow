<?php

namespace App\Support\MediaLibrary;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

class CustomPathGenerator implements PathGenerator
{
    public function getPath(Media $media): string
    {
        return $this->getBasePath($media).'/'.$media->id;
    }

    public function getPathForConversions(Media $media): string
    {
        return $this->getBasePath($media).'/conversions/'.$media->id;
    }

    public function getPathForResponsiveImages(Media $media): string
    {
        return $this->getBasePath($media).'/responsive-images/'.$media->id;
    }

    public function getBasePath(Media $media): string
    {
        $teamId = $media->model->team_id ?? 'default';

        return 'class-resources/'.$teamId;
    }
}
