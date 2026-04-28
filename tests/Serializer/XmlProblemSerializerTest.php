<?php

declare(strict_types=1);

namespace Linkedcode\Middleware\Problem\Tests\Serializer;

use PHPUnit\Framework\TestCase;
use Linkedcode\Middleware\Problem\Serializer\XmlProblemSerializer;

final class XmlProblemSerializerTest extends TestCase
{
    private XmlProblemSerializer $serializer;

    protected function setUp(): void
    {
        $this->serializer = new XmlProblemSerializer();
    }

    public function testContentType(): void
    {
        $this->assertSame('application/problem+xml', $this->serializer->contentType());
    }

    public function testSerializesScalarFields(): void
    {
        $data = ['type' => 'about:blank', 'title' => 'Not Found', 'status' => 404];
        $result = $this->serializer->serialize($data);

        $xml = new \SimpleXMLElement($result);
        $this->assertSame('about:blank', (string) $xml->type);
        $this->assertSame('Not Found', (string) $xml->title);
        $this->assertSame('404', (string) $xml->status);
    }

    public function testNamespace(): void
    {
        $result = $this->serializer->serialize(['status' => 404]);
        $this->assertStringContainsString('urn:ietf:rfc:7807', $result);
    }

    public function testSerializesNestedArray(): void
    {
        $data = ['errors' => [['field' => 'email', 'message' => 'Invalid']]];
        $result = $this->serializer->serialize($data);

        $xml = new \SimpleXMLElement($result);
        $this->assertSame('email', (string) $xml->errors->item->field);
        $this->assertSame('Invalid', (string) $xml->errors->item->message);
    }
}
