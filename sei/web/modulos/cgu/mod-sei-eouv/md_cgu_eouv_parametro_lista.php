<?
/**
* TRIBUNAL REGIONAL FEDERAL DA 4ª REGIÃO
*
* 17/12/2007 - criado por fbv
*
* Versão do Gerador de Código: 1.10.1
*
* Versão no CVS: $Id$
*/

function alterarParametro(MdCguEouvParametroDTO $objMdCguEouvParametroDTO)
{
    try {
        $objMdCguEouvAlterarParametroRN = new MdCguEouvParametroRN();
        $objMdCguEouvAlterarParametroRN->alterarParametro($objMdCguEouvParametroDTO);
        PaginaSEI::getInstance()->setBolExibirMensagens(true);
        PaginaSEI::getInstance()->setStrMensagem('Parâmetro "' . $objMdCguEouvParametroDTO->getStrNoParametro() . '" alterado com sucesso.');
        header('Location: ' . SessaoSEI::getInstance()->assinarLink('controlador.php?acao=md_cgu_eouv_parametro_listar' ));
    } catch (Exception $e) {
        PaginaSEI::getInstance()->processarExcecao($e);
    }
}

try {
  require_once dirname(__FILE__).'/../../../SEI.php';

  session_start();

  //////////////////////////////////////////////////////////////////////////////
  //InfraDebug::getInstance()->setBolLigado(false);
  //InfraDebug::getInstance()->setBolDebugInfra(true);
  //InfraDebug::getInstance()->limpar();
  //////////////////////////////////////////////////////////////////////////////

  SessaoSEI::getInstance()->validarLink();
  SessaoSEI::getInstance()->validarPermissao($_GET['acao']);
    $strTitulo = 'Parâmetros do Módulo de Integração com o FalaBR';
    $arrComandos = array();

    $objMdCguEouvParametroDTO = new MdCguEouvParametroDTO();
    $objMdCguEouvParametroDTO->retTodos();

    PaginaSEI::getInstance()->prepararOrdenacao($objMdCguEouvParametroDTO, 'IdParametro', InfraDTO::$TIPO_ORDENACAO_ASC);

    $objMdCguEouvParametroRN = new MdCguEouvParametroRN();
    $arrObjMdCguEouvParametroDTO = $objMdCguEouvParametroRN->listarParametro($objMdCguEouvParametroDTO);
    $numRegistros = count($arrObjMdCguEouvParametroDTO);
    if ($numRegistros > 0) {
        for ($i = 0; $i < $numRegistros; $i++) {

            $strParametroNome = $arrObjMdCguEouvParametroDTO[$i]->getStrNoParametro();

            switch ($strParametroNome) {

                case "EOUV_DATA_INICIAL_IMPORTACAO_MANIFESTACOES":
                    $dataInicialImportacaoManifestacoes = $arrObjMdCguEouvParametroDTO[$i];
                    break;

                case "EOUV_ID_SERIE_DOCUMENTO_EXTERNO_DADOS_MANIFESTACAO":
                    $idTipoDocumentoAnexoDadosManifestacao = $arrObjMdCguEouvParametroDTO[$i];
                    break;

                case "EOUV_USUARIO_ACESSO_WEBSERVICE":
                    $usuarioWebService = $arrObjMdCguEouvParametroDTO[$i];
                    break;

                case "EOUV_SENHA_ACESSO_WEBSERVICE":
                    $senhaUsuarioWebService = $arrObjMdCguEouvParametroDTO[$i];
                    break;

                case "CLIENT_ID":
                    $client_id = $arrObjMdCguEouvParametroDTO[$i];
                    break;

                case "CLIENT_SECRET":
                    $client_secret = $arrObjMdCguEouvParametroDTO[$i];
                    break;

                case "EOUV_URL_WEBSERVICE_IMPORTACAO_MANIFESTACAO":
                    $urlWebServiceEOuv = $arrObjMdCguEouvParametroDTO[$i];
                    break;
                    
                case "ESIC_ID_UNIDADE_PRINCIPAL":
                    $idUnidadeEsicPrincipal = $arrObjMdCguEouvParametroDTO[$i];
                    break;

                case "ESIC_ID_UNIDADE_RECURSO_PRIMEIRA_INSTANCIA":
                    $idUnidadeRecursoPrimeiraInstancia = $arrObjMdCguEouvParametroDTO[$i];
                    break;

                case "ESIC_ID_UNIDADE_RECURSO_SEGUNDA_INSTANCIA":
                    $idUnidadeRecursoSegundaInstancia = $arrObjMdCguEouvParametroDTO[$i];
                    break;

                case "ESIC_ID_UNIDADE_RECURSO_TERCEIRA_INSTANCIA":
                    $idUnidadeRecursoTerceiraInstancia = $arrObjMdCguEouvParametroDTO[$i];
                    break;

                case "ESIC_ID_UNIDADE_RECURSO_PEDIDO_REVISAO":
                    $idUnidadeRecursoPedidoRevisao = $arrObjMdCguEouvParametroDTO[$i];
                    break;

                case "IMPORTAR_DADOS_MANIFESTANTE":
                    $importarDadosManifestante = $arrObjMdCguEouvParametroDTO[$i];
                    break;

                case "ID_UNIDADE_OUVIDORIA":
                    $idUnidadeOuvidoria = $arrObjMdCguEouvParametroDTO[$i];
                    break;
            }
        }
    }

    $arrComandos[] = '<button type="submit" accesskey="S" name="sbmCadastrarMdCguEouvParametro" value="Salvar" class="infraButton"><span class="infraTeclaAtalho">S</span>alvar</button>';

  switch($_GET['acao']){

    case 'md_cgu_eouv_parametro_alterar':
        if($_POST['EOUV_DATA_INICIAL_IMPORTACAO_MANIFESTACOES'] != $dataInicialImportacaoManifestacoes->getStrDeValorParametro()){
            $dataInicialImportacaoManifestacoes->setStrDeValorParametro($_POST['EOUV_DATA_INICIAL_IMPORTACAO_MANIFESTACOES']);
            alterarParametro($dataInicialImportacaoManifestacoes);
        }
        if($_POST['EOUV_ID_SERIE_DOCUMENTO_EXTERNO_DADOS_MANIFESTACAO'] != $idTipoDocumentoAnexoDadosManifestacao->getStrDeValorParametro()){
            $idTipoDocumentoAnexoDadosManifestacao->setStrDeValorParametro($_POST['EOUV_ID_SERIE_DOCUMENTO_EXTERNO_DADOS_MANIFESTACAO']);
            alterarParametro($idTipoDocumentoAnexoDadosManifestacao);
        }
        if($_POST['EOUV_USUARIO_ACESSO_WEBSERVICE'] != $usuarioWebService->getStrDeValorParametro()){
            $usuarioWebService->setStrDeValorParametro($_POST['EOUV_USUARIO_ACESSO_WEBSERVICE']);
            alterarParametro($usuarioWebService);
        }
        if($_POST['EOUV_SENHA_ACESSO_WEBSERVICE'] != $senhaUsuarioWebService->getStrDeValorParametro()){
            $senhaUsuarioWebService->setStrDeValorParametro($_POST['EOUV_SENHA_ACESSO_WEBSERVICE']);
            alterarParametro($senhaUsuarioWebService);
        }
        if($_POST['CLIENT_ID'] != $client_id->getStrDeValorParametro()){
            $client_id->setStrDeValorParametro($_POST['CLIENT_ID']);
            alterarParametro($client_id);
        }
        if($_POST['CLIENT_SECRET'] != $client_secret->getStrDeValorParametro()){
            $client_secret->setStrDeValorParametro($_POST['CLIENT_SECRET']);
            alterarParametro($client_secret);
        }
        if($_POST['EOUV_URL_WEBSERVICE_IMPORTACAO'] != $urlWebServiceEOuv->getStrDeValorParametro()){
            $urlWebServiceEOuv->setStrDeValorParametro($_POST['EOUV_URL_WEBSERVICE_IMPORTACAO']);
            alterarParametro($urlWebServiceEOuv);
        }
        if($_POST['ID_UNIDADE_OUVIDORIA'] != $idUnidadeOuvidoria->getStrDeValorParametro()){
            $idUnidadeOuvidoria->setStrDeValorParametro($_POST['ID_UNIDADE_OUVIDORIA']);
            alterarParametro($idUnidadeOuvidoria);
        }
        $ckImportarDadosManifestantes = ($_POST['IMPORTAR_DADOS_MANIFESTANTE'] == 'on'?'S':'N');
        if($ckImportarDadosManifestantes != $importarDadosManifestante->getStrDeValorParametro()){
            $importarDadosManifestante->setStrDeValorParametro($ckImportarDadosManifestantes);
            alterarParametro($importarDadosManifestante);
        }
        if($_POST['ESIC_ID_UNIDADE_PRINCIPAL'] != $idUnidadeEsicPrincipal->getStrDeValorParametro()){
            $idUnidadeEsicPrincipal->setStrDeValorParametro($_POST['ESIC_ID_UNIDADE_PRINCIPAL']);
            alterarParametro($idUnidadeEsicPrincipal);
        }
        if($_POST['ESIC_ID_UNIDADE_RECURSO_PRIMEIRA_INSTANCIA'] != $idUnidadeRecursoPrimeiraInstancia->getStrDeValorParametro()){
            $idUnidadeRecursoPrimeiraInstancia->setStrDeValorParametro($_POST['ESIC_ID_UNIDADE_RECURSO_PRIMEIRA_INSTANCIA']);
            alterarParametro($idUnidadeRecursoPrimeiraInstancia);
        }
        if($_POST['ESIC_ID_UNIDADE_RECURSO_SEGUNDA_INSTANCIA'] != $idUnidadeRecursoSegundaInstancia->getStrDeValorParametro()){
            $idUnidadeRecursoSegundaInstancia->setStrDeValorParametro($_POST['ESIC_ID_UNIDADE_RECURSO_SEGUNDA_INSTANCIA']);
            alterarParametro($idUnidadeRecursoSegundaInstancia);
        }
        if($_POST['ESIC_ID_UNIDADE_RECURSO_TERCEIRA_INSTANCIA'] != $idUnidadeRecursoTerceiraInstancia->getStrDeValorParametro()){
            $idUnidadeRecursoTerceiraInstancia->setStrDeValorParametro($_POST['ESIC_ID_UNIDADE_RECURSO_TERCEIRA_INSTANCIA']);
            alterarParametro($idUnidadeRecursoTerceiraInstancia);
        }
        if($_POST['ESIC_ID_UNIDADE_RECURSO_PEDIDO_REVISAO'] != $idUnidadeRecursoPedidoRevisao->getStrDeValorParametro()){
            $idUnidadeRecursoPedidoRevisao->setStrDeValorParametro($_POST['ESIC_ID_UNIDADE_RECURSO_PEDIDO_REVISAO']);
            alterarParametro($idUnidadeRecursoPedidoRevisao);
        }
        break;
    case 'md_cgu_eouv_parametro_listar':
      break;

    default:
      throw new InfraException("Ação '".$_GET['acao']."' não reconhecida.");
  }
    $strItensSelSerie = SerieINT::montarSelectNomeExternos('null','&nbsp;',$idTipoDocumentoAnexoDadosManifestacao->getStrDeValorParametro());
    $strItensSelUnidadeOuv = UnidadeINT::montarSelectSiglaDescricao('null','&nbsp;',$idUnidadeOuvidoria->getStrDeValorParametro());
    $strItensSelUnidadeEsic = UnidadeINT::montarSelectSiglaDescricao('null','&nbsp;',$idUnidadeEsicPrincipal->getStrDeValorParametro());
    $strItensSelUnidadePrimeira = UnidadeINT::montarSelectSiglaDescricao('null','&nbsp;',$idUnidadeRecursoPrimeiraInstancia->getStrDeValorParametro());
    $strItensSelUnidadeSegunda = UnidadeINT::montarSelectSiglaDescricao('null','&nbsp;',$idUnidadeRecursoSegundaInstancia->getStrDeValorParametro());
    $strItensSelUnidadeTerceira = UnidadeINT::montarSelectSiglaDescricao('null','&nbsp;',$idUnidadeRecursoTerceiraInstancia->getStrDeValorParametro());
    $strItensSelUnidadeRevisao = UnidadeINT::montarSelectSiglaDescricao('null','&nbsp;',$idUnidadeRecursoPedidoRevisao->getStrDeValorParametro());

}catch(Exception $e){
  PaginaSEI::getInstance()->processarExcecao($e);
} 

