<?php

use LBHurtado\XDocument\Contracts\BrowserDocumentDriver;
use LBHurtado\XDocument\Contracts\DocumentDriver;
use LBHurtado\XDocument\Contracts\PdfDocumentDriver;
use LBHurtado\XDocument\Drivers\JsonDocumentDriver;

arch('package source is framework and persistence independent')
    ->expect('LBHurtado\XDocument')
    ->not->toUse([
        'Illuminate\Database',
        'Illuminate\Http',
        'App',
        'ThreeNeti',
    ]);

arch('JSON driver implements only the generic document driver')
    ->expect(JsonDocumentDriver::class)
    ->toImplement(DocumentDriver::class)
    ->not->toUse([BrowserDocumentDriver::class, PdfDocumentDriver::class]);

it('defines browser and PDF boundaries without implementations', function () {
    expect(BrowserDocumentDriver::class)->toBeInterface()
        ->and(PdfDocumentDriver::class)->toBeInterface()
        ->and(class_exists('LBHurtado\XDocument\Drivers\BrowserDocumentDriver'))->toBeFalse()
        ->and(class_exists('LBHurtado\XDocument\Drivers\PdfDocumentDriver'))->toBeFalse();
});

it('contains no GNE repository business or settlement machinery', function () {
    $root = dirname(__DIR__, 2);
    $sources = [];
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/src')) as $file) {
        if ($file instanceof SplFileInfo && $file->isFile()) {
            $sources[] = file_get_contents($file->getPathname());
        }
    }
    $source = implode("\n", $sources);
    $composer = file_get_contents($root.'/composer.json');

    expect($source)->not->toContain(
        'CompilationSubject',
        'ArtifactChain',
        'ResolveDocument',
        'Eloquent',
        'Inertia',
        'Vue',
        'x-change',
        'Settlement',
        'Voucher',
        'Pay Code',
        'Adobe',
        'AcroForm',
        'XFDF',
    )->and($composer)->not->toContain('3neti/gne', 'illuminate/database', 'inertia', 'vue');
});
