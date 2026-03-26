<?php

declare(strict_types=1);

namespace Linkedcode\Middleware\Problem\Exception;

final class NotFoundException extends ProblemException
{
    protected int $status = 404;
    protected string $title = 'Resource Not Found';
}
