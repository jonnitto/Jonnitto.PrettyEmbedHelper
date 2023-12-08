<?php

namespace Jonnitto\PrettyEmbedHelper\Service;

use JsonException;
use Neos\ContentRepository\Exception\NodeException;
use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Flow\Http\Client\InfiniteRedirectionException;
use Neos\Flow\Persistence\Exception\IllegalObjectTypeException;
use Neos\Flow\Persistence\Exception\InvalidQueryException;
use Neos\Flow\ResourceManagement\Exception;

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
            $node->getNodeType()->isOfType('Jonnitto.PrettyEmbedHelper:Mixin.Metadata.Duration')
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
        $node->setProperty('metadataID', null);
        $node->setProperty('metadataTitle', null);
        $node->setProperty('metadataRatio', null);
        $node->setProperty('metadataDuration', null);
        $node->setProperty('metadataImage', null);
        $node->setProperty('metadataThumbnail', null);
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
            ($propertyName === 'assets' &&
                $node->getNodeType()->isOfType('Jonnitto.PrettyEmbedHelper:Mixin.Metadata.Duration'))
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
        switch ($this->checkNodeAndSetPlatform($node)) {
            case 'audio':
                $data = $this->assetService->getAndSaveDataId3($node, $remove, 'Audio');
                break;

            case 'video':
                $data = $this->assetService->getAndSaveDataId3($node, $remove, 'Video');
                break;

            case 'youtube':
                try {
                    $data = $this->youtubeService->getAndSaveDataFromApi($node, $remove);
                } catch (JsonException | NodeException | InfiniteRedirectionException | IllegalObjectTypeException | InvalidQueryException | Exception $e) {
                }
                break;

            case 'vimeo':
                $data = $this->vimeoService->getAndSaveDataFromApi($node, $remove);
                break;

            default:
                return $this->defaultReturn;
        }

        return $data ?? $this->defaultReturn;
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

        if ($node->getNodeType()->isOfType('Jonnitto.PrettyEmbedYoutube:Mixin.VideoID')) {
            return 'youtube';
        }

        if ($node->getNodeType()->isOfType('Jonnitto.PrettyEmbedVimeo:Mixin.VideoID')) {
            return 'vimeo';
        }

        if (!$node->getNodeType()->isOfType('Jonnitto.PrettyEmbedVideoPlatforms:Mixin.VideoID')) {
            return null;
        }

        $platform = $this->parseID->platform($node->getProperty('videoID'));
        if (!$platform) {
            $node->setProperty('metadataDuration', null);
        }
        $node->setProperty('platform', $platform);
        return $platform;
    }
}
