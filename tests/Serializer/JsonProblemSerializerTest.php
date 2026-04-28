<?php

declare(strict_types=1);

namespace Linkedcode\Middleware\Problem\Tests\Serializer;

use PHPUnit\Framework\TestCase;
use Linkedcode\Middleware\Problem\Serializer\JsonProblemSerializer;

final class JsonProblemSerializerTest extends TestCase
{
    private JsonProblemSerializer $serializer;

    protected function setUp(): void
    {
        $this->serializer = new JsonProblemSerializer();
    }

    public function testContentType(): void
    {
        $this->assertSame('application/problem+json', $this->serializer->contentType());
    }

    public function testSerializesData(): void
    {
        $data = ['type' => 'about:blank', 'title' => 'Not Found', 'status' => 404];
        $result = $this->serializer->serialize($data);

        $decoded = json_decode($result, true);
        $this->assertSame($data, $decoded);
    }

    public function testSerializesUnicode(): void
    {
        $data = ['detail' => 'Error de ação'];
        $result = $this->serializer->serialize($data);

        $this->assertStringContainsString('Error de ação', $result);
    }
}