PaginaSEI::getInstance()->montarDocType();
PaginaSEI::getInstance()->abrirHtml();
PaginaSEI::getInstance()->abrirHead();
PaginaSEI::getInstance()->montarMeta();
PaginaSEI::getInstance()->montarTitle(PaginaSEI::getInstance()->getStrNomeSistema().' - '.$strTitulo);
PaginaSEI::getInstance()->montarStyle();
PaginaSEI::getInstance()->abrirStyle();
?>
.infraAreaDados {
    margin-bottom: 1em;
}
<?
PaginaSEI::getInstance()->fecharStyle();
PaginaSEI::getInstance()->montarJavaScript();
PaginaSEI::getInstance()->abrirJavaScript();
?>
    //<script>
    function inicializar(){

    }
    function OnSubmitForm() {
    return ValidarCadastroParametro();
    }

    function ValidarCadastroParametro() {
        if (infraTrim(document.getElementById('EOUV_DATA_INICIAL_IMPORTACAO_MANIFESTACOES').value)=='') {
            alert('Informe a Data Inicial de Importação.');
            document.getElementById('EOUV_ID_SERIE_DOCUMENTO_EXTERNO_DADOS_MANIFESTACAO').focus();
            return false;
        }
        if (infraTrim(document.getElementById('EOUV_ID_SERIE_DOCUMENTO_EXTERNO_DADOS_MANIFESTACAO').value)=='null') {
            alert('Informe o Tipo de Documento.');
            document.getElementById('EOUV_ID_SERIE_DOCUMENTO_EXTERNO_DADOS_MANIFESTACAO').focus();
            return false;
        }
        if (infraTrim(document.getElementById('EOUV_USUARIO_ACESSO_WEBSERVICE').value)=='') {
            alert('Informe o Usuário.');
            document.getElementById('EOUV_USUARIO_ACESSO_WEBSERVICE').focus();
            return false;
        }
        if (infraTrim(document.getElementById('EOUV_SENHA_ACESSO_WEBSERVICE').value)=='') {
            alert('Informe a senha.');
            document.getElementById('EOUV_SENHA_ACESSO_WEBSERVICE').focus();
            return false;
        }
        if (infraTrim(document.getElementById('CLIENT_ID').value)=='') {
            alert('Informe o CLIENT_ID.');
            document.getElementById('CLIENT_ID').focus();
            return false;
        }
        if (infraTrim(document.getElementById('CLIENT_SECRET').value)=='') {
            alert('Informe o CLIENT_SECRET.');
            document.getElementById('CLIENT_SECRET').focus();
            return false;
        }
        if (infraTrim(document.getElementById('EOUV_URL_WEBSERVICE_IMPORTACAO').value)=='') {
            alert('Informe a URL do Webservice.');
            document.getElementById('EOUV_URL_WEBSERVICE_IMPORTACAO').focus();
            return false;
        }
        if (infraTrim(document.getElementById('ID_UNIDADE_OUVIDORIA').value)=='null') {
            alert('Informe a Unidade de Ouvidoria');
            document.getElementById('ID_UNIDADE_OUVIDORIA').focus();
            return false;
        }
        if (infraTrim(document.getElementById('ESIC_ID_UNIDADE_PRINCIPAL').value)=='null') {
            alert('Informe a Unidade de Acesso a Informação.');
            document.getElementById('ESIC_ID_UNIDADE_PRINCIPAL').focus();
            return false;
        }
        if (infraTrim(document.getElementById('ESIC_ID_UNIDADE_RECURSO_PRIMEIRA_INSTANCIA').value)=='null') {
            alert('Informe a Unidade de Recurso em 1ª Instância.');
            document.getElementById('ESIC_ID_UNIDADE_RECURSO_PRIMEIRA_INSTANCIA').focus();
            return false;
        }
        if (infraTrim(document.getElementById('ESIC_ID_UNIDADE_RECURSO_SEGUNDA_INSTANCIA').value)=='null') {
            alert('Informe a Unidade de Recurso em 2ª Instância.');
            document.getElementById('ESIC_ID_UNIDADE_RECURSO_SEGUNDA_INSTANCIA').focus();
            return false;
        }
        if (infraTrim(document.getElementById('ESIC_ID_UNIDADE_RECURSO_TERCEIRA_INSTANCIA').value)=='null') {
            alert('Informe a Unidade de Recurso em 3ª Instância.');
            document.getElementById('ESIC_ID_UNIDADE_RECURSO_TERCEIRA_INSTANCIA').focus();
            return false;
        }
        if (infraTrim(document.getElementById('ESIC_ID_UNIDADE_RECURSO_PEDIDO_REVISAO').value)=='null') {
            alert('Informe a Unidade de Pedido de Revisão.');
            document.getElementById('ESIC_ID_UNIDADE_RECURSO_PEDIDO_REVISAO').focus();
            return false;
        }
        return true;
    }
    //</script>
