<?php

declare(strict_types=1);

namespace Survos\ConnectionsBundle\Tests;

use PHPUnit\Framework\TestCase;
use Survos\ConnectionsBundle\Link\ConnectionsLinker;
use Survos\ConnectionsBundle\SurvosConnectionsBundle;
use Survos\ConnectionsContract\ConnectionGraph;
use Survos\ConnectionsContract\Edge;
use Survos\ConnectionsContract\Group;
use Survos\ConnectionsContract\Node;
use Survos\ConnectionsContract\Scope;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;

final class BundleTest extends TestCase
{
    private function graph(): ConnectionGraph
    {
        return new ConnectionGraph(new Scope('d'), 'a', [new Node('a', 'Person', 'Ann'), new Node('b', 'Person', 'Ben')], [new Edge('e', 'Family', 'a', 'b', 'spouse of', symmetric: true)]);
    }

    private function render(bool $withLinker): string
    {
        $kernel = new ConnectionsTestKernel('test', true, $withLinker);
        $kernel->boot();
        try {
            return $kernel->getContainer()->get('test.twig')->createTemplate('{{ connections(graph) }}')->render(['graph' => $this->graph()]);
        } finally {
            $kernel->shutdown();
        }
    }

    public function testTheBundleWiresTheExtensionTemplateAndAnUnlinkedDefault(): void
    {
        $html = $this->render(false);

        self::assertStringContainsString('<svg', $html);
        self::assertStringContainsString('Connections for Ann', $html);
        self::assertStringNotContainsString('<a href', $html);
    }

    public function testAnApplicationAliasReplacesTheDefaultLinker(): void
    {
        self::assertStringContainsString('href="/people/b"', $this->render(true));
    }
}

final class AppLinker implements ConnectionsLinker
{
    public function nodeUrl(Node $node, ConnectionGraph $graph): ?string
    {
        return '/people/'.$node->id;
    }

    public function moreUrl(ConnectionGraph $graph, Group $group): ?string
    {
        return null;
    }

    public function sourceUrl(Edge $edge, ConnectionGraph $graph): ?string
    {
        return null;
    }
}

final class ConnectionsTestKernel extends Kernel
{
    use MicroKernelTrait;

    public function __construct(string $environment, bool $debug, private readonly bool $withLinker)
    {
        parent::__construct($environment, $debug);
    }

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new TwigBundle();
        yield new SurvosConnectionsBundle();
    }

    public function getProjectDir(): string
    {
        return sys_get_temp_dir().'/connections-bundle-test/project';
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir().'/connections-bundle-test/cache-'.($this->withLinker ? 'linked' : 'plain');
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir().'/connections-bundle-test/log';
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', ['secret' => 'test', 'test' => true, 'http_method_override' => false]);
        $container->extension('twig', ['strict_variables' => true]);
        $container->services()->alias('test.twig', 'twig')->public();
        if ($this->withLinker) {
            $container->services()->set(AppLinker::class);
            $container->services()->alias(ConnectionsLinker::class, AppLinker::class);
        }
    }
}
