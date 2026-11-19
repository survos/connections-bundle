<?php

declare(strict_types=1);

namespace Survos\ConnectionsBundle\Link;

use Survos\ConnectionsContract\ConnectionGraph;
use Survos\ConnectionsContract\Edge;
use Survos\ConnectionsContract\Group;
use Survos\ConnectionsContract\Node;

/** Where the application sends the reader. The contract carries no addresses of its own, apart from a source URL. */
interface ConnectionsLinker
{
    /** The page for a node, or null to draw it without a link. */
    public function nodeUrl(Node $node, ConnectionGraph $graph): ?string;

    /** The page that shows the next connections of a group, or null when the application has none. */
    public function moreUrl(ConnectionGraph $graph, Group $group): ?string;

    /** The source article or record behind an edge; usually $edge->sourceUrl. */
    public function sourceUrl(Edge $edge, ConnectionGraph $graph): ?string;
}
