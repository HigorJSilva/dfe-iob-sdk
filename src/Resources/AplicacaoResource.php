<?php

namespace Emitte\DfeIob\Resources;

/**
 * Recurso ADM - Aplicação.
 *
 * Servidor: https://dev-dfe-adm.iob.com.br
 * Autenticação: Bearer token (OAuth 3 etapas via AdmAuthenticator)
 */
class AplicacaoResource extends BaseResource
{
    // -------------------------------------------------------------------------
    // CRUD de Aplicação
    // -------------------------------------------------------------------------

    /**
     * Cadastra uma nova aplicação.
     *
     * @param array<string, mixed> $data Payload conforme AddAplicacaoRequest
     * @return array<string, mixed>
     */
    public function criar(array $data): array
    {
        return $this->client->post('/api/Aplicacao', $data);
    }

    /**
     * Atualiza uma aplicação existente.
     *
     * @param array<string, mixed> $data Payload conforme UpdateAplicacaoRequest
     * @return array<string, mixed>
     */
    public function atualizar(array $data): array
    {
        return $this->client->put('/api/Aplicacao', $data);
    }

    /**
     * Remove uma aplicação pelo ID.
     *
     * @return array<string, mixed>
     */
    public function remover(string $id): array
    {
        return $this->client->delete("/api/Aplicacao/{$id}");
    }

    /**
     * Lista todas as aplicações paginadas.
     *
     * @param array<string, mixed> $query Parâmetros de paginação/filtro
     * @return array<string, mixed>
     */
    public function listar(array $query = []): array
    {
        return $this->client->get('/api/Aplicacao/listar', $query);
    }

    /**
     * Busca uma aplicação pelo ID.
     *
     * @return array<string, mixed>
     */
    public function buscarPorId(string $id): array
    {
        return $this->client->get("/api/Aplicacao/id/{$id}");
    }

    // -------------------------------------------------------------------------
    // Webhook
    // -------------------------------------------------------------------------

    /**
     * Cadastra um webhook para a aplicação.
     *
     * @param array<string, mixed> $data Payload conforme AddWebhookRequest
     * @return array<string, mixed>
     */
    public function criarWebhook(string $idAplicacao, array $data): array
    {
        return $this->client->post("/api/Aplicacao/webhook/{$idAplicacao}", $data);
    }

    /**
     * Lista os webhooks de uma aplicação.
     *
     * @return array<string, mixed>
     */
    public function listarWebhooks(string $idAplicacao): array
    {
        return $this->client->get("/api/Aplicacao/webhook/{$idAplicacao}");
    }

    /**
     * Atualiza o webhook de uma aplicação.
     *
     * @param array<string, mixed> $data Payload conforme AddWebhookRequest
     * @return array<string, mixed>
     */
    public function atualizarWebhook(string $idAplicacao, array $data): array
    {
        return $this->client->put("/api/Aplicacao/webhook/{$idAplicacao}", $data);
    }

    /**
     * Remove o webhook de uma aplicação.
     *
     * @return array<string, mixed>
     */
    public function removerWebhook(string $idAplicacao): array
    {
        return $this->client->delete("/api/Aplicacao/webhook/{$idAplicacao}");
    }
}
