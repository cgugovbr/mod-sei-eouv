<?php

/**
 * CONTROLADORIA GERAL DA UNI�O- CGU
 *
 * 09/10/2015 - criado por Rafael Leandro
 *
 */

error_reporting(E_ALL); ini_set('display_errors', '1');

require_once dirname(__FILE__) . '/../../../../SEI.php';

require_once dirname(__FILE__) . '/../util/MdCguEouvGerarPdfEsic.php';
require_once dirname(__FILE__) . '/../util/MdCguEouvGerarPdfInicial.php';

//header('Content-Type: text/html; charset=UTF-8');

class MdCguEouvAgendamentoRN extends InfraRN
{
    protected $urlWebServiceEOuv;
    protected $urlWebServiceESicRecursos;
    protected $urlWebServiceAnexosEOuv;
    protected $idTipoDocumentoAnexoDadosManifestacao;
    protected $idUnidadeOuvidoria;
    protected $idUnidadeEsicPrincipal;
    protected $idUnidadeRecursoPrimeiraInstancia;
    protected $idUnidadeRecursoSegundaInstancia;
    protected $idUnidadeRecursoTerceiraInstancia;
    protected $idUnidadeRecursoPedidoRevisao;
    protected $ocorreuErroEmProtocolo;
    protected $usuarioWebService;
    protected $senhaUsuarioWebService;
    protected $client_id;
    protected $client_secret;
    protected $token;
    protected $importar_dados_manifestante;
    protected $dataInicialImportacaoManifestacoes;
    protected $idRelatorioImportacao;
    protected $identificacaoServico = 'CadastrarManifestacao';
    protected $siglaSistema = 'EOUV';
    
    public function __construct()
    {
        parent::__construct();
        //ini_set('memory_limit', '1024M');
    }

    /**
     * @param MdCguEouvRelatorioImportacaoDTO $objEouvRelatorioImportacaoDTO
     * @param string $SinSucessoExecucao
     * @param string $textoMensagemFinal
     * @param MdCguEouvRelatorioImportacaoRN $objEouvRelatorioImportacaoRN
     * @return void
     */
    public function gravarRelatorioImportacaoSucesso(MdCguEouvRelatorioImportacaoDTO $objEouvRelatorioImportacaoDTO, string $SinSucessoExecucao, string $textoMensagemFinal, MdCguEouvRelatorioImportacaoRN $objEouvRelatorioImportacaoRN)
    {
        $objEouvRelatorioImportacaoDTO2 = new MdCguEouvRelatorioImportacaoDTO();

        $objEouvRelatorioImportacaoDTO2->setNumIdRelatorioImportacao($objEouvRelatorioImportacaoDTO->getNumIdRelatorioImportacao());
        $objEouvRelatorioImportacaoDTO2->setStrSinSucesso($SinSucessoExecucao);
        $objEouvRelatorioImportacaoDTO2->setStrDeLogProcessamento($textoMensagemFinal);
        $objEouvRelatorioImportacaoDTO2->setStrTipManifestacao('R');
        $objEouvRelatorioImportacaoRN->alterar($objEouvRelatorioImportacaoDTO2);
    }

    /**
     * @param MdCguEouvRelatorioImportacaoDTO $objEouvRelatorioImportacaoDTO
     * @param Exception $e
     * @param MdCguEouvRelatorioImportacaoRN $objEouvRelatorioImportacaoRN
     * @return void
     */
    public function gravarRelatorioImportacaoErro(MdCguEouvRelatorioImportacaoDTO $objEouvRelatorioImportacaoDTO, Exception $e, MdCguEouvRelatorioImportacaoRN $objEouvRelatorioImportacaoRN)
    {
        $objEouvRelatorioImportacaoDTO3 = new MdCguEouvRelatorioImportacaoDTO();
        $objEouvRelatorioImportacaoDTO3->setNumIdRelatorioImportacao($objEouvRelatorioImportacaoDTO->getNumIdRelatorioImportacao());
        $strMensagem = 'Ocorreu um erro no processamento:' . $e;
        $strMensagem = substr($strMensagem, 0, 500);
        $objEouvRelatorioImportacaoDTO3->setStrDeLogProcessamento($strMensagem);

        $objEouvRelatorioImportacaoRN->alterar($objEouvRelatorioImportacaoDTO3);
    }

    protected function inicializarObjInfraIBanco()
    {
        return BancoSEI::getInstance();
    }


    /**
     * Fun��o para importar as manifesta��es do FalaBr
     */
    public function executarImportacaoManifestacaoFalaBr()
    {

        $debugLocal = false;

        // Log
        LogSEI::getInstance()->gravar('Rotina de Importa��o de Manifesta��es do FalaBR (e-Sic)', InfraLog::$INFORMACAO);

        // Lista par�metros
        $objEouvParametroDTO = new MdCguEouvParametroDTO();
        $objEouvParametroDTO->retTodos();

        // Busca par�metros do banco de dados da tabela md_eouv_parametros
        $objEouvParametroRN = new MdCguEouvParametroRN();
        $arrObjEouvParametroDTO = $objEouvParametroRN->listarParametro($objEouvParametroDTO);
        $numRegistros = count($arrObjEouvParametroDTO);

        $this->preencheVariaveis($numRegistros, $arrObjEouvParametroDTO);

        /**
         * Fun��o para buscar o 'restante' do token sem o limite de 255 caracteres do SEI
         */
        $tokenPart2 = BancoSEI::getInstance()->consultarSql("select substring(de_valor_parametro, 256, 455) from md_eouv_parametros where no_parametro='TOKEN';")[0]['computed'];
        $this->token = $this->token . $tokenPart2;

        // Busca par�metros do banco de dados da tabela infra_parametros
        $objInfraParametro = new InfraParametro(BancoSEI::getInstance());
        $dataAtual = InfraData::getStrDataHoraAtual();

        $isBolHabilitada = SessaoSEI::getInstance(false)->isBolHabilitada();
        SessaoSEI::getInstance()->setBolHabilitada(false);

        // Simula login inicial
        self::simulaLogin($this->siglaSistema, $this->identificacaoServico, $this->idUnidadeEsicPrincipal);

        // Executa a importa��o dos dados
        try {

            //Retorna dados da �ltima execu��o com Sucesso
            $objUltimaExecucao = MdCguEouvAgendamentoINT::retornarUltimaExecucaoSucesso('R');

            if ($objUltimaExecucao != null) {
                // Debug Logs
                $debugLocal && LogSEI::getInstance()->gravar('$objUltimaExecuxao (e-Sic):' . $objUltimaExecucao->getDthDthPeriodoFinal());

                $ultimaDataExecucao = $objUltimaExecucao->getDthDthPeriodoFinal();
            } else {
                // Debug Logs
                $debugLocal && LogSEI::getInstance()->gravar('$objUltimaExecuxao (e-Sic) � NULL');

                //Primeira execu��o ou nenhuma executada com sucesso
                $ultimaDataExecucao = $this->dataInicialImportacaoManifestacoes;
            }

            $semManifestacoesEncontradas = true;
            $qtdManifestacoesNovas = 0;
            $qtdManifestacoesAntigas = 0;
            $semRecursosEncontrados = true;
            $qtdRecursosNovos = 0;

            /**
             * A fun��o abaixo gravarLogImportacao recebe o tipo de manifesta��o 'R' (Recursos) para as manifesta��es do e-Sic
             */

            $objEouvRelatorioImportacaoDTO = $this->gravarLogImportacao($ultimaDataExecucao, $dataAtual, 'R');
            $this->idRelatorioImportacao = $objEouvRelatorioImportacaoDTO->getNumIdRelatorioImportacao();
            $objEouvRelatorioImportacaoRN = new MdCguEouvRelatorioImportacaoRN();
            $SinSucessoExecucao = 'N';
            $textoMensagemErroToken = '';


            /**
             * As fun��es abaixo fazem a busca no webservice dos dados a serem trabalhados na rotina de importa��o
             */
            $debugLocal && LogSEI::getInstance()->gravar('Iniciando a consulta inicial');
            $retornoWs = $this->executarServicoConsultaManifestacoes($this->urlWebServiceEOuv, $this->token, $ultimaDataExecucao, $dataAtual, null, $this->idRelatorioImportacao);
            $retornoWsRecursos = $this->executarServicoConsultaRecursos($this->urlWebServiceESicRecursos, $this->token, $ultimaDataExecucao, $dataAtual, null, $this->idRelatorioImportacao);

            //Caso retornado algum erro - Manifesta��es e-Sic
            if (is_string($retornoWs)) {
                $debugLocal && LogSEI::getInstance()->gravar('Retorno da consulta $retornoWs � uma string: ' . $retornoWs);

                if (strpos($retornoWs, 'Invalidado') !== false) {
                    //Tenta gerar novo token
                    $tokenValido = MdCguEouvWS::apiValidarToken($this->urlWebServiceEOuv, $this->usuarioWebService, $this->senhaUsuarioWebService, $this->client_id, $this->client_secret);

                    if (isset($tokenValido['error'])) {
                        $textoMensagemErroToken = 'N�o foi poss�vel validar o Token de acesso aos WebServices do E-ouv. <br>Verifique as informa��es de Usu�rio, Senha, Client_Id e Client_Secret nas configura��es de Par�metros do M�dulo';

                    } elseif (isset($tokenValido['access_token'])) {
                        $this->gravarParametroToken($tokenValido['access_token']);
                        $this->token = $tokenValido['access_token'];

                        //Chama novamente a execu��o da ConsultaManifestacao que deu errado por causa do Token
                        $retornoWs = $this->executarServicoConsultaManifestacoes($this->urlWebServiceEOuv, $this->token, $ultimaDataExecucao, $dataAtual, null, $this->idRelatorioImportacao);
                        $retornoWsRecursos = $this->executarServicoConsultaRecursos($this->urlWebServiceESicRecursos, $this->token, $ultimaDataExecucao, $dataAtual, null, $this->idRelatorioImportacao);
                    }
                }
            }

            /**
             * @todo - criar rotina para buscar recursos das manifest��es com erro caso exista alguma na tabela de log
             */

            if ($textoMensagemErroToken == '') {

                /**
                 * @debug - manifestacao com erro
                 * Comentar a linha abaixo para debugar um retorno manual
                 */
                $debugLocal && LogSEI::getInstance()->gravar('Inicia busca manifesta��o com erros');
                $arrComErro = $this->obterManifestacoesComErro($this->urlWebServiceEOuv, $this->token, $ultimaDataExecucao, $dataAtual, $this->idRelatorioImportacao, 'R');

                $arrManifestacoes = array();

                if (is_array($retornoWs)) {
                    // Filtra as manifesta��es e-Sic
                    $arrManifestacoes = array_filter($retornoWs, function($manifestacao) {
                        $objMdCguEouvDeparaImportacaoDTO = new MdCguEouvDeparaImportacaoDTO();
                        $objMdCguEouvDeparaImportacaoDTO->retTodos();
                        $objMdCguEouvDeparaImportacaoDTO->setStrSinAtivo('S');

                        $objMdCguEouvDeparaImportacaoRN = new MdCguEouvDeparaImportacaoRN();
                        $arrObjMdCguEouvDeparaImportacaoDTO = $objMdCguEouvDeparaImportacaoRN->listar($objMdCguEouvDeparaImportacaoDTO);
                        $numRegistrosMdCguEouvDeparaImportacaoDTO = count($arrObjMdCguEouvDeparaImportacaoDTO);
                        $tiposValidos = array();
                        for ($i = 0; $i < $numRegistrosMdCguEouvDeparaImportacaoDTO; $i++) {
                            $idTipoManifestacaoEouv = $arrObjMdCguEouvDeparaImportacaoDTO[$i]->getNumIdTipoManifestacaoEouv();
                            $tiposValidos[] = $idTipoManifestacaoEouv;
                        }

                        return in_array($manifestacao['TipoManifestacao']['IdTipoManifestacao'], $tiposValidos);
                    });
                    $qtdManifestacoesNovas = count($arrManifestacoes);
                    $debugLocal && LogSEI::getInstance()->gravar('Possui novas manifesta��es qtd: ' . $qtdManifestacoesNovas);

                }

                $arrRecursos = array();
                if (isset($retornoWsRecursos) && is_array($retornoWsRecursos)) {
                    $debugLocal && LogSEI::getInstance()->gravar('Possui recursos qtd: ' . count($retornoWsRecursos['Recursos']));
                    $arrRecursos = $retornoWsRecursos['Recursos'];
                    $qtdRecursosNovos = count($arrRecursos);

                }

                if (is_array($arrComErro)) {
                    $debugLocal && LogSEI::getInstance()->gravar('Possui manifesta��es com erros - qtd: ' . count($arrComErro));
                    $qtdManifestacoesAntigas = count($arrComErro);
                    $arrManifestacoes = array_merge($arrManifestacoes, $arrComErro);

                }

                if (count($arrManifestacoes) > 0) {
                    $semManifestacoesEncontradas = false;
                    foreach ($arrManifestacoes as $retornoWsLinha) {
                        $debugLocal && LogSEI::getInstance()->gravar('Inicia importa��o por Linha');
                        $this->executarImportacaoLinha($retornoWsLinha);
                    }
                }
                // Importa recursos e-Sic
                if (count($arrRecursos) > 0) {
                    $semRecursosEncontrados = false;
                    foreach ($arrRecursos as $retornoWsLinha) {
                        $debugLocal && LogSEI::getInstance()->gravar('Inicia importa��o por linha de Recursos - protocolo: ' . $retornoWsLinha['numProtocolo']);
                        $this->executarImportacaoLinhaRecursos($retornoWsLinha);
                    }
                }

                $textoMensagemFinal = 'Execu��o Finalizada com Sucesso!';
                $SinSucessoExecucao = 'S';

                if ($semManifestacoesEncontradas) {
                    $textoMensagemFinal = $textoMensagemFinal . ' N�o foram encontradas manifesta��es para o per�odo.';
                } else {
                    $textoMensagemFinal = $textoMensagemFinal . '<br>Quantidade de Manifesta��es novas encontradas (FalaBr): ' . $qtdManifestacoesNovas . '<br>Quantidade de Manifesta��es encontadas que ocorreram erro em outras importa��es: ' . $qtdManifestacoesAntigas;
                }

                if ($semRecursosEncontrados) {
                    $textoMensagemFinal = $textoMensagemFinal . ' N�o foram encontrados recursos para o per�odo.';
                } else {
                    $textoMensagemFinal = $textoMensagemFinal . '<br>Quantidade de Recursos novos encontrados (e-Sic): ' . $qtdRecursosNovos;
                }

                if ($this->ocorreuErroEmProtocolo) {
                    $textoMensagemFinal = $textoMensagemFinal . '<br> Ocorreram erros em 1 ou mais protocolos.';
                }
            } else {
                $textoMensagemFinal = $textoMensagemErroToken;
            }

            //Grava a execu��o com sucesso se tiver corrido tudo bem
            $this->gravarRelatorioImportacaoSucesso($objEouvRelatorioImportacaoDTO, $SinSucessoExecucao, $textoMensagemFinal, $objEouvRelatorioImportacaoRN);

            LogSEI::getInstance()->gravar('Finalizado a import��o dos processos - FalaBR');

        } catch(Exception $e) {
            $this->gravarRelatorioImportacaoErro($objEouvRelatorioImportacaoDTO, $e, $objEouvRelatorioImportacaoRN);

            PaginaSEI::getInstance()->processarExcecao($e);

        } finally {
            //Restaura a Sess�o
            SessaoSEI::getInstance()->setBolHabilitada($isBolHabilitada);
        }
    }

