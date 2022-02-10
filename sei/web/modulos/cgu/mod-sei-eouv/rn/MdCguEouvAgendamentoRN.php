<?php

/**
 * CONTROLADORIA GERAL DA UNI�O- CGU
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

    public function apiRestRequest($url, $token, $tipo)
    {
        /**
         * Debug do token antes da requisi��o
         */
//        var_dump($token);
//        die();

        $curl = curl_init();

        /**
         * @test - teste de op��es do Curl
         */
//        $ua = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US) AppleWebKit/525.13 (KHTML, like Gecko) Chrome/0.A.B.C Safari/525.13';

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
//            CURLOPT_SSL_VERIFYPEER => false, // @todo - COMENTAR ESSA LINHA!!!
//            CURLOPT_AUTOREFERER => true,
//            CURLOPT_USERAGENT => $ua,
//            CURLOPT_COOKIE => 'NID=67=pdjIQN5CUKVn0bRgAlqitBk7WHVivLsbLcr7QOWMn35Pq03N1WMy6kxYBPORtaQUPQrfMK4Yo0vVz8tH97ejX3q7P2lNuPjTOhwqaI2bXCgPGSDKkdFoiYIqXubR0cTJ48hIAaKQqiQi_lpoe6edhMglvOO9ynw; PREF=ID=52aa671013493765:U=0cfb5c96530d04e3:FF=0:LD=en:TM=1370266105:LM=1370341612:GM=1:S=Kcc6KUnZwWfy3cOl; OTZ=1800625_34_34__34_; S=talkgadget=38GaRzFbruDPtFjrghEtRw; SID=DQAAALoAAADHyIbtG3J_u2hwNi4N6UQWgXlwOAQL58VRB_0xQYbDiL2HA5zvefboor5YVmHc8Zt5lcA0LCd2Riv4WsW53ZbNCv8Qu_THhIvtRgdEZfgk26LrKmObye1wU62jESQoNdbapFAfEH_IGHSIA0ZKsZrHiWLGVpujKyUvHHGsZc_XZm4Z4tb2bbYWWYAv02mw2njnf4jiKP2QTxnlnKFK77UvWn4FFcahe-XTk8Jlqblu66AlkTGMZpU0BDlYMValdnU; HSID=A6VT_ZJ0ZSm8NTdFf; SSID=A9_PWUXbZLazoEskE; APISID=RSS_BK5QSEmzBxlS/ApSt2fMy1g36vrYvk; SAPISID=ZIMOP9lJ_E8SLdkL/A32W20hPpwgd5Kg1J',
//            CURLOPT_FOLLOWLOCATION => true,
//            CURLOPT_HEADER => true,
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

        /**
         * Debug API Request
         */
//        echo "<hr>";
//        echo "<br>token: " . $token;
//        echo "<hr>";
//        echo "<br>url: " . $url;
//        echo "<hr>";
//        var_dump($response);
//        echo "<hr>";
//        var_dump($httpcode);
//        echo "<hr><hr>";
//        die();

        switch ($httpcode) {
            case 200:
                $response = json_decode($response, true);
                $response = $this->decode_result($response);
                break;
            case 401:
                $response = 'Token Invalidado. HTTP Status: ' . $httpcode;
                break;
            case 404: // Nenhum retorno encontrado...
                $response = 'Nenhum retorno encontrado! HTTP Status: ' . $httpcode;
                break;
            default:
                $response = "Erro: Ocorreu algum erro n�o tratado. HTTP Status: " . $httpcode;
                break;
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
//            CURLOPT_HTTPHEADER => array(
//                "Content-Type: application/x-www-form-urlencoded",
//                "Postman-Token: 65f1b627-4926-49ed-8109-8586ffc4ec53",
//                "cache-control: no-cache"
//            ),
        ));

        $response = curl_exec($curl);

        $err = curl_error($curl);

        curl_close($curl);

        $response = json_decode($response, true);

//        echo "<hr><hr>>";
//        echo "<br><br>username:" . $username;
//        echo "<br><br>password:" . $password;
//        echo "<br><br>cliend_id:" . $client_id;
//        echo "$<br><br>client_secret:" . $client_secret;
//        echo "<br><br>url:" . $url;
//        echo "<hr><hr>>";
//        echo "<br><br>token:" . $response;
//        echo "<hr><hr>>";
//        var_dump($response);
//        echo "<hr><hr>";
//        die();

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
        /**
         * Debug do token antes da requisi��o
         */
