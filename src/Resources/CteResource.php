<?php

namespace Emitte\DfeIob\Resources;

/**
 * Recurso CT-e (Conhecimento de Transporte Eletrônico).
 *
 * Servidor: https://dev-dfe-cte.iob.com.br
 * Autenticação: x-api-key (passada no HttpClient CT-e como header padrão)
 *
 * Observação: a maioria dos endpoints de consulta e download exige também o
 * header "BusinessId" (com B maiúsculo, conforme a documentação da API).
 */
class CteResource extends BaseResource
{
    // -------------------------------------------------------------------------
    // Emissão
    // -------------------------------------------------------------------------

    /**
     * Emite um CT-e.
     *
     * Nota: a API exige os headers x-api-key (já configurado no cliente) e BusinessId,
     * apesar de a documentação oficial não listá-los como obrigatórios neste endpoint.
     *
     * @param array<string, mixed> $data          Payload conforme AddCteRequest
     * @param string               $businessId    ID do negócio (header obrigatório)
     * @param bool                 $validarSchema Valida o XML contra o schema (padrão: true)
     * @return array<string, mixed>
     */
    public function emitir(array $data, string $businessId, bool $validarSchema = true): array
    {
        return $this->client->post('/api/Cte', $data, headers: ['BusinessId' => $businessId], query: [
            'validarSchema' => $validarSchema ? 'true' : 'false',
        ]);
    }

    // -------------------------------------------------------------------------
    // Consultas
    // -------------------------------------------------------------------------

    /**
     * Consulta um CT-e pelo ID interno.
     *
     * @return array<string, mixed>
     */
    public function consultarPorId(string $id, string $businessId): array
    {
        return $this->client->get("/api/Cte/id/{$id}", headers: [
            'BusinessId' => $businessId,
        ]);
    }

    /**
     * Consulta um CT-e pelo ID de integração.
     *
     * @return array<string, mixed>
     */
    public function consultarPorIdIntegracao(string $idIntegracao, string $businessId): array
    {
        return $this->client->get("/api/Cte/id-integracao/{$idIntegracao}", headers: [
            'BusinessId' => $businessId,
        ]);
    }

    /**
     * Consulta CT-e emitidos dentro de um período.
     *
     * @param string      $cpfCnpj        CPF ou CNPJ do emitente (obrigatório)
     * @param string      $inicio         Data de início (dd-MM-yyyy, obrigatório)
     * @param string      $fim            Data de fim (dd-MM-yyyy, obrigatório)
     * @param string      $businessId     BusinessId do emitente
     * @param string|null $tokenPaginacao Token de paginação (opcional)
     * @return array<string, mixed>
     */
    public function consultarPorPeriodo(
        string $cpfCnpj,
        string $inicio,
        string $fim,
        string $businessId,
        ?string $tokenPaginacao = null,
    ): array {
        $query = compact('cpfCnpj', 'inicio', 'fim');

        if ($tokenPaginacao !== null) {
            $query['tokenPaginacao'] = $tokenPaginacao;
        }

        return $this->client->get('/api/Cte/consulta-periodo', $query, headers: [
            'BusinessId' => $businessId,
        ]);
    }

    // -------------------------------------------------------------------------
    // Download de documentos
    // -------------------------------------------------------------------------

    /**
     * Baixa o XML de envio de um CT-e.
     */
    public function downloadXmlEnvio(string $idNota, string $businessId): string
    {
        return $this->client->getRaw("/api/Cte/{$idNota}/xmlenvio", headers: [
            'BusinessId' => $businessId,
        ]);
    }

    /**
     * Baixa o XML autorizado de um CT-e.
     */
    public function downloadXml(string $idNota, string $businessId): string
    {
        return $this->client->getRaw("/api/Cte/{$idNota}/xml", headers: [
            'BusinessId' => $businessId,
        ]);
    }

    /**
     * Baixa o DACTE (PDF) de um CT-e.
     */
    public function downloadPdf(string $idNota, string $businessId): string
    {
        return $this->client->getRaw("/api/Cte/{$idNota}/pdf", headers: [
            'BusinessId' => $businessId,
        ]);
    }

    /**
     * Baixa o XML de um evento de CT-e.
     */
    public function downloadEventoXml(string $idOperacao, string $businessId): string
    {
        return $this->client->getRaw("/api/Cte/evento/{$idOperacao}/xml", headers: [
            'BusinessId' => $businessId,
        ]);
    }

    /**
     * Baixa o PDF de um evento de CT-e.
     */
    public function downloadEventoPdf(string $idOperacao, string $businessId): string
    {
        return $this->client->getRaw("/api/Cte/evento/{$idOperacao}/pdf", headers: [
            'BusinessId' => $businessId,
        ]);
    }

    // -------------------------------------------------------------------------
    // Cancelamento
    // -------------------------------------------------------------------------

    /**
     * Cancela um CT-e.
     *
     * @param array<string, mixed> $data Payload conforme CancelCteRequest
     * @return array<string, mixed>
     */
    public function cancelar(string $idNota, array $data): array
    {
        return $this->client->post("/api/Cte/cancelar/{$idNota}", $data);
    }

    /**
     * Consulta o status de cancelamento de um CT-e.
     *
     * @return array<string, mixed>
     */
    public function consultarStatusCancelamento(string $idNota): array
    {
        return $this->client->get("/api/Cte/cancelar/{$idNota}/consulta");
    }

    /**
     * Baixa o XML de cancelamento de um CT-e.
     */
    public function downloadXmlCancelamento(string $idNota, string $businessId): string
    {
        return $this->client->getRaw("/api/Cte/cancelar/{$idNota}/xml", headers: [
            'BusinessId' => $businessId,
        ]);
    }

    // -------------------------------------------------------------------------
    // Carta de Correção (CC-e)
    // -------------------------------------------------------------------------

    /**
     * Solicita uma Carta de Correção para um CT-e.
     *
     * @param array<string, mixed> $data Payload conforme CartaCorrecaoCteRequest
     * @return array<string, mixed>
     */
    public function solicitarCartaCorrecao(string $idNota, array $data, string $businessId): array
    {
        return $this->client->post("/api/Cte/correcao/{$idNota}", $data, headers: [
            'BusinessId' => $businessId,
        ]);
    }

    /**
     * Consulta o status de uma Carta de Correção de CT-e.
     *
     * @return array<string, mixed>
     */
    public function consultarCartaCorrecao(string $idNota, string $businessId): array
    {
        return $this->client->get("/api/Cte/correcao/{$idNota}/consulta", headers: [
            'BusinessId' => $businessId,
        ]);
    }

    /**
     * Baixa o XML de uma Carta de Correção de CT-e.
     */
    public function downloadXmlCartaCorrecao(string $idNota, string $businessId): string
    {
        return $this->client->getRaw("/api/Cte/correcao/{$idNota}/xml", headers: [
            'BusinessId' => $businessId,
        ]);
    }
}
