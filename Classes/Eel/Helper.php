<?php

namespace Jonnitto\PrettyEmbedHelper\Eel;


use Neos\Flow\Annotations as Flow;
use Neos\Eel\ProtectedContextAwareInterface;
use Jonnitto\PrettyEmbedHelper\Service\ApiService;
use Jonnitto\PrettyEmbedHelper\Service\ParseIDService;
use Jonnitto\PrettyEmbedHelper\Utility\Utility;

/**
 * @Flow\Proxy(false)
 */
class Helper implements ProtectedContextAwareInterface
{
    /**
     * @Flow\Inject
     * @var ParseIDService
     */
    protected $parseID;

    /**
     * @Flow\Inject
     * @var ApiService
     */
    protected $api;

    /**
     * @Flow\Inject
     * @var Utility
     */
    protected $utility;

    /**
     * This helper calcualtes the padding from a given ratio or width and height
     *
     * @param float|integer|string $ratio
     * @return string|null The calculated value
     */
    public function paddingTop($ratio = null): ?string
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
    public function vimeoThumbnail($videoID): ?string
    {
        $data = $this->api->vimeo($videoID);
        if (!isset($data)) {
            return null;
        }
        return $this->utility->removeProtocolFromUrl($data['thumbnail_url']) ?? null;
    }

    /**
     * Return the id from a video platform
     *
     * @param string|integer $videoID
     * @return string|null
     */
    public function platformID($videoID, string $platform): ?string
    {
        if ($platform === 'vimeo') {
            return $this->parseID->vimeo($videoID);
        }
        return $this->parseID->youtube($videoID);
    }

    /**
     * Return the id from vimeo
     *
     * @param string|integer $videoID
     * @return string|integer|null
     */
    public function vimeoID($videoID): ?string
    {
        return $this->parseID->vimeo($videoID);
    }

    /**
     * Return the id from youtube
     *
     * @param string|integer $videoID
     * @return string|null
     */
    public function youtubeID($videoID): ?string
    {
        return $this->parseID->youtube($videoID);
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
