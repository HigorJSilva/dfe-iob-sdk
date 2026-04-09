<?php

namespace Emitte\DfeIob\Resources;

/**
 * Recurso NF-e (Nota Fiscal Eletrônica).
 *
 * Servidor: https://dev-dfe.nfe.iob.com.br
 * Autenticação: x-api-key (passada no HttpClient padrão DFe)
 */
class NfeResource extends BaseResource
{
    // -------------------------------------------------------------------------
    // Emissão
    // -------------------------------------------------------------------------

    /**
     * Emite uma NF-e.
     *
     * @param array<string, mixed> $data                Payload conforme AddNfeRequest
     * @param bool                 $validarSchema        Valida o XML contra o schema (padrão: true)
     * @param bool                 $consultarStatusSefaz Consulta status SEFAZ no monitor (padrão: false)
     * @return array<string, mixed>
     */
    public function emitir(array $data, bool $validarSchema = true, bool $consultarStatusSefaz = false): array
    {
        return $this->client->post('/api/Nfe', $data, query: [
            'validarSchema'        => $validarSchema ? 'true' : 'false',
            'consultarStatusSefaz' => $consultarStatusSefaz ? 'true' : 'false',
        ]);
    }

    // -------------------------------------------------------------------------
    // Consultas
    // -------------------------------------------------------------------------

    /**
     * Consulta uma NF-e pelo ID interno.
     *
     * @return array<string, mixed>
     */
    public function consultarPorId(string $id, string $idAplicacao, string $businessId): array
    {
        return $this->client->get("/api/Nfe/id/{$id}", headers: [
            'idAplicacao' => $idAplicacao,
            'businessId'  => $businessId,
        ]);
    }

    /**
     * Consulta uma NF-e pelo ID de integração.
     *
     * @return array<string, mixed>
     */
    public function consultarPorIdIntegracao(string $idIntegracao, string $idAplicacao, string $businessId): array
    {
        return $this->client->get("/api/Nfe/id-integracao/{$idIntegracao}", headers: [
            'idAplicacao' => $idAplicacao,
            'businessId'  => $businessId,
        ]);
    }

    /**
     * Consulta NF-e emitidas dentro de um período.
     *
     * @param string      $cpfCnpj        CPF ou CNPJ do emitente
     * @param string      $inicio         Data de início (dd-MM-yyyy)
     * @param string      $fim            Data de fim (dd-MM-yyyy)
     * @param string|null $tokenPaginacao Token de paginação (opcional)
     * @return array<string, mixed>
     */
    public function consultarPorPeriodo(
        string $cpfCnpj,
        string $inicio,
        string $fim,
        ?string $tokenPaginacao = null,
    ): array {
        $query = compact('cpfCnpj', 'inicio', 'fim');

        if ($tokenPaginacao !== null) {
            $query['tokenPaginacao'] = $tokenPaginacao;
        }

        return $this->client->get('/api/Nfe/consulta-periodo', $query);
    }

    // -------------------------------------------------------------------------
    // Download de documentos
    // -------------------------------------------------------------------------

    /**
     * Baixa o XML de uma NF-e.
     */
    public function downloadXml(string $idNota, string $idAplicacao, string $businessId): string
    {
        return $this->client->getRaw("/api/Nfe/{$idNota}/xml", headers: [
            'idAplicacao' => $idAplicacao,
            'businessId'  => $businessId,
        ]);
    }

    /**
     * Baixa o DANFE (PDF) de uma NF-e.
     */
    public function downloadPdf(string $idNota, string $idAplicacao, string $businessId): string
    {
        return $this->client->getRaw("/api/Nfe/{$idNota}/pdf", headers: [
            'idAplicacao' => $idAplicacao,
            'businessId'  => $businessId,
        ]);
    }

    /**
     * Baixa o XML de um evento de NF-e.
     * Nota: a API exige apenas o header "businessId" neste endpoint.
     */
    public function downloadEventoXml(string $idEvento, string $businessId): string
    {
        return $this->client->getRaw("/api/Nfe/evento/{$idEvento}/xml", headers: [
            'businessId' => $businessId,
        ]);
    }

    /**
     * Baixa o PDF de um evento de NF-e.
     * Nota: a API exige apenas o header "businessId" neste endpoint.
     */
    public function downloadEventoPdf(string $idEvento, string $businessId): string
    {
        return $this->client->getRaw("/api/Nfe/evento/{$idEvento}/pdf", headers: [
            'businessId' => $businessId,
        ]);
    }

