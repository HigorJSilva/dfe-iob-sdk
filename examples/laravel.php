<?php

/**
 * Exemplos de uso do SDK em Laravel.
 *
 * 1. Instalar o pacote:
 *      composer require emitte/dfe-iob-sdk
 *
 * 2. Publicar a configuração:
 *      php artisan vendor:publish --tag=dfe-iob-config
 *
 * 3. Definir as variáveis no .env:
 *
 *      IOB_API_KEY=SUA_API_KEY_DFE
 *
 *      IOB_ADM_CLIENT_ID=IOB_ADM_CLIENT_ID
 *      IOB_ADM_CLIENT_SECRET=SEU_CLIENT_SECRET
 *      IOB_ADM_X_API_KEY=IOB_ADM_X_API_KEY
 *      IOB_ADM_USERNAME=IOB_ADM_USERNAME
 *      IOB_ADM_PASSWORD=IOB_ADM_PASSWORD
 *
 *      # Opcional: store de cache para persistir tokens ADM entre requisições
 *      IOB_TOKEN_CACHE_STORE=redis
 *
 * O ServiceProvider é registrado automaticamente via package auto-discovery.
 */

// =============================================================================
// OPÇÃO A — Via Facade (recomendada para uso rápido)
// =============================================================================

use Emitte\DfeIob\Exceptions\ApiException;
use Emitte\DfeIob\Exceptions\AuthenticationException;
use Emitte\DfeIob\Facades\DfeIob;

// -----------------------------------------------------------------------------
// NF-e
// -----------------------------------------------------------------------------

// Emitir
$resposta = DfeIob::nfe()->emitir(
    data:       ['idIntegracao' => 'pedido-001', /* ... demais campos */],
    businessId: 'SEU_BUSINESS_ID',
);

$idNota = $resposta['data']['id'];

// Consultar por ID
$nota = DfeIob::nfe()->consultarPorId($idNota, 'SUA_ID_APLICACAO', 'SEU_BUSINESS_ID');

// Consultar por ID de integração
$nota = DfeIob::nfe()->consultarPorIdIntegracao('pedido-001', 'SUA_ID_APLICACAO', 'SEU_BUSINESS_ID');

// Consultar por período (com paginação)
$resultado = DfeIob::nfe()->consultarPorPeriodo(
    cpfCnpj: '12345678000195',
    inicio:  '01-04-2025',
    fim:     '30-04-2025',
);
$proximaPagina = $resultado['data']['tokenPaginacao'] ?? null;

// Download XML
$xml = DfeIob::nfe()->downloadXml($idNota, 'SUA_ID_APLICACAO', 'SEU_BUSINESS_ID');
return response($xml, 200)->header('Content-Type', 'application/xml');

// Download PDF (DANFE)
$pdf = DfeIob::nfe()->downloadPdf($idNota, 'SUA_ID_APLICACAO', 'SEU_BUSINESS_ID');
return response($pdf, 200)->header('Content-Type', 'application/pdf');

// Cancelar
DfeIob::nfe()->cancelar($idNota, [
    'idAplicacao'   => 'SUA_ID_APLICACAO',
    'justificativa' => 'Cancelamento a pedido do cliente.',
], 'SEU_BUSINESS_ID');

// Carta de Correção
DfeIob::nfe()->solicitarCartaCorrecao($idNota, [
    'idAplicacao' => 'SUA_ID_APLICACAO',
    'correcao'  => 'Corrigir endereço do destinatário.',
], 'SEU_BUSINESS_ID');

// Inutilizar faixa de numeração
DfeIob::nfe()->inutilizar([
    'idAplicacao'   => 'SUA_ID_APLICACAO',
    'businessId'    => 'SEU_BUSINESS_ID',
    'cnpj'          => '12345678000195',
    'ano'           => '26',
    'serie'         => 1,
    'numeroInicial' => 10,
    'numeroFinal'   => 15,
    'justificativa' => 'Falha no sistema.',
], 'SEU_BUSINESS_ID');

// Insucesso de entrega
DfeIob::nfe()->registrarInsucessoEntrega($idNota, [
    'idAplicacao' => 'SUA_ID_APLICACAO',
    'businessId'  => 'SEU_BUSINESS_ID',
    // ... campos conforme SolicitarInsucessoEntregaNfeRequest
]);

