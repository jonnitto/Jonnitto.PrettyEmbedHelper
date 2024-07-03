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

/**
 * @Flow\Scope("singleton")
 */
class MetadataService
{
    /**
     * @Flow\Inject
     * @var AssetService
     */
    protected $assetService;

    /**
     * @Flow\Inject
     * @var YoutubeService
     */
    protected $youtubeService;

    /**
     * @Flow\Inject
     * @var VimeoService
     */
    protected $vimeoService;

    /**
     * @Flow\Inject
     * @var ParseIDService
     */
    protected $parseID;

    /**
     * @Flow\Inject
     * @var ImageService
     */
    protected $imageService;

    /**
     * @var array
     */
    protected $defaultReturn = ['node' => null];

    /**
     * Wrapper method to handle signals from Node::nodeAdded
     *
     * @param NodeInterface $node
     * @return array|null[]
     * @throws IllegalObjectTypeException
     * @throws NodeException
     */
    public function onNodeAdded(NodeInterface $node)
    {
        return $this->createDataFromService($node);
    }

    /**
     * Create data
     *
     * @param NodeInterface $node
     * @param bool $remove
     * @return array Information about the node
     * @throws NodeException
     * @throws IllegalObjectTypeException
     */
    public function createDataFromService(NodeInterface $node, bool $remove = false): array
    {
        if (
            $node->hasProperty('videoID') ||
            $node->getNodeType()->isOfType('Jonnitto.PrettyEmbedHelper:Mixin.Metadata')
        ) {
            return $this->dataFromService($node, $remove);
        }
        return $this->defaultReturn;
    }

    /**
     * Removes the metadata
     * @throws IllegalObjectTypeException
     */
    public function removeMetaData(NodeInterface $node): void
    {
        Utility::removeMetadata($node);
        $this->imageService->removeTagIfEmpty();
    }

    /**
     * Update data
     *
     * @param NodeInterface $node
     * @param string $propertyName
     * @param mixed $oldValue
     * @param mixed $newValue
     * @return array Information about the node
     * @throws NodeException
     * @throws IllegalObjectTypeException
     */
    public function updateDataFromService(NodeInterface $node, string $propertyName, $oldValue, $newValue): array
    {
        if (
            ($propertyName === 'videoID' && $oldValue !== $newValue) ||
            ($propertyName === 'type' && $node->hasProperty('videoID')) ||
            ($propertyName === 'assets' && $node->getNodeType()->isOfType('Jonnitto.PrettyEmbedHelper:Mixin.Metadata'))
        ) {
            return $this->dataFromService($node);
        }
        return $this->defaultReturn;
    }

    /**
     * Saves and returns the metadata
     *
     * @param NodeInterface $node
     * @param boolean $remove
     * @return array Information about the node
     * @throws NodeException
     * @throws IllegalObjectTypeException
     */
    protected function dataFromService(NodeInterface $node, bool $remove = false): array
    {
        $platform = $this->checkNodeAndSetPlatform($node);
        if (!$platform) {
            return $this->defaultReturn;
        }

        if ($platform == 'audio') {
            return $this->assetService->getAndSaveDataId3($node, $remove, 'Audio');
        }

        if ($platform == 'video') {
            return $this->assetService->getAndSaveDataId3($node, $remove, 'Video');
        }

        if ($platform == 'youtube') {
            try {
                $data = $this->youtubeService->getAndSaveDataFromApi($node, $remove);
            } catch (JsonException | NodeException | InfiniteRedirectionException | IllegalObjectTypeException | InvalidQueryException | Exception $e) {
            }
            return $data ?? $this->defaultReturn;
        }

        if ($platform == 'vimeo') {
            return $this->vimeoService->getAndSaveDataFromApi($node, $remove);
        }

        return $this->defaultReturn;
    }

    /**
     * Check the node and return the platform/type
     *
     * @param NodeInterface $node
     * @return string|null
     * @throws NodeException
     */
    protected function checkNodeAndSetPlatform(NodeInterface $node): ?string
    {
        if ($node->getNodeType()->isOfType('Jonnitto.PrettyEmbedAudio:Mixin.Assets')) {
            return 'audio';
        }

        if ($node->getNodeType()->isOfType('Jonnitto.PrettyEmbedVideo:Mixin.Assets')) {
            return 'video';
        }

        if (!$node->getNodeType()->isOfType('Jonnitto.PrettyEmbedVideoPlatforms:Mixin.VideoID')) {
            return null;
        }

        $platform = $this->parseID->platform($node->getProperty('videoID'));
        if (!$platform) {
            Utility::removeMetadata($node);
        }
        $node->setProperty('platform', $platform);
        return $platform;
    }
}
