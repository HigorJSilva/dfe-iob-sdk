<?php

namespace Emitte\DfeIob\Resources;

/**
 * Recurso ADM - Certificado Digital.
 *
 * Servidor: https://dev-dfe-adm.iob.com.br
 * Autenticação: Bearer token (OAuth 3 etapas via AdmAuthenticator)
 */
class CertificadoResource extends BaseResource
{
    /**
     * Cadastra um novo certificado digital para uma empresa.
     *
     * @param array<string, mixed> $data Payload conforme AddCertificadoRequest
     * @return array<string, mixed>
     */
    public function criar(array $data): array
    {
        return $this->client->post('/api/Certificado', $data);
    }

    /**
     * Atualiza um certificado digital existente.
     *
     * @param array<string, mixed> $data Payload conforme AddCertificadoRequest
     * @return array<string, mixed>
     */
    public function atualizar(array $data): array
    {
        return $this->client->put('/api/Certificado', $data);
    }

    /**
     * Lista todos os certificados paginados.
     *
     * @param string|null $idAplicacao    Filtrar por aplicação (obrigatório na prática)
     * @param string|null $tokenPaginacao Token para próxima página
     * @return array<string, mixed>
     */
    public function listar(?string $idAplicacao = null, ?string $tokenPaginacao = null): array
    {
        $query = array_filter([
            'idAplicacao'    => $idAplicacao,
            'tokenPaginacao' => $tokenPaginacao,
        ]);

        return $this->client->get('/api/Certificado/listar', $query);
    }

    /**
     * Busca um certificado pelo ID.
     *
     * @return array<string, mixed>
     */
    public function buscarPorId(string $idCertificado): array
    {
        return $this->client->get("/api/Certificado/{$idCertificado}");
    }

    /**
     * Remove um certificado pelo ID.
     *
     * @return array<string, mixed>
     */
    public function remover(string $idCertificado): array
    {
        return $this->client->delete("/api/Certificado/{$idCertificado}");
    }
}