//        var_dump($token);
//        die();

        $arrParametrosUrl = array(
            'dataCadastroInicio' => $ultimaDataExecucao,
            'dataCadastroFim' => $dataAtual,
            'numprotocolo' => $numprotocolo
        );

        $arrParametrosUrl = http_build_query($arrParametrosUrl);

        $urlConsultaManifestacao = $urlConsultaManifestacao . "?" . $arrParametrosUrl;

        $retornoWs = $this->apiRestRequest($urlConsultaManifestacao, $token, 1);

        if (is_null($numprotocolo)) {
            // Verifica se retornou Token Invalido
            if (is_string($retornoWs)) {
                // Token expirado, necess�rio gerar novo Token
                if (strpos($retornoWs, 'Invalidado') !== false) {
                    return "Token Invalidado";
                }
                // Outro erro
                if (strpos($retornoWs, 'Erro') !== false) {
                    return "Erro:" . $retornoWs;
                }
            }
        } else {
            // Faz tratamento diferenciado para consulta por Protocolo espec�fico
            if(is_string($retornoWs)) {
                if (strpos($retornoWs, '404') !== false) {
//                        $this->gravarLogLinha($this->formatarProcesso($numprotocolo), $numIdRelatorio, "Usu�rio n�o possui permiss�o de acesso neste protocolo.", 'N');
                    $this->gravarLogLinha($this->formatarProcesso($numprotocolo), $numIdRelatorio, 'Nenhum retorno encontrado!', 'S');
                    $retornoWs = null;
                } elseif (strpos($retornoWs, 'Erro') !== false) {
//                    var_dump($retornoWs);die();
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

        $retornoWs = $this->apiRestRequest($urlConsultaRecurso, $token, 1);

        if (is_null($numprotocolo)) {
            //Verifica se retornou Token Invalido
            if (is_string($retornoWs)) {
                if (strpos($retornoWs, 'Invalidado') !== false) {
                    //Token expirado, necessário gerar novo Token
                    return "Token Invalidado";
                }

                //Outro erro
                if (strpos($retornoWs, 'Erro') !== false) {
                    //Token expirado, necessário gerar novo Token
                    return "Erro:" . $retornoWs;
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
        fun��o criada para tratar o retorno de dados do WS, pois quando existe apenas um unico resultado retorna um objeto e
        quando tem mais de um resultado retorna um array ocasionando falhas na exibi��o dos dados.
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
            $objEouvRelatorioImportacaoRN->cadastrar($objEouvRelatorioImportacaoDTO);

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
//        $objEouvRelatorioImportacaoDetalheDTO->retStrTipManifestacao();
        $objEouvRelatorioImportacaoDetalheDTO->setNumIdRelatorioImportacao($idRelatorioImportacao);
        $objEouvRelatorioImportacaoDetalheDTO->setStrProtocoloFormatado($numProtocolo);
//        $objEouvRelatorioImportacaoDetalheDTO->setStrTipManifestacao($tipoManifestacao);

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
            throw new InfraException('Unidade ['.$IdUnidade.'] n�o encontrada.');
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
     * Fun��o para importar as manifesta��es e-Ouv do FalaBR
     *
     * Tipos: 1, 2, 3, 4, 5, 6 e 7
     */
    public function executarImportacaoManifestacaoEOuv()
    {
        // Debug
//        InfraDebug::getInstance()->setBolLigado(true);
//        InfraDebug::getInstance()->setBolDebugInfra(true);
//        InfraDebug::getInstance()->setBolEcho(true);
//        InfraDebug::getInstance()->limpar();

        // Log
        LogSEI::getInstance()->gravar('Rotina de Importa��o de Manifesta��es do E-Ouv', InfraLog::$INFORMACAO);

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

        // Busca par�metros do banco de dados
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

        /**
         * Fun��o para buscar o 'restante' do token sem o limite de 255 caracteres do SEI
         */
        $tokenPart2 = BancoSEI::getInstance()->consultarSql('select substring(de_valor_parametro, 256, 455) from md_eouv_parametros where id_parametro=10;')[0]['computed'];
        $token = $token . $tokenPart2;

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

            //Retorna dados da �ltima execu��o com Sucesso
            $objUltimaExecucao = MdCguEouvAgendamentoINT::retornarUltimaExecucaoSucesso();

            if ($objUltimaExecucao != null) {
                $ultimaDataExecucao = $objUltimaExecucao->getDthDthPeriodoFinal();
                $idUltimaExecucao = $objUltimaExecucao->getNumIdRelatorioImportacao();
            } //Primeira execu��o ou nenhuma executada com sucesso
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
//            $retornoWs = [["Links"=>[["rel"=>"self","href"=>"https://sistema.ouvidorias.gov.br/api/manifestacoes/2670732"]],"IndPossuiIdentidadePreservada"=>false,"IdManifestacao"=>2670732,"NumerosProtocolo"=>["23546059531202007"],"OuvidoriaDestino"=>["IdOuvidoria"=>65,"IdOrgaoSiorg"=>100911,"NomeOuvidoria"=>"IFCE � Instituto Federal de Educa��o, Ci�ncia e Tecnologia do Cear�"],"Assunto"=>["IdAssunto"=>57,"DescAssunto"=>"Defesa do Consumidor"],"Servico"=>null,"TipoFormulario"=>["IdTipoFormulario"=>1,"DescTipoFormulario"=>"Padr�o"],"TipoManifestacao"=>["IdTipoManifestacao"=>2,"DescTipoManifestacao"=>"Reclama��o"],"EmailManifestante"=>"luanaalbuquerquev@gmail.com","DataCadastro"=>"24/11/2020","PrazoAtendimento"=>null,"Situacao"=>["IdSituacaoManifestacao"=>5,"DescSituacaoManifestacao"=>"Complementa��o Solicitada"],"ResponsavelAnalise"=>"T�rcio Victor de Oliveira Leal"]];

            //Caso retornado algum erro
            if (is_string($retornoWs)) {
                if (strpos($retornoWs, 'Invalidado') !== false) {
                    //Tenta gerar novo token
                    $tokenValido = $this->apiValidarToken($urlWebServiceEOuv, $usuarioWebService, $senhaUsuarioWebService, $client_id, $client_secret);

                    if (isset($tokenValido['error'])) {
                        $textoMensagemErroToken = 'N�o foi poss�vel validar o Token de acesso aos WebServices do E-ouv. <br>Verifique as informa��es de Usu�rio, Senha, Client_Id e Client_Secret nas configura��es de Par�metros do M�dulo';

                    } elseif (isset($tokenValido['access_token'])) {
                        $this->gravarParametroToken($tokenValido['access_token']);
                        $token = $tokenValido['access_token'];

                        //Chama novamente a execu��o da ConsultaManifestacao que deu errado por causa do Token
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

                $textoMensagemFinal = 'Execu��o Finalizada com Sucesso!';
                $SinSucessoExecucao = 'S';

                if ($semManifestacoesEncontradas) {
                    $textoMensagemFinal = $textoMensagemFinal . ' N�o foram encontradas manifesta��es para o per�odo.';
                } else {
                    $textoMensagemFinal = $textoMensagemFinal . '<br>Quantidade de Manifesta��es novas encontradas (e-Ouv|e-Sic): ' . $qtdManifestacoesNovas . '<br>Quantidade de Manifesta��es encontadas que ocorreram erro em outras importa��es: ' . $qtdManifestacoesAntigas;
                }

                if ($ocorreuErroEmProtocolo) {
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

            die;
        }
    }

    /**
     * Fun��o para importar as manifesta��es e-Sic do FalaBR (tipo 8)
     */
    public function executarImportacaoManifestacaoESic()
    {

        // Debug
//        InfraDebug::getInstance()->setBolLigado(true);
//        InfraDebug::getInstance()->setBolDebugInfra(true);
//        InfraDebug::getInstance()->setBolEcho(true);
//        InfraDebug::getInstance()->limpar();

        $debugLocal = false;

        // Log
        LogSEI::getInstance()->gravar('Rotina de Importa��o de Manifesta��es do FalaBR (e-Sic)', InfraLog::$INFORMACAO);

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

        // Lista par�metros
        $objEouvParametroDTO = new MdCguEouvParametroDTO();
        $objEouvParametroDTO->retTodos();

        // Busca par�metros do banco de dados da tabela md_eouv_parametros
        $objEouvParametroRN = new MdCguEouvParametroRN();
        $arrObjEouvParametroDTO = $objEouvParametroRN->listarParametro($objEouvParametroDTO);
        $numRegistros = count($arrObjEouvParametroDTO);

        // Preenche vari�veis locais com dados da tabela md_eouv_parametros
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
                        /**
                         * @debug token
                         */
//                        var_dump($arrObjEouvParametroDTO[$i]);
//                        var_dump($token);
//                        die();
                        break;

                    case "IMPORTAR_DADOS_MANIFESTANTE":
                        $importar_dados_manifestante = $arrObjEouvParametroDTO[$i]->getStrDeValorParametro();
                        break;
                }
            }
        }

        /**
         * Fun��o para buscar o 'restante' do token sem o limite de 255 caracteres do SEI
         */
        $tokenPart2 = BancoSEI::getInstance()->consultarSql('select substring(de_valor_parametro, 256, 455) from md_eouv_parametros where no_parametro="TOKEN";')[0]['computed'];
        $token = $token . $tokenPart2;

        // Debugar Token
//        var_dump($token);
//        die();

        // Busca par�metros do banco de dados da tabela infra_parametros
        $objInfraParametro = new InfraParametro(BancoSEI::getInstance());
        $idUsuarioSei = $objInfraParametro->getValor('ID_USUARIO_SEI');
        $dataAtual = InfraData::getStrDataHoraAtual();
        $SiglaSistema = 'EOUV';
        $IdentificacaoServico = 'CadastrarManifestacao';

        // Simula login inicial
        $this->simulaLogin($SiglaSistema, $IdentificacaoServico, $idUnidadeEsicPrincipal);

        // Executa a importa��o dos dados
        try {

            //Retorna dados da �ltima execu��o com Sucesso
            $objUltimaExecucao = MdCguEouvAgendamentoINT::retornarUltimaExecucaoSucesso('R');

            if ($objUltimaExecucao != null) {
                // Debug Logs
                $debugLocal && LogSEI::getInstance()->gravar('$objUltimaExecuxao (e-Sic):' . $objUltimaExecucao->getDthDthPeriodoFinal());

                $ultimaDataExecucao = $objUltimaExecucao->getDthDthPeriodoFinal();
                $idUltimaExecucao = $objUltimaExecucao->getNumIdRelatorioImportacao();
            } else {
                // Debug Logs
                $debugLocal && LogSEI::getInstance()->gravar('$objUltimaExecuxao (e-Sic) � NULL');

                //Primeira execu��o ou nenhuma executada com sucesso
                $ultimaDataExecucao = $dataInicialImportacaoManifestacoes;
            }

            /**
             * @debug data
             * Para auxiliar no debug pode-se definir uma data espec�fica para um determinado processo
             */
//            var_dump($ultimaDataExecucao);
//            $ultimaDataExecucao = '03/01/2022 00:00:00';
//            $dataAtual = '03/01/2022 23:59:59';
//            $ultimaDataExecucao = '06/01/2022 13:20:02';
//            $dataAtual = '06/01/2022 13:30:02';
//            var_dump('chegou aqui...');
//            die();

            $semManifestacoesEncontradas = true;
            $qtdManifestacoesNovas = 0;
            $qtdManifestacoesAntigas = 0;
            $semRecursosEncontrados = true;
            $qtdRecursosNovos = 0;
            $qtdRecursosAntigos = 0;

            /**
             * A fun��o abaixo gravarLogImportacao recebe o tipo de manifesta��o 'R' (Recursos) para as manifesta��es do e-Sic
             */

            /**
             * Debug
             */
//            var_dump($ultimaDataExecucao);
//            var_dump($dataAtual);
//            var_dump('<hr>');
//            die();

            $objEouvRelatorioImportacaoDTO = $this->gravarLogImportacao($ultimaDataExecucao, $dataAtual, 'R');
            $idRelatorioImportacao = $objEouvRelatorioImportacaoDTO->getNumIdRelatorioImportacao();
            $objEouvRelatorioImportacaoRN = new MdCguEouvRelatorioImportacaoRN();
            $SinSucessoExecucao = 'N';
            $textoMensagemErroToken = '';

            /**
             * Debug do token antes da requisi��o
             */
//            var_dump($token);
//            var_dump('<hr>');
//            die();

            /**
             * As fun��es abaixo fazem a busca no webservice dos dados a serem trabalhados na rotina de importa��o
             */
            $debugLocal && LogSEI::getInstance()->gravar('Iniciando a consulta inicial');
            $retornoWs = $this->executarServicoConsultaManifestacoes($urlWebServiceEOuv, $token, $ultimaDataExecucao, $dataAtual, null, $idRelatorioImportacao);
            $retornoWsRecursos = $this->executarServicoConsultaRecursos($urlWebServiceESicRecursos, $token, $ultimaDataExecucao, $dataAtual, null, $idRelatorioImportacao);

            /**
             * @debug retorno 1
             * Caso esteja debugando, e queira inserir o retorno direto, pode-se fazer aqui ou at� mesmo descomentar a
             * abaixo para deixar vazio acelerando os testes das fun��es subsequ�ntes.
             */
//            $retornoWs = [];
//            $retornoWsRecursos = [];
//            $retornoWs = "Token Invalidado";
//            var_dump('<hr>');
//            var_dump('$retornoWs');
//            var_dump('<hr>');
//            var_dump($retornoWs);
//            var_dump('<hr>');
//            var_dump('$retornoWsRecursos');
//            var_dump('<hr>');
//            var_dump($retornoWsRecursos);
//            die();
//            $token = '52ZftR2JOyqJVAcwizPCLIpkX73Tx9yuEONfCFKaVogtxOgwgr73DJyd-nV3Ljb8g1mGN0y7Nzi8hIqgZZF1o3KM5h23aBMVYQjjbZJsJy2Pmu20flbcLkdkYDuGe44ZAFf330ljI4lcJmg1JoiC_me68h9qd-1-OOdFNRSITvgLuHtKBXiFOsDFGumEatFgbniJp1skjDpTBzvMpxh33yiw7cUS-6uS7ifCUmGN2ljyxQFjESvbxEcSB3LyLOcRZn2a2A_saiokC2T7tyJMyzuz8f4W1H2kSY8sIpPPWwG-Nv0b-eBWL9bmYerz0yK1t9gXtaGW9oi1LfbJFqauEJic6mZ_CEK9OHJRXtAnhrmZNc0AZZOGbWwBrTA2q10h6SXZa0viS-PJJjwXVw8qvwmL20K6oSr9T-H5levQOjES-Hfx';
//            $retornoWs = [[
//                "Links"=>[
//                    [
//                        "rel"=>"self",
//                        "href"=>"https://treinafalabr.cgu.gov.br/api/manifestacoes/47680"
//                    ]
//                ],
//                "IdManifestacao"=>47680,
//                "NumerosProtocolo"=>["00106000346202162"],
//                "OuvidoriaDestino"=>[
//                    "IdOuvidoria"=>6,
//                    "IdOrgaoSiorg"=>214460,
//                    "NomOuvidoria"=>"CGU � Controladoria-Geral da Uni�o"
//
//                ],
//                "Assunto"=>[
//                    "IdAssunto"=>377,
//                    "DescAssunto"=>"Acesso � informa��o"
//                ],
//                "Servico"=>null,
//                "TipoFormulario"=>[
//                    "IdTipoFormulario"=>3,
//                    "DescTipoFormulario"=>"Acesso � Informa��o"
//                ],
//                "TipoManifestacao"=>[
//                    "IdTipoManifestacao"=>8,
//                    "DescTipoManifestacao"=>"Acesso � Informa��o"
//                ],
//                "EmailManifestante"=>"daianecalado@yahoo.com.br",
//                "DataCadastro"=>"14/10/2021",
//                "PrazoAtendimento"=>"03/11/2021",
//                "Situacao"=>[
//                    "IdSituacaoManifestacao"=>1,
//                    "DescSituacaoManifestacao"=>"Cadastrada"
//                ],
//                "ResponsavelAnalise"=>"",
//                "IndPossuiIdentidadePreservada"=>false,
//                "SubAssunto"=>null,
//                "Tags"=>[],
//                "Tag"=>null,
//                "LocalFato"=>[
//                    "Municipio"=>null,
//                    "DescricaoLocalFato"=>"",
//                    "GeoReferencia"=>null
//                ]
//            ]];

            //Caso retornado algum erro - Manifesta��es e-Sic
            if (is_string($retornoWs)) {
                $debugLocal && LogSEI::getInstance()->gravar('Retorno da consulta $retornoWs � uma string: ' . $retornoWs);
                // Debug de retorno com erro
//                var_dump('$retornoWS � uma string');
//                var_dump($retornoWs);
//                die();

                if (strpos($retornoWs, 'Invalidado') !== false) {
                    //Tenta gerar novo token
                    $tokenValido = $this->apiValidarToken($urlWebServiceEOuv, $usuarioWebService, $senhaUsuarioWebService, $client_id, $client_secret);

                    if (isset($tokenValido['error'])) {
                        $textoMensagemErroToken = 'N�o foi poss�vel validar o Token de acesso aos WebServices do E-ouv. <br>Verifique as informa��es de Usu�rio, Senha, Client_Id e Client_Secret nas configura��es de Par�metros do M�dulo';

                    } elseif (isset($tokenValido['access_token'])) {
                        $this->gravarParametroToken($tokenValido['access_token']);
                        $token = $tokenValido['access_token'];

                        /**
                         * @debug token valido
                         */
//                        var_dump($tokenVal|ido);
//                        var_dump($token);
//                        die();

                        //Chama novamente a execu��o da ConsultaManifestacao que deu errado por causa do Token
                        $retornoWs = $this->executarServicoConsultaManifestacoes($urlWebServiceEOuv, $token, $ultimaDataExecucao, $dataAtual, null, $idRelatorioImportacao);
                        $retornoWsRecursos = $this->executarServicoConsultaRecursos($urlWebServiceESicRecursos, $token, $ultimaDataExecucao, $dataAtual, null, $idRelatorioImportacao);
                    }
                }
            }

            /**
             * @debug retorno 2
             * Caso esteja debugando, e queira inserir o retorno direto, pode-se fazer aqui ou at� mesmo descomentar a
             * abaixo para deixar vazio acelerando os testes das fun��es subsequ�ntes.
             */
//            $retornoWs = [];
//            $retornoWsRecursos = [];
//            var_dump($retornoWs[0]['Links']);
//            var_dump($retornoWsRecursos);
//            die();

            /**
             * @todo - criar rotina para buscar recursos das manifest��es com erro caso exista alguma na tabela de log
             */

            if ($textoMensagemErroToken == '') {

                /**
                 * @debug - manifestacao com erro
                 * Comentar a linha abaixo para debugar um retorno manual
                 */
                $debugLocal && LogSEI::getInstance()->gravar('Inicia busca manifesta��o com erros');
                $arrComErro = $this->obterManifestacoesComErro($urlWebServiceEOuv, $token, $ultimaDataExecucao, $dataAtual, $idRelatorioImportacao, 'R');

                $arrManifestacoes = array();

                if (is_array($retornoWs)) {
                    $debugLocal && LogSEI::getInstance()->gravar('Possui retornoWS qtd: ' . count($retornoWs));
                    $qtdManifestacoesNovas = count($retornoWs);
                    $arrManifestacoes = $retornoWs;
//                    var_dump('<hr>');
//                    var_dump('Qtd retornoWS: <br />');
//                    var_dump(count($retornoWs));
//                    var_dump('<hr>');
                }

                if (isset($retornoWsRecursos) && is_array($retornoWsRecursos)) {
                    $debugLocal && LogSEI::getInstance()->gravar('Possui recursos qtd: ' . count($retornoWsRecursos['Recursos']));
                    $arrRecursos = $retornoWsRecursos['Recursos'];
                    $qtdRecursosNovos = count($arrRecursos);
//                    var_dump('<hr>');
//                    var_dump('Qtd retornoWsRecursos: <br />');
//                    var_dump(count($arrRecursos));
//                    var_dump('<hr>');
                }

                if (is_array($arrComErro)) {
                    $debugLocal && LogSEI::getInstance()->gravar('Possui manifesta��es com erros - qtd: ' . count($arrComErro));
                    $qtdManifestacoesAntigas = count($arrComErro);
                    $arrManifestacoes = array_merge($arrManifestacoes, $arrComErro);
//                    var_dump('<hr>');
//                    var_dump('Qtd arrComErro: <br />');
//                    var_dump(count($arrComErro));
//                    var_dump('<hr>');
                }

                // Importa manifesta��es e-Sic
                if (count($arrManifestacoes) > 0) {
                    $semManifestacoesEncontradas = false;
                    foreach ($arrManifestacoes as $retornoWsLinha) {
                        if ($retornoWsLinha['TipoManifestacao']['IdTipoManifestacao'] == 8) {

                            /**
                             * Para fazer debug de uma Manifesta��o por ID (IdManifestacao)
                             * descomente o if abaixo e coloque o n�mero do IdManifestacao
                             * para somente fazer o loop de importa��o se for passar no if!
                             */
//                            var_dump('<hr>');
//                            var_dump('Manifesta��o: <br />');
//                            var_dump($retornoWsLinha['NumerosProtocolo']);
//                            var_dump('<hr>');
//                            continue;
//                            var_dump($retornoWsLinha['IdManifestacao']);
//                            var_dump('<hr>');
//                            die();
//                            if ($retornoWsLinha['IdManifestacao'] <> '38796') {
//                                $debugLocal && LogSEI::getInstance()->gravar('Pulou a idManifesta��o: ' . $retornoWsLinha['IdManifestacao']);
//                                continue;
//                            }
//                            if ($retornoWsLinha['NumerosProtocolo'][0] <> '00106000003202289') {
//                                $debugLocal && LogSEI::getInstance()->gravar('Pulou a idManifesta��o: ' . $retornoWsLinha['IdManifestacao']);
//                                continue;
//                            }
//                            var_dump($retornoWsLinha['NumerosProtocolo']);
//                            var_dump('passou pra importar...');
//                            die();

                            $debugLocal && LogSEI::getInstance()->gravar('Inicia importa��o por Linha');
                            $this->executarImportacaoLinha($retornoWsLinha, 'R');
                        }
                    }
                }
//die();
                // Importa recursos e-Sic
                if (count($arrRecursos) > 0) {
                    $semRecursosEncontrados = false;
                    foreach ($arrRecursos as $retornoWsLinha) {

                        /**
                         * Debug importa��o de recursos
                         */
//                        var_dump('<hr>');
//                        var_dump('Recurso: <br />');
//                        var_dump($retornoWsLinha['numProtocolo']);
//                        var_dump('<hr>');
//                        continue;
//                        if ($retornoWsLinha['numProtocolo'] <> '00106.000003/2022-89') {
//                            $debugLocal && LogSEI::getInstance()->gravar('Pulou o recurso do protocolo: ' . $retornoWsLinha['numProtocolo']);
//                            continue;
//                        }
//                        var_dump('<hr>');
//                        var_dump($retornoWsLinha);
//                        var_dump('<hr>');
//                        die();
                        $debugLocal && LogSEI::getInstance()->gravar('Inicia importa��o por linha de Recursos - protocolo: ' . $retornoWsLinha['numProtocolo']);
                        $this->executarImportacaoLinhaRecursos($retornoWsLinha);
                    }
                }

                /**
                 * Debug
                 */
//                var_dump('<hr>');
//                var_dump('fim...');
//                die();

                $textoMensagemFinal = 'Execu��o Finalizada com Sucesso!';
                $SinSucessoExecucao = 'S';

                if ($semManifestacoesEncontradas) {
                    $textoMensagemFinal = $textoMensagemFinal . ' N�o foram encontradas manifesta��es para o per�odo.';
                } else {
                    $textoMensagemFinal = $textoMensagemFinal . '<br>Quantidade de Manifesta��es novas encontradas (e-Ouv|e-Sic): ' . $qtdManifestacoesNovas . '<br>Quantidade de Manifesta��es encontadas que ocorreram erro em outras importa��es: ' . $qtdManifestacoesAntigas;
                }

                if ($semRecursosEncontrados) {
                    $textoMensagemFinal = $textoMensagemFinal . ' N�o foram encontrados recursos para o per�odo.';
                } else {
//                    $textoMensagemFinal = $textoMensagemFinal . '<br>Quantidade de Recursos novos encontrados (e-Sic): ' . $qtdRecursosNovos . '<br>Quantidade de Recursos encontados que ocorreram erro em outras importa��es: ' . $qtdRecursosAntigos;
                    $textoMensagemFinal = $textoMensagemFinal . '<br>Quantidade de Recursos novos encontrados (e-Sic): ' . $qtdRecursosNovos;
                }

                if ($ocorreuErroEmProtocolo) {
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

        $debugLocal = false;

        $objProcedimentoDTO = new ProcedimentoDTO();
        $objProtocoloDTO = new ProtocoloDTO();
        $objProcedimentoRN = new ProcedimentoRN();
        $objProcedimentoDTO->setDblIdProcedimento(null);

        $linkDetalheManifestacao = $retornoWsLinha['Links'][0]['href'];
        $arrDetalheManifestacao = $this->apiRestRequest($linkDetalheManifestacao, $token, 2);

        /**
         * Verifica Tipo de Manifesta��o e-Ouv ou e-Sic
         */
        if ($tipoManifestacao == 'P' && $retornoWsLinha['TipoManifestacao']['IdTipoManifestacao'] <> 8) {
            $debugLocal && LogSEI::getInstance()->gravar('Importa��o tipo "P" - tipoManifesta��o <> "8"');
            $manifestacaoESic = false;
            $idUnidadeDestino = $idUnidadeOuvidoria;
        } elseif ($tipoManifestacao == 'R' && $retornoWsLinha['TipoManifestacao']['IdTipoManifestacao'] == 8) {
            $debugLocal && LogSEI::getInstance()->gravar('Importa��o tipo "R" - tipoManifesta��o == "8"');
            $manifestacaoESic = true;
            $idUnidadeDestino = $idUnidadeEsicPrincipal;

            /**
             * Importar Recursos caso seja manifesta��o e-Sic (Tipo 8)
             */
            $arrRecursosManifestacao = $this->apiRestRequest($urlWebServiceESicRecursos . '?NumProtocolo=' . $arrDetalheManifestacao['NumerosProtocolo'][0], $token, 2);
        }

        $dataRegistro = $arrDetalheManifestacao['DataCadastro'];
        $numProtocoloFormatado =  $this->formatarProcesso($arrDetalheManifestacao['NumerosProtocolo'][0]);


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
            $this->gravarLogLinha($numProtocoloFormatado, $idRelatorioImportacao, 'Tipo de processo n�o foi informado.', 'N');
            /**
             * @todo - n�o deveria parara aqui? se n�o tiver um tipo de processo n�o informado?
             */
        } else {
            $objEouvDeparaImportacaoDTO = new MdCguEouvDeparaImportacaoDTO();
            $objEouvDeparaImportacaoDTO->retNumIdTipoProcedimento();
            $objEouvDeparaImportacaoDTO->setNumIdTipoManifestacaoEouv($arrDetalheManifestacao['TipoManifestacao']['IdTipoManifestacao']);

            $objEouvDeparaImportacaoRN = new MdCguEouvDeparaImportacaoRN();
            $objEouvDeparaImportacaoDTO = $objEouvDeparaImportacaoRN->consultarRN0186($objEouvDeparaImportacaoDTO);

            if (!$objEouvDeparaImportacaoDTO == null) {
                $idTipoManifestacaoSei = $objEouvDeparaImportacaoDTO->getNumIdTipoProcedimento();
            } else {
                $this->gravarLogLinha($numProtocoloFormatado, $objEouvRelatorioImportacaoDTO->getNumIdRelatorioImportacao(), 'N�o existe mapeamento DePara do Tipo de Manifesta��o do FalaBR (E-Ouv|E-Sic) para o tipo de procedimento do SEI.', 'N');
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

        /**
         * Debug
         */
//        if ($this->formatarProcesso($numProtocoloFormatado) <> '00106.000363/2021-08') {
//            return;
//        }
//        var_dump($this->formatarProcesso($numProtocoloFormatado));
//        var_dump('<hr>');
//        var_dump($objProtocoloDTOExistente);
//        var_dump('<hr>');
//        var_dump($tipoManifestacao);
//        var_dump('<hr>');
//        die();

        // 1. Caso j� exista um Protocolo no SEI com o mesmo NUP
        if (! is_null($objProtocoloDTOExistente)) {
            // 2. Se existir e for e-Ouv
            if ($tipoManifestacao == 'P') {
                $debugLocal && LogSEI::getInstance()->gravar('Importando Linha Manifesta��o e-ouv - protocolo: ' . $this->formatarProcesso($numProtocoloFormatado));
                // 2.1 Importar anexos novos se existirem... e retornar log
                // @todo - melhoria pr�xima vers�o e-Ouv
                $this->gravarLogLinha($numProtocoloFormatado, $idRelatorioImportacao, 'Erro na grava��o: ' . 'J� existe um processo (e-Ouv) utilizando o n�mero de protocolo.', 'N', $tipoManifestacao);
            }

            // 3. Se existir e for e-Sic
            if ($tipoManifestacao == 'R') {

                $debugLocal && LogSEI::getInstance()->gravar('Importando Linha Manifesta��o e-SIC - protocolo: ' . $this->formatarProcesso($numProtocoloFormatado));

                /**
                 * @todo - @teste
                 * Teste aqui pra validar se o prazo sendo 'maior' na peti��o inicial j� n�o deve importar os recursos..... (??)
                 */
                // Data do �ltimo prazo de atendimento para este protocolo
//                $objUltimaDataPrazoAtendimento = MdCguEouvAgendamentoINT::retornarUltimaDataPrazoAtendimento($numProtocoloFormatado, $tipoManifestacao);
                $objUltimaDataPrazoAtendimento = MdCguEouvAgendamentoINT::retornarUltimaDataPrazoAtendimento($numProtocoloFormatado);

                /**
                 * Debug
                 */
//                var_dump('come�a aqui o debug das datas:');
//                var_dump('<hr>');
//                var_dump('$objUltimaDataPrazoAtendimento: ' . $objUltimaDataPrazoAtendimento);
//                var_dump('<hr>');
//                var_dump('$objUltimaDataPrazoAtendimento->getDthDthPrazoAtendimento(): ' . $objUltimaDataPrazoAtendimento->getDthDthPrazoAtendimento());
//                var_dump('<hr>');
//                var_dump('$dataPrazoAtendimento: ' . $dataPrazoAtendimento);
//                var_dump('<hr>');
//                var_dump('data � diferente? :' . isset($objUltimaDataPrazoAtendimento) && $objUltimaDataPrazoAtendimento->getDthDthPrazoAtendimento() <> $dataPrazoAtendimento);
//                var_dump('<hr>');
//                die();

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
                            // Se for 1 inst�ncia envia processo para ESIC_ID_UNIDADE_RECURSO_PRIMEIRA_INSTANCIA
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
                                LogSEI::getInstance()->gravar('M�dulo Integra��o FalaBR - (Recurso tipo ' . $tipo_recurso . ') Processo ' . $numProtocoloFormatado . ' enviado para unidade ' . $idUnidadeRecursoPrimeiraInstancia, InfraLog::$INFORMACAO);

                            } catch (Exception $e) {
                                LogSEI::getInstance()->gravar('M�dulo Integra��o FalaBR - (Recurso tipo ' . $tipo_recurso . ') N�o foi possivel abrir o Processo ' . $numProtocoloFormatado . ' na unidade ' . $idUnidadeRecursoPrimeiraInstancia . ' - erro: ' . $e, InfraLog::$INFORMACAO);
                            }
                        } else {
                            /**
                             * @todo - confirmar - aqui deve ficar como 'N' ou 'S'? Se fircar como 'N' entra como erro... ?? e � preciso gravar que n�o houve recurso mas teve altera��o na data de prazo de atencimento,
                             * esta data precisa ser salva no banco de dados... comentar/documentar aqui!
                             */
                            $this->gravarLogLinha($numProtocoloFormatado, $idRelatorioImportacao, 'Sem recursos novos.', 'S', $tipoManifestacao, $dataPrazoAtendimento);
                        }
                    } catch (Exception $e) {
                        $this->gravarLogLinha($numProtocoloFormatado, $idRelatorioImportacao, 'Erro na grava��o recurso: ' . $e, 'N', $tipoManifestacao);
                    }
                } else {
                    // 4.2 Se n�o houve altera��o na data 'PrazoAtendimento' retornar log
                    $this->gravarLogLinha($numProtocoloFormatado, $idRelatorioImportacao, 'J� existe um processo (e-Sic) utilizando o n�mero de protocolo e n�o h� altera��o para nova importa��o.', 'S', $tipoManifestacao, $dataPrazoAtendimento);
                }
            }
        } else {
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

                /**
                 * Alteramos de 'push' para 'unshift' para ir para o �ltimo da lista!
                 */
//                array_push($arrDocumentos, $documentoManifestacao);
                array_unshift($arrDocumentos, $documentoManifestacao);
                $objEntradaGerarProcedimentoAPI->setDocumentos($arrDocumentos);
                $objSaidaGerarProcedimentoAPI = $objSeiRN->gerarProcedimento($objEntradaGerarProcedimentoAPI);
                $this->gravarLogLinha($numProtocoloFormatado, $idRelatorioImportacao, 'Protocolo ' . $arrDetalheManifestacao['numProtocolo'] . ' gravado com sucesso.', 'S', $tipoManifestacao);

            } catch (Exception $e) {

                if ($objSaidaGerarProcedimentoAPI != null and $objSaidaGerarProcedimentoAPI->getIdProcedimento() > 0){
                    $this->excluirProcessoComErro($objSaidaGerarProcedimentoAPI->getIdProcedimento());
                }
                $this->gravarLogLinha($numProtocoloFormatado, $idRelatorioImportacao, 'Erro na grava��o: ' . $e, 'N', $tipoManifestacao);
            }
        }
    }

    public function executarImportacaoLinhaRecursos ($arrRecursosManifestacao, $tipoManifestacao = 'R')
    {
        $debugLocal = false;

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
                 * Debug do prazo de atendimento
                 */
//                var_dump('<hr>');
//                var_dump('Protocolo:');
//                var_dump('<hr>');
//                var_dump($numProtocoloFormatado);
//                var_dump('<hr>');
//                var_dump('$objUltimaDataPrazoAtendimento->getDthDthPrazoAtendimento():<br />');
//                var_dump(isset($objUltimaDataPrazoAtendimento) ? $objUltimaDataPrazoAtendimento->getDthDthPrazoAtendimento() : 'null');
//                var_dump('<hr>');
//                var_dump('$dataPrazoAtendimento:<br />');
//                var_dump($dataPrazoAtendimento);
//                var_dump('<hr>');
//                var_dump('VAI IMPORTAR? <br />>');
//                var_dump($objUltimaDataPrazoAtendimento && $objUltimaDataPrazoAtendimento->getDthDthPrazoAtendimento() <> $dataPrazoAtendimento);
//                var_dump('<hr>');
//                die();

                /**
                 * Regra de bloqueio na cria��o de novos recursos caso j� exista um recurso superior ao atualmente listado
                 * - regra implementada devido � duplicidade na importa��o dos processos
                 */
                $ultimoTipoRecursoImportado = MdCguEouvAgendamentoINT::retornarTipoManifestacao($idRelatorioImportacao, $numProtocoloFormatado);
                $ultimoTipoRecursoImportado = $ultimoTipoRecursoImportado ? $ultimoTipoRecursoImportado->getStrTipManifestacao() : $this->verificaTipo($arrRecursosManifestacao, 'R1');
                $debugLocal && LogSEI::getInstance()->gravar('[executarImportacaoLinhaRecursos] Ultimo tipo de recurso importado: ' . $ultimoTipoRecursoImportado . ' - Tipo recurso atual: ' . $this->verificaTipo($arrRecursosManifestacao, 'R1'));

                $permiteImportacaoRecursoAtual = $this->permiteImportacaoRecursoAtual($this->verificaTipo($arrRecursosManifestacao, 'R1'), $ultimoTipoRecursoImportado);
                $debugLocal && LogSEI::getInstance()->gravar('[executarImportacaoLinhaRecursos] Permite criar o recurso atual: ' . $permiteImportacaoRecursoAtual);

                if ($permiteImportacaoRecursoAtual == 'bloquear') {
                    $debugLocal && LogSEI::getInstance()->gravar('[executarImportacaoLinhaRecursos] N�o foi permitido criar o recurso, pode deve haver recurso anterior j� importado');
                    // Se n�o for permitido criar o recurso
                    $this->gravarLogLinha($numProtocoloFormatado, $idRelatorioImportacao, 'O recurso existente no FalaBR n�o ser� importado devido � regra implementada - tipoAtual: "' . $this->verificaTipo($arrRecursosManifestacao, 'R') . '" | tipoAnterior: '. $ultimoTipoRecursoImportado .' | protocolo.', 'S', $ultimoTipoRecursoImportado, $objUltimaDataPrazoAtendimento->getDthDthPrazoAtendimento());
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

                            // Vincular Recursos com as unidades corretas conforme o tipo de recurso
                            // Se for 1 inst�ncia envia processo para ESIC_ID_UNIDADE_RECURSO_PRIMEIRA_INSTANCIA
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

                            $debugLocal && LogSEI::getInstance()->gravar('[executarImportacaoLinhaRecursos] Tipo de recurso: ' . $tipo_recurso);

                            // Buscar dados da Manifesta��o
                            $numProtocoloSemFormatacao = str_replace(['.', '/', '-'], ['', '', ''], $numProtocoloFormatado);
                            $retornoWsLinha = $this->executarServicoConsultaManifestacoes($urlWebServiceEOuv, $token, null, null, $numProtocoloSemFormatacao, $idRelatorioImportacao);
                            $linkDetalheManifestacao = $retornoWsLinha[0]['Links'][0]['href'];
                            $arrDetalheManifestacao = $this->apiRestRequest($linkDetalheManifestacao, $token, 2);

                            $debugLocal && LogSEI::getInstance()->gravar('Importando Recurso processo: ' . $numProtocoloFormatado . ' | tipo: ' . $tipo_recurso);

                            /**
                             * Debug teste de tipod e recurso
                             */
//                            var_dump('<hr>');
//                            var_dump($numProtocoloSemFormatacao);
//                            var_dump('<hr>');
//                            var_dump($tipo_recurso);
//                            die();

                            /**
                             * Verificar o tipo de recurso de for diferente de segunda inst�ncia, trazer todos os recursos para o documento pdf
                             */
                            if ($tipo_recurso <> 'R1') {
                                /**
                                 * Debug recursos dispon�veis para o mesmo protocolo que devem retornar no mesmo arquivo
                                 */
//                                var_dump('<hr>');
//                                var_dump($arrRecursosManifestacao);
//                                var_dump('<hr>');
//                                var_dump('reverse');
//                                var_dump('<hr>');
//                                $arrRecursosManifestacaoComAnteriores = array_reverse($this->executarServicoConsultaRecursos($urlWebServiceESicRecursos, $token, null, null, $numProtocoloSemFormatacao));
//                                var_dump('<hr>');
//                                var_dump($arrRecursosManifestacaoComAnteriores['Recursos']);
//                                var_dump('<hr>');
//                                var_dump('normal');
//                                var_dump('<hr>');
//                                $arrRecursosManifestacaoComAnterioresNormal = $this->executarServicoConsultaRecursos($urlWebServiceESicRecursos, $token, null, null, $numProtocoloSemFormatacao);
//                                var_dump('<hr>');
//                                var_dump($arrRecursosManifestacaoComAnterioresNormal['Recursos']);
//                                var_dump('<hr>');
//                                die();

                                $arrRecursosManifestacaoComAnteriores = $this->executarServicoConsultaRecursos($urlWebServiceESicRecursos, $token, null, null, $numProtocoloSemFormatacao);
                                $this->gerarPDFDocumentoESic($arrDetalheManifestacao, $arrRecursosManifestacaoComAnteriores, $objProtocoloDTOExistente->getDblIdProtocolo(), $tipo_recurso);
                            } else {
                                $this->gerarPDFDocumentoESic($arrDetalheManifestacao, $arrRecursosManifestacao, $objProtocoloDTOExistente->getDblIdProtocolo(), $tipo_recurso);
                            }

                            $this->gravarLogLinha($numProtocoloFormatado, $idRelatorioImportacao, 'Recurso tipo ' . $tipo_recurso . ' com protocolo ' . $numProtocoloFormatado . ' importado com sucesso com ' . $anexoCount . ' anexos incluidos no protocolo.', 'S', $tipo_recurso, $dataPrazoAtendimento);
                            $debugLocal && LogSEI::getInstance()->gravar('Importando Recurso processo: ' . $numProtocoloFormatado . ' | tipo: ' . $tipo_recurso . 'depois de gravar log ?!');
                            // $this->gravarLogLinha($numProtocoloFormatado, $idRelatorioImportacao, 'Recurso com protocolo ' . $numProtocoloFormatado . ' importado com sucesso com ' . $anexoCount . ' anexos incluidos no protocolo.', 'S', $tipoManifestacao, $dataPrazoAtendimento);
                            LogSEI::getInstance()->gravar('M�dulo Integra��o FalaBR - Importa��o de Recurso ' . $numProtocoloFormatado . ': total de  Anexos configurados: ' . $anexoCount, InfraLog::$INFORMACAO);

                            // Carregar anexos
                            if (count($arrRecursosManifestacao['anexos']) > 0) {
                                $this->gerarAnexosProtocolo($arrRecursosManifestacao['anexos'], $numProtocoloFormatado, $tipoManifestacao, $objProtocoloDTOExistente->getDblIdProtocolo());
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
                                LogSEI::getInstance()->gravar('M�dulo Integra��o FalaBR - (Recurso tipo ' . $tipo_recurso . ') Processo ' . $numProtocoloFormatado . ' enviado para unidade ' . $idUnidadeRecursoPrimeiraInstancia, InfraLog::$INFORMACAO);

                            } catch (Exception $e) {
                                LogSEI::getInstance()->gravar('M�dulo Integra��o FalaBR - (Recurso tipo ' . $tipo_recurso . ') N�o foi possivel abrir o Processo ' . $numProtocoloFormatado . ' na unidade ' . $idUnidadeRecursoPrimeiraInstancia . ' - erro: ' . $e, InfraLog::$INFORMACAO);
                            }
                        } else {
                            $this->gravarLogLinha($numProtocoloFormatado, $idRelatorioImportacao, 'Sem recursos novos.', 'S', $ultimoTipoRecursoImportado, $dataPrazoAtendimento);
                        }
                    } catch (Exception $e) {
                        $debugLocal && LogSEI::getInstance()->gravar('[executarImportacaoLinhaRecursos] Erro importando anexo do recruso');
                        $this->gravarLogLinha($numProtocoloFormatado, $idRelatorioImportacao, 'Erro na grava��o recurso: ' . $e, 'N', $tipoManifestacao);
                    }
                } else {
                    $debugLocal && LogSEI::getInstance()->gravar('[executarImportacaoLinhaRecursos] N�o importou recurso pois o prazo de atendimento � igual e n�o faz nada.. n�o atualiza o log para n�o atualizar a data do novo prazo nem o tipo de recurso');
                    // Se n�o houve altera��o na data 'PrazoAtendimento' retornar log
                    $this->gravarLogLinha($numProtocoloFormatado, $idRelatorioImportacao, 'J� existe um recurso (e-Sic) do tipo "' . $this->verificaTipo($arrRecursosManifestacao, 'R') . '" para este protocolo e n�o h� altera��o para nova importa��o.', 'S', $ultimoTipoRecursoImportado, $objUltimaDataPrazoAtendimento->getDthDthPrazoAtendimento());
                }
            }
        } else {
            $this->gravarLogLinha($numProtocoloFormatado, $idRelatorioImportacao, 'Existe recurso para o processo ' . $numProtocoloFormatado . ', por�m este processo n�o existe no SEI. Provavelmente � um processo antes da data de in�cio de utiliza��o do m�dulo ou o Tipo de Manifesta��o do FalaBR n�o foi registrada para este m�dulo.', 'S', $tipoManifestacao);
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
         * // DADOS INICIAIS DA MANIFESTA��O
         * Primeiro � gerado o PDF com todas as informa��es referentes a Manifesta��o, e mais abaixo
         * � incluindo como um anexo do novo Processo Gerado
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
            $desc_raca_cor = $retornoWsLinha['Manifestante']['corRa�a'];
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
        $pdf->Cell(0, 5, "Dados da Manifesta��o", 0, 1, 'C');
        $pdf->Cell(0, 5, "", "B", 1, 'C');
        $pdf->Ln(20);

        //***********************************************************************************************
        //1. Dados INICIAIS
        //***********************************************************************************************
        $pdf->SetFont('arial', 'B', 14);
        $pdf->Cell(0, 20, "1. Dados Iniciais da Manifesta��o", 0, 0, 'L');
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

        //Tipo de Manifesta��o
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(150, 20, "Tipo da Manifesta��o:", 0, 0, 'L');
        $pdf->setFont('arial', '', 12);
        $pdf->Cell(0, 20, $id_tipo_manifestacao . " - " . $desc_tipo_manifestacao, 0, 1, 'L');

        //EnvolveDas4OuSuperior
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(450, 20, "Den�ncia Envolvendo Ocupante de Cargo Comissionado DAS4 ou Superior?:", 0, 0, 'L');
        $pdf->setFont('arial', '', 12);
        $pdf->Cell(20, 20, $envolve_das4_superior, 0, 1, 'L');

        //Prazo de Atendimento
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(150, 20, "Prazo de Atendimento:", 0, 0, 'L');
        $pdf->setFont('arial', '', 12);
        $pdf->Cell(70, 20, $dt_prazo_atendimento, 0, 1, 'L');

        //Nome do �rg�o
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(150, 20, "Nome do �rg�o:", 0, 0, 'L');
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

            //Faixa Et�ria
            $pdf->SetFont('arial', 'B', 12);
            $pdf->Cell(150, 20, "Faixa Et�ria:", 0, 0, 'L');
            $pdf->setFont('arial', '', 12);
            $pdf->Cell(70, 20, $desc_faixa_etaria, 0, 1, 'L');

            //Ra�a Cor
            $pdf->SetFont('arial', 'B', 12);
            $pdf->Cell(150, 20, "Ra�a/Cor:", 0, 0, 'L');
            $pdf->setFont('arial', '', 12);
            $pdf->Cell(70, 20, $desc_raca_cor, 0, 1, 'L');

            //Sexo
            $pdf->SetFont('arial', 'B', 12);
            $pdf->Cell(150, 20, "Sexo:", 0, 0, 'L');
            $pdf->setFont('arial', '', 12);
            $pdf->Cell(70, 20, $sexo, 0, 1, 'L');

            //Documento Identifica��o
            $pdf->SetFont('arial', 'B', 12);
            $pdf->Cell(170, 20, "Documento de Identifica��o:", 0, 0, 'L');
            $pdf->setFont('arial', '', 12);
            $pdf->Cell(70, 20, $desc_documento_identificacao, 0, 1, 'L');

            //Número do Documento Identifica��o
            $pdf->SetFont('arial', 'B', 12);
            $pdf->Cell(150, 20, "N�mero do Documento:", 0, 0, 'L');
            $pdf->setFont('arial', '', 12);
            $pdf->Cell(70, 20, $numero_documento_identificacao, 0, 1, 'L');

            $pdf->ln(4);
            //Endere�o
            $pdf->SetFont('arial', 'B', 12);
            $pdf->Cell(70, 20, "Endere�o:", 0, 1, 'L');
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
            $pdf->Cell(150, 20, "N�o importado do E-Ouv devido a configura��o no m�dulo.", 0, 0, 'L');
        }
        $pdf->Ln(20);

        //***********************************************************************************************
        //3. Dados do Fato da Manifesta��o
        //***********************************************************************************************
        $pdf->Ln(20);
        $pdf->SetFont('arial', 'B', 14);
        $pdf->Cell(70, 20, "3. Fato da Manifesta��o:", 0, 0, 'L');
        $pdf->Ln(20);

        //Munic�pio/UF
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(115, 20, "Munic�pio/UF:", 0, 0, 'L');
        $pdf->setFont('arial', '', 12);
        $pdf->Cell(70, 20, $desc_municipio_fato, 0, 1, 'L');

        //Descricao Local
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(115, 20, "Local:", 0, 0, 'L');
        $pdf->setFont('arial', '', 12);
        $pdf->Cell(70, 20, $descricao_local_fato, 0, 1, 'L');

        //Descri��o
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(115, 20, "Descri��o:", 0, 1, 'L');
        $pdf->setFont('arial', '', 12);
        $pdf->MultiCell(0, 20, $descricao_fato, 0, 'J');

        //Envolvidos
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(115, 20, "Envolvidos:", 0, 1, 'L');
        $pdf->setFont('arial', '', 12);

        for ($x = 0; $x < count($envolvidos); $x++) {
            $pdf->Cell(70, 20, "Fun��o:", 0, 0, 'L');
            $pdf->Cell(0, 20, $envolvidos[$x][0], 0, 1, 'L');
            $pdf->Cell(70, 20, "Nome:", 0, 0, 'L');
            $pdf->Cell(0, 20, $envolvidos[$x][1], 0, 1, 'L');
            $pdf->Cell(70, 20, "�rg�o:", 0, 0, 'L');
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
            $pdf->Cell(70, 20, "5. Observa��es:", 0, 0, 'L');
            $pdf->Ln(20);

            $pdf->SetFont('arial', '', 12);
            $pdf->MultiCell(0, 20, "Um ou mais anexos da manifesta��o n�o foram importados para o SEI devido a restri��es da extens�o do arquivo. Acesse a manifesta��o atrav�s do link abaixo para mais detalhes. ", 0, 'J');
        }

        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(115, 20, "Link para manifesta��o no E-ouv:", 0, 1, 'L');
        $pdf->setFont('arial', '', 12);
        $pdf->Cell(0, 20, $urlEouvDetalhesManifestacao, 0, 1, 'L');

        $objAnexoRN = new AnexoRN();
        $strNomeArquivoInicialUpload = $objAnexoRN->gerarNomeArquivoTemporario();

        $pdf->Output(DIR_SEI_TEMP . "/" . $strNomeArquivoInicialUpload . ".pdf", "F");

        //Renomeia tirando a extens�o para o SEI trabalhar o Arquivo
        rename(DIR_SEI_TEMP . "/" . $strNomeArquivoInicialUpload . ".pdf", DIR_SEI_TEMP . "/" . $strNomeArquivoInicialUpload);

        $objDocumentoManifestacao = new DocumentoAPI();
        $objDocumentoManifestacao->setTipo('R');
        $objDocumentoManifestacao->setIdSerie($idTipoDocumentoAnexoDadosManifestacao);
        $objDocumentoManifestacao->setData($retornoWsLinha['DataCadastro']);
        $objDocumentoManifestacao->setNomeArquivo('Relat�rioDadosManifesta��o.pdf');
        $objDocumentoManifestacao->setConteudo(base64_encode(file_get_contents(DIR_SEI_TEMP . "/" . $strNomeArquivoInicialUpload)));

        return $objDocumentoManifestacao;
    }

    public function gerarPDFDocumentoESic($retornoWsLinha, $retornoWsRecursos = null, $IdProtocolo = false, $tipo_recurso = '')
    {
        global $idTipoDocumentoAnexoDadosManifestacao,
               $ocorreuErroAdicionarAnexo,
               $importar_dados_manifestante,
               $idRelatorioImportacao;

        /**
         * Testa acessar dado da manifesta��o se n�o conseguir salva log
         */
        try {
            $IdTipoManifestacao = $retornoWsLinha['TipoManifestacao']['IdTipoManifestacao'];
        } catch (Exception $e) {
            $this->gravarLogLinha($IdProtocolo ? $IdProtocolo : 'n/a', $idRelatorioImportacao, substr('ERRO-esic|' . $retornoWsLinha . '|' . $e, 0, 500), 'N');
            return;
        }

        /***********************************************************************************************
         * DADOS INICIAIS DA MANIFESTA��O
         * Primeiro � gerado o PDF com todas as informa��es referentes a Manifesta��o e mais abaixo
         * � inclu�do como um anexo do novo Processo Gerado
         * *********************************************************************************************/

        $pdf = new InfraPDF("P", "pt", "A4");

        $pdf->AddPage();

        /**
         * Arquivo PDF - manifesta��o e-Sic
         */

        // Cabe�alho
        $pdf->SetFont('arial', 'B', 18);
        $pdf->Cell(0, 5, "Plataforma Integrada de Ouvidoria e Acesso � Informa��o", 0, 1, 'C');
        $pdf->Ln(20);
        $pdf->Cell(0, 5, "Detalhes da Manifesta��o", 0, 1, 'C');
        $pdf->Ln(30);

        /**
         * Dados b�sicos da manifesta��o
         */
        $menu_count = 1;
        $pdf->SetFont('arial', 'B', 14);
//        $pdf->SetDrawColor(135,206,250);
        $pdf->Cell(0, 20, $menu_count . ". Dados B�sicos da Manifesta��o", 1, 0, 'L');
//        $pdf->SetDrawColor(255,255,255);
        $pdf->Ln(30);

        // Tipo de Manifesta��o
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(180, 20, "Tipo da Manifesta��o:", 0, 0, 'R');
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

        // �rg�o Destinat�rio - NomeOuvidoria
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(180, 20, "�rg�o Destinat�rio:", 0, 0, 'R');
        $pdf->setFont('arial', '', 12);
        $pdf->Cell(0, 20, $retornoWsLinha['OuvidoriaDestino']['NomOuvidoria'], 0, 1, 'L');

        // �rg�o de Interesse
        if ($retornoWsLinha['OrgaoInteresse'] && $retornoWsLinha['OrgaoInteresse']['NomeOrgao']) {
            $pdf->SetFont('arial', 'B', 12);
            $pdf->Cell(180, 20, "�rg�o de Interesse:", 0, 0, 'R');
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

        // Situa��o
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(180, 20, "Situa��o:", 0, 0, 'R');
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

        // Servi�o
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(180, 20, "Servi�o:", 0, 0, 'R');
        $pdf->setFont('arial', '', 12);
        $pdf->Cell(70, 20, $retornoWsLinha['Servico'], 0, 1, 'L');

        // Outro Servi�o (?)
//        $pdf->SetFont('arial', 'B', 12);
//        $pdf->Cell(180, 20, "Outro Servi�o:", 0, 0, 'R');
//        $pdf->setFont('arial', '', 12);
//        $pdf->Cell(70, 20, $retornoWsLinha['Servico'], 0, 1, 'L');

        /**
         * Dados b�sicos da manifesta��o
         */
        $menu_count++;
        $pdf->Ln(30);
        $pdf->SetFont('arial', 'B', 14);
        $pdf->Cell(0, 20, $menu_count . ". Teor da Manifesta��o", 1, 0, 'L');
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

        // Munic�pio do local do fato
//        $pdf->SetFont('arial', 'B', 12);
//        $pdf->Cell(180, 20, "Munic�pio do local do fato:", 0, 0, 'R');
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
         * - 1 : "Anexo Manifesta��o"
         * - 2 : "Anexo Resposta"
         */
        // Anexos
        $menu_count++;
        $pdf->Ln(30);
        $pdf->SetFont('arial', 'B', 14);
        $pdf->Cell(0, 20, $menu_count . ". Anexo(s) do Pedido Inicial", true, 0, 'L');
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
                    $pdf->Cell(70, 20, $anexo['IndComplementar'] ? 'sim' : 'n�o', 0, 1, 'L');

                    $pdf->Ln(20);
                }
            }
        }
        if ($anexo_tipo_original == 0) {
            // Sem anexo original
            $pdf->SetFont('arial', 'B', 12);
            $pdf->Cell(180, 20, "N�o h� anexos originais da manifesta��o.", 0, 0, 'L');
            $pdf->Ln(20);
        }
        if ($anexo_tipo_complementar == 0) {
            // Sem anexo complementar
            $pdf->SetFont('arial', 'B', 12);
            $pdf->Cell(180, 20, "N�o h� anexos complementares.", 0, 0, 'L');
            $pdf->Ln(20);
        }

        // Textos Complementares - @todo - onde est�o os textos complementares?
