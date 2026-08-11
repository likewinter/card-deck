<?php

declare(strict_types=1);

/**
 * Generates docs/api.md from the docblocks in src/.
 *
 * Usage:
 *   php scripts/generate-api-reference.php           Write docs/api.md
 *   php scripts/generate-api-reference.php --check   Exit non-zero if docs/api.md is out of date
 *
 * Zero runtime dependencies: only the Reflection API and composer's autoloader.
 * The script also enforces documentation coverage — every public class,
 * method, property, and constant must carry a docblock description.
 */

const ROOT_NAMESPACE = 'Likewinter\CardDeck';

require __DIR__ . '/../vendor/autoload.php';

$projectRoot = dirname(__DIR__);
$outputFile = $projectRoot . '/docs/api.md';
$checkOnly = in_array('--check', $argv, true);

requireAllPhpFiles($projectRoot . '/src');

$types = discoverTypes(ROOT_NAMESPACE);
if ($types === []) {
    fwrite(STDERR, "No classes found under the " . ROOT_NAMESPACE . " namespace.\n");
    exit(1);
}

$sections = groupIntoSections($types);

$missing = findMissingDocumentation($types);
if ($missing !== []) {
    fwrite(STDERR, "Missing docblocks:\n");
    foreach ($missing as $item) {
        fwrite(STDERR, "  - {$item}\n");
    }
    fwrite(STDERR, 'Document the items above, then run `composer api-docs`.' . "\n");
    exit(1);
}

$markdown = renderReference($sections);

if ($checkOnly) {
    $existing = is_file($outputFile) ? file_get_contents($outputFile) : '';
    if ($existing !== $markdown) {
        fwrite(STDERR, "docs/api.md is out of date. Run `composer api-docs` and commit the result.\n");
        exit(1);
    }
    echo "docs/api.md is up to date.\n";
    exit(0);
}

file_put_contents($outputFile, $markdown);
echo 'Wrote ' . $outputFile . "\n";

// ── Discovery ────────────────────────────────────────────────────────────

function requireAllPhpFiles(string $directory): void
{
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            require_once $file->getPathname();
        }
    }
}

/**
 * @return list<ReflectionClass>
 */
function discoverTypes(string $namespacePrefix): array
{
    $types = [];
    foreach (array_merge(get_declared_classes(), get_declared_interfaces()) as $name) {
        $reflection = new ReflectionClass($name);
        if ($reflection->isAnonymous() || !str_starts_with($name, $namespacePrefix . '\\')) {
            continue;
        }
        $types[] = $reflection;
    }

    usort($types, static fn(ReflectionClass $a, ReflectionClass $b) => $a->getName() <=> $b->getName());

    return array_values(array_unique($types, SORT_REGULAR));
}

/**
 * @param list<ReflectionClass> $types
 *
 * @return array<string, list<ReflectionClass>>
 */
function groupIntoSections(array $types): array
{
    $sections = [
        'Card identity' => [],
        'Core primitives' => [],
        'Reference games' => [],
    ];

    foreach ($types as $type) {
        $namespace = $type->getNamespaceName();
        if ($namespace === ROOT_NAMESPACE . '\Card') {
            $sections['Card identity'][] = $type;
        } elseif ($namespace === ROOT_NAMESPACE) {
            $sections['Core primitives'][] = $type;
        } elseif (str_starts_with($namespace, ROOT_NAMESPACE . '\Games')) {
            $sections['Reference games'][] = $type;
        }
    }

    foreach ($sections as &$sectionTypes) {
        usort($sectionTypes, static fn(ReflectionClass $a, ReflectionClass $b) => $a->getShortName() <=> $b->getShortName());
    }

    return $sections;
}

// ── Docblock parsing ─────────────────────────────────────────────────────

/**
 * @return array{description: string, tags: list<array{name: string, body: string}>}
 */