    public function preencheVariaveis(int $numRegistros, $arrObjEouvParametroDTO)
    {
        // Preenche vari�veis locais com dados da tabela md_eouv_parametros
        if ($numRegistros > 0) {
            for ($i = 0; $i < $numRegistros; $i++) {

                $strParametroNome = $arrObjEouvParametroDTO[$i]->getStrNoParametro();

                switch ($strParametroNome) {

                    case "ESIC_DATA_INICIAL_IMPORTACAO_MANIFESTACOES":
                        $this->dataInicialImportacaoManifestacoes = $arrObjEouvParametroDTO[$i]->getStrDeValorParametro();
                        break;

                    case "ESIC_URL_WEBSERVICE_IMPORTACAO_RECURSOS":
                        $this->urlWebServiceESicRecursos = $arrObjEouvParametroDTO[$i]->getStrDeValorParametro();
                        break;

                    case "EOUV_ID_SERIE_DOCUMENTO_EXTERNO_DADOS_MANIFESTACAO":
                        $this->idTipoDocumentoAnexoDadosManifestacao = $arrObjEouvParametroDTO[$i]->getStrDeValorParametro();
                        break;

                    case "EOUV_USUARIO_ACESSO_WEBSERVICE":
                        $this->usuarioWebService = $arrObjEouvParametroDTO[$i]->getStrDeValorParametro();
                        break;

                    case "EOUV_SENHA_ACESSO_WEBSERVICE":
                        $this->senhaUsuarioWebService = $arrObjEouvParametroDTO[$i]->getStrDeValorParametro();
                        break;

                    case "CLIENT_ID":
                        $this->client_id = $arrObjEouvParametroDTO[$i]->getStrDeValorParametro();
                        break;

                    case "CLIENT_SECRET":
                        $this->client_secret = $arrObjEouvParametroDTO[$i]->getStrDeValorParametro();
                        break;

                    case "EOUV_URL_WEBSERVICE_IMPORTACAO_MANIFESTACAO":
                        $this->urlWebServiceEOuv = $arrObjEouvParametroDTO[$i]->getStrDeValorParametro();
                        break;

                    case "EOUV_URL_WEBSERVICE_IMPORTACAO_ANEXO_MANIFESTACAO":
                        $this->urlWebServiceAnexosEOuv = $arrObjEouvParametroDTO[$i]->getStrDeValorParametro();
                        break;

                    case "ESIC_ID_UNIDADE_PRINCIPAL":
                        $this->idUnidadeEsicPrincipal = $arrObjEouvParametroDTO[$i]->getStrDeValorParametro();
                        break;

                    case "ESIC_ID_UNIDADE_RECURSO_PRIMEIRA_INSTANCIA":
                        $this->idUnidadeRecursoPrimeiraInstancia = $arrObjEouvParametroDTO[$i]->getStrDeValorParametro();
                        break;

                    case "ESIC_ID_UNIDADE_RECURSO_SEGUNDA_INSTANCIA":
                        $this->idUnidadeRecursoSegundaInstancia = $arrObjEouvParametroDTO[$i]->getStrDeValorParametro();
                        break;

                    case "ESIC_ID_UNIDADE_RECURSO_TERCEIRA_INSTANCIA":
                        $this->idUnidadeRecursoTerceiraInstancia = $arrObjEouvParametroDTO[$i]->getStrDeValorParametro();
                        break;

                    case "ESIC_ID_UNIDADE_RECURSO_PEDIDO_REVISAO":
                        $this->idUnidadeRecursoPedidoRevisao = $arrObjEouvParametroDTO[$i]->getStrDeValorParametro();
                        break;

                    case "TOKEN":
                        $this->token = $arrObjEouvParametroDTO[$i]->getStrDeValorParametro();
                        break;

                    case "IMPORTAR_DADOS_MANIFESTANTE":
                        $this->importar_dados_manifestante = $arrObjEouvParametroDTO[$i]->getStrDeValorParametro();
                        break;

                    case "ID_UNIDADE_OUVIDORIA":
                        $this->idUnidadeOuvidoria = $arrObjEouvParametroDTO[$i]->getStrDeValorParametro();
                        break;

                }
            }
        }
    }

    public function gravarParametroToken($tokenGerado){

        $objEouvParametroDTO = new MdCguEouvParametroDTO();
        $objEouvParametroDTO -> setNumIdParametro(10);
        $objEouvParametroDTO -> setStrNoParametro('TOKEN');
        $objEouvParametroDTO -> setStrDeValorParametro($tokenGerado);

        $objEouvParametroRN = new MdCguEouvParametroRN();
        $objEouvParametroRN->alterarParametro($objEouvParametroDTO);

    }

