<?php

namespace Jonnitto\PrettyEmbedHelper\Service;

use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Exception\NodeException;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Flow\Utility\Environment;
use Neos\Flow\Utility\Exception;
use Neos\Utility\Exception\FilesException;
use Neos\Utility\Files;
use function file_exists;
use function round;

/**
 * @Flow\Scope("singleton")
 */
class AssetService
{
    /**
     * @Flow\Inject
     * @var Environment
     */
    protected $environment;

    /**
     * @Flow\Inject
     * @var ResourceManager
     */
    protected $resourceManager;

    /**
     * Set cache directory
     *
     * @return void
     */
    protected function setCacheDirectory(): void
    {
        if (class_exists('JamesHeinrich\GetID3\Utils')) {
            try {
                $cacheDirectory = Files::concatenatePaths([
                    $this->environment->getPathToTemporaryDirectory(),
                    (string) $this->environment->getContext(),
                    'Jonnitto_PrettyEmbedHelper_GetID3_Cache',
                ]);
                Files::createDirectoryRecursively($cacheDirectory);
                \JamesHeinrich\GetID3\Utils::setTempDirectory($cacheDirectory);
            } catch (Exception | FilesException $e) {
            }
        }
    }

    /**
     * Save the duration in seconds from audio or video files
     *
     * @param NodeInterface $node
     * @param boolean $remove
     * @param string $type
     * @return array
     * @throws NodeException
     */
    public function getAndSaveDataId3(NodeInterface $node, bool $remove, string $type): array
    {
        $duration = null;
        if ($remove === true || !class_exists('JamesHeinrich\GetID3\GetID3')) {
            $node->setProperty('metadataDuration', null);
        } else {
            $this->setCacheDirectory();
            $assets = $node->getProperty('assets');

            if (isset($assets) && !empty($assets)) {
                $getID3 = new \JamesHeinrich\GetID3\GetID3();
                $file = $assets[0]->getResource()->createTemporaryLocalCopy();
                if (file_exists($file)) {
                    $fileInfo = $getID3->analyze($file);
                    $duration = (int) round($fileInfo['playtime_seconds']);
                }
            }
            $node->setProperty('metadataDuration', $duration);
        }

        return [
            'nodeTypeName' => $node->getNodeType()->getName(),
            'node' => $type,
            'type' => '',
            'id' => '',
            'path' => $node->getPath(),
            'data' => isset($duration),
        ];
    }
}