//        $pdf->SetFont('arial', 'B', 12);
//        $pdf->Cell(150, 20, "Descri��o:", 0, 1, 'L');
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
//                $pdf->Cell(180, 20, "Fun��o:", 0, 0, 'R');
//                $pdf->setFont('arial', '', 12);
//                $pdf->Cell(70, 20, $envolvido['Funcao'], 0, 1, 'L');
//
//                // Anexo Complementar
//                $pdf->SetFont('arial', 'B', 12);
//                $pdf->Cell(180, 20, "Org�o:", 0, 0, 'R');
//                $pdf->setFont('arial', '', 12);
//                $pdf->Cell(70, 20, $envolvido['Orgao'], 0, 1, 'L');
//
//                $pdf->Ln(20);
//            }
//        } else {
//            // Sem envolvidos
//            $pdf->SetFont('arial', 'B', 12);
//            $pdf->Cell(180, 20, "N�o h� envolvidos na manifesta��o.", 0, 0, 'L');
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
            $pdf->Cell(180, 20, "N�o h� campos adicionais.", 0, 0, 'L');
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
//        $pdf->MultiCell(180, 20, "Envolve ocupante de cargo comissionado DAS a partir do n�vel 4 ou equivalente?", 0, 'R');
//        $pdf->setFont('arial', '', 12);
//        $pdf->MultiCell(380, 20,$retornoWsLinha['InformacoesAdicionais']['EnvolveCargoComissionadoDAS4OuSuperior'], 0, 'C');
//        $pdf->Ln(20);

        // Manifesta��o Apta?
