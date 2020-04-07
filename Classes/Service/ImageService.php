<?php

namespace Jonnitto\PrettyEmbedHelper\Service;

use Neos\Flow\Annotations as Flow;
use Doctrine\Common\Collections\ArrayCollection;
use Neos\Flow\Persistence\Exception\IllegalObjectTypeException;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Media\Domain\Model\Tag;
use Neos\Media\Domain\Model\Image;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\Media\Domain\Repository\TagRepository;

/**
 * @Flow\Scope("singleton")
 */
class ImageService
{

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @Flow\Inject
     * @var TagRepository
     */
    protected $tagRepository;

    /**
     * @Flow\Inject
     * @var AssetRepository
     */
    protected $assetRepository;


    /**
     * * @Flow\Inject
     * @var ResourceManager
     */
    protected $resourceManager;

    /**
     * @var Image[]
     */
    private $pendingThumbnailToDelete = [];


    /**
     * @param NodeInterface $node
     * @param string $url
     * @param string|integer $videoId
     * @param string $type
     * @param string|null $filenameSuffix
     * @return Image|null
     */
    public function import(NodeInterface $node, string $url, $videoId, string $type, ?string $filenameSuffix = null): ?Image
    {
        if (!$node->getNodeType()->isOfType('Jonnitto.PrettyEmbedHelper:Mixin.Metadata.Thumbnail')) {
            return null;
        }

        if (isset($filenameSuffix)) {
            $filenameSuffix = "-{$filenameSuffix}";
        }

        $assetOriginal = $url; //original asset may have get parameters in the url
        $asset = preg_replace('/(^.*\.(jpg|jpeg|png|gif|webp)).*$/', '$1', $assetOriginal); //asset witout get parametes for neos import
        $extension = preg_replace('/^.*\.(jpg|jpeg|png|gif|webp)$/', '$1', $asset); // asset extension

        $filename = "{$type}-{$videoId}{$filenameSuffix}.{$extension}";

        $availableImage = $this->assetRepository->findBySearchTermOrTags($filename)->getFirst();
        if (isset($availableImage)) {
            return $availableImage;
        }

        /** 
         * @var ArrayCollection $tags
         */
        $tags = new ArrayCollection([$this->findOrCreateTag()]);

        $resource = $this->resourceManager->importResource($assetOriginal);

        /** 
         * @var Image $image
         */
        $image = new Image($resource);
        $image->getResource()->setFilename($filename);
        $image->getResource()->setMediaType("image/{$extension}");
        $image->setTags($tags);
        $this->assetRepository->add($image);
        $this->persistenceManager->persistAll();
        return $image;
    }

    /**
     * @param NodeInterface $node
     * @return void
     */
    public function remove(NodeInterface $node): void
    {
        $thumbnail = $node->getProperty('metadataThumbnail');
        if (isset($thumbnail)) {
            $this->pendingThumbnailToDelete[$node->getIdentifier()] = $thumbnail;
        }
    }

    /**
     * @return void
     * @throws IllegalObjectTypeException 
     */
    public function removeTagIfEmpty(): void
    {
        /** 
         * @var Tag $tag
         */
        $tag = $this->findTag();

        if (isset($tag) && $this->assetRepository->countByTag($tag) === 0) {
            $this->tagRepository->remove($tag);
            $this->persistenceManager->persistAll();
        }
    }

    /**
     * @param NodeInterface $node
     * @param Workspace $targetWorkspace
     * @return void
     */
    public function removeDataAfterNodePublishing(NodeInterface $node, Workspace $targetWorkspace)
    {
        if (!$targetWorkspace->isPublicWorkspace() || !$node->isRemoved() || !$node->getNodeType()->isOfType('Jonnitto.PrettyEmbedHelper:Mixin.Metadata.Thumbnail')) {
            return;
        }

        $this->remove($node);
    }

    /**
     * @return void
     */
    public function deletePendingData(): void
    {
        foreach ($this->pendingThumbnailToDelete as $thumbnail) {
            // If the video is multiple times on the page, don't delete the thumbnail
            try {
                $this->assetRepository->remove($thumbnail);
            } catch (\Throwable $th) {
                unset($th);
            }
        }
        $this->removeTagIfEmpty();
    }

    /**
     * This calculates the padding-top from width and height
     *
     * @param integer $width
     * @param integer $height
     * @return string The calculated value
     */
    public function calculatePaddingTop(int $width, int $height): string
    {
        return (100 / ($width / $height)) . '%';
    }

    /**
     * @return Tag|null
     */
    protected function findTag(): ?Tag
    {
        return $this->tagRepository->findByLabel('PrettyEmbed')->getFirst();
    }

    /**
     * @return Tag 
     * @throws IllegalObjectTypeException 
     */
    protected function createTag(): Tag
    {
        /** 
         * @var Tag $tag
         */
        $tag = new Tag('PrettyEmbed');

        $this->tagRepository->add($tag);

        return $tag;
    }

    /**
     * @return Tag
     */
    protected function findOrCreateTag(): Tag
    {
        /** 
         * @var Tag $tag
         */
        $tag = $this->findTag();


        if ($tag === null) {
            return $this->createTag();
        }

        return $tag;
    }
}
