<?php

namespace LBHurtado\XDocument\Contract;

enum DocumentCompilationStatus: string
{
    case Succeeded = 'succeeded';
    case Unsupported = 'unsupported';
    case Failed = 'failed';
}
