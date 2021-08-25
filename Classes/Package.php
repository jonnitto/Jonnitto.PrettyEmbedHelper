<?php

namespace Jonnitto\PrettyEmbedHelper;

use Neos\Flow\Core\Bootstrap;
use Neos\ContentRepository\Domain\Model\Node;
use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\Flow\Persistence\Doctrine\PersistenceManager;
use Neos\Flow\Package\Package as BasePackage;
use Jonnitto\PrettyEmbedHelper\Service\MetadataService;
use Jonnitto\PrettyEmbedHelper\Service\ImageService;

class Package extends BasePackage
{

    /**
     * @param Bootstrap $bootstrap The current bootstrap
     * @return void
     */
    public function boot(Bootstrap $bootstrap): void
    {
        $dispatcher = $bootstrap->getSignalSlotDispatcher();
        $dispatcher->connect(
            Node::class,
            'nodeAdded',
            MetadataService::class,
            'createDataFromService'
        );
        $dispatcher->connect(
            Node::class,
            'nodePropertyChanged',
            MetadataService::class,
            'updateDataFromService'
        );
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
