<?php

declare(strict_types=1);

namespace Survos\ConnectionsBundle\Layout;

use Survos\ConnectionsContract\Edge;

final readonly class PlacedEdge
{
    /** @param string $path an SVG path "d" attribute */
    public function __construct(public Edge $edge, public string $path)
    {
    }
}
