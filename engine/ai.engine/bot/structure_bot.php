#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Website structure bot
 *
 * Finds structural regions in an HTML document and reports their byte offsets.
 * Sequences 1 and 2 are fixed (head and navbar); sequences 3 to 5 are reserved
 * for selectors supplied on the command line.
 *
 * Examples:
 *   php bot/structure_bot.php --url=https://example.com
 *   php bot/structure_bot.php --file=website/index.html --sequence3=main
 *   php bot/structure_bot.php --file=website/index.html --sequence3=footer
 */

final class StructureBot
{
    /** @return array<string, mixed> */
    public function inspect(string $html, string $source, array $customSelectors): array
    {
        $sequences = [
            ['number' => 1, 'name' => 'head', 'selector' => 'head'],
            ['number' => 2, 'name' => 'navbar', 'selector' => 'navbar'],
            ['number' => 3, 'name' => 'custom_3', 'selector' => $customSelectors[3] ?? null],
            ['number' => 4, 'name' => 'custom_4', 'selector' => $customSelectors[4] ?? null],
            ['number' => 5, 'name' => 'custom_5', 'selector' => $customSelectors[5] ?? null],
        ];

        $results = [];
        foreach ($sequences as $sequence) {
            $selector = $sequence['selector'];
            $results[] = [
                'sequence' => $sequence['number'],
                'name' => $sequence['name'],
                'selector' => $selector,
                'status' => $selector === null ? 'awaiting_selector' : 'complete',
                'matches' => $selector === null ? [] : $this->findRegions($html, $selector),
            ];
        }

        return [
            'source' => $source,
            'document_bytes' => strlen($html),
            'sequences' => $results,
        ];
    }

    /**
     * A selector can be an element name (head, main, footer), `navbar`, or an
     * id/class value prefixed with # or . (for example #site-nav or .navbar).
     *
     * @return list<array{tag:string,start:int,end:int,html:string}>
     */
    private function findRegions(string $html, string $selector): array
    {
        $openingTags = [];
        preg_match_all('/<([a-zA-Z][a-zA-Z0-9:-]*)(?:\\s[^<>]*)?>/i', $html, $openingTags, PREG_OFFSET_CAPTURE);

        $regions = [];
        foreach ($openingTags[0] as $index => $fullMatch) {
            $openingTag = $fullMatch[0];
            $start = $fullMatch[1];
            $tag = strtolower($openingTags[1][$index][0]);

            if (!$this->matchesSelector($openingTag, $tag, $selector)) {
                continue;
            }

            $end = $this->findClosingOffset($html, $tag, $start + strlen($openingTag));
            $regions[] = [
                'tag' => $tag,
                'start' => $start,
                'end' => $end,
                'html' => substr($html, $start, $end - $start),
            ];
        }

        return $regions;
    }

    private function matchesSelector(string $openingTag, string $tag, string $selector): bool
    {
        $selector = trim($selector);
        if ($selector === 'navbar') {
            if ($tag === 'nav') {
                return true;
            }
            return preg_match('/\\b(?:id|class)\\s*=\\s*(["\'])[^"\']*\\b(?:nav|navbar|navigation)\\b[^"\']*\\1/i', $openingTag) === 1;
        }

        if ($selector[0] === '#' || $selector[0] === '.') {
            $attribute = $selector[0] === '#' ? 'id' : 'class';
            $value = preg_quote(substr($selector, 1), '/');
            return preg_match('/\\b' . $attribute . '\\s*=\\s*(["\'])[^"\']*(?<![a-zA-Z0-9_-])' . $value . '(?![a-zA-Z0-9_-])[^"\']*\\1/i', $openingTag) === 1;
        }

        return strtolower($selector) === $tag;
    }

    private function findClosingOffset(string $html, string $tag, int $contentStart): int
    {
        $tokens = [];
        preg_match_all('/<\\/?' . preg_quote($tag, '/') . '\\b[^>]*>/i', $html, $tokens, PREG_OFFSET_CAPTURE, $contentStart);
        $depth = 1;
        foreach ($tokens[0] as $token) {
            $text = $token[0];
            if (str_starts_with($text, '</')) {
                $depth--;
                if ($depth === 0) {
                    return $token[1] + strlen($text);
                }
            } elseif (!str_ends_with(rtrim($text), '/>')) {
                $depth++;
            }
        }

        // Preserve the opening element's location if malformed HTML has no close tag.
        return $contentStart;
    }
}

/** @return array{source:string, html:string, customSelectors:array<int, string>} */
function readInput(array $arguments): array
{
    $options = getopt('', ['url:', 'file:', 'sequence3:', 'sequence4:', 'sequence5:', 'help']);
    if (isset($options['help']) || (!isset($options['url']) && !isset($options['file']))) {
        fwrite(STDERR, "Usage: php bot/structure_bot.php (--url=URL | --file=PATH) [--sequence3=SELECTOR] [--sequence4=SELECTOR] [--sequence5=SELECTOR]\\n");
        exit(isset($options['help']) ? 0 : 1);
    }
    if (isset($options['url'], $options['file'])) {
        fwrite(STDERR, "Choose either --url or --file, not both.\\n");
        exit(1);
    }

    if (isset($options['file'])) {
        $path = $options['file'];
        $html = @file_get_contents($path);
        if ($html === false) {
            fwrite(STDERR, "Unable to read file: {$path}\\n");
            exit(1);
        }
        $source = realpath($path) ?: $path;
    } else {
        if (!filter_var($options['url'], FILTER_VALIDATE_URL)) {
            fwrite(STDERR, "--url must be a valid URL.\\n");
            exit(1);
        }
        $handle = curl_init($options['url']);
        curl_setopt_array($handle, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_USERAGENT => 'AIEngineStructureBot/1.0',
        ]);
        $html = curl_exec($handle);
        $error = curl_error($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        curl_close($handle);
        if ($html === false || $status >= 400) {
            fwrite(STDERR, "Unable to fetch URL" . ($error !== '' ? ": {$error}" : " (HTTP {$status})") . ".\\n");
            exit(1);
        }
        $source = $options['url'];
    }

    $customSelectors = [];
    for ($sequence = 3; $sequence <= 5; $sequence++) {
        $key = 'sequence' . $sequence;
        if (isset($options[$key]) && trim($options[$key]) !== '') {
            $customSelectors[$sequence] = trim($options[$key]);
        }
    }

    return ['source' => $source, 'html' => $html, 'customSelectors' => $customSelectors];
}

$input = readInput($argv);
$result = (new StructureBot())->inspect($input['html'], $input['source'], $input['customSelectors']);
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
