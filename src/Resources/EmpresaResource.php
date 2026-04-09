<?php

namespace Emitte\DfeIob\Resources;

use Emitte\DfeIob\Exceptions\IobException;

/**
 * Recurso ADM - Empresa.
 *
 * Servidor: https://dev-dfe-adm.iob.com.br
 * Autenticação: Bearer token (OAuth 3 etapas via AdmAuthenticator)
 */
class EmpresaResource extends BaseResource
{
    // -------------------------------------------------------------------------
    // CRUD de Empresa
    // -------------------------------------------------------------------------

    /**
     * Cadastra uma nova empresa.
     *
     * @param array<string, mixed> $data Payload conforme AddEmpresaRequest
     * @return array<string, mixed>
     */
    public function criar(array $data): array
    {
        return $this->client->post('/api/Empresa', $data);
    }

    /**
     * Atualiza os dados de uma empresa.
     *
     * @param array<string, mixed> $data Dados da empresa
     * @return array<string, mixed>
     */
    public function atualizar(string $id, array $data): array
    {
        return $this->client->put("/api/Empresa/{$id}", $data);
    }

    /**
     * Lista todas as empresas paginadas.
     *
     * @param string      $idAplicacao    ID da aplicação (obrigatório na prática)
     * @param string|null $tokenPaginacao Token para próxima página
     * @return array<string, mixed>
     */
    public function listar(string $idAplicacao, ?string $tokenPaginacao = null): array
    {
        $query = array_filter([
            'idAplicacao'    => $idAplicacao,
            'tokenPaginacao' => $tokenPaginacao,
        ]);

        return $this->client->get('/api/Empresa/listar', $query);
    }

    /**
     * Busca uma empresa pelo CPF/CNPJ.
     *
     * @param string|null $idAplicacao Filtrar por aplicação
     * @return array<string, mixed>
     */
    public function buscarPorCpfCnpj(string $cpfCnpj, ?string $idAplicacao = null): array
    {
        $query = array_filter([
            'cpfCnpj'     => $cpfCnpj,
            'idAplicacao' => $idAplicacao,
        ]);

        return $this->client->get('/api/Empresa/buscar-por-cpf-cnpj', $query);
    }

    // -------------------------------------------------------------------------
    // Logotipo
    // -------------------------------------------------------------------------

    /**
     * Adiciona ou substitui o logotipo de uma empresa.
     *
     * O SDK detecta automaticamente a extensão da imagem pelos bytes do arquivo
     * e nomeia o arquivo como "{cpfCnpj}.{extensao}" (ex: 12345678000195.png).
     *
     * Formatos aceitos pela API: PNG, JPG, JPEG (máximo 200 KB).
     *
     * @param string          $cpfCnpj      CNPJ/CPF da empresa previamente cadastrada
     * @param string|resource $conteudo     Conteúdo binário da imagem ou file handle aberto
     * @param string          $idAplicacao  ID da aplicação onde a empresa foi cadastrada
     * @return array<string, mixed>
     */
    public function adicionarLogo(string $cpfCnpj, mixed $conteudo, string $idAplicacao): array
    {
        $bytes    = $this->lerBytes($conteudo);
        $extensao = $this->detectarExtensao($bytes);
        $filename = "{$cpfCnpj}.{$extensao}";

        return $this->client->postMultipart('/api/Empresa/adicionar-logo', [
            ['name' => 'cpfCnpj',     'contents' => $cpfCnpj],
            ['name' => 'idAplicacao', 'contents' => $idAplicacao],
            ['name' => 'logo',        'contents' => $bytes, 'filename' => $filename],
        ]);
    }

    /**
     * Remove o logotipo de uma empresa pelo CPF/CNPJ.
     *
     * @param string|null $idAplicacao Filtrar por aplicação
     * @return array<string, mixed>
     */
    public function removerLogo(string $cpfCnpj, ?string $idAplicacao = null): array
    {
        $query = array_filter(['idAplicacao' => $idAplicacao]);

        return $this->client->delete("/api/Empresa/remover-logo/{$cpfCnpj}", query: $query);
    }

    /**
     * Obtém a URL temporária do logotipo de uma empresa.
     *
     * @param string|null $idAplicacao Filtrar por aplicação
     * @return array<string, mixed>
     */
    public function baixarLogo(string $cpfCnpj, ?string $idAplicacao = null): array
    {
        $query = array_filter(['idAplicacao' => $idAplicacao]);

        return $this->client->get("/api/Empresa/baixar-logo/{$cpfCnpj}", $query);
    }

    // -------------------------------------------------------------------------
    // Helpers internos
    // -------------------------------------------------------------------------

    /**
     * Lê os bytes de um conteúdo que pode ser string ou resource.
     * Para resources, rebobina para o início antes de ler.
     *
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
                throw new IobException('Não foi possível ler o conteúdo do arquivo de logo.');
            }

            return $bytes;
        }

        throw new IobException('O parâmetro $conteudo deve ser string ou resource.');
    }

    /**
     * Detecta a extensão da imagem pelos magic bytes do arquivo.
     * Suporta PNG, JPG/JPEG. Lança exceção para formatos não suportados.
     */
    private function detectarExtensao(string $bytes): string
    {
        // PNG:  89 50 4E 47 0D 0A 1A 0A
        if (str_starts_with($bytes, "\x89PNG")) {
            return 'png';
        }

        // JPEG: FF D8 FF
        if (str_starts_with($bytes, "\xFF\xD8\xFF")) {
            return 'jpg';
        }

        throw new IobException(
            'Formato de imagem não suportado. A API aceita apenas PNG, JPG ou JPEG.',
        );
    }
}