function parseDocblock(string|false|null $docComment): array
{
    if ($docComment === false || $docComment === null || $docComment === '') {
        return ['description' => '', 'tags' => []];
    }

    $cleaned = [];
    foreach (explode("\n", $docComment) as $line) {
        $line = preg_replace('#^\s*/\*\*|\*/\s*$#', '', $line) ?? '';
        $line = preg_replace('#^\s*\*\s?#', '', $line) ?? '';
        $cleaned[] = rtrim($line);
    }
    while ($cleaned !== [] && trim($cleaned[0]) === '') {
        array_shift($cleaned);
    }
    while ($cleaned !== [] && trim(end($cleaned)) === '') {
        array_pop($cleaned);
    }

    $descriptionLines = [];
    $tags = [];
    $tagIndex = -1;

    foreach ($cleaned as $line) {
        if (preg_match('/^@([A-Za-z][\w-]*)\s*(.*)$/', $line, $matches) === 1) {
            $tags[] = ['name' => $matches[1], 'body' => trim($matches[2])];
            $tagIndex = count($tags) - 1;
            continue;
        }

        if ($tagIndex >= 0) {
            if (trim($line) !== '') {
                $tags[$tagIndex]['body'] .= ' ' . trim($line);
            }
            continue;
        }

        $descriptionLines[] = $line;
    }

    while ($descriptionLines !== [] && trim(end($descriptionLines)) === '') {
        array_pop($descriptionLines);
    }

    return ['description' => implode("\n", $descriptionLines), 'tags' => $tags];
}

/**
 * Split a @param body of the form "Type $name description" into parts.
 * The type may itself contain "$" characters (callable signatures), so
 * the LAST "$identifier" is treated as the parameter name.
 *
 * @return array{name: string, description: string}|null
 */
function parseParamTag(string $body): ?array
{
    if (preg_match('/^(.*\S)\s+\$(\w+)\s*(.*)$/s', $body, $matches) !== 1) {
        return null;
    }

    return ['name' => $matches[2], 'description' => trim($matches[3])];
}

// ── Documentation coverage ───────────────────────────────────────────────

/**
 * @param list<ReflectionClass> $types
 *
 * @return list<string>
 */
function findMissingDocumentation(array $types): array
{
    $missing = [];

    foreach ($types as $type) {
        $label = $type->getName();

        if (parseDocblock($type->getDocComment())['description'] === '') {
            $missing[] = "{$label} (class docblock)";
        }

        foreach (publicMethods($type) as $method) {
            $parsed = parseDocblock($method->getDocComment());
            $hasDocTags = array_any(
                $parsed['tags'],
                static fn(array $tag) => in_array($tag['name'], ['param', 'return', 'throws'], true),
            );
            if ($parsed['description'] === '' && !$hasDocTags) {
                $missing[] = "{$label}::{$method->getName()}()";
            }
        }

        foreach (publicConstants($type) as $constant) {
            if (parseDocblock($constant->getDocComment())['description'] === '') {
                $missing[] = "{$label}::{$constant->getName()}";
            }
        }

        $constructorParams = constructorParamDescriptions($type);
        foreach (publicProperties($type) as $property) {
            if ($property->isPromoted() && ($constructorParams[$property->getName()] ?? '') !== '') {
                continue;
            }
            $parsed = parseDocblock($property->getDocComment());
            $hasVar = tagBody($parsed, 'var') !== null;
            if ($parsed['description'] === '' && !$hasVar) {
                $missing[] = "{$label}::\${$property->getName()}";
            }
        }
    }

    return $missing;
}

/**
 * @return list<ReflectionMethod>
 */
function publicMethods(ReflectionClass $type): array
{
    $methods = [];
    foreach ($type->getMethods() as $method) {
        if (!$method->isPublic() || $method->getDeclaringClass()->getName() !== $type->getName()) {
            continue;
        }
        if ($type->isEnum() && in_array($method->getName(), ['cases', 'from', 'tryFrom'], true)) {
            continue;
        }
        $methods[] = $method;
    }

    return $methods;
}

/**
 * Enum cases are excluded — they are rendered in the Cases section.
 * (getReflectionConstants() reports cases as plain ReflectionClassConstant,
 * so they are filtered out by name.)
 *
 * @return list<ReflectionClassConstant>
 */
function publicConstants(ReflectionClass $type): array
{
    $caseNames = [];
    if ($type->isEnum()) {
        // ReflectionEnumCase and ReflectionEnumBackedCase are siblings;
        // getName() comes from the Reflector interface they both implement.
        $caseNames = array_map(
            static fn(\Reflector $case) => $case->getName(),
            (new ReflectionEnum($type->getName()))->getCases(),
        );
    }

    return array_values(array_filter(
        $type->getReflectionConstants(ReflectionClassConstant::IS_PUBLIC),
        static fn(ReflectionClassConstant $constant) => !in_array($constant->getName(), $caseNames, true),
    ));
}

