<?php

namespace Ecotone\Laravel;

use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Modelling\RepositoryBuilder;

/**
 * licence Apache-2.0
 */
final class EloquentRepositoryBuilder implements RepositoryBuilder
{
    private EloquentRepository $eloquentRepository;

    public function __construct()
    {
        $this->eloquentRepository = new EloquentRepository();
    }

    public function canHandle(string $aggregateClassName): bool
    {
        return $this->eloquentRepository->canHandle($aggregateClassName);
    }

    public function compile(MessagingContainerBuilder $builder): Definition|Reference
    {
        return new Definition(EloquentRepository::class);
    }
}
