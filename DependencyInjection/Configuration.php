<?php

namespace Ang3\Bundle\LoggableHistoryBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Configuration du bundle.
 *
 * @author Joanis ROUANET
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}.
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();

        $rootNode = $treeBuilder->root('ang3_loggable_history');

        $rootNode
        	->useAttributeAsKey('name')
            ->prototype('array')
	            ->prototype('array')
	                ->children()
	                    ->arrayNode('log_classes')
                            ->beforeNormalization()
                                ->ifString()
                                ->then(function ($v) {
                                    return [$v];
                                })
                            ->end()
                            ->requiresAtLeastOneElement()
                            ->prototype('scalar')
                            ->end()
                        ->end()
                        ->arrayNode('subjects')
                            ->beforeNormalization()
                                ->ifString()
                                ->then(function ($v) {
                                    return [$v];
                                })
                            ->end()
                            ->requiresAtLeastOneElement()
                            ->prototype('scalar')
                            ->end()
                        ->end()
	                    ->arrayNode('fields')
                            ->beforeNormalization()
                                ->ifString()
                                ->then(function ($v) {
                                    return [$v];
                                })
                            ->end()
            				->prototype('scalar')
            				->end()
	                    ->end()
	                    ->arrayNode('actions')
                            ->beforeNormalization()
                                ->ifString()
                                ->then(function ($v) {
                                    return [$v];
                                })
                            ->end()
	                    	->requiresAtLeastOneElement()
            				->prototype('scalar')
            				->end()
	                    ->end()
                        ->scalarNode('validation')
                            ->cannotBeEmpty()
                            ->defaultNull()
                        ->end()
	                ->end()
	            ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
