<?php

namespace Emitte\DfeIob\Resources;

use Emitte\DfeIob\Exceptions\IobException;

/**
 * Recurso ADM - Certificado Digital.
 *
 * Servidor: https://dev-dfe-adm.iob.com.br
 * Autenticação: Bearer token (OAuth 3 etapas via AdmAuthenticator)
 *
 * Formatos aceitos pela API: .PFX, .CER, .P12, .P7
 */
class CertificadoResource extends BaseResource
{
    /**
     * Cadastra um novo certificado digital para uma empresa.
     *
     * O SDK detecta automaticamente o formato do arquivo pelos seus bytes
     * e nomeia o arquivo como "certificado.{extensao}" (ex: certificado.pfx).
     *
     * Formatos detectados: PFX/P12 (DER), CER (PEM), P7B (PKCS#7 PEM).
     *
     * @param string          $businessId       ID da empresa previamente cadastrada
     * @param string          $idAplicacao      ID da aplicação onde a empresa foi cadastrada
     * @param string          $senhaCertificado Senha do certificado digital
     * @param string|resource $conteudo         Conteúdo binário do arquivo ou file handle
     * @param string|null     $email            E-mail para notificações de vencimento (opcional)
     * @return array<string, mixed>
     */
    public function criar(
        string $businessId,
        string $idAplicacao,
        string $senhaCertificado,
        mixed $conteudo,
        ?string $email = null,
    ): array {
        $bytes    = $this->lerBytes($conteudo);
        $extensao = $this->detectarExtensao($bytes);
        $filename = "certificado.{$extensao}";

        $multipart = [
            ['name' => 'businessId',       'contents' => $businessId],
            ['name' => 'idAplicacao',      'contents' => $idAplicacao],
            ['name' => 'senhaCertificado', 'contents' => $senhaCertificado],
            ['name' => 'certificado',      'contents' => $bytes, 'filename' => $filename],
        ];

        if ($email !== null) {
            $multipart[] = ['name' => 'email', 'contents' => $email];
        }

        return $this->client->postMultipart('/api/Certificado', $multipart);
    }

    /**
     * Atualiza (renova) um certificado digital existente.
     *
     * O SDK detecta automaticamente o formato do arquivo pelos seus bytes.
     *
     * @param string          $businessId       ID da empresa
     * @param string          $idAplicacao      ID da aplicação
     * @param string          $senhaCertificado Senha do novo certificado
     * @param string|resource $conteudo         Conteúdo binário do arquivo ou file handle
     * @param string|null     $email            E-mail para notificações de vencimento (opcional)
     * @return array<string, mixed>
     */
    public function atualizar(
        string $businessId,
        string $idAplicacao,
        string $senhaCertificado,
        mixed $conteudo,
        ?string $email = null,
    ): array {
        $bytes    = $this->lerBytes($conteudo);
        $extensao = $this->detectarExtensao($bytes);
        $filename = "certificado.{$extensao}";

        $multipart = [
            ['name' => 'businessId',       'contents' => $businessId],
            ['name' => 'idAplicacao',      'contents' => $idAplicacao],
            ['name' => 'senhaCertificado', 'contents' => $senhaCertificado],
            ['name' => 'certificado',      'contents' => $bytes, 'filename' => $filename],
        ];

        if ($email !== null) {
            $multipart[] = ['name' => 'email', 'contents' => $email];
        }

        return $this->client->putMultipart('/api/Certificado', $multipart);
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

    // -------------------------------------------------------------------------
    // Helpers internos
    // -------------------------------------------------------------------------

    /**
     * @param string|resource $conteudo
     */
    private function lerBytes(mixed $conteudo): string
    {
        if (is_string($conteudo)) {
            return $conteudo;
        }

        if (is_resource($conteudo)) {
            rewind($conteudo);
            $bytes = stream_get_contents($conteudo);

            if ($bytes === false) {
                throw new IobException('Não foi possível ler o conteúdo do arquivo de certificado.');
            }

            return $bytes;
        }

        throw new IobException('O parâmetro $conteudo deve ser string ou resource.');
    }

    /**
     * Detecta o formato do certificado pelos bytes do arquivo.
     *
     * Lógica de detecção:
     *  - PEM PKCS#7  (-----BEGIN PKCS7----- / -----BEGIN CMS-----)  → p7b
     *  - PEM X.509   (-----BEGIN CERTIFICATE-----)                   → cer
     *  - DER ASN.1   (\x30\x82 ...)
     *      · contém OID PKCS#12 (\x2a\x86\x48\x86\xf7\x0d\x01\x0c) → pfx
     *      · caso contrário                                           → cer
     *  - Formato não reconhecido                                      → lança exceção
     */
    private function detectarExtensao(string $bytes): string
    {
        // Arquivos PEM começam com "-----BEGIN"
        if (str_starts_with($bytes, '-----BEGIN')) {
            if (str_contains($bytes, 'BEGIN PKCS7') || str_contains($bytes, 'BEGIN CMS')) {
                return 'p7b';
            }

            return 'cer';
        }

        // Arquivos DER começam com a tag ASN.1 SEQUENCE (0x30)
        if (str_starts_with($bytes, "\x30")) {
            // OID PKCS#12 (1.2.840.113549.1.12) presente nos primeiros 64 bytes
            $cabecalho = substr($bytes, 0, 64);
            if (str_contains($cabecalho, "\x2a\x86\x48\x86\xf7\x0d\x01\x0c")) {
                return 'pfx';
            }

            return 'cer';
        }

        throw new IobException(
            'Formato de certificado não reconhecido. A API aceita PFX, P12, CER, P7 e P7B.',
        );
    }
}
