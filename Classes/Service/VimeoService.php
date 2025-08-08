<?php

namespace Jonnitto\PrettyEmbedHelper\Service;

use Jonnitto\PrettyEmbedHelper\Utility\Utility;
use Jonnitto\PrettyEmbedPresentation\Service\ApiService;
use Jonnitto\PrettyEmbedPresentation\Service\ParseIDService;
use Jonnitto\PrettyEmbedPresentation\Utility\Utility as PresentationUtility;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Exception\NodeException;
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
     * Get and save data from oembed service
     *
     * @param NodeInterface $node
     * @param boolean $remove
     * @return array|null
     * @throws NodeException
     * @throws IllegalObjectTypeException
     */
    public function getAndSaveDataFromApi(NodeInterface $node, bool $remove = false): ?array
    {
        $this->imageService->remove($node);

        $returnArray = [
            'nodeTypeName' => $node->getNodeType()->getName(),
            'node' => 'Vimeo',
            'type' => 'Video',
            'path' => $node->getPath(),
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
            $ratio = PresentationUtility::getRatio($data['width'], $data['height']);
            $image = $data['thumbnail_url'] ?? null;
            $duration = $data['duration'] ?? null;
            $hash = isset($data['uri']) && str_contains($data['uri'], ':') ? explode(':', $data['uri'])[1] : null;

            if (isset($image)) {
                try {
                    $thumbnail = $this->imageService->import($node, $image, $videoID, 'Vimeo');
                } catch (IllegalObjectTypeException | InvalidQueryException | Exception | \Exception $e) {
                }
            }
        }

        Utility::setMetadata($node, null, [
            'videoID' => $videoID,
            'hash' => $hash,
            'title' => $title ?? null,
            'aspectRatio' => $ratio ?? null,
            'duration' => $duration ?? null,
            'image' => PresentationUtility::removeProtocolFromUrl($image ?? null),
            'thumbnail' => $thumbnail ?? null,
            'href' => PresentationUtility::vimeoHref($videoID, false, $hash),
            'embedHref' => PresentationUtility::vimeoHref($videoID, true, $hash),
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
