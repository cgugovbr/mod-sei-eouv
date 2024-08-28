<?php

/**
 * CONTROLADORIA GERAL DA UNIÃO- CGU
 *
 * 09/10/2015 - criado por Rafael Leandro
 *
 */

require_once dirname(__FILE__) . '/../util/MdCguEouvGerarPdfLai.php';
require_once dirname(__FILE__) . '/../util/MdCguEouvGerarPdfOuv.php';
require_once __DIR__ . '/../util/MdCguEouvClient.php';

class MdCguEouvAgendamentoRN extends InfraRN
{
    protected $idTipoDocumentoAnexoDadosManifestacao;
    protected $idUnidadeOuvidoria;
    protected $idUnidadeEsicPrincipal;
    protected $idUnidadeRecursoPrimeiraInstancia;
    protected $idUnidadeRecursoSegundaInstancia;
    protected $idUnidadeRecursoTerceiraInstancia;
    protected $idUnidadeRecursoPedidoRevisao;
    protected $ocorreuErroEmProtocolo;
    protected $importar_dados_manifestante;
    protected $dataInicialImportacaoManifestacoes;
    protected $identificacaoServico = 'CadastrarManifestacao';
    protected $siglaSistema = 'EOUV';

    private $apiClient;
    private $tiposDeManifestacaoAtivos;
    private $tipoAcessoAInformacaoAtivo;
    private $protocolosProcessados;

    public function __construct()
    {
        parent::__construct();
    }

    public function inicializar()
    {
        // Cria objeto cliente da API FalaBR
        $this->apiClient = new MdCguEouvClient();

        // Carrega parâmetros
        $objEouvParametroDTO = new MdCguEouvParametroDTO();
        $objEouvParametroDTO->retTodos();
        $objEouvParametroRN = new MdCguEouvParametroRN();
        $arrObjEouvParametroDTO = $objEouvParametroRN->listarParametro($objEouvParametroDTO);
        $numRegistros = count($arrObjEouvParametroDTO);

        $this->preencheVariaveis($numRegistros, $arrObjEouvParametroDTO);

        // Carrega dados dos tipos de manifestação configurados
        $this->carregarTiposDeManifestacao();

        // Inicializa lista de protocolos processados
        $this->protocolosProcessados = [];
    }

    protected function inicializarObjInfraIBanco()
    {
        return BancoSEI::getInstance();
    }

    /**
     * Carrega nos parâmetros do objeto os tipos de manifestação atualmente
     * ativados nas configurações do módulos
     * @return void
     */
    private function carregarTiposDeManifestacao()
    {
        $objMdCguEouvDeparaImportacaoDTO = new MdCguEouvDeparaImportacaoDTO();
        $objMdCguEouvDeparaImportacaoDTO->retTodos();
        $objMdCguEouvDeparaImportacaoDTO->setStrSinAtivo('S');

        $objMdCguEouvDeparaImportacaoRN = new MdCguEouvDeparaImportacaoRN();
        $arrObjMdCguEouvDeparaImportacaoDTO = $objMdCguEouvDeparaImportacaoRN->listar($objMdCguEouvDeparaImportacaoDTO);
        $this->tiposDeManifestacaoAtivos = array();
        $this->tipoAcessoAInformacaoAtivo = false;
        foreach ($arrObjMdCguEouvDeparaImportacaoDTO as $objMdCguEouvDeparaImportacaoDTO) {
            $idTipoManifestacao = $objMdCguEouvDeparaImportacaoDTO->getNumIdTipoManifestacaoEouv();
            if ($idTipoManifestacao == 8) {
                $this->tipoAcessoAInformacaoAtivo = true;
            }
            $this->tiposDeManifestacaoAtivos[] = $idTipoManifestacao;
        }
    }

    /**
     * Filtra um array de manifestações para deixar passar apenas os tipos
     * que estão atualmente ativos na configuração
     * @param array $manifestacoes Array de estruturas DadosBasicosManifestacaoDTO ou ManifestacaoDTO
     * @return array Array filtrado
     */
    private function filtrarTiposDeManifestacaoAtivos($manifestacoes) {
        $tiposAtivos = $this->tiposDeManifestacaoAtivos;
        return array_filter($manifestacoes, function($manifestacao) use ($tiposAtivos) {
            return in_array($manifestacao['TipoManifestacao']['IdTipoManifestacao'], $tiposAtivos);
        });
    }

