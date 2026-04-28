<?php

declare(strict_types=1);

namespace Linkedcode\Middleware\Problem\Tests\Mapper;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Linkedcode\Middleware\Problem\Mapper\DefaultExceptionMapper;
use Linkedcode\Middleware\Problem\Exception\NotFoundException;
use Linkedcode\Middleware\Problem\Exception\ValidationException;
use Linkedcode\Middleware\Problem\ValidationError;

final class DefaultExceptionMapperTest extends TestCase
{
    private DefaultExceptionMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new DefaultExceptionMapper();
    }

    public function testMapsProblemException(): void
    {
        $e = new NotFoundException('Not found');
        $problem = $this->mapper->map($e);

        $this->assertSame(404, $problem->getStatus());
        $this->assertSame('Resource Not Found', $problem->getTitle());
    }

    public function testMapsValidationException(): void
    {
        $e = new ValidationException([new ValidationError('/email', 'Invalid')]);
        $problem = $this->mapper->map($e);

        $this->assertSame(422, $problem->getStatus());
        $this->assertArrayHasKey('errors', $problem->getExtensions());
    }

    public function testMapsGenericExceptionTo500(): void
    {
        $e = new RuntimeException('Something broke');
        $problem = $this->mapper->map($e);

        $this->assertSame(500, $problem->getStatus());
        $this->assertSame('Internal Server Error', $problem->getTitle());
        $this->assertSame('Something broke', $problem->getDetail());
    }

    public function testMaps401Exception(): void
    {
        $e = new RuntimeException('Unauthorized', 401);
        $problem = $this->mapper->map($e);

        $this->assertSame(401, $problem->getStatus());
        $this->assertSame('Unauthorized', $problem->getTitle());
    }

    public function testMaps403Exception(): void
    {
        $e = new RuntimeException('Forbidden', 403);
        $problem = $this->mapper->map($e);

        $this->assertSame(403, $problem->getStatus());
    }
}
