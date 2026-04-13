<?php

namespace Emitte\DfeIob\Resources;

/**
 * Recurso NFS-e (Nota Fiscal de Serviços Eletrônica).
 *
 * Servidor: https://dev-dfe-nfse.iob.com.br
 * Autenticação: x-api-key (passada no HttpClient NFS-e)
 */
class NfseResource extends BaseResource
{
    // -------------------------------------------------------------------------
    // Emissão
    // -------------------------------------------------------------------------

    /**
     * Emite uma NFS-e.
     *
     * Nota: a API exige os headers x-api-key (já configurado no cliente) e BusinessId,
     * apesar de a documentação oficial não listá-los como obrigatórios neste endpoint.
     *
     * @param array<string, mixed> $data          Payload conforme AddNfseRequest
     * @param string               $businessId    ID do negócio (header obrigatório)
     * @param bool                 $validarSchema Valida o XML contra o schema (padrão: true)
     * @return array<string, mixed>
     */
    public function emitir(array $data, string $businessId, bool $validarSchema = true): array
    {
        return $this->client->post('/api/Nfse', $data, headers: ['BusinessId' => $businessId], query: [
            'validarSchema' => $validarSchema ? 'true' : 'false',
        ]);
    }

    // -------------------------------------------------------------------------
    // Consultas
    // -------------------------------------------------------------------------

    /**
     * Consulta uma NFS-e pelo ID interno.
     *
     * @return array<string, mixed>
     */
    public function consultarPorId(string $id): array
    {
        return $this->client->get("/api/Nfse/id/{$id}");
    }

    /**
     * Consulta uma NFS-e pelo ID de integração.
     *
     * @return array<string, mixed>
     */
    public function consultarPorIdIntegracao(string $idIntegracao): array
    {
        return $this->client->get("/api/Nfse/id-integracao/{$idIntegracao}");
    }

    /**
     * Consulta NFS-e emitidas dentro de um período.
     *
     * @param string      $cpfCnpj        CPF ou CNPJ do prestador (obrigatório)
     * @param string      $inicio         Data de início (dd-MM-yyyy, obrigatório)
     * @param string      $fim            Data de fim (dd-MM-yyyy, obrigatório)
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

        return $this->client->get('/api/Nfse/consulta-periodo', $query);
    }

    /**
     * Consulta NFS-e por período para painel (visão resumida).
     *
     * @param string      $inicio         Data de início (dd-MM-yyyy, obrigatório)
     * @param string      $fim            Data de fim (dd-MM-yyyy, obrigatório)
     * @param string|null $businessId     Filtrar por businessId (opcional)
     * @param string|null $idAplicacao    Filtrar por aplicação (opcional)
     * @param string|null $tokenPaginacao Token de paginação (opcional)
     * @return array<string, mixed>
     */
    public function consultarPorPeriodoPainel(
        string $inicio,
        string $fim,
        ?string $businessId = null,
        ?string $idAplicacao = null,
        ?string $tokenPaginacao = null,
    ): array {
        $query = array_filter([
            'inicio'         => $inicio,
            'fim'            => $fim,
            'businessId'     => $businessId,
            'idAplicacao'    => $idAplicacao,
            'tokenPaginacao' => $tokenPaginacao,
        ]);

        return $this->client->get('/api/Nfse/consulta-periodo-painel', $query);
    }

    // -------------------------------------------------------------------------
    // Download de documentos
    // -------------------------------------------------------------------------

    /**
     * Baixa o XML de envio de uma NFS-e.
     */
    public function downloadXmlEnvio(string $id): string
    {
        return $this->client->getRaw("/api/Nfse/id/{$id}/xml-envio");
    }

    /**
     * Baixa o XML de retorno de uma NFS-e.
     */
    public function downloadXmlRetorno(string $id): string
    {
        return $this->client->getRaw("/api/Nfse/id/{$id}/xml-retorno");
    }

    /**
     * Baixa o PDF (DANFS-e) de uma NFS-e.
     */
    public function downloadPdf(string $idNota): string
    {
        return $this->client->getRaw("/api/Nfse/{$idNota}/pdf");
    }

    /**
     * Baixa o XML de um evento de NFS-e.
     */
    public function downloadEventoXml(string $idEvento): string
    {
        return $this->client->getRaw("/api/Nfse/evento/{$idEvento}/xml");
    }

    // -------------------------------------------------------------------------
    // Cancelamento
    // -------------------------------------------------------------------------

    /**
     * Cancela uma NFS-e.
     *
     * @param array<string, mixed> $data       Payload conforme CancelNfseRequest
     * @param string               $businessId ID do negócio (header obrigatório)
     * @return array<string, mixed>
     */
    public function cancelar(string $idNota, array $data, string $businessId): array
    {
        return $this->client->post("/api/Nfse/cancelar/{$idNota}", $data, headers: [
            'BusinessId' => $businessId,
        ]);
    }

    /**
     * Consulta o status de cancelamento de uma NFS-e.
     *
     * @return array<string, mixed>
     */
    public function consultarStatusCancelamento(string $idNota): array
    {
        return $this->client->get("/api/Nfse/cancelar/{$idNota}/consulta");
    }

    /**
     * Baixa o XML de cancelamento de uma NFS-e.
     */
    public function downloadXmlCancelamento(string $idNota): string
    {
        return $this->client->getRaw("/api/Nfse/cancelar/{$idNota}/xml");
    }
}
