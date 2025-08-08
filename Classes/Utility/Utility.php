<?php

namespace Jonnitto\PrettyEmbedHelper\Utility;

use Jonnitto\PrettyEmbedHelper\Service\ApiService;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use function get_headers;
use function preg_replace;
use function strpos;

class Utility
{
    const THUMBNAIL_PROPERTY = 'prettyembedMetadataThumbnail';
    const METADATA_PROPERTY = 'prettyembedMetadata';

    /**
     * Get Metdata value
     *
     * @param NodeInterface $node
     * @param string|null $property
     * @return void
     */
    public static function getMetadata(NodeInterface $node, ?string $property = null)
    {
        if ($property == 'thumbnail') {
            return $node->getProperty(self::THUMBNAIL_PROPERTY);
        }

        $metadata = $node->getProperty(self::METADATA_PROPERTY) ?? [];

        if (empty($property)) {
            $metadata['thumbnail'] = $node->getProperty(self::THUMBNAIL_PROPERTY);
            return $metadata;
        }

        return $metadata[$property] ?? null;
    }

    /**
     * Set Metadata value
     *
     * @param NodeInterface $node
     * @param string|null $property is null, all metadata will be replaced
     * @param mixed $value
     * @return void
     */
    public static function setMetadata(NodeInterface $node, ?string $property = null, $value = null): void
    {
        if (empty($property)) {
            $thumbnail = $value['thumbnail'] ?? null;
            unset($value['thumbnail']);
            $node->setProperty(self::THUMBNAIL_PROPERTY, $thumbnail);
            $node->setProperty(self::METADATA_PROPERTY, $value);
            return;
        }
        if ($property == 'thumbnail') {
            $node->setProperty(self::THUMBNAIL_PROPERTY, $value);
            return;
        }

        $metadata = $node->getProperty(self::METADATA_PROPERTY) ?? [];
        $metadata[$property] = $value;
        $node->setProperty(self::METADATA_PROPERTY, $metadata);
    }

    /**
     * Remove Metadata value
     *
     * @param NodeInterface $node
     * @param string|null $property If null, all metadata will be removed
     * @return void
     */
    public static function removeMetadata(NodeInterface $node, ?string $property = null): void
    {
        if ($property == 'thumbnail' || empty($property)) {
            $node->setProperty(self::THUMBNAIL_PROPERTY, null);
            return;
        }

        if (empty($property)) {
            $node->setProperty(self::METADATA_PROPERTY, []);
            return;
        }

        $metadata = $node->getProperty(self::METADATA_PROPERTY) ?? [];
        unset($metadata[$property]);
        $node->setProperty(self::METADATA_PROPERTY, $metadata);
        return;
    }

    /**
     * Return the href from Vimeo
     *
     * @param string|integer $videoID
     * @param boolean $embedded
     * @param string|null $hash
     * @return string
     */
    public static function vimeoHref($videoID, bool $embedded = false, $hash = null): string
    {
        if ($embedded) {
            $hash = !empty($hash) ? '&h=' . $hash : '';
            $parameter = 'autoplay=1&background=0&title=0&byline=0&portrait=0';
            return sprintf('https://player.vimeo.com/video/%s?%s%s', $videoID, $parameter, $hash);
        }
        $hash = !empty($hash) ? '/' . $hash : '';
        return sprintf('https://vimeo.com/%s%s', $videoID, $hash);
    }

    /**
     * Return the href from YouTube
     *
     * @param string $videoID
     * @param string $type
     * @param boolean $embedded
     * @return string
     */
    public static function youtubeHref(string $videoID, ?string $type = 'video', bool $embedded = false): string
    {
        $parameter = 'autoplay=1&modestbranding=1&playsinline=1&rel=0';

        if ($type == 'playlist') {
            if ($embedded) {
                return sprintf('https://www.youtube.com/embed/videoseries?list=%s&%s', $videoID, $parameter);
            }
            return sprintf('https://www.youtube.com/playlist?list=%s', $videoID);
        }

        if ($type == 'short') {
            if ($embedded) {
                return sprintf('https://www.youtube.com/embed/%s?%s', $videoID, $parameter);
            }
            return sprintf('https://www.youtube.com/shorts/%s', $videoID);
        }

        if ($embedded) {
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

    /**
     * Simplifies a fraction by dividing the numerator and denominator by their greatest common divisor.
     *
     * @param integer $numerator
     * @param integer $denominator
     * @return string
     */
    public static function getRatio($numerator, $denominator): ?string
    {
        if (!is_int($numerator) || !is_int($denominator) || $denominator == 0) {
            return null;
        }

        $divisor = self::greatestCommonDivisor($numerator, $denominator);
        $simplifiedNumerator = $numerator / $divisor;
        $simplifiedDenominator = $denominator / $divisor;

        return sprintf('%s / %s', $simplifiedNumerator, $simplifiedDenominator);
    }

    /**
     * Calculates the greatest common divisor using the Euclidean algorithm.
     *
     * @param integer $numerator
     * @param integer $denominator
     * @return integer
     */
    protected static function greatestCommonDivisor(int $numerator, int $denominator): int
    {
        while ($denominator != 0) {
            $temp = $denominator;
            $denominator = $numerator % $denominator;
            $numerator = $temp;
        }
        return abs($numerator); // Always return a positive GCD
    }
}
