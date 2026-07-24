<?php

use LBHurtado\XDocument\Browser\Html\BrowserHtmlProjectionAdapter;
use LBHurtado\XDocument\Contract\DocumentCompilationRequest;
use LBHurtado\XDocument\Contract\ResolvedDocument;
use LBHurtado\XDocument\Contracts\BrowserDocumentDriver;
use LBHurtado\XDocument\Contracts\DocumentDriver;
use LBHurtado\XDocument\Contracts\PdfDocumentDriver;
use LBHurtado\XDocument\Drivers\BrowserDocumentDriver as ConcreteBrowserDocumentDriver;
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

it('keeps JSON compilation free of broad catches rendering persistence and network access', function () {
    $source = file_get_contents(dirname(__DIR__, 2).'/src/Drivers/JsonDocumentDriver.php');

    expect($source)->not->toContain(
        'catch (Throwable',
        'Repository',
        'Eloquent',
        'PDF',
        'HTML',
        'Http',
        'file_put_contents',
        'curl_',
    );
});

it('implements only the browser boundary and leaves PDF deferred', function () {
    expect(BrowserDocumentDriver::class)->toBeInterface()
        ->and(PdfDocumentDriver::class)->toBeInterface()
        ->and(ConcreteBrowserDocumentDriver::class)->toImplement(BrowserDocumentDriver::class)
        ->and(class_exists('LBHurtado\XDocument\Drivers\PdfDocumentDriver'))->toBeFalse();
});

it('keeps browser projection free of frameworks rendering persistence network and broad catches', function () {
    $root = dirname(__DIR__, 2);
    $sources = [
        file_get_contents($root.'/src/Drivers/BrowserDocumentDriver.php'),
    ];
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/src/Projection/Browser')) as $file) {
        if ($file instanceof SplFileInfo && $file->isFile()) {
            $sources[] = file_get_contents($file->getPathname());
        }
    }

    expect(implode("\n", $sources))->not->toContain(
        'catch (Throwable',
        'CompilationSubject',
        'ArtifactChain',
        'Repository',
        'Eloquent',
        'Inertia',
        'Vue',
        'React',
        'Blade',
        'Livewire',
        'Illuminate\\Http',
        'PDF',
        'HTML',
        'file_put_contents',
        'curl_',
    );
});

arch('browser and JSON drivers remain independent peers')
    ->expect(ConcreteBrowserDocumentDriver::class)
    ->not->toUse([JsonDocumentDriver::class, PdfDocumentDriver::class])
    ->and(JsonDocumentDriver::class)
    ->not->toUse([ConcreteBrowserDocumentDriver::class, BrowserDocumentDriver::class]);

arch('HTML adapter consumes only browser projection and output boundaries')
    ->expect(BrowserHtmlProjectionAdapter::class)
    ->not->toUse([
        ResolvedDocument::class,
        DocumentCompilationRequest::class,
        ConcreteBrowserDocumentDriver::class,
        JsonDocumentDriver::class,
    ]);

it('keeps HTML adaptation free of business frameworks scripts persistence network and broad catches', function () {
    $source = file_get_contents(dirname(__DIR__, 2).'/src/Browser/Html/BrowserHtmlProjectionAdapter.php');

    expect($source)->not->toContain(
        'catch (Throwable',
        'CompilationSubject',
        'ArtifactChain',
        'Repository',
        'Eloquent',
        'Inertia',
        'Vue',
        'React',
        'Blade',
        'Livewire',
        'Illuminate\\Http',
        'ResolvedDocument',
        'DocumentCompilationRequest',
        '<script',
        '<button',
        '<form',
        'href=',
        'onclick=',
        'PDF',
        'file_put_contents',
        'curl_',
    );
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

arch('compatibility harness is independent of projection drivers')
    ->expect('LBHurtado\XDocument\Compatibility')
    ->not->toUse([
        'LBHurtado\XDocument\Drivers',
        'LBHurtado\XDocument\Contracts\DocumentDriver',
    ]);
