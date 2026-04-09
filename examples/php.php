<?php

/**
 * Exemplos de uso do SDK em PHP puro (sem Laravel).
 *
 * Pré-requisitos:
 *   composer require emitte/dfe-iob-sdk
 */

require __DIR__ . '/../vendor/autoload.php';

use Emitte\DfeIob\DfeIobSdk;
use Emitte\DfeIob\Exceptions\ApiException;
use Emitte\DfeIob\Exceptions\AuthenticationException;

// -----------------------------------------------------------------------------
// Configuração
// -----------------------------------------------------------------------------

$sdk = DfeIobSdk::make([
    // URLs (valores padrão já apontam para o ambiente de desenvolvimento)
    // 'dfe_nfe_base_url'  => 'https://dev-dfe.nfe.iob.com.br',
    // 'dfe_nfse_base_url' => 'https://dev-dfe-nfse.iob.com.br',
    // 'dfe_cte_base_url'  => 'https://dev-dfe-cte.iob.com.br',
    // 'adm_base_url'      => 'https://dev-dfe-adm.iob.com.br',
    // 'usm_base_url'      => 'https://development-usm-svc-app.iob.com.br',

    // API key usada nos módulos DFe (NFC-e, NF-e, NFS-e, CT-e)
    'api_key' => 'SUA_API_KEY_DFE',

    // Credenciais do módulo ADM (autenticação OAuth 3 etapas via USM)
    'adm' => [
        'client_id'     => 'IOB_ADM_CLIENT_ID',
        'client_secret' => 'SEU_CLIENT_SECRET',
        'x_api_key'     => 'IOB_ADM_X_API_KEY',
        'username'      => 'IOB_ADM_USERNAME',
        'password'      => 'IOB_ADM_PASSWORD',
    ],

    'http' => [
        'timeout'         => 30,
        'connect_timeout' => 10,
    ],
]);

// Em PHP puro o SDK usa InMemoryTokenStore por padrão:
// os tokens ADM vivem apenas durante a execução do script.

// -----------------------------------------------------------------------------
// NF-e — Emissão
// -----------------------------------------------------------------------------

try {
    $resposta = $sdk->nfe()->emitir([
        'idIntegracao' => 'pedido-001',
        'idAplicacao'  => 'SUA_ID_APLICACAO',
        'businessId'   => 'SEU_BUSINESS_ID',
        'emitente' => [
            'cpfCnpj' => '12345678000195',
            'nome'    => 'Empresa Exemplo Ltda',
            // ... demais campos do emitente
        ],
        'destinatario' => [
            'cpfCnpj' => '98765432100',
            'nome'    => 'João da Silva',
            // ... demais campos do destinatário
        ],
        'itens' => [
            [
                'numero'      => 1,
                'descricao'   => 'Produto Exemplo',
                'cfop'        => '5102',
                'quantidade'  => 1,
                'valorUnitario' => 100.00,
                // ... demais campos do item
            ],
        ],
        // ... demais campos da NF-e conforme schema AddNfeRequest
    ]);

    echo "NF-e enviada! ID: " . ($resposta['data']['id'] ?? 'n/d') . PHP_EOL;
} catch (ApiException $e) {
    echo "Erro HTTP {$e->getStatusCode()}: {$e->getMessage()}" . PHP_EOL;
    print_r($e->getResponseBody());
}

// -----------------------------------------------------------------------------
// NF-e — Consulta por ID
// -----------------------------------------------------------------------------

try {
    $nota = $sdk->nfe()->consultarPorId(
        id:           'ID_DA_NOTA',
        idAplicacao:  'SUA_ID_APLICACAO',
        businessId:   'SEU_BUSINESS_ID',
    );

    echo "Status da NF-e: " . ($nota['data']['status'] ?? 'n/d') . PHP_EOL;
} catch (ApiException $e) {
    echo "Erro ao consultar NF-e: {$e->getMessage()}" . PHP_EOL;
}

// -----------------------------------------------------------------------------
// NF-e — Consulta por ID de integração
// -----------------------------------------------------------------------------

try {
    $nota = $sdk->nfe()->consultarPorIdIntegracao(
        idIntegracao: 'pedido-001',
        idAplicacao:  'SUA_ID_APLICACAO',
        businessId:   'SEU_BUSINESS_ID',
    );

    echo "NF-e pelo ID integração: " . ($nota['data']['chaveNfe'] ?? 'n/d') . PHP_EOL;
} catch (ApiException $e) {
    echo "Erro: {$e->getMessage()}" . PHP_EOL;
}

// -----------------------------------------------------------------------------
// NF-e — Download de XML
// -----------------------------------------------------------------------------

