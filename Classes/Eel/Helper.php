<?php

namespace Jonnitto\PrettyEmbedHelper\Eel;


use Neos\Flow\Annotations as Flow;
use Neos\Eel\ProtectedContextAwareInterface;
use Jonnitto\PrettyEmbedHelper\Service\OembedService;
use Jonnitto\PrettyEmbedHelper\Service\ParseIDService;

/**
 * @Flow\Proxy(false)
 */
class Helper implements ProtectedContextAwareInterface
{

    /**
     * This helper calcualtes the padding from a given ratio or width and height
     *
     * @param float|integer|string $ratio
     * @return string|null The calculated value
     */
    function paddingTop($ratio = null): ?string
    {
        if ($ratio === null) {
            return null;
        }
        if (is_string($ratio)) {
            return $ratio;
        }

        return (100 / $ratio) . '%';
    }

    /**
     * Return the thumbnail URL from vimeo
     *
     * @param string|integer $videoID
     * @return string|null
     */
    function vimeoThumbnail($videoID): ?string
    {
        $data = OembedService::vimeo($videoID);
        if (!isset($data)) {
            return null;
        }
        return OembedService::removeProtocolFromUrl($data->thumbnail_url) ?? null;
    }

    /**
     * Return the id from a video platform
     *
     * @param string|integer $videoID
     * @return string|integer|null
     */
    function platformID($videoID, string $platform)
    {
        if ($platform === 'vimeo') {
            return ParseIDService::vimeo($videoID);
        }
        return ParseIDService::youtube($videoID);
    }

    /**
     * Return the id from vimeo
     *
     * @param string|integer $videoID
     * @return string|integer|null
     */
    function vimeoID($videoID)
    {
        return ParseIDService::vimeo($videoID);
    }

    /**
     * Return the id from vimeo
     *
     * @param string|integer $videoID
     * @return array|null
     */
    function youtubeID($videoID)
    {
        return ParseIDService::youtube($videoID);
    }


    /**
     * All methods are considered safe
     *
     * @param string $methodName
     * @return boolean
     */
    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
