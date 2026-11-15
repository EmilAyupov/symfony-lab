<?php

declare(strict_types=1);

namespace App\AuditBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('audit');

        $treeBuilder->getRootNode()
            ->children()
                ->booleanNode('enabled')
                    ->defaultTrue()
                ->end()
                ->arrayNode('ignored_fields')
                    ->scalarPrototype()->end()
                    ->defaultValue(['password', 'token'])
                ->end()
            ->end();

        return $treeBuilder;
    }
}
