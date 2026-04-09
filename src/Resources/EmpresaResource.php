<?php

namespace Emitte\DfeIob\Resources;

use Emitte\DfeIob\Exceptions\ApiException;

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
     * @param string|null $idAplicacao    Filtrar por aplicação (obrigatório na prática)
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
     * @param resource|string $fileContents Conteúdo do arquivo de imagem
     * @param string          $filename     Nome do arquivo (ex: logo.png)
     * @return array<string, mixed>
     */
    public function adicionarLogo(mixed $fileContents, string $filename = 'logo.png'): array
    {
        return $this->client->postMultipart('/api/Empresa/adicionar-logo', [
            [
                'name'     => 'file',
                'contents' => $fileContents,
                'filename' => $filename,
            ],
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
}
