<?php

declare(strict_types=1);

namespace Linkedcode\Middleware\Problem;

final class ValidationError
{
    public function __construct(
        private readonly string $pointer,
        private readonly string $detail,
    ) {}

    public function getPointer(): string
    {
        return $this->pointer;
    }

    public function getDetail(): string
    {
        return $this->detail;
    }

    public function toArray(): array
    {
        return [
            'pointer' => $this->pointer,
            'detail' => $this->detail,
        ];
    }
}
