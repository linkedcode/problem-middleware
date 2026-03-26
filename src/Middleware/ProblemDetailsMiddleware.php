<?php

declare(strict_types=1);

namespace Linkedcode\Middleware\Problem\Middleware;

use Throwable;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Linkedcode\Middleware\Problem\Mapper\ExceptionMapperInterface;
use Linkedcode\Middleware\Problem\ProblemResponseFactory;

final class ProblemDetailsMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly ExceptionMapperInterface $mapper,
        private readonly ProblemResponseFactory $responseFactory
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (Throwable $e) {
            $problem = $this->mapper->map($e);
            return $this->responseFactory->create($problem);
        }
    }
}
