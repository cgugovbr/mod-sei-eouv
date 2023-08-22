<?
/*
 * CONTROLADORIA GERAL DA UNIÃO - CGU
 *
 * 23/06/2015 - criado por Rafael Leandro Ferreira
 *
 *
 *Este WebService tem o objetivo de atender a necessidade da CGU que não está suportada dentro dos métodos
 *existentes em SeiWS.php.
 *Foi criado este arquivo para não fazer alterações neste arquivo. O ideal é que posteriormente estes métodos sejam incorporados
 *ao SeiWS para estar disponível como um método homologado pelo SEI.
 */



require_once dirname(__FILE__) . '/../../../../SEI.php';

error_reporting(E_ALL); ini_set('display_errors', '1');

class MdCguEouvWS extends InfraWS {

    public function getObjInfraLog(){
        return LogSEI::getInstance();
    }

    public function testarAgendamentoEouv($SiglaSistema, $IdentificacaoServico, $IdUnidade){

        try{

            InfraDebug::getInstance()->setBolLigado(false);
            InfraDebug::getInstance()->setBolDebugInfra(false);
            InfraDebug::getInstance()->limpar();

            InfraDebug::getInstance()->gravar(__METHOD__);
            InfraDebug::getInstance()->gravar('SIGLA SISTEMA:'.$SiglaSistema);
            InfraDebug::getInstance()->gravar('IDENTIFICACAO SERVICO:'.$IdentificacaoServico);
            InfraDebug::getInstance()->gravar('ID UNIDADE:'.$IdUnidade);

            SessaoSEI::getInstance(false);

                        $objServicoDTO = $this->obterServico($SiglaSistema, $IdentificacaoServico);

            if ($IdUnidade!=null){
                $objUnidadeDTO = $this->obterUnidade($IdUnidade,null);
            }else{
                $objUnidadeDTO = null;
            }

            $this->validarAcessoAutorizado(explode(',',str_replace(' ','',$objServicoDTO->getStrServidor())));

            if ($objUnidadeDTO==null){
                SessaoSEI::getInstance()->simularLogin(null, SessaoSEI::$UNIDADE_TESTE, $objServicoDTO->getNumIdUsuario(), null);
            }else{
                SessaoSEI::getInstance()->simularLogin(null, null, $objServicoDTO->getNumIdUsuario(), $objUnidadeDTO->getNumIdUnidade());
            }

            $objEOuvAgendamentoRn = new MdCguEouvAgendamentoRN();
            $objEOuvAgendamentoRn -> executarImportacaoManifestacaoEOuv();

        }catch(Exception $e){
            $this->processarExcecao($e);
        }
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

    public static function apiRestRequest($url, $token, $tipo)
    {
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

        switch ($httpcode) {
            case 200:
                $response = json_decode($response, true);
                $response = self::decode_result($response);
                break;
            case 401:
                $response = 'Token Invalidado. HTTP Status: ' . $httpcode;
                break;
            case 404: // Nenhum retorno encontrado...
                $response = 'Nenhum retorno encontrado! HTTP Status: ' . $httpcode;
                break;
            default:
                $response = "Erro: Ocorreu algum erro não tratado. HTTP Status: " . $httpcode;
                break;
        }

        return $response;
    }

    static function decode_result($array)
    {
        foreach($array as $key => $value) {
            if(is_array($value)) {
                $array[$key] = self::decode_result($value);
            } else {
                $array[$key] = utf8_decode($value);
            }
        }
        return $array;
    }

    public static function apiValidarToken($url, $username, $password, $client_id, $client_secret)
    {
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
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json",
                "cache-control: no-cache"
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $response = json_decode($response, true);

        return $response;

    }

    public static function gravarParametroToken($tokenGerado){

        $objEouvParametroDTO = new MdCguEouvParametroDTO();
        $objEouvParametroDTO -> setNumIdParametro(10);
        $objEouvParametroDTO -> setStrNoParametro('TOKEN');
        $objEouvParametroDTO -> setStrDeValorParametro($tokenGerado);

        $objEouvParametroRN = new MdCguEouvParametroRN();
        $objEouvParametroRN->alterarParametro($objEouvParametroDTO);

    }

    function  apiRequest($url, $params)
    {

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://treinamentoouvidorias.cgu.gov.br/oauth/token",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_POSTFIELDS => "client_id=15&client_secret=rwkp6899&grant_type=password&username=wsIntegracaoSEI&password=teste123&undefined=",
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/x-www-form-urlencoded",
                "Postman-Token: fbbee7e4-1efd-47be-b64a-ed2ee2dd4f1b",
                "cache-control: no-cache"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            return "cURL Error #:" . $err;
        } else {
            return $response;
        }

    }

