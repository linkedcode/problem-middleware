# linkedcode/problem-middleware

Middleware y utilidades para responder errores HTTP con formato
[RFC 7807 / Problem Details](https://datatracker.ietf.org/doc/html/rfc7807)
en aplicaciones PHP (Slim 4).

## Requisitos

- PHP `^8.1`
- Slim `^4.0`

## Instalacion

```bash
composer require linkedcode/problem-middleware
```

## Uso con Slim 4

```php
<?php

declare(strict_types=1);

use Slim\Factory\AppFactory;
use Linkedcode\Middleware\Problem\Mapper\DefaultExceptionMapper;
use Linkedcode\Middleware\Problem\ProblemResponseFactory;
use Linkedcode\Middleware\Problem\Integration\SlimErrorHandler;

$app = AppFactory::create();

$responseFactory = new ProblemResponseFactory($app->getResponseFactory());
$mapper = new DefaultExceptionMapper();

$errorMiddleware = $app->addErrorMiddleware(true, true, true);
$errorMiddleware->setDefaultErrorHandler(
    new SlimErrorHandler($mapper, $responseFactory, true)
);
```

## Excepciones incluidas

- `NotFoundException` -> status `404`
- `ValidationException` -> status `422` + extension para campos invalidos
- `HttpException` -> status configurable
- `ResourceConflictException` -> status `409`

Todas extienden `ProblemException` y se serializan automaticamente como
`application/problem+json`.

## Lanzar excepciones de dominio

### Usar las excepciones incluidas

Desde cualquier lugar de la aplicacion (handler, servicio, repositorio) basta con
lanzar la excepcion correspondiente; el middleware la captura y genera la respuesta
`application/problem+json` automaticamente:

```php
use Linkedcode\Middleware\Problem\Exception\NotFoundException;
use Linkedcode\Middleware\Problem\Exception\ValidationException;
use Linkedcode\Middleware\Problem\Exception\ResourceConflictException;
use Linkedcode\Middleware\Problem\ValidationError;

// 404
throw new NotFoundException('Usuario no encontrado');

// 409
throw new ResourceConflictException('Ya existe un recurso con ese identificador');

// 422 con detalle por campo
throw new ValidationException([
    new ValidationError('must be a positive integer', '#/age'),
    new ValidationError("must be 'green', 'red' or 'blue'", '#/profile/color'),
]);
```

### Crear excepciones de dominio propias

**Opcion 1 — extender `ProblemException`** (recomendada para excepciones de
infraestructura o aplicacion que ya conocen el status HTTP):

```php
use Linkedcode\Middleware\Problem\Exception\ProblemException;

final class PaymentRequiredException extends ProblemException
{
    protected int    $status = 402;
    protected string $title  = 'Payment Required';
    protected string $type   = 'https://example.com/problems/payment-required';
}

// uso
throw new PaymentRequiredException('La suscripcion ha vencido');
```

**Opcion 2 — implementar `DomainExceptionInterface`** (recomendada para excepciones
de dominio puro que no deben depender de conceptos HTTP):

```php
use Linkedcode\Middleware\Problem\Exception\DomainExceptionInterface;

final class InsufficientStockException extends \RuntimeException
    implements DomainExceptionInterface
{
    public function getHttpStatus(): int     { return 422; }
    public function getProblemType(): string  { return 'https://example.com/problems/insufficient-stock'; }
    public function getProblemTitle(): string { return 'Insufficient Stock'; }
}

// uso
throw new InsufficientStockException('Solo quedan 3 unidades en stock');
```

El `DefaultExceptionMapper` detecta la interfaz automaticamente; no hace falta
ningun mapper personalizado.

## Respuesta de ejemplo

```json
{
  "type": "about:blank",
  "title": "Validation Error",
  "status": 422,
  "detail": "Validation failed",
  "errors": [
    {
      "detail": "must be a positive integer",
      "pointer": "#/age"
    },
    {
      "detail": "must be 'green', 'red' or 'blue'",
      "pointer": "#/profile/color"
    }
  ]
}
```
