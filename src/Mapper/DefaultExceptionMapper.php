<?php

declare(strict_types=1);

namespace Linkedcode\Middleware\Problem\Mapper;

use Linkedcode\Middleware\Problem\Exception\ProblemException;
use Linkedcode\Middleware\Problem\Problem;
use Linkedcode\Middleware\Problem\ProblemInterface;
use Throwable;

/**
 * Mapper base: traduce a RFC 9457 todo lo que este paquete puede reconocer por
 * sí solo, y deja al host sólo lo que es propio de su aplicación.
 *
 * Cubre cuatro familias, en orden:
 *
 *  1. ProblemException — las excepciones de este paquete, que ya traen status.
 *  2. Las excepciones de auth-middleware (401 / 403).
 *  3. Las interfaces de excepción del kernel (linkedcode/ddd).
 *  4. InvalidArgumentException y los errores de tipo o valor de PHP → 422, y
 *     cualquier otra cosa → 500.
 *
 * Los grupos 2 y 3 se reconocen por nombre para no acoplar este middleware a
 * esos paquetes: quien no los tenga instalados simplemente nunca hace match.
 *
 * El host extiende esta clase y sobreescribe map() para sus propios casos
 * (401 de auth, 502 de un servicio externo), delegando el resto en parent.
 */
class DefaultExceptionMapper implements ExceptionMapperInterface
{
    /**
     * Detail de los 422 por tipo o valor inválido. Es fijo porque el mensaje
     * original de un TypeError expone la firma del método y la ruta del archivo
     * que lo llamó. Protegido para que un host que sobreescriba este caso pueda
     * reusar el mismo texto en vez de inventar otro.
     */
    protected const MALFORMED_INPUT_DETAIL =
        'Some of the submitted data has an invalid type or value.';

    /**
     * Interfaz del kernel => [status, title].
     *
     * Se listan por FQCN en string a propósito: `instanceof` con un nombre de
     * clase que no existe devuelve false en lugar de fallar, así que este
     * paquete no necesita depender de linkedcode/ddd.
     *
     * El orden importa: ValidationException va primero porque es la única que
     * aporta errores por campo.
     */
    private const DOMAIN_EXCEPTIONS = [
        'Linkedcode\DDD\Domain\Exception\ValidationException' => [422, 'Unprocessable Entity'],
        'Linkedcode\DDD\Domain\Exception\NotFoundException'   => [404, 'Not Found'],
        'Linkedcode\DDD\Domain\Exception\ForbiddenException'  => [403, 'Forbidden'],
        'Linkedcode\DDD\Domain\Exception\ConflictException'   => [409, 'Conflict'],
    ];

    /**
     * Excepciones de auth-middleware => [status, title].
     *
     * Se listan por FQCN en string por el mismo motivo que las del kernel: este
     * paquete no depende de auth-middleware. Traen su status en getCode(), pero
     * se fija acá para no confiar en que el host no lo haya cambiado al lanzarlas.
     */
    private const AUTH_EXCEPTIONS = [
        'Linkedcode\Middleware\Auth\Exception\UnauthorizedException' => [401, 'Unauthorized'],
        'Linkedcode\Middleware\Auth\Exception\ForbiddenException'    => [403, 'Forbidden'],
    ];

    public function map(Throwable $e): ProblemInterface
    {
        // Las excepciones de este paquete ya saben su status.
        if ($e instanceof ProblemException) {
            return $e->toProblem();
        }

        foreach (self::AUTH_EXCEPTIONS as $class => [$status, $title]) {
            if ($e instanceof $class) {
                return new Problem('about:blank', $title, $status, $e->getMessage());
            }
        }

        foreach (self::DOMAIN_EXCEPTIONS as $interface => [$status, $title]) {
            if (!$e instanceof $interface) {
                continue;
            }

            return new Problem(
                type: 'about:blank',
                title: $title,
                status: $status,
                detail: $e->getMessage(),
                extensions: $this->extensionsFor($e),
            );
        }

        if ($e instanceof \InvalidArgumentException) {
            return new Problem('about:blank', 'Unprocessable Entity', 422, $e->getMessage());
        }

        // TypeError y ValueError heredan de Error, no de Exception, así que no
        // los alcanza ningún brazo anterior y caerían en el 500. Son lo que PHP
        // lanza cuando un dato llega con el tipo equivocado al borde tipado del
        // dominio —un método que declara int, un enum::from() con un valor que
        // no es caso—: bajo strict_types eso es culpa del payload y no del
        // servidor, así que corresponde 422.
        //
        // No se captura Error entero a propósito: ArithmeticError,
        // DivisionByZeroError y UnhandledMatchError son bugs de la aplicación,
        // no datos mal formados, y tienen que seguir saliendo como 500.
        //
        // El detail es fijo y no reenvía getMessage(): el mensaje de un
        // TypeError nombra la firma del método y la ruta del archivo que lo
        // llamó, y ProblemDetailsMiddleware sólo limpia los 5xx. Reenviarlo en
        // un 422 filtraría internals por la puerta que el scrubbing deja abierta.
        if ($e instanceof \TypeError || $e instanceof \ValueError) {
            return new Problem(
                'about:blank',
                'Unprocessable Entity',
                422,
                self::MALFORMED_INPUT_DETAIL,
            );
        }

        return new Problem('about:blank', 'Internal Server Error', 500, $e->getMessage());
    }

    /**
     * ValidationException del kernel expone errors(); el resto no aporta
     * extensiones. El shape de errors() lo decide cada implementación, así que
     * se reenvía tal cual.
     *
     * @return array<string, mixed>
     */
    private function extensionsFor(Throwable $e): array
    {
        if (!method_exists($e, 'errors')) {
            return [];
        }

        $errors = $e->errors();

        return $errors === [] ? [] : ['errors' => $errors];
    }
}
