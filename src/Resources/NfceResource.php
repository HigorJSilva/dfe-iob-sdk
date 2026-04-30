<?php

namespace Emitte\DfeIob\Resources;

/**
 * Recurso NFC-e (Nota Fiscal de Consumidor Eletrônica).
 *
 * Servidor: https://dev-dfe.nfe.iob.com.br
 * Autenticação: x-api-key (passada no HttpClient padrão DFe)
 */
class NfceResource extends BaseResource
{
    // -------------------------------------------------------------------------
    // Emissão
    // -------------------------------------------------------------------------

    /**
     * Emite uma NFC-e (assíncrono). Retorna o ID da tentativa de emissão.
     *
     * Nota: a API exige os headers x-api-key (já configurado no cliente) e BusinessId,
     * apesar de a documentação oficial não listá-los como obrigatórios neste endpoint.
     *
     * @param array<string, mixed> $data          Payload conforme schema AddNfceRequest
     * @param string               $businessId    ID do negócio (header obrigatório)
     * @param bool                 $validarSchema Valida o XML contra o schema (padrão: true)
     * @return array<string, mixed>
     */
    public function emitir(array $data, string $businessId, bool $validarSchema = true): array
    {
        return $this->client->post('/api/Nfce', $data, headers: ['BusinessId' => $businessId], query: ['validarSchema' => $validarSchema ? 'true' : 'false']);
    }

    // -------------------------------------------------------------------------
    // Consultas
    // -------------------------------------------------------------------------

    /**
     * Consulta o resumo de uma NFC-e pelo ID interno.
     *
     * @return array<string, mixed>
     */
    public function consultarPorId(string $id, string $idAplicacao, string $businessId): array
    {
        return $this->client->get("/api/Nfce/id/{$id}", headers: [
            'idAplicacao' => $idAplicacao,
            'businessId'  => $businessId,
        ]);
    }

    /**
     * Consulta o resumo de uma ou mais NFC-e pelo ID de integração.
     *
     * @return array<string, mixed>
     */
    public function consultarPorIdIntegracao(string $idIntegracao, string $idAplicacao, string $businessId): array
    {
        return $this->client->get("/api/Nfce/id-integracao/{$idIntegracao}", headers: [
            'idAplicacao' => $idAplicacao,
            'businessId'  => $businessId,
        ]);
    }

    /**
     * Consulta NFC-e emitidas dentro de um período.
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
        string $businessId,
        ?string $tokenPaginacao = null,
    ): array {
        $query = compact('cpfCnpj', 'inicio', 'fim');

        if ($tokenPaginacao !== null) {
            $query['tokenPaginacao'] = $tokenPaginacao;
        }

        return $this->client->get('/api/Nfce/consulta-periodo', $query, headers: [
            'businessId'  => $businessId,
        ]);
    }

    /**
     * Consulta a posição de falha de comunicação com a SEFAZ para uma NFC-e.
     *
     * @param array<string, mixed> $data Payload conforme ConsultaPosFalhaComunicacaoSefazNfceRequest
     * @return array<string, mixed>
     */
    public function consultarSefaz(array $data): array
    {
        return $this->client->post('/api/Nfce/consultar-sefaz', $data);
    }

    // -------------------------------------------------------------------------
    // Download de documentos
    // -------------------------------------------------------------------------

    /**
     * Baixa o XML de uma NFC-e.
     */
    public function downloadXml(string $idNota, string $idAplicacao, string $businessId): string
    {
        return $this->client->getRaw("/api/Nfce/{$idNota}/xml", headers: [
            'idAplicacao' => $idAplicacao,
            'businessId'  => $businessId,
        ]);
    }

    /**
     * Baixa o DANFE (PDF) de uma NFC-e.
     */
    public function downloadPdf(string $idNota, string $idAplicacao, string $businessId): string
    {
        return $this->client->getRaw("/api/Nfce/{$idNota}/pdf", headers: [
            'idAplicacao' => $idAplicacao,
            'businessId'  => $businessId,
        ]);
    }

    // -------------------------------------------------------------------------
    // Cancelamento
    // -------------------------------------------------------------------------

    /**
     * Cancela uma NFC-e emitida.
     *
     * @param array<string, mixed> $data       Payload conforme CancelNfceRequest
     * @param string               $businessId ID do negócio (header obrigatório)
     * @return array<string, mixed>
     */
    public function cancelar(string $idNota, array $data, string $businessId): array
    {
        return $this->client->post("/api/Nfce/cancelar/{$idNota}", $data, headers: [
            'BusinessId' => $businessId,
        ]);
    }

    /**
     * Consulta o status de cancelamento de uma NFC-e.
     *
     * @return array<string, mixed>
     */
    public function consultarStatusCancelamento(string $idNota, string $businessId, string $idAplicacao): array
    {
        return $this->client->get("/api/Nfce/cancelar/{$idNota}/consulta", headers: [
            'businessId'  => $businessId,
            'idAplicacao' => $idAplicacao,
        ]);
    }

    /**
     * Baixa o XML de cancelamento de uma NFC-e.
     */
    public function downloadXmlCancelamento(string $idNota, string $businessId, string $idAplicacao): string
    {
        return $this->client->getRaw("/api/Nfce/cancelar/{$idNota}/xml", headers: [
            'businessId'  => $businessId,
            'idAplicacao' => $idAplicacao,
        ]);
    }

    // -------------------------------------------------------------------------
    // Inutilização
    // -------------------------------------------------------------------------

    /**
     * Inutiliza uma faixa de números de NFC-e.
     *
     * @param array<string, mixed> $data       Payload conforme InutilizarRequest
     * @param string               $businessId ID do negócio (header obrigatório)
     * @return array<string, mixed>
     */
    public function inutilizar(array $data, string $businessId): array
    {
        return $this->client->post('/api/Nfce/inutilizar', $data, headers: [
            'BusinessId' => $businessId,
        ]);
    }

    /**
     * Consulta o status de inutilização de uma faixa de números.
     *
     * @return array<string, mixed>
     */
    public function consultarStatusInutilizacao(string $id, string $businessId, string $idAplicacao): array
    {
        return $this->client->get("/api/Nfce/inutilizar/{$id}/consulta", headers: [
            'businessId'  => $businessId,
            'idAplicacao' => $idAplicacao,
        ]);
    }

    // -------------------------------------------------------------------------
    // Validação
    // -------------------------------------------------------------------------

    /**
     * Valida o payload de uma NFC-e contra o schema XML sem emitir.
     *
     * @param array<string, mixed> $data Payload conforme AddNfceRequest
     * @return array<string, mixed>
     */
    public function validar(array $data, string $businessId, string $tenantId): array
    {
        return $this->client->post('/api/Nfce/validar', $data, headers: [
            'businessId' => $businessId,
            'tenantId'   => $tenantId,
        ]);
    }
}
