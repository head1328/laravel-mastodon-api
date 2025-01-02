<?php

namespace Revolution\Mastodon\Concerns;

trait Medias
{
    /**
     * Upload new media.
     */
    public function uploadMedia(string $file, array $options = null): array
    {
        $url = '/media';

        $params = array_merge(['file' => $file], $options ?? []);

        return $this->post($url, $params ?? []);
    }
}