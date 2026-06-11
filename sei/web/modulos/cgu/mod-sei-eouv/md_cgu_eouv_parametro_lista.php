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

try {
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
                    $idTipoDocumentoDadosManifestacao = $arrObjMdCguEouvParametroDTO[$i];
                    break;

                case "ID_SERIE_ANEXO":
                    $idTipoDocumentoAnexo = $arrObjMdCguEouvParametroDTO[$i];
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

                case "IMPORTAR_DADOS_MANIFESTANTE":
                    $importarDadosManifestante = $arrObjMdCguEouvParametroDTO[$i];
                    break;
            }
        }
    }

    $arrComandos[] = '<button type="submit" accesskey="S" name="sbmCadastrarMdCguEouvParametro" value="Salvar" class="infraButton"><span class="infraTeclaAtalho">S</span>alvar</button>';

  switch($_GET['acao']){

    case 'md_cgu_eouv_parametro_alterar':
        $objMdCguEouvAlterarParametroRN = new MdCguEouvParametroRN();

        if($_POST['EOUV_DATA_INICIAL_IMPORTACAO_MANIFESTACOES'] != $dataInicialImportacaoManifestacoes->getStrDeValorParametro()){
            $dataInicialImportacaoManifestacoes->setStrDeValorParametro($_POST['EOUV_DATA_INICIAL_IMPORTACAO_MANIFESTACOES']);
            $objMdCguEouvAlterarParametroRN->alterarParametro($dataInicialImportacaoManifestacoes);
        }
        if($_POST['EOUV_ID_SERIE_DOCUMENTO_EXTERNO_DADOS_MANIFESTACAO'] != $idTipoDocumentoDadosManifestacao->getStrDeValorParametro()){
            $idTipoDocumentoDadosManifestacao->setStrDeValorParametro($_POST['EOUV_ID_SERIE_DOCUMENTO_EXTERNO_DADOS_MANIFESTACAO']);
            $objMdCguEouvAlterarParametroRN->alterarParametro($idTipoDocumentoDadosManifestacao);
        }
        if($_POST['ID_SERIE_ANEXO'] != $idTipoDocumentoAnexo->getStrDeValorParametro()){
            $idTipoDocumentoAnexo->setStrDeValorParametro($_POST['ID_SERIE_ANEXO']);
            $objMdCguEouvAlterarParametroRN->alterarParametro($idTipoDocumentoAnexo);
        }
        if($_POST['EOUV_USUARIO_ACESSO_WEBSERVICE'] != $usuarioWebService->getStrDeValorParametro()){
            $usuarioWebService->setStrDeValorParametro($_POST['EOUV_USUARIO_ACESSO_WEBSERVICE']);
            $objMdCguEouvAlterarParametroRN->alterarParametro($usuarioWebService);
        }
        if($_POST['EOUV_SENHA_ACESSO_WEBSERVICE'] != $senhaUsuarioWebService->getStrDeValorParametro()){
            $senhaUsuarioWebService->setStrDeValorParametro($_POST['EOUV_SENHA_ACESSO_WEBSERVICE']);
            $objMdCguEouvAlterarParametroRN->alterarParametro($senhaUsuarioWebService);
        }
        if($_POST['CLIENT_ID'] != $client_id->getStrDeValorParametro()){
            $client_id->setStrDeValorParametro($_POST['CLIENT_ID']);
            $objMdCguEouvAlterarParametroRN->alterarParametro($client_id);
        }
        if($_POST['CLIENT_SECRET'] != $client_secret->getStrDeValorParametro()){
            $client_secret->setStrDeValorParametro($_POST['CLIENT_SECRET']);
            $objMdCguEouvAlterarParametroRN->alterarParametro($client_secret);
        }
        if($_POST['EOUV_URL_WEBSERVICE_IMPORTACAO'] != $urlWebServiceEOuv->getStrDeValorParametro()){
            $urlWebServiceEOuv->setStrDeValorParametro($_POST['EOUV_URL_WEBSERVICE_IMPORTACAO']);
            $objMdCguEouvAlterarParametroRN->alterarParametro($urlWebServiceEOuv);
        }
        $ckImportarDadosManifestantes = ($_POST['IMPORTAR_DADOS_MANIFESTANTE'] == 'on'?'1':'0');
        if($ckImportarDadosManifestantes != $importarDadosManifestante->getStrDeValorParametro()){
            $importarDadosManifestante->setStrDeValorParametro($ckImportarDadosManifestantes);
            $objMdCguEouvAlterarParametroRN->alterarParametro($importarDadosManifestante);
        }

        PaginaSEI::getInstance()->setStrMensagem('Parâmetros alterados com sucesso.');
        header('Location: ' . SessaoSEI::getInstance()->assinarLink('controlador.php?acao=md_cgu_eouv_parametro_listar' ));
        die();
        break;
    case 'md_cgu_eouv_parametro_listar':
      break;

    default:
      throw new InfraException("Ação '".$_GET['acao']."' não reconhecida.");
  }
    $strItensSelSerieDados = SerieINT::montarSelectNomeExternos('null','&nbsp;',$idTipoDocumentoDadosManifestacao->getStrDeValorParametro());
    $strItensSelSerieAnexo = SerieINT::montarSelectNomeExternos('null','&nbsp;',$idTipoDocumentoAnexo->getStrDeValorParametro());

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
.alerta {
    color: #ff4545;
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
            document.getElementById('EOUV_DATA_INICIAL_IMPORTACAO_MANIFESTACOES').focus();
            return false;
        }
        if (infraTrim(document.getElementById('EOUV_ID_SERIE_DOCUMENTO_EXTERNO_DADOS_MANIFESTACAO').value)=='null') {
            alert('Informe o Tipo de Documento para os Dados da Manifestação.');
            document.getElementById('EOUV_ID_SERIE_DOCUMENTO_EXTERNO_DADOS_MANIFESTACAO').focus();
            return false;
        }
        if (infraTrim(document.getElementById('ID_SERIE_ANEXO').value)=='null') {
            alert('Informe o Tipo de Documento para os Anexos da Manifestação.');
            document.getElementById('ID_SERIE_ANEXO').focus();
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
            alert('Informe a URL do FalaBR.');
            document.getElementById('EOUV_URL_WEBSERVICE_IMPORTACAO').focus();
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
        UR<span class="infraTeclaAtalho">L</span> do FalaBR:</label>
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
            <?=PaginaSEI::getInstance()->setCheckbox($importarDadosManifestante->getStrDeValorParametro(), '1', '0')?>   />
        <label id="lblIMPORTAR_DADOS_MANIFESTANTE" for="IMPORTAR_DADOS_MANIFESTANTE" accesskey="I" class="infraLabelCheckbox"><span class="infraTeclaAtalho">I</span>mportar Dados do Manifestante </label>
    </div>

    <!-- EOUV_ID_SERIE_DOCUMENTO_EXTERNO_DADOS_MANIFESTACAO -->
    <div class="infraAreaDados">
        <label id="lblEOUV_ID_SERIE_DOCUMENTO_EXTERNO_DADOS_MANIFESTACAO" for="EOUV_ID_SERIE_DOCUMENTO_EXTERNO_DADOS_MANIFESTACAO" accesskey="T" class="infraLabelObrigatorio"><span class="infraTeclaAtalho">T</span>ipo de documento do relatório da manifestação:</label>
        <select id="EOUV_ID_SERIE_DOCUMENTO_EXTERNO_DADOS_MANIFESTACAO" name="EOUV_ID_SERIE_DOCUMENTO_EXTERNO_DADOS_MANIFESTACAO" class="infraSelect"
                tabindex="<?=PaginaSEI::getInstance()->getProxTabDados()?>" >
            <?=$strItensSelSerieDados?>
        </select>
    </div>

    <!-- ID_SERIE_ANEXO -->
    <div class="infraAreaDados">
        <label id="lblID_SERIE_ANEXO" for="ID_SERIE_ANEXO" accesskey="p" class="infraLabelObrigatorio">Ti<span class="infraTeclaAtalho">p</span>o de documento dos anexos da manifestação:</label>
        <select id="ID_SERIE_ANEXO" name="ID_SERIE_ANEXO" class="infraSelect"
                tabindex="<?=PaginaSEI::getInstance()->getProxTabDados()?>" >
            <?=$strItensSelSerieAnexo?>
        </select>
    </div>

    <? PaginaSEI::getInstance()->montarBarraComandosInferior($arrComandos); ?>
</form>
<?
PaginaSEI::getInstance()->fecharBody();
PaginaSEI::getInstance()->fecharHtml();
?>