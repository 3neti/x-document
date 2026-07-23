<?php

namespace LBHurtado\XDocument\Contracts;

use LBHurtado\XDocument\Contract\DocumentCompilationRequest;
use LBHurtado\XDocument\Contract\DocumentCompilationResult;

interface DocumentDriver
{
    public function name(): string;

    public function compile(DocumentCompilationRequest $request): DocumentCompilationResult;
}
