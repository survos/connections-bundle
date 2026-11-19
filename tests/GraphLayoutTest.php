<?php

declare(strict_types=1);

namespace Survos\ConnectionsBundle\Tests;

use PHPUnit\Framework\TestCase;
use Survos\ConnectionsBundle\Layout\GraphLayout;
use Survos\ConnectionsContract\ConnectionGraph;
use Survos\ConnectionsContract\Edge;
use Survos\ConnectionsContract\Node;
use Survos\ConnectionsContract\Scope;

final class GraphLayoutTest extends TestCase
{
    private function graph(): ConnectionGraph
    {
        $nodes = [new Node('f', 'Person', 'Focus'), new Node('b', 'Person', 'Bea'), new Node('a', 'Person', 'Al'), new Node('far', 'Person', 'Far'), new Node('lone', 'Person', 'Lone')];

        return new ConnectionGraph(new Scope('d'), 'f', $nodes, [
            new Edge('e1', 'Family', 'f', 'b', 'child of'),
            new Edge('e2', 'Family', 'f', 'a', 'child of'),
            new Edge('e3', 'Family', 'b', 'far', 'spouse of', symmetric: true),
        ]);
    }

    public function testColumnsFollowDistanceFromTheFocus(): void
    {
        $placed = (new GraphLayout())->layout($this->graph());
        $depth = [];
        foreach ($placed->nodes as $n) {
            $depth[$n->node->id] = $n->depth;
        }

        ksort($depth);
        self::assertSame(['a' => 1, 'b' => 1, 'f' => 0, 'far' => 2, 'lone' => 2], $depth);
        self::assertSame(3 * GraphLayout::NODE_WIDTH + 2 * GraphLayout::COLUMN_GAP, $placed->width);
    }

    public function testOrderIsStableAndAlphabeticWithinAColumn(): void
    {
        $placed = (new GraphLayout())->layout($this->graph());
        $column = array_values(array_filter($placed->nodes, static fn ($n) => 1 === $n->depth));

        self::assertSame(['Al', 'Bea'], array_map(static fn ($n) => $n->node->caption, $column));
        self::assertLessThan($column[1]->y, $column[0]->y);
        self::assertEquals($placed, (new GraphLayout())->layout($this->graph()));
    }

    public function testEveryNodeAndEdgeIsPlaced(): void
    {
        $placed = (new GraphLayout())->layout($this->graph());

        self::assertCount(5, $placed->nodes);
        self::assertCount(3, $placed->edges);
        foreach ($placed->edges as $edge) {
            self::assertStringStartsWith('M ', $edge->path);
        }
        self::assertTrue($placed->nodes[0]->focus);
    }

    public function testASingleNodeGraphHasNoEdges(): void
    {
        $placed = (new GraphLayout())->layout(new ConnectionGraph(new Scope('d'), 'x', [new Node('x', 'Person', 'X')]));

        self::assertSame(GraphLayout::NODE_WIDTH, $placed->width);
        self::assertSame([], $placed->edges);
    }
}