//        $pdf->SetFont('arial', 'B', 12);
//        $pdf->MultiCell(180, 20, "Manifesta��o Apta?", 0, 'R');
//        $pdf->setFont('arial', '', 12);
//        $pdf->MultiCell(380, 20, $retornoWsLinha['InformacoesAdicionais']['Apta'], 0, 'C');
//        $pdf->Ln(20);

        // H� envolvimento de Empresa?
//        $pdf->SetFont('arial', 'B', 12);
//        $pdf->MultiCell(180, 20, "H� envolvimento de Empresa?", 0, 'R');
//        $pdf->setFont('arial', '', 12);
//        $pdf->MultiCell(380, 20, $retornoWsLinha['InformacoesAdicionais']['EnvolveEmpresa'], 0, 'C');
//        $pdf->Ln(20);

        // H� envolvimento de Servidor P�blico?
//        $pdf->SetFont('arial', 'B', 12);
//        $pdf->MultiCell(180, 20, "H� envolvimento de Servidor P�blico?", 0, 'R');
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

                    // Decis�o
                    $pdf->SetFont('arial', 'B', 12);
                    $pdf->Cell(180, 20, "Decis�o:", 0, 0, 'R');
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
            $pdf->Cell(180, 20, "N�o h� registro de respostas.", 0, 0, 'L');
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
                    $pdf->Cell(70, 20, $anexo['IndComplementar'] ? 'sim' : 'n�o', 0, 1, 'L');

                    $pdf->Ln(20);
                }
            }
        }
        if (! isset($possui_anexo_resposta)) {
            // Sem anexo resposta
            $pdf->SetFont('arial', 'B', 12);
            $pdf->Cell(180, 20, "N�o h� anexos de respostas.", 0, 0, 'L');
            $pdf->Ln(20);
        }

        /**
         * Recursos
         *
         * Neste item importamos as seguintes op��es de recursos:
         * - Pedido de Revis�o
         * - Recurso de Primeira Inst�ncia
         * - Recurso de Segunda Inst�ncia
         */
        if ($retornoWsRecursos && $retornoWsRecursos <> '' && !is_string($retornoWsRecursos)) {
            $menu_count++;
            $pdf->Ln(30);
            $pdf->SetFont('arial', 'B', 14);
            $pdf->Cell(0, 20, $menu_count . ". Recursos", true, 0, 'L');
            $pdf->Ln(30);

            $recursos = isset($retornoWsRecursos['Recursos']) ? $retornoWsRecursos['Recursos'] : [$retornoWsRecursos];

            if (count($recursos) > 0) {

                $reversedRecursos = array_reverse($recursos);

                /**
                 * Debug de revers�o do Array para colocar dados do recurso em ordem crescente
                 */
//                var_dump('<hr>');
//                var_dump($reversedRecursos);
//                var_dump('<hr>');
//                var_dump($recusos);
//                var_dump('<hr>');
//                die();

                foreach ($reversedRecursos as $recurso) {

                    /**
                     * Somente gerar� documento caso seja recursos 1� ou 2� instancia ou pedido de revis�o,
                     * IdInstanciaRecurso = [1, 2, 6] conforme API FalaBR consultado dia 01/12/2020
                     * url: https://falabr.cgu.gov.br/Help
                     */
                    if (in_array($recurso['instancia']['IdInstanciaRecurso'], [1, 2, 6])) {
                        $pdf->SetFont('arial', 'B', 12);
                        $pdf->Cell(0, 20, "Dados do Recurso -  " . $recurso['instancia']['DescInstanciaRecurso'], true, 1, 'L');

                        // Destinat�rio
                        $pdf->SetFont('arial', 'B', 12);
                        $pdf->Cell(180, 20, "Destinat�rio:", 0, 0, 'R');
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

                        // Anexos
                        $pdf->SetFont('arial', 'B', 12);
                        $pdf->Cell(180, 20, "Anexos:", 0, 0, 'R');
                        $pdf->setFont('arial', '', 12);
                        $anexosRecursos = $recurso['anexos'];
                        if (is_array($anexosRecursos) && count($anexosRecursos) > 0) {
                            $pdf->Cell(70, 20, ' ', 0, 1, 'L');
                            foreach ($anexosRecursos as $anexoRecurso) {
                                $pdf->Cell(180, 20, '-', 0, 0, 'R');
                                $pdf->Cell(70, 20, $anexoRecurso['nomeArquivo'], 0, 1, 'L');
                            }
                        } else {
                            $pdf->Cell(70, 20, 'N�o possui anexos', 0, 1, 'L');
                        }

                        $pdf->Ln(20);
                    }
                }
            }
        }

        /**
         * Den�ncia de Descumprimento
         */
        $menu_count++;
        $pdf->Ln(30);
        $pdf->SetFont('arial', 'B', 14);
        $pdf->Cell(0, 20, $menu_count . " Den�ncia de Descumprimento", true, 0, 'L');
        $pdf->Ln(30);

        $denuncias = $retornoWsLinha['Historico'];
        if (count($denuncias) > 0) {
            foreach ($denuncias as $denuncia) {
                if ($denuncia['Denuncia']['TxtFato'] <> '') {

                    $possui_denuncia = true;

                    // Den�ncia
                    $pdf->setFont('arial', '', 12);
                    $pdf->MultiCell(0, 20, $denuncia['HistoricoAcao']['Denuncia']['TxtFato'], 0, 'L');

                    $pdf->Ln(20);
                }
            }
        }
        if (!count($denuncias) > 0 || !isset($possui_denuncia)) {
            // Sem respostas
            $pdf->SetFont('arial', 'B', 12);
            $pdf->Cell(180, 20, "N�o h� registro de den�ncias de descumprimento.", 0, 0, 'L');
            $pdf->Ln(20);
        }

        /**
         * Encaminhamento
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

                    // �rg�o Origem
                    $pdf->SetFont('arial', 'B', 12);
                    $pdf->Cell(180, 20, "�rg�o/Entidade de Origem:", 0, 0, 'R');
                    $pdf->setFont('arial', '', 12);
                    $pdf->Cell(70, 20, $encaminhamento['Encaminhamento']['OuvidoriaOrigem']['NomOuvidoria'], 0, 1, 'L');

                    // �rg�o Destino
                    $pdf->SetFont('arial', 'B', 12);
                    $pdf->Cell(180, 20, "�rg�o/Entidade Destinat�ria:", 0, 0, 'R');
                    $pdf->setFont('arial', '', 12);
                    $pdf->Cell(70, 20, $encaminhamento['Encaminhamento']['OuvidoriaDestino']['NomOuvidoria'], 0, 1, 'L');

                    // Mensagem ao Destinat�rio
                    $pdf->SetFont('arial', 'B', 12);
                    $pdf->Cell(180, 20, "Mensagem ao Destinat�rio:", 0, 0, 'R');
                    $pdf->setFont('arial', '', 12);
                    $pdf->Cell(70, 20, $encaminhamento['Encaminhamento']['TxtNotificacaoDestinatario'], 0, 1, 'L');

                    // Mensagem ao Cidad�o
                    $pdf->SetFont('arial', 'B', 12);
                    $pdf->Cell(180, 20, "Mensagem ao Cidad�o:", 0, 0, 'R');
                    $pdf->setFont('arial', '', 12);
                    $pdf->Cell(70, 20, $encaminhamento['Encaminhamento']['TxtNotificacaoSolicitante'], 0, 1, 'L');

                    $pdf->Ln(20);
                }
            }
        }
        if (!count($encaminhamentos) > 0 || !isset($possui_denuncia)) {
            // Sem respostas
            $pdf->SetFont('arial', 'B', 12);
            $pdf->Cell(180, 20, "N�o h� registro de encaminhamentos.", 0, 0, 'L');
            $pdf->Ln(20);
        }

        /**
         * Prorroga��o
         */
        $menu_count++;
        $pdf->Ln(30);
        $pdf->SetFont('arial', 'B', 14);
        $pdf->Cell(0, 20, $menu_count . ". Dados de Prorroga��o", true, 0, 'L');
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

                    // Motivo da Prorroga��o
                    $pdf->SetFont('arial', 'B', 12);
                    $pdf->Cell(180, 20, "Motivo:", 0, 0, 'R');
                    $pdf->setFont('arial', '', 12);
                    $pdf->Cell(70, 20, $prorrogacao['Prorrogacao']['MotivoProrrogacaoManifestacao']['DescMotivoProrrogacaoManifestacao'], 0, 1, 'L');

                    // Justificativa da Prorroga��o
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
            $pdf->Cell(180, 20, "N�o h� registro de prorroga��es.", 0, 0, 'L');
            $pdf->Ln(20);
        }

        /**
         * Observa��es finais
         */
        if($ocorreuErroAdicionarAnexo == true){
            $pdf->Ln(20);
            $pdf->SetFont('arial', 'B', 14);
            $pdf->Cell(70, 20, "12. Observa��es:", 0, 0, 'L');
            $pdf->Ln(20);

            $pdf->SetFont('arial', '', 12);
            $pdf->MultiCell(0, 20, "Um ou mais anexos da manifesta��o n�o foram importados para o SEI devido a restri��es da extens�o do arquivo. Acesse a manifesta��o atrav�s do link abaixo para mais detalhes. ", 0, 'J');
        }

        // e-Sic fim
        $pdf->Ln(30);
        $pdf->MultiCell(0, 1, '', 1, 'J', 1);
