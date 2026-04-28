<?php

declare(strict_types=1);

namespace Linkedcode\Middleware\Problem\Integration;

use Throwable;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Linkedcode\Middleware\Problem\Mapper\ExceptionMapperInterface;
use Linkedcode\Middleware\Problem\Problem;
use Linkedcode\Middleware\Problem\ProblemResponseFactory;

final class SlimErrorHandler
{
    public function __construct(
        private readonly ExceptionMapperInterface $mapper,
        private readonly ProblemResponseFactory $responseFactory,
        private readonly bool $displayErrorDetails = false
    ) {}

    public function __invoke(
        ServerRequestInterface $request,
        Throwable $exception,
        bool $displayErrorDetails
    ): ResponseInterface {
        $problem = $this->mapper->map($exception);

        if ($displayErrorDetails) {
            $extensions = $problem->getExtensions();
            $extensions['trace'] = $exception->getTraceAsString();

            $problem = new Problem(
                $problem->getType(),
                $problem->getTitle(),
                $problem->getStatus(),
                $problem->getDetail(),
                $problem->getInstance(),
                $extensions
            );
        }

        return $this->responseFactory->create($problem, $request);
    }
}
