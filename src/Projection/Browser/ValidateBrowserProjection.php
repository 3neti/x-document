<?php

namespace LBHurtado\XDocument\Projection\Browser;

use LBHurtado\XDocument\Exceptions\InvalidBrowserProjection;
use LogicException;
use Opis\JsonSchema\Errors\ErrorFormatter;
use stdClass;

final readonly class ValidateBrowserProjection
{
    public function __construct(private BrowserProjectionSchemaRegistry $schemas = new BrowserProjectionSchemaRegistry) {}

    public function handle(BrowserProjection $projection): BrowserProjection
    {
        $payload = json_decode(json_encode($projection->toArray(), JSON_THROW_ON_ERROR), false, flags: JSON_THROW_ON_ERROR);
        if (! $payload instanceof stdClass) {
            throw new LogicException('Browser projection did not serialize to an object.');
        }
        $validation = $this->schemas->validator()->validate($payload, BrowserProjectionSchemaRegistry::Projection);
        if (! $validation->isValid()) {
            $error = $validation->error();
            if ($error === null) {
                throw new LogicException('Opis reported an invalid browser projection without an error.');
            }
            throw new InvalidBrowserProjection('Generated browser projection violates browser/1.0: '.json_encode(
                (new ErrorFormatter)->format($error),
                JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
            ));
        }

        return $projection;
    }
}
