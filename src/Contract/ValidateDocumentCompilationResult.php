<?php

namespace LBHurtado\XDocument\Contract;

use LBHurtado\XDocument\Exceptions\InvalidDocumentCompilationResult;
use LogicException;
use Opis\JsonSchema\Errors\ErrorFormatter;
use stdClass;

final readonly class ValidateDocumentCompilationResult
{
    public function __construct(private ContractSchemaRegistry $schemas = new ContractSchemaRegistry) {}

    public function handle(DocumentCompilationResult $result): DocumentCompilationResult
    {
        $payload = json_decode(json_encode($result->toArray(), JSON_THROW_ON_ERROR), false, flags: JSON_THROW_ON_ERROR);
        if (! $payload instanceof stdClass) {
            throw new LogicException('Document compilation result did not serialize to an object.');
        }
        $validation = $this->schemas->validator()->validate($payload, ContractSchemaRegistry::Result);
        if (! $validation->isValid()) {
            $error = $validation->error();
            if ($error === null) {
                throw new LogicException('Opis reported an invalid result without a validation error.');
            }
            $errors = (new ErrorFormatter)->format($error);
            throw new InvalidDocumentCompilationResult('Generated x-document result violates contract 1.0: '.json_encode($errors, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        }

        return $result;
    }
}
