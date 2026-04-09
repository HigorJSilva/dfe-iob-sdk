<?php

namespace Emitte\DfeIob\Auth;

use Emitte\DfeIob\Exceptions\AuthenticationException;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use Throwable;

/**
 * Gerencia a autenticação de 3 etapas do módulo ADM via servidor USM da IOB.
 *
 * Etapa 1 — GET /oauth/signin/token
 *   Retorna um signin_token temporário usando Basic auth (clientId:clientSecret).
 *
 * Etapa 2 — POST /oauth/signin
 *   Troca o signin_token por access_token + refresh_token usando as
 *   credenciais da aplicação no motor IOB.
 *
 * Etapa 3 — POST /oauth/refreshtoken
 *   Renova o access_token usando o refresh_token (executado automaticamente
 *   quando o access_token estiver prestes a expirar).
 */
class AdmAuthenticator
{
    private const USM_PATH = '/hypercube_usm/v1/oauth';

    /** Margem de segurança (segundos) para renovar o token antes de expirar. */
    private const REFRESH_MARGIN = 120;

    private GuzzleClient $http;

    public function __construct(
        private readonly string $usmBaseUrl,
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly string $xApiKey,
        private readonly string $username,
        private readonly string $password,
        private readonly TokenStore $tokenStore,
        private readonly string $cachePrefix = 'iob_adm_token',
    ) {
        $this->http = new GuzzleClient([
            'base_uri' => rtrim($usmBaseUrl, '/'),
            'timeout'  => 15,
        ]);
    }

    /**
     * Retorna um Bearer access_token válido, renovando-o se necessário.
     */
    public function getAccessToken(): string
    {
        $token = $this->tokenStore->get("{$this->cachePrefix}_access");

        if ($token !== null) {
            return $token;
        }

        // Tenta renovar via refresh_token antes de fazer login completo
        $refreshToken = $this->tokenStore->get("{$this->cachePrefix}_refresh");

        if ($refreshToken !== null) {
            return $this->refresh($refreshToken);
        }

        return $this->login();
    }

    /**
     * Força a invalidação dos tokens em cache, exigindo novo login.
     */
    public function invalidate(): void
    {
        $this->tokenStore->forget("{$this->cachePrefix}_access");
        $this->tokenStore->forget("{$this->cachePrefix}_refresh");
    }

    // -------------------------------------------------------------------------
    // Métodos internos
    // -------------------------------------------------------------------------

    /**
     * Executa as etapas 1 e 2 da autenticação e armazena os tokens.
     */
    private function login(): string
    {
        $signinToken = $this->fetchSigninToken();

        try {
            $response = $this->http->post(self::USM_PATH . '/signin', [
                'headers' => [
                    'x-api-key'    => $this->xApiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'username'      => $this->username,
                    'password'      => $this->password,
                    'client_id'     => $this->clientId,
                    'signin_token'  => $signinToken,
                ],
            ]);

            $body = json_decode((string) $response->getBody(), true);
        } catch (GuzzleException $e) {
            throw new AuthenticationException(
                'Falha na etapa 2 da autenticação ADM: ' . $e->getMessage(),
                previous: $e,
            );
        }

        return $this->storeTokens($body);
    }

    /**
     * Etapa 1: obtém o signin_token temporário.
     */
    private function fetchSigninToken(): string
    {
        $basicCredential = base64_encode("{$this->clientId}:{$this->clientSecret}");

        try {
            $response = $this->http->get(self::USM_PATH . '/signin/token', [
                'headers' => [
                    'x-api-key'     => $this->xApiKey,
                    'Authorization' => "Basic {$basicCredential}",
                ],
            ]);

            $body = json_decode((string) $response->getBody(), true);
        } catch (GuzzleException $e) {
            throw new AuthenticationException(
                'Falha na etapa 1 da autenticação ADM: ' . $e->getMessage(),
                previous: $e,
            );
        }

        if (empty($body['token'])) {
            throw new AuthenticationException('Etapa 1: resposta sem campo "token".');
        }

        return $body['token'];
    }

    /**
     * Etapa 3: renova o access_token usando o refresh_token.
     */
    private function refresh(string $refreshToken): string
    {
        try {
            $response = $this->http->post(self::USM_PATH . '/refreshtoken', [
                'headers' => [
                    'x-api-key'      => $this->xApiKey,
                    'Content-Type'   => 'application/json',
                    'accept-language' => 'pt-BR,pt;q=0.9',
                ],
                'json' => ['refresh_token' => $refreshToken],
            ]);

            $body = json_decode((string) $response->getBody(), true);
        } catch (GuzzleException $e) {
            // refresh falhou — tenta login completo
            $this->tokenStore->forget("{$this->cachePrefix}_refresh");
            return $this->login();
        }

        return $this->storeTokens($body);
    }

    /**
     * Salva access_token e refresh_token no TokenStore com TTLs apropriados.
     */
    private function storeTokens(array $body): string
    {
        if (empty($body['access_token'])) {
            throw new AuthenticationException('Resposta de autenticação sem campo "access_token".');
        }

        $accessToken  = $body['access_token'];
        $refreshToken = $body['refresh_token'] ?? null;

        $accessTtl  = (int) ($body['expires_in'] ?? 3600) - self::REFRESH_MARGIN;
        $refreshTtl = (int) ($body['refresh_token_expires_in'] ?? 432000);

        $this->tokenStore->put("{$this->cachePrefix}_access", $accessToken, max($accessTtl, 60));

        if ($refreshToken !== null) {
            $this->tokenStore->put("{$this->cachePrefix}_refresh", $refreshToken, $refreshTtl);
        }

        return $accessToken;
    }
}