try {
    $xml = $sdk->nfe()->downloadXml(
        idNota:      'ID_DA_NOTA',
        idAplicacao: 'SUA_ID_APLICACAO',
        businessId:  'SEU_BUSINESS_ID',
    );

    file_put_contents('/tmp/nfe.xml', $xml);
    echo "XML salvo em /tmp/nfe.xml" . PHP_EOL;
} catch (ApiException $e) {
    echo "Erro ao baixar XML: {$e->getMessage()}" . PHP_EOL;
}

// -----------------------------------------------------------------------------
// NF-e — Download de PDF (DANFE)
// -----------------------------------------------------------------------------

try {
    $pdf = $sdk->nfe()->downloadPdf(
        idNota:      'ID_DA_NOTA',
        idAplicacao: 'SUA_ID_APLICACAO',
        businessId:  'SEU_BUSINESS_ID',
    );

    file_put_contents('/tmp/danfe.pdf', $pdf);
    echo "PDF salvo em /tmp/danfe.pdf" . PHP_EOL;
} catch (ApiException $e) {
    echo "Erro ao baixar PDF: {$e->getMessage()}" . PHP_EOL;
}

// -----------------------------------------------------------------------------
// NF-e — Cancelamento
// -----------------------------------------------------------------------------

try {
    $resposta = $sdk->nfe()->cancelar('ID_DA_NOTA', [
        'idAplicacao' => 'SUA_ID_APLICACAO',
        'businessId'  => 'SEU_BUSINESS_ID',
        'justificativa' => 'Cancelamento solicitado pelo cliente.',
    ]);

    echo "Cancelamento solicitado: " . ($resposta['data'] ?? 'ok') . PHP_EOL;
} catch (ApiException $e) {
    echo "Erro ao cancelar: {$e->getMessage()}" . PHP_EOL;
}

// -----------------------------------------------------------------------------
// NF-e — Carta de Correção (CC-e)
// -----------------------------------------------------------------------------

try {
    $resposta = $sdk->nfe()->solicitarCartaCorrecao('ID_DA_NOTA', [
        'idAplicacao' => 'SUA_ID_APLICACAO',
        'businessId'  => 'SEU_BUSINESS_ID',
        'correcao'    => 'Corrigir endereço do destinatário: Rua das Flores, 123.',
        'sequencia'   => 1,
    ]);

    echo "CC-e solicitada!" . PHP_EOL;
} catch (ApiException $e) {
    echo "Erro na CC-e: {$e->getMessage()}" . PHP_EOL;
}

// -----------------------------------------------------------------------------
// NF-e — Consulta por período
// -----------------------------------------------------------------------------

try {
    $resultado = $sdk->nfe()->consultarPorPeriodo(
        cpfCnpj: '12345678000195',
        inicio:  '01-04-2025',
        fim:     '30-04-2025',
    );

    $notas = $resultado['data']['items'] ?? [];
    echo "Notas encontradas: " . count($notas) . PHP_EOL;

    // Paginação — se houver mais páginas:
    $tokenPaginacao = $resultado['data']['tokenPaginacao'] ?? null;
    if ($tokenPaginacao) {
        $pagina2 = $sdk->nfe()->consultarPorPeriodo(
            cpfCnpj:        '12345678000195',
            inicio:         '01-04-2025',
            fim:            '30-04-2025',
            tokenPaginacao: $tokenPaginacao,
        );
    }
} catch (ApiException $e) {
    echo "Erro na consulta por período: {$e->getMessage()}" . PHP_EOL;
}

// -----------------------------------------------------------------------------
// NFC-e — Emissão
// -----------------------------------------------------------------------------

try {
    $resposta = $sdk->nfce()->emitir([
        'idIntegracao' => 'venda-caixa-001',
        'idAplicacao'  => 'SUA_ID_APLICACAO',
        'businessId'   => 'SEU_BUSINESS_ID',
        // ... campos conforme schema AddNfceRequest
    ]);

    echo "NFC-e enviada! ID: " . ($resposta['data']['id'] ?? 'n/d') . PHP_EOL;
} catch (ApiException $e) {
    echo "Erro na NFC-e: {$e->getMessage()}" . PHP_EOL;
}

// -----------------------------------------------------------------------------
// NFC-e — Inutilização
// -----------------------------------------------------------------------------

try {
    $resposta = $sdk->nfce()->inutilizar([
        'idAplicacao' => 'SUA_ID_APLICACAO',
        'businessId'  => 'SEU_BUSINESS_ID',
        'cnpj'        => '12345678000195',
        'serie'       => 1,
        'numeroInicial' => 10,
        'numeroFinal'   => 15,
        'justificativa' => 'Números inutilizados por falha no sistema.',
    ]);

    echo "Inutilização: " . ($resposta['data']['status'] ?? 'ok') . PHP_EOL;
} catch (ApiException $e) {
    echo "Erro na inutilização: {$e->getMessage()}" . PHP_EOL;
}

