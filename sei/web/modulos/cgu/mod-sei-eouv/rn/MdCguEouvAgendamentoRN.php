<?
/**
 * CONTROLADORIA GERAL DA UNIÃO- CGU
 *
 * 09/10/2015 - criado por Rafael Leandro
 *
 */
error_reporting(E_ALL); ini_set('display_errors', '1');

require_once dirname(__FILE__) . '/../../../../SEI.php';

//header('Content-Type: text/html; charset=UTF-8');

class MdCguEouvAgendamentoRN extends InfraRN
{

    public function __construct()
    {
        parent::__construct();
        //ini_set('memory_limit', '1024M');
    }

    /**
     * @param $objWS
     * @param $usuarioWebService
     * @param $senhaUsuarioWebService
     * @param $ultimaDataExecucao
     * @param $dataAtual
     * @return mixed
     * @throws Exception
     */

    public function apiRestRequest($url, $token, $tipo){

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "UTF-8",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_SSLVERSION => 6,
            CURLOPT_HTTPHEADER => array(
                "Accept: */*",
                "Authorization: Bearer " . $token,
                "Cache-Control: no-cache"
            ),
        ));

        $response = curl_exec($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $err = curl_error($curl);

        curl_close($curl);

        //Se tiver retornado Token Invalidado
        if ($httpcode == 401) {
            $response = "Token Invalidado. HTTP Status: " . $httpcode;
        } elseif ($httpcode == 200) {
            $response = json_decode($response, true);
            $response = $this->decode_result($response);
        } elseif ($httpcode == 404) {
            $response = '';
        } else {
            $response = "Erro: Ocorreu algum erro não tratado. HTTP Status: " . $httpcode;
        }

        return $response;
    }

    function decode_result($array)
    {

        foreach($array as $key => $value) {
            if(is_array($value)) {
                $array[$key] = $this->decode_result($value);
            } else {
                //$array[$key] = mb_convert_encoding($value, 'Windows-1252', 'UTF-8');
                $array[$key] = utf8_decode($value);
            }
        }

        return $array;
    }

    public function apiValidarToken($url, $username, $password, $client_id, $client_secret)
    {

        /*echo "<br><br>token:" . $token;
        echo "<br><br>username:" . $username;
        echo "<br><br>$password:" . $token;
        echo "<br><br>token:" . $token;
        echo "<br><br>token:" . $token;*/

        //get Url Ambiente
        $url = parse_url($url);
        $urlAmbiente = $url['scheme'] . '://' . $url['host'] . '/oauth/token';

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $urlAmbiente,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "UTF-8",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_SSLVERSION => 6,
            CURLOPT_POSTFIELDS => "client_id=".$client_id."&client_secret=".$client_secret."&grant_type=password&username=".$username."&password=".$password."&undefined=",
            //CURLOPT_POSTFIELDS => "client_id=15&client_secret=rwkp6899&grant_type=password&username=wsIntegracaoSEI&password=teste1235&undefined=",
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json",
                "cache-control: no-cache"
            ),
        ));

        $response = curl_exec($curl);

        $err = curl_error($curl);

        curl_close($curl);

        $response = json_decode($response, true);
        return $response;

    }

    public function gravarParametroToken($tokenGerado){

        $objEouvParametroDTO = new MdCguEouvParametroDTO();
        $objEouvParametroDTO -> setNumIdParametro(10);
        $objEouvParametroDTO -> setStrNoParametro('TOKEN');
        $objEouvParametroDTO -> setStrDeValorParametro($tokenGerado);

        $objEouvParametroRN = new MdCguEouvParametroRN();
        $arrObjEouvParametroDTO = $objEouvParametroRN->alterarParametro($objEouvParametroDTO);

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

        $retornoWs = $this->apiRestRequest($urlConsultaManifestacao, $token, 1);

        if (is_null($numprotocolo)) {
            //Verifica se retornou Token Invalido
            if (is_string($retornoWs)) {
                if (strpos($retornoWs, 'Invalidado') !== false) {
                    //Token expirado, necessÃ¡rio gerar novo Token
                    return "Token Invalidado";
                }

                //Outro erro
                if (strpos($retornoWs, 'Erro') !== false) {
                    //Token expirado, necessÃ¡rio gerar novo Token
                    return "Erro:" . $retornoWs;
                }

            }
        } else {
            //Faz tratamento diferenciado para consulta por Protocolo específico
            if(is_string($retornoWs)) {
                if (strpos($retornoWs, 'Erro') !== false) {
                    if (strpos($retornoWs, '404') !== false) {
                        $this->gravarLogLinha($this->formatarProcesso($numprotocolo), $numIdRelatorio, "Usuário não possui permissão de acesso neste protocolo.", 'N');
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

    public function executarServicoConsultaRecursos($urlConsultaRecurso, $token, $ultimaDataExecucao = null, $dataAtual = null, $numprotocolo = null, $numIdRelatorio = null)
    {

        $arrParametrosUrl = array(
            'dataAberturaInicio' => $ultimaDataExecucao,
            'dataAberturaFim' => $dataAtual,
            'NumProtocolo' => $numprotocolo
        );

        $arrParametrosUrl = http_build_query($arrParametrosUrl);

        $urlConsultaManifestacao = $urlConsultaRecurso . "?" . $arrParametrosUrl;

        $retornoWs = $this->apiRestRequest($urlConsultaRecurso, $token, 1);

        if (is_null($numprotocolo)) {
            //Verifica se retornou Token Invalido
            if (is_string($retornoWs)) {
                if (strpos($retornoWs, 'Invalidado') !== false) {
                    //Token expirado, necessÃ¡rio gerar novo Token
                    return "Token Invalidado";
                }

                //Outro erro
                if (strpos($retornoWs, 'Erro') !== false) {
                    //Token expirado, necessÃ¡rio gerar novo Token
                    return "Erro:" . $retornoWs;
                }

            }
        } else {
            //Faz tratamento diferenciado para consulta por Protocolo específico
            if(is_string($retornoWs)) {
                if (strpos($retornoWs, 'Erro') !== false) {
                    if (strpos($retornoWs, '404') !== false) {
                        $this->gravarLogLinha($this->formatarProcesso($numprotocolo), $numIdRelatorio, "Usuário não possui permissão de acesso neste protocolo.", 'N');
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

    protected function inicializarObjInfraIBanco()
    {
        return BancoSEI::getInstance();
    }

    // GZIP DECODE
    function gzdecode($data)
    {

        return gzinflate(substr($data, 10, -8));
    }

    public function verificaRetornoWS($retornoWsLista)
    {
        /*
        função criada para tratar o retorno de dados do WS, pois quando existe apenas um unico resultado retorna um objeto e
        quando tem mais de um resultado retorna um array ocasionando falhas na exibição dos dados.
        */
        if (isset($retornoWsLista) and key_exists(0, $retornoWsLista)) {
            $resultado = $retornoWsLista;
        } else {
            $resultado = array ( $retornoWsLista );
        }
        return $resultado;
    }

    public function retornaDataFormatoEouv($strData)
    {
        $dataFormatada = substr($strData, 6, 4) . "-" . substr($strData, 3, 2) . "-" . substr($strData, 0, 2) . " " . substr($strData, 11, 8);
        return $dataFormatada;
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
            $objEouvRelatorioImportacaoRN = $objEouvRelatorioImportacaoRN->cadastrar($objEouvRelatorioImportacaoDTO);

            return $objEouvRelatorioImportacaoDTO;

        }catch (Exception $e) {
            PaginaInfra::getInstance()->processarExcecao($e);
            die;
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

            //Se já estiver na lista não faz novamente para determinado protocolo
            if (!in_array($numProtocolo, $arrProtocolos)){

                //Adiciona no array de Protocolos
                array_push($arrProtocolos, $numProtocolo);

                $retornoWsErro = $this->executarServicoConsultaManifestacoes($urlConsultaManifestacao, $token, null, $dataAtual, $numProtocolo, $numIdRelatorio);

                if (!is_null($retornoWsErro) && $retornoWsErro <> ''){
                    //$arrRetornoWs = $this->verificaRetornoWS($retornoWsErro['GetListaManifestacaoOuvidoriaResult']['ManifestacoesOuvidoria']['ManifestacaoOuvidoria']);
                    $arrResult = array_merge($arrResult, $retornoWsErro);
                }
            }
        }

        return $arrResult;
    }

    private function obterServico($SiglaSistema, $IdentificacaoServico){

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

    private function obterUnidade($IdUnidade, $SiglaUnidade){

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

    function array_to_object($array) {
        $obj = new stdClass;
        foreach($array as $k => $v) {
            if(strlen($k)) {
                if(is_array($v)) {
                    $obj->{$k} = $this->array_to_object($v); //RECURSION
                } else {
                    $obj->{$k} = $v;
                }
            }
        }
        return $obj;
    }

    /**
     * Função para importar as manifestações e-Ouv do FalaBR
     *
     * Tipos: 1, 2, 3, 4, 5, 6 e 7
     */
    public function executarImportacaoManifestacaoEOuv()
    {
        // Debug
        InfraDebug::getInstance()->setBolLigado(true);
        InfraDebug::getInstance()->setBolDebugInfra(false);
        InfraDebug::getInstance()->setBolEcho(false);
        InfraDebug::getInstance()->limpar();

        // Log
        LogSEI::getInstance()->gravar('Rotina de Importação de Manifestações do E-Ouv', InfraLog::$INFORMACAO);

        global $objEouvRelatorioImportacaoDTO,
               $objEouvRelatorioImportacaoRN,
               $objInfraParametro,
               $urlWebServiceEOuv,
               $urlWebServiceAnexosEOuv,
               $idTipoDocumentoAnexoPadrao,
               $idTipoDocumentoAnexoDadosManifestacao,
               $idUnidadeOuvidoria,
               $idUsuarioSei,
               $dataAtual,
               $objUltimaExecucao,
               $ocorreuErroEmProtocolo,
               $idRelatorioImportacao,
               $usuarioWebService,
               $senhaUsuarioWebService,
               $client_id,
               $client_secret,
               $token,
               $importar_dados_manifestante;

        $objEouvParametroDTO = new MdCguEouvParametroDTO();
        $objEouvParametroDTO -> retTodos();

        // Busca parâmetros do banco de dados
        $objEouvParametroRN = new MdCguEouvParametroRN();
        $arrObjEouvParametroDTO = $objEouvParametroRN->listarParametro($objEouvParametroDTO);

        $numRegistros = count($arrObjEouvParametroDTO);

        if ($numRegistros > 0){
            for($i = 0;$i < $numRegistros; $i++){

                $strParametroNome = $arrObjEouvParametroDTO[$i]->getStrNoParametro();

                switch ($strParametroNome){

                    case "EOUV_DATA_INICIAL_IMPORTACAO_MANIFESTACOES":
                        $dataInicialImportacaoManifestacoes = $arrObjEouvParametroDTO[$i]->getStrDeValorParametro();
                        break;

                    case "EOUV_ID_SERIE_DOCUMENTO_EXTERNO_DADOS_MANIFESTACAO":
                        $idTipoDocumentoAnexoDadosManifestacao = $arrObjEouvParametroDTO[$i]->getStrDeValorParametro();
                        break;

                    case "ID_SERIE_EXTERNO_OUVIDORIA":
                        $idTipoDocumentoAnexoPadrao = $arrObjEouvParametroDTO[$i]->getStrDeValorParametro();
                        break;

                    case "EOUV_USUARIO_ACESSO_WEBSERVICE":
                        $usuarioWebService = $arrObjEouvParametroDTO[$i]->getStrDeValorParametro();
                        break;

                    case "EOUV_SENHA_ACESSO_WEBSERVICE":
                        $senhaUsuarioWebService = $arrObjEouvParametroDTO[$i]->getStrDeValorParametro();
                        break;

                    case "CLIENT_ID":
                        $client_id = $arrObjEouvParametroDTO[$i]->getStrDeValorParametro();
                        break;

                    case "CLIENT_SECRET":
                        $client_secret = $arrObjEouvParametroDTO[$i]->getStrDeValorParametro();
                        break;

                    case "EOUV_URL_WEBSERVICE_IMPORTACAO_MANIFESTACAO":
                        $urlWebServiceEOuv = $arrObjEouvParametroDTO[$i]->getStrDeValorParametro();
                        break;

                    case "EOUV_URL_WEBSERVICE_IMPORTACAO_ANEXO_MANIFESTACAO":
                        $urlWebServiceAnexosEOuv = $arrObjEouvParametroDTO[$i]->getStrDeValorParametro();
                        break;

                    case "ID_UNIDADE_OUVIDORIA":
                        $idUnidadeOuvidoria = $arrObjEouvParametroDTO[$i]->getStrDeValorParametro();
                        break;

                    case "TOKEN":
                        $token = $arrObjEouvParametroDTO[$i]->getStrDeValorParametro();
                        break;

                    case "IMPORTAR_DADOS_MANIFESTANTE":
                        $importar_dados_manifestante = $arrObjEouvParametroDTO[$i]->getStrDeValorParametro();
                        break;


                }
            }
        }

        $objInfraParametro = new InfraParametro(BancoSEI::getInstance());
        $idUsuarioSei = $objInfraParametro->getValor('ID_USUARIO_SEI');
        //$urlEouvDetalhesManifestacao = $objInfraParametro->getValor('EOUV_URL_DETALHE_MANIFESTACAO');
        $dataAtual = InfraData::getStrDataHoraAtual();
        $SiglaSistema = 'EOUV';
        $IdentificacaoServico = 'CadastrarManifestacao';

        //Quando estiver executando agendamento Simula Login
        if (SessaoSEI::getInstance()->getNumIdUnidadeAtual()==null && SessaoSEI::getInstance()->getNumIdUsuario()==null){

            try{

                InfraDebug::getInstance()->gravar(__METHOD__);
                InfraDebug::getInstance()->gravar('SIGLA SISTEMA:'.$SiglaSistema);
                InfraDebug::getInstance()->gravar('IDENTIFICACAO SERVICO:'.$IdentificacaoServico);
                InfraDebug::getInstance()->gravar('ID UNIDADE:'.$idUnidadeOuvidoria);

                SessaoSEI::getInstance(false);

                $objServicoDTO = $this->obterServico($SiglaSistema, $IdentificacaoServico);

                if ($idUnidadeOuvidoria!=null){
                    $objUnidadeDTO = $this->obterUnidade($idUnidadeOuvidoria,null);
                }else{
                    $objUnidadeDTO = null;
                }

                // $this->validarAcessoAutorizado(explode(',',str_replace(' ','',$objServicoDTO->getStrServidor())));

                SessaoSEI::getInstance()->simularLogin(null, null, $objServicoDTO->getNumIdUsuario(), $objUnidadeDTO->getNumIdUnidade());

            }catch(Exception $e){
                LogSEI::getInstance()->gravar('Ocorreu erro simular Login.'.$e);
                PaginaSEI::getInstance()->processarExcecao($e);
            }
        }

        try {

            //Retorna dados da Última execução com Sucesso
            $objUltimaExecucao = MdCguEouvAgendamentoINT::retornarUltimaExecucaoSucesso();

            if ($objUltimaExecucao != null) {
                $ultimaDataExecucao = $objUltimaExecucao->getDthDthPeriodoFinal();
                $idUltimaExecucao = $objUltimaExecucao->getNumIdRelatorioImportacao();
            } //Primeira execução ou nenhuma executada com sucesso
            else {
                $ultimaDataExecucao = $dataInicialImportacaoManifestacoes;
            }

//            $ultimaDataExecucao = '13/11/2020 01:00:00';
//            $dataAtual = '13/11/2020 23:59:00';
            $semManifestacoesEncontradas = true;
            $qtdManifestacoesNovas = 0;
            $qtdManifestacoesAntigas = 0;
            $objEouvRelatorioImportacaoDTO = $this->gravarLogImportacao($ultimaDataExecucao, $dataAtual);
            $idRelatorioImportacao = $objEouvRelatorioImportacaoDTO->getNumIdRelatorioImportacao();
            $objEouvRelatorioImportacaoRN = new MdCguEouvRelatorioImportacaoRN();
            $SinSucessoExecucao = 'N';
            $textoMensagemErroToken = '';

            $retornoWs = $this->executarServicoConsultaManifestacoes($urlWebServiceEOuv, $token, $ultimaDataExecucao, $dataAtual, null, $idRelatorioImportacao);

            /**
             * Exemplo de retorno da API
             */
//            $retornoWs = [["Links"=>[["rel"=>"self","href"=>"https://sistema.ouvidorias.gov.br/api/manifestacoes/2670732"]],"IndPossuiIdentidadePreservada"=>false,"IdManifestacao"=>2670732,"NumerosProtocolo"=>["23546059531202007"],"OuvidoriaDestino"=>["IdOuvidoria"=>65,"IdOrgaoSiorg"=>100911,"NomeOuvidoria"=>"IFCE – Instituto Federal de Educação, Ciência e Tecnologia do Ceará"],"Assunto"=>["IdAssunto"=>57,"DescAssunto"=>"Defesa do Consumidor"],"Servico"=>null,"TipoFormulario"=>["IdTipoFormulario"=>1,"DescTipoFormulario"=>"Padrão"],"TipoManifestacao"=>["IdTipoManifestacao"=>2,"DescTipoManifestacao"=>"Reclamação"],"EmailManifestante"=>"luanaalbuquerquev@gmail.com","DataCadastro"=>"24/11/2020","PrazoAtendimento"=>null,"Situacao"=>["IdSituacaoManifestacao"=>5,"DescSituacaoManifestacao"=>"Complementação Solicitada"],"ResponsavelAnalise"=>"Tércio Victor de Oliveira Leal"]];

            //Caso retornado algum erro
            if (is_string($retornoWs)) {
                if (strpos($retornoWs, 'Invalidado') !== false) {
                    //Tenta gerar novo token
                    $tokenValido = $this->apiValidarToken($urlWebServiceEOuv, $usuarioWebService, $senhaUsuarioWebService, $client_id, $client_secret);

                    if (isset($tokenValido['error'])) {
                        $textoMensagemErroToken = 'Não foi possível validar o Token de acesso aos WebServices do E-ouv. <br>Verifique as informações de Usuário, Senha, Client_Id e Client_Secret nas configurações de Parâmetros do Módulo';

                    } elseif (isset($tokenValido['access_token'])) {
                        $this->gravarParametroToken($tokenValido['access_token']);
                        $token = $tokenValido['access_token'];

                        //Chama novamente a execução da ConsultaManifestacao que deu errado por causa do Token
                        $retornoWs = $this->executarServicoConsultaManifestacoes($urlWebServiceEOuv, $token, $ultimaDataExecucao, $dataAtual, null, $idRelatorioImportacao);
                    }
                }

            }

            if ($textoMensagemErroToken == '') {
                $arrComErro = $this->obterManifestacoesComErro($urlWebServiceEOuv, $token, $ultimaDataExecucao, $dataAtual, $idRelatorioImportacao);

                $arrManifestacoes = array();

                if (is_array($retornoWs)) {
                    $qtdManifestacoesNovas = count($retornoWs);
                    $arrManifestacoes = $retornoWs;
                }

                if (is_array($arrComErro)) {
                    $qtdManifestacoesAntigas = count($arrComErro);
                    $arrManifestacoes = array_merge($arrManifestacoes, $arrComErro);
                }

                if (count($arrManifestacoes) > 0) {
                    $semManifestacoesEncontradas = false;
                    foreach ($arrManifestacoes as $retornoWsLinha) {
                        if ($retornoWsLinha['TipoManifestacao']['IdTipoManifestacao'] <> 8) {
                            $this->executarImportacaoLinha($retornoWsLinha);
                        }
                    }
                }

                $textoMensagemFinal = 'Execução Finalizada com Sucesso!';
                $SinSucessoExecucao = 'S';

                if ($semManifestacoesEncontradas) {
                    $textoMensagemFinal = $textoMensagemFinal . ' Não foram encontradas manifestações para o período.';
                } else {
                    $textoMensagemFinal = $textoMensagemFinal . '<br>Quantidade de Manifestações novas encontradas (e-Ouv|e-Sic): ' . $qtdManifestacoesNovas . '<br>Quantidade de Manifestações encontadas que ocorreram erro em outras importações: ' . $qtdManifestacoesAntigas;
                }

                if ($ocorreuErroEmProtocolo) {
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

            die;
        }
    }

    /**
     * Função para importar as manifestações e-Sic do FalaBR (tipo 8)
     */
    public function executarImportacaoManifestacaoESic()
    {
        // Debug
        InfraDebug::getInstance()->setBolLigado(true);
        InfraDebug::getInstance()->setBolDebugInfra(true);
        InfraDebug::getInstance()->setBolEcho(false);
        InfraDebug::getInstance()->limpar();

        // Log
        LogSEI::getInstance()->gravar('Rotina de Importação de Manifestações do FalaBR (e-Sic)', InfraLog::$INFORMACAO);

        global $objEouvRelatorioImportacaoDTO,
               $objEouvRelatorioImportacaoRN,
               $objInfraParametro,
               $urlWebServiceEOuv,
               $urlWebServiceESicRecursos,
               $urlWebServiceAnexosEOuv,
               $idTipoDocumentoAnexoPadrao,
               $idTipoDocumentoAnexoDadosManifestacao,
               $idUnidadeOuvidoria,
               $idUnidadeEsicPrincipal,
               $idUnidadeRecursoPrimeiraInstancia,
               $idUnidadeRecursoSegundaInstancia,
               $idUsuarioSei,
               $dataAtual,
               $objUltimaExecucao,
               $ocorreuErroEmProtocolo,
               $idRelatorioImportacao,
               $usuarioWebService,
               $senhaUsuarioWebService,
               $client_id,
               $client_secret,
               $token,
               $importar_dados_manifestante;

        // Lista parâmetros
        $objEouvParametroDTO = new MdCguEouvParametroDTO();
        $objEouvParametroDTO->retTodos();

        // Busca parâmetros do banco de dados da tabela md_eouv_parametros
        $objEouvParametroRN = new MdCguEouvParametroRN();
        $arrObjEouvParametroDTO = $objEouvParametroRN->listarParametro($objEouvParametroDTO);
        $numRegistros = count($arrObjEouvParametroDTO);

        // Preenche variáveis locais com dados da tabela md_eouv_parametros
        if ($numRegistros > 0) {
            for($i = 0;$i < $numRegistros; $i++) {

                $strParametroNome = $arrObjEouvParametroDTO[$i]->getStrNoParametro();

                switch ($strParametroNome) {

                    case "ESIC_DATA_INICIAL_IMPORTACAO_MANIFESTACOES":
                        $dataInicialImportacaoManifestacoes = $arrObjEouvParametroDTO[$i]->getStrDeValorParametro();
                        break;

                    case "ESIC_URL_WEBSERVICE_IMPORTACAO_RECURSOS":
                        $urlWebServiceESicRecursos = $arrObjEouvParametroDTO[$i]->getStrDeValorParametro();
                        break;

                    case "EOUV_ID_SERIE_DOCUMENTO_EXTERNO_DADOS_MANIFESTACAO":
                        $idTipoDocumentoAnexoDadosManifestacao = $arrObjEouvParametroDTO[$i]->getStrDeValorParametro();
                        break;

                    case "ID_SERIE_EXTERNO_OUVIDORIA":
                        $idTipoDocumentoAnexoPadrao = $arrObjEouvParametroDTO[$i]->getStrDeValorParametro();
                        break;

                    case "EOUV_USUARIO_ACESSO_WEBSERVICE":
                        $usuarioWebService = $arrObjEouvParametroDTO[$i]->getStrDeValorParametro();
                        break;

                    case "EOUV_SENHA_ACESSO_WEBSERVICE":
                        $senhaUsuarioWebService = $arrObjEouvParametroDTO[$i]->getStrDeValorParametro();
                        break;

                    case "CLIENT_ID":
                        $client_id = $arrObjEouvParametroDTO[$i]->getStrDeValorParametro();
                        break;

                    case "CLIENT_SECRET":
                        $client_secret = $arrObjEouvParametroDTO[$i]->getStrDeValorParametro();
                        break;

                    case "EOUV_URL_WEBSERVICE_IMPORTACAO_MANIFESTACAO":
                        $urlWebServiceEOuv = $arrObjEouvParametroDTO[$i]->getStrDeValorParametro();
                        break;

                    case "EOUV_URL_WEBSERVICE_IMPORTACAO_ANEXO_MANIFESTACAO":
                        $urlWebServiceAnexosEOuv = $arrObjEouvParametroDTO[$i]->getStrDeValorParametro();
                        break;

                    case "ESIC_ID_UNIDADE_PRINCIPAL":
                        $idUnidadeEsicPrincipal = $arrObjEouvParametroDTO[$i]->getStrDeValorParametro();
                        break;

                    case "ESIC_ID_UNIDADE_RECURSO_PRIMEIRA_INSTANCIA":
                        $idUnidadeRecursoPrimeiraInstancia = $arrObjEouvParametroDTO[$i]->getStrDeValorParametro();
                        break;

                    case "ESIC_ID_UNIDADE_RECURSO_SEGUNDA_INSTANCIA":
                        $idUnidadeRecursoSegundaInstancia = $arrObjEouvParametroDTO[$i]->getStrDeValorParametro();
                        break;

                    case "ESIC_ID_UNIDADE_RECURSO_TERCEIRA_INSTANCIA":
                        $idUnidadeRecursoTerceiraInstancia = $arrObjEouvParametroDTO[$i]->getStrDeValorParametro();
                        break;

                    case "ESIC_ID_UNIDADE_RECURSO_PEDIDO_REVISAO":
                        $idUnidadeRecursoPedidoRevisao = $arrObjEouvParametroDTO[$i]->getStrDeValorParametro();
                        break;

                    case "TOKEN":
                        $token = $arrObjEouvParametroDTO[$i]->getStrDeValorParametro();
                        break;

                    case "IMPORTAR_DADOS_MANIFESTANTE":
                        $importar_dados_manifestante = $arrObjEouvParametroDTO[$i]->getStrDeValorParametro();
                        break;
                }
            }
        }

        // Busca parâmetros do banco de dados da tabela infra_parametros
        $objInfraParametro = new InfraParametro(BancoSEI::getInstance());
        $idUsuarioSei = $objInfraParametro->getValor('ID_USUARIO_SEI');
        $dataAtual = InfraData::getStrDataHoraAtual();
        $SiglaSistema = 'EOUV';
        $IdentificacaoServico = 'CadastrarManifestacao';

        // Simula login inicial
        $this->simulaLogin($SiglaSistema, $IdentificacaoServico, $idUnidadeEsicPrincipal);

        // Executa a importação dos dados
        try {

            //Retorna dados da Última execução com Sucesso
            $objUltimaExecucao = MdCguEouvAgendamentoINT::retornarUltimaExecucaoSucesso('R');

            if ($objUltimaExecucao != null) {
                $ultimaDataExecucao = $objUltimaExecucao->getDthDthPeriodoFinal();
                $idUltimaExecucao = $objUltimaExecucao->getNumIdRelatorioImportacao();
            } else {
                //Primeira execução ou nenhuma executada com sucesso
                $ultimaDataExecucao = $dataInicialImportacaoManifestacoes;
            }

//            $ultimaDataExecucao = '04/11/2020 01:00:00';
//            $dataAtual = '04/11/2020 23:59:00';
//            $ultimaDataExecucao = '06/01/2021 00:10:00';
//            $dataAtual = '06/01/2021 11:00:00';
            $semManifestacoesEncontradas = true;
            $qtdManifestacoesNovas = 0;
            $qtdManifestacoesAntigas = 0;
            $semRecursosEncontrados = true;
            $qtdRecursosNovos = 0;
            $qtdRecursosAntigos = 0;

            /**
             * A função abaixo gravarLogImportacao recebe o tipo de manifestação 'R' (Recursos) para as manifestações do e-Sic
             */
            $objEouvRelatorioImportacaoDTO = $this->gravarLogImportacao($ultimaDataExecucao, $dataAtual, 'R');
            $idRelatorioImportacao = $objEouvRelatorioImportacaoDTO->getNumIdRelatorioImportacao();
            $objEouvRelatorioImportacaoRN = new MdCguEouvRelatorioImportacaoRN();
            $SinSucessoExecucao = 'N';
            $textoMensagemErroToken = '';

            $retornoWs = $this->executarServicoConsultaManifestacoes($urlWebServiceEOuv, $token, $ultimaDataExecucao, $dataAtual, null, $idRelatorioImportacao);
//            $retornoWs = [];
            $retornoWsRecursos = $this->executarServicoConsultaRecursos($urlWebServiceESicRecursos, $token, $ultimaDataExecucao, $dataAtual, null, $idRelatorioImportacao);
//            $retornoWsRecursos = [];

            //Caso retornado algum erro - Manifestações e-Sic
            if (is_string($retornoWs)) {

                if (strpos($retornoWs, 'Invalidado') !== false) {
                    //Tenta gerar novo token
                    $tokenValido = $this->apiValidarToken($urlWebServiceEOuv, $usuarioWebService, $senhaUsuarioWebService, $client_id, $client_secret);

                    if (isset($tokenValido['error'])) {
                        $textoMensagemErroToken = 'Não foi possível validar o Token de acesso aos WebServices do E-ouv. <br>Verifique as informações de Usuário, Senha, Client_Id e Client_Secret nas configurações de Parâmetros do Módulo';

                    } elseif (isset($tokenValido['access_token'])) {
                        $this->gravarParametroToken($tokenValido['access_token']);
                        $token = $tokenValido['access_token'];

                        //Chama novamente a execução da ConsultaManifestacao que deu errado por causa do Token
                        $retornoWs = $this->executarServicoConsultaManifestacoes($urlWebServiceEOuv, $token, $ultimaDataExecucao, $dataAtual, null, $idRelatorioImportacao);
                        $retornoWsRecursos = $this->executarServicoConsultaRecursos($urlWebServiceESicRecursos, $token, $ultimaDataExecucao, $dataAtual, null, $idRelatorioImportacao);
                    }
                }
            }

            /**
             * @todo - criar rotina para buscar recursos das manifestções com erro caso exista alguma na tabela de log
             */

            if ($textoMensagemErroToken == '') {
                $arrComErro = $this->obterManifestacoesComErro($urlWebServiceEOuv, $token, $ultimaDataExecucao, $dataAtual, $idRelatorioImportacao, 'R');

                $arrManifestacoes = array();

                if (is_array($retornoWs)) {
                    $qtdManifestacoesNovas = count($retornoWs);
                    $arrManifestacoes = $retornoWs;
                }

                if (isset($retornoWsRecursos) && is_array($retornoWsRecursos)) {
                    $arrRecursos = $retornoWsRecursos['Recursos'];
                    $qtdRecursosNovos = count($arrRecursos);
                }

                if (is_array($arrComErro)) {
                    $qtdManifestacoesAntigas = count($arrComErro);
                    $arrManifestacoes = array_merge($arrManifestacoes, $arrComErro);
                }

                // Importa manifestações e-Sic
                if (count($arrManifestacoes) > 0) {
                    $semManifestacoesEncontradas = false;
                    foreach ($arrManifestacoes as $retornoWsLinha) {
                        if ($retornoWsLinha['TipoManifestacao']['IdTipoManifestacao'] == 8) {
                            $this->executarImportacaoLinha($retornoWsLinha, 'R');
                        }
                    }
                }

                // Importa recursos e-Sic
                if (count($arrRecursos) > 0) {
                    $semRecursosEncontrados = false;
                    foreach ($arrRecursos as $retornoWsLinha) {
                        $this->executarImportacaoLinhaRecursos($retornoWsLinha);
                    }
                }

                $textoMensagemFinal = 'Execução Finalizada com Sucesso!';
                $SinSucessoExecucao = 'S';

                if ($semManifestacoesEncontradas) {
                    $textoMensagemFinal = $textoMensagemFinal . ' Não foram encontradas manifestações para o período.';
                } else {
                    $textoMensagemFinal = $textoMensagemFinal . '<br>Quantidade de Manifestações novas encontradas (e-Ouv|e-Sic): ' . $qtdManifestacoesNovas . '<br>Quantidade de Manifestações encontadas que ocorreram erro em outras importações: ' . $qtdManifestacoesAntigas;
                }

                if ($semRecursosEncontrados) {
                    $textoMensagemFinal = $textoMensagemFinal . ' Não foram encontrados recursos para o período.';
                } else {
//                    $textoMensagemFinal = $textoMensagemFinal . '<br>Quantidade de Recursos novos encontrados (e-Sic): ' . $qtdRecursosNovos . '<br>Quantidade de Recursos encontados que ocorreram erro em outras importações: ' . $qtdRecursosAntigos;
                    $textoMensagemFinal = $textoMensagemFinal . '<br>Quantidade de Recursos novos encontrados (e-Sic): ' . $qtdRecursosNovos;
                }

                if ($ocorreuErroEmProtocolo) {
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

        } catch(Exception $e) {

            $objEouvRelatorioImportacaoDTO3 = new MdCguEouvRelatorioImportacaoDTO();
            $objEouvRelatorioImportacaoDTO3->setNumIdRelatorioImportacao($objEouvRelatorioImportacaoDTO->getNumIdRelatorioImportacao());
            $strMensagem = 'Ocorreu um erro no processamento:' . $e;
            $strMensagem = substr($strMensagem, 0, 500);
            $objEouvRelatorioImportacaoDTO3->setStrDeLogProcessamento($strMensagem);
            $objEouvRelatorioImportacaoRN->alterar($objEouvRelatorioImportacaoDTO3);

            PaginaSEI::getInstance()->processarExcecao($e);
            die;
        }
    }

    public function executarImportacaoLinha($retornoWsLinha, $tipoManifestacao = 'P')
    {

        global $objEouvRelatorioImportacaoDTO,
               $idTipoDocumentoAnexoPadrao,
               $urlWebServiceESicRecursos,
               $objProcedimentoDTO,
               $objTipoProcedimentoDTO,
               $arrObjAssuntoDTO,
               $arrObjParticipantesDTO,
               $idTipoDocumentoAnexoDadosManifestacao,
               $idUnidadeOuvidoria,
               $idUnidadeEsicPrincipal,
               $idUnidadeRecursoPrimeiraInstancia,
               $idUnidadeRecursoSegundaInstancia,
               $idUnidadeRecursoTerceiraInstancia,
               $idUnidadeRecursoPedidoRevisao,
               $idUsuarioSei,
               $objWSAnexo,
               $dataRegistro,
               $ocorreuErroEmProtocolo,
               $numProtocoloFormatado,
               $idRelatorioImportacao,
               $token;

        $objProcedimentoDTO = new ProcedimentoDTO();
        $objProtocoloDTO = new ProtocoloDTO();
        $objProcedimentoRN = new ProcedimentoRN();
        $objProcedimentoDTO->setDblIdProcedimento(null);

        $linkDetalheManifestacao = $retornoWsLinha['Links'][0]['href'];
        $arrDetalheManifestacao = $this->apiRestRequest($linkDetalheManifestacao, $token, 2);

        /**
         * Verifica Tipo de Manifestação e-Ouv ou e-Sic
         */
        if ($tipoManifestacao == 'P' && $retornoWsLinha['TipoManifestacao']['IdTipoManifestacao'] <> 8) {
            $manifestacaoESic = false;
            $idUnidadeDestino = $idUnidadeOuvidoria;
        } elseif ($tipoManifestacao == 'R' && $retornoWsLinha['TipoManifestacao']['IdTipoManifestacao'] == 8) {
            $manifestacaoESic = true;
            $idUnidadeDestino = $idUnidadeEsicPrincipal;

            /**
             * Importar Recursos caso seja manifestação e-Sic (Tipo 8)
             */
            $arrRecursosManifestacao = $this->apiRestRequest($urlWebServiceESicRecursos . '?NumProtocolo=' . $arrDetalheManifestacao['NumerosProtocolo'][0], $token, 2);
        }

        $dataRegistro = $arrDetalheManifestacao['DataCadastro'];
        $numProtocoloFormatado =  $this->formatarProcesso($arrDetalheManifestacao['NumerosProtocolo'][0]);


        /**
         * Esta data é gravada na tabela de log detalhada
         * Em caso de alteração no prazo do atendimento será feita nova importação dos dados do recurso
         */
        if ($arrRecursosManifestacao <> '') {
            $dataPrazoAtendimento = $arrRecursosManifestacao['Recursos'][(count($arrRecursosManifestacao['Recursos']) - 1)]['prazoAtendimento'];
        } else {
            $dataPrazoAtendimento = $retornoWsLinha['PrazoAtendimento'];
        }

        /**
         * Limpa os registros de detalhe de importação com erro para este NUP.
         * Caso ocorra um novo, será criado novo registro de erro para o NUP no tratamento desta function.
         */
        $this->limparErrosParaNup($numProtocoloFormatado);

        if (!isset($arrDetalheManifestacao['TipoManifestacao']['IdTipoManifestacao'])) {
            $this->gravarLogLinha($numProtocoloFormatado, $idRelatorioImportacao, 'Tipo de processo não foi informado.', 'N');
        } else {
            $objEouvDeparaImportacaoDTO = new MdCguEouvDeparaImportacaoDTO();
            $objEouvDeparaImportacaoDTO->retNumIdTipoProcedimento();
            $objEouvDeparaImportacaoDTO->setNumIdTipoManifestacaoEouv($arrDetalheManifestacao['TipoManifestacao']['IdTipoManifestacao']);

            $objEouvDeparaImportacaoRN = new MdCguEouvDeparaImportacaoRN();
            $objEouvDeparaImportacaoDTO = $objEouvDeparaImportacaoRN->consultarRN0186($objEouvDeparaImportacaoDTO);

            if (!$objEouvDeparaImportacaoDTO == null) {
                $idTipoManifestacaoSei = $objEouvDeparaImportacaoDTO->getNumIdTipoProcedimento();
            } else {
                $this->gravarLogLinha($numProtocoloFormatado, $objEouvRelatorioImportacaoDTO->getNumIdRelatorioImportacao(), 'Não existe mapeamento DePara do Tipo de Manifestação do FalaBR (E-Ouv|E-Sic) para o tipo de procedimento do SEI.', 'N');
                //continue;
            }
        }

        /**
         * Se for Manifestação do e-Sic verificar:houve alteração na data 'PrazoAtendimento' e
         * gera novo arquivo PDF com as alterações para inserção no mesmo protocolo (NUP) e
         * importa anexos comparando o hash do arquivo para não duplicidade no processo
         */
        // Vefificar se o NUP já existe
        $objProtocoloDTOExistente = $this->verificarProtocoloExistente($this->formatarProcesso($numProtocoloFormatado));

        // 1. Caso já exista um Protocolo no SEI com o mesmo NUP
        if (! is_null($objProtocoloDTOExistente)) {
            // 2. Se existir e for e-Ouv
            if ($tipoManifestacao == 'P') {
                // 2.1 Importar anexos novos se existirem... e retornar log
                // @todo - melhoria próxima versão e-Ouv
                $this->gravarLogLinha($numProtocoloFormatado, $idRelatorioImportacao, 'Erro na gravação: ' . 'Já existe um processo (e-Ouv) utilizando o número de protocolo.', 'N', $tipoManifestacao);
            }

            // 3. Se existir e for e-Sic
            if ($tipoManifestacao == 'R') {

                // Data do último prazo de atendimento para este protocolo
                $objUltimaDataPrazoAtendimento = MdCguEouvAgendamentoINT::retornarUltimaDataPrazoAtendimento($numProtocoloFormatado);

                // 4. Verificar se houve alteração na data 'PrazoAtendimento'
                if (isset($objUltimaDataPrazoAtendimento) && $objUltimaDataPrazoAtendimento->getDthDthPrazoAtendimento() <> $dataPrazoAtendimento) {

                    // Importar anexos do novo recurso
                    try {

                        $anexoCount = 0;
                        if (isset($arrRecursosManifestacao['Recursos']) && is_array($arrRecursosManifestacao['Recursos'])) {

                            // Verifica Tipo de Recurso
                            $tipo_recurso = $this->verificaTipo($arrRecursosManifestacao['Recursos']);

                            // Carregar documento recurso
                            $this->gerarPDFDocumentoESic($arrDetalheManifestacao, $arrRecursosManifestacao, $objProtocoloDTOExistente->getDblIdProtocolo(), $tipo_recurso);
                            LogSEI::getInstance()->gravar('Módulo Integração FalaBR - Importação de Recurso ' . $numProtocoloFormatado . ': total de  Anexos configurados: ' . $anexoCount, InfraLog::$INFORMACAO);
                            $this->gravarLogLinha($numProtocoloFormatado, $idRelatorioImportacao, 'Recurso com protocolo ' . $numProtocoloFormatado . ' importado com sucesso com ' . $anexoCount . ' anexos incluidos no protocolo.', 'S', $tipoManifestacao, $dataPrazoAtendimento);

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
                            // Se for 1 instância envia processo para ESIC_ID_UNIDADE_RECURSO_PRIMEIRA_INSTANCIA
                            if ($tipo_recurso == 'R1') {
                                $unidadeDestino = $idUnidadeRecursoPrimeiraInstancia;
                            } elseif ($tipo_recurso == 'R2') {
                                $unidadeDestino = $idUnidadeRecursoSegundaInstancia;
                            } elseif ($tipo_recurso == 'R3' || $tipo_recurso == 'RC') {
                                $unidadeDestino = $idUnidadeRecursoTerceiraInstancia;
                            } elseif ($tipo_recurso == 'PR') {
                                $unidadeDestino = $idUnidadeRecursoPedidoRevisao;
                            } else {
                                $unidadeDestino = $idUnidadeOuvidoria;
                            }

                            try {
                                $objEntradaEnviarProcesso = new EntradaEnviarProcessoAPI();
                                $objEntradaEnviarProcesso->setIdProcedimento($objProtocoloDTOExistente->getDblIdProtocolo());
                                // $objEntradaEnviarProcesso->setProtocoloProcedimento($numProtocoloFormatado);
                                $objEntradaEnviarProcesso->setUnidadesDestino([$unidadeDestino]);
                                $objEntradaEnviarProcesso->setSinManterAbertoUnidade('S');
                                $objEntradaEnviarProcesso->setSinEnviarEmailNotificacao('S');
                                $objEntradaEnviarProcesso->setSinReabrir('S');

                                $objSeiRN = new SeiRN();
                                $objSeiRN->enviarProcesso($objEntradaEnviarProcesso);
                                LogSEI::getInstance()->gravar('Módulo Integração FalaBR - (Recurso primeira instância) Processo ' . $numProtocoloFormatado . ' enviado para unidade ' . $idUnidadeRecursoPrimeiraInstancia, InfraLog::$INFORMACAO);

                            } catch (Exception $e) {
                                LogSEI::getInstance()->gravar('Módulo Integração FalaBR - (Recurso primeira instância) Não foi possivel abrir o Processo ' . $numProtocoloFormatado . ' na unidade ' . $idUnidadeRecursoPrimeiraInstancia . ' - erro: ' . $e, InfraLog::$INFORMACAO);
                            }
                        } else {
                            $this->gravarLogLinha($numProtocoloFormatado, $idRelatorioImportacao, 'Sem recursos novos.', 'N', $tipoManifestacao);
                        }
                    } catch (Exception $e) {
                        $this->gravarLogLinha($numProtocoloFormatado, $idRelatorioImportacao, 'Erro na gravação recurso: ' . $e, 'N', $tipoManifestacao);
                    }
                } else {
                    // 4.2 Se não houve alteração na data 'PrazoAtendimento' retornar log
                    $this->gravarLogLinha($numProtocoloFormatado, $idRelatorioImportacao, 'Já existe um processo (e-Sic) utilizando o número de protocolo e não há alteração para nova importação.', 'S', $tipoManifestacao);
                }
            }
        } else {
            /**
             * Inicia criação do Procedimento de criação de novo Processo
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
                    throw new Exception('Tipo de processo não encontrado: ' . $idTipoManifestacaoSei);
                }

                /*
                 * Verifica se deve importar documentos tipo 8 (e-Sic)
                 */
                if ($retornoWsLinha['TipoManifestacao']['IdTipoManifestacao'] == 8 && ! $manifestacaoESic) {
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
                $objProcedimentoAPI->setObservacao("Processo Gerado Automaticamente pela Integração SEI x FalaBR");

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

                LogSEI::getInstance()->gravar('Importação de Manifestação ' . $numProtocoloFormatado . ': total de  Anexos configurados: ' . count($arrDocumentos), InfraLog::$INFORMACAO);

//                array_push($arrDocumentos, $documentoManifestacao);
                array_unshift($arrDocumentos, $documentoManifestacao);
                $objEntradaGerarProcedimentoAPI->setDocumentos($arrDocumentos);
                $objSaidaGerarProcedimentoAPI = $objSeiRN->gerarProcedimento($objEntradaGerarProcedimentoAPI);

                $this->gravarLogLinha($numProtocoloFormatado, $idRelatorioImportacao, 'Protocolo ' . $arrDetalheManifestacao['numProtocolo'] . ' gravado com sucesso.', 'S', $tipoManifestacao);

            } catch (Exception $e) {

                if ($objSaidaGerarProcedimentoAPI != null and $objSaidaGerarProcedimentoAPI->getIdProcedimento() > 0){
                    $this->excluirProcessoComErro($objSaidaGerarProcedimentoAPI->getIdProcedimento());
                }
                $this->gravarLogLinha($numProtocoloFormatado, $idRelatorioImportacao, 'Erro na gravação: ' . $e, 'N', $tipoManifestacao);
            }
        }
    }

    public function executarImportacaoLinhaRecursos ($arrRecursosManifestacao, $tipoManifestacao = 'R')
    {

        global $urlWebServiceEOuv,
               $objEouvRelatorioImportacaoDTO,
               $idTipoDocumentoAnexoPadrao,
               $urlWebServiceESicRecursos,
               $objProcedimentoDTO,
               $objTipoProcedimentoDTO,
               $arrObjAssuntoDTO,
               $arrObjParticipantesDTO,
               $idTipoDocumentoAnexoDadosManifestacao,
               $idUnidadeOuvidoria,
               $idUnidadeEsicPrincipal,
               $idUnidadeRecursoPrimeiraInstancia,
               $idUnidadeRecursoSegundaInstancia,
               $idUnidadeRecursoTerceiraInstancia,
               $idUnidadeRecursoPedidoRevisao,
               $idUsuarioSei,
               $objWSAnexo,
               $dataRegistro,
               $ocorreuErroEmProtocolo,
               $numProtocoloFormatado,
               $idRelatorioImportacao,
               $token;

        $objProcedimentoDTO = new ProcedimentoDTO();
        $objProtocoloDTO = new ProtocoloDTO();
        $objProcedimentoRN = new ProcedimentoRN();
        $objProcedimentoDTO->setDblIdProcedimento(null);

        $numProtocoloFormatado =  $this->formatarProcesso($arrRecursosManifestacao['numProtocolo']);
        $dataPrazoAtendimento = $arrRecursosManifestacao['prazoAtendimento'];

        /**
         * Limpa os registros de detalhe de importação com erro para este NUP.
         * Caso ocorra um novo, será criado novo registro de erro para o NUP no tratamento desta function.
         */
        $this->limparErrosParaNup($numProtocoloFormatado);

        /**
         * Se for Manifestação do e-Sic verificar:houve alteração na data 'PrazoAtendimento' e
         * gera novo arquivo PDF com as alterações para inserção no mesmo protocolo (NUP) e
         * importa anexos comparando o hash do arquivo para não duplicidade no processo
         */
        // Vefificar se o NUP já existe
        $objProtocoloDTOExistente = $this->verificarProtocoloExistente($this->formatarProcesso($numProtocoloFormatado));

        // Caso já exista um Protocolo no SEI continua, caso contrário apenas registra o log
        if (! is_null($objProtocoloDTOExistente)) {

            // Se existir e for e-Sic
            if ($tipoManifestacao == 'R') {

                // Data do último prazo de atendimento para este protocolo
                $objUltimaDataPrazoAtendimento = MdCguEouvAgendamentoINT::retornarUltimaDataPrazoAtendimento($numProtocoloFormatado);

                // Verificar se houve alteração na data 'PrazoAtendimento'
                if ($objUltimaDataPrazoAtendimento->getDthDthPrazoAtendimento() <> $dataPrazoAtendimento) {

                    // Importar anexos do novo recurso
                    try {
                        if (isset($arrRecursosManifestacao)) {
                            $anexoCount = isset($arrRecursosManifestacao['qtdAnexos']) ? $arrRecursosManifestacao['qtdAnexos'] : 0;

                            // Verifica Tipo de Recurso
                            $tipo_recurso = $this->verificaTipo($arrRecursosManifestacao);

                            // Buscar dados da Manifestação
                            $numProtocoloSemFormatacao = str_replace(['.', '/', '-'], ['', '', ''], $numProtocoloFormatado);
                            $retornoWsLinha = $this->executarServicoConsultaManifestacoes($urlWebServiceEOuv, $token, null, null, $numProtocoloSemFormatacao, $idRelatorioImportacao);
                            $linkDetalheManifestacao = $retornoWsLinha[0]['Links'][0]['href'];
                            $arrDetalheManifestacao = $this->apiRestRequest($linkDetalheManifestacao, $token, 2);

                            // Carregar documento recurso
                            $this->gerarPDFDocumentoESic($arrDetalheManifestacao, $arrRecursosManifestacao, $objProtocoloDTOExistente->getDblIdProtocolo(), $tipo_recurso);
                            $this->gravarLogLinha($numProtocoloFormatado, $idRelatorioImportacao, 'Recurso com protocolo ' . $numProtocoloFormatado . ' importado com sucesso com ' . $anexoCount . ' anexos incluidos no protocolo.', 'S', $tipoManifestacao, $dataPrazoAtendimento);
                            LogSEI::getInstance()->gravar('Módulo Integração FalaBR - Importação de Recurso ' . $numProtocoloFormatado . ': total de  Anexos configurados: ' . $anexoCount, InfraLog::$INFORMACAO);

                            // Carregar anexos
                            if (count($arrRecursosManifestacao['anexos']) > 0) {
                                $this->gerarAnexosProtocolo($arrRecursosManifestacao['anexos'], $numProtocoloFormatado, $tipoManifestacao, $objProtocoloDTOExistente->getDblIdProtocolo());
                            }

                            // Vincular Recursos com as unidades corretas conforme o tipo de recurso
                            // Se for 1 instância envia processo para ESIC_ID_UNIDADE_RECURSO_PRIMEIRA_INSTANCIA
                            if ($tipo_recurso == 'R1') {
                                $unidadeDestino = $idUnidadeRecursoPrimeiraInstancia;
                            } elseif ($tipo_recurso == 'R2') {
                                $unidadeDestino = $idUnidadeRecursoSegundaInstancia;
                            } elseif ($tipo_recurso == 'R3' || $tipo_recurso == 'RC') {
                                $unidadeDestino = $idUnidadeRecursoTerceiraInstancia;
                            } elseif ($tipo_recurso == 'PR') {
                                $unidadeDestino = $idUnidadeRecursoPedidoRevisao;
                            } else {
                                $unidadeDestino = $idUnidadeOuvidoria;
                            }

                            try {
                                $objEntradaEnviarProcesso = new EntradaEnviarProcessoAPI();
                                $objEntradaEnviarProcesso->setIdProcedimento($objProtocoloDTOExistente->getDblIdProtocolo());
                                // $objEntradaEnviarProcesso->setProtocoloProcedimento($numProtocoloFormatado);
                                $objEntradaEnviarProcesso->setUnidadesDestino([$unidadeDestino]);
                                $objEntradaEnviarProcesso->setSinManterAbertoUnidade('S');
                                $objEntradaEnviarProcesso->setSinEnviarEmailNotificacao('S');
                                $objEntradaEnviarProcesso->setSinReabrir('S');

                                $objSeiRN = new SeiRN();
                                $objSeiRN->enviarProcesso($objEntradaEnviarProcesso);
                                LogSEI::getInstance()->gravar('Módulo Integração FalaBR - (Recurso primeira instância) Processo ' . $numProtocoloFormatado . ' enviado para unidade ' . $idUnidadeRecursoPrimeiraInstancia, InfraLog::$INFORMACAO);

                            } catch (Exception $e) {
                                LogSEI::getInstance()->gravar('Módulo Integração FalaBR - (Recurso primeira instância) Não foi possivel abrir o Processo ' . $numProtocoloFormatado . ' na unidade ' . $idUnidadeRecursoPrimeiraInstancia . ' - erro: ' . $e, InfraLog::$INFORMACAO);
                            }
                        } else {
                            $this->gravarLogLinha($numProtocoloFormatado, $idRelatorioImportacao, 'Sem recursos novos.', 'N', $tipoManifestacao);
                        }
                    } catch (Exception $e) {
                        $this->gravarLogLinha($numProtocoloFormatado, $idRelatorioImportacao, 'Erro na gravação recurso: ' . $e, 'N', $tipoManifestacao);
                    }
                } else {
                    // Se não houve alteração na data 'PrazoAtendimento' retornar log
                    $this->gravarLogLinha($numProtocoloFormatado, $idRelatorioImportacao, 'Já existe um processo (e-Sic) utilizando o número de protocolo e não há alteração para nova importação.', 'S', $tipoManifestacao);
                }
            }
        } else {
            $this->gravarLogLinha($numProtocoloFormatado, $idRelatorioImportacao, 'Existe recuso para o processo ' . $numProtocoloFormatado . ', porém este processo não existe no SEI. Provavelmente é um processo antes da data de início de utilização do módulo ou o Tipo de Manifestação do FalaBR não foi registrada para este módulo.', 'S', $tipoManifestacao);
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

    public function gerarPDFPedidoInicial($retornoWsLinha){

        global $idTipoDocumentoAnexoDadosManifestacao,
               $ocorreuErroAdicionarAnexo,
               $importar_dados_manifestante;

        /***********************************************************************************************
         * // DADOS INICIAIS DA MANIFESTAÇÃO
         * Primeiro é gerado o PDF com todas as informações referentes a Manifestação, e mais abaixo
         * é incluindo como um anexo do novo Processo Gerado
         * **********************************************************************************************/
        $urlEouvDetalhesManifestacao = $retornoWsLinha['Links'][0]['href'];
        $nup = $retornoWsLinha['NumerosProtocolo'][0];
        $dt_cadastro = $retornoWsLinha['DataCadastro'];

        if(is_array($retornoWsLinha['Assunto'])) {
            $desc_assunto = $retornoWsLinha['Assunto']['DescAssunto'];
        }

        if ( is_array($retornoWsLinha['SubAssunto']) && isset($retornoWsLinha['SubAssunto']['DescSubAssunto'])){
            $desc_sub_assunto = $retornoWsLinha['SubAssunto']['DescSubAssunto'];
        }

        $id_tipo_manifestacao = $retornoWsLinha['TipoManifestacao']['IdTipoManifestacao'];
        $desc_tipo_manifestacao = $retornoWsLinha['TipoManifestacao']['DescTipoManifestacao'];
        $envolve_das4_superior = $retornoWsLinha['InformacoesAdicionais']['EnvolveCargoComissionadoDAS4OuSuperior'];
        $dt_prazo_atendimento = $retornoWsLinha['PrazoAtendimento'];
        $nome_orgao = $retornoWsLinha['OuvidoriaDestino']['NomeOuvidoria'];

        if(is_array($retornoWsLinha['CanalEntrada'])) {
            $canal_entrada = $retornoWsLinha['CanalEntrada']['IdCanalEntrada'] . " - " . $retornoWsLinha['CanalEntrada']['DescCanalEntrada'];
        }

        $registrado_por = $retornoWsLinha['RegistradoPor'];

        //print_r($retornoWsLinha['SolicitanteManifestacaoOuvidoria']);
        //exit();

        if (is_array($retornoWsLinha['Manifestante'])) {

            $nome = $retornoWsLinha['Manifestante']['Nome'];
            $desc_faixa_etaria = $retornoWsLinha['Manifestante']['FaixaEtaria'];
            $desc_raca_cor = $retornoWsLinha['Manifestante']['corRaça'];
            $sexo = $retornoWsLinha['Manifestante']['genero'];
            $desc_documento_identificacao = $retornoWsLinha['Manifestante']['TipoDocumentoIdentificacao'];
            $numero_documento_identificacao = $retornoWsLinha['Manifestante']['NumeroDocumentoIdentificacao'];
            $endereco = $retornoWsLinha['Manifestante']['Endereco']['Logradouro'] . " " . $retornoWsLinha['Manifestante']['Endereco']['Complemento'];
            $bairro = $retornoWsLinha['Manifestante']['Endereco']['Bairro'];

            if (is_array($retornoWsLinha['Manifestante']['Endereco']['Municipio'])) {
                $desc_municipio = $retornoWsLinha['Manifestante']['Endereco']['Municipio']['DescMunicipio'] . " / " . $retornoWsLinha['Manifestante']['Endereco']['Municipio']['Uf']['SigUf'] . " - " . $retornoWsLinha['Manifestante']['Endereco']['Municipio']['Uf']['DescUf'];
            }

            $cep = $retornoWsLinha['Manifestante']['Endereco']['Cep'];

            if(is_array($retornoWsLinha['Manifestante']['Telefone'])) {
                $telefone = "(" . $retornoWsLinha['Manifestante']['Telefone']['ddd'] . ") " . $retornoWsLinha['Manifestante']['Telefone']['Numero'];
            }

            $email = $retornoWsLinha['Manifestante']['Email'];

            $idTipoIdentificacaoManifestante = $retornoWsLinha['Manifestante']['TipoIdentificacaoManifestante']['IdTTipoIdentificacaoManifestanteDTO'];
            $descTipoIdentificacaoManifestante = $retornoWsLinha['Manifestante']['TipoIdentificacaoManifestante']['DescTTipoIdentificacaoManifestanteDTO'];
        }

        if(is_array($retornoWsLinha['Teor']['LocalFato'])) {
            if(is_array($retornoWsLinha['Teor']['LocalFato']['Municipio'])) {
                $desc_municipio_fato = $retornoWsLinha['Teor']['LocalFato']['Municipio']['DescMunicipio'] . " / " . $retornoWsLinha['Teor']['LocalFato']['Municipio']['Uf']['SigUf'] . " - " . $retornoWsLinha['Teor']['LocalFato']['Municipio']['Uf']['DescUf'];
            }

            $descricao_local_fato = $retornoWsLinha['Teor']['LocalFato']['DescricaoLocalFato'];
        }

        $descricao_fato = $retornoWsLinha['Teor']['DescricaoAtosOuFatos'];


        $envolvidos = array();
        if (is_array($retornoWsLinha['Teor']['EnvolvidosManifestacao']) && isset($retornoWsLinha['Teor']['EnvolvidosManifestacao'])) {
            $iEnvolvido = 0;
            foreach ($this->verificaRetornoWS($retornoWsLinha['EnvolvidosManifestacao']) as $envolvidosFatoManifestacao) {
                $envolvidos[$iEnvolvido][0] = $envolvidosFatoManifestacao['EnvolvidosManifestacao']['IdFuncaoEnvolvidoManifestacao'] . " - " . $envolvidosFatoManifestacao['EnvolvidosManifestacao']['Funcao'];
                $envolvidos[$iEnvolvido][1] = $envolvidosFatoManifestacao['EnvolvidosManifestacao']['Nome'];
                $envolvidos[$iEnvolvido][2] = $envolvidosFatoManifestacao['EnvolvidosManifestacao']['Orgao'];
                $iEnvolvido++;
            }
        }

        $campos_adicionais = array();

        if (is_array($retornoWsLinha['Teor']['CamposAdicionaisManifestacao']) && isset($retornoWsLinha['Teor']['CamposAdicionaisManifestacao'])) {
            $iCamposAdicionais = 0;
            foreach ($this->verificaRetornoWS($retornoWsLinha['CamposAdicionaisManifestacao']) as $camposAdicionais) {
                $campos_adicionais[$iCamposAdicionais][0] = $camposAdicionais['NomeExibido'];
                $campos_adicionais[$iCamposAdicionais][1] = $camposAdicionais['Valor'];
                $iCamposAdicionais++;
            }
        }

        $pdf = new InfraPDF("P", "pt", "A4");

        $pdf->AddPage();
        //$pdf->Image('logog8.jpg');

        $pdf->SetFont('arial', 'B', 18);
        $pdf->Cell(0, 5, "Dados da Manifestação", 0, 1, 'C');
        $pdf->Cell(0, 5, "", "B", 1, 'C');
        $pdf->Ln(20);

        //***********************************************************************************************
        //1. Dados INICIAIS
        //***********************************************************************************************
        $pdf->SetFont('arial', 'B', 14);
        $pdf->Cell(0, 20, "1. Dados Iniciais da Manifestação", 0, 0, 'L');
        $pdf->Ln(20);

        //NUP
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(150, 20, "NUP:", 0, 0, 'L');
        $pdf->setFont('arial', '', 12);
        $pdf->Cell(0, 20, $nup, 0, 1, 'L');

        //Data Cadastro
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(150, 20, "Data do Cadastro:", 0, 0, 'L');
        $pdf->setFont('arial', '', 12);
        $pdf->Cell(0, 20, $dt_cadastro, 0, 1, 'L');

        //Assunto / SubAssunto
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(150, 20, "Assunto/SubAssunto:", 0, 0, 'L');
        $pdf->setFont('arial', '', 12);
        $pdf->Cell(0, 20, $desc_assunto . " / " . $desc_sub_assunto, 0, 1, 'L');

        //Tipo de Manifestação
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(150, 20, "Tipo da Manifestação:", 0, 0, 'L');
        $pdf->setFont('arial', '', 12);
        $pdf->Cell(0, 20, $id_tipo_manifestacao . " - " . $desc_tipo_manifestacao, 0, 1, 'L');

        //EnvolveDas4OuSuperior
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(450, 20, "Denúncia Envolvendo Ocupante de Cargo Comissionado DAS4 ou Superior?:", 0, 0, 'L');
        $pdf->setFont('arial', '', 12);
        $pdf->Cell(20, 20, $envolve_das4_superior, 0, 1, 'L');

        //Prazo de Atendimento
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(150, 20, "Prazo de Atendimento:", 0, 0, 'L');
        $pdf->setFont('arial', '', 12);
        $pdf->Cell(70, 20, $dt_prazo_atendimento, 0, 1, 'L');

        //Nome do Órgão
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(150, 20, "Nome do Órgão:", 0, 0, 'L');
        $pdf->setFont('arial', '', 12);
        $pdf->Cell(70, 20, $nome_orgao, 0, 1, 'L');

        //Canal Entrada
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(150, 20, "Canal de Entrada:", 0, 0, 'L');
        $pdf->setFont('arial', '', 12);
        $pdf->Cell(70, 20, $canal_entrada, 0, 1, 'L');

        //Registrado Por
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(150, 20, "Registrado Por:", 0, 0, 'L');
        $pdf->setFont('arial', '', 12);
        $pdf->Cell(70, 20, $registrado_por, 0, 1, 'L');

        //***********************************************************************************************
        //2. Dados do Solicitante
        //***********************************************************************************************
        $pdf->Ln(20);
        $pdf->SetFont('arial', 'B', 14);
        $pdf->Cell(70, 20, "2. Dados do Solicitante:", 0, 0, 'L');
        $pdf->Ln(20);

        if ($importar_dados_manifestante) {
            //Nome do Solicitante
            $pdf->SetFont('arial', 'B', 12);
            $pdf->Cell(150, 20, "Nome do Solicitante:", 0, 0, 'L');
            $pdf->setFont('arial', '', 12);
            $pdf->Cell(70, 20, $nome, 0, 1, 'L');

            //Faixa Etária
            $pdf->SetFont('arial', 'B', 12);
            $pdf->Cell(150, 20, "Faixa Etária:", 0, 0, 'L');
            $pdf->setFont('arial', '', 12);
            $pdf->Cell(70, 20, $desc_faixa_etaria, 0, 1, 'L');

            //Raça Cor
            $pdf->SetFont('arial', 'B', 12);
            $pdf->Cell(150, 20, "Raça/Cor:", 0, 0, 'L');
            $pdf->setFont('arial', '', 12);
            $pdf->Cell(70, 20, $desc_raca_cor, 0, 1, 'L');

            //Sexo
            $pdf->SetFont('arial', 'B', 12);
            $pdf->Cell(150, 20, "Sexo:", 0, 0, 'L');
            $pdf->setFont('arial', '', 12);
            $pdf->Cell(70, 20, $sexo, 0, 1, 'L');

            //Documento Identificação
            $pdf->SetFont('arial', 'B', 12);
            $pdf->Cell(170, 20, "Documento de Identificação:", 0, 0, 'L');
            $pdf->setFont('arial', '', 12);
            $pdf->Cell(70, 20, $desc_documento_identificacao, 0, 1, 'L');

            //NÃºmero do Documento Identificação
            $pdf->SetFont('arial', 'B', 12);
            $pdf->Cell(150, 20, "Número do Documento:", 0, 0, 'L');
            $pdf->setFont('arial', '', 12);
            $pdf->Cell(70, 20, $numero_documento_identificacao, 0, 1, 'L');

            $pdf->ln(4);
            //Endereço
            $pdf->SetFont('arial', 'B', 12);
            $pdf->Cell(70, 20, "Endereço:", 0, 1, 'L');
            $pdf->setFont('arial', '', 12);
            $pdf->Cell(70, 20, $endereco, 0, 1, 'L');
            $pdf->Cell(70, 20, $bairro, 0, 1, 'L');
            $pdf->Cell(70, 20, $desc_municipio, 0, 1, 'L');

            //CEP
            $pdf->Cell(70, 20, "CEP:", 0, 0, 'L');
            $pdf->setFont('arial', '', 12);
            $pdf->Cell(70, 20, $cep, 0, 1, 'L');

            //Telefone
            $pdf->SetFont('arial', 'B', 12);
            $pdf->Cell(70, 20, "Telefone:", 0, 0, 'L');
            $pdf->setFont('arial', '', 12);
            $pdf->Cell(70, 20, $telefone, 0, 1, 'L');

            //Email
            $pdf->SetFont('arial', 'B', 12);
            $pdf->Cell(70, 20, "E-mail:", 0, 0, 'L');
            $pdf->setFont('arial', '', 12);
            $pdf->Cell(70, 20, $email, 0, 1, 'L');
        }
        else{
            $pdf->SetFont('arial', 'B', 12);
            $pdf->Cell(150, 20, "Não importado do E-Ouv devido a configuração no módulo.", 0, 0, 'L');
        }
        $pdf->Ln(20);

        //***********************************************************************************************
        //3. Dados do Fato da Manifestação
        //***********************************************************************************************
        $pdf->Ln(20);
        $pdf->SetFont('arial', 'B', 14);
        $pdf->Cell(70, 20, "3. Fato da Manifestação:", 0, 0, 'L');
        $pdf->Ln(20);

        //Município/UF
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(115, 20, "Município/UF:", 0, 0, 'L');
        $pdf->setFont('arial', '', 12);
        $pdf->Cell(70, 20, $desc_municipio_fato, 0, 1, 'L');

        //Descricao Local
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(115, 20, "Local:", 0, 0, 'L');
        $pdf->setFont('arial', '', 12);
        $pdf->Cell(70, 20, $descricao_local_fato, 0, 1, 'L');

        //Descrição
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(115, 20, "Descrição:", 0, 1, 'L');
        $pdf->setFont('arial', '', 12);
        $pdf->MultiCell(0, 20, $descricao_fato, 0, 'J');

        //Envolvidos
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(115, 20, "Envolvidos:", 0, 1, 'L');
        $pdf->setFont('arial', '', 12);

        for ($x = 0; $x < count($envolvidos); $x++) {
            $pdf->Cell(70, 20, "Função:", 0, 0, 'L');
            $pdf->Cell(0, 20, $envolvidos[$x][0], 0, 1, 'L');
            $pdf->Cell(70, 20, "Nome:", 0, 0, 'L');
            $pdf->Cell(0, 20, $envolvidos[$x][1], 0, 1, 'L');
            $pdf->Cell(70, 20, "Órgão:", 0, 0, 'L');
            $pdf->Cell(0, 20, $envolvidos[$x][2], 0, 1, 'L');
            $pdf->Ln(10);
        }

        //***********************************************************************************************
        //4. Campos Adicionais
        //***********************************************************************************************
        $pdf->Ln(20);
        $pdf->SetFont('arial', 'B', 14);
        $pdf->Cell(70, 20, "4. Campos Adicionais:", 0, 0, 'L');
        $pdf->Ln(20);

        for ($y = 0; $y < count($campos_adicionais); $y++) {
            $pdf->SetFont('arial', 'B', 12);
            $pdf->Cell(180, 20, $campos_adicionais[$y][0] . ":", 0, 0, 'L');
            $pdf->setFont('arial', '', 12);
            $pdf->Cell(0, 20, $campos_adicionais[$y][1], 0, 1, 'L');
        }

        if($ocorreuErroAdicionarAnexo == true){
            $pdf->Ln(20);
            $pdf->SetFont('arial', 'B', 14);
            $pdf->Cell(70, 20, "5. Observações:", 0, 0, 'L');
            $pdf->Ln(20);

            $pdf->SetFont('arial', '', 12);
            $pdf->MultiCell(0, 20, "Um ou mais anexos da manifestação não foram importados para o SEI devido a restrições da extensão do arquivo. Acesse a manifestação através do link abaixo para mais detalhes. ", 0, 'J');
        }

        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(115, 20, "Link para manifestação no E-ouv:", 0, 1, 'L');
        $pdf->setFont('arial', '', 12);
        $pdf->Cell(0, 20, $urlEouvDetalhesManifestacao, 0, 1, 'L');

        $objAnexoRN = new AnexoRN();
        $strNomeArquivoInicialUpload = $objAnexoRN->gerarNomeArquivoTemporario();

        $pdf->Output(DIR_SEI_TEMP . "/" . $strNomeArquivoInicialUpload . ".pdf", "F");

        //Renomeia tirando a extensï¿½o para o SEI trabalhar o Arquivo
        rename(DIR_SEI_TEMP . "/" . $strNomeArquivoInicialUpload . ".pdf", DIR_SEI_TEMP . "/" . $strNomeArquivoInicialUpload);

        $objDocumentoManifestacao = new DocumentoAPI();
        $objDocumentoManifestacao->setTipo('R');
        $objDocumentoManifestacao->setIdSerie($idTipoDocumentoAnexoDadosManifestacao);
        $objDocumentoManifestacao->setData($retornoWsLinha['DataCadastro']);
        $objDocumentoManifestacao->setNomeArquivo('RelatórioDadosManifestação.pdf');
        $objDocumentoManifestacao->setConteudo(base64_encode(file_get_contents(DIR_SEI_TEMP . "/" . $strNomeArquivoInicialUpload)));

        return $objDocumentoManifestacao;
    }

    public function gerarPDFDocumentoESic($retornoWsLinha, $retornoWsRecursos = null, $IdProtocolo = false, $tipo_recurso = '')
    {
        global $idTipoDocumentoAnexoDadosManifestacao,
               $ocorreuErroAdicionarAnexo,
               $importar_dados_manifestante;

        /***********************************************************************************************
         * DADOS INICIAIS DA MANIFESTAÇÃO
         * Primeiro é gerado o PDF com todas as informações referentes a Manifestação e mais abaixo
         * é incluído como um anexo do novo Processo Gerado
         * *********************************************************************************************/

        $pdf = new InfraPDF("P", "pt", "A4");

        $pdf->AddPage();

        /**
         * Arquivo PDF - manifestação e-Sic
         */

        // Cabeçalho
        $pdf->SetFont('arial', 'B', 18);
        $pdf->Cell(0, 5, "Plataforma Integrada de Ouvidoria e Acesso à Informação", 0, 1, 'C');
        $pdf->Ln(20);
        $pdf->Cell(0, 5, "Detalhes da Manifestação", 0, 1, 'C');
        $pdf->Ln(30);

        /**
         * Dados básicos da manifestação
         */
        $menu_count = 1;
        $pdf->SetFont('arial', 'B', 14);
        $pdf->Cell(0, 20, $menu_count . ". Dados Básicos da Manifestação", 1, 0, 'L');
        $pdf->Ln(30);

        // Tipo de Manifestação
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(180, 20, "Tipo da Manifestação:", 0, 0, 'R');
        $pdf->setFont('arial', '', 12);
        $pdf->Cell(0, 20, $retornoWsLinha['TipoManifestacao']['IdTipoManifestacao'] . " - " . $retornoWsLinha['TipoManifestacao']['DescTipoManifestacao'], 0, 1, 'L');

        // Esfera (?)
//        $pdf->SetFont('arial', 'B', 12);
//        $pdf->Cell(180, 20, "Esfera:", 0, 0, 'R');
//        $pdf->setFont('arial', '', 12);
//        $pdf->Cell(0, 20, 'n/a', 0, 1, 'L');

        // NUP
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(180, 20, "NUP:", 0, 0, 'R');
        $pdf->setFont('arial', '', 12);
        $pdf->Cell(0, 20, $retornoWsLinha['NumerosProtocolo'][0], 0, 1, 'L');

        // Órgão Destinatário - NomeOuvidoria
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(180, 20, "Órgão Destinatário:", 0, 0, 'R');
        $pdf->setFont('arial', '', 12);
        $pdf->Cell(0, 20, $retornoWsLinha['OuvidoriaDestino']['NomOuvidoria'], 0, 1, 'L');

        // Órgão de Interesse
        if ($retornoWsLinha['OrgaoInteresse'] && $retornoWsLinha['OrgaoInteresse']['NomeOrgao']) {
            $pdf->SetFont('arial', 'B', 12);
            $pdf->Cell(180, 20, "Órgão de Interesse:", 0, 0, 'R');
            $pdf->setFont('arial', '', 12);
            $pdf->Cell(0, 20, $retornoWsLinha['OrgaoInteresse']['NomeOrgao'], 0, 1, 'L');
        }

        // Assunto
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(180, 20, "Assunto:", 0, 0, 'R');
        $pdf->setFont('arial', '', 12);
        $pdf->Cell(0, 20, isset($retornoWsLinha['Assunto']['DescAssunto']) ? $retornoWsLinha['Assunto']['DescAssunto'] : '', 0, 1, 'L');

        // SubAssunto
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(180, 20, "SubAssunto:", 0, 0, 'R');
        if ( is_array($retornoWsLinha['SubAssunto']) && isset($retornoWsLinha['SubAssunto']['DescSubAssunto'])){
            $pdf->setFont('arial', '', 12);
            $pdf->Cell(0, 20, $retornoWsLinha['SubAssunto']['DescSubAssunto'], 0, 1, 'L');
        }

        // Data Cadastro
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(180, 20, "Data do Cadastro:", 0, 0, 'R');
        $pdf->setFont('arial', '', 12);
        $pdf->Cell(0, 20, $retornoWsLinha['DataCadastro'], 0, 1, 'L');

        // Situação
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(180, 20, "Situação:", 0, 0, 'R');
        $pdf->setFont('arial', '', 12);
        $pdf->Cell(0, 20, $retornoWsLinha['Situacao']['DescSituacaoManifestacao'], 0, 1, 'L');

        // Data limite para resposta
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(180, 20, "Data limite para resposta:", 0, 0, 'R');
        $pdf->setFont('arial', '', 12);
        $pdf->Cell(70, 20, $retornoWsLinha['PrazoAtendimento'], 0, 1, 'L');

        // Canal Entrada
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(180, 20, "Canal de Entrada:", 0, 0, 'R');
        $pdf->setFont('arial', '', 12);
        $pdf->Cell(70, 20, $retornoWsLinha['CanalEntrada']['IdCanalEntrada'] . " - " . $retornoWsLinha['CanalEntrada']['DescCanalEntrada'], 0, 1, 'L');

        // Modo de Resposta
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(180, 20, "Modo de Resposta:", 0, 0, 'R');
        $pdf->setFont('arial', '', 12);
        $pdf->Cell(70, 20, $retornoWsLinha['ModoResposta']['DescModoResposta'], 0, 1, 'L');

        // Registrado Por
//        $pdf->SetFont('arial', 'B', 12);
//        $pdf->Cell(180, 20, "Registrado Por:", 0, 0, 'R');
//        $pdf->setFont('arial', '', 12);
//        $pdf->Cell(70, 20, $retornoWsLinha['RegistradoPor'], 0, 1, 'L');

        // Serviço
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(180, 20, "Serviço:", 0, 0, 'R');
        $pdf->setFont('arial', '', 12);
        $pdf->Cell(70, 20, $retornoWsLinha['Servico'], 0, 1, 'L');

        // Outro Serviço (?)
//        $pdf->SetFont('arial', 'B', 12);
//        $pdf->Cell(180, 20, "Outro Serviço:", 0, 0, 'R');
//        $pdf->setFont('arial', '', 12);
//        $pdf->Cell(70, 20, $retornoWsLinha['Servico'], 0, 1, 'L');

        /**
         * Dados básicos da manifestação
         */
        $menu_count++;
        $pdf->Ln(30);
        $pdf->SetFont('arial', 'B', 14);
        $pdf->Cell(0, 20, $menu_count . ". Teor da Manifestação", 1, 0, 'L');
        $pdf->Ln(30);

        // Extrato
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(180, 20, "Extrato:", 0, 0, 'R');
        $pdf->setFont('arial', '', 12);
        $pdf->MultiCell(0, 20, $retornoWsLinha['Teor']['DescricaoAtosOuFatos'], 0, 'J');

        // Proposta de Melhoraia
//        $pdf->SetFont('arial', 'B', 12);
//        $pdf->Cell(180, 20, "Proposta de Melhoria:", 0, 0, 'R');
//        $pdf->setFont('arial', '', 12);
//        $pdf->Cell(0, 20, $retornoWsLinha['Teor']['PropostaMelhoria'], 0, 1, 'L');

        // Município do local do fato
//        $pdf->SetFont('arial', 'B', 12);
//        $pdf->Cell(180, 20, "Município do local do fato:", 0, 0, 'R');
//        if (is_array($retornoWsLinha['Teor']['LocalFato'])) {
//            if (is_array($retornoWsLinha['Teor']['LocalFato']['Municipio'])) {
//                $pdf->setFont('arial', '', 12);
//                $pdf->Cell(70, 20, $retornoWsLinha['Teor']['LocalFato']['Municipio']['DescMunicipio'], 0, 1, 'L');
//            }
//        }

        // UF do local do fato
//        $pdf->SetFont('arial', 'B', 12);
//        $pdf->Cell(180, 20, "UF do local do fato:", 0, 0, 'R');
//        if (is_array($retornoWsLinha['Teor']['LocalFato'])) {
//            if (is_array($retornoWsLinha['Teor']['LocalFato']['Municipio'])) {
//                $pdf->setFont('arial', '', 12);
//                $pdf->Cell(70, 20, $retornoWsLinha['Teor']['LocalFato']['Municipio']['Uf']['SigUf'], 0, 1, 'L');
//            }
//        }

        // Descricao Local
//        $pdf->SetFont('arial', 'B', 12);
//        $pdf->Cell(180, 20, "Local:", 0, 0, 'R');
//        if (is_array($retornoWsLinha['Teor']['LocalFato'])) {
//            $pdf->setFont('arial', '', 12);
//            $pdf->Cell(70, 20, $retornoWsLinha['Teor']['LocalFato']['DescricaoLocalFato'], 0, 1, 'L');
//        }

        /**
         * Anexos
         *
         * - IdTipoAnexoManifestacao : DescTipoAnexoManifestacao
         * - 1 : "Anexo Manifestação"
         * - 2 : "Anexo Resposta"
         */
        // Anexos
        $menu_count++;
        $pdf->Ln(30);
        $pdf->SetFont('arial', 'B', 14);
        $pdf->Cell(0, 20, $menu_count . ". Anexo(s)", true, 0, 'L');
        $pdf->Ln(30);

        $anexos = $retornoWsLinha['Teor']['Anexos'];
        if (count($anexos) > 0) {

            $anexo_tipo_original = 0;
            $anexo_tipo_complementar = 0;

            foreach ($anexos as $anexo) {

                if ($anexo['TipoAnexoManifestacao']['IdTipoAnexoManifestacao'] == 1) {
                    if ($anexo['IndComplementar'] == false) {
                        $anexo_tipo_original++;
                    } else {
                        $anexo_tipo_complementar++;
                    }

                    // Nome do Arquivo
                    $pdf->SetFont('arial', 'B', 12);
                    $pdf->Cell(180, 20, "Nome do arquivo:", 0, 0, 'R');
                    $pdf->setFont('arial', '', 12);
                    $pdf->Cell(70, 20, $anexo['NomeArquivo'], 0, 1, 'L');

                    // Tipo de Anexo
                    $pdf->SetFont('arial', 'B', 12);
                    $pdf->Cell(180, 20, "Tipo de Anexo:", 0, 0, 'R');
                    $pdf->setFont('arial', '', 12);
                    $pdf->Cell(70, 20, $anexo['TipoAnexoManifestacao']['DescTipoAnexoManifestacao'], 0, 1, 'L');

                    // Anexo Complementar
                    $pdf->SetFont('arial', 'B', 12);
                    $pdf->Cell(180, 20, "Anexo Complementar:", 0, 0, 'R');
                    $pdf->setFont('arial', '', 12);
                    $pdf->Cell(70, 20, $anexo['IndComplementar'] ? 'sim' : 'não', 0, 1, 'L');

                    $pdf->Ln(20);
                }
            }
        }
        if ($anexo_tipo_original == 0) {
            // Sem anexo original
            $pdf->SetFont('arial', 'B', 12);
            $pdf->Cell(180, 20, "Não há anexos originais da manifestação.", 0, 0, 'L');
            $pdf->Ln(20);
        }
        if ($anexo_tipo_complementar == 0) {
            // Sem anexo complementar
            $pdf->SetFont('arial', 'B', 12);
            $pdf->Cell(180, 20, "Não há anexos complementares.", 0, 0, 'L');
            $pdf->Ln(20);
        }

        // Textos Complementares - @todo - onde estão os textos complementares?
//        $pdf->SetFont('arial', 'B', 12);
//        $pdf->Cell(150, 20, "Descrição:", 0, 1, 'L');
//        $pdf->setFont('arial', '', 12);
//        $pdf->Cell(0, 20, '??', 0, 1, 'L');

        /**
         * Envolvidos
         */
//        $menu_count++;
//        $pdf->Ln(30);
//        $pdf->SetFont('arial', 'B', 14);
//        $pdf->Cell(0, 20, $menu_count . ". Envolvido(s)", true, 0, 'L');
//        $pdf->Ln(30);
//
//        $envolvidos = $retornoWsLinha['Teor']['EnvolvidosManifestacao'];
//        if (count($envolvidos) > 0) {
//
//            foreach ($envolvidos as $envolvido) {
//
//                // Nome do Arquivo
//                $pdf->SetFont('arial', 'B', 12);
//                $pdf->Cell(180, 20, "Nome:", 0, 0, 'R');
//                $pdf->setFont('arial', '', 12);
//                $pdf->Cell(70, 20, $envolvido['Nome'], 0, 1, 'L');
//
//                // Tipo de Anexo
//                $pdf->SetFont('arial', 'B', 12);
//                $pdf->Cell(180, 20, "Função:", 0, 0, 'R');
//                $pdf->setFont('arial', '', 12);
//                $pdf->Cell(70, 20, $envolvido['Funcao'], 0, 1, 'L');
//
//                // Anexo Complementar
//                $pdf->SetFont('arial', 'B', 12);
//                $pdf->Cell(180, 20, "Orgão:", 0, 0, 'R');
//                $pdf->setFont('arial', '', 12);
//                $pdf->Cell(70, 20, $envolvido['Orgao'], 0, 1, 'L');
//
//                $pdf->Ln(20);
//            }
//        } else {
//            // Sem envolvidos
//            $pdf->SetFont('arial', 'B', 12);
//            $pdf->Cell(180, 20, "Não há envolvidos na manifestação.", 0, 0, 'L');
//            $pdf->Ln(20);
//        }

        /**
         * Campos Adicionais
         */
        $menu_count++;
        $pdf->Ln(30);
        $pdf->SetFont('arial', 'B', 14);
        $pdf->Cell(0, 20, $menu_count . ". Campos Adicionais", true, 0, 'L');
        $pdf->Ln(30);

        $campos_adicionais = $retornoWsLinha['Teor']['CamposAdicionaisManifestacao'];
        if (count($campos_adicionais) > 0) {

            foreach ($campos_adicionais as $campo_adicional) {

                // Campo - NomeExibido: Valor
                $pdf->SetFont('arial', 'B', 12);
                $pdf->Cell(180, 20, $campo_adicional['IdCampoAdicional'] . '. ' . $campo_adicional['Nome'] . ':', 0, 0, 'R');
                $pdf->setFont('arial', '', 12);
                $pdf->Cell(70, 20, $campo_adicional['Valor'], 0, 1, 'L');
                $pdf->Ln(20);
            }
        } else {
            // Sem envolvidos
            $pdf->SetFont('arial', 'B', 12);
            $pdf->Cell(180, 20, "Não há campos adicionais.", 0, 0, 'L');
            $pdf->Ln(20);
        }

        /**
         * Dados das Respostas
         */
//        $menu_count++;
//        $pdf->Ln(30);
//        $pdf->SetFont('arial', 'B', 14);
//        $pdf->Cell(0, 20, $menu_count . ". Dados das Respostas", true, 0, 'L');
//        $pdf->Ln(30);

        // Envolve DAS4 ou superior
//        $pdf->SetFont('arial', 'B', 12);
//        $pdf->MultiCell(180, 20, "Envolve ocupante de cargo comissionado DAS a partir do nível 4 ou equivalente?", 0, 'R');
//        $pdf->setFont('arial', '', 12);
//        $pdf->MultiCell(380, 20,$retornoWsLinha['InformacoesAdicionais']['EnvolveCargoComissionadoDAS4OuSuperior'], 0, 'C');
//        $pdf->Ln(20);

        // Manifestação Apta?
//        $pdf->SetFont('arial', 'B', 12);
//        $pdf->MultiCell(180, 20, "Manifestação Apta?", 0, 'R');
//        $pdf->setFont('arial', '', 12);
//        $pdf->MultiCell(380, 20, $retornoWsLinha['InformacoesAdicionais']['Apta'], 0, 'C');
//        $pdf->Ln(20);

        // Há envolvimento de Empresa?
//        $pdf->SetFont('arial', 'B', 12);
//        $pdf->MultiCell(180, 20, "Há envolvimento de Empresa?", 0, 'R');
//        $pdf->setFont('arial', '', 12);
//        $pdf->MultiCell(380, 20, $retornoWsLinha['InformacoesAdicionais']['EnvolveEmpresa'], 0, 'C');
//        $pdf->Ln(20);

        // Há envolvimento de Servidor Público?
//        $pdf->SetFont('arial', 'B', 12);
//        $pdf->MultiCell(180, 20, "Há envolvimento de Servidor Público?", 0, 'R');
//        $pdf->setFont('arial', '', 12);
//        $pdf->MultiCell(380, 20, $retornoWsLinha['InformacoesAdicionais']['EnvolveServidorPublico'], 0, 'J');
//        $pdf->Ln(20);

        /**
         * Respostas
         */
        $menu_count++;
        $pdf->Ln(30);
        $pdf->SetFont('arial', 'B', 14);
        $pdf->Cell(0, 20, $menu_count . ". Resposta(s)", true, 0, 'L');
        $pdf->Ln(30);

        $historicos = $retornoWsLinha['Historico'];
        if (count($historicos) > 0) {
            $i = 1;
            foreach ($historicos as $historico) {
                if ($historico['HistoricoAcao']['DescTipoAcaoManifestacao'] == 'Registro Resposta') {

                    $pdf->SetFont('arial', 'B', 12);
                    $pdf->Cell(0, 20, "Resposta " . $i, true, 1, 'L');

                    // Tipo de Resposta
                    $pdf->SetFont('arial', 'B', 12);
                    $pdf->Cell(180, 20, "Tipo de Resposta:", 0, 0, 'R');
                    $pdf->setFont('arial', '', 12);
                    $pdf->Cell(70, 20, $historico['Resposta']['TipoRespostaManifestacao']['DescTipoRespostaManifestacao'], 0, 1, 'L');

                    // Data e Hora
                    $pdf->SetFont('arial', 'B', 12);
                    $pdf->Cell(180, 20, "Data e hora:", 0, 0, 'R');
                    $pdf->setFont('arial', '', 12);
                    $pdf->Cell(70, 20, $historico['HistoricoAcao']['DataHoraAcao'], 0, 1, 'L');

                    // Decisão
                    $pdf->SetFont('arial', 'B', 12);
                    $pdf->Cell(180, 20, "Decisão:", 0, 0, 'R');
                    $pdf->setFont('arial', '', 12);
                    $pdf->Cell(70, 20, $historico['Resposta']['Decisao']['descricaoDecisao'], 0, 1, 'L');

                    // Data Compromisso
//                    $pdf->SetFont('arial', 'B', 12);
//                    $pdf->Cell(180, 20, "Compromisso:", 0, 0, 'R');
//                    $pdf->setFont('arial', '', 12);
//                    $pdf->Cell(70, 20, $historico['Resposta']['DataCompromisso'], 0, 1, 'L');

                    // Teor da Resposta
                    $pdf->SetFont('arial', 'B', 12);
                    $pdf->Cell(180, 20, "Teor da Resposta:", 0, 0, 'R');
                    $pdf->setFont('arial', '', 12);
                    $pdf->MultiCell(0, 20, $historico['Resposta']['TxtResposta'], 0, 'L');

                    $pdf->Ln(20);

                    $i++;
                }
            }
        }

        if (!count($historicos) > 0 || (isset($i) && $i == 1)) {
            // Sem respostas
            $pdf->SetFont('arial', 'B', 12);
            $pdf->Cell(180, 20, "Não há registro de respostas.", 0, 0, 'L');
            $pdf->Ln(20);
        }

        /**
         * Anexo das Respostas
         */
        $menu_count++;
        $pdf->Ln(30);
        $pdf->SetFont('arial', 'B', 14);
        $pdf->Cell(0, 20, $menu_count . ". Anexos das Respostas", true, 0, 'L');
        $pdf->Ln(30);

        $anexos = $retornoWsLinha['Teor']['Anexos'];
        if (count($anexos) > 0) {

            foreach ($anexos as $anexo) {

                if ($anexo['TipoAnexoManifestacao']['IdTipoAnexoManifestacao'] == 2) {

                    $possui_anexo_resposta = true;

                    // Nome do Arquivo
                    $pdf->SetFont('arial', 'B', 12);
                    $pdf->Cell(180, 20, "Nome do arquivo:", 0, 0, 'R');
                    $pdf->setFont('arial', '', 12);
                    $pdf->Cell(70, 20, $anexo['NomeArquivo'], 0, 1, 'L');

                    // Tipo de Anexo
                    $pdf->SetFont('arial', 'B', 12);
                    $pdf->Cell(180, 20, "Tipo de Anexo:", 0, 0, 'R');
                    $pdf->setFont('arial', '', 12);
                    $pdf->Cell(70, 20, $anexo['TipoAnexoManifestacao']['DescTipoAnexoManifestacao'], 0, 1, 'L');

                    // Anexo Complementar
                    $pdf->SetFont('arial', 'B', 12);
                    $pdf->Cell(180, 20, "Anexo Complementar:", 0, 0, 'R');
                    $pdf->setFont('arial', '', 12);
                    $pdf->Cell(70, 20, $anexo['IndComplementar'] ? 'sim' : 'não', 0, 1, 'L');

                    $pdf->Ln(20);
                }
            }
        }
        if (! isset($possui_anexo_resposta)) {
            // Sem anexo resposta
            $pdf->SetFont('arial', 'B', 12);
            $pdf->Cell(180, 20, "Não há anexos de respostas.", 0, 0, 'L');
            $pdf->Ln(20);
        }

        /**
         * Recursos
         *
         * Neste item importamos as seguintes opções de recursos:
         * - Pedido de Revisão
         * - Recurso de Primeira Instância
         * - Recurso de Segunda Instãncia
         */
        if ($retornoWsRecursos && $retornoWsRecursos <> '') {
            $menu_count++;
            $pdf->Ln(30);
            $pdf->SetFont('arial', 'B', 14);
            $pdf->Cell(0, 20, $menu_count . ". Recursos", true, 0, 'L');
            $pdf->Ln(30);

            $recursos = isset($retornoWsRecursos['Recursos']) ? $retornoWsRecursos['Recursos'] : [$retornoWsRecursos];

            if (count($recursos) > 0) {
                foreach ($recursos as $recurso) {

                    /**
                     * Somente gerará documento caso seja recursos 1ª ou 2ª instancia ou pedido de revisão,
                     * IdInstanciaRecurso = [1, 2, 6] conforme API FalaBR consultado dia 01/12/2020
                     * url: https://falabr.cgu.gov.br/Help
                     */
                    if (in_array($recurso['instancia']['IdInstanciaRecurso'], [1, 2, 6])) {
                        $pdf->SetFont('arial', 'B', 12);
                        $pdf->Cell(0, 20, "Dados do Recurso -  " . $recurso['instancia']['DescInstanciaRecurso'], true, 1, 'L');

                        // Destinatário
                        $pdf->SetFont('arial', 'B', 12);
                        $pdf->Cell(180, 20, "Destinatário:", 0, 0, 'R');
                        $pdf->setFont('arial', '', 12);
                        $pdf->Cell(70, 20, $retornoWsLinha['OuvidoriaDestino']['NomOuvidoria'], 0, 1, 'L');

                        // Data de Abertura
                        $pdf->SetFont('arial', 'B', 12);
                        $pdf->Cell(180, 20, "Data de abertura:", 0, 0, 'R');
                        $pdf->setFont('arial', '', 12);
                        $pdf->Cell(70, 20, $recurso['dataRecurso'], 0, 1, 'L');

                        // Prazo de Atendimento
                        $pdf->SetFont('arial', 'B', 12);
                        $pdf->Cell(180, 20, "Prazo de Atendimento:", 0, 0, 'R');
                        $pdf->setFont('arial', '', 12);
                        $pdf->Cell(70, 20, $recurso['prazoAtendimento'], 0, 1, 'L');

                        // Tipo Recurso
                        $pdf->SetFont('arial', 'B', 12);
                        $pdf->Cell(180, 20, "Tipo de Recurso:", 0, 0, 'R');
                        $pdf->setFont('arial', '', 12);
                        $pdf->Cell(70, 20, $recurso['tipoRecurso']['DescTipoRecurso'], 0, 1, 'L');

                        // Justificativa
                        $pdf->SetFont('arial', 'B', 12);
                        $pdf->Cell(180, 20, "Justificativa:", 0, 0, 'R');
                        $pdf->setFont('arial', '', 12);
                        $pdf->MultiCell(0, 20, $recurso['justificativa'], 0, 'J');

                        $pdf->Ln(20);
                    }
                }
            }
        }

        /**
         * Denúncia de Descumprimento
         */
        $menu_count++;
        $pdf->Ln(30);
        $pdf->SetFont('arial', 'B', 14);
        $pdf->Cell(0, 20, $menu_count . " Denúncia de Descumprimento", true, 0, 'L');
        $pdf->Ln(30);

        $denuncias = $retornoWsLinha['Historico'];
        if (count($denuncias) > 0) {
            foreach ($denuncias as $denuncia) {
                if ($denuncia['Denuncia']['TxtFato'] <> '') {

                    $possui_denuncia = true;

                    // Denúncia
                    $pdf->setFont('arial', '', 12);
                    $pdf->MultiCell(0, 20, $denuncia['HistoricoAcao']['Denuncia']['TxtFato'], 0, 'L');

                    $pdf->Ln(20);
                }
            }
        }
        if (!count($denuncias) > 0 || !isset($possui_denuncia)) {
            // Sem respostas
            $pdf->SetFont('arial', 'B', 12);
            $pdf->Cell(180, 20, "Não há registro de denúncias de descumprimento.", 0, 0, 'L');
            $pdf->Ln(20);
        }

        /**
         * Ecaminhamento
         */
        $menu_count++;
        $pdf->Ln(30);
        $pdf->SetFont('arial', 'B', 14);
        $pdf->Cell(0, 20, $menu_count . ". Dados de Encaminhamento", true, 0, 'L');
        $pdf->Ln(30);

        $encaminhamentos = $retornoWsLinha['Historico'];
        if (count($encaminhamentos) > 0) {
            foreach ($encaminhamentos as $encaminhamento) {
                if (isset($encaminhamento['Encaminhamento'])) {

                    $possui_denuncia = true;

                    // Órgão Origem
                    $pdf->SetFont('arial', 'B', 12);
                    $pdf->Cell(180, 20, "Órgão/Entidade de Origem:", 0, 0, 'R');
                    $pdf->setFont('arial', '', 12);
                    $pdf->Cell(70, 20, $encaminhamento['Encaminhamento']['OuvidoriaOrigem']['NomOuvidoria'], 0, 1, 'L');

                    // Órgão Destino
                    $pdf->SetFont('arial', 'B', 12);
                    $pdf->Cell(180, 20, "Órgão/Entidade Destinatária:", 0, 0, 'R');
                    $pdf->setFont('arial', '', 12);
                    $pdf->Cell(70, 20, $encaminhamento['Encaminhamento']['OuvidoriaDestino']['NomOuvidoria'], 0, 1, 'L');

                    // Mensagem ao Destinatário
                    $pdf->SetFont('arial', 'B', 12);
                    $pdf->Cell(180, 20, "Mensagem ao Destinatário:", 0, 0, 'R');
                    $pdf->setFont('arial', '', 12);
                    $pdf->Cell(70, 20, $encaminhamento['Encaminhamento']['TxtNotificacaoDestinatario'], 0, 1, 'L');

                    // Mensagem ao Cidadão
                    $pdf->SetFont('arial', 'B', 12);
                    $pdf->Cell(180, 20, "Mensagem ao Cidadão:", 0, 0, 'R');
                    $pdf->setFont('arial', '', 12);
                    $pdf->Cell(70, 20, $encaminhamento['Encaminhamento']['TxtNotificacaoSolicitante'], 0, 1, 'L');

                    $pdf->Ln(20);
                }
            }
        }
        if (!count($encaminhamentos) > 0 || !isset($possui_denuncia)) {
            // Sem respostas
            $pdf->SetFont('arial', 'B', 12);
            $pdf->Cell(180, 20, "Não há registro de encaminhamentos.", 0, 0, 'L');
            $pdf->Ln(20);
        }

        /**
         * Prorrogação
         */
        $menu_count++;
        $pdf->Ln(30);
        $pdf->SetFont('arial', 'B', 14);
        $pdf->Cell(0, 20, $menu_count . ". Dados de Prorrogação", true, 0, 'L');
        $pdf->Ln(30);

        $prorrogacoes = $retornoWsLinha['Historico'];
        if (count($prorrogacoes) > 0) {
            foreach ($prorrogacoes as $prorrogacao) {
                if ($prorrogacao['Prorrogacao'] <> '') {

                    $possui_prorrogacao = true;

                    // Prazo Original
                    $pdf->SetFont('arial', 'B', 12);
                    $pdf->Cell(180, 20, "Prazo Original:", 0, 0, 'R');
                    $pdf->setFont('arial', '', 12);
                    $pdf->Cell(70, 20, $prorrogacao['Prorrogacao']['PrazoOriginal'], 0, 1, 'L');

                    // Novo Prazo
                    $pdf->SetFont('arial', 'B', 12);
                    $pdf->Cell(180, 20, "Novo Prazo:", 0, 0, 'R');
                    $pdf->setFont('arial', '', 12);
                    $pdf->Cell(70, 20, $prorrogacao['Prorrogacao']['NovoPrazo'], 0, 1, 'L');

                    // Motivo da Prorrogação
                    $pdf->SetFont('arial', 'B', 12);
                    $pdf->Cell(180, 20, "Motivo:", 0, 0, 'R');
                    $pdf->setFont('arial', '', 12);
                    $pdf->Cell(70, 20, $prorrogacao['Prorrogacao']['MotivoProrrogacaoManifestacao']['DescMotivoProrrogacaoManifestacao'], 0, 1, 'L');

                    // Justificativa da Prorrogação
                    $pdf->SetFont('arial', 'B', 12);
                    $pdf->Cell(180, 20, "Justificativa:", 0, 0, 'R');
                    $pdf->setFont('arial', '', 12);
                    $pdf->Cell(70, 20, $prorrogacao['Prorrogacao']['TxtJustificativaProrrogacao'], 0, 1, 'L');

                    $pdf->Ln(20);
                }
            }
        }
        if (!count($prorrogacoes) > 0 || !isset($possui_prorrogacao)) {
            // Sem respostas
            $pdf->SetFont('arial', 'B', 12);
            $pdf->Cell(180, 20, "Não há registro de prorrogações.", 0, 0, 'L');
            $pdf->Ln(20);
        }

        /**
         * Observações finais
         */
        if($ocorreuErroAdicionarAnexo == true){
            $pdf->Ln(20);
            $pdf->SetFont('arial', 'B', 14);
            $pdf->Cell(70, 20, "12. Observações:", 0, 0, 'L');
            $pdf->Ln(20);

            $pdf->SetFont('arial', '', 12);
            $pdf->MultiCell(0, 20, "Um ou mais anexos da manifestação não foram importados para o SEI devido a restrições da extensão do arquivo. Acesse a manifestação através do link abaixo para mais detalhes. ", 0, 'J');
        }

        // e-Sic fim
        $pdf->Ln(30);
        $pdf->MultiCell(0, 1, '', 1, 'J', 1);
//        $pdf->Cell(0, 20, "FIM", true, 1, 'C');
//        $pdf->Ln(30);

        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(115, 20, "Link para manifestação no FalaBR:", 0, 1, 'L');
        $pdf->setFont('arial', '', 12);
        $pdf->Write(20, 'https://falabr.cgu.gov.br/', $retornoWsLinha['Links'][0]['href']);

        $objAnexoRN = new AnexoRN();
        $strNomeArquivoInicialUpload = $objAnexoRN->gerarNomeArquivoTemporario();

        $pdf->Output(DIR_SEI_TEMP . "/" . $strNomeArquivoInicialUpload . ".pdf", "F");

        /**
         * Helper para imprimir o pdf em tela
         */
//        header("Content-type: application/pdf");
//        header("Content-Disposition: inline; filename=filename.pdf");
//        @readfile(DIR_SEI_TEMP . '/' . $strNomeArquivoInicialUpload . '.pdf');
//        die();

        //Renomeuia tirando a extensï¿½o para o SEI trabalhar o Arquivo
        rename(DIR_SEI_TEMP . "/" . $strNomeArquivoInicialUpload . ".pdf", DIR_SEI_TEMP . "/" . $strNomeArquivoInicialUpload);

        $objDocumentoManifestacao = new DocumentoAPI();
        $objDocumentoManifestacao->setTipo('R');
        if ($IdProtocolo && $IdProtocolo <> '') {
            $objDocumentoManifestacao->setIdProcedimento($IdProtocolo);
        }
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
        $objDocumentoManifestacao->setIdSerie($idTipoDocumentoAnexoDadosManifestacao);
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
        global $idTipoDocumentoAnexoPadrao,
               $objProcedimentoDTO,
               $objTipoProcedimentoDTO,
               $arrObjAssuntoDTO,
               $arrObjParticipantesDTO,
               $idTipoDocumentoAnexoDadosManifestacao,
               $idUnidadeOuvidoria,
               $idUsuarioSei,
               $objWSAnexo,
               $dataRegistro,
               $strMensagemErroAnexos,
               $ocorreuErroAdicionarAnexo,
               $idRelatorioImportacao,
               $token;

        /**********************************************************************************************************************************************
         * Início da importação de anexos de cada protocolo
         * Desativado momentaneamente
         */

        /*$retornoWsAnexo = $objWSAnexo->GetAnexosManifestacao(array("login" => '11111111111',
            "senha" => 'abcd1234', "numeroProtocolo" => InfraUtil::retirarFormatacao($numProtocoloFormatado)))->GetAnexosManifestacaoResult;
        */

        $objInfraParametro = new InfraParametro(BancoSEI::getInstance());
        $arrAnexosAdicionados = array();

//        echo "<br><br>CHEGOU EM ANEXOS<BR><BR>";
//        var_dump($arrAnexosManifestacao);
//        echo "<br><br>";

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
                    $ext = strtoupper(pathinfo($strNomeArquivoOriginal, PATHINFO_EXTENSION));
                    $intIndexExtensao = array_search($ext, $arrExtensoesPermitidas);

                    if (is_numeric($intIndexExtensao)) {
                        $objAnexoRN = new AnexoRN();
                        $strNomeArquivoUpload = $objAnexoRN->gerarNomeArquivoTemporario();

                        $fp = fopen(DIR_SEI_TEMP . '/' . $strNomeArquivoUpload, 'w');

                        //Busca o conteúdo do Anexo
                        $arrDetalheAnexoManifestacao = $this->apiRestRequest($retornoWsAnexoLinha['Links'][0]['href'], $token, 3);

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
//                        $objAnexoManifestacao->setTipo('A');
                        $objAnexoManifestacao->setTipo('R');
                        $objAnexoManifestacao->setIdSerie($idTipoDocumentoAnexoDadosManifestacao);
                        $objAnexoManifestacao->setData(InfraData::getStrDataHoraAtual());
                        $objAnexoManifestacao->setNomeArquivo($strNomeArquivoOriginal);
                        $objAnexoManifestacao->setNumero($strNomeArquivoOriginal);
                        $objAnexoManifestacao->setConteudo(base64_encode(file_get_contents(DIR_SEI_TEMP . '/' . $strNomeArquivoUpload)));

                        if ($this->hashDuplicado(DIR_SEI_TEMP . '/' . $strNomeArquivoUpload, $numProtocoloFormatado)) {
//                            $this->gravarLogLinha($numProtocoloFormatado, $idRelatorioImportacao, 'Arquivo já anexado ao processo: ' . $strNomeArquivoOriginal, 'S', $tipoManifestacao);
                        } else {
                            if ($IdProtocolo && $IdProtocolo <> '') {
                                $objSEIRN = new SeiRN();
                                $objSEIRN->incluirDocumento($objAnexoManifestacao);
                            }

//                            $this->gravarLogLinha($numProtocoloFormatado, $idRelatorioImportacao, 'Arquivo adicionado como anexo: ' . $strNomeArquivoOriginal, 'S', $tipoManifestacao);
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
                $this->gravarLogLinha($numProtocoloFormatado, $idRelatorioImportacao, 'Um ou mais documentos anexos não foram importados corretamente: ' . $strMensagemErroAnexos, 'S', $tipoManifestacao);
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
            //PaginaSEI::getInstance()->setStrMensagem('ExclusÃ£o realizada com sucesso.');
            //$bolFlagProcessou = true;

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
        if (SessaoSEI::getInstance()->getNumIdUnidadeAtual() == null && SessaoSEI::getInstance()->getNumIdUsuario() == null) {

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
    public function verificaTipo($recursos = null)
    {
        $response = 'P';
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
        if ($recurso['instancia']['IdInstanciaRecurso'] == 3) {
            return 'RC'; // Recurso CGU
        }
        if ($recurso['instancia']['IdInstanciaRecurso'] == 2) {
            return 'R2'; // Recurso 2 instância
        }
        if ($recurso['instancia']['IdInstanciaRecurso'] == 1) {
            return 'R1'; // Recurso 1 instância
        }

        return null;
    }
}

?>