/**
 * Excludes the synthetic $name/$value properties PHP adds to every enum.
 *
 * @return list<ReflectionProperty>
 */
function publicProperties(ReflectionClass $type): array
{
    $properties = [];
    foreach ($type->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
        if ($property->getDeclaringClass()->getName() !== $type->getName()) {
            continue;
        }
        if ($type->isEnum() && in_array($property->getName(), ['name', 'value'], true)) {
            continue;
        }
        $properties[] = $property;
    }

    return $properties;
}

/**
 * @return array<string, string>
 */
function constructorParamDescriptions(ReflectionClass $type): array
{
    $constructor = $type->getConstructor();
    if ($constructor === null) {
        return [];
    }

    $descriptions = [];
    foreach (parseDocblock($constructor->getDocComment())['tags'] as $tag) {
        if ($tag['name'] !== 'param') {
            continue;
        }
        $info = parseParamTag($tag['body']);
        if ($info !== null && $info['description'] !== '') {
            $descriptions[$info['name']] = $info['description'];
        }
    }

    return $descriptions;
}

/**
 * The body of the first tag with the given name, or null.
 *
 * @param array{description: string, tags: list<array{name: string, body: string}>} $parsed
 */
function tagBody(array $parsed, string $name): ?string
{
    foreach ($parsed['tags'] as $tag) {
        if ($tag['name'] === $name) {
            return $tag['body'];
        }
    }

    return null;
}

// ── Rendering ────────────────────────────────────────────────────────────

/**
 * @param array<string, list<ReflectionClass>> $sections
 */
function renderReference(array $sections): string
{
    $out = <<<'MD'
        # API Reference

        Complete reference for the public API of `likewinter/card-deck`,
        generated from the source docblocks by `scripts/generate-api-reference.php`.
        Do not edit this file by hand — update the docblocks in `src/` and run
        `composer api-docs`.

        For guided, task-oriented documentation see the other pages in this
        directory; this reference is the exhaustive companion.

        ## Contents

        MD;

    $toc = [];
    foreach ($sections as $title => $sectionTypes) {
        $links = array_map(
            static fn(ReflectionClass $type) => sprintf('[%s](#%s)', $type->getShortName(), strtolower($type->getShortName())),
            $sectionTypes,
        );
        $toc[] = "- **{$title}:** " . implode(', ', $links);
    }
    $out .= implode("\n", $toc) . "\n";

    foreach ($sections as $title => $sectionTypes) {
        $out .= "\n## {$title}\n";
        foreach ($sectionTypes as $type) {
            $out .= renderType($type);
        }
    }

    return $out;
}

function renderType(ReflectionClass $type): string
{
    $out = sprintf("\n### %s\n\n", $type->getShortName());
    $out .= '`' . kindLine($type) . "`\n";

    $description = parseDocblock($type->getDocComment())['description'];
    if ($description !== '') {
        $out .= "\n{$description}\n";
    }

    if ($type->isEnum()) {
        $out .= renderEnumCases($type);
    }

    $constants = publicConstants($type);
    if ($constants !== []) {
        $out .= "\n**Constants**\n\n";
        foreach ($constants as $constant) {
            $out .= renderConstant($constant);
        }
    }

    $properties = publicProperties($type);
    if ($properties !== []) {
        $out .= "\n**Properties**\n\n";
        $constructorParams = constructorParamDescriptions($type);
        foreach ($properties as $property) {
            $out .= renderProperty($property, $constructorParams, $type);
        }
    }

    $methods = publicMethods($type);
    if ($methods !== []) {
        $out .= "\n**Methods**\n\n";
        foreach ($methods as $method) {
            $out .= renderMethod($method);
        }
    }

    return $out;
}

function kindLine(ReflectionClass $type): string
{
    $kind = $type->isEnum() ? 'enum' : ($type->isInterface() ? 'interface' : 'class');

    $modifiers = [];
    if ($type->isFinal()) {
        $modifiers[] = 'final';
    }
    if ($type->isAbstract() && $kind === 'class') {
        $modifiers[] = 'abstract';
    }
    if ($type->isReadOnly()) {
        $modifiers[] = 'readonly';
    }

    $line = trim(implode(' ', $modifiers) . ' ' . $kind) . ' ' . $type->getName();

    if ($type->isEnum()) {
        $backingType = (new ReflectionEnum($type->getName()))->getBackingType();
        if ($backingType !== null) {
            $line .= ': ' . shortenTypes((string) $backingType);
        }
    }

    $interfaces = directInterfaces($type);
    if ($interfaces !== []) {
        $verb = $type->isInterface() ? 'extends' : 'implements';
        $line .= " {$verb} " . implode(', ', array_map(shortenTypes(...), $interfaces));
    }

    return $line;
}