    function apiRequestManifestacao($token){

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://treinamentoouvidorias.cgu.gov.br/api/manifestacoes?dataCadastroInicio=31/01/2019%2000:00:00&dataCadastroFim=04/02/2019%2023:59:59",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_POSTFIELDS => "undefined=",
            CURLOPT_HTTPHEADER => array(
                "Authorization: Bearer ZwchJxN61InSdNMS1mpN82lvvLCx8i1f24Wkve6wRjk_aHkEHlF3yaVljtndWtEzN2TfhGmhfZxcLMt0PBoksJPve1zcfj5JVW4l-j0C5P_6fclZEVVNZ-pYR_faU1QQBDD23vJnLhIyXZdc11mhS_gcu9SN1fWTWc3S_QD7nPPlhX14aHm4HOPrRcIHp5MO9fc0gLb-y0aOjZ9sNTC6hIFNnWX5HWzK1ZSj3eo0cquweAOC8wmqDXmXy4DWrAWBRvu3mYpF-BNJZXim31E8emjENMcXot6hAuTI-4TdxFSdQZsnn1KgoAmMQUDwRC8jwAeGZU6JXxBPWyUv06kFRv5udw1PeMclLFSF61DvU4FwIQmaRrACkc4a7hZtDlNfSVZ9E1KgQsaf0gHVD-WFOqbT8bMOLfHNSSkZ8lzI18QvpRBufjwhYBu_g4SwqCF6ZNjR_g",
                "Postman-Token: 0744fa6c-14d5-4da7-9c2d-59c5a63dd47b",
                "cache-control: no-cache"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            $response = utf8_decode($response);
            echo $response;
        }

        exit();

    }

