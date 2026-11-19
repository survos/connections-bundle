<?php

declare(strict_types=1);

namespace Survos\ConnectionsBundle\Layout;

final readonly class PlacedGraph
{
    /** @param list<PlacedNode> $nodes @param list<PlacedEdge> $edges */
    public function __construct(public array $nodes, public array $edges, public int $width, public int $height)
    {
    }
}
