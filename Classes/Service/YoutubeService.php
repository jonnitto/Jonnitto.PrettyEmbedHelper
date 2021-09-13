<?php

namespace Jonnitto\PrettyEmbedHelper\Service;

use JsonException;
use Neos\ContentRepository\Exception\NodeException;
use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Jonnitto\PrettyEmbedHelper\Utility\Utility;
use Neos\Flow\Http\Client\InfiniteRedirectionException;
use Neos\Flow\Persistence\Exception\IllegalObjectTypeException;
use Neos\Flow\Persistence\Exception\InvalidQueryException;
use Neos\Flow\ResourceManagement\Exception;
use function get_headers;
use function preg_replace;
use function strpos;
use function trim;

/**
 * @Flow\Scope("singleton")
 */
class YoutubeService
{
    /**
     * @Flow\Inject
     * @var Utility
     */
    protected $utility;

    /**
     * @Flow\Inject
     * @var ImageService
     */
    protected $imageService;

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
     * @Flow\InjectConfiguration(package="Jonnitto.PrettyEmbedYoutube")
     * @var array
     */
    protected $youtubeSettings;

    /**
     * @Flow\InjectConfiguration
     * @var array
     */
    protected $settings;

    /**
     * Get and save data from oembed service
     *
     * @param NodeInterface $node
     * @param boolean $remove
     * @return array|null
     * @throws NodeException
     * @throws IllegalObjectTypeException
     * @throws Exception|InfiniteRedirectionException
     * @throws JsonException|InvalidQueryException
     */
    public function getAndSaveDataFromApi(NodeInterface $node, bool $remove = false): ?array
    {
        $isYoutubePackage = $node->getNodeType()->isOfType(
            'Jonnitto.PrettyEmbedYoutube:Mixin.VideoID'
        );

        $videoIDProperty = null;
        $videoID = null;
        $data = null;
        $title = null;
        $ratio = null;
        $image = null;
        $type = null;
        $thumbnail = null;
        $duration = null;

        $this->imageService->remove($node);

        if ($remove === false) {
            $videoIDProperty = $node->getProperty('videoID');

            if ($isYoutubePackage) {
                $type = $this->youtubeSettings['defaults']['type'];
                if ($node->hasProperty('type')) {
                    $typeFromProperty = $node->getProperty('type');
                    if ($typeFromProperty === 'video' || $typeFromProperty === 'playlist') {
                        $type = $typeFromProperty;
                    }
                }
            } else {
                $type = $this->type($videoIDProperty);
                $node->setProperty('type', $type);
            }

            $videoID = $this->parseID->youtube($videoIDProperty, $type);
            $data = $this->api->youtube(
                $videoID,
                $type,
                $this->settings['youtubeApiKey']
            );

            if (isset($data)) {
                $title = $data['title'] ?? null;
                $ratio = $data['width'] && $data['height'] ?
                    $this->utility->calculatePaddingTop(
                        $data['width'],
                        $data['height']
                    ) : null;
                $duration = $data['duration'] ?? null;
                if (isset($data['imageUrl'], $data['imageResolution'])) {
                    $image = $data['imageUrl'];
                    $resolution = $data['imageResolution'];
                } else {
                    $youtubeImageArray = $this->getBestPossibleYoutubeImage($videoID, $data['thumbnail_url']);
                    $image = $youtubeImageArray['image'];
                    $resolution = $youtubeImageArray['resolution'];
                }
            } else {
                $youtubeImageArray = $this->getBestPossibleYoutubeImage($videoID);
                $image = $youtubeImageArray['image'] ?? null;
                $resolution = $youtubeImageArray['resolution'] ?? null;
            }

            if (isset($image)) {
                $thumbnail = $this->imageService->import(
                    $node,
                    $image,
                    $videoID,
                    'Youtube',
                    $resolution
                );
            }
        }

        $node->setProperty('metadataID', $videoID);
        $node->setProperty('metadataTitle', $title);
        $node->setProperty('metadataRatio', $ratio);
        $node->setProperty(
            'metadataImage',
            $this->utility->removeProtocolFromUrl($image)
        );
        $node->setProperty('metadataThumbnail', $thumbnail);
        $node->setProperty('metadataDuration', $duration);

        $this->imageService->removeTagIfEmpty();

        if ($videoIDProperty || $remove) {
            return [
                'nodeTypeName' => $node->getNodeType()->getName(),
                'node' => 'Youtube',
                'type' => ucfirst($type),
                'id' => $videoID,
                'path' => $node->getPath(),
                'data' => isset($data)
            ];
        }

        return null;
    }

    /**
     * Get the type of video
     *
     * @param string $url
     * @return string The type of the link
     */
    protected function type(string $url): string
    {
        $url = trim($url);
        if (!$url) {
            return 'video';
        }
        return strpos($url, 'list=') !== false ? 'playlist' : 'video';
    }

    /**
     * Get the best possible image from YouTube
     *
     * @param string|integer $videoID
     * @param string|null $url
     * @return array|null
     */
    protected function getBestPossibleYoutubeImage(
        $videoID,
        ?string $url = null
    ): ?array {
        if (!isset($url)) {
            $url = sprintf("https://i.ytimg.com/vi/%s/maxresdefault.jpg", $videoID);
        }

        $resolutions = [
            'maxresdefault', 'sddefault', 'hqdefault', 'mqdefault', 'default'
        ];

        foreach ($resolutions as $resolution) {
            $url = preg_replace(
                '/\/[\w]*\.([a-z]{3,})$/i',
                sprintf("/%s.$1", $resolution),
                $url
            );
            $headers = @get_headers($url);
            if ($headers && strpos($headers[0], '200')) {
                return [
                    'image' => $url,
                    'resolution' => $resolution
                ];
            }
        }

        return null;
    }
}
