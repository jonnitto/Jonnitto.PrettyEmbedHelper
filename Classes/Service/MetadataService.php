<?php

namespace Jonnitto\PrettyEmbedHelper\Service;

use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Jonnitto\PrettyEmbedHelper\Service\AssetService;
use Jonnitto\PrettyEmbedHelper\Service\VimeoService;
use Jonnitto\PrettyEmbedHelper\Service\YoutubeService;

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
     * @return array Informations about the node
     */
    public function createDataFromService(NodeInterface $node, bool $remove = false): array
    {
        if ($node->hasProperty('videoID') || $node->getNodeType()->isOfType('Jonnitto.PrettyEmbedHelper:Mixin.Metadata.Duration')) {
            return $this->dataFromService($node, $remove);
        }
        return $this->defaultReturn;
    }

    /**
     * Update data
     *
     * @param NodeInterface $node
     * @param string $propertyName
     * @param mixed $oldValue
     * @param mixed $newValue
     * @return array Informations about the node
     */
    public function updateDataFromService(
        NodeInterface $node,
        string $propertyName,
        $oldValue,
        $newValue
    ): array {
        if (
            ($propertyName === 'videoID' && $oldValue !== $newValue) ||
            ($propertyName === 'type' && $node->hasProperty('videoID')) ||
            ($propertyName === 'assets' && $node->getNodeType()->isOfType('Jonnitto.PrettyEmbedHelper:Mixin.Metadata.Duration'))
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
     * @return array Informations about the node
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
                $data = $this->youtubeService->getAndSaveDataFromOembed($node, $remove);
                break;

            case 'vimeo':
                $data = $this->vimeoService->getAndSaveDataFromOembed($node, $remove);
                break;

            default:
                return $this->defaultReturn;
                break;
        }

        if (isset($data)) {
            return $data;
        }

        return $this->defaultReturn;
    }

    /**
     * Check the node and return the platform/type
     *
     * @param NodeInterface $node
     * @return string|null
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

        $platform = ParseIDService::platform($node->getProperty('videoID'));
        if (!$platform) {
            $node->setProperty('metadataDuration', null);
        }
        $node->setProperty('platform', $platform);
        return $platform;
    }
}
