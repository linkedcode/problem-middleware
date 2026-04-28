<?php

declare(strict_types=1);

namespace Linkedcode\Middleware\Problem\Exception;

use Throwable;

interface DomainExceptionInterface extends Throwable
{
    public function getHttpStatus(): int;

    public function getProblemType(): string;

    public function getProblemTitle(): string;
}
