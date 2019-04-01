<?
/*
 * CONTROLADORIA GERAL DA UNI�O - CGU
 *
 * 23/06/2015 - criado por Rafael Leandro Ferreira
 *
 *
 *Este WebService tem o objetivo de atender a necessidade da CGU que n�o est� suportada dentro dos m�todos
 *existentes em SeiWS.php.
 *Foi criado este arquivo para n�o fazer altera��es neste arquivo. O ideal � que posteriormente estes m�todos sejam incorporados
 *ao SeiWS para estar dispon�vel como um m�todo homologado pelo SEI.
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

            /*define('OAUTH2_CLIENT_ID', '15');
            define('OAUTH2_CLIENT_SECRET', 'rwkp6899');


            $apiURLBase = 'https://treinamentoouvidorias.cgu.gov.br/';
            $tokenURL = 'https://treinamentoouvidorias.cgu.gov.br/oauth/token';

            //$result = file_get_contents('https://treinamentoouvidorias.cgu.gov.br/oauth/token/?client_id=15&');

            // Exchange the auth code for a token
            $token = $this->apiRequest($tokenURL, FALSE, array(
                'client_id' => OAUTH2_CLIENT_ID,
                'client_secret' => OAUTH2_CLIENT_SECRET,
                'grant_type' => 'password',
                'username' => 'wsIntegracaoSEI',
                'password' => 'teste123'
            ));


            $token = json_decode($token, true);

            //echo $token['access_token'];
            //$_SESSION['access_token'] = $token->access_token;


            $this->apiRequestManifestacao($token['access_token']);
            */

            $objEOuvAgendamentoRn = new MdCguEouvAgendamentoRN();
            $objEOuvAgendamentoRn -> executarImportacaoManifestacaoEOuv();

           
        }catch(Exception $e){
            $this->processarExcecao($e);
        }
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
            $desc_assunto = "Ci�ncias";
            $id_sub_assunto = "1122";
            $desc_sub_assunto = "Tecnologia";
            $id_tipo_manifestacao = "1";
            $desc_tipo_manifestacao = "Den�ncia";
            $envolve_das4_superior = "Sim";
            $dt_prazo_atendimento = "17/05/2015";
            $nome_orgao = "CGU - Controladoria Geral da Uni�o";

            $nome = "Jo�o Jos� dos Santos";
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
            $desc_municipio = "Bras�lia";
            $uf = "DF";
            $cep = "70005-080";
            $ddd_telefone = "61";
            $telefone = "2555-4455";
            $email = "joao.santos@cgu.gov.br";

            $id_municipio_fato = "540";
            $desc_municipio_fato = "Patos de Minas";
            $uf_fato = "MG";
            $descricao_fato = "     O servidor p�blico federal J.C.F foi denunciado pelo Minist�rio P�blico Federal (MPF) por fraudar o concurso para Analista Judici�rio do Tribunal Regional do Trabalho da 3� Regi�o (TRT-3), em Belo Horizonte. A prova foi realizada em 26 de julho, dia em que o homem foi preso em flagrante pelo crime. Ele segue preso na Penitenci�ria Nelson Hungria, em Contagem, na Grande BH.
            A Coordena��o do concurso foi acionada e o candidato foi levado para uma sala para revista pessoal. O dispositivo eletr�nico foi encontrado e o homem preso em flagrante. Laudo da per�cia atestou que o dispositivo utilizado pelo denunciado consistia num ?bot�o espi�o micro c�mera filmadora com 8 GB?, capaz de captar v�deo com �udio em formato digital e fotografias. Os peritos tamb�m atestaram que encontraram no dispositivo tr�s registros audiovisuais com imagens do caderno de provas do concurso. O candidato j� tinha sido denunciado ao MPF como um dos l�deres de uma organiza��o especializada em fraudes de concursos.";
            $envolvidos = array();
            $envolvidos[0] = "Renan Calheiros";
            $envolvidos[1] = "Eduardo Cunha";
            $envolvidos[2] = "Fernando Collor de Mello";
            $envolvidos[3] = "Lindberg Farias";

            $campos_adicionais = array(
                array("Categoria", "Categoria 1"),
                array("� militar", "N�o"),
                array("Cpf", "159.161.879-45")
            );



            //require_once("fpdf/fpdf.php");

            $pdf= new InfraPDF("P","pt","A4");

            $pdf->AddPage();
            //$pdf->Image('logog8.jpg');

            $pdf->SetFont('arial','B',18);
            $pdf->Cell(0,5,"Dados da Manifesta��o",0,1,'C');
            $pdf->Cell(0,5,"","B",1,'C');
            $pdf->Ln(20);

            //***********************************************************************************************
            //1. Dados INICIAIS
            //***********************************************************************************************
            $pdf->SetFont('arial','B',14);
            $pdf->Cell(0,20,"1. Dados Iniciais da Manifesta��o",0,0,'L');
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

            //Tipo de Manifesta��o
            $pdf->SetFont('arial','B',12);
            $pdf->Cell(150,20,"Tipo da Manifesta��o:",0,0,'L');
            $pdf->setFont('arial','',12);
            $pdf->Cell(0,20,$id_tipo_manifestacao . " - " . $desc_tipo_manifestacao ,0,1,'L');

            //EnvolveDas4OuSuperior
            $pdf->SetFont('arial','B',12);
            $pdf->Cell(450,20,"Den�ncia Envolvendo Ocupante de Cargo Comissionado DAS4 ou Superior?:",0,0,'L');
            $pdf->setFont('arial','',12);
            $pdf->Cell(20,20,$envolve_das4_superior,0,1,'L');

            //Prazo de Atendimento
            $pdf->SetFont('arial','B',12);
            $pdf->Cell(150,20,"Prazo de Atendimento:",0,0,'L');
            $pdf->setFont('arial','',12);
            $pdf->Cell(70,20,$dt_prazo_atendimento,0,1,'L');

            //Nome do �rg�o
            $pdf->SetFont('arial','B',12);
            $pdf->Cell(150,20,"Nome do �rg�o:",0,0,'L');
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

            //Faixa Et�ria
            $pdf->SetFont('arial','B',12);
            $pdf->Cell(150,20,"Faixa Et�ria:",0,0,'L');
            $pdf->setFont('arial','',12);
            $pdf->Cell(70,20,$id_faixa_etaria . " - " . $desc_faixa_etaria,0,1,'L');

            //Ra�a Cor
            $pdf->SetFont('arial','B',12);
            $pdf->Cell(150,20,"Ra�a/Cor:",0,0,'L');
            $pdf->setFont('arial','',12);
            $pdf->Cell(70,20,$id_raca_cor . " - " . $desc_raca_cor,0,1,'L');

            //Sexo
            $pdf->SetFont('arial','B',12);
            $pdf->Cell(150,20,"Sexo:",0,0,'L');
            $pdf->setFont('arial','',12);
            $pdf->Cell(70,20,$sexo,0,1,'L');

            //Documento Identifica��o
            $pdf->SetFont('arial','B',12);
            $pdf->Cell(170,20,"Documento de Identifica��o:",0,0,'L');
            $pdf->setFont('arial','',12);
            $pdf->Cell(70,20,$id_documento_identificacao . " - " . $desc_documento_identificacao ,0,1,'L');

            //N�mero do Documento Identifica��o
            $pdf->SetFont('arial','B',12);
            $pdf->Cell(150,20,"N�mero do Documento:",0,0,'L');
            $pdf->setFont('arial','',12);
            $pdf->Cell(70,20,$numero_documento_identificacao,0,1,'L');

            $pdf->ln(4);
            //Endere�o
            $pdf->SetFont('arial','B',12);
            $pdf->Cell(70,20,"Endere�o:",0,1,'L');
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
            //3. Dados do Fato da Manifesta��o
            //***********************************************************************************************
            $pdf->Ln(20);
            $pdf->SetFont('arial','B',14);
            $pdf->Cell(70,20,"3. Fato da Manifesta��o:",0,0,'L');
            $pdf->Ln(20);

            //Munic�pio/UF
            $pdf->SetFont('arial','B',12);
            $pdf->Cell(115,20,"Munic�pio/UF:",0,0,'L');
            $pdf->setFont('arial','',12);
            $pdf->Cell(70,20,$id_municipio_fato . " - " . $desc_municipio_fato . " / " . $uf_fato,0,1,'L');

            //Descri��o
            $pdf->SetFont('arial','B',12);
            $pdf->Cell(115,20,"Descri��o:",0,1,'L');
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
}

/*
 $servidorSoap = new SoapServer("sei.wsdl",array('encoding'=>'ISO-8859-1'));
 $servidorSoap->setClass("SeiWS");

 //S� processa se acessado via POST
 if ($_SERVER['REQUEST_METHOD']=='POST') {
 $servidorSoap->handle();
 }
 */

/*$servidorSoap = new BeSimple\SoapServer\SoapServer( "cgu.wsdl", array ('encoding'=>'ISO-8859-1',
    'soap_version' => SOAP_1_1,
    'attachment_type'=>BeSimple\SoapCommon\Helper::ATTACHMENTS_TYPE_MTOM));
$servidorSoap->setClass ( "CguWS" );

//S� processa se acessado via POST
if ($_SERVER['REQUEST_METHOD']=='POST') {
    $servidorSoap->handle($HTTP_RAW_POST_DATA);
}*/
?>