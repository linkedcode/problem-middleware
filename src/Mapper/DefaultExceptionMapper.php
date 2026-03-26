<?php

declare(strict_types=1);

namespace Linkedcode\Middleware\Problem\Mapper;

use Throwable;
use Slim\Exception\HttpException as SlimHttpException;
use Linkedcode\Middleware\Problem\Problem;
use Linkedcode\Middleware\Problem\ProblemInterface;
use Linkedcode\Middleware\Problem\Exception\ProblemException;

final class DefaultExceptionMapper implements ExceptionMapperInterface
{
    public function map(Throwable $e): ProblemInterface
    {
        if ($e instanceof ProblemException) {
            return $e->toProblem();
        }

        if ($e instanceof SlimHttpException) {
            return new Problem(
                type: 'about:blank',
                title: $e->getMessage() ?: 'HTTP Error',
                status: $e->getCode() ?: 500
            );
        }

        return new Problem(
            type: 'about:blank',
            title: 'Internal Server Error',
            status: 500,
            detail: $e->getMessage()
        );
    }
}