// -----------------------------------------------------------------------------
// NFC-e
// -----------------------------------------------------------------------------

// Emitir
$resposta = DfeIob::nfce()->emitir(
    data:       ['idIntegracao' => 'venda-001', /* ... campos conforme AddNfceRequest */],
    businessId: 'SEU_BUSINESS_ID',
);

// Validar payload sem emitir
$erros = DfeIob::nfce()->validar($payload, 'SEU_BUSINESS_ID', 'SEU_TENANT_ID');

// Consultar SEFAZ (pós-falha de comunicação)
DfeIob::nfce()->consultarSefaz([/* ConsultaPosFalhaComunicacaoSefazNfceRequest */]);

// Download PDF (DANFE NFC-e)
$pdf = DfeIob::nfce()->downloadPdf($idNota, 'SUA_ID_APLICACAO', 'SEU_BUSINESS_ID');

// -----------------------------------------------------------------------------
// NFS-e
// -----------------------------------------------------------------------------

$resposta = DfeIob::nfse()->emitir(
    data:       ['idIntegracao' => 'servico-001', /* ... campos conforme AddNfseRequest */],
    businessId: 'SEU_BUSINESS_ID',
);

$idNfse = $resposta['data']['id'];

DfeIob::nfse()->cancelar($idNfse, [/* CancelNfseRequest */]);

$xml = DfeIob::nfse()->downloadXmlRetorno($idNfse);
$pdf = DfeIob::nfse()->downloadPdf($idNfse);

// Consulta painel (visão resumida)
$painel = DfeIob::nfse()->consultarPorPeriodoPainel('12345678000195', '01-04-2025', '30-04-2025');

// -----------------------------------------------------------------------------
// CT-e
// -----------------------------------------------------------------------------

$resposta = DfeIob::cte()->emitir(
    data:       ['idIntegracao' => 'transporte-001', /* ... campos conforme AddCteRequest */],
    businessId: 'SEU_BUSINESS_ID',
);

$idCte = $resposta['data']['id'];

// Carta de Correção CT-e
DfeIob::cte()->solicitarCartaCorrecao($idCte, [/* CartaCorrecaoCteRequest */], 'SEU_BUSINESS_ID');

$xml = DfeIob::cte()->downloadXml($idCte, 'SEU_BUSINESS_ID');
$pdf = DfeIob::cte()->downloadPdf($idCte, 'SEU_BUSINESS_ID');

// -----------------------------------------------------------------------------
// ADM — Aplicação
// (token OAuth gerado e renovado automaticamente; persistido no cache Laravel)
// -----------------------------------------------------------------------------

// Listar
$apps = DfeIob::aplicacao()->listar();

// Criar
$app = DfeIob::aplicacao()->criar([
    'nome'      => 'Minha App',
    'descricao' => 'Integração fiscal',
]);
$idApp = $app['data']['id'];

// Atualizar
DfeIob::aplicacao()->atualizar([
    'id'        => $idApp,
    'nome'      => 'Minha App Atualizada',
    'descricao' => 'Integração fiscal v2',
]);

// Remover
DfeIob::aplicacao()->remover($idApp);

// Webhook
DfeIob::aplicacao()->criarWebhook($idApp, [
    'url'     => 'https://meusite.com.br/webhook/iob',
    'eventos' => ['nfe.autorizada', 'nfe.cancelada', 'nfce.autorizada'],
]);

DfeIob::aplicacao()->atualizarWebhook($idApp, [
    'url' => 'https://meusite.com.br/webhook/iob-v2',
]);

DfeIob::aplicacao()->removerWebhook($idApp);

// -----------------------------------------------------------------------------
// ADM — Empresa
// -----------------------------------------------------------------------------

// Criar empresa
$empresa = DfeIob::empresa()->criar([
    'cpfCnpj'     => '12345678000195',
    'razaoSocial' => 'Empresa Exemplo Ltda',
    'endereco'    => [
        'logradouro' => 'Rua das Flores',
        'numero'     => '123',
        'cidade'     => 'São Paulo',
        'uf'         => 'SP',
        'cep'        => '01310100',
    ],
    'nfe'  => [/* NfeEmpresaRequest */],
    'nfce' => [/* NfceEmpresaRequest */],
    // ... demais campos
]);

