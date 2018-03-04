<?php

namespace Ang3\Bundle\LoggableHistoryBundle\DependencyInjection;

use Ang3\Bundle\LoggableHistoryBundle\Factory\AdvancedLogFactory;
use Ang3\Bundle\LoggableHistoryBundle\Factory\HistoryFactory;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * @author Joanis ROUANET
 */
class Ang3LoggableHistoryExtension extends Extension implements PrependExtensionInterface
{
    /**
     * {@inheritdoc}.
     */
    public function prepend(ContainerBuilder $container)
    {
        // Récupération de la configuration
        $configs = $container->getExtensionConfig($this->getAlias());
        $config = $this->processConfiguration(new Configuration(), $configs);

        // Récupération des bundles
        $bundles = $container->getParameter('kernel.bundles');

        // Si les extensiosn Doctrine ne sont pas installées
        if (!isset($bundles['StofDoctrineExtensionsBundle'])) {
            throw new \Exception('Unable to configure Ang3/LoggableHistoryBundle because bundle Stof/DoctrineExtensionsBundle is not enabled.');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // Définition d'un chargeur de fichier YAML
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        // Chargement des services
        $loader->load('services.yml');

        // Pour chaque configuration d'historique
        foreach ($config as $historyName => $events) {
            // Définition de la factory spécialisée pour cet historique
            $serviceDefinition = new Definition(HistoryFactory::class, array($historyName, $events, $container->getDefinition(AdvancedLogFactory::class)));

            // Enregistrement de la définition dans le container
            $container->setDefinition('ang3_loggable_history.factory.'.$historyName, $serviceDefinition);
        }
    }
}
