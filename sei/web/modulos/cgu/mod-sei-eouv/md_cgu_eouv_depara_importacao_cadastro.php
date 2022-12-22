<?
/**
* CONTROLADORIA-GERAL DA UNI�O
* 16/12/2022 - criado por Daniel Coelho
*/

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

  $objDeParaDTO = new MdCguEouvDeparaImportacaoDTO();

  $arrComandos = array();

  switch ($_GET['acao']) {
    case 'md_cgu_eouv_depara_importacao_alterar':
      $strTitulo = 'Alterar Tipo de Processo Associado ao Tipo de Manifesta��o';

      // Lista as op��es de tipos de processo dispon�veis
      $objSeiRN = new SeiRN();
      $arrTipoProcAPI = $objSeiRN->listarTiposProcedimento();

      $arrComandos[] = '<button type="submit" accesskey="S" name="sbmAlterarMdCguEouvDeparaImportacao" value="Salvar" class="infraButton"><span class="infraTeclaAtalho">S</span>alvar</button>';
      $strLinkCancelar = SessaoSEI::getInstance()->assinarLink('controlador.php?'.
        'acao='.PaginaSEI::getInstance()->getAcaoRetorno().'&'.
        'acao_origem='.$_GET['acao']
      );
      $arrComandos[] = '<button type="button" accesskey="C" name="btnCancelar" value="Cancelar" onclick="location.href=\''.$strLinkCancelar.'\';" class="infraButton"><span class="infraTeclaAtalho">C</span>ancelar</button>';

      if (isset($_GET['id_md_cgu_eouv_tipo_manifestacao'])) {
        // Busca o tipo de manifestacao
        $objDeParaDTO->setNumIdTipoManifestacaoEouv($_GET['id_md_cgu_eouv_tipo_manifestacao']);
        $objDeParaDTO->retTodos();
        $objDeParaDTO->retStrTipoProcedimento();
        $objMdCguEouvDeparaImportacaoRN = new MdCguEouvDeparaImportacaoRN();
        $objDeParaDTO = $objMdCguEouvDeparaImportacaoRN->consultar($objDeParaDTO);
        if ($objDeParaDTO == null) {
          throw new InfraException("Registro n�o encontrado.");
        }
      } else if (isset($_POST['sbmAlterarMdCguEouvDeparaImportacao'])) {
        // Verifica par�metros necess�rios
        if (!isset($_POST['hdnIdEouv']) || !isset($_POST['selTipoProc'])) {
          throw new InfraException('Faltando par�metros para concluir a��o');
        }

        // Localiza o registro no banco
        $objDeParaDTO->setNumIdTipoManifestacaoEouv($_POST['hdnIdEouv']);
        $objDeParaDTO->retTodos();
        $objMdCguEouvDeparaImportacaoRN = new MdCguEouvDeparaImportacaoRN();
        $objDeParaDTO = $objMdCguEouvDeparaImportacaoRN->consultar($objDeParaDTO);
        if ($objDeParaDTO == null) {
          throw new InfraException("Registro n�o encontrado.");
        }

        // Atualiza o tipo de processo
        $objDeParaDTO->setNumIdTipoProcedimento($_POST['selTipoProc']);
        $objMdCguEouvDeparaImportacaoRN->alterar($objDeParaDTO);

        // Registra mensagem e redireciona
        PaginaSEI::getInstance()->setStrMensagem('Associa��o com Tipo de Manifesta��o "'.$objDeParaDTO->getStrDeTipoManifestacaoEouv().'" alterada com sucesso.');
        $strLinkRedirect = SessaoSEI::getInstance()->assinarLink('controlador.php?'.
          'acao='.PaginaSEI::getInstance()->getAcaoRetorno().'&'.
          'acao_origem='.$_GET['acao'].
          '#ID-'.$objDeParaDTO->getNumIdTipoManifestacaoEouv()
        );
        header('Location: '.$strLinkRedirect);
        die;
      } else {
        throw new InfraException("Par�metros inv�lidos");
      }

      break;

    default:
      throw new InfraException("A��o '".$_GET['acao']."' n�o reconhecida.");
  }

} catch(Exception $e) {
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

#divGeral {height:30em;}
#lblTipoEouv {position:absolute;left:0%;top:0%;width:70%;}
#txtTipoEouv {position:absolute;left:0%;top:6%;width:70%;}

#lblTipoProc {position:absolute;left:0%;top:16%;width:70%;}
#selTipoProc {position:absolute;left:0%;top:22%;width:70%;}

<?
PaginaSEI::getInstance()->fecharStyle();
PaginaSEI::getInstance()->montarJavaScript();
PaginaSEI::getInstance()->abrirJavaScript();

?>

function inicializar() {

}

<?
PaginaSEI::getInstance()->fecharJavaScript();
PaginaSEI::getInstance()->fecharHead();
PaginaSEI::getInstance()->abrirBody($strTitulo,'onload="inicializar();"');
?>
  <form id="frmMdCguEouvDeparaImportacaoCadastro" method="post" action="<?=SessaoSEI::getInstance()->assinarLink('controlador.php?acao='.$_GET['acao'].'&acao_origem='.$_GET['acao'])?>">
    <?
    PaginaSEI::getInstance()->montarBarraComandosSuperior($arrComandos);
    ?>

    <div id="divGeral" class="infraAreaDados">
      <input type="hidden" id="hdnIdEouv" name="hdnIdEouv" class="infraText" value="<?=$objDeParaDTO->getNumIdTipoManifestacaoEouv()?>">

      <label id="lblTipoEouv" for="txtTipoEouv" class="infraLabelObrigatorio">Tipo de Manifesta��o:</label>
      <input type="text" id="txtTipoEouv" name="txtTipoEouv" class="infraText" disabled="disabled" value="<?=$objDeParaDTO->getStrDeTipoManifestacaoEouv()?>">

      <label id="lblTipoProc" for="selTipoProc" accesskey="T" class="infraLabelObrigatorio"><span class="infraTeclaAtalho">T</span>ipo de Processo:</label>
      <select id="selTipoProc" name="selTipoProc" class="infraSelect">
        <option value=""></option>
        <?
        // Gera as op��es
        $numIdTipoProcAtual = $objDeParaDTO->getNumIdTipoProcedimento();
        foreach ($arrTipoProcAPI as $objTipo) {
          $numId = $objTipo->getIdTipoProcedimento();
          $strNome = $objTipo->getNome();
          $strSelected = ($numId == $numIdTipoProcAtual) ? ' selected="selected"' : '';
          echo '<option'.$strSelected.' value="'.$numId.'">'.$strNome.'</option>';
        }
        ?>
      </select>
    </div>

    <?
    //PaginaSEI::getInstance()->montarAreaDebug();
    ?>
  </form>
<?
PaginaSEI::getInstance()->fecharBody();
PaginaSEI::getInstance()->fecharHtml();
?>