<?php

namespace LBHurtado\XDocument\Projection\Browser;

use Opis\JsonSchema\Resolvers\SchemaResolver;
use Opis\JsonSchema\Validator;

final readonly class BrowserProjectionSchemaRegistry
{
    public const Projection = 'https://3neti.dev/projections/x-document/browser/1.0/browser-projection.schema.json';

    public function validator(): Validator
    {
        $resolver = new SchemaResolver;
        $resolver->registerFile(
            self::Projection,
            dirname(__DIR__, 3).'/resources/projections/browser/1.0/browser-projection.schema.json',
        );

        return new Validator(max_errors: 20, stop_at_first_error: false)->setResolver($resolver);
    }
}
