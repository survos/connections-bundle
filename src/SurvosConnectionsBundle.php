<?php

declare(strict_types=1);

namespace Survos\ConnectionsBundle;

use Survos\ConnectionsBundle\Layout\GraphLayout;
use Survos\ConnectionsBundle\Link\ConnectionsLinker;
use Survos\ConnectionsBundle\Link\NullConnectionsLinker;
use Survos\ConnectionsBundle\Twig\ConnectionsExtension;
use Survos\Kit\AbstractSurvosBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

// Symfony\Component\HttpKernel\Bundle\Bundle <-- Flex auto-registration marker (see Survos\Kit\AbstractSurvosBundle)
/**
 * Draws a Survos\ConnectionsContract\ConnectionGraph. The application decides where links go by aliasing
 * ConnectionsLinker to its own service; without one, nodes are drawn without links.
 */
final class SurvosConnectionsBundle extends AbstractSurvosBundle
{
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        parent::loadExtension($config, $container, $builder);
        $services = $container->services()->defaults()->autowire()->autoconfigure();
        $services->set(GraphLayout::class);
        $services->set(NullConnectionsLinker::class);
        $services->alias(ConnectionsLinker::class, NullConnectionsLinker::class);
        $services->set(ConnectionsExtension::class);
    }
}
