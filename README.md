# emitte/dfe-iob-sdk

SDK PHP para integração com a API **Motor de Documentos Fiscais da IOB**, com suporte nativo ao Laravel.

## Requisitos

- PHP 8.1+
- [Guzzle HTTP](https://github.com/guzzle/guzzle) `^7.0`
- Laravel 9+ *(opcional — funciona standalone)*

## Instalação

```bash
composer require emitte/dfe-iob-sdk
```

## Configuração

### Laravel

Publique o arquivo de configuração:

```bash
php artisan vendor:publish --tag=dfe-iob-config
```

Adicione as variáveis ao `.env`:

```env
# Módulos DFe (NFC-e, NF-e, NFS-e, CT-e)
IOB_API_KEY=sua-api-key

# Módulo ADM (autenticação OAuth 3 etapas via USM)
IOB_ADM_CLIENT_ID=seu-client-id
IOB_ADM_CLIENT_SECRET=seu-client-secret
IOB_ADM_X_API_KEY=sua-x-api-key-usm
IOB_ADM_USERNAME=seu-usuario
IOB_ADM_PASSWORD=sua-senha

# Opcional — store de cache para persistir tokens entre requisições
IOB_TOKEN_CACHE_STORE=redis
```

O `ServiceProvider` e a `Facade` são registrados automaticamente via package auto-discovery.

### PHP puro (standalone)

```php
use Emitte\DfeIob\DfeIobSdk;

$sdk = DfeIobSdk::make([
    'api_key' => 'sua-api-key',
    'adm' => [
        'client_id'     => 'seu-client-id',
        'client_secret' => 'seu-client-secret',
        'x_api_key'     => 'sua-x-api-key-usm',
        'username'      => 'seu-usuario',
        'password'      => 'sua-senha',
    ],
]);
```

---

## Autenticação

O SDK gerencia dois modelos de autenticação:

| Módulo | Mecanismo |
|--------|-----------|
| NFC-e, NF-e, NFS-e, CT-e | `x-api-key` no header (configurado uma vez no cliente) |
| ADM (Aplicação, Empresa, Certificado) | OAuth 3 etapas via servidor USM → Bearer token |

### Autenticação ADM — 3 etapas (automática)

O `AdmAuthenticator` executa e renova os tokens automaticamente:

1. `GET /oauth/signin/token` — Basic auth (clientId:clientSecret) → `signin_token`
2. `POST /oauth/signin` — credenciais + `signin_token` → `access_token` + `refresh_token`
3. `POST /oauth/refreshtoken` — renovação automática antes do token expirar

Em aplicações Laravel, os tokens são persistidos no cache configurado (`IOB_TOKEN_CACHE_STORE`), sobrevivendo entre requisições. Em uso standalone, vivem apenas na memória da execução.

---

## Servidores

| Módulo | URL (desenvolvimento) |
|--------|-----------------------|
| NFC-e / NF-e | `https://dev-dfe-nfe.iob.com.br` |
| NFS-e | `https://dev-dfe-nfse.iob.com.br` |
| CT-e | `https://dev-dfe-cte.iob.com.br` |
| ADM | `https://dev-dfe-adm.iob.com.br` |
| USM (auth) | `https://development-usm-svc-app.iob.com.br` |

Para produção, defina as URLs no `.env` sem o prefixo `dev-`/`development-`.

---

## Uso

### Via Facade (Laravel)

```php
use Emitte\DfeIob\Facades\DfeIob;
```

### Via injeção de dependência (Laravel)

```php
use Emitte\DfeIob\DfeIobSdk;

class NotaFiscalController extends Controller
{
    public function __construct(private readonly DfeIobSdk $iob) {}
}
```

---

## NF-e

```php
// Emitir
$resposta = DfeIob::nfe()->emitir(
    data:       $payload,  // AddNfeRequest
    businessId: 'SEU_BUSINESS_ID',
);
$idNota = $resposta['data']['id'];

// Consultar por ID
$nota = DfeIob::nfe()->consultarPorId($idNota, 'SUA_ID_APLICACAO', 'SEU_BUSINESS_ID');

// Consultar por ID de integração
$nota = DfeIob::nfe()->consultarPorIdIntegracao('meu-id', 'SUA_ID_APLICACAO', 'SEU_BUSINESS_ID');

// Consultar por período
$resultado = DfeIob::nfe()->consultarPorPeriodo(
    cpfCnpj:        '12345678000195',
    inicio:         '01-04-2025',
    fim:            '30-04-2025',
    tokenPaginacao: $resultado['data']['tokenPaginacao'] ?? null, // paginação
);

// Download XML
$xml = DfeIob::nfe()->downloadXml($idNota, 'SUA_ID_APLICACAO', 'SEU_BUSINESS_ID');

// Download PDF (DANFE)
$pdf = DfeIob::nfe()->downloadPdf($idNota, 'SUA_ID_APLICACAO', 'SEU_BUSINESS_ID');

// Cancelar
DfeIob::nfe()->cancelar($idNota, $payload, 'SEU_BUSINESS_ID');

// Carta de Correção (CC-e)
DfeIob::nfe()->solicitarCartaCorrecao($idNota, $payload, 'SEU_BUSINESS_ID');
DfeIob::nfe()->downloadXmlCartaCorrecao($idNota, 'SEU_BUSINESS_ID');
DfeIob::nfe()->downloadPdfCartaCorrecao($idNota, 'SEU_BUSINESS_ID');

// Inutilizar faixa de numeração
DfeIob::nfe()->inutilizar($payload, 'SEU_BUSINESS_ID');

// Insucesso de entrega
DfeIob::nfe()->registrarInsucessoEntrega($idNota, $payload);
```

---

## NFC-e

```php
// Emitir
$resposta = DfeIob::nfce()->emitir(
    data:       $payload,  // AddNfceRequest
    businessId: 'SEU_BUSINESS_ID',
);
$idNfce = $resposta['data']['id'];

// Consultar por ID
DfeIob::nfce()->consultarPorId($idNfce, 'SUA_ID_APLICACAO', 'SEU_BUSINESS_ID');

// Consultar por ID de integração
DfeIob::nfce()->consultarPorIdIntegracao('meu-id', 'SUA_ID_APLICACAO', 'SEU_BUSINESS_ID');

// Consultar por período
DfeIob::nfce()->consultarPorPeriodo('12345678000195', '01-04-2025', '30-04-2025');

// Download XML / PDF
$xml = DfeIob::nfce()->downloadXml($idNfce, 'SUA_ID_APLICACAO', 'SEU_BUSINESS_ID');
$pdf = DfeIob::nfce()->downloadPdf($idNfce, 'SUA_ID_APLICACAO', 'SEU_BUSINESS_ID');

// Cancelar
DfeIob::nfce()->cancelar($idNfce, $payload, 'SEU_BUSINESS_ID');

// Inutilizar
DfeIob::nfce()->inutilizar($payload, 'SEU_BUSINESS_ID');

// Validar payload sem emitir
DfeIob::nfce()->validar($payload, 'SEU_BUSINESS_ID', 'SEU_TENANT_ID');
```

---

## NFS-e

```php
// Emitir
$resposta = DfeIob::nfse()->emitir(
    data:       $payload,  // AddNfseRequest
    businessId: 'SEU_BUSINESS_ID',
);
$idNfse = $resposta['data']['id'];

// Consultar por ID / ID de integração
DfeIob::nfse()->consultarPorId($idNfse);
DfeIob::nfse()->consultarPorIdIntegracao('meu-id');

// Consultar por período
DfeIob::nfse()->consultarPorPeriodo('12345678000195', '01-04-2025', '30-04-2025');

// Download XML envio / retorno / PDF
DfeIob::nfse()->downloadXmlEnvio($idNfse);
DfeIob::nfse()->downloadXmlRetorno($idNfse);
DfeIob::nfse()->downloadPdf($idNfse);

// Cancelar
DfeIob::nfse()->cancelar($idNfse, $payload, 'SEU_BUSINESS_ID');
```

---

## CT-e

```php
// Emitir
$resposta = DfeIob::cte()->emitir(
    data:       $payload,  // AddCteRequest
    businessId: 'SEU_BUSINESS_ID',
);
$idCte = $resposta['data']['id'];

// Consultar por ID / ID de integração
DfeIob::cte()->consultarPorId($idCte, 'SEU_BUSINESS_ID');
DfeIob::cte()->consultarPorIdIntegracao('meu-id', 'SEU_BUSINESS_ID');

// Download XML / PDF
$xml = DfeIob::cte()->downloadXml($idCte, 'SEU_BUSINESS_ID');
$pdf = DfeIob::cte()->downloadPdf($idCte, 'SEU_BUSINESS_ID');

// Cancelar
DfeIob::cte()->cancelar($idCte, $payload, 'SEU_BUSINESS_ID');

// Carta de Correção (CC-e)
DfeIob::cte()->solicitarCartaCorrecao($idCte, $payload, 'SEU_BUSINESS_ID');
DfeIob::cte()->downloadXmlCartaCorrecao($idCte, 'SEU_BUSINESS_ID');
```

---

## ADM — Aplicação

```php
// CRUD
DfeIob::aplicacao()->listar();
DfeIob::aplicacao()->buscarPorId($idApp);
DfeIob::aplicacao()->criar(['nome' => 'Minha App', 'descricao' => '...']);
DfeIob::aplicacao()->atualizar(['id' => $idApp, 'nome' => 'Novo Nome']);
DfeIob::aplicacao()->remover($idApp);

// Webhook
DfeIob::aplicacao()->criarWebhook($idApp, ['url' => 'https://...', 'eventos' => [...]]);
DfeIob::aplicacao()->listarWebhooks($idApp);
DfeIob::aplicacao()->atualizarWebhook($idApp, ['url' => 'https://...']);
DfeIob::aplicacao()->removerWebhook($idApp);
```

---

## ADM — Empresa

```php
// CRUD
DfeIob::empresa()->criar($payload);
DfeIob::empresa()->atualizar($id, $payload);
DfeIob::empresa()->listar('SUA_ID_APLICACAO');
DfeIob::empresa()->buscarPorCpfCnpj('12345678000195', 'SUA_ID_APLICACAO');

// Logo
// O SDK detecta automaticamente a extensão (PNG/JPG) pelos bytes do arquivo
// e nomeia o arquivo como "{cnpj}.{extensao}" — ex: 12345678000195.png
DfeIob::empresa()->adicionarLogo(
    cpfCnpj:     '12345678000195',
    conteudo:    file_get_contents('/path/logo.png'), // ou fopen(...)
    idAplicacao: 'SUA_ID_APLICACAO',
);
DfeIob::empresa()->removerLogo('12345678000195', 'SUA_ID_APLICACAO');
DfeIob::empresa()->baixarLogo('12345678000195', 'SUA_ID_APLICACAO');
```

---

## ADM — Certificado Digital

```php
// O SDK detecta automaticamente o formato (PFX, PEM, P7B) pelos bytes
// e nomeia o arquivo como "certificado.{extensao}" — ex: certificado.pfx
// Formatos suportados: PFX, P12, CER, P7, P7B

DfeIob::certificado()->criar(
    businessId:       'SEU_BUSINESS_ID',
    idAplicacao:      'SUA_ID_APLICACAO',
    senhaCertificado: 'senha',
    conteudo:         file_get_contents('/path/cert.pfx'), // ou fopen(...)
    email:            'alertas@empresa.com.br', // opcional
);

DfeIob::certificado()->atualizar(
    businessId:       'SEU_BUSINESS_ID',
    idAplicacao:      'SUA_ID_APLICACAO',
    senhaCertificado: 'senha',
    conteudo:         file_get_contents('/path/novo.pfx'),
);

DfeIob::certificado()->listar('SUA_ID_APLICACAO');
DfeIob::certificado()->buscarPorId($idCertificado);
DfeIob::certificado()->remover($idCertificado);
```

---

## Tratamento de erros

```php
use Emitte\DfeIob\Exceptions\ApiException;
use Emitte\DfeIob\Exceptions\AuthenticationException;
use Emitte\DfeIob\Exceptions\IobException;

try {
    $resposta = DfeIob::nfe()->emitir(data: $payload, businessId: 'SEU_BUSINESS_ID');
} catch (AuthenticationException $e) {
    // Falha nas etapas OAuth do módulo ADM
} catch (ApiException $e) {
    $e->getStatusCode();   // código HTTP (400, 409, 500...)
    $e->getMessage();      // mensagem de erro
    $e->getResponseBody(); // body completo da resposta
} catch (IobException $e) {
    // Erros internos do SDK (ex: formato de arquivo não suportado)
}
```

---

## Estrutura do pacote

```
src/
├── Auth/
│   ├── AdmAuthenticator.php    # OAuth 3 etapas USM com auto-refresh
│   ├── TokenStore.php          # Interface de cache
│   ├── InMemoryTokenStore.php  # Para uso standalone
│   └── LaravelTokenStore.php   # Usa o cache do Laravel
├── Http/
│   └── HttpClient.php          # Wrapper Guzzle
├── Resources/
│   ├── NfceResource.php
│   ├── NfeResource.php
│   ├── NfseResource.php
│   ├── CteResource.php
│   ├── AplicacaoResource.php
│   ├── EmpresaResource.php
│   └── CertificadoResource.php
├── Exceptions/
│   ├── IobException.php
│   ├── AuthenticationException.php
│   └── ApiException.php
├── DfeIobSdk.php
├── DfeIobServiceProvider.php
└── Facades/DfeIob.php
config/
└── dfe-iob.php
```
