<?php

namespace LBHurtado\XDocument\Drivers;

use LBHurtado\XDocument\Contract\CanonicalJson;
use LBHurtado\XDocument\Contract\DocumentCompilationRequest;
use LBHurtado\XDocument\Contract\DocumentCompilationResult;
use LBHurtado\XDocument\Contract\DocumentOutput;
use LBHurtado\XDocument\Contract\ValidateDocumentCompilationResult;
use LBHurtado\XDocument\Contracts\DocumentDriver;

final readonly class JsonDocumentDriver implements DocumentDriver
{
    public function __construct(
        private CanonicalJson $json = new CanonicalJson,
        private ValidateDocumentCompilationResult $validateResult = new ValidateDocumentCompilationResult,
    ) {}

    public function name(): string
    {
        return 'json';
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
                warnings: ["Driver json cannot compile a request targeted to {$request->requestedDriver}."],
                capabilities: $this->capabilities(),
                metadata: [
                    'requested_driver' => $request->requestedDriver,
                    'supported_driver' => $this->name(),
                ],
            ));
        }

        $unsupportedCapabilities = array_values(array_diff($request->requestedCapabilities, $this->capabilities()));
        sort($unsupportedCapabilities, SORT_STRING);
        if ($unsupportedCapabilities !== []) {
            return $this->validateResult->handle(DocumentCompilationResult::unsupported(
                request: $request,
                driver: $this->name(),
                warnings: array_map(
                    fn (string $capability): string => "Unsupported capability: {$capability}",
                    $unsupportedCapabilities,
                ),
                capabilities: $this->capabilities(),
                metadata: [
                    'requested_capabilities' => $request->requestedCapabilities,
                    'supported_capabilities' => $this->capabilities(),
                    'unsupported_capabilities' => $unsupportedCapabilities,
                ],
            ));
        }

        $content = $this->json->encode($request->toArray(), pretty: true).PHP_EOL;
        $output = DocumentOutput::inline(mediaType: 'application/json', content: $content);

        return $this->validateResult->handle(DocumentCompilationResult::succeeded(
            request: $request,
            driver: $this->name(),
            output: $output,
            capabilities: $this->capabilities(),
            metadata: [
                'output_identity' => $output->checksum,
                'request_fingerprint' => $request->requestFingerprint,
            ],
        ));
    }
}
