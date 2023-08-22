<?php

/**
 * CONTROLADORIA GERAL DA UNIÃO- CGU
 *
 * 09/10/2015 - criado por Rafael Leandro
 *
 */

error_reporting(E_ALL); ini_set('display_errors', '1');

require_once dirname(__FILE__) . '/../../../../SEI.php';

class MdCguEouvAgendamentoRN extends InfraRN
{
    protected $objEouvRelatorioImportacaoDTO;
    protected $objEouvRelatorioImportacaoRN;
    protected $objInfraParametro;
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
    protected $idUsuarioSei;
    protected $dataAtual;
    protected $objUltimaExecucao;
    protected $ocorreuErroEmProtocolo;
    protected $idRelatorioImportacao;
    protected $usuarioWebService;
    protected $senhaUsuarioWebService;
    protected $client_id;
    protected $client_secret;
    protected $token;
    protected $importar_dados_manifestante;
    protected $dataInicialImportacaoManifestacoes;

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
        // Preenche variáveis locais com dados da tabela md_eouv_parametros
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
            die;
        }

    }

    /**
     * Função para importar as manifestações e-Ouv do FalaBR
     *
     * Tipos: 1, 2, 3, 4, 5, 6 e 7
     */
    public function executarImportacaoManifestacaoEOuv()
    {
        // Log
        LogSEI::getInstance()->gravar('Rotina de Importação de Manifestações do E-Ouv', InfraLog::$INFORMACAO);

        $objEouvParametroDTO = new MdCguEouvParametroDTO();
        $objEouvParametroDTO->retTodos();

        // Busca parâmetros do banco de dados
        $objEouvParametroRN = new MdCguEouvParametroRN();
        $arrObjEouvParametroDTO = $objEouvParametroRN->listarParametro($objEouvParametroDTO);

        $numRegistros = count($arrObjEouvParametroDTO);

        $this->preencheVariaveisEouv($numRegistros, $arrObjEouvParametroDTO);

        /**
         * Função para buscar o 'restante' do token sem o limite de 255 caracteres do SEI
         */
        $tokenPart2 = BancoSEI::getInstance()->consultarSql('select substring(de_valor_parametro, 256, 455) from md_eouv_parametros where id_parametro=10;')[0]['computed'];
        $this->token = $this->token . $tokenPart2;

        $dataAtual = InfraData::getStrDataHoraAtual();
        $SiglaSistema = 'EOUV';
        $IdentificacaoServico = 'CadastrarManifestacao';

        $isBolHabilitada = SessaoSEI::getInstance(false)->isBolHabilitada();
        SessaoSEI::getInstance()->setBolHabilitada(false);

        // Simula login inicial
        $this->simulaLogin($SiglaSistema, $IdentificacaoServico, $this->idUnidadeOuvidoria);

        try {

            //Retorna dados da Última execução com Sucesso
            $objUltimaExecucao = MdCguEouvAgendamentoINT::retornarUltimaExecucaoSucesso();

            if ($objUltimaExecucao != null) {
                $ultimaDataExecucao = $objUltimaExecucao->getDthDthPeriodoFinal();
                $idUltimaExecucao = $objUltimaExecucao->getNumIdRelatorioImportacao();
            } //Primeira execução ou nenhuma executada com sucesso
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

            $retornoWs = $this->executarServicoConsultaManifestacoes($this->urlWebServiceEOuv, $this->token, $ultimaDataExecucao, $dataAtual, null, $idRelatorioImportacao);

            //Caso retornado algum erro
            if (is_string($retornoWs)) {
                if (strpos($retornoWs, 'Invalidado') !== false) {
                    //Tenta gerar novo token
                    $tokenValido = MdCguEouvWS::apiValidarToken($this->urlWebServiceEOuv, $this->usuarioWebService, $this->senhaUsuarioWebService, $this->client_id, $this->client_secret);

                    if (isset($tokenValido['error'])) {
                        $textoMensagemErroToken = 'Não foi possível validar o Token de acesso aos WebServices do E-ouv. <br>Verifique as informações de Usuário, Senha, Client_Id e Client_Secret nas configurações de Parâmetros do Módulo';

                    } elseif (isset($tokenValido['access_token'])) {
                        $this->gravarParametroToken($tokenValido['access_token']);
                        $token = $tokenValido['access_token'];

                        //Chama novamente a execução da ConsultaManifestacao que deu errado por causa do Token
                        $retornoWs = $this->executarServicoConsultaManifestacoes($this->urlWebServiceEOuv, $token, $ultimaDataExecucao, $dataAtual, null, $idRelatorioImportacao);
                    }
                }
            }

            if ($textoMensagemErroToken == '') {
                $arrComErro = $this->obterManifestacoesComErro($this->urlWebServiceEOuv, $token, $ultimaDataExecucao, $dataAtual, $idRelatorioImportacao);

                $arrManifestacoes = array();

                if (is_array($retornoWs)) {
                    // Filtra as manifestações e-Ouv
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
                        $this->executarImportacaoLinha($retornoWsLinha);
                    }
                }

                $textoMensagemFinal = 'Execução Finalizada com Sucesso!';
                $SinSucessoExecucao = 'S';

                if ($semManifestacoesEncontradas) {
                    $textoMensagemFinal = $textoMensagemFinal . ' Não foram encontradas manifestações para o período.';
                } else {
                    $textoMensagemFinal = $textoMensagemFinal . '<br>Quantidade de Manifestações novas encontradas (e-Ouv): ' . $qtdManifestacoesNovas . '<br>Quantidade de Manifestações encontadas que ocorreram erro em outras importações: ' . $qtdManifestacoesAntigas;
                }

                if ($this->ocorreuErroEmProtocolo) {
                    $textoMensagemFinal = $textoMensagemFinal . '<br> Ocorreram erros em 1 ou mais protocolos.';
                }
            } else {
                $textoMensagemFinal = $textoMensagemErroToken;
            }

            //Grava a execução com sucesso se tiver corrido tudo bem
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
            //Restaura a sessão
            SessaoSEI::getInstance()->setBolHabilitada($isBolHabilitada);
        }
    }

    /**
     * Função para importar as manifestações e-Sic do FalaBR (tipo 8)
     */
    public function executarImportacaoManifestacaoESic()
    {
        $debugLocal = false;

        // Log
        LogSEI::getInstance()->gravar('Rotina de Importação de Manifestações do FalaBR (e-Sic)', InfraLog::$INFORMACAO);

        // Lista parâmetros
        $objEouvParametroDTO = new MdCguEouvParametroDTO();
        $objEouvParametroDTO->retTodos();

        // Busca parâmetros do banco de dados da tabela md_eouv_parametros
        $objEouvParametroRN = new MdCguEouvParametroRN();
        $arrObjEouvParametroDTO = $objEouvParametroRN->listarParametro($objEouvParametroDTO);
        $numRegistros = count($arrObjEouvParametroDTO);

        $this->preencheVariaveis($numRegistros, $arrObjEouvParametroDTO);

        /**
         * Função para buscar o 'restante' do token sem o limite de 255 caracteres do SEI
         */
        $tokenPart2 = BancoSEI::getInstance()->consultarSql("select substring(de_valor_parametro, 256, 455) from md_eouv_parametros where no_parametro='TOKEN';")[0]['computed'];
        $this->token = $this->token . $tokenPart2;

        // Busca parâmetros do banco de dados da tabela infra_parametros
        $dataAtual = InfraData::getStrDataHoraAtual();
        $SiglaSistema = 'EOUV';
        $IdentificacaoServico = 'CadastrarManifestacao';

        $isBolHabilitada = SessaoSEI::getInstance(false)->isBolHabilitada();
        SessaoSEI::getInstance()->setBolHabilitada(false);

        // Simula login inicial
        $this->simulaLogin($SiglaSistema, $IdentificacaoServico, $this->idUnidadeEsicPrincipal);

        // Executa a importação dos dados
        try {
            //Retorna dados da Última execução com Sucesso
            $objUltimaExecucao = MdCguEouvAgendamentoINT::retornarUltimaExecucaoSucesso('R');

            if ($objUltimaExecucao != null) {
                // Debug Logs
                $debugLocal && LogSEI::getInstance()->gravar('$objUltimaExecuxao (e-Sic):' . $objUltimaExecucao->getDthDthPeriodoFinal());

                $ultimaDataExecucao = $objUltimaExecucao->getDthDthPeriodoFinal();
            } else {
                // Debug Logs
                $debugLocal && LogSEI::getInstance()->gravar('$objUltimaExecuxao (e-Sic) é NULL');

                //Primeira execução ou nenhuma executada com sucesso
                $ultimaDataExecucao = $this->dataInicialImportacaoManifestacoes;
            }

            $semManifestacoesEncontradas = true;
            $qtdManifestacoesNovas = 0;
            $qtdManifestacoesAntigas = 0;
            $semRecursosEncontrados = true;
            $qtdRecursosNovos = 0;

            /**
             * A função abaixo gravarLogImportacao recebe o tipo de manifestação 'R' (Recursos) para as manifestações do e-Sic
             */
            $objEouvRelatorioImportacaoDTO = $this->gravarLogImportacao($ultimaDataExecucao, $dataAtual, 'R');
            $idRelatorioImportacao = $objEouvRelatorioImportacaoDTO->getNumIdRelatorioImportacao();
            $objEouvRelatorioImportacaoRN = new MdCguEouvRelatorioImportacaoRN();
            $SinSucessoExecucao = 'N';
            $textoMensagemErroToken = '';

            /**
             * As funções abaixo fazem a busca no webservice dos dados a serem trabalhados na rotina de importação
             */
            $debugLocal && LogSEI::getInstance()->gravar('Iniciando a consulta inicial');
            $retornoWs = $this->executarServicoConsultaManifestacoes($this->urlWebServiceEOuv, $this->token, $ultimaDataExecucao, $dataAtual, null, $idRelatorioImportacao);
            $retornoWsRecursos = $this->executarServicoConsultaRecursos($this->urlWebServiceESicRecursos, $this->token, $ultimaDataExecucao, $dataAtual, null, $idRelatorioImportacao);

            //Caso retornado algum erro - Manifestações e-Sic
            if (is_string($retornoWs)) {
                $debugLocal && LogSEI::getInstance()->gravar('Retorno da consulta $retornoWs é uma string: ' . $retornoWs);

                if (strpos($retornoWs, 'Invalidado') !== false) {
                    //Tenta gerar novo token
                    $tokenValido = MdCguEouvWS::apiValidarToken($this->urlWebServiceEOuv, $this->usuarioWebService, $this->senhaUsuarioWebService, $this->client_id, $this->client_secret);

                    if (isset($tokenValido['error'])) {
                        $textoMensagemErroToken = 'Não foi possível validar o Token de acesso aos WebServices do E-ouv. <br>Verifique as informações de Usuário, Senha, Client_Id e Client_Secret nas configurações de Parâmetros do Módulo';

                    } elseif (isset($tokenValido['access_token'])) {
                        $this->gravarParametroToken($tokenValido['access_token']);
                        $token = $tokenValido['access_token'];

                        //Chama novamente a execução da ConsultaManifestacao que deu errado por causa do Token
                        $retornoWs = $this->executarServicoConsultaManifestacoes($this->urlWebServiceEOuv, $token, $ultimaDataExecucao, $dataAtual, null, $idRelatorioImportacao);
                        $retornoWsRecursos = $this->executarServicoConsultaRecursos($this->urlWebServiceESicRecursos, $token, $ultimaDataExecucao, $dataAtual, null, $idRelatorioImportacao);
                    }
                }
            }

            /**
             * @todo - criar rotina para buscar recursos das manifestções com erro caso exista alguma na tabela de log
             */

            if ($textoMensagemErroToken == '') {

                /**
                 * @debug - manifestacao com erro
                 * Comentar a linha abaixo para debugar um retorno manual
                 */
                $debugLocal && LogSEI::getInstance()->gravar('Inicia busca manifestação com erros');
                $arrComErro = $this->obterManifestacoesComErro($this->urlWebServiceEOuv, $token, $ultimaDataExecucao, $dataAtual, $idRelatorioImportacao, 'R');

                $arrManifestacoes = array();

                if (is_array($retornoWs)) {
                    // Filtra as manifestações e-Sic
                    $arrManifestacoes = array_filter($retornoWs, function($manifestacao) {
                        return $manifestacao['TipoManifestacao']['IdTipoManifestacao'] == 8;
                    });
                    $qtdManifestacoesNovas = count($arrManifestacoes);
                    $debugLocal && LogSEI::getInstance()->gravar('Possui novas manifestações qtd: ' . $qtdManifestacoesNovas);
                }

                $arrRecursos = array();
                if (isset($retornoWsRecursos) && is_array($retornoWsRecursos)) {
                    $debugLocal && LogSEI::getInstance()->gravar('Possui recursos qtd: ' . count($retornoWsRecursos['Recursos']));
                    $arrRecursos = $retornoWsRecursos['Recursos'];
                    $qtdRecursosNovos = count($arrRecursos);
                }

                if (is_array($arrComErro)) {
                    $debugLocal && LogSEI::getInstance()->gravar('Possui manifestações com erros - qtd: ' . count($arrComErro));
                    $qtdManifestacoesAntigas = count($arrComErro);
                    $arrManifestacoes = array_merge($arrManifestacoes, $arrComErro);
                }

                // Importa manifestações e-Sic
                if (count($arrManifestacoes) > 0) {
                    $semManifestacoesEncontradas = false;
                    foreach ($arrManifestacoes as $retornoWsLinha) {
                        $debugLocal && LogSEI::getInstance()->gravar('Inicia importação por Linha');
                        $this->executarImportacaoLinha($retornoWsLinha, 'R');
                    }
                }

                // Importa recursos e-Sic
                if (count($arrRecursos) > 0) {
                    $semRecursosEncontrados = false;
                    foreach ($arrRecursos as $retornoWsLinha) {
                        $debugLocal && LogSEI::getInstance()->gravar('Inicia importação por linha de Recursos - protocolo: ' . $retornoWsLinha['numProtocolo']);
                        $this->executarImportacaoLinhaRecursos($retornoWsLinha);
                    }
                }

                $textoMensagemFinal = 'Execução Finalizada com Sucesso!';
                $SinSucessoExecucao = 'S';

                if ($semManifestacoesEncontradas) {
                    $textoMensagemFinal = $textoMensagemFinal . ' Não foram encontradas manifestações para o período.';
                } else {
                    $textoMensagemFinal = $textoMensagemFinal . '<br>Quantidade de Manifestações novas encontradas (e-Sic): ' . $qtdManifestacoesNovas . '<br>Quantidade de Manifestações encontadas que ocorreram erro em outras importações: ' . $qtdManifestacoesAntigas;
                }

                if ($semRecursosEncontrados) {
                    $textoMensagemFinal = $textoMensagemFinal . ' Não foram encontrados recursos para o período.';
                } else {
                    $textoMensagemFinal = $textoMensagemFinal . '<br>Quantidade de Recursos novos encontrados (e-Sic): ' . $qtdRecursosNovos;
                }

                if ($this->ocorreuErroEmProtocolo) {
                    $textoMensagemFinal = $textoMensagemFinal . '<br> Ocorreram erros em 1 ou mais protocolos.';
                }
            } else {
                $textoMensagemFinal = $textoMensagemErroToken;
            }

            //Grava a execução com sucesso se tiver corrido tudo bem
            $objEouvRelatorioImportacaoDTO2 = new MdCguEouvRelatorioImportacaoDTO();

            $objEouvRelatorioImportacaoDTO2->setNumIdRelatorioImportacao($objEouvRelatorioImportacaoDTO->getNumIdRelatorioImportacao());
            $objEouvRelatorioImportacaoDTO2->setStrSinSucesso($SinSucessoExecucao);
            $objEouvRelatorioImportacaoDTO2->setStrDeLogProcessamento($textoMensagemFinal);
            $objEouvRelatorioImportacaoDTO2->setStrTipManifestacao('R');
            $objEouvRelatorioImportacaoRN->alterar($objEouvRelatorioImportacaoDTO2);

            LogSEI::getInstance()->gravar('Finalizado a importção dos processos e-Sic - FalaBR');

        } catch(Exception $e) {

            $objEouvRelatorioImportacaoDTO3 = new MdCguEouvRelatorioImportacaoDTO();
            $objEouvRelatorioImportacaoDTO3->setNumIdRelatorioImportacao($objEouvRelatorioImportacaoDTO->getNumIdRelatorioImportacao());
            $strMensagem = 'Ocorreu um erro no processamento:' . $e;
            $strMensagem = substr($strMensagem, 0, 500);
            $objEouvRelatorioImportacaoDTO3->setStrDeLogProcessamento($strMensagem);
            $objEouvRelatorioImportacaoRN->alterar($objEouvRelatorioImportacaoDTO3);

            PaginaSEI::getInstance()->processarExcecao($e);

        } finally {
            //Restaura a Sessão
            SessaoSEI::getInstance()->setBolHabilitada($isBolHabilitada);
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

    public function gerarAnexosProtocolo($arrAnexosManifestacao, $numProtocoloFormatado, $tipoManifestacao = 'P', $IdProtocolo = false)
    {
        /**********************************************************************************************************************************************
         * Início da importação de anexos de cada protocolo
         * Desativado momentaneamente
         */

        $objInfraParametro = new InfraParametro(BancoSEI::getInstance());
        $arrAnexosAdicionados = array();

        $intTotAnexos = count($arrAnexosManifestacao);

        if($intTotAnexos == 0){
            //Não encontrou anexos..
            return $arrAnexosAdicionados;
        }

        //Trata as extensÃµes permitidas
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

            foreach ($this->verificaRetornoWS($retornoWsAnexoLista) as $retornoWsAnexoLinha) {
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

                        //Busca o conteúdo do Anexo
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

                        if ($this->hashDuplicado(DIR_SEI_TEMP . '/' . $strNomeArquivoUpload, $numProtocoloFormatado)) {

                        } else {
                            if ($IdProtocolo && $IdProtocolo <> '') {
                                $objSEIRN = new SeiRN();
                                $objSEIRN->incluirDocumento($objAnexoManifestacao);
                            }

                            array_push($arrAnexosAdicionados, $objAnexoManifestacao);
                        }
                    } else {
                        $ocorreuErroAdicionarAnexo = true;
                        LogSEI::getInstance()->gravar('Importação de Manifestação ' . $numProtocoloFormatado . ': Arquivo ' . $strNomeArquivoOriginal . ' possui extensão inválida.', InfraLog::$INFORMACAO);
                        continue;
                    }
                }
                catch(Exception $e){
                    $ocorreuErroAdicionarAnexo = true;
                    $strMensagemErroAnexos = $strMensagemErroAnexos . " " . $e;
                }
            }

            if($ocorreuErroAdicionarAnexo==true){
                $this->gravarLogLinha($numProtocoloFormatado, $this->idRelatorioImportacao, 'Um ou mais documentos anexos não foram importados corretamente: ' . $strMensagemErroAnexos, 'S', $tipoManifestacao);
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

    /**
     * Verifica se já existe um Protocolo no SEI com o número (NUP)
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
     * Verifica se já existe o hash do arquivo na tabela anexo coluna hash
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
     * Função para simular login
     *
     * @param $siglaSistema
     * @param $idServico
     * @param $idUnidade
     */
    public function simulaLogin($siglaSistema, $idServico, $idUnidade)
    {
        try {

            InfraDebug::getInstance()->gravar(__METHOD__);
            InfraDebug::getInstance()->gravar('SIGLA SISTEMA:'.$siglaSistema);
            InfraDebug::getInstance()->gravar('IDENTIFICACAO SERVICO:'.$idServico);
            InfraDebug::getInstance()->gravar('ID UNIDADE:'.$idUnidade);

            SessaoSEI::getInstance(false);

            $objServicoDTO = $this->obterServico($siglaSistema, $idServico);

            if ($idUnidade!=null) {
                $objUnidadeDTO = $this->obterUnidade($idUnidade,null);
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
     * - 1 = primeira instância
     * - 2 = segunda instância
     *
     * @param null $recursos
     * @return string
     *
     * - 'P' - Padrão, não possui recursos de primeira ou segunda instância
     * - 'R1' - Recurso de primeira instância
     * - 'R2' - Recurso de segunda instância
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
            return 'PR'; // Pedido Revisão
        }
        if ($recurso['instancia']['IdInstanciaRecurso'] == 7) {
            return 'R3'; // Recurso 3 instância
        }
        if ($recurso['instancia']['IdInstanciaRecurso'] == 4) {
            return 'RE'; // Reclamação
        }
        if ($recurso['instancia']['IdInstanciaRecurso'] == 3) {
            return 'RC'; // Recurso CGU
        }
        if ($recurso['instancia']['IdInstanciaRecurso'] == 2) {
            return 'R2'; // Recurso 2 instância
        }
        if ($recurso['instancia']['IdInstanciaRecurso'] == 1) {
            return 'R1'; // Recurso 1 instância
        }

        return 'R';
    }

    /**
     * Verifica se existe recurso 'posterior' cadastrado
     *
     * - Posterior está entre aspas pq o recurso deve seguir uma órdem cronológica para se adequar à importação dos
     * dados no SEI
     *
     * @param $idRelatorioImportacao
     * @param $numProtocolo
     * @param $tipoManifestacao
     * @return bool|void
     */
    public function permiteImportacaoRecursoAtual($tipoManifestacaoAtual, $ultimoTipoRecursoImportado)
    {
        $debugLocal = false;

        $debugLocal && LogSEI::getInstance()->gravar('[permiteImportacaoRecursoAtual] Verificando se existe algum recurso anterior');

        // Se ja existir no log um recurso anterior verifica se o novo recurso e 'superior' ao já registrado
        if ($tipoManifestacaoAtual) {

            $debugLocal && LogSEI::getInstance()->gravar('[permiteImportacaoRecursoAtual] Existe log, validando o tipo de manifestação: ' . $tipoManifestacaoAtual . ' para o anteior existente: ' . $ultimoTipoRecursoImportado);

            /**
             * [CUIDADO] Nâo é possível utilizar o 'switch > case' aqui - não sei o por quê, mas não funciona....  @study (??)
             */

            /**
             * Para criar um R1 (Recurso de Primeira Instância) pode existir somente PR (Pedido de Revisão),
             * R (Pedido Inicial do e-Sic)
             */
            if ($tipoManifestacaoAtual == 'R1' && in_array($ultimoTipoRecursoImportado, ['R2', 'RC', 'R3'])) {
                $debugLocal && LogSEI::getInstance()->gravar('[permiteImportacaoRecursoAtual] Deve bloquear a criação deste recurso! tipoAtual: ' . $tipoManifestacaoAtual . ' - tipoAnterior: ' . $ultimoTipoRecursoImportado);
                return 'bloquear';
            }

            /**
             * Para criar um R2 (Recurso de Segunda Instância) pode existir somente R1 (Recurso de Primeira Instância),
             * PR (Pedido de Revisão), R (Pedido Inicial do e-Sic)
             */
            if ($tipoManifestacaoAtual == 'R2' && in_array($ultimoTipoRecursoImportado, ['RC', 'R3'])) {
                $debugLocal && LogSEI::getInstance()->gravar('[permiteImportacaoRecursoAtual] Deve bloquear a criação deste recurso! tipoAtual: ' . $tipoManifestacaoAtual . ' - tipoAnterior: ' . $ultimoTipoRecursoImportado);
                return 'bloquear';
            }

            /**
             * Se for tipo 4 - Reclamação - não importar
             */
            if ($tipoManifestacaoAtual == 'RE') {
                $debugLocal && LogSEI::getInstance()->gravar('[permiteImportacaoRecursoAtual] Deve bloquear a criação deste recurso! tipoAtual: ' . $tipoManifestacaoAtual . ' - tipoAnterior: ' . $ultimoTipoRecursoImportado);
                return 'bloquear';
            }

            /**
             * Para criar um RC ainda não existe regra interna definida
             */
//            if ($tipoManifestacaoAtual == 'RC') {}

            /**
             * Para criar um RC ainda não existe regra interna definida
             */
//            if ($tipoManifestacaoAtual == 'R3') {}

            /**
             * Para criar um PR (Pedido de Revisão) pode existir somente R (Pedido Inicial do e-Sic)
             */
            if ($tipoManifestacaoAtual == 'PR' && in_array($ultimoTipoRecursoImportado, ['R1', 'R2', 'RC', 'R3'])) {
                $debugLocal && LogSEI::getInstance()->gravar('[permiteImportacaoRecursoAtual] Deve bloquear a criação deste recurso! tipoAtual: ' . $tipoManifestacaoAtual . ' - tipoAnterior: ' . $ultimoTipoRecursoImportado);
                return 'bloquear';
            }
        }

        /**
         * Se existir algo na tabela, porém, não estiver definido na regra acima ou se não existir nenhum registro na
         * tabela, a importação será permitida
         * [CUIDADO] Caso haja duplicidade na importação, pode haver algum tipo de recurso não mapeado no campo
         * "instancia": { "IdInstanciaRecurso": ## > na API do FalaBR
         */
        $debugLocal && LogSEI::getInstance()->gravar('[permiteImportacaoRecursoAtual] Vai permitir a criação desse recurso!');
        return 'permitir';
    }
}
?>