    public function executarServicoConsultaManifestacoes($urlConsultaManifestacao, $token, $ultimaDataExecucao, $dataAtual, $numprotocolo = null, $numIdRelatorio = null)
    {
        $arrParametrosUrl = array(
            'dataCadastroInicio' => $ultimaDataExecucao,
            'dataCadastroFim' => $dataAtual,
            'numprotocolo' => $numprotocolo
        );

        $arrParametrosUrl = http_build_query($arrParametrosUrl);

        $urlConsultaManifestacao = $urlConsultaManifestacao . "?" . $arrParametrosUrl;

        $retornoWs = MdCguEouvWS::apiRestRequest($urlConsultaManifestacao, $token, 1);

        if (is_null($numprotocolo)) {
            // Verifica se retornou Token Invalido
            if (is_string($retornoWs)) {
                // Token expirado, necess�rio gerar novo Token
                if (strpos($retornoWs, 'Invalidado') !== false) {
                    return "Token Invalidado";
                }
            }
        } else {
            // Faz tratamento diferenciado para consulta por Protocolo espec�fico
            if(is_string($retornoWs)) {
                if (strpos($retornoWs, '404') !== false) {
                    $this->gravarLogLinha($this->formatarProcesso($numprotocolo), $numIdRelatorio, 'Nenhum retorno encontrado!', 'S');
                    $retornoWs = null;
                } elseif (strpos($retornoWs, 'Erro') !== false) {
                    $this->gravarLogLinha($this->formatarProcesso($numprotocolo), $numIdRelatorio, "Erro desconhecido" . $retornoWs, 'N');
                    throw new Exception($retornoWs);
                }
            }
        }

        return $retornoWs;
    }

    public function executarServicoConsultaRecursos($urlConsultaRecurso, $token, $ultimaDataExecucao = null, $dataAtual = null, $numprotocolo = null, $numIdRelatorio = null)
    {

        $debugLocal = false;
        $debugLocal && LogSEI::getInstance()->gravar('[executarServicoConsultaRecursos] Par�metros: $ultimaDataExecucao: ' . $ultimaDataExecucao . ' | $dataAtual: ' . $dataAtual . ' | $numprotocolo: ' . $numprotocolo);

        $arrParametrosUrl = array(
            'dataAberturaInicio' => $ultimaDataExecucao,
            'dataAberturaFim' => $dataAtual,
            'NumProtocolo' => $numprotocolo
        );

        $arrParametrosUrl = http_build_query($arrParametrosUrl);

        $urlConsultaRecurso = $urlConsultaRecurso . "?" . $arrParametrosUrl;

        $retornoWs = MdCguEouvWS::apiRestRequest($urlConsultaRecurso, $token, 1);

        if (is_null($numprotocolo)) {
            //Verifica se retornou Token Invalido
            if (is_string($retornoWs)) {
                if (strpos($retornoWs, 'Invalidado') !== false) {
                    //Token expirado, necessário gerar novo Token
                    return "Token Invalidado";
                }
            }
        } else {
            //Faz tratamento diferenciado para consulta por Protocolo espec�fico
            if(is_string($retornoWs)) {
                if (strpos($retornoWs, 'Erro') !== false) {
                    if (strpos($retornoWs, '404') !== false) {
                        $this->gravarLogLinha($this->formatarProcesso($numprotocolo), $numIdRelatorio, "Usu�rio n�o possui permiss�o de acesso neste protocolo.", 'N');
                        $retornoWs = null;
                    } else {
                        $this->gravarLogLinha($this->formatarProcesso($numprotocolo), $numIdRelatorio, "Erro desconhecido" . $retornoWs, 'N');
                        throw new Exception($retornoWs);
                    }
                }
            }
        }

        return $retornoWs;
    }

    public function criarNovoProcesso($idTipoManifestacaoSei, $idTipoManifestacao, bool $manifestacaoESic, $arrDetalheManifestacao, $idUnidadeDestino, string $numProtocoloFormatado, $tipoManifestacao, $arrRecursosManifestacao)
    {
        /**
         * Inicia cria��o do Procedimento de cria��o de novo Processo
         */
        try {
            $objTipoProcedimentoDTO = new TipoProcedimentoDTO();
            $objTipoProcedimentoDTO->retNumIdTipoProcedimento();
            $objTipoProcedimentoDTO->retStrNome();
            $objTipoProcedimentoDTO->retStrStaNivelAcessoSugestao();
            $objTipoProcedimentoDTO->retStrStaGrauSigiloSugestao();
            $objTipoProcedimentoDTO->retStrSinIndividual();
            $objTipoProcedimentoDTO->retNumIdHipoteseLegalSugestao();
            $objTipoProcedimentoDTO->setNumIdTipoProcedimento($idTipoManifestacaoSei);

            $objTipoProcedimentoRN = new TipoProcedimentoRN();
            $objTipoProcedimentoDTO = $objTipoProcedimentoRN->consultarRN0267($objTipoProcedimentoDTO);

            if ($objTipoProcedimentoDTO == null) {
                throw new Exception('Tipo de processo n�o encontrado: ' . $idTipoManifestacaoSei);
            }

            /*
             * Verifica se deve importar documentos tipo 8 (e-Sic)
             */
            if ($idTipoManifestacao == 8 && !$manifestacaoESic) {
                return;
            }

            $objProcedimentoAPI = new ProcedimentoAPI();
            $objProcedimentoAPI->setIdTipoProcedimento($objTipoProcedimentoDTO->getNumIdTipoProcedimento());

            $varEspecificacaoAssunto = "";

            if (is_array($arrDetalheManifestacao['Assunto'])) {
                $varEspecificacaoAssunto = $arrDetalheManifestacao['Assunto']['DescAssunto'];
            }
            if (is_array($arrDetalheManifestacao['SubAssunto'])) {
                $varEspecificacaoAssunto = $varEspecificacaoAssunto . " / " . $arrDetalheManifestacao['SubAssunto']['DescSubAssunto'];
            }

            $objProcedimentoAPI->setEspecificacao($varEspecificacaoAssunto);
            $objProcedimentoAPI->setIdUnidadeGeradora($idUnidadeDestino);
            $objProcedimentoAPI->setNumeroProtocolo($numProtocoloFormatado);
            $objProcedimentoAPI->setDataAutuacao($arrDetalheManifestacao['DataCadastro']);
            $objProcedimentoAPI->setNivelAcesso($objTipoProcedimentoDTO->getStrStaNivelAcessoSugestao());
            $objProcedimentoAPI->setGrauSigilo($objTipoProcedimentoDTO->getStrStaGrauSigiloSugestao());
            $objProcedimentoAPI->setIdHipoteseLegal($objTipoProcedimentoDTO->getNumIdHipoteseLegalSugestao());
            $objProcedimentoAPI->setObservacao("Processo Gerado Automaticamente pela Integra��o SEI x FalaBR");

            $objEntradaGerarProcedimentoAPI = new EntradaGerarProcedimentoAPI();
            $objEntradaGerarProcedimentoAPI->setProcedimento($objProcedimentoAPI);

            $objSaidaGerarProcedimentoAPI = new SaidaGerarProcedimentoAPI();

            $objSeiRN = new SeiRN();

            $arrDocumentos = $this->gerarAnexosProtocolo($arrDetalheManifestacao['Teor']['Anexos'], $numProtocoloFormatado, $tipoManifestacao);

            /**
             * Verificar o tipo de documento a ser importado para gerar o PDF conforme tipo de documento
             */
            if ($manifestacaoESic) {
                $documentoManifestacao = $this->gerarPDFDocumentoESic($arrDetalheManifestacao, $arrRecursosManifestacao);
            } else {
                $documentoManifestacao = $this->gerarPDFPedidoInicial($arrDetalheManifestacao);
            }

            LogSEI::getInstance()->gravar('Importa��o de Manifesta��o ' . $numProtocoloFormatado . ': total de  Anexos configurados: ' . count($arrDocumentos), InfraLog::$INFORMACAO);

            array_unshift($arrDocumentos, $documentoManifestacao);
            $objEntradaGerarProcedimentoAPI->setDocumentos($arrDocumentos);
            $objSaidaGerarProcedimentoAPI = $objSeiRN->gerarProcedimento($objEntradaGerarProcedimentoAPI);
            $this->gravarLogLinha($numProtocoloFormatado, $this->idRelatorioImportacao, 'Protocolo ' . $arrDetalheManifestacao['numProtocolo'] . ' gravado com sucesso.', 'S', $tipoManifestacao);

        } catch (Exception $e) {

            if ($objSaidaGerarProcedimentoAPI != null and $objSaidaGerarProcedimentoAPI->getIdProcedimento() > 0) {
                $this->excluirProcessoComErro($objSaidaGerarProcedimentoAPI->getIdProcedimento());
            }
            $this->gravarLogLinha($numProtocoloFormatado, $this->idRelatorioImportacao, 'Erro na grava��o: ' . $e, 'N', $tipoManifestacao);
        }
        return $arrDocumentos;
    }

    public function getUnidadeDestino(string $tipo_recurso)
    {
        // Vincular Recursos com as unidades corretas conforme o tipo de recurso
        // Se for 1 inst�ncia envia processo para ESIC_ID_UNIDADE_RECURSO_PRIMEIRA_INSTANCIA
        if ($tipo_recurso == 'R1') {
            $unidadeDestino = $this->idUnidadeRecursoPrimeiraInstancia;
        } elseif ($tipo_recurso == 'R2') {
            $unidadeDestino = $this->idUnidadeRecursoSegundaInstancia;
        } elseif ($tipo_recurso == 'R3' || $tipo_recurso == 'RC') {
            $unidadeDestino = $this->idUnidadeRecursoTerceiraInstancia;
        } elseif ($tipo_recurso == 'PR') {
            $unidadeDestino = $this->idUnidadeRecursoPedidoRevisao;
        } else {
            $unidadeDestino = $this->idUnidadeOuvidoria;
        }
        return $unidadeDestino;
    }

    // GZIP DECODE
    function gzdecode($data)
    {

        return gzinflate(substr($data, 10, -8));
    }

