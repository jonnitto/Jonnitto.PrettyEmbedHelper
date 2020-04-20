<?php

namespace Jonnitto\PrettyEmbedHelper\Service;

use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Jonnitto\PrettyEmbedHelper\Service\ImageService;
use Jonnitto\PrettyEmbedHelper\Service\ParseIDService;
use Jonnitto\PrettyEmbedHelper\Service\OembedService;

/**
 * @Flow\Scope("singleton")
 */
class YoutubeService
{
    /**
     * @Flow\Inject
     * @var ImageService
     */
    protected $imageService;

    /**
     * @Flow\InjectConfiguration(package="Jonnitto.PrettyEmbedYoutube")
     * @var array
     */
    protected $youtubeSettings;

    /**
     * @param NodeInterface $node
     * @param boolean $remove
     * @return array|null
     */
    public function getAndSaveDataFromOembed(NodeInterface $node, bool $remove = false): ?array
    {
        $isYoutubePackage = $node->getNodeType()->isOfType('Jonnitto.PrettyEmbedYoutube:Mixin.VideoID');

        $videoIDProperty = null;
        $videoID = null;
        $data = null;
        $title = null;
        $ratio = null;
        $image = null;
        $type = null;
        $thumbnail = null;
        $resolution = null;

        $this->imageService->remove($node);

        if ($remove === false) {
            $videoIDProperty = $node->getProperty('videoID');

            if ($isYoutubePackage) {
                $type = $this->youtubeSettings['defaults']['type'];
                if ($node->hasProperty('type')) {
                    $typeFromProperty = $node->getProperty('type');
                    if ($typeFromProperty === 'video' || $typeFromProperty === 'playlist') {
                        $type = $typeFromProperty;
                    }
                }
            } else {
                $type = ParseIDService::youtubeType($videoIDProperty) ?? 'video';
                $node->setProperty('type', $type);
            }

            $videoID = ParseIDService::youtube($videoIDProperty, $type);
            $data = OembedService::youtube($videoID, $type);

            if (isset($data)) {
                $title = $data->title ?? null;
                $ratio = $data->width && $data->height ? $this->imageService->calculatePaddingTop($data->width, $data->height) : null;
                $youtubeImageArray = $this->getBestPossibleYoutubeImage($videoID, $data->thumbnail_url);
                $image = $youtubeImageArray['image'];
                $resolution = $youtubeImageArray['resolution'];
            } else {
                $youtubeImageArray = $this->getBestPossibleYoutubeImage($videoID);
                $image = $youtubeImageArray['image'];
                $resolution = $youtubeImageArray['resolution'];
            }

            if (isset($image)) {
                $thumbnail = $this->imageService->import($node, $image, $videoID, 'Youtube', $resolution);
            }
        }

        $node->setProperty('metadataID', $videoID);
        $node->setProperty('metadataTitle', $title);
        $node->setProperty('metadataRatio', $ratio);
        $node->setProperty('metadataImage', OembedService::removeProtocolFromUrl($image));
        $node->setProperty('metadataThumbnail', $thumbnail);

        $this->imageService->removeTagIfEmpty();

        if ($videoIDProperty || $remove) {
            return [
                'nodeTypeName' => $node->getNodeType()->getName(),
                'node' => 'Youtube',
                'type' => ucfirst($type),
                'id' => $videoID,
                'path' => $node->getPath(),
                'data' => isset($data)
            ];
        }

        return null;
    }

    /**
     * Get the best possible image from youtube
     *
     * @param string|integer $videoID
     * @param string|null $url
     * @return array|null
     */
    protected function getBestPossibleYoutubeImage($videoID, ?string $url = null): ?array
    {
        if (!isset($url)) {
            $url = "https://i.ytimg.com/vi/{$videoID}/maxresdefault.jpg";
        }

        $resolutions = ['maxresdefault', 'sddefault', 'hqdefault', 'mqdefault', 'default'];

        foreach ($resolutions as $resolution) {
            $url = preg_replace('/\/[\w]*\.([a-z]{3,})$/i', "/{$resolution}.$1", $url);
            $headers = @get_headers($url);
            if ($headers && strpos($headers[0], '200')) {
                return [
                    'image' => $url,
                    'resolution' => $resolution
                ];
            }
        }

        return null;
    }
}
