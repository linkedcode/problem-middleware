<?php

declare(strict_types=1);

namespace Linkedcode\Middleware\Problem\Exception;

final class ForbiddenException extends ProblemException
{
    protected int $status = 403;
    protected string $title = 'Forbidden';
}
