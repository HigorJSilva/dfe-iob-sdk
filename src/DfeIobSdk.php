<?php

namespace Emitte\DfeIob;

use Emitte\DfeIob\Auth\AdmAuthenticator;
use Emitte\DfeIob\Auth\InMemoryTokenStore;
use Emitte\DfeIob\Auth\TokenStore;
use Emitte\DfeIob\Http\HttpClient;
use Emitte\DfeIob\Resources\AplicacaoResource;
use Emitte\DfeIob\Resources\CertificadoResource;
use Emitte\DfeIob\Resources\CteResource;
use Emitte\DfeIob\Resources\EmpresaResource;
use Emitte\DfeIob\Resources\NfceResource;
use Emitte\DfeIob\Resources\NfeResource;
use Emitte\DfeIob\Resources\NfseResource;

/**
 * Ponto de entrada do SDK para a API Motor de Documentos Fiscais da IOB.
 *
 * Uso básico (sem Laravel):
 * ```php
 * $sdk = DfeIobSdk::make([
 *     'api_key'          => 'sua-api-key',
 *     'adm' => [
 *         'client_id'     => '...',
 *         'client_secret' => '...',
 *         'x_api_key'     => '...',
 *         'username'      => '...',
 *         'password'      => '...',
 *     ],
 * ]);
 *
 * // Emitir NF-e
 * $resposta = $sdk->nfe()->emitir($payload);
 *
 * // ADM
 * $empresas = $sdk->empresa()->listar();
 * ```
 */
class DfeIobSdk
{
    // URLs de produção (sem "dev-") para referência
    private const DEFAULT_DFE_NFE_URL  = 'https://dev-dfe.nfe.iob.com.br';
    private const DEFAULT_DFE_NFSE_URL = 'https://dev-dfe-nfse.iob.com.br';
    private const DEFAULT_DFE_CTE_URL  = 'https://dev-dfe-cte.iob.com.br';
    private const DEFAULT_ADM_URL      = 'https://dev-dfe-adm.iob.com.br';
    private const DEFAULT_USM_URL      = 'https://development-usm-svc-app.iob.com.br';

    private HttpClient $dfeNfeClient;
    private HttpClient $dfeNfseClient;
    private HttpClient $dfeCteClient;
    private HttpClient $admClient;
    private AdmAuthenticator $admAuth;

    /** @param array<string, mixed> $config */
    public function __construct(private readonly array $config)
    {
        $this->boot();
    }

    /**
     * Cria uma instância do SDK a partir de um array de configuração.
     *
     * @param array<string, mixed> $config
     */
    public static function make(array $config, ?TokenStore $tokenStore = null): self
    {
        $instance = new self($config);

        if ($tokenStore !== null) {
            $instance->setTokenStore($tokenStore);
        }

        return $instance;
    }

    // -------------------------------------------------------------------------
    // Recursos DFe
    // -------------------------------------------------------------------------

    public function nfce(): NfceResource
    {
        return new NfceResource($this->dfeNfeClient);
    }

    public function nfe(): NfeResource
    {
        return new NfeResource($this->dfeNfeClient);
    }

    public function nfse(): NfseResource
    {
        return new NfseResource($this->dfeNfseClient);
    }

    public function cte(): CteResource
    {
        return new CteResource($this->dfeCteClient);
    }

    // -------------------------------------------------------------------------
    // Recursos ADM
    // -------------------------------------------------------------------------

    public function aplicacao(): AplicacaoResource
    {
        return new AplicacaoResource($this->buildAdmClientWithToken());
    }

    public function empresa(): EmpresaResource
    {
        return new EmpresaResource($this->buildAdmClientWithToken());
    }

    public function certificado(): CertificadoResource
    {
        return new CertificadoResource($this->buildAdmClientWithToken());
    }

    // -------------------------------------------------------------------------
    // Acesso ao autenticador ADM
    // -------------------------------------------------------------------------

    /**
     * Retorna o autenticador ADM para gerenciamento manual de tokens (ex: invalidar).
     */
    public function admAuthenticator(): AdmAuthenticator
    {
        return $this->admAuth;
    }

    // -------------------------------------------------------------------------
    // Internos
    // -------------------------------------------------------------------------

    private function boot(): void
    {
        $apiKey  = $this->config['api_key'] ?? '';
        $timeout = (int) ($this->config['http']['timeout'] ?? 30);
        $connect = (int) ($this->config['http']['connect_timeout'] ?? 10);

        $dfeHeaders = array_filter(['x-api-key' => $apiKey]);

        $this->dfeNfeClient = new HttpClient(
            $this->config['dfe_nfe_base_url'] ?? self::DEFAULT_DFE_NFE_URL,
            $dfeHeaders,
            $timeout,
            $connect,
        );

        $this->dfeNfseClient = new HttpClient(
            $this->config['dfe_nfse_base_url'] ?? self::DEFAULT_DFE_NFSE_URL,
            $dfeHeaders,
            $timeout,
            $connect,
        );

        $this->dfeCteClient = new HttpClient(
            $this->config['dfe_cte_base_url'] ?? self::DEFAULT_DFE_CTE_URL,
            $dfeHeaders,
            $timeout,
            $connect,
        );

        $adm = $this->config['adm'] ?? [];

        $this->admAuth = new AdmAuthenticator(
            usmBaseUrl:    $this->config['usm_base_url'] ?? self::DEFAULT_USM_URL,
            clientId:      $adm['client_id'] ?? '',
            clientSecret:  $adm['client_secret'] ?? '',
            xApiKey:       $adm['x_api_key'] ?? '',
            username:      $adm['username'] ?? '',
            password:      $adm['password'] ?? '',
            tokenStore:    new InMemoryTokenStore(),
            cachePrefix:   $this->config['token_cache']['prefix'] ?? 'iob_adm_token',
        );

        // admClient sem Bearer — será construído dinamicamente em buildAdmClientWithToken()
        $this->admClient = new HttpClient(
            $this->config['adm_base_url'] ?? self::DEFAULT_ADM_URL,
            [],
            $timeout,
            $connect,
        );
    }

    private function setTokenStore(TokenStore $store): void
    {
        $adm    = $this->config['adm'] ?? [];
        $prefix = $this->config['token_cache']['prefix'] ?? 'iob_adm_token';

        $this->admAuth = new AdmAuthenticator(
            usmBaseUrl:    $this->config['usm_base_url'] ?? self::DEFAULT_USM_URL,
            clientId:      $adm['client_id'] ?? '',
            clientSecret:  $adm['client_secret'] ?? '',
            xApiKey:       $adm['x_api_key'] ?? '',
            username:      $adm['username'] ?? '',
            password:      $adm['password'] ?? '',
            tokenStore:    $store,
            cachePrefix:   $prefix,
        );
    }

    /**
     * Cria um HttpClient para o ADM com o Bearer token atual injetado.
     * O token é resolvido (e renovado se expirado) a cada chamada.
     */
    private function buildAdmClientWithToken(): HttpClient
    {
        $token   = $this->admAuth->getAccessToken();
        $timeout = (int) ($this->config['http']['timeout'] ?? 30);
        $connect = (int) ($this->config['http']['connect_timeout'] ?? 10);

        return new HttpClient(
            $this->config['adm_base_url'] ?? self::DEFAULT_ADM_URL,
            [
                'Authorization' => "Bearer {$token}",
                'Content-Type'  => 'application/json',
            ],
            $timeout,
            $connect,
        );
    }
}
