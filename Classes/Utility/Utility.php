<?php

namespace Jonnitto\PrettyEmbedHelper\Utility;

use Neos\ContentRepository\Core\Feature\NodeModification\Command\SetNodeProperties;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\PropertyValuesToWrite;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;

class Utility
{
    const THUMBNAIL_PROPERTY = 'prettyembedMetadataThumbnail';
    const METADATA_PROPERTY = 'prettyembedMetadata';

    /**
     * Get Metdata value
     *
     * @param Node $node
     * @param string|null $property
     * @return mixed
     */
    public static function getMetadata(Node $node, ?string $property = null): mixed
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
     * @param Node $node
     * @param string|null $property is null, all metadata will be replaced
     * @param mixed $value
     * @return void
     */
    public static function setMetadata(
        ContentRepositoryRegistry $contentRepositoryRegistry,
        Node $node,
        ?string $property = null,
        $value = null,
    ): void {
        $contentRepository = $contentRepositoryRegistry->get($node->contentRepositoryId);

        if (empty($property)) {
            $thumbnail = $value['thumbnail'] ?? null;
            unset($value['thumbnail']);
            $contentRepository->handle(
                SetNodeProperties::create(
                    $node->workspaceName,
                    $node->aggregateId,
                    $node->originDimensionSpacePoint,
                    PropertyValuesToWrite::fromArray([
                        self::THUMBNAIL_PROPERTY => $thumbnail,
                        self::METADATA_PROPERTY => $value,
                    ]),
                ),
            );
            return;
        }
        if ($property == 'thumbnail') {
            $contentRepository->handle(
                SetNodeProperties::create(
                    $node->workspaceName,
                    $node->aggregateId,
                    $node->originDimensionSpacePoint,
                    PropertyValuesToWrite::fromArray([
                        self::THUMBNAIL_PROPERTY => $value,
                    ]),
                ),
            );
            return;
        }

        $metadata = $node->getProperty(self::METADATA_PROPERTY) ?? [];
        $metadata[$property] = $value;
        $contentRepository->handle(
            SetNodeProperties::create(
                $node->workspaceName,
                $node->aggregateId,
                $node->originDimensionSpacePoint,
                PropertyValuesToWrite::fromArray([
                    self::METADATA_PROPERTY => $metadata,
                ]),
            ),
        );
    }

    /**
     * Remove Metadata value
     *
     * @param Node $node
     * @param string|null $property If null, all metadata will be removed
     * @return void
     */
    public static function removeMetadata(
        ContentRepositoryRegistry $contentRepositoryRegistry,
        Node $node,
        ?string $property = null,
    ): void {
        $contentRepository = $contentRepositoryRegistry->get($node->contentRepositoryId);

        if ($property == 'thumbnail') {
            $contentRepository->handle(
                SetNodeProperties::create(
                    $node->workspaceName,
                    $node->aggregateId,
                    $node->originDimensionSpacePoint,
                    PropertyValuesToWrite::fromArray([
                        self::THUMBNAIL_PROPERTY => null,
                    ]),
                ),
            );
            return;
        } elseif (empty($property)) {
            $contentRepository->handle(
                SetNodeProperties::create(
                    $node->workspaceName,
                    $node->aggregateId,
                    $node->originDimensionSpacePoint,
                    PropertyValuesToWrite::fromArray([
                        self::THUMBNAIL_PROPERTY => null,
                        self::METADATA_PROPERTY => [],
                    ]),
                ),
            );
            return;
        }

        $metadata = $node->getProperty(self::METADATA_PROPERTY) ?? [];
        unset($metadata[$property]);
        $contentRepository->handle(
            SetNodeProperties::create(
                $node->workspaceName,
                $node->aggregateId,
                $node->originDimensionSpacePoint,
                PropertyValuesToWrite::fromArray([
                    self::METADATA_PROPERTY => $metadata,
                ]),
            ),
        );
    }
}
