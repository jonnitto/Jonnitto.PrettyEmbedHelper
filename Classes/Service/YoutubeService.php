<?php

namespace Jonnitto\PrettyEmbedHelper\Service;

use Jonnitto\PrettyEmbedHelper\Utility\Utility;
use Jonnitto\PrettyEmbedPresentation\Service\ApiService;
use Jonnitto\PrettyEmbedPresentation\Service\ParseIDService;
use Jonnitto\PrettyEmbedPresentation\Utility\Utility as PresentationUtility;
use Neos\ContentRepository\Core\Feature\NodeModification\Command\SetNodeProperties;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\PropertyValuesToWrite;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodePath;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\Client\InfiniteRedirectionException;
use Neos\Flow\Persistence\Exception\IllegalObjectTypeException;
use Neos\Flow\Persistence\Exception\InvalidQueryException;
use Neos\Flow\ResourceManagement\Exception;
use JsonException;
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

    #[Flow\Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    #[Flow\InjectConfiguration('YouTube.apiKey', 'Jonnitto.PrettyEmbed')]
    protected $apiKey;

    /**
     * Get and save data from oembed service
     *
     * @param Node $node
     * @param boolean $remove
     * @return array|null
     * @throws IllegalObjectTypeException
     * @throws Exception|InfiniteRedirectionException
     * @throws JsonException|InvalidQueryException
     */
    public function getAndSaveDataFromApi(Node $node, bool $remove = false): ?array
    {
        $this->imageService->remove($node);

        $returnArray = [
            'nodeTypeName' => $node->nodeTypeName->value,
            'node' => 'Youtube',
            'path' => NodePath::fromNodeNames($node->name),
            'data' => false,
        ];

        if ($remove === true) {
            $this->metadataService->removeMetaData($node);
            return $returnArray;
        }

        $videoIDProperty = $node->getProperty('videoID');
        $type = $this->type($videoIDProperty);

        $contentRepository = $this->contentRepositoryRegistry->get($node->contentRepositoryId);
        $contentRepository->handle(
            SetNodeProperties::create(
                $node->workspaceName,
                $node->aggregateId,
                $node->originDimensionSpacePoint,
                PropertyValuesToWrite::fromArray([
                    'type' => $type,
                ]),
            ),
        );

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

        Utility::setMetadata($this->contentRepositoryRegistry, $node, null, [
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
