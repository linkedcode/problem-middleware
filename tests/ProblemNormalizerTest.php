<?php

declare(strict_types=1);

namespace Linkedcode\Middleware\Problem\Tests;

use PHPUnit\Framework\TestCase;
use Linkedcode\Middleware\Problem\Problem;
use Linkedcode\Middleware\Problem\ProblemNormalizer;

final class ProblemNormalizerTest extends TestCase
{
    private ProblemNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new ProblemNormalizer();
    }

    public function testNormalizesRequiredFields(): void
    {
        $problem = new Problem('about:blank', 'Not Found', 404);
        $data = $this->normalizer->normalize($problem);

        $this->assertSame('about:blank', $data['type']);
        $this->assertSame('Not Found', $data['title']);
        $this->assertSame(404, $data['status']);
    }

    public function testOmitsNullOptionalFields(): void
    {
        $problem = new Problem('about:blank', 'Not Found', 404);
        $data = $this->normalizer->normalize($problem);

        $this->assertArrayNotHasKey('detail', $data);
        $this->assertArrayNotHasKey('instance', $data);
    }

    public function testIncludesOptionalFields(): void
    {
        $problem = new Problem('about:blank', 'Not Found', 404, 'Resource missing', '/requests/1');
        $data = $this->normalizer->normalize($problem);

        $this->assertSame('Resource missing', $data['detail']);
        $this->assertSame('/requests/1', $data['instance']);
    }

    public function testMergesExtensions(): void
    {
        $problem = new Problem('about:blank', 'Error', 400, extensions: ['foo' => 'bar']);
        $data = $this->normalizer->normalize($problem);

        $this->assertSame('bar', $data['foo']);
    }
}