    /**
     * Função para agendamento que importa as manifestações do FalaBR
     */
    public function executarImportacaoManifestacaoFalaBr()
    {
        $debugLocal = false;

        // Log
        LogSEI::getInstance()->gravar('Rotina de Importação de Manifestações do FalaBR (e-Sic)', InfraLog::$INFORMACAO);

        // Inicializa objeto para executar importação
        $this->inicializar();

        $dataAtual = InfraData::getStrDataHoraAtual();

        $isBolHabilitada = SessaoSEI::getInstance(false)->isBolHabilitada();
        SessaoSEI::getInstance()->setBolHabilitada(false);

        // Executa a importação dos dados
        try {

            //Retorna dados da Última execução com Sucesso
            $objUltimaExecucao = MdCguEouvAgendamentoINT::retornarUltimaExecucaoSucesso();

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

            // Consulta novas manifestações
            $debugLocal && LogSEI::getInstance()->gravar('Consulta novas manifestações');
            $arrManifestacoes = $this->apiClient->consultaManifestacoesNoIntervalo($ultimaDataExecucao, $dataAtual);
            $arrManifestacoes = $this->filtrarTiposDeManifestacaoAtivos($arrManifestacoes);
            $qtdManifestacoesNovas = count($arrManifestacoes);
            $debugLocal && LogSEI::getInstance()->gravar('Possui novas manifestações qtd: ' . $qtdManifestacoesNovas);

            // Consulta manifestações com erro
            $debugLocal && LogSEI::getInstance()->gravar('Consulta manifestação anteriores com erros');
            $arrComErro = $this->obterManifestacoesComErro();
            $arrComErro = $this->filtrarTiposDeManifestacaoAtivos($arrComErro);
            $qtdManifestacoesAntigas = count($arrComErro);
            $debugLocal && LogSEI::getInstance()->gravar('Possui manifestações com erros - qtd: ' . $qtdManifestacoesAntigas);
            $arrManifestacoes = array_merge($arrManifestacoes, $arrComErro);

            // Consulta novos recursos, caso a importação de manifestações de acesso à informação esteja ativa
            if ($this->tipoAcessoAInformacaoAtivo) {
                $debugLocal && LogSEI::getInstance()->gravar('Consulta novos recursos');
                $arrRecursos = $this->apiClient->consultaRecursosNoIntervalo($ultimaDataExecucao, $dataAtual);
                $qtdRecursosNovos = count($arrRecursos);
                $debugLocal && LogSEI::getInstance()->gravar('Possui recursos qtd: ' . $qtdRecursosNovos);
            } else {
                $debugLocal && LogSEI::getInstance()->gravar('Importação de solicitações de acesso à informação desabilitada');
                $arrRecursos = [];
            }

            // Importa manifestações
            if (count($arrManifestacoes) > 0) {
                $semManifestacoesEncontradas = false;
                foreach ($arrManifestacoes as $retornoWsLinha) {
                    $debugLocal && LogSEI::getInstance()->gravar('Inicia importação por Linha');
                    $this->executarImportacaoLinha($retornoWsLinha);
                }
            }

            // Importa recursos
            if (count($arrRecursos) > 0) {
                $semRecursosEncontrados = false;
                foreach ($arrRecursos as $retornoWsLinha) {
                    $debugLocal && LogSEI::getInstance()->gravar('Inicia importação por linha de Recursos - protocolo: ' . $retornoWsLinha['numProtocolo']);
                    $this->executarImportacaoLinhaRecursos($retornoWsLinha);
                }
            }

            $textoMensagemFinal = 'Execução Finalizada com Sucesso!';

            if ($semManifestacoesEncontradas) {
                $textoMensagemFinal = $textoMensagemFinal . ' Não foram encontradas manifestações para o período.';
            } else {
                $textoMensagemFinal = $textoMensagemFinal . '<br>Quantidade de Manifestações novas encontradas (FalaBr): ' . $qtdManifestacoesNovas .
                    '<br>Quantidade de Manifestações encontadas que ocorreram erro em outras importações: ' . $qtdManifestacoesAntigas;
            }

            if ($semRecursosEncontrados) {
                $textoMensagemFinal = $textoMensagemFinal . ' Não foram encontrados recursos para o período.';
            } else {
                $textoMensagemFinal = $textoMensagemFinal . '<br>Quantidade de Recursos novos encontrados (e-Sic): ' . $qtdRecursosNovos;
            }

            if ($this->ocorreuErroEmProtocolo) {
                $textoMensagemFinal = $textoMensagemFinal . '<br> Ocorreram erros em 1 ou mais protocolos.';
            }

            // Grava log de sucesso da importação
            $this->gravarLogImportacao($ultimaDataExecucao, $dataAtual, 'S', $textoMensagemFinal);

            LogSEI::getInstance()->gravar('Finalizada a importação dos processos - FalaBR');

        } catch (Exception $e) {
            $this->gravarLogImportacao($ultimaDataExecucao, $dataAtual, 'N',
                'Ocorreu um erro na importação: ' . strval($e));
            throw $e;
        } finally {
            //Restaura a Sessão
            SessaoSEI::getInstance()->setBolHabilitada($isBolHabilitada);
        }
    }

