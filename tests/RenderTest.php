<?php

declare(strict_types=1);

namespace Survos\ConnectionsBundle\Tests;

use PHPUnit\Framework\TestCase;
use Survos\ConnectionsBundle\Layout\GraphLayout;
use Survos\ConnectionsBundle\Link\ConnectionsLinker;
use Survos\ConnectionsBundle\Twig\ConnectionsExtension;
use Survos\ConnectionsContract\ConnectionGraph;
use Survos\ConnectionsContract\Edge;
use Survos\ConnectionsContract\EdgeKind;
use Survos\ConnectionsContract\Evidence;
use Survos\ConnectionsContract\Group;
use Survos\ConnectionsContract\Node;
use Survos\ConnectionsContract\Scope;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

final class RenderTest extends TestCase
{
    private const ARTICLE = '37573aa3-8292-531e-844f-a7f7f8f92db1';

    private function twig(?ConnectionsLinker $linker = null): Environment
    {
        $loader = new FilesystemLoader();
        $loader->addPath(dirname(__DIR__).'/templates', 'SurvosConnections');
        $twig = new Environment($loader, ['strict_variables' => true, 'autoescape' => 'html']);
        $twig->addExtension(new ConnectionsExtension(new GraphLayout(), $linker ?? new class implements ConnectionsLinker {
            public function nodeUrl(Node $node, ConnectionGraph $graph): ?string
            {
                return '/person/'.rawurlencode($node->id);
            }

            public function moreUrl(ConnectionGraph $graph, Group $group): ?string
            {
                return '/more/'.$group->key.'?offset='.$group->nextOffset();
            }

            public function sourceUrl(Edge $edge, ConnectionGraph $graph): ?string
            {
                return $edge->sourceUrl;
            }
        }));

        return $twig;
    }

    private function graph(string $quote = 'son of the late Billy and Helen Smoot.'): ConnectionGraph
    {
        return new ConnectionGraph(new Scope('news/rappnews-obits'), self::ARTICLE.':wayne',
            [new Node(self::ARTICLE.':wayne', 'Person', 'Wayne Mitchell Smoot'), new Node(self::ARTICLE.':billy', 'Person', 'Billy Smoot')],
            [new Edge('r:1', 'Family', self::ARTICLE.':wayne', self::ARTICLE.':billy', 'child of', 'parent of',
                evidence: [new Evidence('urn:ftm:'.self::ARTICLE, $quote, 381, 419, 'ai-obituary-read')], sourceUrl: 'https://www.rappnews.com/archives/article_x')],
            [new Group('Family', 60, 0, 24)], false);
    }

    public function testItDrawsTheGraphTheListAndTheQuote(): void
    {
        $html = $this->twig()->createTemplate('{{ connections(graph) }}')->render(['graph' => $this->graph()]);

        self::assertStringContainsString('<svg', $html);
        self::assertStringContainsString('role="img"', $html);
        self::assertStringContainsString('Connections for Wayne Mitchell Smoot', $html);
        self::assertStringContainsString('Billy Smoot', $html);
        self::assertStringContainsString('Read connections as a list (1)', $html);
        self::assertStringContainsString('son of the late Billy and Helen Smoot.', $html);
        self::assertStringContainsString('(extracted, not reviewed)', $html);
        self::assertStringContainsString('href="https://www.rappnews.com/archives/article_x"', $html);
    }

    public function testNodeLinksComeFromTheApplicationsLinker(): void
    {
        $html = $this->twig()->createTemplate('{{ connections(graph) }}')->render(['graph' => $this->graph()]);

        self::assertStringContainsString('href="/person/'.rawurlencode(self::ARTICLE.':billy').'"', $html);
        self::assertStringContainsString('aria-label="Open Person: Billy Smoot"', $html);
    }

    public function testWithoutALinkerNodesAreDrawnWithoutLinks(): void
    {
        $none = new class implements ConnectionsLinker {
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
                return null;
            }
        };
        $html = $this->twig($none)->createTemplate('{{ connections(graph) }}')->render(['graph' => $this->graph()]);

        self::assertStringNotContainsString('<a href', $html);
        self::assertStringContainsString('Showing 24 of 60 Family connections.', $html);
        self::assertStringNotContainsString('More connections', $html);
    }

    public function testAnIncompleteGroupOffersMoreConnections(): void
    {
        $html = $this->twig()->createTemplate('{{ connections(graph) }}')->render(['graph' => $this->graph()]);

        self::assertStringContainsString('<a href="/more/Family?offset=24">More connections</a>', $html);
    }

    public function testQuotesAndNamesAreEscaped(): void
    {
        $html = $this->twig()->createTemplate('{{ connections(graph) }}')->render(['graph' => $this->graph('<script>alert(1)</script>')]);

        self::assertStringNotContainsString('<script>alert(1)</script>', $html);
        self::assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
    }

    public function testASameAsEdgeIsDrawnDashedAndLabelledAsReviewed(): void
    {
        $graph = new ConnectionGraph(new Scope('d'), 'a', [new Node('a', 'Person', 'A'), new Node('b', 'Person', 'B')],
            [new Edge('d1', 'Decision', 'a', 'b', 'same person as', symmetric: true, kind: EdgeKind::SameAs, status: 'reviewed')]);
        $html = $this->twig()->createTemplate('{{ connections(graph) }}')->render(['graph' => $graph]);

        self::assertStringContainsString('connections-edge--same-as', $html);
        self::assertStringContainsString('(reviewed: same person)', $html);
    }

    public function testNodesShowTheirKindYearsAndRoles(): void
    {
        $graph = new ConnectionGraph(new Scope('d'), 'p', [
            new Node('p', 'Person', 'John Bray Hutt', '1920-03-01', '1990', roles: ['Named on stone', 'Spouse']),
            new Node('s', 'Document', 'Hutt Gravestone', kind: 'Stone record'),
        ], [new Edge('e', 'Reference', 'p', 's', 'named on')]);
        $html = $this->twig()->createTemplate('{{ connections(graph) }}')->render(['graph' => $graph]);

        self::assertStringContainsString('1920–1990 · Named on stone, Spouse', $html);
        self::assertStringContainsString('>STONE RECORD<', $html);
        self::assertStringContainsString('>PERSON<', $html);
        self::assertStringContainsString('John Bray Hutt (1920–1990)', $html, 'The list view shows the years too.');
        self::assertStringContainsString('aria-label="Open Person: John Bray Hutt, 1920–1990 · Named on stone, Spouse"', $html);
    }
}
