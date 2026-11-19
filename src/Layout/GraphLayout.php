<?php

declare(strict_types=1);

namespace Survos\ConnectionsBundle\Layout;

use Survos\ConnectionsContract\ConnectionGraph;
use Survos\ConnectionsContract\Edge;

/**
 * Columns by distance from the focus: the focus in the first, its direct connections in the second, anything further
 * in the third. Within a column nodes are ordered by the relation to the previous column, then by caption, so the
 * same graph always draws the same way.
 */
final class GraphLayout
{
    public const int NODE_WIDTH = 270;
    public const int NODE_HEIGHT = 78;
    public const int COLUMN_GAP = 90;
    public const int ROW_GAP = 16;
    private const int MAX_DEPTH = 2;

    public function layout(ConnectionGraph $graph): PlacedGraph
    {
        $depth = [$graph->focus => 0];
        $queue = [$graph->focus];
        while ($queue) {
            $id = array_shift($queue);
            foreach ($graph->edgesOf($id) as $edge) {
                $other = $edge->from === $id ? $edge->to : $edge->from;
                if (!isset($depth[$other])) {
                    $depth[$other] = min($depth[$id] + 1, self::MAX_DEPTH);
                    $queue[] = $other;
                }
            }
        }
        // A node with no path to the focus is still drawn, in the last column.
        foreach ($graph->nodes as $node) {
            $depth[$node->id] ??= self::MAX_DEPTH;
        }

        $firstLabel = [];
        foreach ($graph->edges as $edge) {
            foreach ([$edge->from, $edge->to] as $end) {
                $firstLabel[$end] ??= $edge->label;
            }
        }
        $columns = [];
        foreach ($graph->nodes as $node) {
            $columns[$depth[$node->id]][] = $node;
        }
        ksort($columns);
        $rows = max(array_map('count', $columns));
        $height = $rows * (self::NODE_HEIGHT + self::ROW_GAP) - self::ROW_GAP;

        $placed = [];
        $byId = [];
        foreach ($columns as $column => $nodes) {
            usort($nodes, static fn ($a, $b): int => [$firstLabel[$a->id] ?? '', $a->caption, $a->id] <=> [$firstLabel[$b->id] ?? '', $b->caption, $b->id]);
            $top = intdiv($height - (count($nodes) * (self::NODE_HEIGHT + self::ROW_GAP) - self::ROW_GAP), 2);
            foreach ($nodes as $row => $node) {
                $item = new PlacedNode($node, $column, $column * (self::NODE_WIDTH + self::COLUMN_GAP), $top + $row * (self::NODE_HEIGHT + self::ROW_GAP), self::NODE_WIDTH, self::NODE_HEIGHT, $node->id === $graph->focus);
                $placed[] = $item;
                $byId[$node->id] = $item;
            }
        }
        $edges = array_map(fn (Edge $e): PlacedEdge => new PlacedEdge($e, self::path($byId[$e->from], $byId[$e->to])), $graph->edges);

        return new PlacedGraph($placed, $edges, (max(array_keys($columns)) + 1) * self::NODE_WIDTH + max(array_keys($columns)) * self::COLUMN_GAP, $height);
    }

    private static function path(PlacedNode $from, PlacedNode $to): string
    {
        $y1 = $from->y + intdiv($from->height, 2);
        $y2 = $to->y + intdiv($to->height, 2);
        if ($from->x === $to->x) {
            $x = $from->x;

            return sprintf('M %d %d C %d %d, %d %d, %d %d', $x, $y1, $x - 60, $y1, $x - 60, $y2, $x, $y2);
        }
        [$x1, $x2] = $from->x < $to->x ? [$from->x + $from->width, $to->x] : [$from->x, $to->x + $to->width];
        $bend = intdiv($x2 - $x1, 2);

        return sprintf('M %d %d C %d %d, %d %d, %d %d', $x1, $y1, $x1 + $bend, $y1, $x2 - $bend, $y2, $x2, $y2);
    }
}
