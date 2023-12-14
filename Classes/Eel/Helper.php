<?php

namespace Jonnitto\PrettyEmbedHelper\Eel;

use Jonnitto\PrettyEmbedHelper\Service\ParseIDService;
use Jonnitto\PrettyEmbedHelper\Utility\Utility;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\Client\InfiniteRedirectionException;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Model\ImageInterface;
use Neos\Media\Domain\Model\ThumbnailConfiguration;
use Neos\Media\Domain\Service\ThumbnailService;
use Neos\Media\Exception\ThumbnailServiceException;
use JsonException;

class Helper implements ProtectedContextAwareInterface
{
    /**
     * @Flow\Inject
     * @var ThumbnailService
     */
    protected $thumbnailService;

    /**
     * Get the metadata from a node
     *
     * @param NodeInterface $node
     * @param string|null $property If not set, all metadata will be returned
     * @return mixed
     */
    public function getMetadata($node, ?string $property = null)
    {
        return Utility::getMetadata($node, $property);
    }

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
     * @param boolean $embedded
     * @return string
     */
    public function vimeoHref($videoID, bool $embedded = false): string
    {
        return Utility::vimeoHref($videoID, $embedded);
    }

    /**
     * Return the href from YouTube
     *
     * @param string $videoID
     * @param string $type
     * @param boolean $embedded
     * @return string
     */
    public function youtubeHref(string $videoID, ?string $type = 'video', bool $embedded = false): string
    {
        return Utility::youtubeHref($videoID, $type, $embedded);
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
     * @param AssetInterface $asset
     * @param integer $maximumWidth Desired maximum width of the image
     * @param boolean $async Whether the thumbnail can be generated asynchronously
     * @param integer $quality Quality of the processed image
     * @param string $format Format for the image, only jpg, jpeg, gif, png, wbmp, xbm, webp and bmp are supported.
     * @return null|ImageInterface
     * @throws ThumbnailServiceException
     */
    public function createThumbnail(AssetInterface $asset, $maximumWidth = null, $format = null, $quality = null)
    {
        $width = null;
        $height = null;
        $maximumHeight = null;
        $allowCropping = false;
        $allowUpScaling = false;
        $async = false;

        $thumbnailConfiguration = new ThumbnailConfiguration(
            $width,
            $maximumWidth,
            $height,
            $maximumHeight,
            $allowCropping,
            $allowUpScaling,
            $async,
            $quality,
            $format
        );

        $thumbnailImage = $this->thumbnailService->getThumbnail($asset, $thumbnailConfiguration);
        if (!$thumbnailImage instanceof ImageInterface) {
            return null;
        }
        return $thumbnailImage;
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
