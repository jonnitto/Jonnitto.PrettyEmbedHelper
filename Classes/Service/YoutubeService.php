<?php

namespace Jonnitto\PrettyEmbedHelper\Service;

use Jonnitto\PrettyEmbedHelper\Utility\Utility;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Exception\NodeException;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\Client\InfiniteRedirectionException;
use Neos\Flow\Persistence\Exception\IllegalObjectTypeException;
use Neos\Flow\Persistence\Exception\InvalidQueryException;
use Neos\Flow\ResourceManagement\Exception;
use JsonException;
use function strpos;
use function trim;

/**
 * @Flow\Scope("singleton")
 */
class YoutubeService
{
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
     * @Flow\Inject
     * @var MetadataService
     */
    protected $metadataService;

    /**
     * @Flow\InjectConfiguration(package="Jonnitto.PrettyEmbed", path="YouTube.apiKey")
     * @var string
     */
    protected $apiKey;

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
        $this->imageService->remove($node);

        $returnArray = [
            'nodeTypeName' => $node->getNodeType()->getName(),
            'node' => 'Youtube',
            'path' => $node->getPath(),
            'data' => false,
        ];

        if ($remove === true) {
            $this->metadataService->removeMetaData($node);
            return $returnArray;
        }

        $videoIDProperty = $node->getProperty('videoID');
        $type = $this->type($videoIDProperty);
        $node->setProperty('type', $type);

        $videoID = $this->parseID->youtube($videoIDProperty, $type);
        $data = $this->api->youtube($videoID, $type, $this->apiKey);

        if (isset($data)) {
            $title = $data['title'] ?? null;
            $ratio = $data['width'] && $data['height'] ? sprintf('%s / %s', $data['width'], $data['height']) : null;
            $duration = $data['duration'] ?? null;
            if (isset($data['imageUrl'], $data['imageResolution'])) {
                $image = $data['imageUrl'];
                $resolution = $data['imageResolution'];
            } else {
                $youtubeImageArray = Utility::getBestPossibleYoutubeImage($videoID, $data['thumbnail_url'] ?? null);
                $image = $youtubeImageArray['image'];
                $resolution = $youtubeImageArray['resolution'];
            }
        } else {
            $youtubeImageArray = Utility::getBestPossibleYoutubeImage($videoID);
            $image = $youtubeImageArray['image'] ?? null;
            $resolution = $youtubeImageArray['resolution'] ?? null;
        }

        if (isset($image)) {
            $thumbnail = $this->imageService->import($node, $image, $videoID, 'Youtube', $resolution);
        }

        $node->setProperty('metadataID', $videoID);
        $node->setProperty('metadataTitle', $title ?? null);
        $node->setProperty('metadataRatio', $ratio ?? null);
        $node->setProperty('metadataImage', Utility::removeProtocolFromUrl($image));
        $node->setProperty('metadataThumbnail', $thumbnail ?? null);
        $node->setProperty('metadataDuration', $duration ?? null);

        $this->imageService->removeTagIfEmpty();

        if (!$videoIDProperty) {
            return null;
        }

        $returnArray['id'] = $videoID;
        $returnArray['type'] = ucfirst($type);
        $returnArray['data'] = isset($data);
        return $returnArray;
    }

    /**
     * Get the type of video
     *
     * @param string $url
     * @return string The type of the link
     */
    public function type(string $url): string
    {
        $url = trim($url);
        if (!$url) {
            return 'video';
        }
        return strpos($url, 'list=') !== false ? 'playlist' : 'video';
    }
}
