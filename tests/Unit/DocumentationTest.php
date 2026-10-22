<?php

use Duitku\Laravel\Data\PaymentRequest;
use League\CommonMark\GithubFlavoredMarkdownConverter;

it('keeps the documented payment fields and types aligned with the DTO', function () {
    $markdown = file_get_contents(__DIR__.'/../../docs/guide/usage-payments.md');
    $html = (new GithubFlavoredMarkdownConverter)->convert($markdown);
    $document = new DOMDocument;
    @$document->loadHTML((string) $html);
    $rows = (new DOMXPath($document))->query('//table[1]/tbody/tr');
    $documented = [];

    foreach ($rows as $row) {
        $cells = $row->getElementsByTagName('td');
        $documented[$cells->item(0)->textContent] = $cells->item(1)->textContent;
    }

    $parameters = (new ReflectionClass(PaymentRequest::class))->getConstructor()->getParameters();
    $names = array_map(fn ($parameter) => $parameter->getName(), $parameters);
    expect(array_keys($documented))->toEqualCanonicalizing($names);

    foreach ($parameters as $parameter) {
        $type = $parameter->getType();
        $types = $type instanceof ReflectionUnionType ? $type->getTypes() : [$type];

        foreach ($types as $namedType) {
            $shortName = class_basename($namedType->getName());
            expect($documented[$parameter->getName()])->toContain($shortName);
        }

        expect(str_contains($documented[$parameter->getName()], 'null'))->toBe($type->allowsNull());
    }
});
