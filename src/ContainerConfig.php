<?php

namespace Heystack\Maxmind;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @author Cam Spiers <cameron@heyday.co.nz>
 * @package Heystack\Maxmind
 */
class ContainerConfig implements ConfigurationInterface
{
    /**
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('maxmind_locale_detector');

        $rootNode
            ->children()
                ->scalarNode('key')->isRequired()->end()
                ->integerNode('timeout')->end()
                ->arrayNode('excluded_user_agents')
                    ->prototype('scalar')->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