// -----------------------------------------------------------------------------
// NFS-e — Emissão
// -----------------------------------------------------------------------------

try {
    $resposta = $sdk->nfse()->emitir([
        'idIntegracao' => 'servico-001',
        // ... campos conforme schema AddNfseRequest
    ]);

    echo "NFS-e enviada! ID: " . ($resposta['data']['id'] ?? 'n/d') . PHP_EOL;
} catch (ApiException $e) {
    echo "Erro na NFS-e: {$e->getMessage()}" . PHP_EOL;
}

// -----------------------------------------------------------------------------
// CT-e — Emissão
// -----------------------------------------------------------------------------

try {
    $resposta = $sdk->cte()->emitir([
        'idIntegracao' => 'transporte-001',
        // ... campos conforme schema AddCteRequest
    ]);

    echo "CT-e enviado! ID: " . ($resposta['data']['id'] ?? 'n/d') . PHP_EOL;
} catch (ApiException $e) {
    echo "Erro no CT-e: {$e->getMessage()}" . PHP_EOL;
}

// -----------------------------------------------------------------------------
// ADM — Aplicação
// (usa Bearer token OAuth 3 etapas, gerado e renovado automaticamente)
// -----------------------------------------------------------------------------

try {
    // Listar aplicações
    $apps = $sdk->aplicacao()->listar();
    echo "Aplicações: " . count($apps['data']['items'] ?? []) . PHP_EOL;

    // Criar aplicação
    $novaApp = $sdk->aplicacao()->criar([
        'nome'      => 'Minha Aplicação',
        'descricao' => 'Aplicação de testes',
    ]);
    $idApp = $novaApp['data']['id'] ?? null;
    echo "Aplicação criada: {$idApp}" . PHP_EOL;

    // Criar webhook para a aplicação
    $sdk->aplicacao()->criarWebhook($idApp, [
        'url'    => 'https://meusite.com.br/webhook/iob',
        'eventos' => ['nfe.autorizada', 'nfe.cancelada'],
    ]);
    echo "Webhook criado!" . PHP_EOL;
} catch (AuthenticationException $e) {
    echo "Erro de autenticação ADM: {$e->getMessage()}" . PHP_EOL;
} catch (ApiException $e) {
    echo "Erro ADM: {$e->getMessage()}" . PHP_EOL;
}

// -----------------------------------------------------------------------------
// ADM — Empresa
// -----------------------------------------------------------------------------

try {
    // Criar empresa
    $empresa = $sdk->empresa()->criar([
        'cpfCnpj'      => '12345678000195',
        'razaoSocial'  => 'Empresa Exemplo Ltda',
        'nomeFantasia' => 'Exemplo',
        'endereco' => [
            'logradouro' => 'Rua das Flores',
            'numero'     => '123',
            'cidade'     => 'São Paulo',
            'uf'         => 'SP',
            'cep'        => '01310100',
        ],
        // ... demais campos conforme AddEmpresaRequest
    ]);
    echo "Empresa criada: " . ($empresa['data']['id'] ?? 'n/d') . PHP_EOL;

    // Buscar empresa pelo CNPJ
    $busca = $sdk->empresa()->buscarPorCpfCnpj('12345678000195');
    echo "Empresa encontrada: " . ($busca['data']['razaoSocial'] ?? 'n/d') . PHP_EOL;

    // Upload do logotipo
    $logoConteudo = file_get_contents('/caminho/para/logo.png');
    $sdk->empresa()->adicionarLogo($logoConteudo, 'logo.png');
    echo "Logotipo enviado!" . PHP_EOL;
} catch (ApiException $e) {
    echo "Erro Empresa: {$e->getMessage()}" . PHP_EOL;
}

// -----------------------------------------------------------------------------
// ADM — Certificado Digital
// -----------------------------------------------------------------------------

try {
    // Listar certificados
    $certificados = $sdk->certificado()->listar();
    echo "Certificados: " . count($certificados['data']['items'] ?? []) . PHP_EOL;

    // Cadastrar certificado
    $cert = $sdk->certificado()->criar([
        'cpfCnpj'    => '12345678000195',
        'pfxBase64'  => base64_encode(file_get_contents('/caminho/certificado.pfx')),
        'senha'      => 'senha-do-certificado',
    ]);
    echo "Certificado cadastrado: " . ($cert['data']['id'] ?? 'n/d') . PHP_EOL;
} catch (ApiException $e) {
    echo "Erro Certificado: {$e->getMessage()}" . PHP_EOL;
}