    public function gravarLogImportacao($ultimaDataExecucao, $dataAtual, $tipoManifestacao = 'P'){

        try {
            $objEouvRelatorioImportacaoDTO = new MdCguEouvRelatorioImportacaoDTO();

            $objEouvRelatorioImportacaoDTO->retNumIdRelatorioImportacao();
            $objEouvRelatorioImportacaoDTO->setNumIdRelatorioImportacao(null);
            $objEouvRelatorioImportacaoDTO->setDthDthImportacao(InfraData::getStrDataHoraAtual());
            $objEouvRelatorioImportacaoDTO->setDthDthPeriodoInicial($ultimaDataExecucao);
            $objEouvRelatorioImportacaoDTO->setDthDthPeriodoFinal($dataAtual);
            $objEouvRelatorioImportacaoDTO->setStrDeLogProcessamento('Passo 1 - Iniciando processamento.');
            $objEouvRelatorioImportacaoDTO->setStrSinSucesso('N');
            $objEouvRelatorioImportacaoDTO->setStrTipManifestacao($tipoManifestacao);

            $objEouvRelatorioImportacaoRN = new MdCguEouvRelatorioImportacaoRN();
            $objEouvRelatorioImportacaoRN->cadastrar($objEouvRelatorioImportacaoDTO);

            return $objEouvRelatorioImportacaoDTO;

        }catch (Exception $e) {
            PaginaInfra::getInstance()->processarExcecao($e);
        }

    }

    /**
     * Grava log detalhado
     */
    public function gravarLogLinha($numProtocolo, $idRelatorioImportacao, $mensagem, $sinSucesso, $tipoManifestacao = 'P', $dataPrazoAtendimento = null)
    {
        $objEouvRelatorioImportacaoDetalheDTO = new MdCguEouvRelatorioImportacaoDetalheDTO();
        $objEouvRelatorioImportacaoDetalheDTO->retStrProtocoloFormatado();
        $objEouvRelatorioImportacaoDetalheDTO->setNumIdRelatorioImportacao($idRelatorioImportacao);
        $objEouvRelatorioImportacaoDetalheDTO->setStrProtocoloFormatado($numProtocolo);

        $objEouvRelatorioImportacaoDetalheRN = new MdCguEouvRelatorioImportacaoDetalheRN();
        $objExisteDetalheDTO = $objEouvRelatorioImportacaoDetalheRN->consultar($objEouvRelatorioImportacaoDetalheDTO);

        $objEouvRelatorioImportacaoDetalheDTO->setStrSinSucesso($sinSucesso);
        $objEouvRelatorioImportacaoDetalheDTO->setStrTipManifestacao($tipoManifestacao);
        $objEouvRelatorioImportacaoDetalheDTO->setStrDescricaoLog(substr($mensagem,0,254));
        $objEouvRelatorioImportacaoDetalheDTO->setDthDthImportacao(InfraData::getStrDataHoraAtual());
        $objEouvRelatorioImportacaoDetalheDTO->setDthDthPrazoAtendimento($dataPrazoAtendimento);

        if ($objExisteDetalheDTO==null) {
            $objEouvRelatorioImportacaoDetalheRN->cadastrar($objEouvRelatorioImportacaoDetalheDTO);
        } else {
            $objEouvRelatorioImportacaoDetalheRN->alterar($objEouvRelatorioImportacaoDetalheDTO);
        }
    }

    public function obterManifestacoesComErro($urlConsultaManifestacao, $token, $ultimaDataExecucao, $dataAtual, $numIdRelatorio, $TipoManifestacao = 'P')
    {
        $objEouvRelatorioImportacaoDetalheDTO = new MdCguEouvRelatorioImportacaoDetalheDTO();
        $objEouvRelatorioImportacaoDetalheDTO->retStrProtocoloFormatado();
        $objEouvRelatorioImportacaoDetalheDTO->setStrSinSucesso('N');
        $objEouvRelatorioImportacaoDetalheDTO->setStrTipManifestacao($TipoManifestacao);

        $objEouvRelatorioImportacaoDetalheRN = new MdCguEouvRelatorioImportacaoDetalheRN();
        $objListaErros = $objEouvRelatorioImportacaoDetalheRN->listar($objEouvRelatorioImportacaoDetalheDTO);

        $arrResult = array();
        $arrProtocolos = array();

        foreach($objListaErros as $erro) {

            $numProtocolo = preg_replace("/[^0-9]/", "", $erro->getStrProtocoloFormatado());

            //Se j� estiver na lista n�o faz novamente para determinado protocolo
            if (!in_array($numProtocolo, $arrProtocolos)){

                //Adiciona no array de Protocolos
                array_push($arrProtocolos, $numProtocolo);

                $retornoWsErro = $this->executarServicoConsultaManifestacoes($urlConsultaManifestacao, $token, null, $dataAtual, $numProtocolo, $numIdRelatorio);

                if (!is_null($retornoWsErro) && $retornoWsErro <> ''){
                    $arrResult = array_merge($arrResult, $retornoWsErro);
                }
            }
        }

        return $arrResult;
    }

    private static function obterServico($SiglaSistema, $IdentificacaoServico){

        $objUsuarioDTO = new UsuarioDTO();
        $objUsuarioDTO->retNumIdUsuario();
        $objUsuarioDTO->setStrSigla($SiglaSistema);
        $objUsuarioDTO->setStrStaTipo(UsuarioRN::$TU_SISTEMA);

        $objUsuarioRN = new UsuarioRN();
        $objUsuarioDTO = $objUsuarioRN->consultarRN0489($objUsuarioDTO);

        if ($objUsuarioDTO==null){
            throw new InfraException('Sistema ['.$SiglaSistema.'] n�o encontrado.');
        }

        $objServicoDTO = new ServicoDTO();
        $objServicoDTO->retNumIdServico();
        $objServicoDTO->retStrIdentificacao();
        $objServicoDTO->retStrSiglaUsuario();
        $objServicoDTO->retNumIdUsuario();
        $objServicoDTO->retStrServidor();
        $objServicoDTO->retStrSinLinkExterno();
        $objServicoDTO->retNumIdContatoUsuario();
        $objServicoDTO->setNumIdUsuario($objUsuarioDTO->getNumIdUsuario());
        $objServicoDTO->setStrIdentificacao($IdentificacaoServico);

        $objServicoRN = new ServicoRN();
        $objServicoDTO = $objServicoRN->consultar($objServicoDTO);

        if ($objServicoDTO==null){
            throw new InfraException('Servi�o ['.$IdentificacaoServico.'] do sistema ['.$SiglaSistema.'] n�o encontrado.');
        }

        return $objServicoDTO;
    }

    private static function obterUnidade($IdUnidade, $SiglaUnidade){

        $objUnidadeDTO = new UnidadeDTO();
        $objUnidadeDTO->retNumIdUnidade();
        $objUnidadeDTO->retStrSigla();
        $objUnidadeDTO->retStrDescricao();

        if($IdUnidade!=null) {
            $objUnidadeDTO->setNumIdUnidade($IdUnidade);
        }
        if($SiglaUnidade!=null){
            $objUnidadeDTO->setStrSigla($SiglaUnidade);
        }

        $objUnidadeRN = new UnidadeRN();
        $objUnidadeDTO = $objUnidadeRN->consultarRN0125($objUnidadeDTO);

        if ($objUnidadeDTO==null){
            throw new InfraException('Unidade ['.$IdUnidade.'] n�o encontrada.');
        }

        return $objUnidadeDTO;
    }

