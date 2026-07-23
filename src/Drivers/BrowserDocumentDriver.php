<?php

namespace LBHurtado\XDocument\Drivers;

use LBHurtado\XDocument\Contract\CanonicalJson;
use LBHurtado\XDocument\Contract\DocumentCompilationRequest;
use LBHurtado\XDocument\Contract\DocumentCompilationResult;
use LBHurtado\XDocument\Contract\DocumentOutput;
use LBHurtado\XDocument\Contract\ValidateDocumentCompilationResult;
use LBHurtado\XDocument\Contracts\BrowserDocumentDriver as BrowserDocumentDriverContract;
use LBHurtado\XDocument\Projection\Browser\BrowserProjection;
use LBHurtado\XDocument\Projection\Browser\BuildBrowserProjection;
use LBHurtado\XDocument\Projection\Browser\ValidateBrowserProjection;

final readonly class BrowserDocumentDriver implements BrowserDocumentDriverContract
{
    public function __construct(
        private BuildBrowserProjection $buildProjection = new BuildBrowserProjection,
        private ValidateBrowserProjection $validateProjection = new ValidateBrowserProjection,
        private CanonicalJson $json = new CanonicalJson,
        private ValidateDocumentCompilationResult $validateResult = new ValidateDocumentCompilationResult,
    ) {}

    public function name(): string
    {
        return 'browser';
    }

    /** @return list<string> */
    public function capabilities(): array
    {
        return ['actions', 'attachments', 'evidence'];
    }

    public function compile(DocumentCompilationRequest $request): DocumentCompilationResult
    {
        if ($request->requestedDriver !== $this->name()) {
            return $this->validateResult->handle(DocumentCompilationResult::unsupported(
                request: $request,
                driver: $this->name(),
                warnings: ["Driver browser cannot compile a request targeted to {$request->requestedDriver}."],
                capabilities: $this->capabilities(),
                metadata: ['requested_driver' => $request->requestedDriver, 'supported_driver' => $this->name()],
            ));
        }
        $unsupportedCapabilities = array_values(array_diff($request->requestedCapabilities, $this->capabilities()));
        sort($unsupportedCapabilities, SORT_STRING);
        if ($unsupportedCapabilities !== []) {
            return $this->validateResult->handle(DocumentCompilationResult::unsupported(
                request: $request,
                driver: $this->name(),
                warnings: array_map(fn (string $capability): string => "Unsupported capability: {$capability}", $unsupportedCapabilities),
                capabilities: $this->capabilities(),
                metadata: [
                    'requested_capabilities' => $request->requestedCapabilities,
                    'supported_capabilities' => $this->capabilities(),
                    'unsupported_capabilities' => $unsupportedCapabilities,
                ],
            ));
        }

        $projection = $this->validateProjection->handle($this->buildProjection->handle($request));
        $content = $this->json->encode($projection->toArray(), pretty: true).PHP_EOL;
        $output = DocumentOutput::inline(BrowserProjection::MediaType, $content);

        return $this->validateResult->handle(DocumentCompilationResult::succeeded(
            request: $request,
            driver: $this->name(),
            output: $output,
            capabilities: $this->capabilities(),
            metadata: [
                'output_identity' => $output->checksum,
                'projection_format' => BrowserProjection::Format,
                'projection_identifier' => $projection->identifier,
                'request_fingerprint' => $request->requestFingerprint,
            ],
        ));
    }
}