    private function preencheVariaveis(int $numRegistros, $arrObjEouvParametroDTO)
    {
        // Preenche variáveis locais com dados da tabela md_eouv_parametros
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

    /**
     * Criar novo processo SEI para uma nova importação de protocolo do FalaBR
     * @param int $idTipoProcessoSei ID do tipo de processo a ser criado
     * @param array $manifestacao Estrutura ManifestacaoDTO
     * (https://falabr.cgu.gov.br/Help/ResourceModel?modelName=ManifestacaoDTO)
     * @param int $idUnidadeDestino ID da unidade SEI onde o processo será criado
     * @param string $numProtocoloFormatado Protocolo do processo formatado
     * @param array $arrDocumentos Lista de objetos DocumentoAPI que serão incluídos
     * no processo
     * @return void
     */
    public function criarNovoProcesso($idTipoProcessoSei, $manifestacao, $idUnidadeDestino, $numProtocoloFormatado, $arrDocumentos)
    {
        try {
            $objTipoProcedimentoDTO = new TipoProcedimentoDTO();
            $objTipoProcedimentoDTO->retNumIdTipoProcedimento();
            $objTipoProcedimentoDTO->retStrNome();
            $objTipoProcedimentoDTO->retStrStaNivelAcessoSugestao();
            $objTipoProcedimentoDTO->retStrStaGrauSigiloSugestao();
            $objTipoProcedimentoDTO->retStrSinIndividual();
            $objTipoProcedimentoDTO->retNumIdHipoteseLegalSugestao();
            $objTipoProcedimentoDTO->setNumIdTipoProcedimento($idTipoProcessoSei);

            $objTipoProcedimentoRN = new TipoProcedimentoRN();
            $objTipoProcedimentoDTO = $objTipoProcedimentoRN->consultarRN0267($objTipoProcedimentoDTO);

            if ($objTipoProcedimentoDTO == null) {
                throw new Exception('Tipo de processo não encontrado: ' . $idTipoProcessoSei);
            }

            $objProcedimentoAPI = new ProcedimentoAPI();
            $objProcedimentoAPI->setIdTipoProcedimento($objTipoProcedimentoDTO->getNumIdTipoProcedimento());

            $varEspecificacaoAssunto = "";

            if (is_array($manifestacao['Assunto'])) {
                $varEspecificacaoAssunto = $manifestacao['Assunto']['DescAssunto'];
            }
            if (is_array($manifestacao['SubAssunto'])) {
                $varEspecificacaoAssunto = $varEspecificacaoAssunto . " / " . $manifestacao['SubAssunto']['DescSubAssunto'];
            }

            $objProcedimentoAPI->setEspecificacao($varEspecificacaoAssunto);
            $objProcedimentoAPI->setIdUnidadeGeradora($idUnidadeDestino);
            $objProcedimentoAPI->setNumeroProtocolo($numProtocoloFormatado);
            $objProcedimentoAPI->setDataAutuacao($manifestacao['DataCadastro']);
            $objProcedimentoAPI->setNivelAcesso($objTipoProcedimentoDTO->getStrStaNivelAcessoSugestao());
            $objProcedimentoAPI->setGrauSigilo($objTipoProcedimentoDTO->getStrStaGrauSigiloSugestao());
            $objProcedimentoAPI->setIdHipoteseLegal($objTipoProcedimentoDTO->getNumIdHipoteseLegalSugestao());
            $objProcedimentoAPI->setObservacao("Processo Gerado Automaticamente pela Integração SEI x FalaBR");

            $objEntradaGerarProcedimentoAPI = new EntradaGerarProcedimentoAPI();
            $objEntradaGerarProcedimentoAPI->setProcedimento($objProcedimentoAPI);

            LogSEI::getInstance()->gravar('Importação de Manifestação ' . $numProtocoloFormatado . ': total de documentos: ' . count($arrDocumentos), InfraLog::$INFORMACAO);

            $objEntradaGerarProcedimentoAPI->setDocumentos($arrDocumentos);
            $objSeiRN = new SeiRN();
            $objSaidaGerarProcedimentoAPI = $objSeiRN->gerarProcedimento($objEntradaGerarProcedimentoAPI);
        } catch (Exception $e) {

            if (isset($objSaidaGerarProcedimentoAPI) && $objSaidaGerarProcedimentoAPI->getIdProcedimento() > 0) {
                $this->excluirProcessoComErro($objSaidaGerarProcedimentoAPI->getIdProcedimento());
            }

            throw $e;
        }
    }

    public function obterUnidadeDestino(string $tipoImportacao)
    {
        switch ($tipoImportacao) {
            case 'P':
                return $this->idUnidadeOuvidoria;
            case 'R':
                return $this->idUnidadeEsicPrincipal;
            case 'R1':
                return $this->idUnidadeRecursoPrimeiraInstancia;
            case 'R2':
                return $this->idUnidadeRecursoSegundaInstancia;
            case 'R3':
            case 'RC':
                return $this->idUnidadeRecursoTerceiraInstancia;
            case 'PR':
                return $this->idUnidadeRecursoPedidoRevisao;
            default:
                throw new InfraException('Tipo de importação desconhecido: ' . $tipoImportacao);
        }
    }

    /**
     * Grava os logs da importação no banco de dados, tanto o log principal
     * quanto os logs detalhados de cada protocolo (que estão em $this->protocolosProcessados)
     * @param string $dthInicial Data e hora inicial de manifestações buscadas
     * no formato DD/MM/YYYY HH:MM:SS
     * @param string $dthFinal Data e hora final de manifestações buscadas
     * no formato DD/MM/YYYY HH:MM:SS
     * @param string $sinSucesso 'S' se a importação foi bem sucedida ou 'N' se
     * aconteceram erros
     * @param string $mensagem Mensagem de log da importação
     * @return void
     */
    public function gravarLogImportacao($dthInicial, $dthFinal, $sinSucesso, $mensagem) {
        $objEouvRelatorioImportacaoDTO = new MdCguEouvRelatorioImportacaoDTO();

        $objEouvRelatorioImportacaoDTO->retNumIdRelatorioImportacao();
        $objEouvRelatorioImportacaoDTO->setDthDthImportacao(InfraData::getStrDataHoraAtual());
        $objEouvRelatorioImportacaoDTO->setDthDthPeriodoInicial($dthInicial);
        $objEouvRelatorioImportacaoDTO->setDthDthPeriodoFinal($dthFinal);
        $objEouvRelatorioImportacaoDTO->setStrDeLogProcessamento(substr($mensagem, 0, 500));
        $objEouvRelatorioImportacaoDTO->setStrSinSucesso($sinSucesso);

        $objEouvRelatorioImportacaoRN = new MdCguEouvRelatorioImportacaoRN();
        $objEouvRelatorioImportacaoRN->cadastrar($objEouvRelatorioImportacaoDTO);

        $idRelatorioImportacao = $objEouvRelatorioImportacaoDTO->getNumIdRelatorioImportacao();

        // Grava logs detalhados de cada protocolo processado
        $objRelatorioImportacaoDetalheRN = new MdCguEouvRelatorioImportacaoDetalheRN();
        foreach ($this->protocolosProcessados as $numProtocoloFormatado => $dados) {
            $objRelatorioImportacaoDetalheDTO = $dados['dto'];
            $objRelatorioImportacaoDetalheDTO->setNumIdRelatorioImportacao($idRelatorioImportacao);
            $objRelatorioImportacaoDetalheRN->cadastrar($objRelatorioImportacaoDetalheDTO);
        }
    }

    /**
     * Grava log detalhado da importação de um determinado protocolo
     * na variável $this->protocolosProcessados. Não grava no banco de dados.
     * @param string $numProtocoloFormatado NUP formatado
     * @param string $mensagem Mensagem de log
     * @param string $sinSucesso 'S' se a importação foi bem sucedida, 'N'
     * se ocorreram erros na importação
     * @param string $tipoManifestacao 'P' para manifestação de Ouvidoria, 'R'
     * para manifestação de LAI, 'R1' para recurso de 1ª instância, 'R2' para
     * recurso de 2ª instância, 'R3' para recurso de 3ª instância genérico,
     * 'RC' para recurso de 3ª instância na CGU (federal), 'PR' para pedido de
     * revisão
     * @return void
     */
    private function gravarLogProtocolo($numProtocoloFormatado, $mensagem, $sinSucesso, $tipoManifestacao)
    {
        $objEouvRelatorioImportacaoDetalheDTO = new MdCguEouvRelatorioImportacaoDetalheDTO();
        $objEouvRelatorioImportacaoDetalheDTO->setStrProtocoloFormatado($numProtocoloFormatado);
        $objEouvRelatorioImportacaoDetalheDTO->setStrSinSucesso($sinSucesso);
        $objEouvRelatorioImportacaoDetalheDTO->setStrTipManifestacao($tipoManifestacao);
        $objEouvRelatorioImportacaoDetalheDTO->setStrDescricaoLog(substr($mensagem,0,254));

        $this->protocolosProcessados[$numProtocoloFormatado] = [
            'dto' => $objEouvRelatorioImportacaoDetalheDTO,
            'sucesso' => $sinSucesso == 'S',
        ];
    }

    private function obterManifestacoesComErro()
    {
        $objEouvRelatorioImportacaoDetalheDTO = new MdCguEouvRelatorioImportacaoDetalheDTO();
        $objEouvRelatorioImportacaoDetalheDTO->retStrProtocoloFormatado();
        $objEouvRelatorioImportacaoDetalheDTO->retStrTipManifestacao();
        $objEouvRelatorioImportacaoDetalheDTO->setStrSinSucesso('N');

        $objEouvRelatorioImportacaoDetalheRN = new MdCguEouvRelatorioImportacaoDetalheRN();
        $objListaErros = $objEouvRelatorioImportacaoDetalheRN->listar($objEouvRelatorioImportacaoDetalheDTO);

        $arrResult = array();
        $arrProtocolos = array();

        foreach($objListaErros as $erro) {

            $numProtocoloFormatado = $erro->getStrProtocoloFormatado();

            //Se já estiver na lista não faz novamente para determinado protocolo
            if (!in_array($numProtocoloFormatado, $arrProtocolos)){

                //Adiciona no array de Protocolos
                array_push($arrProtocolos, $numProtocoloFormatado);

                $retornoWsErro = $this->apiClient->consultaManifestacao($numProtocoloFormatado);

                if (!is_null($retornoWsErro)){
                    $arrResult[] = $retornoWsErro;
                } else {
                    // Marca protocolo como bem sucedido para não tentar de novo na próxima execução
                    $this->limparErrosParaNup($numProtocoloFormatado);
                    $this->gravarLogProtocolo($numProtocoloFormatado, 'Protocolo não encontrado!', 'S', $erro->getStrTipManifestacao());
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
            throw new InfraException('Sistema ['.$SiglaSistema.'] não encontrado.');
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
            throw new InfraException('Serviço ['.$IdentificacaoServico.'] do sistema ['.$SiglaSistema.'] não encontrado.');
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
            throw new InfraException('Unidade ['.$IdUnidade.'] não encontrada.');
        }

        return $objUnidadeDTO;
    }

    public function executarImportacaoLinha($retornoWsLinha)
    {
        $debugLocal = false;

        $numProtocoloFormatado =  $this->formatarProcesso($retornoWsLinha['NumerosProtocolo'][0]);

        if (array_key_exists($numProtocoloFormatado, $this->protocolosProcessados)) {
            return; // Protocolo já processado nessa execução
        }
        
        /**
         * Limpa os registros de detalhe de importação com erro para este NUP.
         * Caso ocorra um novo, será criado novo registro de erro para o NUP no tratamento desta function.
         */
        $this->limparErrosParaNup($numProtocoloFormatado);

        // Verifica se o tipo de manifestação é suportado
        if (is_array($retornoWsLinha['TipoManifestacao'])) {
            $numIdTipoManifestacao = $retornoWsLinha['TipoManifestacao']['IdTipoManifestacao'];
        } else {
            $numIdTipoManifestacao = null;
        }
        if (is_null($numIdTipoManifestacao) || $numIdTipoManifestacao > 8) {
            // Se não for suportado marca como sucesso para evitar reimportação na próxima execução.
            $this->gravarLogProtocolo($numProtocoloFormatado,
                'Tipo de manifestação não suportado (ID = '.$numIdTipoManifestacao.'). Não será importada.',
                'S', 'P');
            return;
        }

        // Verifica Tipo de Manifestação: Ouvidoria ou LAI
        if ($numIdTipoManifestacao != 8) {
            $debugLocal && LogSEI::getInstance()->gravar('Importação tipo "P" - tipoManifestação <> "8"');
            $tipoManifestacao = 'P';
        } else {
            $debugLocal && LogSEI::getInstance()->gravar('Importação tipo "R" - tipoManifestação == "8"');
            $tipoManifestacao = 'R';
        }

        try {
            // Verifica o tipo de processo SEI correspondente
            $objEouvDeparaImportacaoDTO = new MdCguEouvDeparaImportacaoDTO();
            $objEouvDeparaImportacaoDTO->retNumIdTipoProcedimento();
            $objEouvDeparaImportacaoDTO->setNumIdTipoManifestacaoEouv($numIdTipoManifestacao);
            
            $objEouvDeparaImportacaoRN = new MdCguEouvDeparaImportacaoRN();
            $objEouvDeparaImportacaoDTO = $objEouvDeparaImportacaoRN->consultar($objEouvDeparaImportacaoDTO);
            
            if ($objEouvDeparaImportacaoDTO == null) {
                $this->gravarLogProtocolo($numProtocoloFormatado,
                'Não existe mapeamento desse tipo de manifestação do FalaBR para o tipo de processo do SEI.',
                'N', $tipoManifestacao);
                return;
            } else {
                $idTipoManifestacaoSei = $objEouvDeparaImportacaoDTO->getNumIdTipoProcedimento();
            }
            
            // Consulta detalhes da manifestação
            $manifestacao = $this->apiClient->consultaDetalhadaManifestacao($retornoWsLinha);

            // Vefificar se o NUP já existe
            $objProtocoloDTOExistente = $this->verificarProtocoloExistente($numProtocoloFormatado);
            $tipoUltimaImportacao = null;
            
            // Caso já exista um Protocolo no SEI com o mesmo NUP
            if (!is_null($objProtocoloDTOExistente)) {
                // Buscar importação anterior, caso tenha existido
                $tipoUltimaImportacao = MdCguEouvAgendamentoINT::retornarUltimoTipoManifestacao($numProtocoloFormatado);

                if ($tipoUltimaImportacao == null) {
                    $this->gravarLogProtocolo($numProtocoloFormatado, 'Já existe um processo SEI utilizando o número de protocolo, ' .
                        'mas aparentemente ele não foi criado pela integração com o FalaBR. Dados não serão importados.', 'N', $tipoManifestacao);
                    return;
                }
            }

            // Consulta recursos
            $arrRecursos = $this->apiClient->consultaRecursosDaManifestacao($numProtocoloFormatado);
            $arrRecursos = $this->filtraRecursosSuportados($arrRecursos);
            $numRecursos = count($arrRecursos);

            // Define o tipo de importação
            if ($numRecursos > 0) {
                // Verifica o tipo do último recurso
                $tipoImportacaoAtual = $this->obterTipoImportacao($arrRecursos[$numRecursos-1]);
            } else {
                $tipoImportacaoAtual = $tipoManifestacao;
            }

            // Verifica se a importação é necessária
            if ($tipoUltimaImportacao == $tipoImportacaoAtual) {
                $this->gravarLogProtocolo($numProtocoloFormatado, 'Protocolo já importado anteriormente.', 'S', $tipoUltimaImportacao);
                return;
            }

            // Verifica se a importação é permitida
            if ($tipoUltimaImportacao && !$this->permiteImportacaoAtual($tipoImportacaoAtual, $tipoUltimaImportacao)) {
                $this->gravarLogProtocolo($numProtocoloFormatado, 'Importação inconsistente. ' .
                    'Importação atual: ' . $tipoImportacaoAtual . ', Importação anterior: ' . $tipoUltimaImportacao,
                    'N', $tipoImportacaoAtual);
                return;
            }

            // Gerar documentos a serem importados
            if ($tipoImportacaoAtual == 'P') {
                $anexosGerados = $this->gerarAnexosProtocolo($manifestacao['Teor']['Anexos'], $numProtocoloFormatado);
                $objDocumentoManifestacao = $this->gerarPDFOuvidoria($manifestacao, [], $tipoImportacaoAtual, $anexosGerados['erro']);
                $arrDocumentos = array_merge([$objDocumentoManifestacao], $anexosGerados['documentos']);
            } else {
                $arrAnexos = [];
                $ocorreuErroAnexos = false;

                // Caso seja a primeira importação, importa anexos do pedido inicial
                if ($tipoUltimaImportacao == null) {
                    $anexosGerados = $this->gerarAnexosProtocolo($manifestacao['Teor']['Anexos'], $numProtocoloFormatado);
                    if ($anexosGerados['erro']) {
                        $ocorreuErroAnexos = true;
                    }
                    $arrAnexos = array_merge($arrAnexos, $anexosGerados['documentos']);
                }

                // Importa anexos de recursos desde a última importação
                if ($numRecursos > 0) {
                    $aposUltimaImportacao = ($tipoUltimaImportacao == null) ||
                        ($tipoUltimaImportacao == 'R') ||
                        ($tipoUltimaImportacao == 'P');

                    foreach ($arrRecursos as $recurso) {
                        if (!$aposUltimaImportacao) {
                            if ($this->obterTipoImportacao($recurso) == $tipoUltimaImportacao) {
                                $aposUltimaImportacao = true;
                            }
                        } else {
                            $anexosGerados = $this->gerarAnexosProtocolo($recurso['anexos'], $numProtocoloFormatado);
                            if ($anexosGerados['erro']) {
                                $ocorreuErroAnexos = true;
                            }
                            $arrAnexos = array_merge($arrAnexos, $anexosGerados['documentos']);
                        }
                    }
                }

                // Gera PDF principal
                if ($tipoManifestacao == 'P') {
                    $objDocumentoManifestacao = $this->gerarPDFOuvidoria($manifestacao, $arrRecursos, $tipoImportacaoAtual, $ocorreuErroAnexos);
                } else {
                    $objDocumentoManifestacao = $this->gerarPDFLai($manifestacao, $arrRecursos, $tipoImportacaoAtual, $ocorreuErroAnexos);
                }

                // Agrupa documentos
                $arrDocumentos = array_merge([$objDocumentoManifestacao], $arrAnexos);
            }

            // Verificar a unidade de destino
            $idUnidadeDestino = $this->obterUnidadeDestino($tipoImportacaoAtual);

            // Verifica se cria um processo ou inclui documento
            if (is_null($objProtocoloDTOExistente)) {
                // Primeira importação, deve-se criar um processo
                $this->simulaLogin($this->siglaSistema, $this->identificacaoServico, $idUnidadeDestino);
                $this->criarNovoProcesso($idTipoManifestacaoSei, $manifestacao, $idUnidadeDestino, $numProtocoloFormatado, $arrDocumentos);
                $this->gravarLogProtocolo($numProtocoloFormatado, 'Protocolo importado com sucesso.', 'S', $tipoImportacaoAtual);
            } else {
                // Simula login na unidade geradora do processo existente
                $this->simulaLogin($this->siglaSistema, $this->identificacaoServico, $objProtocoloDTOExistente->getNumIdUnidadeGeradora());

                // Inclui os documentos no processo
                $objSeiRN = new SeiRN();
                foreach($arrDocumentos as $objDocumentoAPI) {
                    $objDocumentoAPI->setIdProcedimento($objProtocoloDTOExistente->getDblIdProtocolo());
                    $objSeiRN->incluirDocumento($objDocumentoAPI);
                }

                // Remete o processo para a unidade correta
                $objEntradaEnviarProcesso = new EntradaEnviarProcessoAPI();
                $objEntradaEnviarProcesso->setIdProcedimento($objProtocoloDTOExistente->getDblIdProtocolo());
                $objEntradaEnviarProcesso->setUnidadesDestino([$idUnidadeDestino]);
                $objEntradaEnviarProcesso->setSinManterAbertoUnidade('S');
                $objEntradaEnviarProcesso->setSinEnviarEmailNotificacao('S');
                $objEntradaEnviarProcesso->setSinReabrir('S');

                $objSeiRN->enviarProcesso($objEntradaEnviarProcesso);

                $this->gravarLogProtocolo($numProtocoloFormatado, 'Recurso importado com sucesso.', 'S', $tipoImportacaoAtual);
            }
        } catch (\Exception $e) {
            $this->gravarLogProtocolo($numProtocoloFormatado, 'Erro na importação: ' . $e, 'N', $tipoImportacaoAtual);
            throw $e;
        }
    }

    /**
     * Filtra um array, removendo tipos de recursos não suportados pela integração
     * @param array $arrRecursos Lista de estruturas RecursoDTO
     * (https://falabr.cgu.gov.br/Help/ResourceModel?modelName=RecursoDTO)
     * @return array Lista de estruturas RecursoDTO apenas com tipos de recurso
     * suportados
     */
    private function filtraRecursosSuportados($arrRecursos) {
        return array_filter($arrRecursos, function ($recurso) {
            // 1 -> Recurso de primeira instância
            // 2 -> Recurso de segunda instância
            // 6 -> Pedido de Revisão
            return in_array($recurso['instancia']['IdInstanciaRecurso'], [1, 2, 6]);
        });
    }

    public function executarImportacaoLinhaRecursos($recurso)
    {
        $numProtocoloFormatado = $this->formatarProcesso($recurso['numProtocolo']);

        if (array_key_exists($numProtocoloFormatado, $this->protocolosProcessados)) {
            return; // Protocolo já processado nessa execução
        }

        // Consultar manifestação do recurso
        $manifestacao = $this->apiClient->consultaManifestacao($numProtocoloFormatado);

        // Processar importação da manifestação atualizada
        if ($manifestacao) {
            if (in_array($manifestacao['TipoManifestacao']['IdTipoManifestacao'], $this->tiposDeManifestacaoAtivos)) {
                $this->executarImportacaoLinha($manifestacao);
            }
        } else {
            $this->gravarLogProtocolo($numProtocoloFormatado, 'Não foi possível acessar a manifestação',
                'N', $this->obterTipoImportacao($recurso));
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

    private function gerarPDFOuvidoria($retornoWsLinha, $recursos, $tipoImportacaoAtual, $ocorreuErroAdicionarAnexo)
    {
        $pedidoRevisao = count($recursos) > 0 ? $recursos[0] : null;
        $mdCguEouvGerarPdf = new MdCguEouvGerarPdfOuv($retornoWsLinha, $pedidoRevisao, $this->importar_dados_manifestante, $ocorreuErroAdicionarAnexo);
        $pdf = $mdCguEouvGerarPdf->obterPDF();

        $objAnexoRN = new AnexoRN();
        $strNomeArquivoInicialUpload = $objAnexoRN->gerarNomeArquivoTemporario();

        $pdf->Output(DIR_SEI_TEMP . "/" . $strNomeArquivoInicialUpload . ".pdf", "F");

        //Renomeia tirando a extensão para o SEI trabalhar o Arquivo
        rename(DIR_SEI_TEMP . "/" . $strNomeArquivoInicialUpload . ".pdf", DIR_SEI_TEMP . "/" . $strNomeArquivoInicialUpload);

        $objDocumentoManifestacao = new DocumentoAPI();
        $objDocumentoManifestacao->setTipo('R');
        if ($tipoImportacaoAtual == 'PR') {
            $nomeDocumentoArvore = 'Pedido Revisão';
        } else {
            $nomeDocumentoArvore = 'Manifestação';
        }
        $objDocumentoManifestacao->setNumero($nomeDocumentoArvore);
        $objDocumentoManifestacao->setIdSerie($this->idTipoDocumentoAnexoDadosManifestacao);
        $objDocumentoManifestacao->setData($retornoWsLinha['DataCadastro']);
        $objDocumentoManifestacao->setNomeArquivo('RelatórioDadosManifestação.pdf');
        $objDocumentoManifestacao->setConteudo(base64_encode(file_get_contents(DIR_SEI_TEMP . "/" . $strNomeArquivoInicialUpload)));

        return $objDocumentoManifestacao;
    }

    private function gerarPDFLai($retornoWsLinha, $retornoWsRecursos = [], $tipo_recurso = '', $ocorreuErroAdicionarAnexo = false)
    {
        $objGerarPdf = new MdCguEouvGerarPdfLai($retornoWsLinha, $retornoWsRecursos, $this->importar_dados_manifestante, $ocorreuErroAdicionarAnexo);
        $pdf = $objGerarPdf->obterPDF();
        $objAnexoRN = new AnexoRN();
        $strNomeArquivoInicialUpload = $objAnexoRN->gerarNomeArquivoTemporario();

        $pdf->Output(DIR_SEI_TEMP . "/" . $strNomeArquivoInicialUpload . ".pdf", "F");

        //Renomeia tirando a extensão para o SEI trabalhar o Arquivo
        rename(DIR_SEI_TEMP . "/" . $strNomeArquivoInicialUpload . ".pdf", DIR_SEI_TEMP . "/" . $strNomeArquivoInicialUpload);

        $objDocumentoManifestacao = new DocumentoAPI();
        $objDocumentoManifestacao->setTipo('R');
        if ($tipo_recurso == 'R1') {
            $nomeDocumentoArvore = 'Primeira Instância';
        } elseif ($tipo_recurso == 'R2') {
            $nomeDocumentoArvore = 'Segunda Instância';
        } elseif ($tipo_recurso == 'R3' || $tipo_recurso == 'RC') {
            $nomeDocumentoArvore = 'Terceira Instância';
        } elseif ($tipo_recurso == 'PR') {
            $nomeDocumentoArvore = 'Pedido Revisão';
        } else {
            $nomeDocumentoArvore = 'Pedido Inicial';
        }

        $objDocumentoManifestacao->setNumero($nomeDocumentoArvore);
        $objDocumentoManifestacao->setIdSerie($this->idTipoDocumentoAnexoDadosManifestacao);
        $objDocumentoManifestacao->setData($retornoWsLinha['DataCadastro']);
        $objDocumentoManifestacao->setNomeArquivo('RelatorioDadosManifestacao.pdf');
        $objDocumentoManifestacao->setConteudo(base64_encode(file_get_contents(DIR_SEI_TEMP . "/" . $strNomeArquivoInicialUpload)));

        return $objDocumentoManifestacao;
    }

    /**
     * Gera objetos DocumentoAPI do SEI para os anexos da manifestação ou recurso, mas não
     * os adiciona ao processo.
     * @param array $arrAnexosManifestacao Lista de estruturas DadosBasicosAnexoDTO
     * (https://falabr.cgu.gov.br/Help/ResourceModel?modelName=DadosBasicosAnexoDTO) ou
     * DadosBasicosAnexoRecursoDTO
     * (https://falabr.cgu.gov.br/Help/ResourceModel?modelName=DadosBasicosAnexoRecursoDTO)
     * @param string $numProtocoloFormatado Número do protocolo da manifestação formatado
     * @return array Array associativo cuja chave 'documentos' é um array de objetos
     * DocumentoAPI do SEI e achave 'erro' é um bool que indica se algum anexo tem extensão inválida
     */
    private function gerarAnexosProtocolo($arrAnexosManifestacao, $numProtocoloFormatado)
    {
        $arrAnexosAdicionados = array();
        $intTotAnexos = count($arrAnexosManifestacao);
        $arrExtensoesInvalidas = [];

        if($intTotAnexos == 0){
            //Não encontrou anexos..
            return [
                'documentos' => [],
                'erro' => false,
            ];
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

        foreach ($arrAnexosManifestacao as $retornoWsAnexoLinha) {
            $strNomeArquivoOriginal = $retornoWsAnexoLinha['NomeArquivo']; // Para DadosBasicosAnexoDTO é NomeArquivo
            if ($strNomeArquivoOriginal == null) {
                $strNomeArquivoOriginal = $retornoWsAnexoLinha['nomeArquivo']; // Para DadosBasicosAnexoRecursoDTO é nomeArquivo
            }

            // Ajustamos aqui o nome do arquivo limitado a 50 caracteres
            $strNomeArquivoOriginal = substr($strNomeArquivoOriginal, -50, 50);

            $ext = strtoupper(pathinfo($strNomeArquivoOriginal, PATHINFO_EXTENSION));
            $intIndexExtensao = array_search($ext, $arrExtensoesPermitidas);

            if (is_numeric($intIndexExtensao)) {
                $objAnexoRN = new AnexoRN();
                $strNomeArquivoUpload = $objAnexoRN->gerarNomeArquivoTemporario();

                // Faz download do anexo
                $strCaminhoArquivoUpload = DIR_SEI_TEMP . '/' . $strNomeArquivoUpload;
                $this->apiClient->downloadAnexo($retornoWsAnexoLinha, $strCaminhoArquivoUpload);

                // Se o arquivo vier vazio irá gerar erro ao cadastrar no SEI
                if (filesize($strCaminhoArquivoUpload) == 0) {
                    continue;
                }

                $objAnexoManifestacao = new DocumentoAPI();

                $objAnexoManifestacao->setTipo('R');
                $objAnexoManifestacao->setIdSerie($this->idTipoDocumentoAnexoDadosManifestacao);
                $objAnexoManifestacao->setData(InfraData::getStrDataHoraAtual());
                $objAnexoManifestacao->setNomeArquivo($strNomeArquivoOriginal);
                $objAnexoManifestacao->setNumero($strNomeArquivoOriginal);
                $objAnexoManifestacao->setConteudo(base64_encode(file_get_contents($strCaminhoArquivoUpload)));

                if (!$this->hashDuplicado($strCaminhoArquivoUpload, $numProtocoloFormatado)) {
                    array_push($arrAnexosAdicionados, $objAnexoManifestacao);
                }
            } else {
                if (!in_array($ext, $arrExtensoesInvalidas)) {
                    $arrExtensoesInvalidas[] = $ext;
                }
                LogSEI::getInstance()->gravar('Importação de Manifestação ' . $numProtocoloFormatado . ': Arquivo ' . $strNomeArquivoOriginal . ' possui extensão inválida.', InfraLog::$INFORMACAO);
            }
        }

        return [
            'documentos' => $arrAnexosAdicionados,
            'erro' => count($arrExtensoesInvalidas) > 0,
        ];
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
     * Verifica se já existe um Protocolo no SEI com o número (NUP)
     */
    public function verificarProtocoloExistente($numProtocoloFormatado)
    {
        $objProtocoloDTOExistente = new ProtocoloDTO();
        $objProtocoloRNExistente = new ProtocoloRN();
        $objProtocoloDTOExistente->retDblIdProtocolo();
        $objProtocoloDTOExistente->retNumIdUnidadeGeradora();
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
    private function simulaLogin($siglaSistema, $idServico, $idUnidade)
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
     * Obter o tipo de importação a partir de um recurso
     * @param array $recurso Estrutura RecursoDTO
     * (https://falabr.cgu.gov.br/Help/ResourceModel?modelName=RecursoDTO)
     * @return string Tipo de importação representado por este recurso
     */
    private function obterTipoImportacao($recurso)
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
     * Verifica se a importação é consistente com a ordem lógica dos recursos.
     * Ex: um recurso de primeira instância não pode ser importado se a
     * última importação foi de um recurso de segunda instância no mesmo processo.
     * @param string $tipoImportacaoAtual O tipo da importação corrente dentre os
     * tipos suportados
     * @param string $ultimoTipoImportacao O tipo da última importação bem
     * sucedida do protocolo.
     * @return bool true se a importação é permitida e false caso contrário
     * 
     * Obs: Tipos suportados são: P, R, PR, R1, R2, R3 e RC
     */
    public function permiteImportacaoAtual($tipoImportacaoAtual, $ultimoTipoImportacao)
    {
        /**
         * Os pedidos iniciais, tanto de LAI quanto de Ouvidoria (R e P),
         * formam o estágio inicial.
         * 
         * Em seguida pode vir um Pedido de Revisão, que é uma espécie de
         * recurso que pode ser enviado quando há reclassificação de uma
         * manifestação (Ex: uma denúncia pode ser reclassificada pelo órgão
         * como pedido de acesso à informação e vice-versa).
         * 
         * Em seguida pode vir um Recurso de 1ª instância (R1).
         * 
         * Em seguida pode vir um recurso de 2ª instância (R2).
         * 
         * Em seguida pode vir um recurso de 3ª instância (R3 ou RC). RC indica
         * que a terceira instância é a CGU, que ocorre quando o pedido é feito
         * a um órgão federal. R3 indica que o pedido não é na esfera federal.
        */
        $ordem = [
            'P' => 0,
            'R' => 0,
            'PR' => 1,
            'R1' => 2,
            'R2' => 3,
            'R3' => 4,
            'RC' => 4,
        ];

        // Verifica se um recurso de fase anterior não está sendo importado
        // após um recurso de fase posterior
        if ($ordem[$tipoImportacaoAtual] <= $ordem[$ultimoTipoImportacao]) {
            return false;
        } else {
            return true;
        }
    }
}
?>
