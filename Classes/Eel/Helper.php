<?php

namespace Jonnitto\PrettyEmbedHelper\Eel;

use Jonnitto\PrettyEmbedHelper\Service\ApiService;
use Jonnitto\PrettyEmbedHelper\Service\ParseIDService;
use Jonnitto\PrettyEmbedHelper\Utility\Utility;
use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\Client\InfiniteRedirectionException;
use JsonException;

/**
 * @Flow\Proxy(false)
 */
class Helper implements ProtectedContextAwareInterface
{
    /**
     * Return the thumbnail URL from vimeo
     *
     * @param string|integer $videoID
     * @return string|null
     * @throws InfiniteRedirectionException
     * @throws JsonException
     */
    public function vimeoThumbnail($videoID): ?string
    {
        return Utility::vimeoThumbnail($videoID);
    }

    /**
     * Return the href from Vimeo
     *
     * @param string|integer $videoID
     * @param boolean $embeded
     * @return string
     */
    public function vimeoHref($videoID, bool $embeded = false): string
    {
        return Utility::vimeoHref($videoID, $embeded);
    }

    /**
     * Return the href from YouTube
     *
     * @param string $videoID
     * @param string $type
     * @param boolean $embeded
     * @return string
     */
    public function youtubeHref(string $videoID, ?string $type = 'video', bool $embeded = false): string
    {
        return Utility::youtubeHref($videoID, $type, $embeded);
    }

    /**
     * Return the thumbnail URL from vimeo
     *
     * @param string $videoID
     * @return string|null
     */
    public function youtubeThumbnail(string $videoID): ?string
    {
        return Utility::youtubeThumbnail($videoID);
    }

    /**
     * Return the id from a video platform
     *
     * @param string|integer $videoID
     * @param string $platform
     * @return string|null
     */
    public function platformID($videoID, string $platform): ?string
    {
        if ($platform === 'vimeo') {
            return $this->vimeoID($videoID);
        }
        return $this->youtubeID($videoID);
    }

    /**
     * Return the id from Vimeo
     *
     * @param string|integer $videoID
     * @return string|null
     */
    public function vimeoID($videoID): ?string
    {
        $parseID = new ParseIDService();
        return $parseID->vimeo($videoID);
    }

    /**
     * Return the id from YouTube
     *
     * @param string|integer $videoID
     * @return string|null
     */
    public function youtubeID($videoID): ?string
    {
        $parseID = new ParseIDService();
        return $parseID->youtube($videoID);
    }

    /**
     * All methods are considered safe
     *
     * @param string $methodName
     * @return boolean
     */
    public function allowsCallOfMethod($methodName): bool
    {
        return true;
    }
}
