<?php

declare(strict_types=1);

namespace Linkedcode\Middleware\Problem\Serializer;

final class ProblemSerializerFactory
{
    public function create(string $format): ProblemSerializerInterface
    {
        return match ($format) {
            'xml'   => new XmlProblemSerializer(),
            default => new JsonProblemSerializer(),
        };
    }
}
