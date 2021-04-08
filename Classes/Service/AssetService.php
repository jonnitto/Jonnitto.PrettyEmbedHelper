<?php

namespace Jonnitto\PrettyEmbedHelper\Service;

use JamesHeinrich\GetID3\GetID3;
use JamesHeinrich\GetID3\Utils as GetID3Utils;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Flow\Utility\Environment;
use Neos\Utility\Files;


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
        $cacheDirectory = Files::concatenatePaths([
            $this->environment->getPathToTemporaryDirectory(),
            (string) $this->environment->getContext(),
            'Jonnitto_PrettyEmbedHelper_GetID3_Cache'
        ]);
        Files::createDirectoryRecursively($cacheDirectory);
        GetID3Utils::setTempDirectory($cacheDirectory);
    }

    /**
     * Save the duration in seconds from audio or video files
     *
     * @param NodeInterface $node
     * @param boolean $remove
     * @param string $type
     * @return array
     */
    public function getAndSaveDataId3(NodeInterface $node, bool $remove = false, string $type): array
    {
        $duration = null;
        if ($remove === true) {
            $node->setProperty('metadataDuration', null);
        } else {
            $this->setCacheDirectory();
            $assets = $node->getProperty('assets');

            if (isset($assets) && !empty($assets)) {
                $getID3 = new GetID3;
                $file = $assets[0]->getResource()->createTemporaryLocalCopy();
                if (\file_exists($file)) {
                    $fileInfo = $getID3->analyze($file);
                    $duration = (int) \round($fileInfo['playtime_seconds']);
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
            'data' => isset($duration)
        ];
    }
}
