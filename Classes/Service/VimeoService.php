<?php

namespace Jonnitto\PrettyEmbedHelper\Service;

use Jonnitto\PrettyEmbedHelper\Utility\Utility;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodePath;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\Client\InfiniteRedirectionException;
use Neos\Flow\Persistence\Exception\IllegalObjectTypeException;
use Neos\Flow\Persistence\Exception\InvalidQueryException;
use Neos\Flow\ResourceManagement\Exception;
use JsonException;

#[Flow\Scope('singleton')]
class VimeoService
{
    #[Flow\Inject]
    protected ImageService $imageService;

    #[Flow\Inject]
    protected ParseIDService $parseID;

    #[Flow\Inject]
    protected MetadataService $metadataService;

    #[Flow\Inject]
    protected ApiService $api;

    /**
     * @Flow\Inject
     * @var ContentRepositoryRegistry
     */
    #[Flow\Inject]
    protected $contentRepositoryRegistry;

    /**
     * Get and save data from oembed service
     *
     * @param Node $node
     * @param boolean $remove
     * @return array|null
     * @throws IllegalObjectTypeException
     */
    public function getAndSaveDataFromApi(Node $node, bool $remove = false): ?array
    {
        $this->imageService->remove($node);

        $returnArray = [
            'nodeTypeName' => $node->nodeTypeName->value,
            'node' => 'Vimeo',
            'type' => 'Video',
            'path' => NodePath::fromNodeNames($node->name),
            'data' => false,
        ];

        if ($remove === true) {
            $this->metadataService->removeMetaData($node);
            return $returnArray;
        }

        $videoIDProperty = $node->getProperty('videoID');
        $videoID = $this->parseID->vimeo($videoIDProperty);
        try {
            $data = $this->api->vimeo($videoID);
        } catch (JsonException | InfiniteRedirectionException $e) {
        }

        if (isset($data)) {
            $videoID = $data['video_id'] ?? $videoID;
            $title = $data['title'] ?? null;
            $ratio = Utility::getRatio($data['width'], $data['height']);
            $image = $data['thumbnail_url'] ?? null;
            $duration = $data['duration'] ?? null;

            if (isset($image)) {
                try {
                    $thumbnail = $this->imageService->import($node, $image, $videoID, 'Vimeo');
                } catch (IllegalObjectTypeException | InvalidQueryException | Exception | \Exception $e) {
                }
            }
        }

        Utility::setMetadata($this->contentRepositoryRegistry, $node, null, [
            'videoID' => $videoID,
            'title' => $title ?? null,
            'aspectRatio' => $ratio ?? null,
            'duration' => $duration ?? null,
            'image' => Utility::removeProtocolFromUrl($image ?? null),
            'thumbnail' => $thumbnail ?? null,
            'href' => Utility::vimeoHref($videoID, false),
            'embedHref' => Utility::vimeoHref($videoID, true),
        ]);

        $this->imageService->removeTagIfEmpty();

        if (!$videoIDProperty) {
            return null;
        }

        $returnArray['id'] = $videoID;
        $returnArray['data'] = isset($data);
        return $returnArray;
    }
}
