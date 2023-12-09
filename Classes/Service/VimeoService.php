<?php

namespace Jonnitto\PrettyEmbedHelper\Service;

use Jonnitto\PrettyEmbedHelper\Utility\Utility;
use JsonException;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Exception\NodeException;
use Neos\Flow\Annotations as Flow;
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
     * @var MetadataService
     */
    protected $metadataService;

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
            $title = $data['title'] ?? null;
            $ratio = $data['width'] && $data['height'] ? sprintf('%s / %s', $data['width'], $data['height']) : null;
            $image = $data['thumbnail_url'] ?? null;
            $duration = $data['duration'] ?? null;

            if (isset($image)) {
                try {
                    $thumbnail = $this->imageService->import($node, $image, $videoID, 'Vimeo');
                } catch (IllegalObjectTypeException | InvalidQueryException | Exception | \Exception $e) {
                }
            }
        }
        $node->setProperty('metadataID', $videoID);
        $node->setProperty('metadataTitle', $title ?? null);
        $node->setProperty('metadataRatio', $ratio ?? null);
        $node->setProperty('metadataDuration', $duration ?? null);
        $node->setProperty('metadataImage', $this->utility->removeProtocolFromUrl($image ?? null));
        $node->setProperty('metadataThumbnail', $thumbnail ?? null);

        $this->imageService->removeTagIfEmpty();

        if (!$videoIDProperty) {
            return null;
        }

        $returnArray['id'] = $videoID;
        $returnArray['data'] = isset($data);
        return $returnArray;
    }
}
