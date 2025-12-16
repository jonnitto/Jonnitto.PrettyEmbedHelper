<?php

namespace Jonnitto\PrettyEmbedHelper\Service;

use Jonnitto\PrettyEmbedHelper\Utility\Utility;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodePath;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Flow\Utility\Environment;
use Neos\Flow\Utility\Exception;
use Neos\Utility\Exception\FilesException;
use Neos\Utility\Files;
use DateTime;
use function file_exists;
use function floor;

#[Flow\Scope('singleton')]
class AssetService
{
    #[Flow\Inject]
    protected Environment $environment;

    #[Flow\Inject]
    protected ResourceManager $resourceManager;

    #[Flow\Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

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
     * @param Node $node
     * @param boolean $remove
     * @param string $type
     * @return array
     */
    public function getAndSaveDataId3(Node $node, bool $remove, string $type): array
    {
        $duration = null;
        $audio = null;

        if ($remove === true || !class_exists('JamesHeinrich\GetID3\GetID3')) {
            Utility::removeMetadata($this->contentRepositoryRegistry, $node, 'duration');
        } else {
            $this->setCacheDirectory();
            $assets = $node->getProperty('assets');

            if (isset($assets) && !empty($assets)) {
                $getID3 = new \JamesHeinrich\GetID3\GetID3();
                $file = $assets[0]->getResource()->createTemporaryLocalCopy();
                if (file_exists($file)) {
                    $fileInfo = $getID3->analyze($file);
                    $duration = (int) floor($fileInfo['playtime_seconds']);

                    if ($type === 'Audio') {
                        $audio = $this->audioData($fileInfo, $duration);
                    }
                }
            }
            if ($type === 'Audio') {
                Utility::setMetadata($this->contentRepositoryRegistry, $node, null, $audio);
            } else {
                Utility::setMetadata($this->contentRepositoryRegistry, $node, 'duration', $duration);
            }
        }

        return [
            'nodeTypeName' => $node->nodeTypeName->value,
            'node' => $type,
            'type' => '',
            'id' => '',
            'path' => NodePath::fromNodeNames($node->name),
            'data' => isset($duration),
        ];
    }

    private function audioData(array $fileInfo, int $duration): array
    {
        if (isset($fileInfo['tags'])) {
            $idKey = array_key_first($fileInfo['tags']);
            $tags = $fileInfo['tags'][$idKey];
        }

        if (!isset($tags) && isset($fileInfo['id3v2']) && isset($fileInfo['id3v2']['comments'])) {
            $tags = $fileInfo['id3v2']['comments'];
        }

        if (!isset($tags) && isset($fileInfo['id3v1'])) {
            $tags = $fileInfo['id3v1'];
        }

        if (isset($tags)) {
            $year = $tags['year'][0] ?? null;
            $monthAndDay = $tags['date'][0] ?? null;

            if ($year && $monthAndDay) {
                $date = DateTime::createFromFormat('Ydm-H:i', $year . $monthAndDay . '-00:00');
            }
            $title = $tags['title'][0] ?? null;
            $artist = $tags['artist'][0] ?? null;
            $album = $tags['album'][0] ?? null;
        }

        return array_filter([
            'duration' => $duration,
            'date' => $date ?? null,
            'title' => $title ?? null,
            'artist' => $artist ?? null,
            'album' => $album ?? null,
        ]);
    }
}
