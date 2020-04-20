<?php

namespace Jonnitto\PrettyEmbedHelper\Command;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\Exception as EelException;
use Neos\Neos\Exception as NeosException;
use Neos\Neos\Domain\Service\SiteService;
use Neos\ContentRepository\Exception\NodeException;
use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;
use Neos\ContentRepository\Domain\Service\ContentDimensionCombinator;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\ContentRepository\Domain\Repository\WorkspaceRepository;
use Jonnitto\PrettyEmbedHelper\Service\MetadataService;

/**
 * @Flow\Scope("singleton")
 */
class PrettyEmbedCommandController extends CommandController
{
    /**
     * @Flow\Inject
     * @var MetadataService
     */
    protected $metadataService;

    /**
     * @Flow\Inject
     * @var ContextFactoryInterface
     */
    protected $contextFactory;

    /**
     * @Flow\Inject
     * @var ContentDimensionCombinator
     */
    protected $dimensionCombinator;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @Flow\Inject
     * @var WorkspaceRepository
     */
    protected $workspaceRepository;

    /**
     * Generate metadata for the PrettyEmbed Vimeo and Youtube player
     *
     * This generates the metadata for all video player which has the mixin 
     * - Jonnitto.PrettyEmbedVideoPlatforms:Mixin.VideoID
     * - Jonnitto.PrettyEmbedVimeo:Mixin.VideoID
     * - Jonnitto.PrettyEmbedYoutube:Mixin.VideoID
     *
     * @param string $workspace Workspace name, default is 'live'
     * @param boolean $remove Is set, all metadata will be removed
     * @return void
     * @throws EelException
     * @throws NodeException
     * @throws NeosException
     */

    public function metadataCommand(string $workspace = 'live', bool $remove = false): void
    {
        $this->outputLine('');
        /** @noinspection PhpUndefinedMethodInspection */
        if ($this->workspaceRepository->countByName($workspace) === 0) {
            $this->outputLine('<error>Workspace "%s" does not exist</error>', [$workspace]);
            exit(1);
        }

        $contextProperties = [
            'workspaceName' => $workspace,
            'dimensions' => array(),
            'invisibleContentShown' => true,
            'inaccessibleContentShown' => true
        ];
        $baseContext = $this->contextFactory->create($contextProperties);
        $baseContextSitesNode = $baseContext->getNode(SiteService::SITES_ROOT_PATH);
        if (!$baseContextSitesNode) {
            $this->outputFormatted(sprintf('<error>Could not find "%s" root node</error>', SiteService::SITES_ROOT_PATH));
            $this->quit(1);
        }
        $baseContextSiteNodes = $baseContextSitesNode->getChildNodes();
        if ($baseContextSiteNodes === []) {
            $this->outputFormatted(sprintf('<error>Could not find any site nodes in "%s" root node</error>', SiteService::SITES_ROOT_PATH));
            $this->quit(1);
        }
        $this->outputFormatted('Searching for PrettyEmbed nodes which are able to save metadata');
        $successArray = array();
        $errorArray = array();
        foreach ($this->dimensionCombinator->getAllAllowedCombinations() as $dimensionCombination) {
            $flowQuery = new FlowQuery($baseContextSiteNodes);
            $siteNodes = $flowQuery->context(['dimensions' => $dimensionCombination, 'targetDimensions' => []])->get();
            if (count($siteNodes) > 0) {
                foreach ($siteNodes as $siteNode) {
                    $returnFromSiteNode = $this->metadataService->createDataFromService($siteNode, $remove);
                    if ($returnFromSiteNode['node']) {
                        if ($returnFromSiteNode['data']) {
                            $successArray[] = $returnFromSiteNode;
                        } else {
                            $errorArray[] = $returnFromSiteNode;
                        }
                    }
                    $nodes = $flowQuery->find('[instanceof Jonnitto.PrettyEmbedVideoPlatforms:Mixin.VideoID],[instanceof Jonnitto.PrettyEmbedVimeo:Mixin.VideoID],[instanceof Jonnitto.PrettyEmbedYoutube:Mixin.VideoID]')->get();
                    foreach ($nodes as $node) {
                        $returnFromNode = $this->metadataService->createDataFromService($node, $remove);
                        if ($returnFromNode['node']) {
                            if ($returnFromNode['data']) {
                                $successArray[] = $returnFromNode;
                            } else {
                                $errorArray[] = $returnFromNode;
                            }
                        }
                    }
                }
            }
        }
        $this->persistenceManager->persistAll();

        if (count($errorArray) === 0 && count($successArray) === 0) {
            $this->outputFormatted('<error>There were no node types found</error>');
            $this->quit(0);
        }

        if (count($successArray)) {
            $output = '<success>Saved the metadata from <b>%s %s</b> entries</success>';
            $this->outputLine('');
            $this->outputEntris($successArray, 'Youtube', $output);
            $this->outputEntris($successArray, 'Vimeo', $output);
        }

        if (count($errorArray)) {
            $this->outputLine('');

            if ($remove === true) {
                $output = '<success>Removed the metadata from <b>%s %s</b> entries</success>';
                $this->outputEntris($errorArray, 'Youtube', $output);
                $this->outputEntris($errorArray, 'Vimeo', $output);
            } else {
                $this->outputLine('<error>There where <b>%s errors</b> fetching metadata:</error>', [count($errorArray)]);
                $tableRows = [];
                foreach ($errorArray as $error) {
                    $tableRows[] = [
                        $error['nodeTypeName'],
                        "{$error['node']} {$error['type']}",
                        $error['id'],
                        $error['path']
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
     * Count how many entris
     *
     * @param array $array
     * @param string $type The type of the video (Youtube or Vimeo)
     * @param string $output The output in the console
     * @return integer Returns the amount of entries
     */
    protected function outputEntris(array $array, string $type, string $output): int
    {
        $count = array_reduce($array, function ($carry, $item) use ($type) {
            if ($item['node'] === $type) {
                $carry++;
            }
            return $carry;
        });
        if (isset($count)) {
            $this->outputFormatted($output, [$count, $type]);
        }
        return $count ?? 0;
    }
}