/**
 * The interfaces the type declares itself, without the implicit noise:
 * ancestors of other listed interfaces (Traversable via IteratorAggregate),
 * UnitEnum/BackedEnum on enums, and the Stringable that PHP adds to any
 * class with __toString (kept for interfaces, where it is a real parent).
 *
 * @return list<string>
 */
function directInterfaces(ReflectionClass $type): array
{
    $interfaces = $type->getInterfaceNames();

    if ($type->isEnum()) {
        $interfaces = array_values(array_diff($interfaces, ['UnitEnum', 'BackedEnum']));
    }

    $direct = [];
    foreach ($interfaces as $candidate) {
        $isAncestorOfAnother = array_any(
            $interfaces,
            static fn(string $other) => $candidate !== $other && is_subclass_of($other, $candidate),
        );
        if (!$isAncestorOfAnother) {
            $direct[] = $candidate;
        }
    }

    if (!$type->isInterface()) {
        $direct = array_values(array_diff($direct, ['Stringable']));
    }

    return $direct;
}

function renderEnumCases(ReflectionClass $type): string
{
    $out = "\n**Cases**\n\n";
    foreach ((new ReflectionEnum($type->getName()))->getCases() as $case) {
        $rendered = '`' . $case->getName();
        if ($case instanceof ReflectionEnumBackedCase) {
            $rendered .= ' = ' . var_export($case->getBackingValue(), true);
        }
        $rendered .= '`';

        $description = parseDocblock($case->getDocComment())['description'];
        $out .= '- ' . $rendered . ($description !== '' ? ' — ' . oneLine($description) : '') . "\n";
    }

    return $out;
}

function renderConstant(ReflectionClassConstant $constant): string
{
    $description = parseDocblock($constant->getDocComment())['description'];
    $value = var_export($constant->getValue(), true);

    return sprintf("- `%s = %s`%s\n", $constant->getName(), $value, $description !== '' ? ' — ' . oneLine($description) : '');
}

/**
 * @param array<string, string> $constructorParams
 */
function renderProperty(ReflectionProperty $property, array $constructorParams, ReflectionClass $type): string
{
    $parsed = parseDocblock($property->getDocComment());
    $description = $parsed['description'];

    if ($property->isPromoted()) {
        $description = $constructorParams[$property->getName()] ?? $description;
    }

    $varTag = tagBody($parsed, 'var');
    $displayType = $varTag !== null
        ? leadingType($varTag)[0]
        : shortenTypes((string) $property->getType());

    $attributes = ["`{$displayType}`"];
    if ($property->isReadOnly() && !$type->isReadOnly()) {
        $attributes[] = 'readonly';
    }

    return sprintf(
        "- `$%s` (%s)%s\n",
        $property->getName(),
        implode(', ', $attributes),
        $description !== '' ? ' — ' . oneLine($description) : '',
    );
}

function renderMethod(ReflectionMethod $method): string
{
    $parsed = parseDocblock($method->getDocComment());

    $signature = '';
    if ($method->getAttributes(\NoDiscard::class) !== []) {
        $signature .= '#[NoDiscard] ';
    }
    if ($method->isStatic()) {
        $signature .= 'static ';
    }
    $signature .= $method->getName() . '(' . implode(', ', array_map(renderParameter(...), $method->getParameters())) . ')';
    $returnType = $method->getReturnType();
    if ($returnType !== null) {
        $signature .= ': ' . shortenTypes((string) $returnType);
    }

    $out = "- **`{$signature}`**\n";

    if ($parsed['description'] !== '') {
        $out .= "\n" . indent(oneParagraphBlock($parsed['description'])) . "\n";
    }

    $bullets = [];
    foreach ($parsed['tags'] as $tag) {
        if ($tag['name'] === 'param') {
            $info = parseParamTag($tag['body']);
            if ($info !== null && $info['description'] !== '') {
                $bullets[] = sprintf('- `$%s` — %s', $info['name'], $info['description']);
            }
        } elseif ($tag['name'] === 'return' && $tag['body'] !== '') {
            [$returnType, $returnDescription] = leadingType($tag['body']);
            $bullets[] = sprintf(
                '- **Returns:** `%s`%s',
                shortenTypes($returnType),
                $returnDescription !== '' ? ' — ' . $returnDescription : '',
            );
        } elseif ($tag['name'] === 'throws') {
            [$exception, $throwsDescription] = leadingType($tag['body']);
            $bullets[] = sprintf(
                '- **Throws:** `%s`%s',
                shortenTypes($exception),
                $throwsDescription !== '' ? ' — ' . $throwsDescription : '',
            );
        }
    }

    if ($bullets !== []) {
        $out .= ($parsed['description'] === '' ? "\n" : '') . indent(implode("\n", $bullets)) . "\n";
    }

    return $out;
}