<?
PaginaSEI::getInstance()->fecharJavaScript();
PaginaSEI::getInstance()->fecharHead();
PaginaSEI::getInstance()->abrirBody($strTitulo,'onload="inicializar();"');
?>
<form id="frmMdCguEouvParametroLista" method="post" onsubmit="return OnSubmitForm();" action="<?=SessaoSEI::getInstance()->assinarLink('controlador.php?acao=md_cgu_eouv_parametro_alterar&acao_origem='.$_GET['acao'])?>">
  <? PaginaSEI::getInstance()->montarBarraComandosSuperior($arrComandos); ?>
    <!-- EOUV_URL_WEBSERVICE_IMPORTACAO -->
    <div class="infraAreaDados">
        <label id="lblEOUV_URL_WEBSERVICE_IMPORTACAO" for="EOUV_URL_WEBSERVICE_IMPORTACAO" accesskey="W" class="infraLabelObrigatorio">
            URL do <span class="infraTeclaAtalho">W</span>ebService:</label>
        <input type="text" id="EOUV_URL_WEBSERVICE_IMPORTACAO" name="EOUV_URL_WEBSERVICE_IMPORTACAO" class="infraText"
               value="<?=PaginaSEI::tratarHTML($urlWebServiceEOuv->getStrDeValorParametro());?>" onkeypress="return infraMascaraTexto(this,event,100);"
               maxlength="100" size="50" tabindex="<?=PaginaSEI::getInstance()->getProxTabDados()?>" />
    </div>
    <!-- EOUV_USUARIO_ACESSO_WEBSERVICE -->
    <div class="infraAreaDados">
        <label id="lblEOUV_USUARIO_ACESSO_WEBSERVICE for="EOUV_USUARIO_ACESSO_WEBSERVICE" accesskey="U" class="infraLabelObrigatorio">
        <span class="infraTeclaAtalho">U</span>suário:</label>
        <input type="text" id="EOUV_USUARIO_ACESSO_WEBSERVICE" name="EOUV_USUARIO_ACESSO_WEBSERVICE" class="infraText"
               value="<?=PaginaSEI::tratarHTML($usuarioWebService->getStrDeValorParametro());?>" onkeypress="return infraMascaraTexto(this,event,50);"
               maxlength="50" tabindex="<?=PaginaSEI::getInstance()->getProxTabDados()?>" />
        <!-- EOUV_SENHA_ACESSO_WEBSERVICE -->
        <label id="lblEOUV_SENHA_ACESSO_WEBSERVICE" for="EOUV_SENHA_ACESSO_WEBSERVICE" accesskey="S" class="infraLabelObrigatorio">
            <span class="infraTeclaAtalho">S</span>enha:</label>
        <input type="password" id="EOUV_SENHA_ACESSO_WEBSERVICE" name="EOUV_SENHA_ACESSO_WEBSERVICE" onkeypress="return infraMascaraTexto(this,event,50);" class="infraText"
                  tabindex="<?=PaginaSEI::getInstance()->getProxTabDados()?>" value="<?=PaginaSEI::tratarHTML($senhaUsuarioWebService->getStrDeValorParametro());?>" />
    </div>
    <!-- CLIENT_ID -->
    <div class="infraAreaDados">
        <label id="lblCLIENT_ID" for="CLIENT_ID" accesskey="C" class="infraLabelObrigatorio">
        <span class="infraTeclaAtalho">C</span>lientID:</label>
        <input type="text" id="CLIENT_ID" name="CLIENT_ID" class="infraText"
               value="<?=PaginaSEI::tratarHTML($client_id->getStrDeValorParametro());?>" onkeypress="return infraMascaraTexto(this,event,50);"
               maxlength="50" tabindex="<?=PaginaSEI::getInstance()->getProxTabDados()?>" />
        <!-- CLIENT_SECRET -->
        <label id="lblCLIENT_SECRET" for="CLIENT_SECRET" accesskey="n" class="infraLabelObrigatorio">
            Clie<span class="infraTeclaAtalho">n</span>tSecret:</label>
        <input type="password" id="CLIENT_SECRET" name="CLIENT_SECRET" onkeypress="return infraMascaraTexto(this,event,50);" class="infraText"
               tabindex="<?=PaginaSEI::getInstance()->getProxTabDados()?>" value="<?=PaginaSEI::tratarHTML($client_secret->getStrDeValorParametro());?>" />
    </div>

    <!-- EOUV_DATA_INICIAL_IMPORTACAO_MANIFESTACOES -->
    <div class="infraAreaDados">
        <label id="lblEOUV_DATA_INICIAL_IMPORTACAO_MANIFESTACOES" for="EOUV_DATA_INICIAL_IMPORTACAO_MANIFESTACOES" accesskey="D" class="infraLabelObrigatorio">
            <span class="infraTeclaAtalho">D</span>ata Inicial de Importação:</label>
        <input type="text" id="EOUV_DATA_INICIAL_IMPORTACAO_MANIFESTACOES" name="EOUV_DATA_INICIAL_IMPORTACAO_MANIFESTACOES" onkeypress="return infraMascaraData(this, event)"
               class="infraText" value="<?=PaginaSEI::tratarHTML($dataInicialImportacaoManifestacoes->getStrDeValorParametro());?>"
               tabindex="<?=PaginaSEI::getInstance()->getProxTabDados()?>" />
        <img src="<?=PaginaSEI::getInstance()->getIconeCalendario()?>" id="imgCalDtaGeracaoInformar" title="Selecionar Data" alt="Selecionar Data"  class="infraImg"
             onclick="infraCalendario('EOUV_DATA_INICIAL_IMPORTACAO_MANIFESTACOES',this);" tabindex="<?=PaginaSEI::getInstance()->getProxTabDados()?>" />
    </div>
    <!-- IMPORTAR_DADOS_MANIFESTANTE -->
    <div class="infraDivCheckbox infraAreaDados" style="height:3em;">
        <input type="checkbox" id="IMPORTAR_DADOS_MANIFESTANTE" name="IMPORTAR_DADOS_MANIFESTANTE" class="infraCheckbox"
            <?=PaginaSEI::getInstance()->setCheckbox($importarDadosManifestante->getStrDeValorParametro())?>   />
        <label id="lblIMPORTAR_DADOS_MANIFESTANTE" for="IMPORTAR_DADOS_MANIFESTANTE" accesskey="I" class="infraLabelCheckbox"><span class="infraTeclaAtalho">I</span>mportar Dados do Manifestante </label>
    </div>

    <!-- EOUV_ID_SERIE_DOCUMENTO_EXTERNO_DADOS_MANIFESTACAO -->
    <div class="infraAreaDados">
        <label id="lblEOUV_ID_SERIE_DOCUMENTO_EXTERNO_DADOS_MANIFESTACAO" for="EOUV_ID_SERIE_DOCUMENTO_EXTERNO_DADOS_MANIFESTACAO" accesskey="T" class="infraLabelObrigatorio"><span class="infraTeclaAtalho">T</span>ipo de documento usado na importação:</label>
        <select id="EOUV_ID_SERIE_DOCUMENTO_EXTERNO_DADOS_MANIFESTACAO" name="EOUV_ID_SERIE_DOCUMENTO_EXTERNO_DADOS_MANIFESTACAO" class="infraSelect"
                tabindex="<?=PaginaSEI::getInstance()->getProxTabDados()?>" >
            <?=$strItensSelSerie?>
        </select>
    </div>

    <div class="infraAreaDados">
        <label id="lblID_UNIDADE_OUVIDORIA" for="ID_UNIDADE_OUVIDORIA" accesskey="O" class="infraLabelObrigatorio">Unidade de <span class="infraTeclaAtalho">O</span>uvidoria:</label>
        <select id="ID_UNIDADE_OUVIDORIA" name="ID_UNIDADE_OUVIDORIA" class="infraSelect" tabindex="<?=PaginaSEI::getInstance()->getProxTabDados()?>" >
            <?=$strItensSelUnidadeOuv?>
        </select>
    </div> 
    <div class="infraAreaDados">
        <label id="lblESIC_ID_UNIDADE_PRINCIPAL" for="ESIC_ID_UNIDADE_PRINCIPAL" accesskey="A" class="infraLabelObrigatorio">Unidade de <span class="infraTeclaAtalho">A</span>cesso à Informação:</label>
        <select id="ESIC_ID_UNIDADE_PRINCIPAL" name="ESIC_ID_UNIDADE_PRINCIPAL" class="infraSelect" tabindex="<?=PaginaSEI::getInstance()->getProxTabDados()?>" >
            <?=$strItensSelUnidadeEsic?>
        </select>
    </div>
    <div class="infraAreaDados">
        <label id="lblESIC_ID_UNIDADE_RECURSO_PRIMEIRA_INSTANCIA" for="ESIC_ID_UNIDADE_RECURSO_PRIMEIRA_INSTANCIA" accesskey="1" class="infraLabelObrigatorio">Unidade de Recurso em <span class="infraTeclaAtalho">1</span>ª Instância:</label>
        <select id="ESIC_ID_UNIDADE_RECURSO_PRIMEIRA_INSTANCIA" name="ESIC_ID_UNIDADE_RECURSO_PRIMEIRA_INSTANCIA"  class="infraSelect" tabindex="<?=PaginaSEI::getInstance()->getProxTabDados()?>" >
            <?=$strItensSelUnidadePrimeira?>
        </select>
    </div>
    <div class="infraAreaDados">
        <label id="lblESIC_ID_UNIDADE_RECURSO_SEGUNDA_INSTANCIA" for="ESIC_ID_UNIDADE_RECURSO_SEGUNDA_INSTANCIA" accesskey="2" class="infraLabelObrigatorio">Unidade de Recurso em <span class="infraTeclaAtalho">2</span>ª Instância:</label>
        <select id="ESIC_ID_UNIDADE_RECURSO_SEGUNDA_INSTANCIA" name="ESIC_ID_UNIDADE_RECURSO_SEGUNDA_INSTANCIA"  class="infraSelect" tabindex="<?=PaginaSEI::getInstance()->getProxTabDados()?>" >
            <?=$strItensSelUnidadeSegunda?>
        </select>
    </div>
    <div class="infraAreaDados">
        <label id="lblESIC_ID_UNIDADE_RECURSO_TERCEIRA_INSTANCIA" for="ESIC_ID_UNIDADE_RECURSO_TERCEIRA_INSTANCIA" accesskey="3" class="infraLabelObrigatorio">Unidade de Recurso em <span class="infraTeclaAtalho">3</span>ª Instância:</label>
        <select id="ESIC_ID_UNIDADE_RECURSO_TERCEIRA_INSTANCIA" name="ESIC_ID_UNIDADE_RECURSO_TERCEIRA_INSTANCIA" class="infraSelect" tabindex="<?=PaginaSEI::getInstance()->getProxTabDados()?>" >
            <?=$strItensSelUnidadeTerceira?>
        </select>
    </div>
    <div class="infraAreaDados">
        <label id="lblESIC_ID_UNIDADE_RECURSO_PEDIDO_REVISAO" for="ESIC_ID_UNIDADE_RECURSO_PEDIDO_REVISAO" accesskey="R" class="infraLabelObrigatorio">Unidade de Pedido de <span class="infraTeclaAtalho">R</span>evisão:</label>
        <select id="ESIC_ID_UNIDADE_RECURSO_PEDIDO_REVISAO" name="ESIC_ID_UNIDADE_RECURSO_PEDIDO_REVISAO" class="infraSelect" tabindex="<?=PaginaSEI::getInstance()->getProxTabDados()?>" >
            <?=$strItensSelUnidadeRevisao?>
        </select>
    </div>

    <? PaginaSEI::getInstance()->montarBarraComandosInferior($arrComandos); ?>
</form>
<?
PaginaSEI::getInstance()->fecharBody();
PaginaSEI::getInstance()->fecharHtml();
?>