<?php

declare(strict_types=1);

namespace Linkedcode\Middleware\Problem;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

final class ProblemResponseFactory
{
    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly ProblemNormalizer $normalizer = new ProblemNormalizer()
    ) {}

    public function create(ProblemInterface $problem): ResponseInterface
    {
        $response = $this->responseFactory->createResponse($problem->getStatus());

        $payload = json_encode(
            $this->normalizer->normalize($problem),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        $response->getBody()->write($payload ?: '{}');

        return $response->withHeader('Content-Type', 'application/problem+json');
    }
}
