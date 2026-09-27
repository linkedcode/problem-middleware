<?php

/*
 * Este paquete no depende de linkedcode/slim-oauth — DefaultExceptionMapper
 * reconoce sus excepciones por nombre (string), justamente para no acoplarse.
 *
 * Para testear ese reconocimiento se declaran acá las mismas clases con el FQCN
 * real. El guard evita redeclararlas si el host sí tiene slim-oauth
 * instalado, en cuyo caso ganan las verdaderas.
 */

namespace Linkedcode\SlimOAuth\Exception;

if (!class_exists(OAuthException::class, false)) {
    class OAuthException extends \RuntimeException {}

    class ProviderNotFoundException extends OAuthException {}

    class InvalidOAuthStateException extends OAuthException {}

    class InvalidRefreshTokenException extends OAuthException {}

    class TokenExchangeException extends OAuthException {}
}
