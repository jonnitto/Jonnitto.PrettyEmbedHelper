<?php

namespace Jonnitto\PrettyEmbedHelper;

use Jonnitto\PrettyEmbedHelper\Service\ImageService;
use Jonnitto\PrettyEmbedHelper\Service\MetadataService;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\SharedModel\Workspace\Workspace;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Package\Package as BasePackage;
use Neos\Flow\Persistence\Doctrine\PersistenceManager;

class Package extends BasePackage
{
    /**
     * @param Bootstrap $bootstrap The current bootstrap
     * @return void
     */
    public function boot(Bootstrap $bootstrap): void
    {
        $dispatcher = $bootstrap->getSignalSlotDispatcher();
        $dispatcher->connect(Node::class, 'nodeAdded', MetadataService::class, 'onNodeAdded');
        $dispatcher->connect(Node::class, 'nodePropertyChanged', MetadataService::class, 'updateDataFromService');
        $dispatcher->connect(
            Workspace::class,
            'afterNodePublishing',
            ImageService::class,
            'removeDataAfterNodePublishing'
        );
        $dispatcher->connect(
            PersistenceManager::class,
            'allObjectsPersisted',
            ImageService::class,
            'deletePendingData',
            false
        );
    }
}
