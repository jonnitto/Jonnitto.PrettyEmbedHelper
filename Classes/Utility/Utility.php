<?php

namespace Jonnitto\PrettyEmbedHelper\Utility;

use Jonnitto\PrettyEmbedHelper\Service\ApiService;
use JsonException;
use Neos\ContentRepository\Core\Feature\NodeModification\Command\SetNodeProperties;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\PropertyValuesToWrite;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Http\Client\InfiniteRedirectionException;
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
     * @param Node $node
     * @param string|null $property
     * @return mixed
     */
    public static function getMetadata(Node $node, ?string $property = null): mixed
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
     * @param Node $node
     * @param string|null $property is null, all metadata will be replaced
     * @param mixed $value
     * @return void
     */
    public static function setMetadata(ContentRepositoryRegistry $contentRepositoryRegistry, Node $node, ?string $property = null, $value = null): void
    {
        $contentRepository = $contentRepositoryRegistry->get($node->contentRepositoryId);

        if (empty($property)) {
            $thumbnail = $value['thumbnail'] ?? null;
            unset($value['thumbnail']);
            $contentRepository->handle(SetNodeProperties::create(
                $node->workspaceName,
                $node->aggregateId,
                $node->originDimensionSpacePoint,
                PropertyValuesToWrite::fromArray([
                    self::THUMBNAIL_PROPERTY => $thumbnail,
                    self::METADATA_PROPERTY => $value,
                ]),
            ));
            return;
        }
        if ($property == 'thumbnail') {
            $contentRepository->handle(SetNodeProperties::create(
                $node->workspaceName,
                $node->aggregateId,
                $node->originDimensionSpacePoint,
                PropertyValuesToWrite::fromArray([
                    self::THUMBNAIL_PROPERTY => $value,
                ]),
            ));
            return;
        }

        $metadata = $node->getProperty(self::METADATA_PROPERTY) ?? [];
        $metadata[$property] = $value;
        $contentRepository->handle(SetNodeProperties::create(
            $node->workspaceName,
            $node->aggregateId,
            $node->originDimensionSpacePoint,
            PropertyValuesToWrite::fromArray([
                self::METADATA_PROPERTY => $metadata,
            ]),
        ));
    }

    /**
     * Remove Metadata value
     *
     * @param Node $node
     * @param string|null $property If null, all metadata will be removed
     * @return void
     */
    public static function removeMetadata(ContentRepositoryRegistry $contentRepositoryRegistry, Node $node, ?string $property = null): void
    {
        $contentRepository = $contentRepositoryRegistry->get($node->contentRepositoryId);

        if ($property == 'thumbnail') {
            $contentRepository->handle(SetNodeProperties::create(
                $node->workspaceName,
                $node->aggregateId,
                $node->originDimensionSpacePoint,
                PropertyValuesToWrite::fromArray([
                    self::THUMBNAIL_PROPERTY => null,
                ]),
            ));
            return;
        } elseif (empty($property)) {
            $contentRepository->handle(SetNodeProperties::create(
                $node->workspaceName,
                $node->aggregateId,
                $node->originDimensionSpacePoint,
                PropertyValuesToWrite::fromArray([
                    self::THUMBNAIL_PROPERTY => null,
                    self::METADATA_PROPERTY => [],
                ]),
            ));
            return;
        }

        $metadata = $node->getProperty(self::METADATA_PROPERTY) ?? [];
        unset($metadata[$property]);
        $contentRepository->handle(SetNodeProperties::create(
            $node->workspaceName,
            $node->aggregateId,
            $node->originDimensionSpacePoint,
            PropertyValuesToWrite::fromArray([
                self::METADATA_PROPERTY => $metadata,
            ]),
        ));
    }

    /**
     * Return the href from Vimeo
     *
     * @param string|integer $videoID
     * @param boolean $embedded
     * @return string
     */
    public static function vimeoHref($videoID, bool $embedded = false): string
    {
        if ($embedded) {
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
}
