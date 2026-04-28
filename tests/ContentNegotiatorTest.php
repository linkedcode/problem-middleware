<?php

declare(strict_types=1);

namespace Linkedcode\Middleware\Problem\Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\ServerRequestInterface;
use Linkedcode\Middleware\Problem\ContentNegotiator;
use Linkedcode\Middleware\Problem\Serializer\JsonProblemSerializer;
use Linkedcode\Middleware\Problem\Serializer\XmlProblemSerializer;

final class ContentNegotiatorTest extends TestCase
{
    private ContentNegotiator $negotiator;

    protected function setUp(): void
    {
        $this->negotiator = new ContentNegotiator();
    }

    private function requestWithAccept(string $accept): ServerRequestInterface
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getHeaderLine')
            ->with('Accept')
            ->willReturn($accept);

        return $request;
    }

    public function testDefaultsToJsonWithNoAcceptHeader(): void
    {
        $serializer = $this->negotiator->negotiate($this->requestWithAccept(''));
        $this->assertInstanceOf(JsonProblemSerializer::class, $serializer);
    }

    #[DataProvider('jsonMediaTypes')]
    public function testNegotiatesJson(string $mediaType): void
    {
        $serializer = $this->negotiator->negotiate($this->requestWithAccept($mediaType));
        $this->assertInstanceOf(JsonProblemSerializer::class, $serializer);
    }

    public static function jsonMediaTypes(): array
    {
        return [
            ['application/json'],
        ];
    }

    #[DataProvider('xmlMediaTypes')]
    public function testNegotiatesXml(string $mediaType): void
    {
        $serializer = $this->negotiator->negotiate($this->requestWithAccept($mediaType));
        $this->assertInstanceOf(XmlProblemSerializer::class, $serializer);
    }

    public static function xmlMediaTypes(): array
    {
        return [
            ['application/xml'],
            ['text/xml'],
        ];
    }

    public function testFallsBackToJsonForUnknownMediaType(): void
    {
        $serializer = $this->negotiator->negotiate($this->requestWithAccept('text/html'));
        $this->assertInstanceOf(JsonProblemSerializer::class, $serializer);
    }
}
