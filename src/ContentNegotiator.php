<?php

declare(strict_types=1);

namespace Linkedcode\Middleware\Problem;

use Psr\Http\Message\ServerRequestInterface;
use Linkedcode\Middleware\Problem\Serializer\ProblemSerializerFactory;
use Linkedcode\Middleware\Problem\Serializer\ProblemSerializerInterface;

final class ContentNegotiator
{
    private const MEDIA_TYPES = [
        'application/xml'  => 'xml',
        'text/xml'         => 'xml',
        'application/json' => 'json',
    ];

    public function __construct(
        private readonly ProblemSerializerFactory $factory = new ProblemSerializerFactory(),
    ) {}

    public function negotiate(ServerRequestInterface $request): ProblemSerializerInterface
    {
        $accept = $request->getHeaderLine('Accept');

        foreach (self::MEDIA_TYPES as $mediaType => $format) {
            if (str_contains($accept, $mediaType)) {
                return $this->factory->create($format);
            }
        }

        return $this->factory->create('json');
    }
}
