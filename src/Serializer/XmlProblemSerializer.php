<?php

declare(strict_types=1);

namespace Linkedcode\Middleware\Problem\Serializer;

use SimpleXMLElement;

final class XmlProblemSerializer implements ProblemSerializerInterface
{
    public function serialize(array $data): string
    {
        $xml = new SimpleXMLElement('<problem xmlns="urn:ietf:rfc:7807"/>');

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $this->appendArray($xml, $key, $value);
            } else {
                $xml->addChild($key, htmlspecialchars((string) $value));
            }
        }

        return $xml->asXML() ?: '<problem xmlns="urn:ietf:rfc:7807"/>';
    }

    public function contentType(): string
    {
        return 'application/problem+xml';
    }

    private function appendArray(SimpleXMLElement $parent, string $name, array $items): void
    {
        $node = $parent->addChild($name);
        foreach ($items as $key => $value) {
            if (is_array($value)) {
                $this->appendArray($node, is_int($key) ? 'item' : $key, $value);
            } else {
                $node->addChild(is_int($key) ? 'item' : $key, htmlspecialchars((string) $value));
            }
        }
    }
}
