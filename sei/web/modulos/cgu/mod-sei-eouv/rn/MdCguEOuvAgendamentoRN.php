<?
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

    public function apiRestRequest($url, $token, $tipo){

        /*TIPO:
        1 = Lista de Manifesta��es
        2 = Detalhe da Manifesta��o
        3 = Detalhe de um Anexo da Manifesta��o
        */
        /*if ($tipo == 1) {
            echo "<BR>URL: " . $url;
            //echo "<BR>TOKEN: " . $token;
            //exit();
        }
        */
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_POSTFIELDS => "undefined=",
            CURLOPT_HTTPHEADER => array(
                "Authorization: Bearer " . $token,
                "cache-control: no-cache",

            ),
        ));

        $response = curl_exec($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $err = curl_error($curl);

        curl_close($curl);

        //Se tiver retornado Token Invalidado
        if ($httpcode == 401) {
            $response = "Token Invalidado. HTTP Status: " . $httpcode;
        }
        elseif ($httpcode == 200) {
            $response = json_decode($response, true);
            $response = $this->decode_result($response);
        }
        else{
            $response = "Erro: Ocorreu algum erro n�o tratado. HTTP Status: " . $httpcode;
        }

        return $response;

    }

    function decode_result($array)
    {
        foreach($array as $key => $value)
        {
            if(is_array($value))
            {
                $array[$key] = $this->decode_result($value);
            }
            else
            {
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
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_POSTFIELDS => "client_id=".$client_id."&client_secret=".$client_secret."&grant_type=password&username=".$username."&password=".$password."&undefined=",
            //CURLOPT_POSTFIELDS => "client_id=15&client_secret=rwkp6899&grant_type=password&username=wsIntegracaoSEI&password=teste1235&undefined=",
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/x-www-form-urlencoded",
                "Postman-Token: 65f1b627-4926-49ed-8109-8586ffc4ec53",
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


        if(is_null($numprotocolo)) {
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
        }

        //Faz tratamento diferenciado para consulta por Protocolo espec�fico
        else{

            if (strpos($retornoWs, 'Erro') !== false) {
                if(strpos($retornoWs, '404') !== false){
                    $this->gravarLogLinha($this->formatarProcesso($numprotocolo), $numIdRelatorio, "Usu�rio n�o possui permiss�o de acesso neste protocolo.", 'N');
                    $retornoWs = null;
                }
                else{
                    $this->gravarLogLinha($this->formatarProcesso($numprotocolo), $numIdRelatorio, "Erro desconhecido" . $retornoWs, 'N');
                    throw new Exception($retornoWs);
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

    public function gravarLogImportacao($ultimaDataExecucao, $dataAtual){

        try {
            $objEouvRelatorioImportacaoDTO = new MdCguEouvRelatorioImportacaoDTO();

            $objEouvRelatorioImportacaoDTO->retNumIdRelatorioImportacao();
            $objEouvRelatorioImportacaoDTO->setNumIdRelatorioImportacao(null);
            $objEouvRelatorioImportacaoDTO->setDthDthImportacao(InfraData::getStrDataHoraAtual());
            $objEouvRelatorioImportacaoDTO->setDthDthPeriodoInicial($ultimaDataExecucao);
            $objEouvRelatorioImportacaoDTO->setDthDthPeriodoFinal($dataAtual);
            $objEouvRelatorioImportacaoDTO->setStrDeLogProcessamento('Passo 1 - Iniciando processamento.');
            $objEouvRelatorioImportacaoDTO->setStrSinSucesso('N');

            $objEouvRelatorioImportacaoRN = new MdCguEouvRelatorioImportacaoRN();
            $objEouvRelatorioImportacaoRN = $objEouvRelatorioImportacaoRN->cadastrar($objEouvRelatorioImportacaoDTO);

            return $objEouvRelatorioImportacaoDTO;

        }catch (Exception $e) {
            PaginaInfra::getInstance()->processarExcecao($e);
            die;
        }

    }

    public function gravarLogLinha($numProtocolo, $idRelatorioImportacao, $mensagem, $sinSucesso)
    {

        /*echo "<BR><BR>GRAVAR LOG LINHA<BR><BR>";
        echo "<BR>PROTOCOLO" . $numProtocolo;
        echo "<BR>IdRelatorio" . $idRelatorioImportacao;
        echo "<BR>MENSAGEM" . $mensagem;
        echo "<BR>sinSucesso" . $sinSucesso;*/


        $objEouvRelatorioImportacaoDetalheDTO = new MdCguEouvRelatorioImportacaoDetalheDTO();
        $objEouvRelatorioImportacaoDetalheDTO->retStrProtocoloFormatado();
        $objEouvRelatorioImportacaoDetalheDTO->setNumIdRelatorioImportacao($idRelatorioImportacao);
        $objEouvRelatorioImportacaoDetalheDTO->setStrProtocoloFormatado($numProtocolo);


        $objEouvRelatorioImportacaoDetalheRN = new MdCguEouvRelatorioImportacaoDetalheRN();
        $objExisteDetalheDTO = $objEouvRelatorioImportacaoDetalheRN->consultar($objEouvRelatorioImportacaoDetalheDTO);

        $objEouvRelatorioImportacaoDetalheDTO->setStrSinSucesso($sinSucesso);
        $objEouvRelatorioImportacaoDetalheDTO->setStrDescricaoLog(substr($mensagem,0,254));

        $objEouvRelatorioImportacaoDetalheDTO->setDthDthImportacao(InfraData::getStrDataHoraAtual());

        if($objExisteDetalheDTO==null) {
            $objEouvRelatorioImportacaoDetalheRN->cadastrar($objEouvRelatorioImportacaoDetalheDTO);
        }
        else{
            $objEouvRelatorioImportacaoDetalheRN->alterar($objEouvRelatorioImportacaoDetalheDTO);
        }

    }

    public function obterManifestacoesComErro($urlConsultaManifestacao, $token, $ultimaDataExecucao, $dataAtual, $numIdRelatorio)
    {
        $objEouvRelatorioImportacaoDetalheDTO = new MdCguEouvRelatorioImportacaoDetalheDTO();
        $objEouvRelatorioImportacaoDetalheDTO->retStrProtocoloFormatado();
        $objEouvRelatorioImportacaoDetalheDTO->setStrSinSucesso('N');

        $objEouvRelatorioImportacaoDetalheRN = new MdCguEouvRelatorioImportacaoDetalheRN();
        $objListaErros = $objEouvRelatorioImportacaoDetalheRN->listar($objEouvRelatorioImportacaoDetalheDTO);

        $arrResult = array();
        $arrProtocolos = array();

        foreach($objListaErros as $erro){

            $numProtocolo = preg_replace("/[^0-9]/", "", $erro->getStrProtocoloFormatado());

            //Se já estiver na lista n�o faz novamente para determinado protocolo
            if (!in_array($numProtocolo, $arrProtocolos)){

                //Adiciona no array de Protocolos
                array_push($arrProtocolos, $numProtocolo);

                $retornoWsErro = $this->executarServicoConsultaManifestacoes($urlConsultaManifestacao, $token, null, $dataAtual, $numProtocolo, $numIdRelatorio);

                if (!is_null($retornoWsErro)){
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


    public function executarImportacaoManifestacaoEOuv()
    {

        //try{
        InfraDebug::getInstance()->setBolLigado(true);
        InfraDebug::getInstance()->setBolDebugInfra(false);
        InfraDebug::getInstance()->setBolEcho(false);
        InfraDebug::getInstance()->limpar();

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

            //$ultimaDataExecucao = '05/02/2019 17:00:00';
            //$dataAtual = '05/02/2019 18:00:00';
            $semManifestacoesEncontradas = true;
            $qtdManifestacoesNovas = 0;
            $qtdManifestacoesAntigas = 0;
            $objEouvRelatorioImportacaoDTO = $this->gravarLogImportacao($ultimaDataExecucao, $dataAtual);
            $idRelatorioImportacao = $objEouvRelatorioImportacaoDTO->getNumIdRelatorioImportacao();
            $objEouvRelatorioImportacaoRN = new MdCguEouvRelatorioImportacaoRN();
            $SinSucessoExecucao = 'N';
            $textoMensagemErroToken = '';

            $retornoWs = $this->executarServicoConsultaManifestacoes($urlWebServiceEOuv, $token, $ultimaDataExecucao, $dataAtual, null, $idRelatorioImportacao);

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
                        $this->executarImportacaoLinha($retornoWsLinha);
                    }
                }

                $textoMensagemFinal = 'Execu��o Finalizada com Sucesso!';
                $SinSucessoExecucao = 'S';


                if ($semManifestacoesEncontradas) {
                    $textoMensagemFinal = $textoMensagemFinal . ' N�o foram encontradas manifesta��es para o per�odo.';
                } else {
                    $textoMensagemFinal = $textoMensagemFinal . '<br>Quantidade de Manifesta��es novas encontradas: ' . $qtdManifestacoesNovas . '<br>Quantidade de Manifesta��es encontadas que ocorreram erro em outras importa��es: ' . $qtdManifestacoesAntigas;
                }

                if ($ocorreuErroEmProtocolo) {
                    $textoMensagemFinal = $textoMensagemFinal . '<br> Ocorreram erros em 1 ou mais protocolos.';
                }
            }
            else{
                $textoMensagemFinal = $textoMensagemErroToken;
            }

            //Grava a execu��o com sucesso se tiver corrido tudo bem
            $objEouvRelatorioImportacaoDTO2 = new MdCguEouvRelatorioImportacaoDTO();

            $objEouvRelatorioImportacaoDTO2->setNumIdRelatorioImportacao($objEouvRelatorioImportacaoDTO->getNumIdRelatorioImportacao());
            $objEouvRelatorioImportacaoDTO2->setStrSinSucesso($SinSucessoExecucao);
            $objEouvRelatorioImportacaoDTO2->setStrDeLogProcessamento($textoMensagemFinal);
            $objEouvRelatorioImportacaoRN->alterar($objEouvRelatorioImportacaoDTO2);
        }
        catch(Exception $e){

            //print_r ($e);

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


    public function executarImportacaoLinha($retornoWsLinha){

        global $objEouvRelatorioImportacaoDTO,
               $idTipoDocumentoAnexoPadrao,
               $objProcedimentoDTO,
               $objTipoProcedimentoDTO,
               $arrObjAssuntoDTO,
               $arrObjParticipantesDTO,
               $idTipoDocumentoAnexoDadosManifestacao,
               $idUnidadeOuvidoria,
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

        /*foreach($retornoWsLinha as $x => $x_value) {
            echo "Key=" . $x . ", Value=" . $x_value;
            echo "<br>";
        }*/
        /*echo "DADOS INICIAIS<BR>";
        var_dump($retornoWsLinha);*/

        $linkDetalheManifestacao = $retornoWsLinha['Links'][0]['href'];
        //$linkDetalheManifestacao = 'https://treinamentoouvidorias.cgu.gov.br/api/manifestacoes/24003'; // TESTE MANIFESTACAO COM MUITOS ANEXOS, DESATIVAR

        $arrDetalheManifestacao = $this->apiRestRequest($linkDetalheManifestacao, $token, 2);

        $dataRegistro = $arrDetalheManifestacao['DataCadastro'];
        $numProtocoloFormatado =  $this->formatarProcesso($arrDetalheManifestacao['NumerosProtocolo'][0]);

        //Limpa os registros de detalhe de importa��o com erro para este nup. Caso ocorra um novo, ser� criado
        // novo registro de erro para o NUP no tratamento desta function.
        $this->limparErrosParaNup($numProtocoloFormatado);

        /*echo "<br><br><br>Detalhe Manifesta��o:";
        var_dump($arrDetalheManifestacao);
        echo "<br><br>";*/

        if (!isset($arrDetalheManifestacao['TipoManifestacao']['IdTipoManifestacao'])) {
            $this->gravarLogLinha($numProtocoloFormatado, $idRelatorioImportacao, 'Tipo de processo n�o foi informado.', 'N');
        } else {

            $objEouvDeparaImportacaoDTO = new MdCguEouvDeparaImportacaoDTO();
            $objEouvDeparaImportacaoDTO->retNumIdTipoProcedimento();
            $objEouvDeparaImportacaoDTO->setNumIdTipoManifestacaoEouv($arrDetalheManifestacao['TipoManifestacao']['IdTipoManifestacao']);

            $objEouvDeparaImportacaoRN = new MdCguEouvDeparaImportacaoRN();
            $objEouvDeparaImportacaoDTO = $objEouvDeparaImportacaoRN->consultarRN0186($objEouvDeparaImportacaoDTO);

            if (!$objEouvDeparaImportacaoDTO == null) {
                $idTipoManifestacaoSei = $objEouvDeparaImportacaoDTO->getNumIdTipoProcedimento();
            } else {
                $this->gravarLogLinha($numProtocoloFormatado, $objEouvRelatorioImportacaoDTO->getNumIdRelatorioImportacao(), 'N�o existe mapeamento DePara do Tipo de Manifesta��o do E-Ouv para o tipo de procedimento do SEI.', 'N');
                //continue;
            }
        }

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

            $objProcedimentoAPI = new ProcedimentoAPI();
            $objProcedimentoAPI->setIdTipoProcedimento($objTipoProcedimentoDTO->getNumIdTipoProcedimento());

            $varEspecificacaoAssunto = $arrDetalheManifestacao['Assunto'] . " - " . $arrDetalheManifestacao['SubAssunto'];


            $objProcedimentoAPI->setEspecificacao($varEspecificacaoAssunto);
            $objProcedimentoAPI->setIdUnidadeGeradora($idUnidadeOuvidoria);
            $objProcedimentoAPI->setNumeroProtocolo($numProtocoloFormatado);
            $objProcedimentoAPI->setDataAutuacao($arrDetalheManifestacao['DataCadastro']);
            $objEntradaGerarProcedimentoAPI = new EntradaGerarProcedimentoAPI();
            $objEntradaGerarProcedimentoAPI->setProcedimento($objProcedimentoAPI);

            $objSaidaGerarProcedimentoAPI = new SaidaGerarProcedimentoAPI();

            $objSeiRN = new SeiRN();

            $arrDocumentos = $this->gerarAnexosProtocolo($arrDetalheManifestacao['Teor']['Anexos'], $numProtocoloFormatado);

            $documentoManifestacao =  $this->gerarPDFPedidoInicial($arrDetalheManifestacao);
            LogSEI::getInstance()->gravar('Importa��o de Manifesta��o ' . $numProtocoloFormatado . ': total de  Anexos configurados: ' . count($arrDocumentos), InfraLog::$INFORMACAO);

            array_push($arrDocumentos, $documentoManifestacao);

            $objEntradaGerarProcedimentoAPI->setDocumentos($arrDocumentos);

            $objSaidaGerarProcedimentoAPI = $objSeiRN->gerarProcedimento($objEntradaGerarProcedimentoAPI);

            $this->gravarLogLinha($numProtocoloFormatado, $idRelatorioImportacao, 'Protocolo ' . $arrDetalheManifestacao['numProtocolo'] . ' gravado com sucesso.', 'S');

        } catch (Exception $e) {

            if ($objSaidaGerarProcedimentoAPI != null and $objSaidaGerarProcedimentoAPI->getIdProcedimento() > 0){
                $this->excluirProcessoComErro($objSaidaGerarProcedimentoAPI->getIdProcedimento());
            }
            //print_r($e);
            //throw new InfraException('Erro gerando Processo.',$e);
            $this->gravarLogLinha($numProtocoloFormatado, $idRelatorioImportacao, 'Erro na grava��o: ' . $e, 'N');
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
         * //DADOS INICIAIS DA MANIFESTA��O
         * Primeiro � gerado o PDF com todas as informa��es referentes a Manifesta��o, e mais abaixo
         * � inclu�do como um anexo do novo Processo Gerado
         * **********************************************************************************************/
        $urlEouvDetalhesManifestacao = $retornoWsLinha['Links'][0]['href'];
        $nup = $retornoWsLinha['NumerosProtocolo'][0];
        $dt_cadastro = $retornoWsLinha['DataCadastro'];
        $desc_assunto = $retornoWsLinha['DescAssunto'];
        $desc_sub_assunto = $retornoWsLinha['DescSubAssunto'];
        $id_tipo_manifestacao = $retornoWsLinha['TipoManifestacao']['IdTipoManifestacao'];
        $desc_tipo_manifestacao = $retornoWsLinha['TipoManifestacao']['DescTipoManifestacao'];
        $envolve_das4_superior = $retornoWsLinha['InformacoesAdicionais']['EnvolveCargoComissionadoDAS4OuSuperior'];
        $dt_prazo_atendimento = $retornoWsLinha['PrazoAtendimento'];
        $nome_orgao = $retornoWsLinha['OuvidoriaDestino']['NomeOuvidoria'];

        //print_r($retornoWsLinha['SolicitanteManifestacaoOuvidoria']);
        //exit();

        $nome = $retornoWsLinha['Manifestante']['Nome'];
        $desc_faixa_etaria = $retornoWsLinha['Manifestante']['FaixaEtaria'];
        $desc_raca_cor = $retornoWsLinha['Manifestante']['corRa�a'];
        $sexo = $retornoWsLinha['Manifestante']['genero'];
        $desc_documento_identificacao = $retornoWsLinha['Manifestante']['TipoDocumentoIdentificacao'];
        $numero_documento_identificacao = $retornoWsLinha['Manifestante']['NumeroDocumentoIdentificacao'];
        $endereco = $retornoWsLinha['Manifestante']['Endereco']['Logradouro'] . " " . $retornoWsLinha['Manifestante']['Endereco']['Complemento'];
        $bairro = $retornoWsLinha['Manifestante']['Endereco']['Bairro'];
        $desc_municipio = $retornoWsLinha['Manifestante']['Endereco']['Municipio'];
        $cep = $retornoWsLinha['Manifestante']['Endereco']['Cep'];
        $telefone = $retornoWsLinha['Manifestante']['Telefone'];
        $email = $retornoWsLinha['Manifestante']['Email'];

        $idTipoIdentificacaoManifestante = $retornoWsLinha['Manifestante']['TipoIdentificacaoManifestante']['IdTTipoIdentificacaoManifestanteDTO'];
        $descTipoIdentificacaoManifestante = $retornoWsLinha['Manifestante']['TipoIdentificacaoManifestante']['DescTTipoIdentificacaoManifestanteDTO'];

        $desc_municipio_fato = $retornoWsLinha['LocalFato']['Municipio'];
        $descricao_fato = $retornoWsLinha['LocalFato']['DescricaoLocalFato'];

        $envolvidos = array();
        if (isset($retornoWsLinha['EnvolvidosManifestacao'])) {
            $iEnvolvido = 0;
            foreach ($this->verificaRetornoWS($retornoWsLinha['EnvolvidosManifestacao']) as $envolvidosFatoManifestacao) {
                $envolvidos[$iEnvolvido][0] = $envolvidosFatoManifestacao['EnvolvidosManifestacao']['IdFuncaoEnvolvidoManifestacao'] . " - " . $envolvidosFatoManifestacao['EnvolvidosManifestacao']['Funcao'];
                $envolvidos[$iEnvolvido][1] = $envolvidosFatoManifestacao['EnvolvidosManifestacao']['Nome'];
                $envolvidos[$iEnvolvido][2] = $envolvidosFatoManifestacao['EnvolvidosManifestacao']['Orgao'];
                $iEnvolvido++;
            }
        }

        $campos_adicionais = array();

        if (isset($retornoWsLinha['CamposAdicionaisManifestacao'])) {
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

    public function gerarAnexosProtocolo($arrAnexosManifestacao, $numProtocoloFormatado){

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
         * Início da importa��o de anexos de cada protocolo
         * Desativado momentaneamente
         */

        /*$retornoWsAnexo = $objWSAnexo->GetAnexosManifestacao(array("login" => '11111111111',
            "senha" => 'abcd1234', "numeroProtocolo" => InfraUtil::retirarFormatacao($numProtocoloFormatado)))->GetAnexosManifestacaoResult;
        */

        $objInfraParametro = new InfraParametro(BancoSEI::getInstance());
        $arrAnexosAdicionados = array();

        /*echo "<br><br>CHEGOU EM ANEXOS<BR><BR>";
        var_dump($arrAnexosManifestacao);
        echo "<br><br>";*/

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
                        $objAnexoManifestacao->setTipo('R');
                        $objAnexoManifestacao->setIdSerie($idTipoDocumentoAnexoDadosManifestacao);
                        $objAnexoManifestacao->setData(InfraData::getStrDataHoraAtual());
                        $objAnexoManifestacao->setNomeArquivo($strNomeArquivoOriginal);
                        $objAnexoManifestacao->setConteudo(base64_encode(file_get_contents(DIR_SEI_TEMP . '/' . $strNomeArquivoUpload)));

                        array_push($arrAnexosAdicionados, $objAnexoManifestacao);
                        $this->gravarLogLinha($numProtocoloFormatado, $idRelatorioImportacao, 'Arquivo adicionado como anexo: ' . $strNomeArquivoOriginal, 'S');
                    }
                    else
                    {
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
                $this->gravarLogLinha($numProtocoloFormatado, $idRelatorioImportacao, 'Um ou mais documentos anexos n�o foram importados corretamente: ' . $strMensagemErroAnexos, 'S');
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
}

?>