    public function executarImportacaoLinha($retornoWsLinha)
    {
        $debugLocal = false;

        $objProcedimentoDTO = new ProcedimentoDTO();
        $objProcedimentoDTO->setDblIdProcedimento(null);

        $linkDetalheManifestacao = $retornoWsLinha['Links'][0]['href'];
        $arrDetalheManifestacao = MdCguEouvWS::apiRestRequest($linkDetalheManifestacao, $this->token, 2);

        /**
         * Verifica Tipo de Manifesta��o e-Ouv ou e-Sic
         */
        if ($retornoWsLinha['TipoManifestacao']['IdTipoManifestacao'] <> 8) {
            $debugLocal && LogSEI::getInstance()->gravar('Importa��o tipo "P" - tipoManifesta��o <> "8"');
            $tipoManifestacao = 'P';
            $manifestacaoESic = false;
            $idUnidadeDestino = $this->idUnidadeOuvidoria;
        } elseif ($retornoWsLinha['TipoManifestacao']['IdTipoManifestacao'] == 8) {
            $debugLocal && LogSEI::getInstance()->gravar('Importa��o tipo "R" - tipoManifesta��o == "8"');
            $tipoManifestacao = 'R';
            $manifestacaoESic = true;
            $idUnidadeDestino = $this->idUnidadeEsicPrincipal;

            /**
             * Importar Recursos caso seja manifesta��o e-Sic (Tipo 8)
             */
            $arrRecursosManifestacao = MdCguEouvWS::apiRestRequest($this->urlWebServiceESicRecursos . '?NumProtocolo=' . $arrDetalheManifestacao['NumerosProtocolo'][0], $this->token, 2);
        }

        $numProtocoloFormatado =  $this->formatarProcesso($arrDetalheManifestacao['NumerosProtocolo'][0]);

        // Verifica se o tipo de manifesta��o � suportado
        $numIdTipoManifestacao = $retornoWsLinha['TipoManifestacao']['IdTipoManifestacao'];
        if ($numIdTipoManifestacao > 8) {
            // Se n�o for marca como sucesso para evitar reimporta��o na pr�xima execu��o.
            $this->gravarLogLinha($numProtocoloFormatado, $this->idRelatorioImportacao,
                'Tipo de manifesta��o n�o suportado (ID = '.$numIdTipoManifestacao.'). N�o ser� importada.', 'S');
            return;
        }


        /**
         * Esta data � gravada na tabela de log detalhada
         * Em caso de altera��o no prazo do atendimento ser� feita nova importa��o dos dados do recurso
         * Verifica se o retorno dos recursos n�o � uma string
         */
        if ($arrRecursosManifestacao <> '' && !is_string($arrRecursosManifestacao)) {
            $debugLocal && LogSEI::getInstance()->gravar('Possui $arrRecursosManifestacao - qtd: ' . count($arrRecursosManifestacao['Recursos']));
            $dataPrazoAtendimento = $arrRecursosManifestacao['Recursos'][(count($arrRecursosManifestacao['Recursos']) - 1)]['prazoAtendimento'];
        } else {
            $debugLocal && LogSEI::getInstance()->gravar('N�O possui $arrRecursosManifestacao');
            $dataPrazoAtendimento = $retornoWsLinha['PrazoAtendimento'];
        }

        /**
         * Limpa os registros de detalhe de importa��o com erro para este NUP.
         * Caso ocorra um novo, ser� criado novo registro de erro para o NUP no tratamento desta function.
         */
        $this->limparErrosParaNup($numProtocoloFormatado);

        if (!isset($arrDetalheManifestacao['TipoManifestacao']['IdTipoManifestacao'])) {
            $this->gravarLogLinha($numProtocoloFormatado, $this->idRelatorioImportacao, 'Tipo de processo n�o foi informado.', 'N');
            /**
             * @todo - n�o deveria parara aqui? se n�o tiver um tipo de processo n�o informado?
             */
        } else {
            $objEouvDeparaImportacaoDTO = new MdCguEouvDeparaImportacaoDTO();
            $objEouvDeparaImportacaoDTO->retNumIdTipoProcedimento();
            $objEouvDeparaImportacaoDTO->setNumIdTipoManifestacaoEouv($arrDetalheManifestacao['TipoManifestacao']['IdTipoManifestacao']);

            $objEouvDeparaImportacaoRN = new MdCguEouvDeparaImportacaoRN();
            $objEouvDeparaImportacaoDTO = $objEouvDeparaImportacaoRN->consultar($objEouvDeparaImportacaoDTO);

            if (!$objEouvDeparaImportacaoDTO == null) {
                $idTipoManifestacaoSei = $objEouvDeparaImportacaoDTO->getNumIdTipoProcedimento();
            } else {
                $this->gravarLogLinha($numProtocoloFormatado, $this->objEouvRelatorioImportacaoDTO->getNumIdRelatorioImportacao(), 'N�o existe mapeamento DePara do Tipo de Manifesta��o do FalaBR (E-Ouv|E-Sic) para o tipo de procedimento do SEI.', 'N');
                //continue;
            }
        }

        /**
         * Se for Manifesta��o do e-Sic verificar:houve altera��o na data 'PrazoAtendimento' e
         * gera novo arquivo PDF com as altera��es para inser��o no mesmo protocolo (NUP) e
         * importa anexos comparando o hash do arquivo para n�o duplicidade no processo
         */
        // Vefificar se o NUP j� existe
        $objProtocoloDTOExistente = $this->verificarProtocoloExistente($this->formatarProcesso($numProtocoloFormatado));

        // 1. Caso j� exista um Protocolo no SEI com o mesmo NUP
        if (! is_null($objProtocoloDTOExistente)) {
            // 2. Se existir e for e-Ouv
            if ($tipoManifestacao == 'P') {
                $debugLocal && LogSEI::getInstance()->gravar('Importando Linha Manifesta��o e-ouv - protocolo: ' . $this->formatarProcesso($numProtocoloFormatado));
                // 2.1 Importar anexos novos se existirem... e retornar log
                // @todo - melhoria pr�xima vers�o e-Ouv
                $this->gravarLogLinha($numProtocoloFormatado, $this->idRelatorioImportacao, 'Erro na grava��o: ' . 'J� existe um processo (e-Ouv) utilizando o n�mero de protocolo.', 'N', $tipoManifestacao);
            }

            // 3. Se existir e for e-Sic
            if ($tipoManifestacao == 'R') {

                $debugLocal && LogSEI::getInstance()->gravar('Importando Linha Manifesta��o e-SIC - protocolo: ' . $this->formatarProcesso($numProtocoloFormatado));

                /**
                 * @todo - @teste
                 * Teste aqui pra validar se o prazo sendo 'maior' na peti��o inicial j� n�o deve importar os recursos..... (??)
                 */
                // Data do �ltimo prazo de atendimento para este protocolo
                $objUltimaDataPrazoAtendimento = MdCguEouvAgendamentoINT::retornarUltimaDataPrazoAtendimento($numProtocoloFormatado);


                // 4. Verificar se houve altera��o na data 'PrazoAtendimento'
                if (isset($objUltimaDataPrazoAtendimento) && $objUltimaDataPrazoAtendimento->getDthDthPrazoAtendimento() > $dataPrazoAtendimento) {

                    // Importar anexos do novo recurso
                    try {
                        $anexoCount = 0;
                        if (isset($arrRecursosManifestacao['Recursos']) && is_array($arrRecursosManifestacao['Recursos'])) {

                            // Verifica Tipo de Recurso
                            $tipo_recurso = $this->verificaTipo($arrRecursosManifestacao['Recursos']);

                            $debugLocal && LogSEI::getInstance()->gravar('[executarImportacaoLinha] Importando o recurso do protocolo: ' . $numProtocoloFormatado);

                            // Carregar documento recurso
                            $this->gerarPDFDocumentoESic($arrDetalheManifestacao, $arrRecursosManifestacao, $objProtocoloDTOExistente->getDblIdProtocolo(), $tipo_recurso);
                            LogSEI::getInstance()->gravar('M�dulo Integra��o FalaBR - Importa��o de Recurso ' . $numProtocoloFormatado . ': total de  Anexos configurados: ' . $anexoCount, InfraLog::$INFORMACAO);
                            $this->gravarLogLinha($numProtocoloFormatado, $this->idRelatorioImportacao, 'Recurso com protocolo ' . $numProtocoloFormatado . ' importado com sucesso com ' . $anexoCount . ' anexos incluidos no protocolo.', 'S', $tipoManifestacao, $dataPrazoAtendimento);

                            // Carregar anexos
                            $recursos = $arrRecursosManifestacao['Recursos'];
                            foreach ($recursos as $recurso) {
                                if (count($recurso['anexos']) > 0) {
                                    $anexosAdicionados = $this->gerarAnexosProtocolo($recurso['anexos'], $numProtocoloFormatado, $tipoManifestacao, $objProtocoloDTOExistente->getDblIdProtocolo());
                                    if (count($anexosAdicionados) > 0) {
                                        $anexoCount++;
                                    }
                                }
                            }

                            // Vincular Recursos com as unidades corretas conforme o tipo de recurso
                            // Se for 1 inst�ncia envia processo para ESIC_ID_UNIDADE_RECURSO_PRIMEIRA_INSTANCIA
                            if ($tipo_recurso == 'R1') {
                                $unidadeDestino = $this->idUnidadeRecursoPrimeiraInstancia;
                            } elseif ($tipo_recurso == 'R2') {
                                $unidadeDestino = $this->idUnidadeRecursoSegundaInstancia;
                            } elseif ($tipo_recurso == 'R3' || $tipo_recurso == 'RC') {
                                $unidadeDestino = $this->idUnidadeRecursoTerceiraInstancia;
                            } elseif ($tipo_recurso == 'PR') {
                                $unidadeDestino = $this->idUnidadeRecursoPedidoRevisao;
                            } else {
                                $unidadeDestino = $this->idUnidadeOuvidoria;
                            }

                            try {
                                $objEntradaEnviarProcesso = new EntradaEnviarProcessoAPI();
                                $objEntradaEnviarProcesso->setIdProcedimento($objProtocoloDTOExistente->getDblIdProtocolo());
                                $objEntradaEnviarProcesso->setUnidadesDestino([$unidadeDestino]);
                                $objEntradaEnviarProcesso->setSinManterAbertoUnidade('S');
                                $objEntradaEnviarProcesso->setSinEnviarEmailNotificacao('S');
                                $objEntradaEnviarProcesso->setSinReabrir('S');

                                $objSeiRN = new SeiRN();
                                $objSeiRN->enviarProcesso($objEntradaEnviarProcesso);
                                LogSEI::getInstance()->gravar('M�dulo Integra��o FalaBR - (Recurso tipo ' . $tipo_recurso . ') Processo ' . $numProtocoloFormatado . ' enviado para unidade ' . $this->idUnidadeRecursoPrimeiraInstancia, InfraLog::$INFORMACAO);

                            } catch (Exception $e) {
                                LogSEI::getInstance()->gravar('M�dulo Integra��o FalaBR - (Recurso tipo ' . $tipo_recurso . ') N�o foi possivel abrir o Processo ' . $numProtocoloFormatado . ' na unidade ' . $this->idUnidadeRecursoPrimeiraInstancia . ' - erro: ' . $e, InfraLog::$INFORMACAO);
                            }
                        } else {
                            /**
                             * @todo - confirmar - aqui deve ficar como 'N' ou 'S'? Se fircar como 'N' entra como erro... ?? e � preciso gravar que n�o houve recurso mas teve altera��o na data de prazo de atencimento,
                             * esta data precisa ser salva no banco de dados... comentar/documentar aqui!
                             */
                            $this->gravarLogLinha($numProtocoloFormatado, $this->idRelatorioImportacao, 'Sem recursos novos.', 'S', $tipoManifestacao, $dataPrazoAtendimento);
                        }
                    } catch (Exception $e) {
                        $this->gravarLogLinha($numProtocoloFormatado, $this->idRelatorioImportacao, 'Erro na grava��o recurso: ' . $e, 'N', $tipoManifestacao);
                    }
                } else {
                    // 4.2 Se n�o houve altera��o na data 'PrazoAtendimento' retornar log
                    $this->gravarLogLinha($numProtocoloFormatado, $this->idRelatorioImportacao, 'J� existe um processo (e-Sic) utilizando o n�mero de protocolo e n�o h� altera��o para nova importa��o.', 'S', $tipoManifestacao, $dataPrazoAtendimento);
                }
            }
        } else {
            $this->criarNovoProcesso($idTipoManifestacaoSei, $retornoWsLinha['TipoManifestacao']['IdTipoManifestacao'], $manifestacaoESic, $arrDetalheManifestacao, $idUnidadeDestino, $numProtocoloFormatado, $tipoManifestacao, $arrRecursosManifestacao);
        }
    }

