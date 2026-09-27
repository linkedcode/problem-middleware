<?php

declare(strict_types=1);

namespace Linkedcode\Middleware\Problem\Tests\Mapper;

use Linkedcode\Middleware\Problem\Exception\HttpException;
use Linkedcode\Middleware\Problem\Exception\NotFoundException as PackageNotFoundException;
use Linkedcode\Middleware\Problem\Mapper\DefaultExceptionMapper;
use Linkedcode\Middleware\Problem\Problem;
use Linkedcode\Middleware\Problem\ProblemInterface;
use PHPUnit\Framework\TestCase;
use Throwable;

final class DefaultExceptionMapperTest extends TestCase
{
    private DefaultExceptionMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new DefaultExceptionMapper();
    }

    public function test_package_exceptions_keep_their_own_status(): void
    {
        self::assertSame(404, $this->mapper->map(new PackageNotFoundException('no está'))->getStatus());
        self::assertSame(418, $this->mapper->map(new HttpException(418, 'soy una tetera'))->getStatus());
    }

    public function test_maps_auth_exceptions_to_401_and_403(): void
    {
        // auth-middleware lanza estas desde ApiStrategy; sin reconocerlas, una
        // request sin credenciales devolvía 500 en vez de 401.
        self::assertSame(401, $this->mapper->map(AuthExceptions::unauthorized('sin token'))->getStatus());
        self::assertSame('Unauthorized', $this->mapper->map(AuthExceptions::unauthorized())->getTitle());
        self::assertSame(403, $this->mapper->map(AuthExceptions::forbidden())->getStatus());
        self::assertSame('Forbidden', $this->mapper->map(AuthExceptions::forbidden())->getTitle());
    }

    public function test_maps_slim_oauth_exceptions(): void
    {
        // Sin reconocerlas, /oauth/login/{provider-inexistente} y un state
        // inválido en el callback devolvían 500.
        self::assertSame(404, $this->mapper->map(new \Linkedcode\SlimOAuth\Exception\ProviderNotFoundException('x'))->getStatus());
        self::assertSame(400, $this->mapper->map(new \Linkedcode\SlimOAuth\Exception\InvalidOAuthStateException('x'))->getStatus());
        self::assertSame(401, $this->mapper->map(new \Linkedcode\SlimOAuth\Exception\InvalidRefreshTokenException('x'))->getStatus());
        self::assertSame(502, $this->mapper->map(new \Linkedcode\SlimOAuth\Exception\TokenExchangeException('x'))->getStatus());
        self::assertSame(400, $this->mapper->map(new \Linkedcode\SlimOAuth\Exception\OAuthException('x'))->getStatus());
    }

    public function test_maps_kernel_not_found_to_404(): void
    {
        $problem = $this->mapper->map(KernelExceptions::notFound('address no existe'));

        self::assertSame(404, $problem->getStatus());
        self::assertSame('Not Found', $problem->getTitle());
        self::assertSame('address no existe', $problem->getDetail());
    }

    public function test_maps_kernel_forbidden_to_403(): void
    {
        self::assertSame(403, $this->mapper->map(KernelExceptions::forbidden())->getStatus());
    }

    public function test_maps_kernel_conflict_to_409(): void
    {
        self::assertSame(409, $this->mapper->map(KernelExceptions::conflict())->getStatus());
    }

    public function test_maps_kernel_validation_to_422_with_field_errors(): void
    {
        $problem = $this->mapper->map(KernelExceptions::validation(['street' => 'no puede estar vacía']));

        self::assertSame(422, $problem->getStatus());
        self::assertSame(['errors' => ['street' => 'no puede estar vacía']], $problem->getExtensions());
    }

    public function test_validation_without_errors_adds_no_extensions(): void
    {
        self::assertSame([], $this->mapper->map(KernelExceptions::validation([]))->getExtensions());
    }

    public function test_maps_invalid_argument_to_422(): void
    {
        self::assertSame(422, $this->mapper->map(new \InvalidArgumentException('mal'))->getStatus());
    }

    public function test_falls_back_to_500(): void
    {
        self::assertSame(500, $this->mapper->map(new \RuntimeException('boom'))->getStatus());
    }

    public function test_maps_type_error_to_422(): void
    {
        $e = new \TypeError('Money::of(): Argument #1 ($amount) must be of type int, string given');

        self::assertSame(422, $this->mapper->map($e)->getStatus());
    }

    public function test_maps_value_error_to_422(): void
    {
        $e = new \ValueError('"XXX" is not a valid backing value for enum Currency');

        self::assertSame(422, $this->mapper->map($e)->getStatus());
    }

    /**
     * El mensaje de un TypeError nombra la firma del método y la ruta del
     * archivo que lo llamó. ProblemDetailsMiddleware sólo limpia los 5xx, así
     * que un 422 que reenviara getMessage() sacaría eso al cliente.
     */
    public function test_type_error_detail_does_not_leak_internals(): void
    {
        $e = new \TypeError(
            'Money::of(): Argument #1 must be of type int, string given, called in /var/www/app/src/X.php on line 42'
        );

        $detail = (string) $this->mapper->map($e)->getDetail();

        self::assertStringNotContainsString('/var/www', $detail);
        self::assertStringNotContainsString('Money::of', $detail);
    }

    /**
     * Error es el padre común, pero sólo TypeError y ValueError hablan del dato
     * que entró: el resto son bugs de la aplicación y siguen siendo 500.
     */
    public function test_other_php_errors_remain_500(): void
    {
        self::assertSame(500, $this->mapper->map(new \DivisionByZeroError('Division by zero'))->getStatus());
        self::assertSame(500, $this->mapper->map(new \ArithmeticError('overflow'))->getStatus());
        self::assertSame(500, $this->mapper->map(new \UnhandledMatchError('unhandled'))->getStatus());
    }

    /**
     * El host hereda para lo suyo y delega el resto: es el uso previsto de esta
     * clase, y lo que evita que cada app se olvide de las interfaces del kernel.
     */
    public function test_a_host_mapper_can_extend_and_delegate(): void
    {
        $mapper = new class extends DefaultExceptionMapper {
            public function map(Throwable $e): ProblemInterface
            {
                if ($e instanceof \DomainException) {
                    return new Problem('about:blank', 'Unauthorized', 401, $e->getMessage());
                }

                return parent::map($e);
            }
        };

        self::assertSame(401, $mapper->map(new \DomainException('sin token'))->getStatus());
        self::assertSame(404, $mapper->map(KernelExceptions::notFound())->getStatus());
        self::assertSame(500, $mapper->map(new \RuntimeException('boom'))->getStatus());
    }

    /**
     * MALFORMED_INPUT_DETAIL es protected para que un host pueda redeclararla,
     * típicamente para traducir el texto. Eso exige que map() la referencie con
     * static:: y no con self::, que la resolvería siempre en esta clase y
     * dejaría la constante del hijo sin efecto.
     */
    public function test_a_host_can_override_the_malformed_input_detail(): void
    {
        $mapper = new class extends DefaultExceptionMapper {
            protected const MALFORMED_INPUT_DETAIL = 'Datos con un tipo o un valor que no corresponde.';
        };

        self::assertSame(
            'Datos con un tipo o un valor que no corresponde.',
            $mapper->map(new \TypeError('Money::of(): Argument #1 must be of type int'))->getDetail(),
        );
    }
}
