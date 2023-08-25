<?php

/**
 * CONTROLADORIA GERAL DA UNI�O- CGU
 *
 * 09/10/2015 - criado por Rafael Leandro
 *
 */

error_reporting(E_ALL); ini_set('display_errors', '1');

require_once dirname(__FILE__) . '/../../../../SEI.php';

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
    protected $identificacaoServico = 'CadastrarManifestacao';
    protected $siglaSistema = 'EOUV';


    public function __construct()
    {
        parent::__construct();
    }

    protected function inicializarObjInfraIBanco()
    {
        return BancoSEI::getInstance();
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
                }
            }
        }
    }

    public function preencheVariaveisEouv(int $numRegistros, $arrObjEouvParametroDTO)
    {
        if ($numRegistros > 0) {
            for ($i = 0; $i < $numRegistros; $i++) {

                $strParametroNome = $arrObjEouvParametroDTO[$i]->getStrNoParametro();

                switch ($strParametroNome) {

                    case "EOUV_DATA_INICIAL_IMPORTACAO_MANIFESTACOES":
                        $this->dataInicialImportacaoManifestacoes = $arrObjEouvParametroDTO[$i]->getStrDeValorParametro();
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

                    case "ID_UNIDADE_OUVIDORIA":
                        $this->idUnidadeOuvidoria = $arrObjEouvParametroDTO[$i]->getStrDeValorParametro();
                        break;

                    case "TOKEN":
                        $this->token = $arrObjEouvParametroDTO[$i]->getStrDeValorParametro();
                        break;

                    case "IMPORTAR_DADOS_MANIFESTANTE":
                        $this->importar_dados_manifestante = $arrObjEouvParametroDTO[$i]->getStrDeValorParametro();
                        break;


                }
            }
        }
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
            die;
        }

    }

    /**
     * Fun��o para importar as manifesta��es e-Ouv do FalaBR
     *
     * Tipos: 1, 2, 3, 4, 5, 6 e 7
     */
    public function executarImportacaoManifestacaoEOuv()
    {
        // Log
        LogSEI::getInstance()->gravar('Rotina de Importa��o de Manifesta��es do E-Ouv', InfraLog::$INFORMACAO);

        $objEouvParametroDTO = new MdCguEouvParametroDTO();
        $objEouvParametroDTO->retTodos();

        // Busca par�metros do banco de dados
        $objEouvParametroRN = new MdCguEouvParametroRN();
        $arrObjEouvParametroDTO = $objEouvParametroRN->listarParametro($objEouvParametroDTO);

        $numRegistros = count($arrObjEouvParametroDTO);

        $this->preencheVariaveisEouv($numRegistros, $arrObjEouvParametroDTO);

        /**
         * Fun��o para buscar o 'restante' do token sem o limite de 255 caracteres do SEI
         */
        $tokenPart2 = BancoSEI::getInstance()->consultarSql('select substring(de_valor_parametro, 256, 455) from md_eouv_parametros where id_parametro=10;')[0]['computed'];
        $this->token = $this->token . $tokenPart2;

        $dataAtual = InfraData::getStrDataHoraAtual();

        $isBolHabilitada = SessaoSEI::getInstance(false)->isBolHabilitada();
        SessaoSEI::getInstance()->setBolHabilitada(false);

        $mdCguEouvWS = new MdCguEouvWS($this->urlWebServiceEOuv, $this->urlWebServiceESicRecursos, $this->idTipoDocumentoAnexoDadosManifestacao,
            $this->idUnidadeOuvidoria, $this->idUnidadeEsicPrincipal, $this->idUnidadeRecursoPrimeiraInstancia, $this->idUnidadeRecursoSegundaInstancia,
            $this->idUnidadeRecursoTerceiraInstancia, $this->idUnidadeRecursoPedidoRevisao, $this->token);
        // Simula login inicial
        $mdCguEouvWS->simulaLogin($this->siglaSistema, $this->identificacaoServico, $this->idUnidadeOuvidoria);

        try {

            //Retorna dados da �ltima execu��o com Sucesso
            $objUltimaExecucao = MdCguEouvAgendamentoINT::retornarUltimaExecucaoSucesso();

            if ($objUltimaExecucao != null) {
                $ultimaDataExecucao = $objUltimaExecucao->getDthDthPeriodoFinal();
            } //Primeira execu��o ou nenhuma executada com sucesso
            else {
                $ultimaDataExecucao = $this->dataInicialImportacaoManifestacoes;
            }

            $semManifestacoesEncontradas = true;
            $qtdManifestacoesNovas = 0;
            $qtdManifestacoesAntigas = 0;
            $objEouvRelatorioImportacaoDTO = $this->gravarLogImportacao($ultimaDataExecucao, $dataAtual);
            $idRelatorioImportacao = $objEouvRelatorioImportacaoDTO->getNumIdRelatorioImportacao();
            $objEouvRelatorioImportacaoRN = new MdCguEouvRelatorioImportacaoRN();
            $SinSucessoExecucao = 'N';
            $textoMensagemErroToken = '';

            $retornoWs = $mdCguEouvWS->executarServicoConsultaManifestacoes($this->urlWebServiceEOuv, $this->token, $ultimaDataExecucao, $dataAtual, null, $idRelatorioImportacao);

            //Caso retornado algum erro
            if (is_string($retornoWs)) {
                if (strpos($retornoWs, 'Invalidado') !== false) {
                    //Tenta gerar novo token
                    $tokenValido = MdCguEouvWS::apiValidarToken($this->urlWebServiceEOuv, $this->usuarioWebService, $this->senhaUsuarioWebService, $this->client_id, $this->client_secret);

                    if (isset($tokenValido['error'])) {
                        $textoMensagemErroToken = 'N�o foi poss�vel validar o Token de acesso aos WebServices do E-ouv. <br>Verifique as informa��es de Usu�rio, Senha, Client_Id e Client_Secret nas configura��es de Par�metros do M�dulo';

                    } elseif (isset($tokenValido['access_token'])) {
                        $mdCguEouvWS->gravarParametroToken($tokenValido['access_token']);
                        $token = $tokenValido['access_token'];

                        //Chama novamente a execu��o da ConsultaManifestacao que deu errado por causa do Token
                        $retornoWs = $mdCguEouvWS->executarServicoConsultaManifestacoes($this->urlWebServiceEOuv, $token, $ultimaDataExecucao, $dataAtual, null, $idRelatorioImportacao);
                    }
                }
            }

            if ($textoMensagemErroToken == '') {
                $arrComErro = $mdCguEouvWS->obterManifestacoesComErro($this->urlWebServiceEOuv, $token, $ultimaDataExecucao, $dataAtual, $idRelatorioImportacao);

                $arrManifestacoes = array();

                if (is_array($retornoWs)) {
                    // Filtra as manifesta��es e-Ouv
                    $arrManifestacoes = array_filter($retornoWs, function($manifestacao) {
                        return $manifestacao['TipoManifestacao']['IdTipoManifestacao'] <> 8;
                    });
                    $qtdManifestacoesNovas = count($arrManifestacoes);
                }

                if (is_array($arrComErro)) {
                    $qtdManifestacoesAntigas = count($arrComErro);
                    $arrManifestacoes = array_merge($arrManifestacoes, $arrComErro);
                }

                if (count($arrManifestacoes) > 0) {
                    $semManifestacoesEncontradas = false;
                    foreach ($arrManifestacoes as $retornoWsLinha) {
                        $mdCguEouvWS->executarImportacaoLinha($retornoWsLinha, 'P', $idRelatorioImportacao);
                    }
                }

                $textoMensagemFinal = 'Execu��o Finalizada com Sucesso!';
                $SinSucessoExecucao = 'S';

                if ($semManifestacoesEncontradas) {
                    $textoMensagemFinal = $textoMensagemFinal . ' N�o foram encontradas manifesta��es para o per�odo.';
                } else {
                    $textoMensagemFinal = $textoMensagemFinal . '<br>Quantidade de Manifesta��es novas encontradas (e-Ouv): ' . $qtdManifestacoesNovas . '<br>Quantidade de Manifesta��es encontadas que ocorreram erro em outras importa��es: ' . $qtdManifestacoesAntigas;
                }

                if ($this->ocorreuErroEmProtocolo) {
                    $textoMensagemFinal = $textoMensagemFinal . '<br> Ocorreram erros em 1 ou mais protocolos.';
                }
            } else {
                $textoMensagemFinal = $textoMensagemErroToken;
            }

            //Grava a execu��o com sucesso se tiver corrido tudo bem
            $objEouvRelatorioImportacaoDTO2 = new MdCguEouvRelatorioImportacaoDTO();

            $objEouvRelatorioImportacaoDTO2->setNumIdRelatorioImportacao($objEouvRelatorioImportacaoDTO->getNumIdRelatorioImportacao());
            $objEouvRelatorioImportacaoDTO2->setStrSinSucesso($SinSucessoExecucao);
            $objEouvRelatorioImportacaoDTO2->setStrDeLogProcessamento($textoMensagemFinal);
            $objEouvRelatorioImportacaoRN->alterar($objEouvRelatorioImportacaoDTO2);



        } catch(Exception $e) {

            $objEouvRelatorioImportacaoDTO3 = new MdCguEouvRelatorioImportacaoDTO();
            $objEouvRelatorioImportacaoDTO3->setNumIdRelatorioImportacao($objEouvRelatorioImportacaoDTO->getNumIdRelatorioImportacao());
            $strMensagem = 'Ocorreu um erro no processamento:' . $e;
            $strMensagem = substr($strMensagem, 0, 500);
            $objEouvRelatorioImportacaoDTO3->setStrDeLogProcessamento($strMensagem);

            $objEouvRelatorioImportacaoRN->alterar($objEouvRelatorioImportacaoDTO3);

            PaginaSEI::getInstance()->processarExcecao($e);
        } finally {
            //Restaura a sess�o
            SessaoSEI::getInstance()->setBolHabilitada($isBolHabilitada);
        }
    }

    /**
     * Fun��o para importar as manifesta��es e-Sic do FalaBR (tipo 8)
     */
    public function executarImportacaoManifestacaoESic()
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
        $dataAtual = InfraData::getStrDataHoraAtual();

        $isBolHabilitada = SessaoSEI::getInstance(false)->isBolHabilitada();
        SessaoSEI::getInstance()->setBolHabilitada(false);

        $mdCguEouvWS = new MdCguEouvWS($this->urlWebServiceEOuv, $this->urlWebServiceESicRecursos, $this->idTipoDocumentoAnexoDadosManifestacao,
            $this->idUnidadeOuvidoria, $this->idUnidadeEsicPrincipal, $this->idUnidadeRecursoPrimeiraInstancia, $this->idUnidadeRecursoSegundaInstancia,
            $this->idUnidadeRecursoTerceiraInstancia, $this->idUnidadeRecursoPedidoRevisao, $this->token);
        // Simula login inicial
        $mdCguEouvWS->simulaLogin($this->siglaSistema, $this->identificacaoServico, $this->idUnidadeEsicPrincipal);

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
            $idRelatorioImportacao = $objEouvRelatorioImportacaoDTO->getNumIdRelatorioImportacao();
            $objEouvRelatorioImportacaoRN = new MdCguEouvRelatorioImportacaoRN();
            $SinSucessoExecucao = 'N';
            $textoMensagemErroToken = '';

            /**
             * As fun��es abaixo fazem a busca no webservice dos dados a serem trabalhados na rotina de importa��o
             */
            $debugLocal && LogSEI::getInstance()->gravar('Iniciando a consulta inicial');

            $retornoWs = $mdCguEouvWS->executarServicoConsultaManifestacoes($this->urlWebServiceEOuv, $this->token, $ultimaDataExecucao, $dataAtual, null, $idRelatorioImportacao);
            $retornoWsRecursos = $mdCguEouvWS->executarServicoConsultaRecursos($this->urlWebServiceESicRecursos, $this->token, $ultimaDataExecucao, $dataAtual, null, $idRelatorioImportacao);

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
                        $token = $tokenValido['access_token'];

                        //Chama novamente a execu��o da ConsultaManifestacao que deu errado por causa do Token
                        $retornoWs = $mdCguEouvWS->executarServicoConsultaManifestacoes($this->urlWebServiceEOuv, $token, $ultimaDataExecucao, $dataAtual, null, $idRelatorioImportacao);
                        $retornoWsRecursos = $mdCguEouvWS->executarServicoConsultaRecursos($this->urlWebServiceESicRecursos, $token, $ultimaDataExecucao, $dataAtual, null, $idRelatorioImportacao);
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
                $arrComErro = $mdCguEouvWS->obterManifestacoesComErro($this->urlWebServiceEOuv, $token, $ultimaDataExecucao, $dataAtual, $idRelatorioImportacao, 'R');

                $arrManifestacoes = array();

                if (is_array($retornoWs)) {
                    // Filtra as manifesta��es e-Sic
                    $arrManifestacoes = array_filter($retornoWs, function($manifestacao) {
                        return $manifestacao['TipoManifestacao']['IdTipoManifestacao'] == 8;
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

                // Importa manifesta��es e-Sic
                if (count($arrManifestacoes) > 0) {
                    $semManifestacoesEncontradas = false;
                    foreach ($arrManifestacoes as $retornoWsLinha) {
                        $debugLocal && LogSEI::getInstance()->gravar('Inicia importa��o por Linha');
                        $mdCguEouvWS->executarImportacaoLinha($retornoWsLinha, 'R',$idRelatorioImportacao);
                    }
                }

                // Importa recursos e-Sic
                if (count($arrRecursos) > 0) {
                    $semRecursosEncontrados = false;
                    foreach ($arrRecursos as $retornoWsLinha) {
                        $debugLocal && LogSEI::getInstance()->gravar('Inicia importa��o por linha de Recursos - protocolo: ' . $retornoWsLinha['numProtocolo']);
                        $mdCguEouvWS->executarImportacaoLinhaRecursos($retornoWsLinha);
                    }
                }

                $textoMensagemFinal = 'Execu��o Finalizada com Sucesso!';
                $SinSucessoExecucao = 'S';

                if ($semManifestacoesEncontradas) {
                    $textoMensagemFinal = $textoMensagemFinal . ' N�o foram encontradas manifesta��es para o per�odo.';
                } else {
                    $textoMensagemFinal = $textoMensagemFinal . '<br>Quantidade de Manifesta��es novas encontradas (e-Sic): ' . $qtdManifestacoesNovas . '<br>Quantidade de Manifesta��es encontadas que ocorreram erro em outras importa��es: ' . $qtdManifestacoesAntigas;
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
            $objEouvRelatorioImportacaoDTO2 = new MdCguEouvRelatorioImportacaoDTO();

            $objEouvRelatorioImportacaoDTO2->setNumIdRelatorioImportacao($objEouvRelatorioImportacaoDTO->getNumIdRelatorioImportacao());
            $objEouvRelatorioImportacaoDTO2->setStrSinSucesso($SinSucessoExecucao);
            $objEouvRelatorioImportacaoDTO2->setStrDeLogProcessamento($textoMensagemFinal);
            $objEouvRelatorioImportacaoDTO2->setStrTipManifestacao('R');
            $objEouvRelatorioImportacaoRN->alterar($objEouvRelatorioImportacaoDTO2);

            LogSEI::getInstance()->gravar('Finalizado a import��o dos processos e-Sic - FalaBR');

        } catch(Exception $e) {

            $objEouvRelatorioImportacaoDTO3 = new MdCguEouvRelatorioImportacaoDTO();
            $objEouvRelatorioImportacaoDTO3->setNumIdRelatorioImportacao($objEouvRelatorioImportacaoDTO->getNumIdRelatorioImportacao());
            $strMensagem = 'Ocorreu um erro no processamento:' . $e;
            $strMensagem = substr($strMensagem, 0, 500);
            $objEouvRelatorioImportacaoDTO3->setStrDeLogProcessamento($strMensagem);
            $objEouvRelatorioImportacaoRN->alterar($objEouvRelatorioImportacaoDTO3);

            PaginaSEI::getInstance()->processarExcecao($e);

        } finally {
            //Restaura a Sess�o
            SessaoSEI::getInstance()->setBolHabilitada($isBolHabilitada);
        }
    }
}
?>
