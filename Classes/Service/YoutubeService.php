<?php

namespace Jonnitto\PrettyEmbedHelper\Service;

use Jonnitto\PrettyEmbedHelper\Utility\Utility;
use Jonnitto\PrettyPresentation\Utility\Utility as PresentationUtility;
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

#[Flow\Scope('singleton')]
class YoutubeService
{
    #[Flow\Inject]
    protected ImageService $imageService;

    #[Flow\Inject]
    protected ParseIDService $parseID;

    #[Flow\Inject]
    protected ApiService $api;

    #[Flow\Inject]
    protected MetadataService $metadataService;

    #[Flow\InjectConfiguration('YouTube.apiKey', 'Jonnitto.PrettyEmbed')]
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
            $ratio = PresentationUtility::getRatio($data['width'], $data['height']);
            $duration = $data['duration'] ?? null;
            if (isset($data['imageUrl'], $data['imageResolution'])) {
                $image = $data['imageUrl'];
                $resolution = $data['imageResolution'];
            } else {
                $youtubeImageArray = PresentationUtility::getBestPossibleYoutubeImage($videoID, $data['thumbnail_url'] ?? null);
                $image = $youtubeImageArray['image'];
                $resolution = $youtubeImageArray['resolution'];
            }
        } else {
            $youtubeImageArray = PresentationUtility::getBestPossibleYoutubeImage($videoID);
            $image = $youtubeImageArray['image'] ?? null;
            $resolution = $youtubeImageArray['resolution'] ?? null;
        }

        if (isset($image)) {
            $thumbnail = $this->imageService->import($node, $image, $videoID, 'Youtube', $resolution);
        }

        Utility::setMetadata($node, null, [
            'videoID' => $videoID,
            'title' => $title ?? null,
            'aspectRatio' => $ratio ?? null,
            'duration' => $duration ?? null,
            'image' => PresentationUtility::removeProtocolFromUrl($image ?? null),
            'href' => PresentationUtility::youtubeHref($videoID, $type, false),
            'embedHref' => PresentationUtility::youtubeHref($videoID, $type, true),
            'thumbnail' => $thumbnail ?? null,
        ]);

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
        if (strpos($url, 'shorts/') !== false) {
            return 'short';
        }
        return strpos($url, 'list=') !== false ? 'playlist' : 'video';
    }
}
