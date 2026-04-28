<?php

declare(strict_types=1);

namespace Linkedcode\Middleware\Problem\Tests;

use PHPUnit\Framework\TestCase;
use Linkedcode\Middleware\Problem\ValidationError;

final class ValidationErrorTest extends TestCase
{
    public function testGetPointer(): void
    {
        $error = new ValidationError('/email', 'Invalid email');
        $this->assertSame('/email', $error->getPointer());
    }

    public function testGetDetail(): void
    {
        $error = new ValidationError('/email', 'Invalid email');
        $this->assertSame('Invalid email', $error->getDetail());
    }

    public function testToArray(): void
    {
        $error = new ValidationError('/name', 'Name is required');
        $this->assertSame([
            'pointer' => '/name',
            'detail'  => 'Name is required',
        ], $error->toArray());
    }
}
