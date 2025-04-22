<?php

namespace Jonnitto\PrettyEmbedHelper\Command;

use Jonnitto\PrettyEmbedHelper\Service\ImageService;
use Jonnitto\PrettyEmbedHelper\Service\MetadataService;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindDescendantNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Eel\Exception as EelException;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Cli\Exception\StopCommandException;
use Neos\Flow\Log\Utility\LogEnvironment;
use Neos\Flow\Persistence\Exception\IllegalObjectTypeException;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Neos\Domain\Service\NodeTypeNameFactory;
use Psr\Log\LoggerInterface;
use function array_reduce;

#[Flow\Scope('singleton')]
class PrettyEmbedCommandController extends CommandController
{
    #[Flow\Inject]
    protected MetadataService $metadataService;

    #[Flow\Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    #[Flow\Inject]
    protected PersistenceManagerInterface $persistenceManager;

    #[Flow\Inject]
    protected LoggerInterface $logger;

    #[Flow\Inject]
    protected ImageService $imageService;

    protected array $success = [];

    protected array $error = [];

    protected array $nodes = [];

    /**
     * Generate metadata for the PrettyEmbed Vimeo/YouTube/Video or Audio player
     *
     * This generates the metadata for all player which has the Jonnitto.PrettyEmbedHelper:Mixin.Metadata mixin
     *
     * @param string $contentRepositoryId ID of the content repository, default is 'default'
     * @param string $workspaceName Workspace name, default is 'live'
     * @param boolean $remove If set, all metadata will be removed
     * @return void
     * @throws EelException
     * @throws StopCommandException
     */
    public function metadataCommand(
        string $contentRepositoryId = 'default',
        string $workspaceName = 'live',
        bool $remove = false
    ): void {
        $contentRepository = $this->contentRepositoryRegistry->get(
            ContentRepositoryId::fromString($contentRepositoryId)
        );
        $workspace = $contentRepository->findWorkspaceByName(WorkspaceName::fromString($workspaceName));

        $this->outputLine();
        if ($workspace === null) {
            $this->outputLine('<error>Workspace "%s" does not exist</error>', [$workspaceName]);
            $this->quit(1);
        }

        $contentGraph = $contentRepository->getContentGraph(WorkspaceName::fromString($workspaceName));
        $sitesNodeAggregate = $contentGraph->findRootNodeAggregateByType(NodeTypeNameFactory::forSites());

        if ($sitesNodeAggregate === null) {
            $this->outputLine('<error>Could not find the sites node aggregate</error>');
            $this->quit(1);
        }

        $this->outputFormatted('Searching for PrettyEmbed nodes which are able to save metadata');

        foreach ($contentRepository->getVariationGraph()->getDimensionSpacePoints() as $dimensionSpacePoint) {
            $subgraph = $contentGraph->getSubgraph($dimensionSpacePoint, VisibilityConstraints::withoutRestrictions());

            $nodes = $subgraph->findDescendantNodes(
                $sitesNodeAggregate->nodeAggregateId,
                FindDescendantNodesFilter::create(nodeTypes: 'Jonnitto.PrettyEmbedHelper:Mixin.Metadata')
            );
            $this->nodes = array_merge($this->nodes, iterator_to_array($nodes));
        }

        $this->processNodes(true);
        $this->imageService->removeAllUnusedImages();

        if (!$remove) {
            $this->error = [];
            $this->success = [];
            $this->processNodes(false);
        }

        if (count($this->error) === 0 && count($this->success) === 0) {
            $this->outputFormatted('<error>There were no node types found</error>');
            $this->quit();
        }

        if (count($this->success)) {
            $this->outputLine();
            $countEntries = [
                'YouTube' => $this->countEntries($this->success, 'Youtube'),
                'Vimeo' => $this->countEntries($this->success, 'Vimeo'),
                'Video' => $this->countEntries($this->success, 'Video'),
                'Audio' => $this->countEntries($this->success, 'Audio'),
            ];

            foreach ($countEntries as $platform => $count) {
                if ($count) {
                    $entriesPlural = $count === 1 ? 'entry' : 'entries';
                    $this->outputFormatted('<success>Saved the metadata from <b>%s %s</b> %s</success>', [
                        $count,
                        $platform,
                        $entriesPlural,
                    ]);
                    $this->logger->debug(
                        sprintf('Saved the metadata from "%s %s" %s', $count, $platform, $entriesPlural),
                        LogEnvironment::fromMethodName(__METHOD__)
                    );
                }
            }
        }

        if (count($this->error)) {
            $this->outputLine();

            if ($remove === true) {
                $countEntries = [
                    'YouTube' => $this->countEntries($this->error, 'Youtube'),
                    'Vimeo' => $this->countEntries($this->error, 'Vimeo'),
                    'Video' => $this->countEntries($this->error, 'Video'),
                    'Audio' => $this->countEntries($this->error, 'Audio'),
                ];

                foreach ($countEntries as $platform => $count) {
                    if ($count) {
                        $entriesPlural = $count === 1 ? 'entry' : 'entries';
                        $this->outputFormatted('<success>Removed the metadata from <b>%s %s</b> %s</success>', [
                            $count,
                            $platform,
                            $entriesPlural,
                        ]);
                        $this->logger->debug(
                            sprintf('Removed the metadata from "%s %s" %s', $count, $platform, $entriesPlural),
                            LogEnvironment::fromMethodName(__METHOD__)
                        );
                    }
                }
            } else {
                $this->outputLine('<error>There where <b>%s errors</b> fetching metadata:</error>', [
                    count($this->error),
                ]);
                $tableRows = [];
                foreach ($this->error as $error) {
                    $this->logger->error(
                        sprintf(
                            'Error fetching metadata for "%s %s" with the id %s and the node type "%s" on the path "%s"',
                            $error['node'],
                            $error['type'],
                            $error['id'],
                            $error['nodeTypeName'],
                            $error['path']
                        ),
                        LogEnvironment::fromMethodName(__METHOD__)
                    );
                    $tableRows[] = [
                        $error['nodeTypeName'],
                        "{$error['node']} {$error['type']}",
                        $error['id'],
                        $error['path'],
                    ];
                }
                $this->output->outputTable($tableRows, ['Name of the node type', 'Type', 'Video ID', 'Node Path']);

                $this->outputFormatted('
<error>Possible errors that data cannot be fetched are:</error>
- The video or playlist is not public listed/private
- The video or playlist with the given id do not exist
                ');
            }
        }
    }

    /**
     * Count entries of the given platform
     *
     * @param array $entries
     * @param string $type The type (YouTube/Vimeo/Video or Audio)
     * @return integer Returns the amount of entries
     */
    protected function countEntries(array $entries, string $type): int
    {
        $count = array_reduce($entries, static function ($carry, $item) use ($type) {
            if ($item['node'] === $type) {
                $carry++;
            }
            return $carry;
        });
        return $count ?? 0;
    }

    /**
     * Process nodes
     *
     * @param boolean $remove
     * @return void
     */
    protected function processNodes(bool $remove): void
    {
        $nodesCount = count($this->nodes);
        if ($nodesCount === 0) {
            return;
        }
        $this->outputLine();
        $this->outputLine($remove ? 'Remove metadata' : 'Add metadata');
        $this->output->progressStart($nodesCount);

        foreach ($this->nodes as $node) {
            try {
                $returnFromNode = $this->metadataService->createDataFromService($node, $remove);
                if ($returnFromNode['node']) {
                    if ($returnFromNode['data']) {
                        $this->success[] = $returnFromNode;
                    } else {
                        $this->error[] = $returnFromNode;
                    }
                }
            } catch (IllegalObjectTypeException $e) {
            }
            $this->output->progressAdvance();
        }
        $this->output->progressFinish();
        $this->outputLine();
        $this->persistenceManager->persistAll();
    }
}
