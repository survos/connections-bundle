<?php

declare(strict_types=1);

namespace Survos\ConnectionsBundle\Link;

use Survos\ConnectionsContract\ConnectionGraph;
use Survos\ConnectionsContract\Edge;
use Survos\ConnectionsContract\Group;
use Survos\ConnectionsContract\Node;

final class NullConnectionsLinker implements ConnectionsLinker
{
    public function nodeUrl(Node $node, ConnectionGraph $graph): ?string
    {
        return null;
    }

    public function moreUrl(ConnectionGraph $graph, Group $group): ?string
    {
        return null;
    }

    public function sourceUrl(Edge $edge, ConnectionGraph $graph): ?string
    {
        return $edge->sourceUrl;
    }
}
