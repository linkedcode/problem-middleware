<?php

declare(strict_types=1);

namespace Linkedcode\Middleware\Problem\Tests\Exception;

use PHPUnit\Framework\TestCase;
use Linkedcode\Middleware\Problem\Exception\ValidationException;
use Linkedcode\Middleware\Problem\ProblemNormalizer;
use Linkedcode\Middleware\Problem\Serializer\JsonProblemSerializer;
use Linkedcode\Middleware\Problem\Serializer\XmlProblemSerializer;
use Linkedcode\Middleware\Problem\ValidationError;

final class ValidationExceptionTest extends TestCase
{
    private function makeException(): ValidationException
    {
        return new ValidationException([
            new ValidationError('/name', 'Name is required'),
            new ValidationError('/email', 'Invalid email format'),
        ]);
    }

    public function testStatus(): void
    {
        $this->assertSame(422, $this->makeException()->toProblem()->getStatus());
    }

    public function testTitle(): void
    {
        $this->assertSame('Validation Error', $this->makeException()->toProblem()->getTitle());
    }

    public function testErrorCount(): void
    {
        $extensions = $this->makeException()->toProblem()->getExtensions();
        $this->assertCount(2, $extensions['errors']);
    }

    public function testErrorFields(): void
    {
        $errors = $this->makeException()->toProblem()->getExtensions()['errors'];

        $this->assertSame('/name', $errors[0]['pointer']);
        $this->assertSame('Name is required', $errors[0]['detail']);
        $this->assertSame('/email', $errors[1]['pointer']);
        $this->assertSame('Invalid email format', $errors[1]['detail']);
    }

    public function testSerializesToJson(): void
    {
        $problem = $this->makeException()->toProblem();
        $data = (new ProblemNormalizer())->normalize($problem);
        $json = json_decode((new JsonProblemSerializer())->serialize($data), true);

        $this->assertSame(422, $json['status']);
        $this->assertCount(2, $json['errors']);
        $this->assertSame('/name', $json['errors'][0]['pointer']);
    }

    public function testSerializesToXml(): void
    {
        $problem = $this->makeException()->toProblem();
        $data = (new ProblemNormalizer())->normalize($problem);
        $xml = new \SimpleXMLElement((new XmlProblemSerializer())->serialize($data));

        $this->assertSame('422', (string) $xml->status);
        $this->assertSame('/name', (string) $xml->errors->item->pointer);
        $this->assertSame('Invalid email format', (string) $xml->errors->item[1]->detail);
    }
}
