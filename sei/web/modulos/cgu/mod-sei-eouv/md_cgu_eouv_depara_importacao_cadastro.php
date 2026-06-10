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
      $strTitulo = 'Alterar Configuração do Tipo de Manifestação ou Recurso';

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
        $objDeParaDTO->setBolExclusaoLogica(false);
        $objDeParaDTO->retTodos(true);
        $objMdCguEouvDeparaImportacaoRN = new MdCguEouvDeparaImportacaoRN();
        $objDeParaDTO = $objMdCguEouvDeparaImportacaoRN->consultar($objDeParaDTO);
        if ($objDeParaDTO == null) {
          throw new InfraException("Registro não encontrado.");
        }
      } else if (isset($_POST['sbmAlterarMdCguEouvDeparaImportacao'])) {
        // Verifica parâmetros necessários
        if (!isset($_POST['hdnIdEouv']) || !isset($_POST['selTipoProc']) || !isset($_POST['selHipoteseLegal']) || !isset($_POST['selUnidadeDestino'])) {
          throw new InfraException('Faltando parâmetros para concluir ação');
        }

        // Localiza o registro no banco
        $objDeParaDTO->setNumIdTipoManifestacaoEouv($_POST['hdnIdEouv']);
        $objDeParaDTO->setBolExclusaoLogica(false);
        $objDeParaDTO->retTodos();
        $objMdCguEouvDeparaImportacaoRN = new MdCguEouvDeparaImportacaoRN();
        $objDeParaDTO = $objMdCguEouvDeparaImportacaoRN->consultar($objDeParaDTO);
        if ($objDeParaDTO == null) {
          throw new InfraException("Registro não encontrado.");
        }

        // Atualiza o tipo de processo
        $objTipoProcedimentoDTO = new TipoProcedimentoDTO();
        $objTipoProcedimentoDTO->setNumIdTipoProcedimento($_POST['selTipoProc']);
        $objTipoProcedimentoDTO->retStrStaNivelAcessoSugestao();
        $objTipoProcedimentoDTO->retNumIdHipoteseLegalSugestao();
        $objTipoProcedimentoRN = new TipoProcedimentoRN();
        $objTipoProcedimentoDTO = $objTipoProcedimentoRN->consultarRN0267($objTipoProcedimentoDTO);
        if (!$objTipoProcedimentoDTO) {
          PaginaSEI::getInstance()->setStrMensagem('Tipo de Processo não encontrado',
            PaginaSEI::$TIPO_MSG_ERRO);
        } else if ($objTipoProcedimentoDTO->getStrStaNivelAcessoSugestao() == ProtocoloRN::$NA_RESTRITO && is_null($objTipoProcedimentoDTO->getNumIdHipoteseLegalSugestao())) {
          PaginaSEI::getInstance()->setStrMensagem('O tipo de processo selecionado '.
            'possui nível de acesso sugerido restrito, porém não possui sugestão '.
            'de hipótese legal, dessa forma não pode ser utilizado pela integração. '.
            'Configure uma hipótese legal sugerida no menu de Administração do SEI '.
            'ou então escolha um outro tipo de processo.', PaginaSEI::$TIPO_MSG_ERRO);
        } else {
          $objDeParaDTO->setNumIdTipoProcedimento($_POST['selTipoProc']);

          // Atualiza a hipótese legal
          $objDeParaDTO->setNumIdHipoteseLegal($_POST['selHipoteseLegal']);

          // Atualiza unidade de destino
          $objDeParaDTO->setNumIdUnidadeDestino($_POST['selUnidadeDestino']);

          // Salvar dados, registra mensagem e redireciona
          $objMdCguEouvDeparaImportacaoRN->alterar($objDeParaDTO);

          PaginaSEI::getInstance()->setStrMensagem('Configurações do tipo "'.$objDeParaDTO->getStrDeTipoManifestacaoEouv().'" alteradas com sucesso.');
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

.infraAreaDados input {
  display: block;
  width: 50%;
  margin-bottom: 1rem;
}

.infraAreaDados select {
  margin-bottom: 1rem;
}

.infraAreaDados p {
  margin: 0;
}

<?
PaginaSEI::getInstance()->fecharStyle();
PaginaSEI::getInstance()->montarJavaScript();
PaginaSEI::getInstance()->abrirJavaScript();

?>

// <script>

function inicializar() {
}

function validarFormulario() {
  if (infraTrim(document.getElementById('selTipoProc').value)=='null') {
    alert('Informe o Tipo de Processo.');
    document.getElementById('selTipoProc').focus();
    return false;
  }

  if (infraTrim(document.getElementById('selHipoteseLegal').value)=='null') {
    alert('Informe a Hipótese Legal.');
    document.getElementById('selHipoteseLegal').focus();
    return false;
  }

  if (infraTrim(document.getElementById('selUnidadeDestino').value)=='null') {
    alert('Informe a Unidade de Destino.');
    document.getElementById('selUnidadeDestino').focus();
    return false;
  }

  return true;
}
// </script>

<?
PaginaSEI::getInstance()->fecharJavaScript();
PaginaSEI::getInstance()->fecharHead();
PaginaSEI::getInstance()->abrirBody($strTitulo,'onload="inicializar();"');
?>
  <form method="post" onsubmit="return validarFormulario();" action="<?=SessaoSEI::getInstance()->assinarLink('controlador.php?acao='.$_GET['acao'].'&acao_origem='.$_GET['acao'])?>">
    <?
    PaginaSEI::getInstance()->montarBarraComandosSuperior($arrComandos);
    ?>

    <div class="infraAreaDados">
      <input type="hidden" id="hdnIdEouv" name="hdnIdEouv" class="infraText" value="<?=$objDeParaDTO->getNumIdTipoManifestacaoEouv()?>">

      <label id="lblTipoEouv" for="txtTipoEouv" class="infraLabelObrigatorio">Tipo de Manifestação / Recurso:</label>
      <input type="text" id="txtTipoEouv" name="txtTipoEouv" maxlength="50" class="infraText" disabled="disabled" value="<?=$objDeParaDTO->getStrDeTipoManifestacaoEouv()?>">

      <label id="lblTipoProc" for="selTipoProc" accesskey="T" class="infraLabelObrigatorio"><span class="infraTeclaAtalho">T</span>ipo de Processo:</label>
      <p>Os processos criados pela integração para este tipo de manifestação ou
        recurso serão do tipo selecionado abaixo. Esses processos seguirão o
        nível de acesso e hipótese legal sugeridos para o tipo.</p>
      <select id="selTipoProc" name="selTipoProc" class="infraSelect">
        <option value="null"></option>
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

      <label for="selHipoteseLegal" accesskey="H" class="infraLabelObrigatorio">
        <span class="infraTeclaAtalho">H</span>ipótese Legal aplicada aos
        documentos importados:
      </label>
      <p>Os documentos importados pela integração sempre possuem nível de acesso
        <strong>restrito</strong>. Selecione abaixo a Hipótese Legal
        que deve ser aplicada a esses documentos.</p>
      <select id="selHipoteseLegal" name="selHipoteseLegal" class="infraSelect"
        tabindex="<?= PaginaSEI::getInstance()->getProxTabDados() ?>" >
        <?= HipoteseLegalINT::montarSelectNomeBaseLegal('null', '&nbsp',
          $objDeParaDTO->getNumIdHipoteseLegal(), ProtocoloRN::$NA_RESTRITO); ?>
      </select>

      <label for="selUnidadeDestino" accesskey="U" class="infraLabelObrigatorio">
        <span class="infraTeclaAtalho">U</span>nidade de Destino:
      </label>
      <p>Os processos desse tipo de manifestação ou recurso serão enviados para
        a unidade selecionada abaixo.</p>
      <select id="selUnidadeDestino" name="selUnidadeDestino" class="infraSelect"
        tabindex="<?= PaginaSEI::getInstance()->getProxTabDados() ?>" >
        <?= UnidadeINT::montarSelectSiglaDescricao('null', '&nbsp;',
          $objDeParaDTO->getNumIdUnidadeDestino()); ?>
      </select>
    </div>

    <?
      PaginaSEI::getInstance()->montarBarraComandosInferior($arrComandos);
    ?>
  </form>
<?
PaginaSEI::getInstance()->fecharBody();
PaginaSEI::getInstance()->fecharHtml();
?>