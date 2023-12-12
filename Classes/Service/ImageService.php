<?php

namespace Jonnitto\PrettyEmbedHelper\Service;

use Doctrine\Common\Collections\ArrayCollection;
use Jonnitto\PrettyEmbedHelper\Utility\Utility;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\ContentRepository\Exception\NodeException;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\Exception\IllegalObjectTypeException;
use Neos\Flow\Persistence\Exception\InvalidQueryException;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\ResourceManagement\Exception;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Media\Domain\Model\Image;
use Neos\Media\Domain\Model\Tag;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\Media\Domain\Repository\TagRepository;
use Throwable;
use function explode;
use function pathinfo;
use function strtolower;

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
     * Import image
     *
     * @param NodeInterface $node
     * @param string $url
     * @param string|integer $videoId
     * @param string $type
     * @param string|null $filenameSuffix
     * @return object|null
     * @throws IllegalObjectTypeException
     * @throws Exception|InvalidQueryException
     * @throws \Exception
     */
    public function import(
        NodeInterface $node,
        string $url,
        $videoId,
        string $type,
        ?string $filenameSuffix = null
    ): ?object {
        if (!$node->getNodeType()->isOfType('Jonnitto.PrettyEmbedHelper:Mixin.Metadata')) {
            return null;
        }

        if (isset($filenameSuffix)) {
            $filenameSuffix = '-' . $filenameSuffix;
        }

        $pathParts = pathinfo(strtolower($url));
        $extension = isset($pathParts['extension']) ? explode('?', $pathParts['extension'])[0] : null;

        if (!$extension) {
            // If no extension is set, set it to jpg
            $extension = 'jpg';
            if ($type === 'Vimeo') {
                // Vimeo don't give us an extension, as they offer avif, webp and jpg based on the browser support
                // Because of that, we add .jpg to ensure we got the jpg variant
                $url .= '.jpg';
            }
        }

        $filename = sprintf('%s-%s%s.%s', $type, str_replace('/', '-', $videoId), $filenameSuffix, $extension);

        $availableImage = $this->assetRepository->findBySearchTermOrTags($filename)->getFirst();
        if (isset($availableImage)) {
            return $availableImage;
        }

        /**
         * @var ArrayCollection $tags
         */
        $tags = new ArrayCollection([$this->findOrCreateTag()]);

        $resource = $this->resourceManager->importResource($url);

        /**
         * @var Image $image
         */
        $image = new Image($resource);
        $image->getResource()->setFilename($filename);
        $image->getResource()->setMediaType('image/' . $extension);
        $image->setTags($tags);
        $this->assetRepository->add($image);
        $this->persistenceManager->persistAll();
        return $image;
    }

    /**
     * Remove image
     *
     * @param NodeInterface $node
     * @return void
     * @throws NodeException
     */
    public function remove(NodeInterface $node): void
    {
        $thumbnail = Utility::getMetadata($node, 'thumbnail');
        if (isset($thumbnail)) {
            $this->pendingThumbnailToDelete[$node->getIdentifier()] = $thumbnail;
        }
    }

    /**
     * Remove the prettyembed tag if he is empty
     *
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
     * Remove all unused images
     *
     * @return void
     * @throws IllegalObjectTypeException
     * @throws InvalidQueryException
     */
    public function removeAllUnusedImages(): void
    {
        /**
         * @var Tag $tag
         */
        $tag = $this->findTag();

        if (isset($tag)) {
            $images = $this->assetRepository->findByTag($tag)->toArray();
            foreach ($images as $image) {
                try {
                    $this->assetRepository->remove($image);
                } catch (Throwable $th) {
                }
            }

            if ($this->assetRepository->countByTag($tag) === 0) {
                $this->tagRepository->remove($tag);
            }
            $this->persistenceManager->persistAll();
        }
    }

    /**
     * This gets triggered after node publishing and put the data into the pending array
     *
     * @param NodeInterface $node
     * @param Workspace $targetWorkspace
     * @return void
     * @throws NodeException
     */
    public function removeDataAfterNodePublishing(NodeInterface $node, Workspace $targetWorkspace): void
    {
        if (
            !$targetWorkspace->isPublicWorkspace() ||
            !$node->isRemoved() ||
            !$node->getNodeType()->isOfType('Jonnitto.PrettyEmbedHelper:Mixin.Metadata')
        ) {
            return;
        }

        $this->remove($node);
    }

    /**
     * Deletes the pending data
     *
     * @return void
     * @throws IllegalObjectTypeException
     */
    public function deletePendingData(): void
    {
        foreach ($this->pendingThumbnailToDelete as $thumbnail) {
            // If the video is multiple times on the page, don't delete the thumbnail
            try {
                $this->assetRepository->remove($thumbnail);
            } catch (Throwable $th) {
                unset($th);
            }
        }

        if (count($this->pendingThumbnailToDelete) > 0) {
            // NOTE: this "if" condition is crucial to prevent a fatal error during an initial "./flow doctrine:migrate"
            // if the database is completely empty at the start. Without this condition, the following happens (in Neos 7
            // at least):
            //
            // Background:
            // - in older Flow versions, the Neos\Flow\Mvc\Dispatcher was used BOTH for Web and CLI requests.
            //   Thus, the "afterControllerInvocation" signal was emitted for both cases.
            // - With Flow 6.0, the Neos\Flow\Mvc\Dispatcher was split apart; so the CLI uses its separate Neos\Flow\Cli\Dispatcher.
            // - however, *FOR BACKWARDS COMPATIBILITY REASONS* (probably) the CLI Dispatcher emits the "afterControllerInvocation"
            //   in the name of the Mvc\Dispatcher; effectively keeping the old behavior as before.
            //
            // Problem Scenario:
            // - The database is completely empty.
            // - ./flow doctrine:migrate is triggered.
            // - this triggers a sub request "./flow neos.flow:doctrine:compileproxies"
            // - when this sub request ENDS, the signal afterControllerInvocation is invoked (see "Background" above).
            // - this triggers PersistenceManagerInterface::persistAll() -
            //   see https://github.com/neos/flow-development-collection/blob/1493e0dfd96f9cb288b006917e0defe6e9449544/Neos.Flow/Classes/Package.php#L63
            // - this, in turn, sends the signal "allObjectsPersisted" (which we listen to in the ../Package.php).
            // - this triggers "deletePendingData" (this method).
            // - (remember, at this point NO DATABASE TABLE WAS CREATED YET)
            // - now, removeTagIfEmpty() unconditionally tries to query the database (via findTag())
            // - findTag() crashes with "A table or view seems to be missing from the database."
            //
            // The FIX:
            // - I believe the clean fix should be that PersistenceManagerInterface should not call allObjectsPersisted
            //   when the database is not ready yet (though I do not know whether this is possible to efficiently detect this)
            // - Thus, our workaround is to ensure we will only remove tags if there were thumbnails to delete (which
            //   is, in the initial case, not possible, as we do not have a database yet :-) )
            //
            // To sum it up again, the if statement above is important to ensure this only runs with fully initialized
            // databases.
            $this->removeTagIfEmpty();
        }
        $this->pendingThumbnailToDelete = [];
    }

    /**
     * Find the "PrettyEmbed" tag
     *
     * @return Tag|null
     */
    protected function findTag(): ?Tag
    {
        return $this->tagRepository->findByLabel('PrettyEmbed')->getFirst();
    }

    /**
     * Create the "PrettyEmbed" tag
     *
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
     * Find/create the "PrettyEmbed" tag
     *
     * @return Tag
     * @throws IllegalObjectTypeException
     */
    protected function findOrCreateTag(): Tag
    {
        /**
         * @var Tag $tag
         */
        $tag = $this->findTag();

        return $tag ?? $this->createTag();
    }
}
