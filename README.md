# survos/connections-bundle

Draws a `Survos\ConnectionsContract\ConnectionGraph` as:

- an SVG (focus first, direct connections in the next column, anything further after that), with a title and a
  description for screen readers, readable without JavaScript;
- the same connections as a list under a `details` element, each with its source quote and a source link;
- a "More connections" link for each relation group that has more than was loaded.

```twig
{{ connections(graph) }}
```

## Links

The graph holds no addresses. The application says where links go by implementing
`Survos\ConnectionsBundle\Link\ConnectionsLinker` (`nodeUrl`, `moreUrl`, `sourceUrl`) and aliasing the interface to
its service. Without one, nodes are drawn without links, and a source link is shown only when the edge carries a
`sourceUrl`.

```yaml
services:
    Survos\ConnectionsBundle\Link\ConnectionsLinker: '@App\Service\StoneConnectionsLinker'
```

## Templates and styling

`@SurvosConnections/graph.html.twig` includes a small `<style>` block with `connections-*` classes and a dark-mode
rule. Override the template in `templates/bundles/SurvosConnections/` to restyle it.

Edges read from the node on the left to the node they point at. An edge with status `reviewed` is drawn heavier, and
a `same-as` edge is dashed.

## Tests

```sh
vendor/bin/phpunit -c bu/connections-bundle/phpunit.xml.dist
```
