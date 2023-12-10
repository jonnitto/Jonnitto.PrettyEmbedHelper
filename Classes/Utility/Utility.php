<?php

namespace Jonnitto\PrettyEmbedHelper\Utility;

use Jonnitto\PrettyEmbedHelper\Service\ApiService;
use function get_headers;
use function preg_replace;
use function strpos;

class Utility
{
    /**
     * Return the href from Vimeo
     *
     * @param string|integer $videoID
     * @param boolean $embeded
     * @return string
     */
    public static function vimeoHref($videoID, bool $embeded = false): string
    {
        if ($embeded) {
            $parameter = 'autoplay=1&background=0&title=0&byline=0&portrait=0';
            return sprintf('https://player.vimeo.com/video/%s?%s', $videoID, $parameter);
        }
        return sprintf('https://vimeo.com/%s', $videoID);
    }

    /**
     * Return the href from YouTube
     *
     * @param string $videoID
     * @param string $type
     * @param boolean $embeded
     * @return string
     */
    public static function youtubeHref(string $videoID, ?string $type = 'video', bool $embeded = false): string
    {
        $parameter = 'autoplay=1&modestbranding=1&playsinline=1&rel=0';

        if ($type == 'playlist') {
            if ($embeded) {
                return sprintf('https://www.youtube.com/embed/videoseries?list=%s&%s', $videoID, $parameter);
            }
            return sprintf('https://www.youtube.com/playlist?list=%s', $videoID);
        }

        if ($embeded) {
            return sprintf('https://www.youtube.com/embed/%s?%s', $videoID, $parameter);
        }
        return sprintf('https://www.youtube.com/watch?v=%s', $videoID);
    }

    /**
     * Return the thumbnail URL from vimeo
     *
     * @param string|integer $videoID
     * @return string|null
     * @throws InfiniteRedirectionException
     * @throws JsonException
     */
    public static function vimeoThumbnail($videoID): ?string
    {
        if (!$videoID) {
            return null;
        }

        $api = new ApiService();
        $data = $api->vimeo($videoID);

        if (!isset($data)) {
            return null;
        }
        return Utility::removeProtocolFromUrl($data['thumbnail_url'] ?? null);
    }

    /**
     * Return the thumbnail URL from vimeo
     *
     * @param string $videoID
     * @return string|null
     */
    public static function youtubeThumbnail(string $videoID): ?string
    {
        if (!$videoID) {
            return null;
        }

        $imageArray = Utility::getBestPossibleYoutubeImage($videoID);

        if (!$imageArray) {
            return null;
        }

        return Utility::removeProtocolFromUrl($imageArray['image'] ?? null);
    }

    /**
     * Remove the protocol from url and replace it with `//`
     *
     * @param string|null $url
     * @return string|null
     */
    public static function removeProtocolFromUrl(?string $url = null): ?string
    {
        if (!$url || !is_string($url)) {
            return null;
        }
        return preg_replace('/https?:\/\//i', '//', $url);
    }

    /**
     * Get the best possible image from YouTube
     *
     * @param string|integer $videoID
     * @param string|null $url
     * @return array|null
     */
    public static function getBestPossibleYoutubeImage($videoID, ?string $url = null): ?array
    {
        if (!isset($url)) {
            $url = sprintf('https://i.ytimg.com/vi/%s/maxresdefault.jpg', $videoID);
        }

        $resolutions = ['maxresdefault', 'sddefault', 'hqdefault', 'mqdefault', 'default'];

        foreach ($resolutions as $resolution) {
            $url = preg_replace('/\/[\w]*\.([a-z]{3,})$/i', sprintf("/%s.$1", $resolution), $url);
            $headers = @get_headers($url);
            if ($headers && strpos($headers[0], '200')) {
                return [
                    'image' => $url,
                    'resolution' => $resolution,
                ];
            }
        }

        return null;
    }
}
