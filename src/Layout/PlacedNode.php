<?php

declare(strict_types=1);

namespace Survos\ConnectionsBundle\Layout;

use Survos\ConnectionsContract\Node;

final readonly class PlacedNode
{
    public function __construct(public Node $node, public int $depth, public int $x, public int $y, public int $width, public int $height, public bool $focus)
    {
    }
}