    // -------------------------------------------------------------------------
    // Cancelamento
    // -------------------------------------------------------------------------

    /**
     * Cancela uma NF-e.
     *
     * @param array<string, mixed> $data Payload conforme CancelNfeRequest
     * @return array<string, mixed>
     */
    public function cancelar(string $idNota, array $data): array
    {
        return $this->client->post("/api/Nfe/cancelar/{$idNota}", $data);
    }

    /**
     * Consulta o status de cancelamento de uma NF-e.
     *
     * @return array<string, mixed>
     */
    public function consultarStatusCancelamento(string $idNota, string $businessId, string $idAplicacao): array
    {
        return $this->client->get("/api/Nfe/cancelar/{$idNota}/consulta", headers: [
            'businessId'  => $businessId,
            'idAplicacao' => $idAplicacao,
        ]);
    }

    /**
     * Baixa o XML de cancelamento de uma NF-e.
     */
    public function downloadXmlCancelamento(string $idNota, string $businessId, string $idAplicacao): string
    {
        return $this->client->getRaw("/api/Nfe/cancelar/{$idNota}/xml", headers: [
            'businessId'  => $businessId,
            'idAplicacao' => $idAplicacao,
        ]);
    }

    // -------------------------------------------------------------------------
    // Inutilização
    // -------------------------------------------------------------------------

    /**
     * Inutiliza uma faixa de números de NF-e.
     *
     * @param array<string, mixed> $data Payload conforme InutilizarRequest
     * @return array<string, mixed>
     */
    public function inutilizar(array $data): array
    {
        return $this->client->post('/api/Nfe/inutilizar', $data);
    }

    /**
     * Consulta o status de inutilização.
     *
     * @return array<string, mixed>
     */
    public function consultarStatusInutilizacao(string $id, string $businessId, string $idAplicacao): array
    {
        return $this->client->get("/api/Nfe/inutilizar/{$id}/consulta", headers: [
            'businessId'  => $businessId,
            'idAplicacao' => $idAplicacao,
        ]);
    }

    // -------------------------------------------------------------------------
    // Carta de Correção (CC-e)
    // -------------------------------------------------------------------------

    /**
     * Solicita uma Carta de Correção para uma NF-e.
     *
     * @param array<string, mixed> $data Payload conforme SolicitarCartaCorrecaoRequest
     * @return array<string, mixed>
     */
    public function solicitarCartaCorrecao(string $idNota, array $data): array
    {
        return $this->client->post("/api/Nfe/correcao/{$idNota}", $data);
    }

    /**
     * Consulta o status de uma Carta de Correção.
     *
     * @return array<string, mixed>
     */
    public function consultarCartaCorrecao(string $idNota, string $businessId, string $idAplicacao): array
    {
        return $this->client->get("/api/Nfe/correcao/{$idNota}/consulta", headers: [
            'businessId'  => $businessId,
            'idAplicacao' => $idAplicacao,
        ]);
    }

    /**
     * Baixa o XML de uma Carta de Correção.
     * Nota: a API não exige headers adicionais neste endpoint.
     */
    public function downloadXmlCartaCorrecao(string $idNota): string
    {
        return $this->client->getRaw("/api/Nfe/correcao/{$idNota}/xml");
    }

    // -------------------------------------------------------------------------
    // Insucesso de Entrega
    // -------------------------------------------------------------------------

    /**
     * Registra insucesso de entrega de uma NF-e.
     *
     * @param array<string, mixed> $data Payload conforme SolicitarInsucessoEntregaNfeRequest
     * @return array<string, mixed>
     */
    public function registrarInsucessoEntrega(string $idNota, array $data): array
    {
        return $this->client->post("/api/Nfe/insucesso-entrega/{$idNota}", $data);
    }

    /**
     * Consulta o status de insucesso de entrega.
     *
     * @return array<string, mixed>
     */
    public function consultarInsucessoEntrega(string $idOperacao, string $businessId, string $idAplicacao): array
    {
        return $this->client->get("/api/Nfe/insucesso-entrega/{$idOperacao}/consulta", headers: [
            'businessId'  => $businessId,
            'idAplicacao' => $idAplicacao,
        ]);
    }
}
