<?php

namespace Jonnitto\PrettyEmbedHelper;

use Neos\Flow\Core\Bootstrap;
use Neos\ContentRepository\Domain\Model\Node;
use Neos\Flow\Package\Package as BasePackage;
use Jonnitto\PrettyEmbedHelper\Service\MetadataService;

class Package extends BasePackage
{

    /**
     * @param Bootstrap $bootstrap The current bootstrap
     * @return void
     */

    public function boot(Bootstrap $bootstrap)
    {
        $dispatcher = $bootstrap->getSignalSlotDispatcher();
        $dispatcher->connect(Node::class, 'nodeAdded', MetadataService::class, 'createDataFromService');
        $dispatcher->connect(Node::class, 'nodePropertyChanged', MetadataService::class, 'updateDataFromService');
    }
}
