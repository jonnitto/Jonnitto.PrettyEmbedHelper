<?php

namespace Jonnitto\PrettyEmbedHelper\Service;

use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Jonnitto\PrettyEmbedHelper\Service\ParseID;
use Jonnitto\PrettyEmbedHelper\Service\Oembed;

/**
 * @Flow\Scope("singleton")
 */
class Metadata
{

    /**
     * @Flow\InjectConfiguration(package="Jonnitto.PrettyEmbedYoutube")
     * @var array
     */
    protected $youtubeSettings;

    /**
     * @param NodeInterface $node
     * @return array Informations about the node
     */

    public function createDataFromService(NodeInterface $node, bool $remove = false): array
    {
        if ($node->hasProperty('videoID')) {
            return $this->dataFromService($node, $remove);
        }
        return [
            'node' => null
        ];
    }

    /**
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
        if ($propertyName === 'videoID' & $oldValue !== $newValue) {
            return $this->dataFromService($node);
        } else if ($propertyName === 'type' && $node->hasProperty('videoID')) {
            return $this->dataFromService($node);
        }
        return [
            'node' => null
        ];
    }

    /**
     * @param NodeInterface $node
     * @param boolean $remove 
     * @return array Informations about the node
     */
    protected function dataFromService(NodeInterface $node, bool $remove = false): array
    {

        if ($node->getNodeType()->isOfType('Jonnitto.PrettyEmbedVimeo:Mixin.VideoID')) {
            // Check Vimeo
            $videoID = null;
            $data = null;
            $title = null;
            $ratio = null;
            $image = null;

            if ($remove === false) {
                $videoID = ParseID::vimeo($node->getProperty('videoID'));
                $data = Oembed::vimeo($videoID);

                if (isset($data)) {
                    $title = $data->title ?? null;
                    $ratio = $data->width && $data->height ? $this->calculatePaddingTop($data->width, $data->height) : null;
                    $image = Oembed::removeProtocolFromUrl($data->thumbnail_url) ?? null;
                }
            }

            $node->setProperty('metadataID', $videoID);
            $node->setProperty('metadataTitle', $title);
            $node->setProperty('metadataRatio', $ratio);
            $node->setProperty('metadataImage', $image);

            return [
                'nodeTypeName' => $node->getNodeType()->getName(),
                'node' => 'Vimeo',
                'type' => 'Video',
                'id' => $videoID,
                'path' => $node->getPath(),
                'data' => isset($data)
            ];
        } else if ($node->getNodeType()->isOfType('Jonnitto.PrettyEmbedYoutube:Mixin.VideoID')) {
            // Check Youtube
            $videoID = null;
            $data = null;
            $title = null;
            $ratio = null;
            $image = null;
            $type = null;

            if ($remove === false) {
                $type = $this->youtubeSettings['defaults']['type'];
                if ($node->hasProperty('type')) {
                    $typeFromProperty = $node->getProperty('type');
                    if ($typeFromProperty == 'video' || $typeFromProperty == 'playlist') {
                        $type = $typeFromProperty;
                    }
                }
                $videoID = ParseID::youtube($node->getProperty('videoID'));
                $data = Oembed::youtube($videoID, $type);

                if (isset($data)) {
                    $title = $data->title ?? null;
                    $ratio = $data->width && $data->height ? $this->calculatePaddingTop($data->width, $data->height) : null;
                    $image = $this->getBestPossibleYoutubeImage($videoID, $data->thumbnail_url);
                } else {
                    $image = $this->getBestPossibleYoutubeImage($videoID);
                }
            }

            $node->setProperty('metadataID', $videoID);
            $node->setProperty('metadataTitle', $title);
            $node->setProperty('metadataRatio', $ratio);
            $node->setProperty('metadataImage', $image);

            return [
                'nodeTypeName' => $node->getNodeType()->getName(),
                'node' => 'Youtube',
                'type' => ucfirst($type),
                'id' => $videoID,
                'path' => $node->getPath(),
                'data' => isset($data)
            ];
        }
        return [
            'node' => null
        ];
    }

    /**
     * Get the best possible image from youtube
     *
     * @param string|integer $videoID
     * @param string|null $url
     * @return string|null
     */
    protected function getBestPossibleYoutubeImage($videoID, ?string $url = null): ?string
    {
        if (!isset($url)) {
            $url = "https://i.ytimg.com/vi/{$videoID}/maxresdefault.jpg";
        }

        $resulutions = ['maxresdefault', 'sddefault', 'hqdefault', 'mqdefault', 'default'];

        foreach ($resulutions as $resultion) {
            $url = preg_replace('/\/[\w]*\.([a-z]{3,})$/i', "/{$resultion}.$1", $url);
            $headers = @get_headers($url);
            if ($headers && strpos($headers[0], '200')) {
                return Oembed::removeProtocolFromUrl($url);
            }
        }

        return null;
    }


    /**
     * This calculates the padding-top from width and height
     *
     * @param integer $width
     * @param integer $height
     * @return string The calculated value
     */
    protected function calculatePaddingTop(int $width, int $height): string
    {
        return (100 / ($width / $height)) . '%';
    }
}