// Atualizar
DfeIob::empresa()->atualizar($empresa['data']['id'], [
    'nomeFantasia' => 'Exemplo',
]);

// Buscar por CNPJ
$busca = DfeIob::empresa()->buscarPorCpfCnpj('12345678000195');

// Listar com paginação
$lista = DfeIob::empresa()->listar(['pagina' => 1, 'tamanhoPagina' => 20]);

// Logo — usando o arquivo vindo de um upload Laravel
$logo = DfeIob::empresa()->adicionarLogo(
    'cnpj',
    request()->file('logo')->get(),
    'SUA_ID_APLICACAO',
);

// Ou a partir de um caminho local
DfeIob::empresa()->adicionarLogo(
    'cnpj',
    fopen(storage_path('app/logo.png'), 'r'),
    'SUA_ID_APLICACAO',
);

DfeIob::empresa()->removerLogo('12345678000195');

$urlLogo = DfeIob::empresa()->baixarLogo('12345678000195');

// -----------------------------------------------------------------------------
// ADM — Certificado Digital
// -----------------------------------------------------------------------------

// Listar
$certs = DfeIob::certificado()->listar();

// Cadastrar
$cert = DfeIob::certificado()->criar(
    'SEU_BUSINESS_ID',
    'SUA_ID_APLICACAO',
    config('app.cert_password'),
    Storage::get('certificados/empresa.pfx')
);

// Buscar
$cert = DfeIob::certificado()->buscarPorId('ID_DO_CERTIFICADO');

// Atualizar (renovação)
DfeIob::certificado()->atualizar(
    'SEU_BUSINESS_ID',
    'SUA_ID_APLICACAO',
    config('app.cert_password'),
    Storage::get('certificados/empresa.pfx')
);

// Remover
DfeIob::certificado()->remover('ID_DO_CERTIFICADO');

// =============================================================================
// OPÇÃO B — Via injeção de dependência no Controller
// =============================================================================

use Emitte\DfeIob\DfeIobSdk;
use Illuminate\Http\Request;

class NotaFiscalController extends Controller
{
    public function __construct(private readonly DfeIobSdk $iob)
    {
    }

    public function emitir(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $resposta = $this->iob->nfe()->emitir($request->validated());

            return response()->json([
                'id'     => $resposta['data']['id'],
                'status' => $resposta['data']['status'],
            ], 201);
        } catch (ApiException $e) {
            return response()->json([
                'erro'    => $e->getMessage(),
                'detalhes' => $e->getResponseBody(),
            ], $e->getStatusCode());
        }
    }

    public function downloadPdf(string $idNota, Request $request): \Illuminate\Http\Response
    {
        try {
            $pdf = $this->iob->nfe()->downloadPdf(
                $idNota,
                $request->header('idAplicacao'),
                $request->header('businessId'),
            );

            return response($pdf, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', "inline; filename=\"danfe-{$idNota}.pdf\"");
        } catch (ApiException $e) {
            abort($e->getStatusCode(), $e->getMessage());
        }
    }
}

// =============================================================================
// OPÇÃO C — Via helper app()
// =============================================================================

$sdk = app(DfeIobSdk::class);
$sdk->nfe()->emitir($payload);

// =============================================================================
// Tratamento de erros
// =============================================================================

try {
    $resposta = DfeIob::nfe()->emitir($payload, 'SEU_BUSINESS_ID');
} catch (AuthenticationException $e) {
    // Falha na autenticação ADM (etapas 1, 2 ou 3 do OAuth USM)
    Log::error('Falha na autenticação IOB ADM', ['erro' => $e->getMessage()]);
} catch (ApiException $e) {
    // Erro HTTP da API (4xx ou 5xx)
    Log::error('Erro na API IOB', [
        'status'   => $e->getStatusCode(),
        'mensagem' => $e->getMessage(),
        'body'     => $e->getResponseBody(),
    ]);

    if ($e->getStatusCode() === 409) {
        // Conflito — nota já emitida anteriormente com mesmo ID de integração
    }

    if ($e->getStatusCode() === 422) {
        // Erro de validação — verifique os campos do payload
    }
} catch (\Emitte\DfeIob\Exceptions\IobException $e) {
    // Base exception — captura qualquer erro do SDK
    Log::error('Erro SDK IOB', ['erro' => $e->getMessage()]);
}
