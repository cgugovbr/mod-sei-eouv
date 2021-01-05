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

        foreach($objListaErros as $erro){

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
        InfraDebug::getInstance()->setBolLigado(true);
        InfraDebug::getInstance()->setBolDebugInfra(false);
        InfraDebug::getInstance()->setBolEcho(false);
        InfraDebug::getInstance()->limpar();

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
        InfraDebug::getInstance()->setBolLigado(true);
        InfraDebug::getInstance()->setBolDebugInfra(true);
        InfraDebug::getInstance()->setBolEcho(false);
        InfraDebug::getInstance()->limpar();

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
        $objEouvParametroDTO -> retTodos();

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
                        break;

                    case "IMPORTAR_DADOS_MANIFESTANTE":
                        $importar_dados_manifestante = $arrObjEouvParametroDTO[$i]->getStrDeValorParametro();
                        break;
                }
            }
        }

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
                $ultimaDataExecucao = $objUltimaExecucao->getDthDthPeriodoFinal();
                $idUltimaExecucao = $objUltimaExecucao->getNumIdRelatorioImportacao();
            } else {
                //Primeira execu��o ou nenhuma executada com sucesso
                $ultimaDataExecucao = $dataInicialImportacaoManifestacoes;
            }

//            $ultimaDataExecucao = '10/11/2020 01:00:00';
//            $dataAtual = '10/11/2020 23:59:00';
            $semManifestacoesEncontradas = true;
            $qtdManifestacoesNovas = 0;
            $qtdManifestacoesAntigas = 0;

            /**
             * A fun��o abaixo gravarLogImportacao recebe o tipo de manifesta��o 'R' (Recursos) para as manifesta��es do e-Sic
             */
            $objEouvRelatorioImportacaoDTO = $this->gravarLogImportacao($ultimaDataExecucao, $dataAtual, 'R');
            $idRelatorioImportacao = $objEouvRelatorioImportacaoDTO->getNumIdRelatorioImportacao();
            $objEouvRelatorioImportacaoRN = new MdCguEouvRelatorioImportacaoRN();
            $SinSucessoExecucao = 'N';
            $textoMensagemErroToken = '';

            $retornoWs = $this->executarServicoConsultaManifestacoes($urlWebServiceEOuv, $token, $ultimaDataExecucao, $dataAtual, null, $idRelatorioImportacao);

            /**
             * Exemplos de Manifesta��es
             */
            // $retornoWs = [["Links"=> [["rel"=>"self","href"=>"https://sistema.ouvidorias.gov.br/api/manifestacoes/2477063"]],"InudPossuiIdentidadePreservada"=>false,"IdManifestacao"=>2477063,"NumerosProtocolo"=>["23546048338202032"],"OuvidoriaDestino"=> ["IdOuvidoria"=>65,"IdOrgaoSiorg"=>100911,"NomeOuvidoria"=>"IFCE � Instituto Federal de Educa��o, Ci�ncia e Tecnologia do Cear�"],"Assunto"=>["IdAssunto"=>731,"DescAssunto"=>"Acesso � informa��o"],"Servico"=>null,"TipoFormulario"=> ["IdTipoFormulario"=>3,"DescTipoFormulario"=>"Acesso � Informa��o"],"TipoManifestacao"=> ["IdTipoManifestacao"=>8,"DescTipoManifestacao"=>"Acesso � Informa��o"],"EmailManifestante"=>"higomeneses@hotmail.com","DataCadastro"=>"02/10/2020","PrazoAtendimento"=>"05/11/2020","Situacao"=>["IdSituacaoManifestacao"=>6,"DescSituacaoManifestacao"=>"Conclu�da"],"ResponsavelAnalise"=>"Ant�nio Jos� Pessoa de Alencar; T�rcio Victor de Oliveira Leal"]];
            // $retornoWs = [["Links"=>[["rel"=>"self","href"=>"https://sistema.ouvidorias.gov.br/api/manifestacoes/2702136"]],"IndPossuiIdentidadePreservada"=>true,"IdManifestacao"=>2702136,"NumerosProtocolo"=>["23546061303202099"],"OuvidoriaDestino"=>["IdOuvidoria"=>65,"IdOrgaoSiorg"=>100911,"NomeOuvidoria"=>"IFCE � Instituto Federal de Educa��o, Ci�ncia e Tecnologia do Cear�"],"Assunto"=>null,"Servico"=>null,"TipoFormulario"=>["IdTipoFormulario"=>3,"DescTipoFormulario"=>"Acesso � Informa��o"],"TipoManifestacao"=>["IdTipoManifestacao"=>8,"DescTipoManifestacao"=>"Acesso � Informa��o"],"EmailManifestante"=>null,"DataCadastro"=>"01/12/2020","PrazoAtendimento"=>"21/12/2020","Situacao"=>["IdSituacaoManifestacao"=>1,"DescSituacaoManifestacao"=>"Cadastrada"],"ResponsavelAnalise"=>""]];

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
                $arrComErro = $this->obterManifestacoesComErro($urlWebServiceEOuv, $token, $ultimaDataExecucao, $dataAtual, $idRelatorioImportacao, 'R');

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
                        if ($retornoWsLinha['TipoManifestacao']['IdTipoManifestacao'] == 8) {
                            $this->executarImportacaoLinha($retornoWsLinha, 'R');
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
         * Detalhamento de exemplos
         */
        // e-Ouv sem anexo - NUP: 23546.057312/2020-85
        //$arrDetalheManifestacao = ["IndRestricaoConteudo"=>true,"ResumoSolicitacao"=>"","Links"=>[["rel"=>"eouv","href"=>"https://sistema.ouvidorias.gov.br/publico/Manifestacao/ServicoDetalharManifestacao?id=+RXkRcJ+K+k=&idsol=+AOAFbvA0/Gyd/H8cmotUB+g9L7hrlDu"]],"IndPossuiIdentidadePreservada"=>null,"IdManifestacao"=>2629725,"NumerosProtocolo"=>["23546057312202085"],"OuvidoriaDestino"=>["IdOuvidoria"=>65,"IdOrgaoSiorg"=>100911,"NomeOuvidoria"=>"IFCE � Instituto Federal de Educa��o, Ci�ncia e Tecnologia do Cear�"],"Assunto"=>["IdAssunto"=>539,"DescAssunto"=>"Den�ncia de irregularidades de servidores"],"Servico"=>null,"TipoFormulario"=>["IdTipoFormulario"=>4,"DescTipoFormulario"=>"Den�ncia"],"TipoManifestacao"=>["IdTipoManifestacao"=>7,"DescTipoManifestacao"=>"Comunica��o"],"EmailManifestante"=>null,"DataCadastro"=>"13/11/2020","PrazoAtendimento"=>null,"Situacao"=>["IdSituacaoManifestacao"=>6,"DescSituacaoManifestacao"=>"Conclu�da"],"ResponsavelAnalise"=>"Ant�nio Jos� Pessoa de Alencar","OrgaoInteresse"=>null,"SubAssunto"=>null,"Tag"=>null,"RegistradoPor"=>"An�nimo","CanalEntrada"=>["IdCanalEntrada"=>13,"DescCanalEntrada"=>"Internet"],"ModoResposta"=>null,"InformacoesAdicionais"=>["Apta"=>"Sim","EnvolveEmpresa"=>"Sim","EnvolveServidorPublico"=>"Sim","EnvolveCargoComissionadoDAS4OuSuperior"=>"Sim"],"dadosExtraJson"=>[],"ObservacoesOuvidoria"=>null,"Teor"=>["DescricaoAtosOuFatos"=>"Sou aluna e gostaria de fazer uma denuncia muito grave  sobre os Professores que n�o est�o dando aula desde o in�cio da Pandemia, as aulas voltaram na forma remota entretanto alguns n�o deram nenhuma aula desde mar�o de 2020, Considero isto um atraso na minha vida de aluna, passei todo semestre anterior sem aula e este que iniciou tamb�m, muitos foram criativos em novas formas de dar aula e outros simplesmente disseram que n�o tem condi��es. dentre eles gostaria que fosse investigado o Prof. Agamenon Goes  e outros do meu departamento da Industria do Campus Fortaleza, n�o deram aula e ficaram todos estes meses recebendo o sal�rio, meu namorado estuda em uma Universidade Privadas que tiveram aula remota mesmo sendo aula pr�tica, procurei no youtube existem muitos v�deos sobre a disciplina que eles d�o aula via v�deo, hoje com todas as ferramentas digitais tem que se esfor�ar para criar novas formas de dar aula. Como se explica isto? muito obrigada ","PropostaMelhoria"=>null,"Anexos"=>[],"LocalFato"=>["Municipio"=>["IdMunicipio"=>230440,"DescMunicipio"=>"Fortaleza","Uf"=>["SigUf"=>"CE","DescUf"=>"CEAR�"]],"DescricaoLocalFato"=>"av treze de maio","GeoReferencia"=>null],"EnvolvidosManifestacao"=>[["IdEnvolvidoManifestacao"=>414184,"Nome"=>"Agamenon Goes","Orgao"=>"IFCE","Funcao"=>"Coordenador(a)","IdFuncaoEnvolvidoManifestacao"=>9]],"CamposAdicionaisManifestacao"=>[]],"Historico"=>[["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Cadastro","DataHoraAcao"=>"13/11/2020 07=>47","Responsavel"=>null,"InformacoesAdicionais"=>"Registro dos dados da manifesta��o por usu�rio An�nimo"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Altera��o de tipo","DataHoraAcao"=>"13/11/2020 07=>47","Responsavel"=>null,"InformacoesAdicionais"=>"Tipo da manifesta��o alterado de Den�ncia para Comunica��o"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Visualiza��o","DataHoraAcao"=>"13/11/2020 09=>36","Responsavel"=>"T�rcio Victor de Oliveira Leal","InformacoesAdicionais"=>"Detalhamento no Fala.BR pelo usu�rio logado"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Visualiza��o","DataHoraAcao"=>"13/11/2020 12=>27","Responsavel"=>null,"InformacoesAdicionais"=>"Detalhamento no Fala.BR atrav�s de link de detalhe por servi�o - webservice_ifce_pro"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Visualiza��o","DataHoraAcao"=>"16/11/2020 12=>34","Responsavel"=>"Ant�nio Jos� Pessoa de Alencar","InformacoesAdicionais"=>"Detalhamento no Fala.BR pelo usu�rio logado"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"An�lise","DataHoraAcao"=>"16/11/2020 12=>35","Responsavel"=>"Ant�nio Jos� Pessoa de Alencar","InformacoesAdicionais"=>"Usu�rio respons�vel pela an�lise=> Ant�nio Jos� Pessoa de Alencar"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Registro Resposta","DataHoraAcao"=>"16/11/2020 12=>36","Responsavel"=>"Ant�nio Jos� Pessoa de Alencar","InformacoesAdicionais"=>"Resposta Conclusiva"],"Resposta"=>["IdRespostaManifestacao"=>2620967,"TipoRespostaManifestacao"=>["IdTipoRespostaManifestacao"=>2,"DescTipoRespostaManifestacao"=>"Resposta Conclusiva"],"TxtResposta"=>"Ol�, em aten��o � manifesta��o, informa-se do envio desta ao Departamento de Correi��o para a tomada de provid�ncia cab�vel. \r\nAtenciosamente, ","RespostaPublicavel"=>false,"Decisao"=>null,"DataCompromisso"=>null]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Visualiza��o","DataHoraAcao"=>"30/11/2020 10=>30","Responsavel"=>"Ant�nio Jos� Pessoa de Alencar","InformacoesAdicionais"=>"Detalhamento no Fala.BR pelo usu�rio logado"]]],"TipoIdentificacaoManifestante"=>["IdTTipoIdentificacaoManifestanteDTO"=>1,"DescTipoIdentificacaoManifestanteDTO"=>"N�o Identificadas (An�nimas)"],"Manifestante"=>null,"IndAcessoRestrito"=>false];
        // e-Ouv COM anexo - NUP: 23546.057312/2020-85
        //$arrDetalheManifestacao = ["IndRestricaoConteudo"=>true,"ResumoSolicitacao"=>"","Links"=>[["rel"=>"eouv","href"=>"https://sistema.ouvidorias.gov.br/publico/Manifestacao/ServicoDetalharManifestacao?id=+RXkRcJ+K+k=&idsol=+AOAFbvA0/Gyd/H8cmotUB+g9L7hrlDu"]],"IndPossuiIdentidadePreservada"=>null,"IdManifestacao"=>2629725,"NumerosProtocolo"=>["23546057312202085"],"OuvidoriaDestino"=>["IdOuvidoria"=>65,"IdOrgaoSiorg"=>100911,"NomeOuvidoria"=>"IFCE � Instituto Federal de Educa��o, Ci�ncia e Tecnologia do Cear�"],"Assunto"=>["IdAssunto"=>539,"DescAssunto"=>"Den�ncia de irregularidades de servidores"],"Servico"=>null,"TipoFormulario"=>["IdTipoFormulario"=>4,"DescTipoFormulario"=>"Den�ncia"],"TipoManifestacao"=>["IdTipoManifestacao"=>7,"DescTipoManifestacao"=>"Comunica��o"],"EmailManifestante"=>null,"DataCadastro"=>"13/11/2020","PrazoAtendimento"=>null,"Situacao"=>["IdSituacaoManifestacao"=>6,"DescSituacaoManifestacao"=>"Conclu�da"],"ResponsavelAnalise"=>"Ant�nio Jos� Pessoa de Alencar","OrgaoInteresse"=>null,"SubAssunto"=>null,"Tag"=>null,"RegistradoPor"=>"An�nimo","CanalEntrada"=>["IdCanalEntrada"=>13,"DescCanalEntrada"=>"Internet"],"ModoResposta"=>null,"InformacoesAdicionais"=>["Apta"=>"Sim","EnvolveEmpresa"=>"Sim","EnvolveServidorPublico"=>"Sim","EnvolveCargoComissionadoDAS4OuSuperior"=>"Sim"],"dadosExtraJson"=>[],"ObservacoesOuvidoria"=>null,"Teor"=>["DescricaoAtosOuFatos"=>"Sou aluna e gostaria de fazer uma denuncia muito grave  sobre os Professores que n�o est�o dando aula desde o in�cio da Pandemia, as aulas voltaram na forma remota entretanto alguns n�o deram nenhuma aula desde mar�o de 2020, Considero isto um atraso na minha vida de aluna, passei todo semestre anterior sem aula e este que iniciou tamb�m, muitos foram criativos em novas formas de dar aula e outros simplesmente disseram que n�o tem condi��es. dentre eles gostaria que fosse investigado o Prof. Agamenon Goes  e outros do meu departamento da Industria do Campus Fortaleza, n�o deram aula e ficaram todos estes meses recebendo o sal�rio, meu namorado estuda em uma Universidade Privadas que tiveram aula remota mesmo sendo aula pr�tica, procurei no youtube existem muitos v�deos sobre a disciplina que eles d�o aula via v�deo, hoje com todas as ferramentas digitais tem que se esfor�ar para criar novas formas de dar aula. Como se explica isto? muito obrigada ","PropostaMelhoria"=>null,"Anexos"=> [["IdAnexoManifestacao"=> 1673462,"NomeArquivo"=> "anexo I_acesso_informacao.pdf","IdObjeto"=> 2531786,"IndComplementar"=> false,"TipoAnexoManifestacao"=> ["IdTipoAnexoManifestacao"=> 2,"DescTipoAnexoManifestacao"=> "Anexo Resposta"],"Links"=> [["rel"=> "self","href"=> "https=>//sistema.ouvidorias.gov.br/api/manifestacoes/2477063/anexos/1673462"]]],["IdAnexoManifestacao"=> 1673463,"NomeArquivo"=> "AnexoII_acesso_informacao.pdf","IdObjeto"=> 2531786,"IndComplementar"=> false,"TipoAnexoManifestacao"=> ["IdTipoAnexoManifestacao"=> 2,"DescTipoAnexoManifestacao"=> "Anexo Resposta"],"Links"=> [["rel"=> "self","href"=> "https=>//sistema.ouvidorias.gov.br/api/manifestacoes/2477063/anexos/1673463"]]]],"LocalFato"=>["Municipio"=>["IdMunicipio"=>230440,"DescMunicipio"=>"Fortaleza","Uf"=>["SigUf"=>"CE","DescUf"=>"CEAR�"]],"DescricaoLocalFato"=>"av treze de maio","GeoReferencia"=>null],"EnvolvidosManifestacao"=>[["IdEnvolvidoManifestacao"=>414184,"Nome"=>"Agamenon Goes","Orgao"=>"IFCE","Funcao"=>"Coordenador(a)","IdFuncaoEnvolvidoManifestacao"=>9]],"CamposAdicionaisManifestacao"=>[]],"Historico"=>[["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Cadastro","DataHoraAcao"=>"13/11/2020 07=>47","Responsavel"=>null,"InformacoesAdicionais"=>"Registro dos dados da manifesta��o por usu�rio An�nimo"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Altera��o de tipo","DataHoraAcao"=>"13/11/2020 07=>47","Responsavel"=>null,"InformacoesAdicionais"=>"Tipo da manifesta��o alterado de Den�ncia para Comunica��o"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Visualiza��o","DataHoraAcao"=>"13/11/2020 09=>36","Responsavel"=>"T�rcio Victor de Oliveira Leal","InformacoesAdicionais"=>"Detalhamento no Fala.BR pelo usu�rio logado"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Visualiza��o","DataHoraAcao"=>"13/11/2020 12=>27","Responsavel"=>null,"InformacoesAdicionais"=>"Detalhamento no Fala.BR atrav�s de link de detalhe por servi�o - webservice_ifce_pro"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Visualiza��o","DataHoraAcao"=>"16/11/2020 12=>34","Responsavel"=>"Ant�nio Jos� Pessoa de Alencar","InformacoesAdicionais"=>"Detalhamento no Fala.BR pelo usu�rio logado"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"An�lise","DataHoraAcao"=>"16/11/2020 12=>35","Responsavel"=>"Ant�nio Jos� Pessoa de Alencar","InformacoesAdicionais"=>"Usu�rio respons�vel pela an�lise=> Ant�nio Jos� Pessoa de Alencar"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Registro Resposta","DataHoraAcao"=>"16/11/2020 12=>36","Responsavel"=>"Ant�nio Jos� Pessoa de Alencar","InformacoesAdicionais"=>"Resposta Conclusiva"],"Resposta"=>["IdRespostaManifestacao"=>2620967,"TipoRespostaManifestacao"=>["IdTipoRespostaManifestacao"=>2,"DescTipoRespostaManifestacao"=>"Resposta Conclusiva"],"TxtResposta"=>"Ol�, em aten��o � manifesta��o, informa-se do envio desta ao Departamento de Correi��o para a tomada de provid�ncia cab�vel. \r\nAtenciosamente, ","RespostaPublicavel"=>false,"Decisao"=>null,"DataCompromisso"=>null]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Visualiza��o","DataHoraAcao"=>"30/11/2020 10=>30","Responsavel"=>"Ant�nio Jos� Pessoa de Alencar","InformacoesAdicionais"=>"Detalhamento no Fala.BR pelo usu�rio logado"]]],"TipoIdentificacaoManifestante"=>["IdTTipoIdentificacaoManifestanteDTO"=>1,"DescTipoIdentificacaoManifestanteDTO"=>"N�o Identificadas (An�nimas)"],"Manifestante"=>null,"IndAcessoRestrito"=>false];

        // e-Sic sem anexo - NUP: 23546.048338/2020-32
        // $arrDetalheManifestacao = ["IndRestricaoConteudo"=>true,"ResumoSolicitacao"=>"Acesso a informa��o","Links"=>[["rel"=>"eouv","href"=>"https://sistema.ouvidorias.gov.br/publico/Manifestacao/ServicoDetalharManifestacao?id=9J8+4T95pjY=&idsol=+AOAFbvA0/Gyd/H8cmotUB+g9L7hrlDu"]],"IndPossuiIdentidadePreservada"=>false,"IdManifestacao"=>2477063,"NumerosProtocolo"=>["23546048338202032"],"OuvidoriaDestino"=>["IdOuvidoria"=>65,"IdOrgaoSiorg"=>100911,"NomeOuvidoria"=>"IFCE � Instituto Federal de Educa��o, Ci�ncia e Tecnologia do Cear�"],"Assunto"=>["IdAssunto"=>731,"DescAssunto"=>"Acesso � informa��o"],"Servico"=>null,"TipoFormulario"=>["IdTipoFormulario"=>3,"DescTipoFormulario"=>"Acesso � Informa��o"],"TipoManifestacao"=>["IdTipoManifestacao"=>8,"DescTipoManifestacao"=>"Acesso � Informa��o"],"EmailManifestante"=>"higomeneses@hotmail.com","DataCadastro"=>"02/10/2020","PrazoAtendimento"=>"05/11/2020","Situacao"=>["IdSituacaoManifestacao"=>6,"DescSituacaoManifestacao"=>"Conclu�da"],"ResponsavelAnalise"=>"Ant�nio Jos� Pessoa de Alencar; T�rcio Victor de Oliveira Leal","OrgaoInteresse"=>null,"SubAssunto"=>null,"Tag"=>null,"RegistradoPor"=>"HIGO CARLOS MENESES DE SOUSA","CanalEntrada"=>["IdCanalEntrada"=>13,"DescCanalEntrada"=>"Internet"],"ModoResposta"=>["IdModoResposta"=>1,"DescModoResposta"=>"Pelo sistema (com avisos por email)","IndModoRespostaAtivo"=>"S"],"InformacoesAdicionais"=>["Apta"=>"","EnvolveEmpresa"=>"","EnvolveServidorPublico"=>"","EnvolveCargoComissionadoDAS4OuSuperior"=>""],"dadosExtraJson"=>[],"ObservacoesOuvidoria"=>null,"Teor"=>["DescricaoAtosOuFatos"=>"Solicito ao Instituto Federal do Cear� a lota��o atual dos professores de Hist�ria (efetivos e substitutos) de cada campus, constando a data de admiss�o no IF, carga hor�ria e situa��o do servidor (ativo, licen�a, processo de aposentadoria, cargo de dire��o ). Al�m disso, solicito tamb�m a previs�o de necessidades da disciplina para os pr�ximos dois anos (2021 e 2022), Gostaria de saber tamb�m se h� previs�o de edital de remo��o para 2021 e 2022. Por fim solicito informa��o se h� pedidos de redistribui��o da disciplina de Hist�ria junto ao IF e se h� previs�o de aposentadoria de professores da �rea Hist�ria at� 2022. Deixo os votos de estima e considera��o.","PropostaMelhoria"=>null,"Anexos"=>[["IdAnexoManifestacao"=>1673462,"NomeArquivo"=>"anexo I_acesso_informacao.pdf","IdObjeto"=>2531786,"IndComplementar"=>false,"TipoAnexoManifestacao"=>["IdTipoAnexoManifestacao"=>2,"DescTipoAnexoManifestacao"=>"Anexo Resposta"],"Links"=>[["rel"=>"self","href"=>"https://sistema.ouvidorias.gov.br/api/manifestacoes/2477063/anexos/1673462"]]],["IdAnexoManifestacao"=>1673463,"NomeArquivo"=>"AnexoII_acesso_informacao.pdf","IdObjeto"=>2531786,"IndComplementar"=>false,"TipoAnexoManifestacao"=>["IdTipoAnexoManifestacao"=>2,"DescTipoAnexoManifestacao"=>"Anexo Resposta"],"Links"=>[["rel"=>"self","href"=>"https://sistema.ouvidorias.gov.br/api/manifestacoes/2477063/anexos/1673463"]]]],"LocalFato"=>["Municipio"=>null,"DescricaoLocalFato"=>"","GeoReferencia"=>null],"EnvolvidosManifestacao"=>[],"CamposAdicionaisManifestacao"=>[]],"Historico"=>[["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Cadastro","DataHoraAcao"=>"02/10/2020 18=>47","Responsavel"=>"HIGO CARLOS MENESES DE SOUSA","InformacoesAdicionais"=>"Registro dos dados da manifesta��o"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Visualiza��o","DataHoraAcao"=>"05/10/2020 09=>56","Responsavel"=>"T�rcio Victor de Oliveira Leal","InformacoesAdicionais"=>"Detalhamento no Fala.BR pelo usu�rio logado"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Visualiza��o","DataHoraAcao"=>"20/10/2020 09=>44","Responsavel"=>"T�rcio Victor de Oliveira Leal","InformacoesAdicionais"=>"Detalhamento no Fala.BR pelo usu�rio logado"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Visualiza��o","DataHoraAcao"=>"26/10/2020 09=>37","Responsavel"=>"Ant�nio Jos� Pessoa de Alencar","InformacoesAdicionais"=>"Detalhamento no Fala.BR pelo usu�rio logado"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Visualiza��o","DataHoraAcao"=>"26/10/2020 09=>55","Responsavel"=>"T�rcio Victor de Oliveira Leal","InformacoesAdicionais"=>"Detalhamento no Fala.BR pelo usu�rio logado"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"An�lise","DataHoraAcao"=>"26/10/2020 09=>59","Responsavel"=>"T�rcio Victor de Oliveira Leal","InformacoesAdicionais"=>"Usu�rio respons�vel pela an�lise=> T�rcio Victor de Oliveira Leal"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Visualiza��o","DataHoraAcao"=>"26/10/2020 10=>22","Responsavel"=>"Ant�nio Jos� Pessoa de Alencar","InformacoesAdicionais"=>"Detalhamento no Fala.BR pelo usu�rio logado"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"An�lise","DataHoraAcao"=>"26/10/2020 10=>39","Responsavel"=>"Ant�nio Jos� Pessoa de Alencar","InformacoesAdicionais"=>"Usu�rio respons�vel pela an�lise=> Ant�nio Jos� Pessoa de Alencar"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Prorroga��o","DataHoraAcao"=>"26/10/2020 10=>40","Responsavel"=>"Ant�nio Jos� Pessoa de Alencar","InformacoesAdicionais"=>"Resposta de manifesta��o prorrogada de 26/10/2020 para 05/11/2020"],"Prorrogacao"=>["MotivoProrrogacaoManifestacao"=>["IdMotivoProrrogacaoManifestacao"=>4,"DescMotivoProrrogacaoManifestacao"=>"Outros motivos","TiposFormulario"=>[["IdTipoFormulario"=>1,"DescTipoFormulario"=>"Padr�o"],["IdTipoFormulario"=>2,"DescTipoFormulario"=>"Simplifique"],["IdTipoFormulario"=>3,"DescTipoFormulario"=>"Acesso � Informa��o"],["IdTipoFormulario"=>4,"DescTipoFormulario"=>"Den�ncia"]]],"TxtJustificativaProrrogacao"=>"Aguardando manifesta��o dos �rg�os t�cnicos respons�veis. ","PrazoOriginal"=>"26/10/2020","NovoPrazo"=>"05/11/2020","IndAutomatica"=>false]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Visualiza��o","DataHoraAcao"=>"03/11/2020 10=>07","Responsavel"=>"Ant�nio Jos� Pessoa de Alencar","InformacoesAdicionais"=>"Detalhamento no Fala.BR pelo usu�rio logado"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Visualiza��o","DataHoraAcao"=>"05/11/2020 09=>52","Responsavel"=>"T�rcio Victor de Oliveira Leal","InformacoesAdicionais"=>"Detalhamento no Fala.BR pelo usu�rio logado"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Visualiza��o","DataHoraAcao"=>"05/11/2020 19=>46","Responsavel"=>"Ant�nio Jos� Pessoa de Alencar","InformacoesAdicionais"=>"Detalhamento no Fala.BR pelo usu�rio logado"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Cadastro","DataHoraAcao"=>"05/11/2020 19=>50","Responsavel"=>"Ant�nio Jos� Pessoa de Alencar","InformacoesAdicionais"=>"Altera��o do assunto da manifesta��o"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Registro Resposta","DataHoraAcao"=>"05/11/2020 19=>50","Responsavel"=>"Ant�nio Jos� Pessoa de Alencar","InformacoesAdicionais"=>"Resposta Conclusiva"],"Resposta"=>["IdRespostaManifestacao"=>2531786,"TipoRespostaManifestacao"=>["IdTipoRespostaManifestacao"=>2,"DescTipoRespostaManifestacao"=>"Resposta Conclusiva"],"TxtResposta"=>"Ol�, boa noite!!\r\n\r\nEm aten��o � Solicita��o de Acesso � Informa��o, a Assist�ncia da Pr�-reitoria de Ensino envia a seguinte resposta=> � Envia-se (anexo I) a listagem de professores que ministraram disciplinas de hist�ria no IFCE com o somat�rio da carga hor�ria das disciplinas nos �ltimos tr�s semestres. Tendo em vista a pandemia e o ensino remoto n�o temos previs�o da oferta de disciplinas de hist�ria para os pr�ximos anos�. \r\nJ� a Assist�ncia da Pr�-reitoria de Gest�o de Pessoas, por sua vez, informa que=> �considerando a  rela��o de servidores que trabalham com a sub�rea de Hist�ria, encaminhamos a Planilha (anexo II) na qual consta a data de efetivo exerc�cio, bem como o campus de lota��o dos servidores.\r\nOs servidores relacionados no anexo II, todos s�o do ativo permanente e nenhum est� de licen�a ou solicitando aposentadoria.\r\nEm se tratando da previs�o das necessidades da disciplina para os pr�ximos dois anos (2021 e 2022), esclarecemos que essa � uma demanda que cabe aos campi, uma vez que estes t�m autonomia did�tica para definir quais as sub�reas necess�rias.\r\nQuanto � publica��o de edital de remo��o esclarecemos que a PROGEP n�o tem previs�o para publica��o. No entanto, � oportuno esclarecer que caso haja necessidade de provimento para a sub�rea de Hist�ria, os campi encaminham as demandas para Pr�-Reitoria de Ensino e, tendo disponibilidade or�ament�ria para provimentos, as vagas s�o ofertadas em remo��o. \r\n� oportuno esclarecer, ainda, que em rela��o � aposentadoria, mesmo que venha a se aposentar um servidor da sub�rea de Hist�ria, a aposentadoria � do cargo de Professor EBTT. Quando do provimento, o campus tem autonomia para definir qual a sub�rea de maior necessidade, considerando os cursos ofertados e o Banco de Professor Equivalente do campus�. \r\n\r\nAtenciosamente, \r\n","RespostaPublicavel"=>false,"Decisao"=>["codigoDecisao"=>5,"descricaoDecisao"=>"Acesso Concedido"],"DataCompromisso"=>null]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Visualiza��o","DataHoraAcao"=>"05/11/2020 19=>55","Responsavel"=>"HIGO CARLOS MENESES DE SOUSA","InformacoesAdicionais"=>"Detalhamento no Fala.BR pelo usu�rio logado"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Visualiza��o","DataHoraAcao"=>"05/11/2020 20=>02","Responsavel"=>"HIGO CARLOS MENESES DE SOUSA","InformacoesAdicionais"=>"Detalhamento no Fala.BR pelo usu�rio logado"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Visualiza��o","DataHoraAcao"=>"05/11/2020 21=>10","Responsavel"=>"HIGO CARLOS MENESES DE SOUSA","InformacoesAdicionais"=>"Detalhamento no Fala.BR pelo usu�rio logado"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Registro de Recurso","DataHoraAcao"=>"05/11/2020 21=>11","Responsavel"=>"HIGO CARLOS MENESES DE SOUSA","InformacoesAdicionais"=>"Registro de recurso de 1� Inst�ncia"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Visualiza��o","DataHoraAcao"=>"06/11/2020 08=>19","Responsavel"=>null,"InformacoesAdicionais"=>"Detalhamento no Fala.BR pelo acesso a link enviado ao manifestante"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Visualiza��o","DataHoraAcao"=>"06/11/2020 08=>27","Responsavel"=>"T�rcio Victor de Oliveira Leal","InformacoesAdicionais"=>"Detalhamento no Fala.BR pelo usu�rio logado"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Visualiza��o","DataHoraAcao"=>"06/11/2020 08=>51","Responsavel"=>"Ant�nio Jos� Pessoa de Alencar","InformacoesAdicionais"=>"Detalhamento no Fala.BR pelo usu�rio logado"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Visualiza��o","DataHoraAcao"=>"06/11/2020 08=>52","Responsavel"=>"Ant�nio Jos� Pessoa de Alencar","InformacoesAdicionais"=>"Detalhamento no Fala.BR pelo usu�rio logado"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Visualiza��o","DataHoraAcao"=>"10/11/2020 10=>58","Responsavel"=>"T�rcio Victor de Oliveira Leal","InformacoesAdicionais"=>"Detalhamento no Fala.BR pelo usu�rio logado"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Visualiza��o","DataHoraAcao"=>"10/11/2020 10=>58","Responsavel"=>"T�rcio Victor de Oliveira Leal","InformacoesAdicionais"=>"Detalhamento no Fala.BR pelo usu�rio logado"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Visualiza��o","DataHoraAcao"=>"12/11/2020 15=>38","Responsavel"=>"HIGO CARLOS MENESES DE SOUSA","InformacoesAdicionais"=>"Detalhamento no Fala.BR pelo usu�rio logado"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Visualiza��o","DataHoraAcao"=>"13/11/2020 10=>06","Responsavel"=>"T�rcio Victor de Oliveira Leal","InformacoesAdicionais"=>"Detalhamento no Fala.BR pelo usu�rio logado"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Visualiza��o","DataHoraAcao"=>"13/11/2020 14=>35","Responsavel"=>"Ant�nio Jos� Pessoa de Alencar","InformacoesAdicionais"=>"Detalhamento no Fala.BR pelo usu�rio logado"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Registro Resposta Recurso","DataHoraAcao"=>"13/11/2020 14=>40","Responsavel"=>"Ant�nio Jos� Pessoa de Alencar","InformacoesAdicionais"=>"Resposta de recurso de 1� inst�ncia"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Visualiza��o","DataHoraAcao"=>"13/11/2020 17=>00","Responsavel"=>"HIGO CARLOS MENESES DE SOUSA","InformacoesAdicionais"=>"Detalhamento no Fala.BR pelo usu�rio logado"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Visualiza��o","DataHoraAcao"=>"13/11/2020 17=>00","Responsavel"=>"HIGO CARLOS MENESES DE SOUSA","InformacoesAdicionais"=>"Detalhamento no Fala.BR pelo usu�rio logado"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Visualiza��o","DataHoraAcao"=>"17/11/2020 10=>40","Responsavel"=>"HIGO CARLOS MENESES DE SOUSA","InformacoesAdicionais"=>"Detalhamento no Fala.BR pelo usu�rio logado"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Visualiza��o","DataHoraAcao"=>"17/11/2020 10=>40","Responsavel"=>"HIGO CARLOS MENESES DE SOUSA","InformacoesAdicionais"=>"Detalhamento no Fala.BR pelo usu�rio logado"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Visualiza��o","DataHoraAcao"=>"17/11/2020 10=>41","Responsavel"=>"HIGO CARLOS MENESES DE SOUSA","InformacoesAdicionais"=>"Detalhamento no Fala.BR pelo usu�rio logado"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Visualiza��o","DataHoraAcao"=>"17/11/2020 10=>41","Responsavel"=>"HIGO CARLOS MENESES DE SOUSA","InformacoesAdicionais"=>"Detalhamento no Fala.BR pelo usu�rio logado"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Visualiza��o","DataHoraAcao"=>"17/11/2020 11=>18","Responsavel"=>"HIGO CARLOS MENESES DE SOUSA","InformacoesAdicionais"=>"Detalhamento no Fala.BR pelo usu�rio logado"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Visualiza��o","DataHoraAcao"=>"17/11/2020 16=>49","Responsavel"=>"HIGO CARLOS MENESES DE SOUSA","InformacoesAdicionais"=>"Detalhamento no Fala.BR pelo usu�rio logado"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Visualiza��o","DataHoraAcao"=>"23/11/2020 18=>58","Responsavel"=>"Webservice IFCE Produ��o","InformacoesAdicionais"=>"Detalhamento no Fala.BR pelo usu�rio logado"]]],"TipoIdentificacaoManifestante"=>["IdTTipoIdentificacaoManifestanteDTO"=>4,"DescTipoIdentificacaoManifestanteDTO"=>"Identificadas sem Restri��o"],"Manifestante"=>["TipoPessoa"=>["IdTipoPessoa"=>1,"DescTipoPessoa"=>"Pessoa F�sica"],"Pais"=>["IdPais"=>1058,"Descricao"=>"Brasil"],"TipoDocumentoIdentificacao"=>["IdTipoDocumentoIdentificacao"=>1,"DescTipoDocumentoIdentificacao"=>"CPF","IndAtivo"=>true],"NumeroDocumentoIdentificacao"=>"02186515385","Nome"=>"HIGO CARLOS MENESES DE SOUSA","Email"=>"higomeneses@hotmail.com","DataNascimento"=>"1989-02-18T00=>00=>00","Telefone"=>["Numero"=>"999711202","ddd"=>"89"],"Endereco"=>["CEP"=>"64607-760","Municipio"=>["IdMunicipio"=>220800,"DescMunicipio"=>"Picos","Uf"=>["SigUf"=>"PI","DescUf"=>"PIAU�"]],"Logradouro"=>"Avenida Senador Helv�dio Nunes, 0, JUNCO","Numero"=>null,"Complemento"=>null,"Bairro"=>null],"genero"=>"M","FaixaEtaria"=>null,"corRa�a"=>null,"Profissao"=>["IdProfissao"=>9,"DescProfissao"=>"Professor"],"Escolaridade"=>["IdEscolaridade"=>5,"Descricao"=>"P�s-gradua��o"],"RazaoSocial"=>null,"TipoInstituicao"=>null,"AreaAtuacao"=>null,"NomeRepresentante"=>null,"CargoRepresentante"=>null,"EmailRepresentante"=>null],"IndAcessoRestrito"=>false];
        // e-Sic sem anexo - NUP: 23546.048338/2020-32 - Segunda Inst�ncia
        // $arrDetalheManifestacao = ["IndRestricaoConteudo"=>true,"ResumoSolicitacao"=>"Acesso a informa��o","Links"=>[["rel"=>"eouv","href"=>"https://sistema.ouvidorias.gov.br/publico/Manifestacao/ServicoDetalharManifestacao?id=9J8+4T95pjY=&idsol=+AOAFbvA0/Gyd/H8cmotUB+g9L7hrlDu"]],"IndPossuiIdentidadePreservada"=>false,"IdManifestacao"=>2477063,"NumerosProtocolo"=>["23546048338202032"],"OuvidoriaDestino"=>["IdOuvidoria"=>65,"IdOrgaoSiorg"=>100911,"NomeOuvidoria"=>"IFCE � Instituto Federal de Educa��o, Ci�ncia e Tecnologia do Cear�"],"Assunto"=>["IdAssunto"=>731,"DescAssunto"=>"Acesso � informa��o"],"Servico"=>null,"TipoFormulario"=>["IdTipoFormulario"=>3,"DescTipoFormulario"=>"Acesso � Informa��o"],"TipoManifestacao"=>["IdTipoManifestacao"=>8,"DescTipoManifestacao"=>"Acesso � Informa��o"],"EmailManifestante"=>"higomeneses@hotmail.com","DataCadastro"=>"02/10/2020","PrazoAtendimento"=>"05/11/2020","Situacao"=>["IdSituacaoManifestacao"=>6,"DescSituacaoManifestacao"=>"Conclu�da"],"ResponsavelAnalise"=>"Ant�nio Jos� Pessoa de Alencar; T�rcio Victor de Oliveira Leal","OrgaoInteresse"=>null,"SubAssunto"=>null,"Tag"=>null,"RegistradoPor"=>"HIGO CARLOS MENESES DE SOUSA","CanalEntrada"=>["IdCanalEntrada"=>13,"DescCanalEntrada"=>"Internet"],"ModoResposta"=>["IdModoResposta"=>1,"DescModoResposta"=>"Pelo sistema (com avisos por email)","IndModoRespostaAtivo"=>"S"],"InformacoesAdicionais"=>["Apta"=>"","EnvolveEmpresa"=>"","EnvolveServidorPublico"=>"","EnvolveCargoComissionadoDAS4OuSuperior"=>""],"dadosExtraJson"=>[],"ObservacoesOuvidoria"=>null,"Teor"=>["DescricaoAtosOuFatos"=>"Solicito ao Instituto Federal do Cear� a lota��o atual dos professores de Hist�ria (efetivos e substitutos) de cada campus, constando a data de admiss�o no IF, carga hor�ria e situa��o do servidor (ativo, licen�a, processo de aposentadoria, cargo de dire��o ). Al�m disso, solicito tamb�m a previs�o de necessidades da disciplina para os pr�ximos dois anos (2021 e 2022), Gostaria de saber tamb�m se h� previs�o de edital de remo��o para 2021 e 2022. Por fim solicito informa��o se h� pedidos de redistribui��o da disciplina de Hist�ria junto ao IF e se h� previs�o de aposentadoria de professores da �rea Hist�ria at� 2022. Deixo os votos de estima e considera��o.","PropostaMelhoria"=>null,"Anexos"=>[["IdAnexoManifestacao"=>1673462,"NomeArquivo"=>"anexo I_acesso_informacao.pdf","IdObjeto"=>2531786,"IndComplementar"=>false,"TipoAnexoManifestacao"=>["IdTipoAnexoManifestacao"=>2,"DescTipoAnexoManifestacao"=>"Anexo Resposta"],"Links"=>[["rel"=>"self","href"=>"https://sistema.ouvidorias.gov.br/api/manifestacoes/2477063/anexos/1673462"]]],["IdAnexoManifestacao"=>1673463,"NomeArquivo"=>"AnexoII_acesso_informacao.pdf","IdObjeto"=>2531786,"IndComplementar"=>false,"TipoAnexoManifestacao"=>["IdTipoAnexoManifestacao"=>2,"DescTipoAnexoManifestacao"=>"Anexo Resposta"],"Links"=>[["rel"=>"self","href"=>"https://sistema.ouvidorias.gov.br/api/manifestacoes/2477063/anexos/1673463"]]]],"LocalFato"=>["Municipio"=>null,"DescricaoLocalFato"=>"","GeoReferencia"=>null],"EnvolvidosManifestacao"=>[],"CamposAdicionaisManifestacao"=>[]],"Historico"=>[["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Cadastro","DataHoraAcao"=>"02/10/2020 18=>47","Responsavel"=>"HIGO CARLOS MENESES DE SOUSA","InformacoesAdicionais"=>"Registro dos dados da manifesta��o"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Visualiza��o","DataHoraAcao"=>"05/10/2020 09=>56","Responsavel"=>"T�rcio Victor de Oliveira Leal","InformacoesAdicionais"=>"Detalhamento no Fala.BR pelo usu�rio logado"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Visualiza��o","DataHoraAcao"=>"20/10/2020 09=>44","Responsavel"=>"T�rcio Victor de Oliveira Leal","InformacoesAdicionais"=>"Detalhamento no Fala.BR pelo usu�rio logado"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Visualiza��o","DataHoraAcao"=>"26/10/2020 09=>37","Responsavel"=>"Ant�nio Jos� Pessoa de Alencar","InformacoesAdicionais"=>"Detalhamento no Fala.BR pelo usu�rio logado"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Visualiza��o","DataHoraAcao"=>"26/10/2020 09=>55","Responsavel"=>"T�rcio Victor de Oliveira Leal","InformacoesAdicionais"=>"Detalhamento no Fala.BR pelo usu�rio logado"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"An�lise","DataHoraAcao"=>"26/10/2020 09=>59","Responsavel"=>"T�rcio Victor de Oliveira Leal","InformacoesAdicionais"=>"Usu�rio respons�vel pela an�lise=> T�rcio Victor de Oliveira Leal"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Visualiza��o","DataHoraAcao"=>"26/10/2020 10=>22","Responsavel"=>"Ant�nio Jos� Pessoa de Alencar","InformacoesAdicionais"=>"Detalhamento no Fala.BR pelo usu�rio logado"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"An�lise","DataHoraAcao"=>"26/10/2020 10=>39","Responsavel"=>"Ant�nio Jos� Pessoa de Alencar","InformacoesAdicionais"=>"Usu�rio respons�vel pela an�lise=> Ant�nio Jos� Pessoa de Alencar"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Prorroga��o","DataHoraAcao"=>"26/10/2020 10=>40","Responsavel"=>"Ant�nio Jos� Pessoa de Alencar","InformacoesAdicionais"=>"Resposta de manifesta��o prorrogada de 26/10/2020 para 05/11/2020"],"Prorrogacao"=>["MotivoProrrogacaoManifestacao"=>["IdMotivoProrrogacaoManifestacao"=>4,"DescMotivoProrrogacaoManifestacao"=>"Outros motivos","TiposFormulario"=>[["IdTipoFormulario"=>1,"DescTipoFormulario"=>"Padr�o"],["IdTipoFormulario"=>2,"DescTipoFormulario"=>"Simplifique"],["IdTipoFormulario"=>3,"DescTipoFormulario"=>"Acesso � Informa��o"],["IdTipoFormulario"=>4,"DescTipoFormulario"=>"Den�ncia"]]],"TxtJustificativaProrrogacao"=>"Aguardando manifesta��o dos �rg�os t�cnicos respons�veis. ","PrazoOriginal"=>"26/10/2020","NovoPrazo"=>"05/11/2020","IndAutomatica"=>false]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Visualiza��o","DataHoraAcao"=>"03/11/2020 10=>07","Responsavel"=>"Ant�nio Jos� Pessoa de Alencar","InformacoesAdicionais"=>"Detalhamento no Fala.BR pelo usu�rio logado"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Visualiza��o","DataHoraAcao"=>"05/11/2020 09=>52","Responsavel"=>"T�rcio Victor de Oliveira Leal","InformacoesAdicionais"=>"Detalhamento no Fala.BR pelo usu�rio logado"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Visualiza��o","DataHoraAcao"=>"05/11/2020 19=>46","Responsavel"=>"Ant�nio Jos� Pessoa de Alencar","InformacoesAdicionais"=>"Detalhamento no Fala.BR pelo usu�rio logado"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Cadastro","DataHoraAcao"=>"05/11/2020 19=>50","Responsavel"=>"Ant�nio Jos� Pessoa de Alencar","InformacoesAdicionais"=>"Altera��o do assunto da manifesta��o"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Registro Resposta","DataHoraAcao"=>"05/11/2020 19=>50","Responsavel"=>"Ant�nio Jos� Pessoa de Alencar","InformacoesAdicionais"=>"Resposta Conclusiva"],"Resposta"=>["IdRespostaManifestacao"=>2531786,"TipoRespostaManifestacao"=>["IdTipoRespostaManifestacao"=>2,"DescTipoRespostaManifestacao"=>"Resposta Conclusiva"],"TxtResposta"=>"Ol�, boa noite!!\r\n\r\nEm aten��o � Solicita��o de Acesso � Informa��o, a Assist�ncia da Pr�-reitoria de Ensino envia a seguinte resposta=> � Envia-se (anexo I) a listagem de professores que ministraram disciplinas de hist�ria no IFCE com o somat�rio da carga hor�ria das disciplinas nos �ltimos tr�s semestres. Tendo em vista a pandemia e o ensino remoto n�o temos previs�o da oferta de disciplinas de hist�ria para os pr�ximos anos�. \r\nJ� a Assist�ncia da Pr�-reitoria de Gest�o de Pessoas, por sua vez, informa que=> �considerando a  rela��o de servidores que trabalham com a sub�rea de Hist�ria, encaminhamos a Planilha (anexo II) na qual consta a data de efetivo exerc�cio, bem como o campus de lota��o dos servidores.\r\nOs servidores relacionados no anexo II, todos s�o do ativo permanente e nenhum est� de licen�a ou solicitando aposentadoria.\r\nEm se tratando da previs�o das necessidades da disciplina para os pr�ximos dois anos (2021 e 2022), esclarecemos que essa � uma demanda que cabe aos campi, uma vez que estes t�m autonomia did�tica para definir quais as sub�reas necess�rias.\r\nQuanto � publica��o de edital de remo��o esclarecemos que a PROGEP n�o tem previs�o para publica��o. No entanto, � oportuno esclarecer que caso haja necessidade de provimento para a sub�rea de Hist�ria, os campi encaminham as demandas para Pr�-Reitoria de Ensino e, tendo disponibilidade or�ament�ria para provimentos, as vagas s�o ofertadas em remo��o. \r\n� oportuno esclarecer, ainda, que em rela��o � aposentadoria, mesmo que venha a se aposentar um servidor da sub�rea de Hist�ria, a aposentadoria � do cargo de Professor EBTT. Quando do provimento, o campus tem autonomia para definir qual a sub�rea de maior necessidade, considerando os cursos ofertados e o Banco de Professor Equivalente do campus�. \r\n\r\nAtenciosamente, \r\n","RespostaPublicavel"=>false,"Decisao"=>["codigoDecisao"=>5,"descricaoDecisao"=>"Acesso Concedido"],"DataCompromisso"=>null]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Visualiza��o","DataHoraAcao"=>"05/11/2020 19=>55","Responsavel"=>"HIGO CARLOS MENESES DE SOUSA","InformacoesAdicionais"=>"Detalhamento no Fala.BR pelo usu�rio logado"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Visualiza��o","DataHoraAcao"=>"05/11/2020 20=>02","Responsavel"=>"HIGO CARLOS MENESES DE SOUSA","InformacoesAdicionais"=>"Detalhamento no Fala.BR pelo usu�rio logado"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Visualiza��o","DataHoraAcao"=>"05/11/2020 21=>10","Responsavel"=>"HIGO CARLOS MENESES DE SOUSA","InformacoesAdicionais"=>"Detalhamento no Fala.BR pelo usu�rio logado"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Registro de Recurso","DataHoraAcao"=>"05/11/2020 21=>11","Responsavel"=>"HIGO CARLOS MENESES DE SOUSA","InformacoesAdicionais"=>"Registro de recurso de 1� Inst�ncia"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Visualiza��o","DataHoraAcao"=>"06/11/2020 08=>19","Responsavel"=>null,"InformacoesAdicionais"=>"Detalhamento no Fala.BR pelo acesso a link enviado ao manifestante"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Visualiza��o","DataHoraAcao"=>"06/11/2020 08=>27","Responsavel"=>"T�rcio Victor de Oliveira Leal","InformacoesAdicionais"=>"Detalhamento no Fala.BR pelo usu�rio logado"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Visualiza��o","DataHoraAcao"=>"06/11/2020 08=>51","Responsavel"=>"Ant�nio Jos� Pessoa de Alencar","InformacoesAdicionais"=>"Detalhamento no Fala.BR pelo usu�rio logado"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Visualiza��o","DataHoraAcao"=>"06/11/2020 08=>52","Responsavel"=>"Ant�nio Jos� Pessoa de Alencar","InformacoesAdicionais"=>"Detalhamento no Fala.BR pelo usu�rio logado"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Visualiza��o","DataHoraAcao"=>"10/11/2020 10=>58","Responsavel"=>"T�rcio Victor de Oliveira Leal","InformacoesAdicionais"=>"Detalhamento no Fala.BR pelo usu�rio logado"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Visualiza��o","DataHoraAcao"=>"10/11/2020 10=>58","Responsavel"=>"T�rcio Victor de Oliveira Leal","InformacoesAdicionais"=>"Detalhamento no Fala.BR pelo usu�rio logado"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Visualiza��o","DataHoraAcao"=>"12/11/2020 15=>38","Responsavel"=>"HIGO CARLOS MENESES DE SOUSA","InformacoesAdicionais"=>"Detalhamento no Fala.BR pelo usu�rio logado"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Visualiza��o","DataHoraAcao"=>"13/11/2020 10=>06","Responsavel"=>"T�rcio Victor de Oliveira Leal","InformacoesAdicionais"=>"Detalhamento no Fala.BR pelo usu�rio logado"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Visualiza��o","DataHoraAcao"=>"13/11/2020 14=>35","Responsavel"=>"Ant�nio Jos� Pessoa de Alencar","InformacoesAdicionais"=>"Detalhamento no Fala.BR pelo usu�rio logado"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Registro Resposta Recurso","DataHoraAcao"=>"13/11/2020 14=>40","Responsavel"=>"Ant�nio Jos� Pessoa de Alencar","InformacoesAdicionais"=>"Resposta de recurso de 1� inst�ncia"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Visualiza��o","DataHoraAcao"=>"13/11/2020 17=>00","Responsavel"=>"HIGO CARLOS MENESES DE SOUSA","InformacoesAdicionais"=>"Detalhamento no Fala.BR pelo usu�rio logado"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Visualiza��o","DataHoraAcao"=>"13/11/2020 17=>00","Responsavel"=>"HIGO CARLOS MENESES DE SOUSA","InformacoesAdicionais"=>"Detalhamento no Fala.BR pelo usu�rio logado"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Visualiza��o","DataHoraAcao"=>"17/11/2020 10=>40","Responsavel"=>"HIGO CARLOS MENESES DE SOUSA","InformacoesAdicionais"=>"Detalhamento no Fala.BR pelo usu�rio logado"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Visualiza��o","DataHoraAcao"=>"17/11/2020 10=>40","Responsavel"=>"HIGO CARLOS MENESES DE SOUSA","InformacoesAdicionais"=>"Detalhamento no Fala.BR pelo usu�rio logado"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Visualiza��o","DataHoraAcao"=>"17/11/2020 10=>41","Responsavel"=>"HIGO CARLOS MENESES DE SOUSA","InformacoesAdicionais"=>"Detalhamento no Fala.BR pelo usu�rio logado"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Visualiza��o","DataHoraAcao"=>"17/11/2020 10=>41","Responsavel"=>"HIGO CARLOS MENESES DE SOUSA","InformacoesAdicionais"=>"Detalhamento no Fala.BR pelo usu�rio logado"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Visualiza��o","DataHoraAcao"=>"17/11/2020 11=>18","Responsavel"=>"HIGO CARLOS MENESES DE SOUSA","InformacoesAdicionais"=>"Detalhamento no Fala.BR pelo usu�rio logado"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Visualiza��o","DataHoraAcao"=>"17/11/2020 16=>49","Responsavel"=>"HIGO CARLOS MENESES DE SOUSA","InformacoesAdicionais"=>"Detalhamento no Fala.BR pelo usu�rio logado"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Visualiza��o","DataHoraAcao"=>"23/11/2020 18=>58","Responsavel"=>"Webservice IFCE Produ��o","InformacoesAdicionais"=>"Detalhamento no Fala.BR pelo usu�rio logado"]]],"TipoIdentificacaoManifestante"=>["IdTTipoIdentificacaoManifestanteDTO"=>4,"DescTipoIdentificacaoManifestanteDTO"=>"Identificadas sem Restri��o"],"Manifestante"=>["TipoPessoa"=>["IdTipoPessoa"=>1,"DescTipoPessoa"=>"Pessoa F�sica"],"Pais"=>["IdPais"=>1058,"Descricao"=>"Brasil"],"TipoDocumentoIdentificacao"=>["IdTipoDocumentoIdentificacao"=>1,"DescTipoDocumentoIdentificacao"=>"CPF","IndAtivo"=>true],"NumeroDocumentoIdentificacao"=>"02186515385","Nome"=>"HIGO CARLOS MENESES DE SOUSA","Email"=>"higomeneses@hotmail.com","DataNascimento"=>"1989-02-18T00=>00=>00","Telefone"=>["Numero"=>"999711202","ddd"=>"89"],"Endereco"=>["CEP"=>"64607-760","Municipio"=>["IdMunicipio"=>220800,"DescMunicipio"=>"Picos","Uf"=>["SigUf"=>"PI","DescUf"=>"PIAU�"]],"Logradouro"=>"Avenida Senador Helv�dio Nunes, 0, JUNCO","Numero"=>null,"Complemento"=>null,"Bairro"=>null],"genero"=>"M","FaixaEtaria"=>null,"corRa�a"=>null,"Profissao"=>["IdProfissao"=>9,"DescProfissao"=>"Professor"],"Escolaridade"=>["IdEscolaridade"=>5,"Descricao"=>"P�s-gradua��o"],"RazaoSocial"=>null,"TipoInstituicao"=>null,"AreaAtuacao"=>null,"NomeRepresentante"=>null,"CargoRepresentante"=>null,"EmailRepresentante"=>null],"IndAcessoRestrito"=>false];

        //$arrDetalheManifestacao = ["IndRestricaoConteudo"=>true,"ResumoSolicitacao"=>"","Links"=>[["rel"=>"eouv","href"=>"https=>//sistema.ouvidorias.gov.br/publico/Manifestacao/ServicoDetalharManifestacao?id=0IRE2Ogle74=&idsol=+AOAFbvA0/Gyd/H8cmotUB+g9L7hrlDu"]],"IndPossuiIdentidadePreservada"=>false,"IdManifestacao"=>2647079,"NumerosProtocolo"=>["23546058301202012"],"OuvidoriaDestino"=>["IdOuvidoria"=>65,"IdOrgaoSiorg"=>100911,"NomeOuvidoria"=>"IFCE � Instituto Federal de Educa��o, Ci�ncia e Tecnologia do Cear�"],"Assunto"=>["IdAssunto"=>22,"DescAssunto"=>"Aux�lio"],"Servico"=>null,"TipoFormulario"=>["IdTipoFormulario"=>1,"DescTipoFormulario"=>"Padr�o"],"TipoManifestacao"=>["IdTipoManifestacao"=>2,"DescTipoManifestacao"=>"Reclama��o"],"EmailManifestante"=>"anasuelyccavalcante@hotmail.com","DataCadastro"=>"17/11/2020","PrazoAtendimento"=>"18/12/2020","Situacao"=>["IdSituacaoManifestacao"=>1,"DescSituacaoManifestacao"=>"Cadastrada"],"ResponsavelAnalise"=>"","OrgaoInteresse"=>null,"SubAssunto"=>null,"Tag"=>null,"RegistradoPor"=>"ANA SUELY COELHO CAVALCANTE","CanalEntrada"=>["IdCanalEntrada"=>13,"DescCanalEntrada"=>"Internet"],"ModoResposta"=>["IdModoResposta"=>1,"DescModoResposta"=>"Pelo sistema (com avisos por email)","IndModoRespostaAtivo"=>"S"],"InformacoesAdicionais"=>["Apta"=>"","EnvolveEmpresa"=>"","EnvolveServidorPublico"=>"","EnvolveCargoComissionadoDAS4OuSuperior"=>""],"dadosExtraJson"=>[],"ObservacoesOuvidoria"=>null,"Teor"=>["DescricaoAtosOuFatos"=>"Devido as informa��es (admiss�o/demiss�o) desatualizadas no INSS, a DATAPREV juntamente com a CEF cancelaram o meu AUX�LIO EMERGENCIAL. Estou DESEMPREGADA desde julho/2019 e N�O POSSUO NENHUMA RENDA. Meu Cadastro �nico est� atualizado. PELO AMOR DE DEUS, RESOLVAM ISSO, sim? N�O POSSUO V�NCULO EMPREGAT�CIO ALGUM, N�O SOU FUNCION�RIA P�BLICA. Aguardo solu��o atrav�s desde canal, pois no site da DATAPREV n�o est�o me permitindo sequer contestar!","PropostaMelhoria"=>null,"Anexos"=>[["IdAnexoManifestacao"=>1705768,"NomeArquivo"=>"Screenshot_20201108-005032.png","IdObjeto"=>null,"IndComplementar"=>false,"TipoAnexoManifestacao"=>["IdTipoAnexoManifestacao"=>1,"DescTipoAnexoManifestacao"=>"Anexo Manifesta��o"],"Links"=>[["rel"=>"self","href"=>"https=>//sistema.ouvidorias.gov.br/api/manifestacoes/2647079/anexos/1705768"]]],["IdAnexoManifestacao"=>1705769,"NomeArquivo"=>"Screenshot_20201106-201011.png","IdObjeto"=>null,"IndComplementar"=>false,"TipoAnexoManifestacao"=>["IdTipoAnexoManifestacao"=>1,"DescTipoAnexoManifestacao"=>"Anexo Manifesta��o"],"Links"=>[["rel"=>"self","href"=>"https=>//sistema.ouvidorias.gov.br/api/manifestacoes/2647079/anexos/1705769"]]],["IdAnexoManifestacao"=>1705770,"NomeArquivo"=>"Screenshot_20201108-005027.png","IdObjeto"=>null,"IndComplementar"=>false,"TipoAnexoManifestacao"=>["IdTipoAnexoManifestacao"=>1,"DescTipoAnexoManifestacao"=>"Anexo Manifesta��o"],"Links"=>[["rel"=>"self","href"=>"https=>//sistema.ouvidorias.gov.br/api/manifestacoes/2647079/anexos/1705770"]]],["IdAnexoManifestacao"=>1705771,"NomeArquivo"=>"Screenshot_20201108-005021.png","IdObjeto"=>null,"IndComplementar"=>false,"TipoAnexoManifestacao"=>["IdTipoAnexoManifestacao"=>1,"DescTipoAnexoManifestacao"=>"Anexo Manifesta��o"],"Links"=>[["rel"=>"self","href"=>"https=>//sistema.ouvidorias.gov.br/api/manifestacoes/2647079/anexos/1705771"]]]],"LocalFato"=>["Municipio"=>["IdMunicipio"=>530010,"DescMunicipio"=>"Bras�lia","Uf"=>["SigUf"=>"DF","DescUf"=>"DISTRITO FEDERAL"]],"DescricaoLocalFato"=>"DATAPREV","GeoReferencia"=>null],"EnvolvidosManifestacao"=>[["IdEnvolvidoManifestacao"=>419152,"Nome"=>"DATAPREV","Orgao"=>"CAIXA ECON�MICA FEDERAL E INSS","Funcao"=>"Ministro(a)","IdFuncaoEnvolvidoManifestacao"=>13]],"CamposAdicionaisManifestacao"=>[]],"Historico"=>[["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Cadastro","DataHoraAcao"=>"17/11/2020 22=>53","Responsavel"=>"ANA SUELY COELHO CAVALCANTE","InformacoesAdicionais"=>"Registro dos dados da manifesta��o"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Visualiza��o","DataHoraAcao"=>"18/11/2020 11=>46","Responsavel"=>"T�rcio Victor de Oliveira Leal","InformacoesAdicionais"=>"Detalhamento no Fala.BR pelo usu�rio logado"]]],"TipoIdentificacaoManifestante"=>["IdTTipoIdentificacaoManifestanteDTO"=>4,"DescTipoIdentificacaoManifestanteDTO"=>"Identificadas sem Restri��o"],"Manifestante"=>["TipoPessoa"=>["IdTipoPessoa"=>1,"DescTipoPessoa"=>"Pessoa F�sica"],"Pais"=>null,"TipoDocumentoIdentificacao"=>["IdTipoDocumentoIdentificacao"=>1,"DescTipoDocumentoIdentificacao"=>"CPF","IndAtivo"=>true],"NumeroDocumentoIdentificacao"=>"04158985870","Nome"=>"ANA SUELY COELHO CAVALCANTE","Email"=>"anasuelyccavalcante@hotmail.com","DataNascimento"=>null,"Telefone"=>["Numero"=>"989596984","ddd"=>"85"],"Endereco"=>["CEP"=>null,"Municipio"=>null,"Logradouro"=>null,"Numero"=>null,"Complemento"=>null,"Bairro"=>null],"genero"=>null,"FaixaEtaria"=>null,"corRa�a"=>null,"Profissao"=>null,"Escolaridade"=>null,"RazaoSocial"=>null,"TipoInstituicao"=>null,"AreaAtuacao"=>null,"NomeRepresentante"=>null,"CargoRepresentante"=>null,"EmailRepresentante"=>null],"IndAcessoRestrito"=>false];
        // Com resposta
       //$arrDetalheManifestacao = ["IndRestricaoConteudo"=>true,"ResumoSolicitacao"=>"","Links"=>[["rel"=>"eouv","href"=>"https://sistema.ouvidorias.gov.br/publico/Manifestacao/ServicoDetalharManifestacao?id=pB2mXMQNO4o=&idsol=+AOAFbvA0/Gyd/H8cmotUB+g9L7hrlDu"]],"IndPossuiIdentidadePreservada"=>false,"IdManifestacao"=>2670732,"NumerosProtocolo"=>["23546059531202007"],"OuvidoriaDestino"=>["IdOuvidoria"=>65,"IdOrgaoSiorg"=>100911,"NomeOuvidoria"=>"IFCE � Instituto Federal de Educa��o, Ci�ncia e Tecnologia do Cear�"],"Assunto"=>["IdAssunto"=>57,"DescAssunto"=>"Defesa do Consumidor"],"Servico"=>null,"TipoFormulario"=>["IdTipoFormulario"=>1,"DescTipoFormulario"=>"Padr�o"],"TipoManifestacao"=>["IdTipoManifestacao"=>2,"DescTipoManifestacao"=>"Reclama��o"],"EmailManifestante"=>"luanaalbuquerquev@gmail.com","DataCadastro"=>"24/11/2020","PrazoAtendimento"=>null,"Situacao"=>["IdSituacaoManifestacao"=>5,"DescSituacaoManifestacao"=>"Complementa��o Solicitada"],"ResponsavelAnalise"=>"T�rcio Victor de Oliveira Leal","OrgaoInteresse"=>null,"SubAssunto"=>null,"Tag"=>null,"RegistradoPor"=>"Luana Albuquerque","CanalEntrada"=>["IdCanalEntrada"=>13,"DescCanalEntrada"=>"Internet"],"ModoResposta"=>["IdModoResposta"=>1,"DescModoResposta"=>"Pelo sistema (com avisos por email)","IndModoRespostaAtivo"=>"S"],"InformacoesAdicionais"=>["Apta"=>"","EnvolveEmpresa"=>"","EnvolveServidorPublico"=>"","EnvolveCargoComissionadoDAS4OuSuperior"=>""],"dadosExtraJson"=>[],"ObservacoesOuvidoria"=>null,"Teor"=>["DescricaoAtosOuFatos"=>"Bom dia!\r\nFico muit�ssimo chateada todas as vezes que preciso resolver algo no SETOR DE EST�GIOS do IFCE, sempre me tratam com descaso e eu demoro MESES para conseguir assinaturas que deveriam levar minutos. Os e-mails demoram muito a serem respondidos, preciso entrar em contato com o coordenador do curso para tentar agilizar o processo.\r\nUltimamente eu tive que entregar os relat�rios de est�gio obrigat�rio, peguei o modelo no SITE OFICIAL do IFCE e ele ficou minimamente diferente do original pois editei no .pages e n�o no .word, eu passei meses escrevendo esses relat�rios e agora preciso refazer em um computador com windows. A institui��o que precisa me dar um documento oficial em .pages, ou aceitar o que eu consegui editar (O MELHOR POSS�VEL E A MUITO CUSTO), eu solicitei este documento e n�o existe. E mais, o recebimento destes relat�rios est� diretamente ligado a continuidade do meu est�gio em 2021, preciso da assinatura do termo aditivo que ainda n�o me foi concedida.\r\nMais um adendo, no semestre passado o coordenador de est�gios fez com que eu retirasse duas cadeiras porque as aulas voltariam em mar�o e eu n�o poderia conciliar o est�gio com o trabalho, n�o voltamos, COMO PREVISTO. Eu perdi duas cadeiras que j� tinha feito provas presencialmente, sem motivo algum. � isto, espero ter sido clara. Obrigada!","PropostaMelhoria"=>null,"Anexos"=>[],"LocalFato"=>["Municipio"=>["IdMunicipio"=>230440,"DescMunicipio"=>"Fortaleza","Uf"=>["SigUf"=>"CE","DescUf"=>"CEAR�"]],"DescricaoLocalFato"=>"IFCE","GeoReferencia"=>null],"EnvolvidosManifestacao"=>[],"CamposAdicionaisManifestacao"=>[]],"Historico"=>[["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Cadastro","DataHoraAcao"=>"24/11/2020 09=>14","Responsavel"=>"Luana Albuquerque","InformacoesAdicionais"=>"Registro dos dados da manifesta��o"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Visualiza��o","DataHoraAcao"=>"24/11/2020 09=>15","Responsavel"=>"T�rcio Victor de Oliveira Leal","InformacoesAdicionais"=>"Detalhamento no Fala.BR pelo usu�rio logado"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Visualiza��o","DataHoraAcao"=>"24/11/2020 09=>16","Responsavel"=>"T�rcio Victor de Oliveira Leal","InformacoesAdicionais"=>"Detalhamento no Fala.BR pelo usu�rio logado"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"An�lise","DataHoraAcao"=>"24/11/2020 09=>17","Responsavel"=>"T�rcio Victor de Oliveira Leal","InformacoesAdicionais"=>"Usu�rio respons�vel pela an�lise=> T�rcio Victor de Oliveira Leal"]],["HistoricoAcao"=>["DescTipoAcaoManifestacao"=>"Registro Resposta","DataHoraAcao"=>"24/11/2020 09=>18","Responsavel"=>"T�rcio Victor de Oliveira Leal","InformacoesAdicionais"=>" - Prazo de resposta suspenso at� que uma complementa��o seja feita. Prazo anterior=> 28/12/2020"],"Resposta"=>["IdRespostaManifestacao"=>2655789,"TipoRespostaManifestacao"=>["IdTipoRespostaManifestacao"=>1,"DescTipoRespostaManifestacao"=>"Pedido de Complementa��o"],"TxtResposta"=>"Bom dia,\r\n\r\nPara o correto encaminhamento de sua reclama��o � �rea final�stica, precisamos saber qual o curso e campus (unidade do IFCE) voc� � aluna.\r\n\r\nAtenciosamente,","RespostaPublicavel"=>false,"Decisao"=>null,"DataCompromisso"=>null]]],"TipoIdentificacaoManifestante"=>["IdTTipoIdentificacaoManifestanteDTO"=>4,"DescTipoIdentificacaoManifestanteDTO"=>"Identificadas sem Restri��o"],"Manifestante"=>["TipoPessoa"=>["IdTipoPessoa"=>1,"DescTipoPessoa"=>"Pessoa F�sica"],"Pais"=>["IdPais"=>1058,"Descricao"=>"Brasil"],"TipoDocumentoIdentificacao"=>["IdTipoDocumentoIdentificacao"=>1,"DescTipoDocumentoIdentificacao"=>"CPF","IndAtivo"=>true],"NumeroDocumentoIdentificacao"=>"60413183378","Nome"=>"Luana Albuquerque","Email"=>"luanaalbuquerquev@gmail.com","DataNascimento"=>null,"Telefone"=>null,"Endereco"=>["CEP"=>null,"Municipio"=>null,"Logradouro"=>null,"Numero"=>null,"Complemento"=>null,"Bairro"=>null],"genero"=>null,"FaixaEtaria"=>null,"corRa�a"=>null,"Profissao"=>null,"Escolaridade"=>null,"RazaoSocial"=>null,"TipoInstituicao"=>null,"AreaAtuacao"=>null,"NomeRepresentante"=>null,"CargoRepresentante"=>null,"EmailRepresentante"=>null],"IndAcessoRestrito"=>false];

        /**
         * Verifica Tipo de Manifesta��o e-Ouv ou e-Sic
         */
        if ($tipoManifestacao == 'P' && $retornoWsLinha['TipoManifestacao']['IdTipoManifestacao'] <> 8) {
            $manifestacaoESic = false;
            $idUnidadeDestino = $idUnidadeOuvidoria;
        } elseif ($tipoManifestacao == 'R' && $retornoWsLinha['TipoManifestacao']['IdTipoManifestacao'] == 8) {
            $manifestacaoESic = true;
            $idUnidadeDestino = $idUnidadeEsicPrincipal;

            /**
             * Importar Recursos caso seja manifesta��o e-Sic (Tipo 8)
             */
            $arrRecursosManifestacao = $this->apiRestRequest($urlWebServiceESicRecursos . $arrDetalheManifestacao['NumerosProtocolo'][0], $token, 2);
            // e-Sic 23546.048338/2020-32 - com recurso primeira inst�ncia
            // $arrRecursosManifestacao = ["ResultadosLimitadosAoMaximo"=>false,"TotalRegistrosEncontrados"=>1,"DataProcessamento"=>"01/12/2020 15:27","Recursos"=>[["numProtocolo"=>"23546.048338/2020-32","idRecurso"=>127668,"instancia"=>["IdInstanciaRecurso"=>1,"DescInstanciaRecurso"=>"Primeira Inst�ncia"],"tipoRecurso"=>["IdTipoRecurso"=>2,"DescTipoRecurso"=>"Informa��o incompleta"],"dataRecurso"=>"05/11/2020 21=>10=>33","prazoAtendimento"=>"13/11/2020 23=>59=>59","situacaoRecurso"=>["idSituacaoRecurso"=>2,"descSituacaoRecurso"=>"Respondido"],"justificativa"=>"Faltou o IF informar a previs�o de aposentadoria dos professores, especialmente os mais antigos=> Abner Jackson Colares Oliveira, Antonio Gilberto Abreu de Souza, Francisco Herbert Rolim de Sousa e  se haver� necessidade de contrata��o de novos docentes na �rea para 2021 e 2022. (Informar os anos separadamente). O IF tamb�m n�o informou quando haver� novo edital pra remo��o de docente.  ","qtdAnexos"=>0,"anexos"=>[],"solicitante"=>["TipoPessoa"=>["IdTipoPessoa"=>1,"DescTipoPessoa"=>"Pessoa F�sica"],"Pais"=>["IdPais"=>1058,"Descricao"=>"Brasil"],"TipoDocumentoIdentificacao"=>["IdTipoDocumentoIdentificacao"=>1,"DescTipoDocumentoIdentificacao"=>"CPF","IndAtivo"=>true],"NumeroDocumentoIdentificacao"=>"02186515385","Nome"=>"HIGO CARLOS MENESES DE SOUSA","Email"=>"higomeneses@hotmail.com","DataNascimento"=>"1989-02-18T00=>00=>00","Telefone"=>["Numero"=>"999711202","ddd"=>"89"],"Endereco"=>["CEP"=>"64607-760","Municipio"=>["IdMunicipio"=>220800,"DescMunicipio"=>"Picos","Uf"=>["SigUf"=>"PI","DescUf"=>"PIAU�"]],"Logradouro"=>"Avenida Senador Helv�dio Nunes, 0, JUNCO","Numero"=>null,"Complemento"=>null,"Bairro"=>null],"genero"=>"M","FaixaEtaria"=>null,"corRa�a"=>null,"Profissao"=>["IdProfissao"=>9,"DescProfissao"=>"Professor"],"Escolaridade"=>["IdEscolaridade"=>5,"Descricao"=>"P�s-gradua��o"],"RazaoSocial"=>null,"TipoInstituicao"=>null,"AreaAtuacao"=>null,"NomeRepresentante"=>null,"CargoRepresentante"=>null,"EmailRepresentante"=>null]]]];
            // $arrRecursosManifestacao = '';
            // e-Sic 23546.048338/2020-32 - com recuso segunda inst�ncia
            // $arrRecursosManifestacao = ["ResultadosLimitadosAoMaximo"=>false,"TotalRegistrosEncontrados"=>1,"DataProcessamento"=>"07/12/2020 13=>24","Recursos"=>[["numProtocolo"=>"23546.048338/2020-32","idRecurso"=>127668,"instancia"=>["IdInstanciaRecurso"=>1,"DescInstanciaRecurso"=>"Primeira Inst�ncia"],"tipoRecurso"=>["IdTipoRecurso"=>2,"DescTipoRecurso"=>"Informa��o incompleta"],"dataRecurso"=>"05/11/2020 21:10:33","prazoAtendimento"=>"13/11/2020 23:59:59","situacaoRecurso"=>["idSituacaoRecurso"=>2,"descSituacaoRecurso"=>"Respondido"],"justificativa"=>"Faltou o IF informar a previs�o de aposentadoria dos professores, especialmente os mais antigos=> Abner Jackson Colares Oliveira, Antonio Gilberto Abreu de Souza, Francisco Herbert Rolim de Sousa e  se haver� necessidade de contrata��o de novos docentes na �rea para 2021 e 2022. (Informar os anos separadamente). O IF tamb�m n�o informou quando haver� novo edital pra remo��o de docente.  ","qtdAnexos"=>0,"anexos"=>[],"solicitante"=>["TipoPessoa"=>["IdTipoPessoa"=>1,"DescTipoPessoa"=>"Pessoa F�sica"],"Pais"=>["IdPais"=>1058,"Descricao"=>"Brasil"],"TipoDocumentoIdentificacao"=>["IdTipoDocumentoIdentificacao"=>1,"DescTipoDocumentoIdentificacao"=>"CPF","IndAtivo"=>true],"NumeroDocumentoIdentificacao"=>"02186515385","Nome"=>"HIGO CARLOS MENESES DE SOUSA","Email"=>"higomeneses@hotmail.com","DataNascimento"=>"1989-02-18T00=>00=>00","Telefone"=>["Numero"=>"999711202","ddd"=>"89"],"Endereco"=>["CEP"=>"64607-760","Municipio"=>["IdMunicipio"=>220800,"DescMunicipio"=>"Picos","Uf"=>["SigUf"=>"PI","DescUf"=>"PIAU�"]],"Logradouro"=>"Avenida Senador Helv�dio Nunes, 0, JUNCO","Numero"=>null,"Complemento"=>null,"Bairro"=>null],"genero"=>"M","FaixaEtaria"=>null,"corRa�a"=>null,"Profissao"=>["IdProfissao"=>9,"DescProfissao"=>"Professor"],"Escolaridade"=>["IdEscolaridade"=>5,"Descricao"=>"P�s-gradua��o"],"RazaoSocial"=>null,"TipoInstituicao"=>null,"AreaAtuacao"=>null,"NomeRepresentante"=>null,"CargoRepresentante"=>null,"EmailRepresentante"=>null]],["numProtocolo"=>"23546.048338/2020-32","idRecurso"=>128472,"instancia"=>["IdInstanciaRecurso"=>2,"DescInstanciaRecurso"=>"Segunda Inst�ncia"],"tipoRecurso"=>["IdTipoRecurso"=>2,"DescTipoRecurso"=>"Informa��o incompleta"],"dataRecurso"=>"26/11/2020 10:25:46","prazoAtendimento"=>"01/12/2020 23:59:59","situacaoRecurso"=>["idSituacaoRecurso"=>2,"descSituacaoRecurso"=>"Respondido"],"justificativa"=>"� Vossa Senhoria,  Senhor  Fernando Eug�nio Lopes de Melo, Diretor �Geral do Campus Cedro- IFCE\r\nSolicito a informa��o da carga hor�ria  geral do Professor de Hist�ria do Campus Cedro, o que inclui ensino, pesquisa e extens�o, did�tico-pedag�gico,  capacita��o e administra��o e representa��o. Na resposta fornecida pelo campus apenas identifiquei  o nome do docente, faltou informar a sua lota��o , no caso,   a sua carga hor�ria geral, o que inclui ensino, pesquisa, extens�o, representa��o, capacita��o e administra��o,  por isso, a resposta ficou incompleta. Por isso, solicito a  resposta completa em rela��o � informa��o solicitada. \r\n\r\nNestes termos, aguardo deferimento.\r\n\r\nAtenciosamente,\r\nSilvera Vieira de Ara�jo Holanda\r\n\r\n\r\n","qtdAnexos"=>0,"anexos"=>[],"solicitante"=>["TipoPessoa"=>["IdTipoPessoa"=>1,"DescTipoPessoa"=>"Pessoa F�sica"],"Pais"=>["IdPais"=>1058,"Descricao"=>"Brasil"],"TipoDocumentoIdentificacao"=>["IdTipoDocumentoIdentificacao"=>1,"DescTipoDocumentoIdentificacao"=>"CPF","IndAtivo"=>true],"NumeroDocumentoIdentificacao"=>"05446951433","Nome"=>"Silvera Vieira de Ara�jo Holanda","Email"=>"silveravieira@hotmail.com","DataNascimento"=>null,"Telefone"=>["Numero"=>"996502528","ddd"=>"83"],"Endereco"=>["CEP"=>"58187-000","Municipio"=>["IdMunicipio"=>251140,"DescMunicipio"=>"Picu�","Uf"=>["SigUf"=>"PB","DescUf"=>"PARA�BA"]],"Logradouro"=>"Rua Francisco Mariano da Silva, n.71","Numero"=>null,"Complemento"=>null,"Bairro"=>null],"genero"=>null,"FaixaEtaria"=>null,"corRa�a"=>null,"Profissao"=>null,"Escolaridade"=>null,"RazaoSocial"=>null,"TipoInstituicao"=>null,"AreaAtuacao"=>null,"NomeRepresentante"=>null,"CargoRepresentante"=>null,"EmailRepresentante"=>null]]]];
            // e-Sic 23546.048338/2020-32 - com recuso segunda inst�ncia e anexo
            // $arrRecursosManifestacao = ["ResultadosLimitadosAoMaximo"=>false,"TotalRegistrosEncontrados"=>1,"DataProcessamento"=>"07/12/2020 13=>24","Recursos"=>[["numProtocolo"=>"23546.048338/2020-32","idRecurso"=>127668,"instancia"=>["IdInstanciaRecurso"=>1,"DescInstanciaRecurso"=>"Primeira Inst�ncia"],"tipoRecurso"=>["IdTipoRecurso"=>2,"DescTipoRecurso"=>"Informa��o incompleta"],"dataRecurso"=>"05/11/2020 21:10:33","prazoAtendimento"=>"13/11/2020 23:59:59","situacaoRecurso"=>["idSituacaoRecurso"=>2,"descSituacaoRecurso"=>"Respondido"],"justificativa"=>"Faltou o IF informar a previs�o de aposentadoria dos professores, especialmente os mais antigos=> Abner Jackson Colares Oliveira, Antonio Gilberto Abreu de Souza, Francisco Herbert Rolim de Sousa e  se haver� necessidade de contrata��o de novos docentes na �rea para 2021 e 2022. (Informar os anos separadamente). O IF tamb�m n�o informou quando haver� novo edital pra remo��o de docente.  ","qtdAnexos"=>0,"anexos"=>[],"solicitante"=>["TipoPessoa"=>["IdTipoPessoa"=>1,"DescTipoPessoa"=>"Pessoa F�sica"],"Pais"=>["IdPais"=>1058,"Descricao"=>"Brasil"],"TipoDocumentoIdentificacao"=>["IdTipoDocumentoIdentificacao"=>1,"DescTipoDocumentoIdentificacao"=>"CPF","IndAtivo"=>true],"NumeroDocumentoIdentificacao"=>"02186515385","Nome"=>"HIGO CARLOS MENESES DE SOUSA","Email"=>"higomeneses@hotmail.com","DataNascimento"=>"1989-02-18T00=>00=>00","Telefone"=>["Numero"=>"999711202","ddd"=>"89"],"Endereco"=>["CEP"=>"64607-760","Municipio"=>["IdMunicipio"=>220800,"DescMunicipio"=>"Picos","Uf"=>["SigUf"=>"PI","DescUf"=>"PIAU�"]],"Logradouro"=>"Avenida Senador Helv�dio Nunes, 0, JUNCO","Numero"=>null,"Complemento"=>null,"Bairro"=>null],"genero"=>"M","FaixaEtaria"=>null,"corRa�a"=>null,"Profissao"=>["IdProfissao"=>9,"DescProfissao"=>"Professor"],"Escolaridade"=>["IdEscolaridade"=>5,"Descricao"=>"P�s-gradua��o"],"RazaoSocial"=>null,"TipoInstituicao"=>null,"AreaAtuacao"=>null,"NomeRepresentante"=>null,"CargoRepresentante"=>null,"EmailRepresentante"=>null]],["numProtocolo"=>"23546.048338/2020-32","idRecurso"=>128472,"instancia"=>["IdInstanciaRecurso"=>2,"DescInstanciaRecurso"=>"Segunda Inst�ncia"],"tipoRecurso"=>["IdTipoRecurso"=>2,"DescTipoRecurso"=>"Informa��o incompleta"],"dataRecurso"=>"07/12/2020 10:25:46","prazoAtendimento"=>"16/12/2020 23:59:59","situacaoRecurso"=>["idSituacaoRecurso"=>2,"descSituacaoRecurso"=>"Respondido"],"justificativa"=>"� Vossa Senhoria,  Senhor  Fernando Eug�nio Lopes de Melo, Diretor �Geral do Campus Cedro- IFCE\r\nSolicito a informa��o da carga hor�ria  geral do Professor de Hist�ria do Campus Cedro, o que inclui ensino, pesquisa e extens�o, did�tico-pedag�gico,  capacita��o e administra��o e representa��o. Na resposta fornecida pelo campus apenas identifiquei  o nome do docente, faltou informar a sua lota��o , no caso,   a sua carga hor�ria geral, o que inclui ensino, pesquisa, extens�o, representa��o, capacita��o e administra��o,  por isso, a resposta ficou incompleta. Por isso, solicito a  resposta completa em rela��o � informa��o solicitada. \r\n\r\nNestes termos, aguardo deferimento.\r\n\r\nAtenciosamente,\r\nSilvera Vieira de Ara�jo Holanda\r\n\r\n\r\n","qtdAnexos"=>2,"anexos"=>[["IdAnexoRecurso"=>1673462,"nomeArquivo"=>"anexo_recurso_acesso_informacao.pdf","Links"=>[["rel"=>"self","href"=>"https://sistema.ouvidorias.gov.br/api/manifestacoes/2477063/anexos/1673462"]]],["IdAnexoManifestacao"=>1673463,"nomeArquivo"=>"AnexoII_recurso_acesso_informacao.pdf","Links"=>[["rel"=>"self","href"=>"https://sistema.ouvidorias.gov.br/api/manifestacoes/2477063/anexos/1673463"]]]],"solicitante"=>["TipoPessoa"=>["IdTipoPessoa"=>1,"DescTipoPessoa"=>"Pessoa F�sica"],"Pais"=>["IdPais"=>1058,"Descricao"=>"Brasil"],"TipoDocumentoIdentificacao"=>["IdTipoDocumentoIdentificacao"=>1,"DescTipoDocumentoIdentificacao"=>"CPF","IndAtivo"=>true],"NumeroDocumentoIdentificacao"=>"05446951433","Nome"=>"Silvera Vieira de Ara�jo Holanda","Email"=>"silveravieira@hotmail.com","DataNascimento"=>null,"Telefone"=>["Numero"=>"996502528","ddd"=>"83"],"Endereco"=>["CEP"=>"58187-000","Municipio"=>["IdMunicipio"=>251140,"DescMunicipio"=>"Picu�","Uf"=>["SigUf"=>"PB","DescUf"=>"PARA�BA"]],"Logradouro"=>"Rua Francisco Mariano da Silva, n.71","Numero"=>null,"Complemento"=>null,"Bairro"=>null],"genero"=>null,"FaixaEtaria"=>null,"corRa�a"=>null,"Profissao"=>null,"Escolaridade"=>null,"RazaoSocial"=>null,"TipoInstituicao"=>null,"AreaAtuacao"=>null,"NomeRepresentante"=>null,"CargoRepresentante"=>null,"EmailRepresentante"=>null]]]];
            // e-Sic 23546.048338/2020-32 - com recuso segunda inst�ncia e mais anexos (teste de anexar mesmos anexos novamente...)
            //$arrRecursosManifestacao = ["ResultadosLimitadosAoMaximo"=>false,"TotalRegistrosEncontrados"=>1,"DataProcessamento"=>"07/12/2020 13=>24","Recursos"=>[["numProtocolo"=>"23546.048338/2020-32","idRecurso"=>127668,"instancia"=>["IdInstanciaRecurso"=>1,"DescInstanciaRecurso"=>"Primeira Inst�ncia"],"tipoRecurso"=>["IdTipoRecurso"=>2,"DescTipoRecurso"=>"Informa��o incompleta"],"dataRecurso"=>"05/11/2020 21:10:33","prazoAtendimento"=>"13/11/2020 23:59:59","situacaoRecurso"=>["idSituacaoRecurso"=>2,"descSituacaoRecurso"=>"Respondido"],"justificativa"=>"Faltou o IF informar a previs�o de aposentadoria dos professores, especialmente os mais antigos=> Abner Jackson Colares Oliveira, Antonio Gilberto Abreu de Souza, Francisco Herbert Rolim de Sousa e  se haver� necessidade de contrata��o de novos docentes na �rea para 2021 e 2022. (Informar os anos separadamente). O IF tamb�m n�o informou quando haver� novo edital pra remo��o de docente.  ","qtdAnexos"=>0,"anexos"=>[],"solicitante"=>["TipoPessoa"=>["IdTipoPessoa"=>1,"DescTipoPessoa"=>"Pessoa F�sica"],"Pais"=>["IdPais"=>1058,"Descricao"=>"Brasil"],"TipoDocumentoIdentificacao"=>["IdTipoDocumentoIdentificacao"=>1,"DescTipoDocumentoIdentificacao"=>"CPF","IndAtivo"=>true],"NumeroDocumentoIdentificacao"=>"02186515385","Nome"=>"HIGO CARLOS MENESES DE SOUSA","Email"=>"higomeneses@hotmail.com","DataNascimento"=>"1989-02-18T00=>00=>00","Telefone"=>["Numero"=>"999711202","ddd"=>"89"],"Endereco"=>["CEP"=>"64607-760","Municipio"=>["IdMunicipio"=>220800,"DescMunicipio"=>"Picos","Uf"=>["SigUf"=>"PI","DescUf"=>"PIAU�"]],"Logradouro"=>"Avenida Senador Helv�dio Nunes, 0, JUNCO","Numero"=>null,"Complemento"=>null,"Bairro"=>null],"genero"=>"M","FaixaEtaria"=>null,"corRa�a"=>null,"Profissao"=>["IdProfissao"=>9,"DescProfissao"=>"Professor"],"Escolaridade"=>["IdEscolaridade"=>5,"Descricao"=>"P�s-gradua��o"],"RazaoSocial"=>null,"TipoInstituicao"=>null,"AreaAtuacao"=>null,"NomeRepresentante"=>null,"CargoRepresentante"=>null,"EmailRepresentante"=>null]],
            //    ["numProtocolo"=>"23546.048338/2020-32","idRecurso"=>128472,"instancia"=>["IdInstanciaRecurso"=>2,"DescInstanciaRecurso"=>"Segunda Inst�ncia"],"tipoRecurso"=>["IdTipoRecurso"=>2,"DescTipoRecurso"=>"Informa��o incompleta"],"dataRecurso"=>"07/12/2020 10:25:46","prazoAtendimento"=>"16/12/2020 23:59:59","situacaoRecurso"=>["idSituacaoRecurso"=>2,"descSituacaoRecurso"=>"Respondido"],"justificativa"=>"� Vossa Senhoria,  Senhor  Fernando Eug�nio Lopes de Melo, Diretor �Geral do Campus Cedro- IFCE\r\nSolicito a informa��o da carga hor�ria  geral do Professor de Hist�ria do Campus Cedro, o que inclui ensino, pesquisa e extens�o, did�tico-pedag�gico,  capacita��o e administra��o e representa��o. Na resposta fornecida pelo campus apenas identifiquei  o nome do docente, faltou informar a sua lota��o , no caso,   a sua carga hor�ria geral, o que inclui ensino, pesquisa, extens�o, representa��o, capacita��o e administra��o,  por isso, a resposta ficou incompleta. Por isso, solicito a  resposta completa em rela��o � informa��o solicitada. \r\n\r\nNestes termos, aguardo deferimento.\r\n\r\nAtenciosamente,\r\nSilvera Vieira de Ara�jo Holanda\r\n\r\n\r\n","qtdAnexos"=>2,"anexos"=>[["IdAnexoRecurso"=>1673462,"nomeArquivo"=>"anexo_recurso_acesso_informacao.pdf","Links"=>[["rel"=>"self","href"=>"https://sistema.ouvidorias.gov.br/api/manifestacoes/2477063/anexos/1673462"]]],["IdAnexoManifestacao"=>1673463,"nomeArquivo"=>"AnexoII_recurso_acesso_informacao.pdf","Links"=>[["rel"=>"self","href"=>"https://sistema.ouvidorias.gov.br/api/manifestacoes/2477063/anexos/1673463"]]]],"solicitante"=>["TipoPessoa"=>["IdTipoPessoa"=>1,"DescTipoPessoa"=>"Pessoa F�sica"],"Pais"=>["IdPais"=>1058,"Descricao"=>"Brasil"],"TipoDocumentoIdentificacao"=>["IdTipoDocumentoIdentificacao"=>1,"DescTipoDocumentoIdentificacao"=>"CPF","IndAtivo"=>true],"NumeroDocumentoIdentificacao"=>"05446951433","Nome"=>"Silvera Vieira de Ara�jo Holanda","Email"=>"silveravieira@hotmail.com","DataNascimento"=>null,"Telefone"=>["Numero"=>"996502528","ddd"=>"83"],"Endereco"=>["CEP"=>"58187-000","Municipio"=>["IdMunicipio"=>251140,"DescMunicipio"=>"Picu�","Uf"=>["SigUf"=>"PB","DescUf"=>"PARA�BA"]],"Logradouro"=>"Rua Francisco Mariano da Silva, n.71","Numero"=>null,"Complemento"=>null,"Bairro"=>null],"genero"=>null,"FaixaEtaria"=>null,"corRa�a"=>null,"Profissao"=>null,"Escolaridade"=>null,"RazaoSocial"=>null,"TipoInstituicao"=>null,"AreaAtuacao"=>null,"NomeRepresentante"=>null,"CargoRepresentante"=>null,"EmailRepresentante"=>null
            //    ]],["numProtocolo"=>"23546.048338/2020-32","idRecurso"=>128472,"instancia"=>["IdInstanciaRecurso"=>2,"DescInstanciaRecurso"=>"Segunda Inst�ncia"],"tipoRecurso"=>["IdTipoRecurso"=>2,"DescTipoRecurso"=>"Informa��o incompleta"],"dataRecurso"=>"07/12/2020 10:25:46","prazoAtendimento"=>"02/01/2021 23:59:59","situacaoRecurso"=>["idSituacaoRecurso"=>2,"descSituacaoRecurso"=>"Respondido"],"justificativa"=>"� Vossa Senhoria,  Senhor  Fernando Eug�nio Lopes de Melo, Diretor �Geral do Campus Cedro- IFCE\r\nSolicito a informa��o da carga hor�ria  geral do Professor de Hist�ria do Campus Cedro, o que inclui ensino, pesquisa e extens�o, did�tico-pedag�gico,  capacita��o e administra��o e representa��o. Na resposta fornecida pelo campus apenas identifiquei  o nome do docente, faltou informar a sua lota��o , no caso,   a sua carga hor�ria geral, o que inclui ensino, pesquisa, extens�o, representa��o, capacita��o e administra��o,  por isso, a resposta ficou incompleta. Por isso, solicito a  resposta completa em rela��o � informa��o solicitada. \r\n\r\nNestes termos, aguardo deferimento.\r\n\r\nAtenciosamente,\r\nSilvera Vieira de Ara�jo Holanda\r\n\r\n\r\n","qtdAnexos"=>2,"anexos"=>[["IdAnexoRecurso"=>1673462,"nomeArquivo"=>"anexo_recurso_acesso_informacao_NOVO.pdf","Links"=>[["rel"=>"self","href"=>"https://sistema.ouvidorias.gov.br/api/manifestacoes/2477063/anexos/1673462"]]],["IdAnexoManifestacao"=>1673463,"nomeArquivo"=>"AnexoII_recurso_acesso_informacao.pdf","Links"=>[["rel"=>"self","href"=>"https://sistema.ouvidorias.gov.br/api/manifestacoes/2477063/anexos/1673463"]]]],"solicitante"=>["TipoPessoa"=>["IdTipoPessoa"=>1,"DescTipoPessoa"=>"Pessoa F�sica"],"Pais"=>["IdPais"=>1058,"Descricao"=>"Brasil"],"TipoDocumentoIdentificacao"=>["IdTipoDocumentoIdentificacao"=>1,"DescTipoDocumentoIdentificacao"=>"CPF","IndAtivo"=>true],"NumeroDocumentoIdentificacao"=>"05446951433","Nome"=>"Silvera Vieira de Ara�jo Holanda","Email"=>"silveravieira@hotmail.com","DataNascimento"=>null,"Telefone"=>["Numero"=>"996502528","ddd"=>"83"],"Endereco"=>["CEP"=>"58187-000","Municipio"=>["IdMunicipio"=>251140,"DescMunicipio"=>"Picu�","Uf"=>["SigUf"=>"PB","DescUf"=>"PARA�BA"]],"Logradouro"=>"Rua Francisco Mariano da Silva, n.71","Numero"=>null,"Complemento"=>null,"Bairro"=>null],"genero"=>null,"FaixaEtaria"=>null,"corRa�a"=>null,"Profissao"=>null,"Escolaridade"=>null,"RazaoSocial"=>null,"TipoInstituicao"=>null,"AreaAtuacao"=>null,"NomeRepresentante"=>null,"CargoRepresentante"=>null,"EmailRepresentante"=>null
            //    ]]]];

            // Processo cgu 00106.000623/2020-56
            //$arrRecursosManifestacao = ["ResultadosLimitadosAoMaximo"=>false,"TotalRegistrosEncontrados"=>1,"DataProcessamento"=>"07/12/2020 13=>24","Recursos"=>[["numProtocolo"=>"00106.000623/2020-56","idRecurso"=>127668,"instancia"=>["IdInstanciaRecurso"=>1,"DescInstanciaRecurso"=>"Primeira Inst�ncia"],"tipoRecurso"=>["IdTipoRecurso"=>2,"DescTipoRecurso"=>"Informa��o incompleta"],"dataRecurso"=>"05/11/2020 21:10:33","prazoAtendimento"=>"13/11/2020 23:59:59","situacaoRecurso"=>["idSituacaoRecurso"=>2,"descSituacaoRecurso"=>"Respondido"],"justificativa"=>"Faltou o IF informar a previs�o de aposentadoria dos professores, especialmente os mais antigos=> Abner Jackson Colares Oliveira, Antonio Gilberto Abreu de Souza, Francisco Herbert Rolim de Sousa e  se haver� necessidade de contrata��o de novos docentes na �rea para 2021 e 2022. (Informar os anos separadamente). O IF tamb�m n�o informou quando haver� novo edital pra remo��o de docente.  ","qtdAnexos"=>0,"anexos"=>[],"solicitante"=>["TipoPessoa"=>["IdTipoPessoa"=>1,"DescTipoPessoa"=>"Pessoa F�sica"],"Pais"=>["IdPais"=>1058,"Descricao"=>"Brasil"],"TipoDocumentoIdentificacao"=>["IdTipoDocumentoIdentificacao"=>1,"DescTipoDocumentoIdentificacao"=>"CPF","IndAtivo"=>true],"NumeroDocumentoIdentificacao"=>"02186515385","Nome"=>"HIGO CARLOS MENESES DE SOUSA","Email"=>"higomeneses@hotmail.com","DataNascimento"=>"1989-02-18T00=>00=>00","Telefone"=>["Numero"=>"999711202","ddd"=>"89"],"Endereco"=>["CEP"=>"64607-760","Municipio"=>["IdMunicipio"=>220800,"DescMunicipio"=>"Picos","Uf"=>["SigUf"=>"PI","DescUf"=>"PIAU�"]],"Logradouro"=>"Avenida Senador Helv�dio Nunes, 0, JUNCO","Numero"=>null,"Complemento"=>null,"Bairro"=>null],"genero"=>"M","FaixaEtaria"=>null,"corRa�a"=>null,"Profissao"=>["IdProfissao"=>9,"DescProfissao"=>"Professor"],"Escolaridade"=>["IdEscolaridade"=>5,"Descricao"=>"P�s-gradua��o"],"RazaoSocial"=>null,"TipoInstituicao"=>null,"AreaAtuacao"=>null,"NomeRepresentante"=>null,"CargoRepresentante"=>null,"EmailRepresentante"=>null]],
            //    ["numProtocolo"=>"00106.000623/2020-56","idRecurso"=>128472,"instancia"=>["IdInstanciaRecurso"=>2,"DescInstanciaRecurso"=>"Segunda Inst�ncia"],"tipoRecurso"=>["IdTipoRecurso"=>2,"DescTipoRecurso"=>"Informa��o incompleta"],"dataRecurso"=>"07/12/2020 10:25:46","prazoAtendimento"=>"16/12/2020 23:59:59","situacaoRecurso"=>["idSituacaoRecurso"=>2,"descSituacaoRecurso"=>"Respondido"],"justificativa"=>"� Vossa Senhoria,  Senhor  Fernando Eug�nio Lopes de Melo, Diretor �Geral do Campus Cedro- IFCE\r\nSolicito a informa��o da carga hor�ria  geral do Professor de Hist�ria do Campus Cedro, o que inclui ensino, pesquisa e extens�o, did�tico-pedag�gico,  capacita��o e administra��o e representa��o. Na resposta fornecida pelo campus apenas identifiquei  o nome do docente, faltou informar a sua lota��o , no caso,   a sua carga hor�ria geral, o que inclui ensino, pesquisa, extens�o, representa��o, capacita��o e administra��o,  por isso, a resposta ficou incompleta. Por isso, solicito a  resposta completa em rela��o � informa��o solicitada. \r\n\r\nNestes termos, aguardo deferimento.\r\n\r\nAtenciosamente,\r\nSilvera Vieira de Ara�jo Holanda\r\n\r\n\r\n","qtdAnexos"=>2,"anexos"=>[["IdAnexoRecurso"=>1673462,"nomeArquivo"=>"anexo_recurso_acesso_informacao.pdf","Links"=>[["rel"=>"self","href"=>"https://sistema.ouvidorias.gov.br/api/manifestacoes/2477063/anexos/1673462"]]],["IdAnexoManifestacao"=>1673463,"nomeArquivo"=>"AnexoII_recurso_acesso_informacao.pdf","Links"=>[["rel"=>"self","href"=>"https://sistema.ouvidorias.gov.br/api/manifestacoes/2477063/anexos/1673463"]]]],"solicitante"=>["TipoPessoa"=>["IdTipoPessoa"=>1,"DescTipoPessoa"=>"Pessoa F�sica"],"Pais"=>["IdPais"=>1058,"Descricao"=>"Brasil"],"TipoDocumentoIdentificacao"=>["IdTipoDocumentoIdentificacao"=>1,"DescTipoDocumentoIdentificacao"=>"CPF","IndAtivo"=>true],"NumeroDocumentoIdentificacao"=>"05446951433","Nome"=>"Silvera Vieira de Ara�jo Holanda","Email"=>"silveravieira@hotmail.com","DataNascimento"=>null,"Telefone"=>["Numero"=>"996502528","ddd"=>"83"],"Endereco"=>["CEP"=>"58187-000","Municipio"=>["IdMunicipio"=>251140,"DescMunicipio"=>"Picu�","Uf"=>["SigUf"=>"PB","DescUf"=>"PARA�BA"]],"Logradouro"=>"Rua Francisco Mariano da Silva, n.71","Numero"=>null,"Complemento"=>null,"Bairro"=>null],"genero"=>null,"FaixaEtaria"=>null,"corRa�a"=>null,"Profissao"=>null,"Escolaridade"=>null,"RazaoSocial"=>null,"TipoInstituicao"=>null,"AreaAtuacao"=>null,"NomeRepresentante"=>null,"CargoRepresentante"=>null,"EmailRepresentante"=>null
            //    ]],["numProtocolo"=>"00106.000623/2020-56","idRecurso"=>128472,"instancia"=>["IdInstanciaRecurso"=>2,"DescInstanciaRecurso"=>"Segunda Inst�ncia"],"tipoRecurso"=>["IdTipoRecurso"=>2,"DescTipoRecurso"=>"Informa��o incompleta"],"dataRecurso"=>"07/12/2020 10:25:46","prazoAtendimento"=>"02/01/2021 23:59:59","situacaoRecurso"=>["idSituacaoRecurso"=>2,"descSituacaoRecurso"=>"Respondido"],"justificativa"=>"� Vossa Senhoria,  Senhor  Fernando Eug�nio Lopes de Melo, Diretor �Geral do Campus Cedro- IFCE\r\nSolicito a informa��o da carga hor�ria  geral do Professor de Hist�ria do Campus Cedro, o que inclui ensino, pesquisa e extens�o, did�tico-pedag�gico,  capacita��o e administra��o e representa��o. Na resposta fornecida pelo campus apenas identifiquei  o nome do docente, faltou informar a sua lota��o , no caso,   a sua carga hor�ria geral, o que inclui ensino, pesquisa, extens�o, representa��o, capacita��o e administra��o,  por isso, a resposta ficou incompleta. Por isso, solicito a  resposta completa em rela��o � informa��o solicitada. \r\n\r\nNestes termos, aguardo deferimento.\r\n\r\nAtenciosamente,\r\nSilvera Vieira de Ara�jo Holanda\r\n\r\n\r\n","qtdAnexos"=>2,"anexos"=>[["IdAnexoRecurso"=>1673462,"nomeArquivo"=>"anexo_recurso_acesso_informacao_NOVO.pdf","Links"=>[["rel"=>"self","href"=>"https://sistema.ouvidorias.gov.br/api/manifestacoes/2477063/anexos/1673462"]]],["IdAnexoManifestacao"=>1673463,"nomeArquivo"=>"AnexoII_recurso_acesso_informacao.pdf","Links"=>[["rel"=>"self","href"=>"https://sistema.ouvidorias.gov.br/api/manifestacoes/2477063/anexos/1673463"]]]],"solicitante"=>["TipoPessoa"=>["IdTipoPessoa"=>1,"DescTipoPessoa"=>"Pessoa F�sica"],"Pais"=>["IdPais"=>1058,"Descricao"=>"Brasil"],"TipoDocumentoIdentificacao"=>["IdTipoDocumentoIdentificacao"=>1,"DescTipoDocumentoIdentificacao"=>"CPF","IndAtivo"=>true],"NumeroDocumentoIdentificacao"=>"05446951433","Nome"=>"Silvera Vieira de Ara�jo Holanda","Email"=>"silveravieira@hotmail.com","DataNascimento"=>null,"Telefone"=>["Numero"=>"996502528","ddd"=>"83"],"Endereco"=>["CEP"=>"58187-000","Municipio"=>["IdMunicipio"=>251140,"DescMunicipio"=>"Picu�","Uf"=>["SigUf"=>"PB","DescUf"=>"PARA�BA"]],"Logradouro"=>"Rua Francisco Mariano da Silva, n.71","Numero"=>null,"Complemento"=>null,"Bairro"=>null],"genero"=>null,"FaixaEtaria"=>null,"corRa�a"=>null,"Profissao"=>null,"Escolaridade"=>null,"RazaoSocial"=>null,"TipoInstituicao"=>null,"AreaAtuacao"=>null,"NomeRepresentante"=>null,"CargoRepresentante"=>null,"EmailRepresentante"=>null
            //    ]]]];
        }

        $dataRegistro = $arrDetalheManifestacao['DataCadastro'];
        $numProtocoloFormatado =  $this->formatarProcesso($arrDetalheManifestacao['NumerosProtocolo'][0]);


        /**
         * Esta data � gravada na tabela de log detalhada
         * Em caso de altera��o no prazo do atendimento ser� feita nova importa��o dos dados do recurso
         */
        if ($arrRecursosManifestacao <> '') {
            $dataPrazoAtendimento = $arrRecursosManifestacao['Recursos'][(count($arrRecursosManifestacao['Recursos']) - 1)]['prazoAtendimento'];
        } else {
            $dataPrazoAtendimento = $retornoWsLinha['PrazoAtendimento'];
        }

        /**
         * Limpa os registros de detalhe de importa��o com erro para este NUP.
         * Caso ocorra um novo, ser� criado novo registro de erro para o NUP no tratamento desta function.
         */
        $this->limparErrosParaNup($numProtocoloFormatado);

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

        // 1. Caso j� exista um Protocolo no SEI com o mesmo NUP
        if (! is_null($objProtocoloDTOExistente)) {
            // 2. Se existir e for e-Ouv
            if ($tipoManifestacao == 'P') {
                // 2.1 Importar anexos novos se existirem... e retornar log
                // @todo - melhoria pr�xima vers�o e-Ouv
                $this->gravarLogLinha($numProtocoloFormatado, $idRelatorioImportacao, 'Erro na grava��o: ' . 'J� existe um processo (e-Ouv) utilizando o n�mero de protocolo.', 'N', $tipoManifestacao);
            }

            // 3. Se existir e for e-Sic
            if ($tipoManifestacao == 'R') {

                // Data do �ltimo prazo de atendimento para este protocolo
                $objUltimaDataPrazoAtendimento = MdCguEouvAgendamentoINT::retornarUltimaDataPrazoAtendimento($numProtocoloFormatado);

                // 4. Verificar se houve altera��o na data 'PrazoAtendimento'
                if ($objUltimaDataPrazoAtendimento->getDthDthPrazoAtendimento() <> $dataPrazoAtendimento) {

                    // Importar anexos do novo recurso
                    try {

                        $anexoCount = 0;
                        if (isset($arrRecursosManifestacao['Recursos']) && is_array($arrRecursosManifestacao['Recursos'])) {

                            // Verifica Tipo de Recurso
                            $tipo_recurso = $this->verificaTipo($arrRecursosManifestacao['Recursos']);

                            // Carregar documento recurso
                            $this->gerarPDFPedidoInicialESic($arrDetalheManifestacao, $arrRecursosManifestacao, $objProtocoloDTOExistente->getDblIdProtocolo(), $tipo_recurso);
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
                                LogSEI::getInstance()->gravar('M�dulo Integra��o FalaBR - (Recurso primeira inst�ncia) Processo ' . $numProtocoloFormatado . ' enviado para unidade ' . $idUnidadeRecursoPrimeiraInstancia, InfraLog::$INFORMACAO);

                            } catch (Exception $e) {
                                LogSEI::getInstance()->gravar('M�dulo Integra��o FalaBR - (Recurso primeira inst�ncia) N�o foi possivel abrir o Processo ' . $numProtocoloFormatado . ' na unidade ' . $idUnidadeRecursoPrimeiraInstancia . ' - erro: ' . $e, InfraLog::$INFORMACAO);
                            }
                        } else {
                            $this->gravarLogLinha($numProtocoloFormatado, $idRelatorioImportacao, 'Sem recursos novos.', 'N', $tipoManifestacao);
                        }
                    } catch (Exception $e) {
                        $this->gravarLogLinha($numProtocoloFormatado, $idRelatorioImportacao, 'Erro na grava��o recurso: ' . $e, 'N', $tipoManifestacao);
                    }
                } else {
                    // 4.2 Se n�o houve altera��o na data 'PrazoAtendimento' retornar log
                    $this->gravarLogLinha($numProtocoloFormatado, $idRelatorioImportacao, 'J� existe um processo (e-Sic) utilizando o n�mero de protocolo e n�o h� altera��o para nova importa��o.', 'S', $tipoManifestacao);
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
                    $documentoManifestacao = $this->gerarPDFPedidoInicialESic($arrDetalheManifestacao, $arrRecursosManifestacao);
                } else {
                    $documentoManifestacao = $this->gerarPDFPedidoInicial($arrDetalheManifestacao);
                }

                LogSEI::getInstance()->gravar('Importa��o de Manifesta��o ' . $numProtocoloFormatado . ': total de  Anexos configurados: ' . count($arrDocumentos), InfraLog::$INFORMACAO);

//                array_push($arrDocumentos, $documentoManifestacao);
                array_unshift($arrDocumentos, $documentoManifestacao);
                $objEntradaGerarProcedimentoAPI->setDocumentos($arrDocumentos);
                $objSaidaGerarProcedimentoAPI = $objSeiRN->gerarProcedimento($objEntradaGerarProcedimentoAPI);

                $this->gravarLogLinha($numProtocoloFormatado, $idRelatorioImportacao, 'Protocolo ' . $arrDetalheManifestacao['numProtocolo'] . ' gravado com sucesso.', 'S', $tipoManifestacao, $dataPrazoAtendimento);

            } catch (Exception $e) {

                if ($objSaidaGerarProcedimentoAPI != null and $objSaidaGerarProcedimentoAPI->getIdProcedimento() > 0){
                    $this->excluirProcessoComErro($objSaidaGerarProcedimentoAPI->getIdProcedimento());
                }
                $this->gravarLogLinha($numProtocoloFormatado, $idRelatorioImportacao, 'Erro na grava��o: ' . $e, 'N', $tipoManifestacao);
            }
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

    public function gerarPDFPedidoInicialESic($retornoWsLinha, $retornoWsRecursos = null, $IdProtocolo = false, $tipo_recurso = '')
    {
        global $idTipoDocumentoAnexoDadosManifestacao,
               $ocorreuErroAdicionarAnexo,
               $importar_dados_manifestante;

        /***********************************************************************************************
         * DADOS INICIAIS DA MANIFESTA��O
         * Primeiro � gerado o PDF com todas as informa��es referentes a Manifesta��o e mais abaixo
         * � incluindo como um anexo do novo Processo Gerado
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
        $pdf->Cell(0, 20, $menu_count . ". Dados B�sicos da Manifesta��o", 1, 0, 'L');
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
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(180, 20, "Proposta de Melhoria:", 0, 0, 'R');
        $pdf->setFont('arial', '', 12);
        $pdf->Cell(0, 20, $retornoWsLinha['Teor']['PropostaMelhoria'], 0, 1, 'L');

        // Munic�pio do local do fato
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(180, 20, "Munic�pio do local do fato:", 0, 0, 'R');
        if (is_array($retornoWsLinha['Teor']['LocalFato'])) {
            if (is_array($retornoWsLinha['Teor']['LocalFato']['Municipio'])) {
                $pdf->setFont('arial', '', 12);
                $pdf->Cell(70, 20, $retornoWsLinha['Teor']['LocalFato']['Municipio']['DescMunicipio'], 0, 1, 'L');
            }
        }

        // UF do local do fato
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(180, 20, "UF do local do fato:", 0, 0, 'R');
        if (is_array($retornoWsLinha['Teor']['LocalFato'])) {
            if (is_array($retornoWsLinha['Teor']['LocalFato']['Municipio'])) {
                $pdf->setFont('arial', '', 12);
                $pdf->Cell(70, 20, $retornoWsLinha['Teor']['LocalFato']['Municipio']['Uf']['SigUf'], 0, 1, 'L');
            }
        }

        // Descricao Local
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(180, 20, "Local:", 0, 0, 'R');
        if (is_array($retornoWsLinha['Teor']['LocalFato'])) {
            $pdf->setFont('arial', '', 12);
            $pdf->Cell(70, 20, $retornoWsLinha['Teor']['LocalFato']['DescricaoLocalFato'], 0, 1, 'L');
        }

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
                    $pdf->SetFont('arial', 'B', 12);
                    $pdf->Cell(180, 20, "Compromisso:", 0, 0, 'R');
                    $pdf->setFont('arial', '', 12);
                    $pdf->Cell(70, 20, $historico['Resposta']['DataCompromisso'], 0, 1, 'L');

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
        if ($retornoWsRecursos && $retornoWsRecursos <> '') {
            $menu_count++;
            $pdf->Ln(30);
            $pdf->SetFont('arial', 'B', 14);
            $pdf->Cell(0, 20, $menu_count . ". Recursos", true, 0, 'L');
            $pdf->Ln(30);

            $recursos = $retornoWsRecursos['Recursos'];

            if (count($recursos) > 0) {
                foreach ($recursos as $recurso) {

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
                if ($encaminhamento['Encaminhamento'] <> '') {

                    $possui_denuncia = true;

                    // �rg�o Origem
                    $pdf->SetFont('arial', 'B', 12);
                    $pdf->Cell(180, 20, "�rg�o/Entidade de Origem:", 0, 0, 'R');
                    $pdf->setFont('arial', '', 12);
                    $pdf->Cell(70, 20, $encaminhamento['Encaminhamento']['OuvidoriaOrigem']['NomeOuvidoria'], 0, 1, 'L');

                    // �rg�o Destino
                    $pdf->SetFont('arial', 'B', 12);
                    $pdf->Cell(180, 20, "�rg�o/Entidade Destinat�ria:", 0, 0, 'R');
                    $pdf->setFont('arial', '', 12);
                    $pdf->Cell(70, 20, $encaminhamento['Encaminhamento']['OuvidoriaDestino']['NomeOuvidoria'], 0, 1, 'L');

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

        //Renomeuia tirando a extens�o para o SEI trabalhar o Arquivo
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

                    $strNomeArquivoOriginal = $retornoWsAnexoLinha['nomeArquivo'];
                    if ($strNomeArquivoOriginal == null) {
                        $strNomeArquivoOriginal = $retornoWsAnexoLinha['NomeArquivo'];
                    }
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
                        $objAnexoManifestacao->setConteudo(base64_encode(file_get_contents(DIR_SEI_TEMP . '/' . $strNomeArquivoUpload)));

                        if ($this->hashDuplicado(DIR_SEI_TEMP . '/' . $strNomeArquivoUpload)) {
                            $this->gravarLogLinha($numProtocoloFormatado, $idRelatorioImportacao, 'Arquivo j� anexado ao processo: ' . $strNomeArquivoOriginal, 'S', $tipoManifestacao);
                        } else {
                            if ($IdProtocolo && $IdProtocolo <> '') {
                                $objSEIRN = new SeiRN();
                                $objSEIRN->incluirDocumento($objAnexoManifestacao);
                            }

                            $this->gravarLogLinha($numProtocoloFormatado, $idRelatorioImportacao, 'Arquivo adicionado como anexo: ' . $strNomeArquivoOriginal, 'S', $tipoManifestacao);
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
    public function hashDuplicado($strArquivo)
    {
        /**
         * @todo - verificar o hash e um arquivo no mesmo protocolo (� poss�vel?)
         */

        // Verifica hash do arquivo
        $hash = md5_file($strArquivo);

        // Select na tabela Anexe com o hash Criado
        $consulta = new MdCguEouvConsultarHashBD($this->getObjInfraIBanco());
        $res = $consulta->consultarHash($hash);

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
    public function verificaTipo($recursos = null)
    {
        $response = 'P';
        if (isset($recursos) && is_array($recursos)) {
            foreach ($recursos as $recurso) {
                if ($recurso['instancia']['IdInstanciaRecurso'] == 6) {
                    $response = 'PR'; // Pedido Revis�o
                    break;
                }
                if ($recurso['instancia']['IdInstanciaRecurso'] == 7) {
                    $response = 'R3'; // Recurso 3 inst�ncia
                    break;
                }
                if ($recurso['instancia']['IdInstanciaRecurso'] == 3) {
                    $response = 'RC'; // Recurso CGU
                    break;
                }
                if ($recurso['instancia']['IdInstanciaRecurso'] == 2) {
                    $response = 'R2'; // Recurso 2 inst�ncia
                    break;
                }
                if ($recurso['instancia']['IdInstanciaRecurso'] == 1) {
                    $response = 'R1'; // Recurso 1 inst�ncia
                }
            }
        }

        return $response;
    }
}

?>
