<?php

namespace Jonnitto\PrettyEmbedHelper\Utility;

use Neos\ContentRepository\Domain\Model\NodeInterface;

class Utility
{
    const THUMBNAIL_PROPERTY = 'prettyembedMetadataThumbnail';
    const METADATA_PROPERTY = 'prettyembedMetadata';

    /**
     * Get Metdata value
     *
     * @param NodeInterface $node
     * @param string|null $property
     * @return void
     */
    public static function getMetadata(NodeInterface $node, ?string $property = null)
    {
        if ($property == 'thumbnail') {
            return $node->getProperty(self::THUMBNAIL_PROPERTY);
        }

        $metadata = $node->getProperty(self::METADATA_PROPERTY) ?? [];

        if (empty($property)) {
            $metadata['thumbnail'] = $node->getProperty(self::THUMBNAIL_PROPERTY);
            return $metadata;
        }

        return $metadata[$property] ?? null;
    }

    /**
     * Set Metadata value
     *
     * @param NodeInterface $node
     * @param string|null $property is null, all metadata will be replaced
     * @param mixed $value
     * @return void
     */
    public static function setMetadata(NodeInterface $node, ?string $property = null, $value = null): void
    {
        if (empty($property)) {
            $thumbnail = $value['thumbnail'] ?? null;
            unset($value['thumbnail']);
            $node->setProperty(self::THUMBNAIL_PROPERTY, $thumbnail);
            $node->setProperty(self::METADATA_PROPERTY, $value);
            return;
        }
        if ($property == 'thumbnail') {
            $node->setProperty(self::THUMBNAIL_PROPERTY, $value);
            return;
        }

        $metadata = $node->getProperty(self::METADATA_PROPERTY) ?? [];
        $metadata[$property] = $value;
        $node->setProperty(self::METADATA_PROPERTY, $metadata);
    }

    /**
     * Remove Metadata value
     *
     * @param NodeInterface $node
     * @param string|null $property If null, all metadata will be removed
     * @return void
     */
    public static function removeMetadata(NodeInterface $node, ?string $property = null): void
    {
        if ($property == 'thumbnail' || empty($property)) {
            $node->setProperty(self::THUMBNAIL_PROPERTY, null);
            return;
        }

        if (empty($property)) {
            $node->setProperty(self::METADATA_PROPERTY, []);
            return;
        }

        $metadata = $node->getProperty(self::METADATA_PROPERTY) ?? [];
        unset($metadata[$property]);
        $node->setProperty(self::METADATA_PROPERTY, $metadata);
        return;
    }
}
