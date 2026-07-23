<?php

namespace LBHurtado\XDocument\Drivers;

use LBHurtado\XDocument\Contract\CanonicalJson;
use LBHurtado\XDocument\Contract\DocumentCompilationRequest;
use LBHurtado\XDocument\Contract\DocumentCompilationResult;
use LBHurtado\XDocument\Contract\DocumentOutput;
use LBHurtado\XDocument\Contracts\DocumentDriver;

final readonly class JsonDocumentDriver implements DocumentDriver
{
    public function __construct(private CanonicalJson $json = new CanonicalJson) {}

    public function name(): string
    {
        return 'json';
    }

    public function compile(DocumentCompilationRequest $request): DocumentCompilationResult
    {
        $content = $this->json->encode($request->toArray(), pretty: true).PHP_EOL;

        return new DocumentCompilationResult(
            $request->contractVersion,
            $request->requestIdentifier,
            $request->document->identifier,
            $request->document->resolutionFingerprint,
            $this->name(),
            'succeeded',
            new DocumentOutput(
                mediaType: 'application/json',
                checksum: 'sha256:'.hash('sha256', $content),
                byteLength: strlen($content),
                inlineContent: $content,
            ),
            capabilities: ['json'],
        );
    }
}
