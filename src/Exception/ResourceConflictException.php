<?php

declare(strict_types=1);

namespace Linkedcode\Middleware\Problem\Exception;

final class ResourceConflictException extends ProblemException
{
    protected int $status = 409;
    protected string $title = 'Conflict';
}
