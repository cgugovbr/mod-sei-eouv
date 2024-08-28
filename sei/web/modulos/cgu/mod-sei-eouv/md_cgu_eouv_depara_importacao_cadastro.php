<?
/**
* CONTROLADORIA-GERAL DA UNIÃO
* 16/12/2022 - criado por Daniel Coelho
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

  $objDeParaDTO = new MdCguEouvDeparaImportacaoDTO();

  $arrComandos = array();

  switch ($_GET['acao']) {
    case 'md_cgu_eouv_depara_importacao_alterar':
      $strTitulo = 'Alterar Tipo de Processo Associado ao Tipo de Manifestação';

      // Lista as opções de tipos de processo disponíveis
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
          throw new InfraException("Registro não encontrado.");
        }
      } else if (isset($_POST['sbmAlterarMdCguEouvDeparaImportacao'])) {
        // Verifica parâmetros necessários
        if (!isset($_POST['hdnIdEouv']) || !isset($_POST['selTipoProc'])) {
          throw new InfraException('Faltando parâmetros para concluir ação');
        }

        // Localiza o registro no banco
        $objDeParaDTO->setNumIdTipoManifestacaoEouv($_POST['hdnIdEouv']);
        $objDeParaDTO->retTodos();
        $objMdCguEouvDeparaImportacaoRN = new MdCguEouvDeparaImportacaoRN();
        $objDeParaDTO = $objMdCguEouvDeparaImportacaoRN->consultar($objDeParaDTO);
        if ($objDeParaDTO == null) {
          throw new InfraException("Registro não encontrado.");
        }

        $objDeParaDTO->setNumIdTipoProcedimento($_POST['selTipoProc']);

        // Verifica se o nível sugerido para o tipo de processo selecionado é público
        // e se for lança um erro
        $objTipoProcedimentoDTO = new TipoProcedimentoDTO();
        $objTipoProcedimentoDTO->setNumIdTipoProcedimento($_POST['selTipoProc']);
        $objTipoProcedimentoDTO->retStrStaNivelAcessoSugestao();
        $objTipoProcedimentoDTO->retNumIdHipoteseLegalSugestao();
        $objTipoProcedimentoRN = new TipoProcedimentoRN();
        $objTipoProcedimentoDTO = $objTipoProcedimentoRN->consultarRN0267($objTipoProcedimentoDTO);
        if (!$objTipoProcedimentoDTO) {
          PaginaSEI::getInstance()->setStrMensagem('Tipo de Processo não encontrado',
            PaginaSEI::$TIPO_MSG_ERRO);
        } else if ($objTipoProcedimentoDTO->getStrStaNivelAcessoSugestao() == ProtocoloRN::$NA_PUBLICO) {
          PaginaSEI::getInstance()->setStrMensagem('O nível de acesso sugerido '.
            'do tipo de processo não pode ser público. Ajuste os parâmetros '.
            'do tipo ou então escolha um outro.', PaginaSEI::$TIPO_MSG_ERRO);
        } else if (is_null($objTipoProcedimentoDTO->getNumIdHipoteseLegalSugestao())) {
          PaginaSEI::getInstance()->setStrMensagem('O tipo de processo não tem '.
            'uma Hipótese Legal de restrição de acesso sugerida. Ajuste os '.
            'parâmetros do tipo ou então escolha um outro.', PaginaSEI::$TIPO_MSG_ERRO);
        } else {
          // Atualiza o tipo de processo
          $objMdCguEouvDeparaImportacaoRN->alterar($objDeParaDTO);

          // Registra mensagem e redireciona
          PaginaSEI::getInstance()->setStrMensagem('Associação com Tipo de Manifestação "'.$objDeParaDTO->getStrDeTipoManifestacaoEouv().'" alterada com sucesso.');
          $strLinkRedirect = SessaoSEI::getInstance()->assinarLink('controlador.php?'.
            'acao='.PaginaSEI::getInstance()->getAcaoRetorno().'&'.
            'acao_origem='.$_GET['acao'].
            '#ID-'.$objDeParaDTO->getNumIdTipoManifestacaoEouv()
          );
          header('Location: '.$strLinkRedirect);
          die;
        }
      } else {
        throw new InfraException("Parâmetros inválidos");
      }

      break;

    default:
      throw new InfraException("Ação '".$_GET['acao']."' não reconhecida.");
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

      <label id="lblTipoEouv" for="txtTipoEouv" class="infraLabelObrigatorio">Tipo de Manifestação:</label>
      <input type="text" id="txtTipoEouv" name="txtTipoEouv" class="infraText" disabled="disabled" value="<?=$objDeParaDTO->getStrDeTipoManifestacaoEouv()?>">

      <label id="lblTipoProc" for="selTipoProc" accesskey="T" class="infraLabelObrigatorio"><span class="infraTeclaAtalho">T</span>ipo de Processo:</label>
      <select id="selTipoProc" name="selTipoProc" class="infraSelect">
        <option value=""></option>
        <?
        // Gera as opções
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