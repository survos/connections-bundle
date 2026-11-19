<?php

declare(strict_types=1);

namespace Survos\ConnectionsBundle\Twig;

use Survos\ConnectionsBundle\Layout\GraphLayout;
use Survos\ConnectionsBundle\Link\ConnectionsLinker;
use Survos\ConnectionsContract\ConnectionGraph;
use Survos\ConnectionsContract\Edge;
use Survos\ConnectionsContract\Group;
use Survos\ConnectionsContract\Node;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class ConnectionsExtension extends AbstractExtension
{
    public function __construct(private readonly GraphLayout $layout, private readonly ConnectionsLinker $linker)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('connections', $this->render(...), ['needs_environment' => true, 'is_safe' => ['html']]),
            new TwigFunction('connections_layout', $this->layout->layout(...)),
            new TwigFunction('connections_node_url', fn (Node $n, ConnectionGraph $g): ?string => $this->linker->nodeUrl($n, $g)),
            new TwigFunction('connections_more_url', fn (ConnectionGraph $g, Group $group): ?string => $this->linker->moreUrl($g, $group)),
            new TwigFunction('connections_source_url', fn (Edge $e, ConnectionGraph $g): ?string => $this->linker->sourceUrl($e, $g)),
            new TwigFunction('connections_dom_id', static fn (ConnectionGraph $g): string => 'cg-'.substr(sha1($g->scope->dataset.'|'.$g->focus), 0, 8)),
        ];
    }

    /** {{ connections(graph) }} */
    public function render(Environment $twig, ConnectionGraph $graph): string
    {
        return $twig->render('@SurvosConnections/graph.html.twig', ['graph' => $graph, 'placed' => $this->layout->layout($graph)]);
    }
}