    public function executarImportacaoLinhaRecursos ($arrRecursosManifestacao, $tipoManifestacao = 'R')
    {
        $debugLocal = false;

        $objProcedimentoDTO = new ProcedimentoDTO();
        $objProcedimentoDTO->setDblIdProcedimento(null);

        $numProtocoloFormatado =  $this->formatarProcesso($arrRecursosManifestacao['numProtocolo']);
        $dataPrazoAtendimento = $arrRecursosManifestacao['prazoAtendimento'];

        /**
         * Limpa os registros de detalhe de importa��o com erro para este NUP.
         * Caso ocorra um novo, ser� criado novo registro de erro para o NUP no tratamento desta function.
         */
        $this->limparErrosParaNup($numProtocoloFormatado);

        /**
         * Se for Manifesta��o do e-Sic verificar:houve altera��o na data 'PrazoAtendimento' e
         * gera novo arquivo PDF com as altera��es para inser��o no mesmo protocolo (NUP) e
         * importa anexos comparando o hash do arquivo para n�o duplicidade no processo
         */
        // Vefificar se o NUP j� existe
        $objProtocoloDTOExistente = $this->verificarProtocoloExistente($numProtocoloFormatado);

        // Caso j� exista um Protocolo no SEI continua, caso contr�rio apenas registra o log
        if (! is_null($objProtocoloDTOExistente)) {

            $debugLocal && LogSEI::getInstance()->gravar('[executarImportacaoLinhaRecursos] Existe o protocolo: ' . $numProtocoloFormatado);

            // Se existir e for e-Sic
            if ($tipoManifestacao == 'R') {

                $debugLocal && LogSEI::getInstance()->gravar('[executarImportacaoLinhaRecursos] � do tipo: ' . $tipoManifestacao . ' > ' . $this->verificaTipo($arrRecursosManifestacao, 'R'));

                // Data do �ltimo prazo de atendimento para este protocolo sem o tipo de recurso para buscar qualquer um recurso anterior
                $objUltimaDataPrazoAtendimento = MdCguEouvAgendamentoINT::retornarUltimaDataPrazoAtendimento($numProtocoloFormatado);
                $debugLocal && LogSEI::getInstance()->gravar('[executarImportacaoLinhaRecursos] �ltimo prazo de atendimento: ' . $objUltimaDataPrazoAtendimento);

                /**
                 * Regra de bloqueio na cria��o de novos recursos caso j� exista um recurso superior ao atualmente listado
                 * - regra implementada devido � duplicidade na importa��o dos processos
                 */
                $ultimoTipoRecursoImportado = MdCguEouvAgendamentoINT::retornarTipoManifestacao($this->idRelatorioImportacao, $numProtocoloFormatado);
                $ultimoTipoRecursoImportado = $ultimoTipoRecursoImportado ? $ultimoTipoRecursoImportado->getStrTipManifestacao() : $this->verificaTipo($arrRecursosManifestacao, 'R1');
                $debugLocal && LogSEI::getInstance()->gravar('[executarImportacaoLinhaRecursos] Ultimo tipo de recurso importado: ' . $ultimoTipoRecursoImportado . ' - Tipo recurso atual: ' . $this->verificaTipo($arrRecursosManifestacao, 'R1'));

                $permiteImportacaoRecursoAtual = $this->permiteImportacaoRecursoAtual($this->verificaTipo($arrRecursosManifestacao, 'R1'), $ultimoTipoRecursoImportado);
                $debugLocal && LogSEI::getInstance()->gravar('[executarImportacaoLinhaRecursos] Permite criar o recurso atual: ' . $permiteImportacaoRecursoAtual);

                if ($permiteImportacaoRecursoAtual == 'bloquear') {
                    $debugLocal && LogSEI::getInstance()->gravar('[executarImportacaoLinhaRecursos] N�o foi permitido criar o recurso, pode deve haver recurso anterior j� importado');
                    // Se n�o for permitido criar o recurso
                    $this->gravarLogLinha($numProtocoloFormatado, $this->idRelatorioImportacao, 'O recurso existente no FalaBR n�o ser� importado devido � regra implementada - tipoAtual: "' . $this->verificaTipo($arrRecursosManifestacao, 'R') . '" | tipoAnterior: '. $ultimoTipoRecursoImportado .' | protocolo.', 'S', $ultimoTipoRecursoImportado, $objUltimaDataPrazoAtendimento->getDthDthPrazoAtendimento());
                    return;
                }

                // Verificar se houve altera��o na data 'PrazoAtendimento'
                if (($objUltimaDataPrazoAtendimento && $objUltimaDataPrazoAtendimento->getDthDthPrazoAtendimento() <> $dataPrazoAtendimento) || $objUltimaDataPrazoAtendimento === null) {

                    $debugLocal && LogSEI::getInstance()->gravar('[executarImportacaoLinhaRecursos] Data de prazo de atendimento diferente da �ltima, incinia importacao');

                    // Importar anexos do novo recurso
                    try {
                        if (isset($arrRecursosManifestacao)) {
                            $anexoCount = isset($arrRecursosManifestacao['qtdAnexos']) ? $arrRecursosManifestacao['qtdAnexos'] : 0;

                            // Verifica Tipo de Recurso
                            $tipo_recurso = $this->verificaTipo($arrRecursosManifestacao);
                            $unidadeDestino = $this->getUnidadeDestino($tipo_recurso);

                            $debugLocal && LogSEI::getInstance()->gravar('[executarImportacaoLinhaRecursos] Tipo de recurso: ' . $tipo_recurso);

                            // Buscar dados da Manifesta��o
                            $numProtocoloSemFormatacao = str_replace(['.', '/', '-'], ['', '', ''], $numProtocoloFormatado);
                            $retornoWsLinha = $this->executarServicoConsultaManifestacoes($this->urlWebServiceEOuv, $this->token, null, null, $numProtocoloSemFormatacao, $this->idRelatorioImportacao);
                            $linkDetalheManifestacao = $retornoWsLinha[0]['Links'][0]['href'];
                            $arrDetalheManifestacao = MdCguEouvWS::apiRestRequest($linkDetalheManifestacao, $this->token, 2);

                            $debugLocal && LogSEI::getInstance()->gravar('Importando Recurso processo: ' . $numProtocoloFormatado . ' | tipo: ' . $tipo_recurso);


                            /**
                             * Verificar o tipo de recurso de for diferente de segunda inst�ncia, trazer todos os recursos para o documento pdf
                             */
                            if ($tipo_recurso <> 'R1') {
                                $arrRecursosManifestacaoComAnteriores = $this->executarServicoConsultaRecursos($this->urlWebServiceESicRecursos, $this->token, null, null, $numProtocoloSemFormatacao);
                                $this->gerarPDFDocumentoESic($arrDetalheManifestacao, $arrRecursosManifestacaoComAnteriores, $objProtocoloDTOExistente->getDblIdProtocolo(), $tipo_recurso);
                            } else {
                                $this->gerarPDFDocumentoESic($arrDetalheManifestacao, $arrRecursosManifestacao, $objProtocoloDTOExistente->getDblIdProtocolo(), $tipo_recurso);
                            }

                            $this->gravarLogLinha($numProtocoloFormatado, $this->idRelatorioImportacao, 'Recurso tipo ' . $tipo_recurso . ' com protocolo ' . $numProtocoloFormatado . ' importado com sucesso com ' . $anexoCount . ' anexos incluidos no protocolo.', 'S', $tipo_recurso, $dataPrazoAtendimento);
                            $debugLocal && LogSEI::getInstance()->gravar('Importando Recurso processo: ' . $numProtocoloFormatado . ' | tipo: ' . $tipo_recurso . 'depois de gravar log ?!');
                            LogSEI::getInstance()->gravar('M�dulo Integra��o FalaBR - Importa��o de Recurso ' . $numProtocoloFormatado . ': total de  Anexos configurados: ' . $anexoCount, InfraLog::$INFORMACAO);

                            // Carregar anexos
                            if (count($arrRecursosManifestacao['anexos']) > 0) {
                                $this->gerarAnexosProtocolo($arrRecursosManifestacao['anexos'], $numProtocoloFormatado, $tipoManifestacao, $objProtocoloDTOExistente->getDblIdProtocolo());
                            }

                            try {
                                $objEntradaEnviarProcesso = new EntradaEnviarProcessoAPI();
                                $objEntradaEnviarProcesso->setIdProcedimento($objProtocoloDTOExistente->getDblIdProtocolo());
                                $objEntradaEnviarProcesso->setUnidadesDestino([$unidadeDestino]);
                                $objEntradaEnviarProcesso->setSinManterAbertoUnidade('S');
                                $objEntradaEnviarProcesso->setSinEnviarEmailNotificacao('S');
                                $objEntradaEnviarProcesso->setSinReabrir('S');

                                $objSeiRN = new SeiRN();
                                $objSeiRN->enviarProcesso($objEntradaEnviarProcesso);
                                LogSEI::getInstance()->gravar('M�dulo Integra��o FalaBR - (Recurso tipo ' . $tipo_recurso . ') Processo ' . $numProtocoloFormatado . ' enviado para unidade ' . $this->idUnidadeRecursoPrimeiraInstancia, InfraLog::$INFORMACAO);

                            } catch (Exception $e) {
                                LogSEI::getInstance()->gravar('M�dulo Integra��o FalaBR - (Recurso tipo ' . $tipo_recurso . ') N�o foi possivel abrir o Processo ' . $numProtocoloFormatado . ' na unidade ' . $this->idUnidadeRecursoPrimeiraInstancia . ' - erro: ' . $e, InfraLog::$INFORMACAO);
                            }
                        } else {
                            $this->gravarLogLinha($numProtocoloFormatado, $this->idRelatorioImportacao, 'Sem recursos novos.', 'S', $ultimoTipoRecursoImportado, $dataPrazoAtendimento);
                        }
                    } catch (Exception $e) {
                        $debugLocal && LogSEI::getInstance()->gravar('[executarImportacaoLinhaRecursos] Erro importando anexo do recruso');
                        $this->gravarLogLinha($numProtocoloFormatado, $this->idRelatorioImportacao, 'Erro na grava��o recurso: ' . $e, 'N', $tipoManifestacao);
                    }
                } else {
                    $debugLocal && LogSEI::getInstance()->gravar('[executarImportacaoLinhaRecursos] N�o importou recurso pois o prazo de atendimento � igual e n�o faz nada.. n�o atualiza o log para n�o atualizar a data do novo prazo nem o tipo de recurso');
                    // Se n�o houve altera��o na data 'PrazoAtendimento' retornar log
                    $this->gravarLogLinha($numProtocoloFormatado, $this->idRelatorioImportacao, 'J� existe um recurso (e-Sic) do tipo "' . $this->verificaTipo($arrRecursosManifestacao, 'R') . '" para este protocolo e n�o h� altera��o para nova importa��o.', 'S', $ultimoTipoRecursoImportado, $objUltimaDataPrazoAtendimento->getDthDthPrazoAtendimento());
                }
            }
        } else {
            $this->gravarLogLinha($numProtocoloFormatado, $this->idRelatorioImportacao, 'Existe recurso para o processo ' . $numProtocoloFormatado . ', por�m este processo n�o existe no SEI. Provavelmente � um processo antes da data de in�cio de utiliza��o do m�dulo ou o Tipo de Manifesta��o do FalaBR n�o foi registrada para este m�dulo.', 'S', $tipoManifestacao);
        }
    }