function renderParameter(ReflectionParameter $parameter): string
{
    $rendered = '';
    $type = $parameter->getType();
    if ($type !== null) {
        $rendered .= shortenTypes((string) $type) . ' ';
    }
    if ($parameter->isVariadic()) {
        $rendered .= '...';
    }
    $rendered .= '$' . $parameter->getName();

    if (!$parameter->isVariadic() && $parameter->isDefaultValueAvailable()) {
        $rendered .= ' = ' . renderDefaultValue($parameter->getDefaultValue());
    }

    return $rendered;
}

function renderDefaultValue(mixed $value): string
{
    if ($value instanceof \UnitEnum) {
        return shortenTypes($value::class) . '::' . $value->name;
    }
    if ($value === null) {
        return 'null';
    }
    if ($value === []) {
        return '[]';
    }

    return var_export($value, true);
}

function shortenTypes(string $type): string
{
    return str_replace(ROOT_NAMESPACE . '\\', '', $type);
}

/**
 * Indent a block by two spaces so it nests under the method's list item.
 */
function indent(string $block): string
{
    return implode("\n", array_map(
        static fn(string $line) => $line === '' ? '' : '  ' . $line,
        explode("\n", $block),
    ));
}

/**
 * Collapse wrapped prose lines to single lines while preserving
 * intentionally indented lines (inline lists, aligned examples) as
 * separate lines with hard Markdown breaks. Blank-line paragraph
 * breaks are kept.
 */
function oneParagraphBlock(string $text): string
{
    $paragraphs = preg_split('/\n\s*\n/', $text) ?: [];
    $renderedParagraphs = [];

    foreach ($paragraphs as $paragraph) {
        $kept = [];
        $prose = [];

        foreach (explode("\n", trim($paragraph, "\n")) as $line) {
            if (preg_match('/^\s+\S/', $line) === 1) {
                if ($prose !== []) {
                    $kept[] = implode(' ', $prose);
                    $prose = [];
                }
                $kept[] = trim($line);
            } else {
                $prose[] = trim($line);
            }
        }
        if ($prose !== []) {
            $kept[] = implode(' ', $prose);
        }

        $renderedParagraphs[] = implode("  \n", $kept);
    }

    return implode("\n\n", $renderedParagraphs);
}

/**
 * Split a tag body into its leading type and remaining description.
 * Generic types may contain spaces ("array<string, list<Card>>"), so
 * the split point is the first space at zero bracket depth that is not
 * part of a union/intersection continuation.
 *
 * @return array{0: string, 1: string} [type, description]
 */
function leadingType(string $body): array
{
    $depth = 0;
    $length = strlen($body);

    for ($i = 0; $i < $length; $i++) {
        $char = $body[$i];
        if ($char === '<' || $char === '{' || $char === '(') {
            $depth++;
            continue;
        }
        if ($char === '>' || $char === '}' || $char === ')') {
            $depth--;
            continue;
        }
        if ($char !== ' ' || $depth !== 0) {
            continue;
        }

        $rest = ltrim(substr($body, $i + 1));
        if ($rest !== '' && ($rest[0] === '|' || $rest[0] === '&')) {
            continue;
        }

        return [substr($body, 0, $i), $rest];
    }

    return [$body, ''];
}

/**
 * Collapse a multi-line description to a single line for inline use.
 */
function oneLine(string $text): string
{
    return preg_replace('/\s*\n\s*/', ' ', trim($text)) ?? $text;
}
