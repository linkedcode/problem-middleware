<?php

declare(strict_types=1);

namespace Linkedcode\Middleware\Problem\Mapper;

use Throwable;
use Linkedcode\Middleware\Problem\ProblemInterface;

interface ExceptionMapperInterface
{
    public function map(Throwable $e): ProblemInterface;
}