    public function limparErrosParaNup($numProtocoloComErro){
        $objEouvRelatorioImportacaoDetalheDTO = new MdCguEouvRelatorioImportacaoDetalheDTO();
        $objEouvRelatorioImportacaoDetalheDTO->retTodos(true);
        $objEouvRelatorioImportacaoDetalheDTO->setStrProtocoloFormatado($numProtocoloComErro);
        $objEouvRelatorioImportacaoDetalheDTO->setStrSinSucesso('N');

        $objEouvRelatorioImportacaoDetalheRN = new MdCguEouvRelatorioImportacaoDetalheRN();
        $objListaErros = $objEouvRelatorioImportacaoDetalheRN->listar($objEouvRelatorioImportacaoDetalheDTO);
        foreach($objListaErros as $erro){
            $erro->setStrSinSucesso('C');
            $objEouvRelatorioImportacaoDetalheRN->alterar($erro);
        }

    }

    public function gerarPDFPedidoInicial($retornoWsLinha)
    {
        $mdCguEouvGerarPdfInicial = new MdCguEouvGerarPdfInicial($retornoWsLinha);
        $pdf = $mdCguEouvGerarPdfInicial->gerarPdfInicial();

        $objAnexoRN = new AnexoRN();
        $strNomeArquivoInicialUpload = $objAnexoRN->gerarNomeArquivoTemporario();

        $pdf->Output(DIR_SEI_TEMP . "/" . $strNomeArquivoInicialUpload . ".pdf", "F");

        //Renomeia tirando a extens�o para o SEI trabalhar o Arquivo
        rename(DIR_SEI_TEMP . "/" . $strNomeArquivoInicialUpload . ".pdf", DIR_SEI_TEMP . "/" . $strNomeArquivoInicialUpload);

        $objDocumentoManifestacao = new DocumentoAPI();
        $objDocumentoManifestacao->setTipo('R');
        $objDocumentoManifestacao->setIdSerie($this->idTipoDocumentoAnexoDadosManifestacao);
        $objDocumentoManifestacao->setData($retornoWsLinha['DataCadastro']);
        $objDocumentoManifestacao->setNomeArquivo('Relat�rioDadosManifesta��o.pdf');
        $objDocumentoManifestacao->setConteudo(base64_encode(file_get_contents(DIR_SEI_TEMP . "/" . $strNomeArquivoInicialUpload)));

        return $objDocumentoManifestacao;
    }

    public function gerarPDFDocumentoESic($retornoWsLinha, $retornoWsRecursos = null, $IdProtocolo = false, $tipo_recurso = '')
    {
        /**
         * Testa acessar dado da manifesta��o se n�o conseguir salva log
         */
        try {
            $IdTipoManifestacao = $retornoWsLinha['TipoManifestacao']['IdTipoManifestacao'];
        } catch (Exception $e) {
            $this->gravarLogLinha($IdProtocolo ? $IdProtocolo : 'n/a', $this->idRelatorioImportacao, substr('ERRO-esic|' . $retornoWsLinha . '|' . $e, 0, 500), 'N');
            return;
        }

        $pdf = MdCguEouvGerarPdfEsic::gerarPdf($retornoWsLinha, $retornoWsRecursos, $this->ocorreuErroAdicionarAnexo);
        $objAnexoRN = new AnexoRN();
        $strNomeArquivoInicialUpload = $objAnexoRN->gerarNomeArquivoTemporario();

        $pdf->Output(DIR_SEI_TEMP . "/" . $strNomeArquivoInicialUpload . ".pdf", "F");

        //Renomeuia tirando a extencaoo para o SEI trabalhar o Arquivo
        rename(DIR_SEI_TEMP . "/" . $strNomeArquivoInicialUpload . ".pdf", DIR_SEI_TEMP . "/" . $strNomeArquivoInicialUpload);

        $objDocumentoManifestacao = new DocumentoAPI();
        $objDocumentoManifestacao->setTipo('R');
        if ($IdProtocolo && $IdProtocolo <> '') {
            $objDocumentoManifestacao->setIdProcedimento($IdProtocolo);
        }
        if ($tipo_recurso == 'R1') {
            $nomeDocumentoArvore = 'Primeira Inst�ncia';
        } elseif ($tipo_recurso == 'R2') {
            $nomeDocumentoArvore = 'Segunda Inst�ncia';
        } elseif ($tipo_recurso == 'R3' || $tipo_recurso == 'RC') {
            $nomeDocumentoArvore = 'Terceira Inst�ncia';
        } elseif ($tipo_recurso == 'PR') {
            $nomeDocumentoArvore = 'Pedido Revis�o';
        } else {
            $nomeDocumentoArvore = 'Pedido Inicial';
        }

        $objDocumentoManifestacao->setNumero($nomeDocumentoArvore);
        $objDocumentoManifestacao->setIdSerie($this->idTipoDocumentoAnexoDadosManifestacao);
        $objDocumentoManifestacao->setData($retornoWsLinha['DataCadastro']);
        $objDocumentoManifestacao->setNomeArquivo('RelatorioDadosManifestacao.pdf');
        $objDocumentoManifestacao->setConteudo(base64_encode(file_get_contents(DIR_SEI_TEMP . "/" . $strNomeArquivoInicialUpload)));

        if ($IdProtocolo && $IdProtocolo <> '') {
            $objSEIRN = new SeiRN();
            $objSEIRN->incluirDocumento($objDocumentoManifestacao);
        }

        return $objDocumentoManifestacao;
    }

    public function gerarAnexosProtocolo($arrAnexosManifestacao, $numProtocoloFormatado, $tipoManifestacao = 'P', $IdProtocolo = false)
    {
        /**********************************************************************************************************************************************
         * In�cio da importa��o de anexos de cada protocolo
         * Desativado momentaneamente
         */


        $arrAnexosAdicionados = array();
        $intTotAnexos = count($arrAnexosManifestacao);

        if($intTotAnexos == 0){
            //N�o encontrou anexos..
            return $arrAnexosAdicionados;
        }

        //Trata as extensões permitidas
        $objArquivoExtensaoDTO = new ArquivoExtensaoDTO();
        $objArquivoExtensaoDTO->retNumIdArquivoExtensao();
        $objArquivoExtensaoDTO->retStrExtensao();
        $objArquivoExtensaoDTO->retStrDescricao();
        $objArquivoExtensaoDTO->retNumTamanhoMaximo();
        $objArquivoExtensaoRN = new ArquivoExtensaoRN();
        $arrObjArquivoExtensaoDTO = $objArquivoExtensaoRN->listar($objArquivoExtensaoDTO);
        $arrExtensoesPermitidas = array();

        foreach($arrObjArquivoExtensaoDTO as $extensao){
            array_push($arrExtensoesPermitidas, strtoupper ($extensao->getStrExtensao()));
        }

        foreach ($arrAnexosManifestacao as $retornoWsAnexoLista) {

            foreach (MdCguEouvWS::verificaRetornoWS($retornoWsAnexoLista) as $retornoWsAnexoLinha) {
                try {

                    $strNomeArquivoOriginal = $retornoWsAnexoLinha['NomeArquivo'];
                    if ($strNomeArquivoOriginal == null) {
                        $strNomeArquivoOriginal = $retornoWsAnexoLinha['nomeArquivo'];
                    }

                    // Ajustamos aqui o nome do arquivo limitado a 50 caracteres
                    $strNomeArquivoOriginal = substr($strNomeArquivoOriginal, -50, 50);

                    $ext = strtoupper(pathinfo($strNomeArquivoOriginal, PATHINFO_EXTENSION));
                    $intIndexExtensao = array_search($ext, $arrExtensoesPermitidas);

                    if (is_numeric($intIndexExtensao)) {
                        $objAnexoRN = new AnexoRN();
                        $strNomeArquivoUpload = $objAnexoRN->gerarNomeArquivoTemporario();

                        $fp = fopen(DIR_SEI_TEMP . '/' . $strNomeArquivoUpload, 'w');

                        //Busca o conte�do do Anexo
                        $arrDetalheAnexoManifestacao = MdCguEouvWS::apiRestRequest($retornoWsAnexoLinha['Links'][0]['href'], $this->token, 3);

                        $strConteudoCodificado = $arrDetalheAnexoManifestacao['ConteudoZipadoEBase64'];

                        $binConteudoDecodificado = '';
                        for ($i = 0; $i < ceil(strlen($strConteudoCodificado) / 256); $i++) {
                            $binConteudoDecodificado = $binConteudoDecodificado . base64_decode(substr($strConteudoCodificado, $i * 256, 256));
                        }

                        $binConteudoUnzip = $this->gzdecode($binConteudoDecodificado);

                        fwrite($fp, $binConteudoUnzip);
                        fclose($fp);

                        $objAnexoManifestacao = new DocumentoAPI();

                        if ($IdProtocolo && $IdProtocolo <> '') {
                            $objAnexoManifestacao->setIdProcedimento($IdProtocolo);
                        }
                        $objAnexoManifestacao->setTipo('R');
                        $objAnexoManifestacao->setIdSerie($this->idTipoDocumentoAnexoDadosManifestacao);
                        $objAnexoManifestacao->setData(InfraData::getStrDataHoraAtual());
                        $objAnexoManifestacao->setNomeArquivo($strNomeArquivoOriginal);
                        $objAnexoManifestacao->setNumero($strNomeArquivoOriginal);
                        $objAnexoManifestacao->setConteudo(base64_encode(file_get_contents(DIR_SEI_TEMP . '/' . $strNomeArquivoUpload)));

                        if (!$this->hashDuplicado(DIR_SEI_TEMP . '/' . $strNomeArquivoUpload, $numProtocoloFormatado)) {
                            if ($IdProtocolo && $IdProtocolo <> '') {
                                $objSEIRN = new SeiRN();
                                $objSEIRN->incluirDocumento($objAnexoManifestacao);
                            }
                            array_push($arrAnexosAdicionados, $objAnexoManifestacao);
                        }
                    } else {
                        $ocorreuErroAdicionarAnexo = true;
                        LogSEI::getInstance()->gravar('Importa��o de Manifesta��o ' . $numProtocoloFormatado . ': Arquivo ' . $strNomeArquivoOriginal . ' possui extens�o inv�lida.', InfraLog::$INFORMACAO);
                        continue;
                    }
                }
                catch(Exception $e){
                    $ocorreuErroAdicionarAnexo = true;
                    $strMensagemErroAnexos = $strMensagemErroAnexos . " " . $e;
                }
            }

            if($ocorreuErroAdicionarAnexo==true){
                $this->gravarLogLinha($numProtocoloFormatado, $this->idRelatorioImportacao, 'Um ou mais documentos anexos n�o foram importados corretamente: ' . $strMensagemErroAnexos, 'S', $tipoManifestacao);
            }
        }

        return $arrAnexosAdicionados;
    }

