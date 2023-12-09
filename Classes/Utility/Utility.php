<?php

namespace Jonnitto\PrettyEmbedHelper\Utility;

use Neos\ContentRepository\Domain\Model\NodeInterface;
use function get_headers;
use function preg_replace;
use function strpos;

class Utility
{

    /**
     * Get single metadata from node
     *
     * @param NodeInterface $node
     * @param string $property
     * @return mixed|null
     */
    public static function getMetadata(NodeInterface $node, string $property)
    {
        $propertyName = 'prettyembedMetadata';
        if (!$node->hasProperty($propertyName)) {
            return null;
        }
        $currentData = $node->getProperty($propertyName) ?? [];
        return $currentData[$property] ?? null;
    }

    /**
     * Remove all metadata from node
     *
     * @param NodeInterface $node
     * @return void
     */
    public static function removeAllMetadata(NodeInterface $node)
    {
        $propertyName = 'prettyembedMetadata';
        if (!$node->hasProperty($propertyName)) {
            return;
        }
        $node->setProperty($propertyName, []);
    }

    /**
     * Remove a metadata entry from node
     *
     * @param NodeInterface $node
     * @param string|array $properties single name or array of names
     * @return mixed Return the old value
     */
    public static function removeMetadata(NodeInterface $node, $properties)
    {
        $propertyName = 'prettyembedMetadata';
        if (!$node->hasProperty($propertyName)) {
            return null;
        }
        $currentData = $node->getProperty($propertyName) ?? [];
        if (is_array($properties)) {
            $oldValues = [];
            foreach ($properties as $property) {
                $oldValues[$property] = $currentData[$property] ?? null;
                unset($currentData[$property]);
            }
            $node->setProperty($propertyName, $currentData);
            return $oldValues;
        }

        $oldValue = $currentData[$properties] ?? null;
        unset($currentData[$properties]);
        $node->setProperty($propertyName, $currentData);
        return $oldValue;
    }

    /**
     * Save single metadata to node
     *
     * @param NodeInterface $node
     * @param mixed $value
     * @param string $property (optional) If not set, the value will be merged with the existing metadata
     * @return array the new metadata
     */
    public static function saveMetadata(NodeInterface $node, $value, ?string $property = null): array
    {
        $propertyName = 'prettyembedMetadata';
        if (!$node->hasProperty($propertyName)) {
            return [];
        }
        $currentData = $node->getProperty($propertyName) ?? [];

        if (isset($property)) {
            $currentData[$property] = $value;
        } else {
            $currentData = array_merge($currentData, $value);
        }
        $node->setProperty($propertyName, $currentData);
        return $currentData;
    }

    /**
     * Remove the protocol from url and replace it with `//`
     *
     * @param string|null $url
     * @return string|null
     */
    public static function removeProtocolFromUrl(?string $url = null): ?string
    {
        if (!is_string($url)) {
            return null;
        }
        return preg_replace('/https?:\/\//i', '//', $url);
    }

    /**
     * Get the best possible image from YouTube
     *
     * @param string|integer $videoID
     * @param string|null $url
     * @return array|null
     */
    public static function getBestPossibleYoutubeImage($videoID, ?string $url = null): ?array
    {
        if (!isset($url)) {
            $url = sprintf('https://i.ytimg.com/vi/%s/maxresdefault.jpg', $videoID);
        }

        $resolutions = ['maxresdefault', 'sddefault', 'hqdefault', 'mqdefault', 'default'];

        foreach ($resolutions as $resolution) {
            $url = preg_replace('/\/[\w]*\.([a-z]{3,})$/i', sprintf("/%s.$1", $resolution), $url);
            $headers = @get_headers($url);
            if ($headers && strpos($headers[0], '200')) {
                return [
                    'image' => $url,
                    'resolution' => $resolution,
                ];
            }
        }

        return null;
    }
}
