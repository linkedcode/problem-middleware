<?php

declare(strict_types=1);

namespace Linkedcode\Middleware\Problem\Exception;

use Linkedcode\Middleware\Problem\ValidationError;

final class ValidationException extends ProblemException
{
    protected int $status = 422;
    protected string $title = 'Validation Error';

    /**
     * @param ValidationError[] $errors
     */
    public function __construct(array $errors)
    {
        parent::__construct('Validation failed');
        $this->extensions['errors'] = array_map(
            static fn(ValidationError $error): array => $error->toArray(),
            $errors
        );
    }
}