    public function excluirProcessoComErro($idProcedimento){

        try{
            $objProcedimentoExcluirDTO = new ProcedimentoDTO();
            $objProcedimentoExcluirDTO->setDblIdProcedimento($idProcedimento);
            $objProcedimentoRN = new ProcedimentoRN();
            $objProcedimentoRN->excluirRN0280($objProcedimentoExcluirDTO);
            ProcedimentoINT::removerProcedimentoVisitado($idProcedimento);
        }catch(Exception $e){
            PaginaSEI::getInstance()->processarExcecao($e);
        }

    }

    public static function formatarProcesso($strProcesso) {

        $strProcesso = InfraUtil::retirarFormatacao($strProcesso);

        if (strlen($strProcesso)==0){
            return '';
        }

        if (strlen($strProcesso) == 17){
            $strProcesso = substr($strProcesso,0,5).".".
                substr($strProcesso,5,6)."/".
                substr($strProcesso,11,4)."-".
                substr($strProcesso,15,2);
        }
        return $strProcesso;
    }

    /**
     * Verifica se j� existe um Protocolo no SEI com o n�mero (NUP)
     */
    public function verificarProtocoloExistente($numProtocoloFormatado)
    {
        $objProtocoloDTOExistente = new ProtocoloDTO();
        $objProtocoloRNExistente = new ProtocoloRN();
        $objProtocoloDTOExistente->retDblIdProtocolo();
        $objProtocoloDTOExistente->retStrProtocoloFormatado();
        $objProtocoloDTOExistente->setStrProtocoloFormatado($this->formatarProcesso($numProtocoloFormatado));
        $objProtocoloDTOExistente = $objProtocoloRNExistente->consultarRN0186($objProtocoloDTOExistente);

        return $objProtocoloDTOExistente;
    }

    /**
     * Verifica se j� existe o hash do arquivo na tabela anexo coluna hash
     *
     * @param $strArquivo
     * @return bool
     * @throws InfraException
     */
    public function hashDuplicado($strArquivo, $numProtocoloFormatado)
    {
        // Verifica hash do arquivo
        $hash = md5_file($strArquivo);

        // Select na tabela Anexe com o hash Criado
        $consulta = new MdCguEouvConsultarHashBD($this->getObjInfraIBanco());
        $res = $consulta->consultarHash($hash, $numProtocoloFormatado);

        return count($res) > 0;
    }

    /**
     * Fun��o para simular login
     *
     * @param $siglaSistema
     * @param $idServico
     * @param $idUnidade
     */
    public static function simulaLogin($siglaSistema, $idServico, $idUnidade)
    {
        try {

            InfraDebug::getInstance()->gravar(__METHOD__);
            InfraDebug::getInstance()->gravar('SIGLA SISTEMA:'.$siglaSistema);
            InfraDebug::getInstance()->gravar('IDENTIFICACAO SERVICO:'.$idServico);
            InfraDebug::getInstance()->gravar('ID UNIDADE:'.$idUnidade);

            SessaoSEI::getInstance(false);

            $objServicoDTO = self::obterServico($siglaSistema, $idServico);

            if ($idUnidade!=null) {
                $objUnidadeDTO = self::obterUnidade($idUnidade,null);
            } else {
                $objUnidadeDTO = null;
            }
            SessaoSEI::getInstance()->simularLogin(null, null, $objServicoDTO->getNumIdUsuario(), $objUnidadeDTO->getNumIdUnidade());

        } catch(Exception $e) {
            LogSEI::getInstance()->gravar('Ocorreu erro simular Login.'.$e);
            PaginaSEI::getInstance()->processarExcecao($e);
        }
    }

    /**
     * Verifica o tipo de Recuso com base na API do FalaBR
     *
     * - IdInstanciaRecurso
     * - 1 = primeira inst�ncia
     * - 2 = segunda inst�ncia
     *
     * @param null $recursos
     * @return string
     *
     * - 'P' - Padr�o, n�o possui recursos de primeira ou segunda inst�ncia
     * - 'R1' - Recurso de primeira inst�ncia
     * - 'R2' - Recurso de segunda inst�ncia
     */
    public function verificaTipo($recursos = null, $default_response = 'P')
    {
        $response = $default_response;
        if (isset($recursos)) {
            if (isset($recursos['instancia'])) {
                $response = $this->checkTipoRecurso($recursos);
            } else {
                foreach ($recursos as $recurso) {
                    if ($this->checkTipoRecurso($recurso)) {
                        $response = $this->checkTipoRecurso($recurso);
                        break;
                    }
                }

            }
        }

        return $response;
    }

    public function checkTipoRecurso($recurso)
    {
        if ($recurso['instancia']['IdInstanciaRecurso'] == 6) {
            return 'PR'; // Pedido Revis�o
        }
        if ($recurso['instancia']['IdInstanciaRecurso'] == 7) {
            return 'R3'; // Recurso 3 inst�ncia
        }
        if ($recurso['instancia']['IdInstanciaRecurso'] == 4) {
            return 'RE'; // Reclama��o
        }
        if ($recurso['instancia']['IdInstanciaRecurso'] == 3) {
            return 'RC'; // Recurso CGU
        }
        if ($recurso['instancia']['IdInstanciaRecurso'] == 2) {
            return 'R2'; // Recurso 2 inst�ncia
        }
        if ($recurso['instancia']['IdInstanciaRecurso'] == 1) {
            return 'R1'; // Recurso 1 inst�ncia
        }

        return 'R';
    }

    /**
     * Verifica se existe recurso 'posterior' cadastrado
     *
     * - Posterior est� entre aspas pq o recurso deve seguir uma �rdem cronol�gica para se adequar � importa��o dos
     * dados no SEI
     *
     */
    public function permiteImportacaoRecursoAtual($tipoManifestacaoAtual, $ultimoTipoRecursoImportado)
    {
        $debugLocal = false;

        $debugLocal && LogSEI::getInstance()->gravar('[permiteImportacaoRecursoAtual] Verificando se existe algum recurso anterior');

        // Se ja existir no log um recurso anterior verifica se o novo recurso e 'superior' ao j� registrado
        if ($tipoManifestacaoAtual) {

            $debugLocal && LogSEI::getInstance()->gravar('[permiteImportacaoRecursoAtual] Existe log, validando o tipo de manifesta��o: ' . $tipoManifestacaoAtual . ' para o anteior existente: ' . $ultimoTipoRecursoImportado);

            /**
             * [CUIDADO] N�o � poss�vel utilizar o 'switch > case' aqui - n�o sei o por qu�, mas n�o funciona....  @study (??)
             */

            /**
             * Para criar um R1 (Recurso de Primeira Inst�ncia) pode existir somente PR (Pedido de Revis�o),
             * R (Pedido Inicial do e-Sic)
             */
            if ($tipoManifestacaoAtual == 'R1' && in_array($ultimoTipoRecursoImportado, ['R2', 'RC', 'R3'])) {
                $debugLocal && LogSEI::getInstance()->gravar('[permiteImportacaoRecursoAtual] Deve bloquear a cria��o deste recurso! tipoAtual: ' . $tipoManifestacaoAtual . ' - tipoAnterior: ' . $ultimoTipoRecursoImportado);
                return 'bloquear';
            }

            /**
             * Para criar um R2 (Recurso de Segunda Inst�ncia) pode existir somente R1 (Recurso de Primeira Inst�ncia),
             * PR (Pedido de Revis�o), R (Pedido Inicial do e-Sic)
             */
            if ($tipoManifestacaoAtual == 'R2' && in_array($ultimoTipoRecursoImportado, ['RC', 'R3'])) {
                $debugLocal && LogSEI::getInstance()->gravar('[permiteImportacaoRecursoAtual] Deve bloquear a cria��o deste recurso! tipoAtual: ' . $tipoManifestacaoAtual . ' - tipoAnterior: ' . $ultimoTipoRecursoImportado);
                return 'bloquear';
            }

            /**
             * Se for tipo 4 - Reclama��o - n�o importar
             */
            if ($tipoManifestacaoAtual == 'RE') {
                $debugLocal && LogSEI::getInstance()->gravar('[permiteImportacaoRecursoAtual] Deve bloquear a cria��o deste recurso! tipoAtual: ' . $tipoManifestacaoAtual . ' - tipoAnterior: ' . $ultimoTipoRecursoImportado);
                return 'bloquear';
            }

            /**
             * Para criar um PR (Pedido de Revis�o) pode existir somente R (Pedido Inicial do e-Sic)
             */
            if ($tipoManifestacaoAtual == 'PR' && in_array($ultimoTipoRecursoImportado, ['R1', 'R2', 'RC', 'R3'])) {
                $debugLocal && LogSEI::getInstance()->gravar('[permiteImportacaoRecursoAtual] Deve bloquear a cria��o deste recurso! tipoAtual: ' . $tipoManifestacaoAtual . ' - tipoAnterior: ' . $ultimoTipoRecursoImportado);
                return 'bloquear';
            }
        }

        /**
         * Se existir algo na tabela, por�m, n�o estiver definido na regra acima ou se n�o existir nenhum registro na
         * tabela, a importa��o ser� permitida
         * [CUIDADO] Caso haja duplicidade na importa��o, pode haver algum tipo de recurso n�o mapeado no campo
         * "instancia": { "IdInstanciaRecurso": ## > na API do FalaBR
         */
        $debugLocal && LogSEI::getInstance()->gravar('[permiteImportacaoRecursoAtual] Vai permitir a cria��o desse recurso!');
        return 'permitir';
    }
}
?>
