<?php

namespace Emitte\DfeIob\Facades;

use Emitte\DfeIob\DfeIobSdk;
use Emitte\DfeIob\Resources\AplicacaoResource;
use Emitte\DfeIob\Resources\CertificadoResource;
use Emitte\DfeIob\Resources\CteResource;
use Emitte\DfeIob\Resources\EmpresaResource;
use Emitte\DfeIob\Resources\NfceResource;
use Emitte\DfeIob\Resources\NfeResource;
use Emitte\DfeIob\Resources\NfseResource;
use Illuminate\Support\Facades\Facade;

/**
 * @method static NfceResource         nfce()
 * @method static NfeResource          nfe()
 * @method static NfseResource         nfse()
 * @method static CteResource          cte()
 * @method static AplicacaoResource    aplicacao()
 * @method static EmpresaResource      empresa()
 * @method static CertificadoResource  certificado()
 *
 * @see DfeIobSdk
 */
class DfeIob extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return DfeIobSdk::class;
    }
}
