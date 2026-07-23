<?php

namespace LBHurtado\XDocument\Contract;

use JsonException;
use LBHurtado\XDocument\Exceptions\InvalidDocumentContract;
use LBHurtado\XDocument\Exceptions\UnsupportedContractVersion;
use LogicException;
use Opis\JsonSchema\Errors\ErrorFormatter;
use stdClass;

final readonly class ValidateDocumentCompilationRequest
{
    public function __construct(private ContractSchemaRegistry $schemas = new ContractSchemaRegistry) {}

    /** @param array<string, mixed> $payload */
    public function handle(array $payload): DocumentCompilationRequest
    {
        $data = json_decode(json_encode($payload, JSON_THROW_ON_ERROR), false, flags: JSON_THROW_ON_ERROR);
        if (! $data instanceof stdClass) {
            throw new InvalidDocumentContract('The x-document request must be a JSON object.');
        }

        return $this->validate($data);
    }

    public function handleJson(string $json): DocumentCompilationRequest
    {
        try {
            $data = json_decode($json, false, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidDocumentContract('The x-document request is malformed JSON.', previous: $exception);
        }
        if (! $data instanceof stdClass) {
            throw new InvalidDocumentContract('The x-document request must be a JSON object.');
        }

        return $this->validate($data);
    }

    private function validate(stdClass $data): DocumentCompilationRequest
    {
        $version = $data->contract_version ?? null;
        if (is_string($version) && $version !== ContractVersion::Version1) {
            throw new UnsupportedContractVersion("Unsupported x-document contract version: {$version}.");
        }
        $result = $this->schemas->validator()->validate($data, ContractSchemaRegistry::Request);
        if (! $result->isValid()) {
            $error = $result->error();
            if ($error === null) {
                throw new LogicException('Opis reported an invalid contract without a validation error.');
            }
            $errors = (new ErrorFormatter)->format($error);
            throw new InvalidDocumentContract('Invalid x-document contract 1.0 request: '.json_encode($errors, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        }

        return DocumentCompilationRequest::fromObject($data);
    }
}
