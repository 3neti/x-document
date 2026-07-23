<?php

namespace LBHurtado\XDocument\Contract;

use Opis\JsonSchema\Resolvers\SchemaResolver;
use Opis\JsonSchema\Validator;

final readonly class ContractSchemaRegistry
{
    public const Request = 'https://3neti.dev/contracts/x-document/1.0/compilation-request.schema.json';

    public const ResolvedDocument = 'https://3neti.dev/contracts/x-document/1.0/resolved-document.schema.json';

    public const Result = 'https://3neti.dev/contracts/x-document/1.0/compilation-result.schema.json';

    /**
     * @return array<string, string>
     */
    public function schemas(): array
    {
        $contractPath = dirname(__DIR__, 2).'/resources/contracts/x-document/1.0';

        return [
            self::Request => $contractPath.'/compilation-request.schema.json',
            self::ResolvedDocument => $contractPath.'/resolved-document.schema.json',
            self::Result => $contractPath.'/compilation-result.schema.json',
        ];
    }

    public function validator(): Validator
    {
        $resolver = new SchemaResolver;
        foreach ($this->schemas() as $schemaId => $schemaPath) {
            $resolver->registerFile($schemaId, $schemaPath);
        }

        return new Validator(max_errors: 20, stop_at_first_error: false)->setResolver($resolver);
    }
}
