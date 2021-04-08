<?php

namespace Jonnitto\PrettyEmbedHelper\Service;

use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\Model\NodeInterface;

/**
 * @Flow\Scope("singleton")
 */
class VimeoService
{
    /**
     * @Flow\Inject
     * @var ImageService
     */
    protected $imageService;

    /**
     * Get and save data from oembed service
     * 
     * @param NodeInterface $node
     * @param boolean $remove
     * @return array|null
     */
    public function getAndSaveDataFromOembed(NodeInterface $node, bool $remove = false): ?array
    {
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
            $videoID = ParseIDService::vimeo($videoIDProperty);
            $data = OembedService::vimeo($videoID);

            if (isset($data)) {
                $title = $data->title ?? null;
                $ratio = $data->width && $data->height ? $this->imageService->calculatePaddingTop($data->width, $data->height) : null;
                $image = $data->thumbnail_url ?? null;
                $duration = $data->duration ?? null;

                if (isset($image)) {
                    $thumbnail = $this->imageService->import($node, $image, $videoID, 'Vimeo');
                }
            }
        }

        $node->setProperty('metadataID', $videoID);
        $node->setProperty('metadataTitle', $title);
        $node->setProperty('metadataRatio', $ratio);
        $node->setProperty('metadataDuration', $duration);
        $node->setProperty('metadataImage', OembedService::removeProtocolFromUrl($image));
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
