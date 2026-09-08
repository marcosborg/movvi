<?php

namespace App\Support;

use Spatie\MediaLibrary\Support\UrlGenerator\DefaultUrlGenerator;

class MediaUrlGenerator extends DefaultUrlGenerator
{
    public function getUrl(): string
    {
        $url = parent::getUrl();

        return str_starts_with((string) $this->media->mime_type, 'image/')
            ? ImageUrl::forDisplay($url)
            : $url;
    }

    public function getResponsiveImagesDirectoryUrl(): string
    {
        return ImageUrl::forDisplay(parent::getResponsiveImagesDirectoryUrl());
    }
}