//        $pdf->Cell(0, 20, "FIM", true, 1, 'C');
//        $pdf->Ln(30);

        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(115, 20, "Link para manifesta��o no FalaBR:", 0, 1, 'L');
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
         * In�cio da importa��o de anexos de cada protocolo
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

                        //Busca o conte�do do Anexo
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
                        $objAnexoManifestacao->setTipo('R');
                        $objAnexoManifestacao->setIdSerie($idTipoDocumentoAnexoDadosManifestacao);
                        $objAnexoManifestacao->setData(InfraData::getStrDataHoraAtual());
                        $objAnexoManifestacao->setNomeArquivo($strNomeArquivoOriginal);
                        $objAnexoManifestacao->setNumero($strNomeArquivoOriginal);
                        $objAnexoManifestacao->setConteudo(base64_encode(file_get_contents(DIR_SEI_TEMP . '/' . $strNomeArquivoUpload)));

                        if ($this->hashDuplicado(DIR_SEI_TEMP . '/' . $strNomeArquivoUpload, $numProtocoloFormatado)) {
//                            $this->gravarLogLinha($numProtocoloFormatado, $idRelatorioImportacao, 'Arquivo j� anexado ao processo: ' . $strNomeArquivoOriginal, 'S', $tipoManifestacao);
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
                $this->gravarLogLinha($numProtocoloFormatado, $idRelatorioImportacao, 'Um ou mais documentos anexos n�o foram importados corretamente: ' . $strMensagemErroAnexos, 'S', $tipoManifestacao);
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
            //PaginaSEI::getInstance()->setStrMensagem('Exclusão realizada com sucesso.');
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
     * @param $idRelatorioImportacao
     * @param $numProtocolo
     * @param $tipoManifestacao
     * @return bool|void
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
             * Para criar um RC ainda n�o existe regra interna definida
             */
//            if ($tipoManifestacaoAtual == 'RC') {}

            /**
             * Para criar um RC ainda n�o existe regra interna definida
             */
//            if ($tipoManifestacaoAtual == 'R3') {}

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
