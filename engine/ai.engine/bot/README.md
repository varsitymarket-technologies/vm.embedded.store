# Website structure bot

The bot reads one HTML page at a time and prints a JSON report. Every match has byte offsets (`start` is inclusive and `end` is exclusive) and the matched HTML.

Sequence 1 locates the `<head>` region. Sequence 2 locates navigation: native `<nav>` elements plus elements whose `id` or `class` includes `nav`, `navbar`, or `navigation`.

Sequences 3–5 are intentionally configurable until their rules are defined. Supply an element name, an ID, or a class selector:

```sh
php bot/structure_bot.php --url=https://example.com --sequence3=main --sequence4=footer --sequence5=.cookie-banner
php bot/structure_bot.php --file=website/index.html
```

Use exactly one of `--url` and `--file`. URL requests require PHP's cURL extension.
