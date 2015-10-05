<?php
namespace Striide\GeoBundle\DependencyInjection;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */

class Configuration implements ConfigurationInterface
{
  /**
   * {@inheritDoc}
   */
  public function getConfigTreeBuilder() 
  {
    $treeBuilder = new TreeBuilder();
    $rootNode = $treeBuilder->root('striide_geo');
    // Here you should define the parameters that are allowed to
    // configure your bundle. See the documentation linked above for
    // more information on that topic.
    $rootNode->children()
        ->scalarNode('google_api_server_key')
        ->isRequired()
        ->cannotBeEmpty()
        ->defaultValue('your_server_side_google_api_key_here');

    return $treeBuilder;
  }
}
