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

/**
 * @Flow\Scope("singleton")
 */
class VimeoService
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
     * Get and save data from oembed service
     *
     * @param NodeInterface $node
     * @param boolean $remove
     * @return array|null
     * @throws NodeException
     * @throws IllegalObjectTypeException
     */
    public function getAndSaveDataFromApi(
        NodeInterface $node,
        bool $remove = false
    ): ?array {
        $videoIDProperty = null;
        $videoID = null;
        $data = null;
        $title = null;
        $ratio = null;
        $image = null;
        $thumbnail = null;
        $duration = null;

        $this->imageService->remove($node);

        if ($remove === false) {
            $videoIDProperty = $node->getProperty('videoID');
            $videoID = $this->parseID->vimeo($videoIDProperty);
            try {
                $data = $this->api->vimeo($videoID);
            } catch (JsonException | InfiniteRedirectionException $e) {
            }

            if (isset($data)) {
                $title = $data['title'] ?? null;
                $ratio = $data['width'] && $data['height'] ?
                    $this->utility->calculatePaddingTop(
                        $data['width'],
                        $data['height']
                    ) :
                    null;
                $image = $data['thumbnail_url'] ?? null;
                $duration = $data['duration'] ?? null;

                if (isset($image)) {
                    try {
                        $thumbnail = $this->imageService->import(
                            $node,
                            $image,
                            $videoID,
                            'Vimeo'
                        );
                    } catch (IllegalObjectTypeException | InvalidQueryException | Exception | \Exception $e) {
                    }
                }
            }
        }

        $node->setProperty('metadataID', $videoID);
        $node->setProperty('metadataTitle', $title);
        $node->setProperty('metadataRatio', $ratio);
        $node->setProperty('metadataDuration', $duration);
        $node->setProperty(
            'metadataImage',
            $this->utility->removeProtocolFromUrl($image)
        );
        $node->setProperty('metadataThumbnail', $thumbnail);

        $this->imageService->removeTagIfEmpty();

        if ($videoIDProperty || $remove) {
            return [
                'nodeTypeName' => $node->getNodeType()->getName(),
                'node' => 'Vimeo',
                'type' => 'Video',
                'id' => $videoID,
                'path' => $node->getPath(),
                'data' => isset($data)
            ];
        }

        return null;
    }
}
