<?php

declare(strict_types=1);

namespace Linkedcode\Middleware\Problem;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ProblemResponseFactory
{
    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly ProblemNormalizer $normalizer = new ProblemNormalizer(),
        private readonly ContentNegotiator $negotiator = new ContentNegotiator(),
    ) {}

    public function create(ProblemInterface $problem, ServerRequestInterface $request): ResponseInterface
    {
        $serializer = $this->negotiator->negotiate($request);

        $response = $this->responseFactory->createResponse($problem->getStatus());
        $response->getBody()->write($serializer->serialize($this->normalizer->normalize($problem)));

        return $response->withHeader('Content-Type', $serializer->contentType());
    }
}