    public function testarGerarPDF($SiglaSistema, $IdentificacaoServico, $IdUnidade)
    {

        try {

            InfraDebug::getInstance()->setBolLigado(false);
            InfraDebug::getInstance()->setBolDebugInfra(false);
            InfraDebug::getInstance()->limpar();

            InfraDebug::getInstance()->gravar(__METHOD__);
            InfraDebug::getInstance()->gravar('SIGLA SISTEMA:' . $SiglaSistema);
            InfraDebug::getInstance()->gravar('IDENTIFICACAO SERVICO:' . $IdentificacaoServico);
            InfraDebug::getInstance()->gravar('ID UNIDADE:' . $IdUnidade);

            SessaoSEI::getInstance(false);

            $objServicoDTO = $this->obterServico($SiglaSistema, $IdentificacaoServico);

            if ($IdUnidade != null) {
                $objUnidadeDTO = $this->obterUnidade($IdUnidade, null);
            } else {
                $objUnidadeDTO = null;
            }

            $this->validarAcessoAutorizado(explode(',', str_replace(' ', '', $objServicoDTO->getStrServidor())));

            if ($objUnidadeDTO == null) {
                SessaoSEI::getInstance()->simularLogin(null, SessaoSEI::$UNIDADE_TESTE, $objServicoDTO->getNumIdUsuario(), null);
            } else {
                SessaoSEI::getInstance()->simularLogin(null, null, $objServicoDTO->getNumIdUsuario(), $objUnidadeDTO->getNumIdUnidade());
            }

            $nup = "00106.000005/2015-49";
            $dt_cadastro = "11/02/2015";
            $id_assunto = "3";
            $desc_assunto = "Ciências";
            $id_sub_assunto = "1122";
            $desc_sub_assunto = "Tecnologia";
            $id_tipo_manifestacao = "1";
            $desc_tipo_manifestacao = "Denúncia";
            $envolve_das4_superior = "Sim";
            $dt_prazo_atendimento = "17/05/2015";
            $nome_orgao = "CGU - Controladoria Geral da União";

            $nome = "João José dos Santos";
            $id_faixa_etaria = "3";
            $desc_faixa_etaria = "21 a 30";
            $id_raca_cor = "1";
            $desc_raca_cor = "Branco";
            $sexo = "Masculino";
            $id_documento_identificacao = "1";
            $desc_documento_identificacao = "Identidade";
            $numero_documento_identificacao = "58749484 SSP-DF";
            $endereco = "SHIS QD 02 Conj. C Casa 25";
            $id_municipio = "1551";
            $desc_municipio = "Brasília";
            $uf = "DF";
            $cep = "70005-080";
            $ddd_telefone = "61";
            $telefone = "2555-4455";
            $email = "joao.santos@cgu.gov.br";

            $id_municipio_fato = "540";
            $desc_municipio_fato = "Patos de Minas";
            $uf_fato = "MG";
            $descricao_fato = "     O servidor público federal J.C.F foi denunciado pelo Ministério Público Federal (MPF) por fraudar o concurso para Analista Judiciário do Tribunal Regional do Trabalho da 3ª Região (TRT-3), em Belo Horizonte. A prova foi realizada em 26 de julho, dia em que o homem foi preso em flagrante pelo crime. Ele segue preso na Penitenciária Nelson Hungria, em Contagem, na Grande BH.
            A Coordenação do concurso foi acionada e o candidato foi levado para uma sala para revista pessoal. O dispositivo eletrônico foi encontrado e o homem preso em flagrante. Laudo da perícia atestou que o dispositivo utilizado pelo denunciado consistia num ?botão espião micro câmera filmadora com 8 GB?, capaz de captar vídeo com áudio em formato digital e fotografias. Os peritos também atestaram que encontraram no dispositivo três registros audiovisuais com imagens do caderno de provas do concurso. O candidato já tinha sido denunciado ao MPF como um dos líderes de uma organização especializada em fraudes de concursos.";
            $envolvidos = array();
            $envolvidos[0] = "Renan Calheiros";
            $envolvidos[1] = "Eduardo Cunha";
            $envolvidos[2] = "Fernando Collor de Mello";
            $envolvidos[3] = "Lindberg Farias";

            $campos_adicionais = array(
                array("Categoria", "Categoria 1"),
                array("É militar", "Não"),
                array("Cpf", "159.161.879-45")
            );



            //require_once("fpdf/fpdf.php");

            $pdf= new InfraPDF("P","pt","A4");

            $pdf->AddPage();
            //$pdf->Image('logog8.jpg');

            $pdf->SetFont('arial','B',18);
            $pdf->Cell(0,5,"Dados da Manifestação",0,1,'C');
            $pdf->Cell(0,5,"","B",1,'C');
            $pdf->Ln(20);

            //***********************************************************************************************
            //1. Dados INICIAIS
            //***********************************************************************************************
            $pdf->SetFont('arial','B',14);
            $pdf->Cell(0,20,"1. Dados Iniciais da Manifestação",0,0,'L');
            $pdf->Ln(20);

            //NUP
            $pdf->SetFont('arial','B',12);
            $pdf->Cell(150,20,"NUP:",0,0,'L');
            $pdf->setFont('arial','',12);
            $pdf->Cell(0,20,$nup,0,1,'L');

            //Data Cadastro
            $pdf->SetFont('arial','B',12);
            $pdf->Cell(150,20,"Data do Cadastro:",0,0,'L');
            $pdf->setFont('arial','',12);
            $pdf->Cell(0,20,$dt_cadastro,0,1,'L');

            //Assunto / SubAssunto
            $pdf->SetFont('arial','B',12);
            $pdf->Cell(150,20,"Assunto/SubAssunto:",0,0,'L');
            $pdf->setFont('arial','',12);
            $pdf->Cell(0,20,$id_assunto ." - ". $desc_assunto." / ". $id_sub_assunto ." - ". $desc_sub_assunto,0,1,'L');

            //Tipo de Manifestação
            $pdf->SetFont('arial','B',12);
            $pdf->Cell(150,20,"Tipo da Manifestação:",0,0,'L');
            $pdf->setFont('arial','',12);
            $pdf->Cell(0,20,$id_tipo_manifestacao . " - " . $desc_tipo_manifestacao ,0,1,'L');

            //EnvolveDas4OuSuperior
            $pdf->SetFont('arial','B',12);
            $pdf->Cell(450,20,"Denúncia Envolvendo Ocupante de Cargo Comissionado DAS4 ou Superior?:",0,0,'L');
            $pdf->setFont('arial','',12);
            $pdf->Cell(20,20,$envolve_das4_superior,0,1,'L');

            //Prazo de Atendimento
            $pdf->SetFont('arial','B',12);
            $pdf->Cell(150,20,"Prazo de Atendimento:",0,0,'L');
            $pdf->setFont('arial','',12);
            $pdf->Cell(70,20,$dt_prazo_atendimento,0,1,'L');

            //Nome do Órgão
            $pdf->SetFont('arial','B',12);
            $pdf->Cell(150,20,"Nome do Órgão:",0,0,'L');
            $pdf->setFont('arial','',12);
            $pdf->Cell(70,20,$nome_orgao,0,1,'L');

            //***********************************************************************************************
            //2. Dados do Solicitante
            //***********************************************************************************************
            $pdf->Ln(20);
            $pdf->SetFont('arial','B',14);
            $pdf->Cell(70,20,"2. Dados do Solicitante:",0,0,'L');
            $pdf->Ln(20);

            //Nome do Solicitante
            $pdf->SetFont('arial','B',12);
            $pdf->Cell(150,20,"Nome do Solicitante:",0,0,'L');
            $pdf->setFont('arial','',12);
            $pdf->Cell(70,20,$nome,0,1,'L');

            //Faixa Etária
            $pdf->SetFont('arial','B',12);
            $pdf->Cell(150,20,"Faixa Etária:",0,0,'L');
            $pdf->setFont('arial','',12);
            $pdf->Cell(70,20,$id_faixa_etaria . " - " . $desc_faixa_etaria,0,1,'L');

            //Raça Cor
            $pdf->SetFont('arial','B',12);
            $pdf->Cell(150,20,"Raça/Cor:",0,0,'L');
            $pdf->setFont('arial','',12);
            $pdf->Cell(70,20,$id_raca_cor . " - " . $desc_raca_cor,0,1,'L');

            //Sexo
            $pdf->SetFont('arial','B',12);
            $pdf->Cell(150,20,"Sexo:",0,0,'L');
            $pdf->setFont('arial','',12);
            $pdf->Cell(70,20,$sexo,0,1,'L');

            //Documento Identificação
            $pdf->SetFont('arial','B',12);
            $pdf->Cell(170,20,"Documento de Identificação:",0,0,'L');
            $pdf->setFont('arial','',12);
            $pdf->Cell(70,20,$id_documento_identificacao . " - " . $desc_documento_identificacao ,0,1,'L');

            //Número do Documento Identificação
            $pdf->SetFont('arial','B',12);
            $pdf->Cell(150,20,"Número do Documento:",0,0,'L');
            $pdf->setFont('arial','',12);
            $pdf->Cell(70,20,$numero_documento_identificacao,0,1,'L');

            $pdf->ln(4);
            //Endereço
            $pdf->SetFont('arial','B',12);
            $pdf->Cell(70,20,"Endereço:",0,1,'L');
            $pdf->setFont('arial','',12);
            $pdf->Cell(70,20,$endereco,0,1,'L');
            $pdf->Cell(70,20,$desc_municipio . " - " . $uf,0,1,'L');

            //CEP
            $pdf->Cell(70,20,"CEP:",0,0,'L');
            $pdf->setFont('arial','',12);
            $pdf->Cell(70,20,$cep,0,1,'L');

            //Telefone
            $pdf->SetFont('arial','B',12);
            $pdf->Cell(70,20,"Telefone:",0,0,'L');
            $pdf->setFont('arial','',12);
            $pdf->Cell(70,20,"(".$ddd_telefone.") " . $telefone,0,1,'L');

            //Email
            $pdf->SetFont('arial','B',12);
            $pdf->Cell(70,20,"E-mail:",0,0,'L');
            $pdf->setFont('arial','',12);
            $pdf->Cell(70,20,$email,0,1,'L');

            //***********************************************************************************************
            //3. Dados do Fato da Manifestação
            //***********************************************************************************************
            $pdf->Ln(20);
            $pdf->SetFont('arial','B',14);
            $pdf->Cell(70,20,"3. Fato da Manifestação:",0,0,'L');
            $pdf->Ln(20);

            //Município/UF
            $pdf->SetFont('arial','B',12);
            $pdf->Cell(115,20,"Município/UF:",0,0,'L');
            $pdf->setFont('arial','',12);
            $pdf->Cell(70,20,$id_municipio_fato . " - " . $desc_municipio_fato . " / " . $uf_fato,0,1,'L');

            //Descrição
            $pdf->SetFont('arial','B',12);
            $pdf->Cell(115,20,"Descrição:",0,1,'L');
            $pdf->setFont('arial','',12);
            $pdf->MultiCell(0,20,$descricao_fato,0,'J');

            //Envolvidos
            $pdf->SetFont('arial','B',12);
            $pdf->Cell(115,20,"Envolvidos:",0,1,'L');
            $pdf->setFont('arial','',12);

            for($x = 0; $x < count($envolvidos); $x++){
                $pdf->Cell(0,20,$envolvidos[$x],0,1,'L');
            }

            //***********************************************************************************************
            //4. Campos Adicionais
            //***********************************************************************************************
            $pdf->Ln(20);
            $pdf->SetFont('arial','B',14);
            $pdf->Cell(70,20,"4. Campos Adicionais:",0,0,'L');
            $pdf->Ln(20);

            for($y = 0; $y < count($campos_adicionais); $y++){
                $pdf->SetFont('arial','B',12);
                $pdf->Cell(115,20,$campos_adicionais[$y][0].":",0,0,'L');
                $pdf->setFont('arial','',12);
                $pdf->Cell(0,20,$campos_adicionais[$y][1],0,1,'L');
            }

            $pdf->Output(DIR_SEI_TEMP."/"."arquivoRafaelTeste9.pdf","F");

        }catch(Exception $e){
            $this->processarExcecao($e);
        }
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

    public static function verificaRetornoWS($retornoWsLista)
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
                // Token expirado, necessário gerar novo Token
                if (strpos($retornoWs, 'Invalidado') !== false) {
                    return "Token Invalidado";
                }
                // Outro erro
                if (strpos($retornoWs, 'Erro') !== false) {
                    return "Erro:" . $retornoWs;
                }
            }
        } else {
            // Faz tratamento diferenciado para consulta por Protocolo específico
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
        $debugLocal && LogSEI::getInstance()->gravar('[executarServicoConsultaRecursos] Parâmetros: $ultimaDataExecucao: ' . $ultimaDataExecucao . ' | $dataAtual: ' . $dataAtual . ' | $numprotocolo: ' . $numprotocolo);

        $arrParametrosUrl = array(
            'dataAberturaInicio' => $ultimaDataExecucao,
            'dataAberturaFim' => $dataAtual,
            'NumProtocolo' => $numprotocolo
        );

        $arrParametrosUrl = http_build_query($arrParametrosUrl);

        $urlConsultaRecurso = $urlConsultaRecurso . "?" . $arrParametrosUrl;

        $retornoWs = self::apiRestRequest($urlConsultaRecurso, $token, 1);

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
                    $arrResult = array_merge($arrResult, $retornoWsErro);
                }
            }
        }

        return $arrResult;
    }
    public function executarImportacaoLinha($retornoWsLinha, $tipoManifestacao = 'P')
    {
        $debugLocal = false;

        $objProcedimentoDTO = new ProcedimentoDTO();
        $objProcedimentoDTO->setDblIdProcedimento(null);

        $linkDetalheManifestacao = $retornoWsLinha['Links'][0]['href'];
        $arrDetalheManifestacao = MdCguEouvWS::apiRestRequest($linkDetalheManifestacao, $this->token, 2);

        /**
         * Verifica Tipo de Manifestação e-Ouv ou e-Sic
         */
        if ($tipoManifestacao == 'P' && $retornoWsLinha['TipoManifestacao']['IdTipoManifestacao'] <> 8) {
            $debugLocal && LogSEI::getInstance()->gravar('Importação tipo "P" - tipoManifestação <> "8"');
            $manifestacaoESic = false;
            $idUnidadeDestino = $this->idUnidadeOuvidoria;
        } elseif ($tipoManifestacao == 'R' && $retornoWsLinha['TipoManifestacao']['IdTipoManifestacao'] == 8) {
            $debugLocal && LogSEI::getInstance()->gravar('Importação tipo "R" - tipoManifestação == "8"');
            $manifestacaoESic = true;
            $idUnidadeDestino = $this->idUnidadeEsicPrincipal;

            /**
             * Importar Recursos caso seja manifestação e-Sic (Tipo 8)
             */
            $arrRecursosManifestacao = MdCguEouvWS::apiRestRequest($this->urlWebServiceESicRecursos . '?NumProtocolo=' . $arrDetalheManifestacao['NumerosProtocolo'][0], $this->token, 2);
        }

        $numProtocoloFormatado =  $this->formatarProcesso($arrDetalheManifestacao['NumerosProtocolo'][0]);

        // Verifica se o tipo de manifestação é suportado
        $numIdTipoManifestacao = $retornoWsLinha['TipoManifestacao']['IdTipoManifestacao'];
        if ($numIdTipoManifestacao > 8) {
            // Se não for marca como sucesso para evitar reimportação na próxima execução.
            $this->gravarLogLinha($numProtocoloFormatado, $this->idRelatorioImportacao,
                'Tipo de manifestação não suportado (ID = '.$numIdTipoManifestacao.'). Não será importada.', 'S');
            return;
        }


        /**
         * Esta data é gravada na tabela de log detalhada
         * Em caso de alteração no prazo do atendimento será feita nova importação dos dados do recurso
         * Verifica se o retorno dos recursos não é uma string
         */
        if ($arrRecursosManifestacao <> '' && !is_string($arrRecursosManifestacao)) {
            $debugLocal && LogSEI::getInstance()->gravar('Possui $arrRecursosManifestacao - qtd: ' . count($arrRecursosManifestacao['Recursos']));
            $dataPrazoAtendimento = $arrRecursosManifestacao['Recursos'][(count($arrRecursosManifestacao['Recursos']) - 1)]['prazoAtendimento'];
        } else {
            $debugLocal && LogSEI::getInstance()->gravar('NÃO possui $arrRecursosManifestacao');
            $dataPrazoAtendimento = $retornoWsLinha['PrazoAtendimento'];
        }

        /**
         * Limpa os registros de detalhe de importação com erro para este NUP.
         * Caso ocorra um novo, será criado novo registro de erro para o NUP no tratamento desta function.
         */
        $this->limparErrosParaNup($numProtocoloFormatado);

        if (!isset($arrDetalheManifestacao['TipoManifestacao']['IdTipoManifestacao'])) {
            $this->gravarLogLinha($numProtocoloFormatado, $this->idRelatorioImportacao, 'Tipo de processo não foi informado.', 'N');
            /**
             * @todo - não deveria parara aqui? se não tiver um tipo de processo não informado?
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
                $this->gravarLogLinha($numProtocoloFormatado, $this->objEouvRelatorioImportacaoDTO->getNumIdRelatorioImportacao(), 'Não existe mapeamento DePara do Tipo de Manifestação do FalaBR (E-Ouv|E-Sic) para o tipo de procedimento do SEI.', 'N');
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
                $debugLocal && LogSEI::getInstance()->gravar('Importando Linha Manifestação e-ouv - protocolo: ' . $this->formatarProcesso($numProtocoloFormatado));
                // 2.1 Importar anexos novos se existirem... e retornar log
                // @todo - melhoria próxima versão e-Ouv
                $this->gravarLogLinha($numProtocoloFormatado, $this->idRelatorioImportacao, 'Erro na gravação: ' . 'Já existe um processo (e-Ouv) utilizando o número de protocolo.', 'N', $tipoManifestacao);
            }

            // 3. Se existir e for e-Sic
            if ($tipoManifestacao == 'R') {

                $debugLocal && LogSEI::getInstance()->gravar('Importando Linha Manifestação e-SIC - protocolo: ' . $this->formatarProcesso($numProtocoloFormatado));

                /**
                 * @todo - @teste
                 * Teste aqui pra validar se o prazo sendo 'maior' na petição inicial já não deve importar os recursos..... (??)
                 */
                // Data do último prazo de atendimento para este protocolo
                $objUltimaDataPrazoAtendimento = MdCguEouvAgendamentoINT::retornarUltimaDataPrazoAtendimento($numProtocoloFormatado);

                // 4. Verificar se houve alteração na data 'PrazoAtendimento'
                if (isset($objUltimaDataPrazoAtendimento) && $objUltimaDataPrazoAtendimento->getDthDthPrazoAtendimento() > $dataPrazoAtendimento) {

                    // Importar anexos do novo recurso
                    try {
                        $anexoCount = 0;
                        if (isset($arrRecursosManifestacao['Recursos']) && is_array($arrRecursosManifestacao['Recursos'])) {

                            // Verifica Tipo de Recurso
                            $tipo_recurso = $this->verificaTipo($arrRecursosManifestacao['Recursos']);

                            $debugLocal && LogSEI::getInstance()->gravar('[executarImportacaoLinha] Importando o recurso do protocolo: ' . $numProtocoloFormatado);

                            // Carregar documento recurso
                            $mdCguEouvGerarPdfEsic = new MdCguEouvGerarPdfEsic();
                            $mdCguEouvGerarPdfEsic->gerarPDFDocumentoESic($arrDetalheManifestacao, $arrRecursosManifestacao, $objProtocoloDTOExistente->getDblIdProtocolo(), $tipo_recurso);
                            LogSEI::getInstance()->gravar('Módulo Integração FalaBR - Importação de Recurso ' . $numProtocoloFormatado . ': total de  Anexos configurados: ' . $anexoCount, InfraLog::$INFORMACAO);
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
                            // Se for 1 instância envia processo para ESIC_ID_UNIDADE_RECURSO_PRIMEIRA_INSTANCIA
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
                                LogSEI::getInstance()->gravar('Módulo Integração FalaBR - (Recurso tipo ' . $tipo_recurso . ') Processo ' . $numProtocoloFormatado . ' enviado para unidade ' . $this->idUnidadeRecursoPrimeiraInstancia, InfraLog::$INFORMACAO);

                            } catch (Exception $e) {
                                LogSEI::getInstance()->gravar('Módulo Integração FalaBR - (Recurso tipo ' . $tipo_recurso . ') Não foi possivel abrir o Processo ' . $numProtocoloFormatado . ' na unidade ' . $this->idUnidadeRecursoPrimeiraInstancia . ' - erro: ' . $e, InfraLog::$INFORMACAO);
                            }
                        } else {
                            /**
                             * @todo - confirmar - aqui deve ficar como 'N' ou 'S'? Se fircar como 'N' entra como erro... ?? e é preciso gravar que não houve recurso mas teve alteração na data de prazo de atencimento,
                             * esta data precisa ser salva no banco de dados... comentar/documentar aqui!
                             */
                            $this->gravarLogLinha($numProtocoloFormatado, $this->idRelatorioImportacao, 'Sem recursos novos.', 'S', $tipoManifestacao, $dataPrazoAtendimento);
                        }
                    } catch (Exception $e) {
                        $this->gravarLogLinha($numProtocoloFormatado, $this->idRelatorioImportacao, 'Erro na gravação recurso: ' . $e, 'N', $tipoManifestacao);
                    }
                } else {
                    // 4.2 Se não houve alteração na data 'PrazoAtendimento' retornar log
                    $this->gravarLogLinha($numProtocoloFormatado, $this->idRelatorioImportacao, 'Já existe um processo (e-Sic) utilizando o número de protocolo e não há alteração para nova importação.', 'S', $tipoManifestacao, $dataPrazoAtendimento);
                }
            }
        } else {
            $this->criarNovoProcesso($idTipoManifestacaoSei, $retornoWsLinha['TipoManifestacao']['IdTipoManifestacao'], $manifestacaoESic, $arrDetalheManifestacao, $idUnidadeDestino, $numProtocoloFormatado, $tipoManifestacao, $arrRecursosManifestacao);
        }
    }
    public function criarNovoProcesso($idTipoManifestacaoSei, $idTipoManifestacao, bool $manifestacaoESic, $arrDetalheManifestacao, $idUnidadeDestino, $numProtocoloFormatado, $tipoManifestacao, $arrRecursosManifestacao)
    {
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
                $mdCguEouvGerarPdfEsic = new MdCguEouvGerarPdfEsic();
                $documentoManifestacao = $mdCguEouvGerarPdfEsic->gerarPDFDocumentoESic($arrDetalheManifestacao, $arrRecursosManifestacao);
            } else {
                $mdCguEouvGerarPdfInicial = new MdCguEouvGerarPdfInicial($arrDetalheManifestacao);
                $documentoManifestacao = $mdCguEouvGerarPdfInicial->gerarPDFPedidoInicial($this->idTipoDocumentoAnexoDadosManifestacao);
            }

            LogSEI::getInstance()->gravar('Importação de Manifestação ' . $numProtocoloFormatado . ': total de  Anexos configurados: ' . count($arrDocumentos), InfraLog::$INFORMACAO);

            array_unshift($arrDocumentos, $documentoManifestacao);
            $objEntradaGerarProcedimentoAPI->setDocumentos($arrDocumentos);
            $objSaidaGerarProcedimentoAPI = $objSeiRN->gerarProcedimento($objEntradaGerarProcedimentoAPI);
            $this->gravarLogLinha($numProtocoloFormatado, $this->idRelatorioImportacao, 'Protocolo ' . $arrDetalheManifestacao['numProtocolo'] . ' gravado com sucesso.', 'S', $tipoManifestacao);

        } catch (Exception $e) {

            if ($objSaidaGerarProcedimentoAPI != null and $objSaidaGerarProcedimentoAPI->getIdProcedimento() > 0) {
                $this->excluirProcessoComErro($objSaidaGerarProcedimentoAPI->getIdProcedimento());
            }
            $this->gravarLogLinha($numProtocoloFormatado, $this->idRelatorioImportacao, 'Erro na gravação: ' . $e, 'N', $tipoManifestacao);
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
        $objProtocoloDTOExistente = $this->verificarProtocoloExistente($numProtocoloFormatado);

        // Caso já exista um Protocolo no SEI continua, caso contrário apenas registra o log
        if (! is_null($objProtocoloDTOExistente)) {

            $debugLocal && LogSEI::getInstance()->gravar('[executarImportacaoLinhaRecursos] Existe o protocolo: ' . $numProtocoloFormatado);

            // Se existir e for e-Sic
            if ($tipoManifestacao == 'R') {

                $debugLocal && LogSEI::getInstance()->gravar('[executarImportacaoLinhaRecursos] É do tipo: ' . $tipoManifestacao . ' > ' . $this->verificaTipo($arrRecursosManifestacao, 'R'));

                // Data do último prazo de atendimento para este protocolo sem o tipo de recurso para buscar qualquer um recurso anterior
                $objUltimaDataPrazoAtendimento = MdCguEouvAgendamentoINT::retornarUltimaDataPrazoAtendimento($numProtocoloFormatado);
                $debugLocal && LogSEI::getInstance()->gravar('[executarImportacaoLinhaRecursos] Último prazo de atendimento: ' . $objUltimaDataPrazoAtendimento);

                /**
                 * Regra de bloqueio na criação de novos recursos caso já exista um recurso superior ao atualmente listado
                 * - regra implementada devido à duplicidade na importação dos processos
                 */
                $ultimoTipoRecursoImportado = MdCguEouvAgendamentoINT::retornarTipoManifestacao($this->idRelatorioImportacao, $numProtocoloFormatado);
                $ultimoTipoRecursoImportado = $ultimoTipoRecursoImportado ? $ultimoTipoRecursoImportado->getStrTipManifestacao() : $this->verificaTipo($arrRecursosManifestacao, 'R1');
                $debugLocal && LogSEI::getInstance()->gravar('[executarImportacaoLinhaRecursos] Ultimo tipo de recurso importado: ' . $ultimoTipoRecursoImportado . ' - Tipo recurso atual: ' . $this->verificaTipo($arrRecursosManifestacao, 'R1'));

                $permiteImportacaoRecursoAtual = $this->permiteImportacaoRecursoAtual($this->verificaTipo($arrRecursosManifestacao, 'R1'), $ultimoTipoRecursoImportado);
                $debugLocal && LogSEI::getInstance()->gravar('[executarImportacaoLinhaRecursos] Permite criar o recurso atual: ' . $permiteImportacaoRecursoAtual);

                if ($permiteImportacaoRecursoAtual == 'bloquear') {
                    $debugLocal && LogSEI::getInstance()->gravar('[executarImportacaoLinhaRecursos] Não foi permitido criar o recurso, pode deve haver recurso anterior já importado');
                    // Se não for permitido criar o recurso
                    $this->gravarLogLinha($numProtocoloFormatado, $this->idRelatorioImportacao, 'O recurso existente no FalaBR não será importado devido à regra implementada - tipoAtual: "' . $this->verificaTipo($arrRecursosManifestacao, 'R') . '" | tipoAnterior: '. $ultimoTipoRecursoImportado .' | protocolo.', 'S', $ultimoTipoRecursoImportado, $objUltimaDataPrazoAtendimento->getDthDthPrazoAtendimento());
                    return;
                }

                // Verificar se houve alteração na data 'PrazoAtendimento'
                if (($objUltimaDataPrazoAtendimento && $objUltimaDataPrazoAtendimento->getDthDthPrazoAtendimento() <> $dataPrazoAtendimento) || $objUltimaDataPrazoAtendimento === null) {

                    $debugLocal && LogSEI::getInstance()->gravar('[executarImportacaoLinhaRecursos] Data de prazo de atendimento diferente da última, incinia importacao');

                    // Importar anexos do novo recurso
                    try {
                        if (isset($arrRecursosManifestacao)) {
                            $anexoCount = isset($arrRecursosManifestacao['qtdAnexos']) ? $arrRecursosManifestacao['qtdAnexos'] : 0;

                            // Verifica Tipo de Recurso
                            $tipo_recurso = $this->verificaTipo($arrRecursosManifestacao);

                            // Vincular Recursos com as unidades corretas conforme o tipo de recurso
                            // Se for 1 instância envia processo para ESIC_ID_UNIDADE_RECURSO_PRIMEIRA_INSTANCIA
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

                            $debugLocal && LogSEI::getInstance()->gravar('[executarImportacaoLinhaRecursos] Tipo de recurso: ' . $tipo_recurso);

                            // Buscar dados da Manifestação
                            $numProtocoloSemFormatacao = str_replace(['.', '/', '-'], ['', '', ''], $numProtocoloFormatado);
                            $retornoWsLinha = $this->executarServicoConsultaManifestacoes($this->urlWebServiceEOuv, $this->token, null, null, $numProtocoloSemFormatacao, $this->idRelatorioImportacao);
                            $linkDetalheManifestacao = $retornoWsLinha[0]['Links'][0]['href'];
                            $arrDetalheManifestacao = MdCguEouvWS::apiRestRequest($linkDetalheManifestacao, $this->token, 2);

                            $debugLocal && LogSEI::getInstance()->gravar('Importando Recurso processo: ' . $numProtocoloFormatado . ' | tipo: ' . $tipo_recurso);

                            /**
                             * Verificar o tipo de recurso de for diferente de segunda instãncia, trazer todos os recursos para o documento pdf
                             */
                            if ($tipo_recurso <> 'R1') {
                                $arrRecursosManifestacaoComAnteriores = $this->executarServicoConsultaRecursos($this->urlWebServiceESicRecursos, $this->token, null, null, $numProtocoloSemFormatacao);
                                $mdCguEouvGerarPdfEsic = new MdCguEouvGerarPdfEsic();
                                $mdCguEouvGerarPdfEsic->gerarPDFDocumentoESic($arrDetalheManifestacao, $arrRecursosManifestacaoComAnteriores, $objProtocoloDTOExistente->getDblIdProtocolo(), $tipo_recurso);
                            } else {
                                $mdCguEouvGerarPdfEsic = new MdCguEouvGerarPdfEsic();
                                $mdCguEouvGerarPdfEsic->gerarPDFDocumentoESic($arrDetalheManifestacao, $arrRecursosManifestacao, $objProtocoloDTOExistente->getDblIdProtocolo(), $tipo_recurso);
                            }

                            $this->gravarLogLinha($numProtocoloFormatado, $this->idRelatorioImportacao, 'Recurso tipo ' . $tipo_recurso . ' com protocolo ' . $numProtocoloFormatado . ' importado com sucesso com ' . $anexoCount . ' anexos incluidos no protocolo.', 'S', $tipo_recurso, $dataPrazoAtendimento);
                            $debugLocal && LogSEI::getInstance()->gravar('Importando Recurso processo: ' . $numProtocoloFormatado . ' | tipo: ' . $tipo_recurso . 'depois de gravar log ?!');
                            LogSEI::getInstance()->gravar('Módulo Integração FalaBR - Importação de Recurso ' . $numProtocoloFormatado . ': total de  Anexos configurados: ' . $anexoCount, InfraLog::$INFORMACAO);

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
                                LogSEI::getInstance()->gravar('Módulo Integração FalaBR - (Recurso tipo ' . $tipo_recurso . ') Processo ' . $numProtocoloFormatado . ' enviado para unidade ' . $this->idUnidadeRecursoPrimeiraInstancia, InfraLog::$INFORMACAO);

                            } catch (Exception $e) {
                                LogSEI::getInstance()->gravar('Módulo Integração FalaBR - (Recurso tipo ' . $tipo_recurso . ') Não foi possivel abrir o Processo ' . $numProtocoloFormatado . ' na unidade ' . $this->idUnidadeRecursoPrimeiraInstancia . ' - erro: ' . $e, InfraLog::$INFORMACAO);
                            }
                        } else {
                            $this->gravarLogLinha($numProtocoloFormatado, $this->idRelatorioImportacao, 'Sem recursos novos.', 'S', $ultimoTipoRecursoImportado, $dataPrazoAtendimento);
                        }
                    } catch (Exception $e) {
                        $debugLocal && LogSEI::getInstance()->gravar('[executarImportacaoLinhaRecursos] Erro importando anexo do recruso');
                        $this->gravarLogLinha($numProtocoloFormatado, $this->idRelatorioImportacao, 'Erro na gravação recurso: ' . $e, 'N', $tipoManifestacao);
                    }
                } else {
                    $debugLocal && LogSEI::getInstance()->gravar('[executarImportacaoLinhaRecursos] Não importou recurso pois o prazo de atendimento é igual e não faz nada.. não atualiza o log para não atualizar a data do novo prazo nem o tipo de recurso');
                    // Se não houve alteração na data 'PrazoAtendimento' retornar log
                    $this->gravarLogLinha($numProtocoloFormatado, $this->idRelatorioImportacao, 'Já existe um recurso (e-Sic) do tipo "' . $this->verificaTipo($arrRecursosManifestacao, 'R') . '" para este protocolo e não há alteração para nova importação.', 'S', $ultimoTipoRecursoImportado, $objUltimaDataPrazoAtendimento->getDthDthPrazoAtendimento());
                }
            }
        } else {
            $this->gravarLogLinha($numProtocoloFormatado, $this->idRelatorioImportacao, 'Existe recurso para o processo ' . $numProtocoloFormatado . ', porém este processo não existe no SEI. Provavelmente é um processo antes da data de início de utilização do módulo ou o Tipo de Manifestação do FalaBR não foi registrada para este módulo.', 'S', $tipoManifestacao);
        }
    }

}
?>