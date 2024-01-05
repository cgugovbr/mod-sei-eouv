<?
/**
* CONTROLADORIA-GERAL DA UNIÃO
* Criado em 15/12/2022
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

  switch($_GET['acao']){
    case 'md_cgu_eouv_depara_importacao_listar':
      $strTitulo = 'Tipos de Manifestação do FalaBR e Tipos de Processo Associados';
      break;

    default:
      throw new InfraException("Ação '".$_GET['acao']."' não reconhecida.");
  }

  $arrComandos = array();

  $objDeParaDTO = new MdCguEouvDeparaImportacaoDTO();
  $objDeParaDTO->retTodos();
  $objDeParaDTO->retStrTipoProcedimento();

  PaginaSEI::getInstance()->prepararOrdenacao($objDeParaDTO, 'IdTipoManifestacaoEouv', InfraDTO::$TIPO_ORDENACAO_ASC);

  $objMdCguEouvDeparaImportacaoRN = new MdCguEouvDeparaImportacaoRN();
  $arrObjDeParaDTO = $objMdCguEouvDeparaImportacaoRN->listar($objDeParaDTO);

  $numRegistros = count($arrObjDeParaDTO);

  if ($numRegistros > 0) {
    $bolAcaoAlterar = SessaoSEI::getInstance()->verificarPermissao('md_cgu_eouv_depara_importacao_alterar');

    $strResultado = '';

    $strSumarioTabela = 'Tabela de Tipos de Manifestação.';
    $strCaptionTabela = 'Tipos de Manifestação';

    $strResultado .= '<table width="99%" class="infraTable" summary="'.$strSumarioTabela.'">'."\n";
    $strResultado .= '<caption class="infraCaption">'.PaginaSEI::getInstance()->gerarCaptionTabela($strCaptionTabela,$numRegistros).'</caption>';
    $strResultado .= '<tr>';
    $strResultado .= '<th class="infraTh">'.PaginaSEI::getInstance()->getThOrdenacao($objDeParaDTO,'ID da Manifestação','IdTipoManifestacaoEouv',$arrObjDeParaDTO).'</th>'."\n";
    $strResultado .= '<th class="infraTh">Tipo de Manifestação</th>'."\n";
    $strResultado .= '<th class="infraTh">Tipo de Processo Associado</th>'."\n";
    $strResultado .= '<th class="infraTh">Ações</th>'."\n";
    $strResultado .= '</tr>'."\n";
    $strCssTr='';
    for ($i = 0; $i < $numRegistros; $i++) {
      $strCssTr = ($strCssTr=='<tr class="infraTrClara">')?'<tr class="infraTrEscura">':'<tr class="infraTrClara">';
      $strResultado .= $strCssTr;

      $strResultado .= '<td width="15%" align="center">'.$arrObjDeParaDTO[$i]->getNumIdTipoManifestacaoEouv().'</td>';
      $strResultado .= '<td width="20%" align="center">'.PaginaSEI::tratarHTML($arrObjDeParaDTO[$i]->getStrDeTipoManifestacaoEouv()).'</td>';
      $strResultado .= '<td align="center">'.PaginaSEI::tratarHTML($arrObjDeParaDTO[$i]->getStrTipoProcedimento()).'</td>';
      $strResultado .= '<td width="15%" align="center">';

      if ($bolAcaoAlterar) {
        $strLinkAlterar = SessaoSEI::getInstance()->assinarLink('controlador.php?'.
          'acao=md_cgu_eouv_depara_importacao_alterar&'.
          'acao_origem='.$_GET['acao'].'&'.
          'acao_retorno='.$_GET['acao'].'&'.
          'id_md_cgu_eouv_tipo_manifestacao='.$arrObjDeParaDTO[$i]->getNumIdTipoManifestacaoEouv()
        );
        $strResultado .= '<a href="'.$strLinkAlterar.'" tabindex="'.PaginaSEI::getInstance()->getProxTabTabela().'">';
        $strResultado .= '<img src="'.PaginaSEI::getInstance()->getIconeAlterar().'" title="Alterar Associação" alt="Alterar Associação" class="infraImg" />';
        $strResultado .= '</a>&nbsp;';
      }

      $strResultado .= '</td></tr>'."\n";
    }
    $strResultado .= '</table>';
  }

  $strLinkFechar = SessaoSEI::getInstance()->assinarLink('controlador.php?'.
    'acao='.PaginaSEI::getInstance()->getAcaoRetorno().'&'.
    'acao_origem='.$_GET['acao']
  );
  $arrComandos[] = '<button type="button" accesskey="F" id="btnFechar" value="Fechar" onclick="location.href=\''.$strLinkFechar.'\'" class="infraButton"><span class="infraTeclaAtalho">F</span>echar</button>';
} catch (Exception $e) {
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
<?
PaginaSEI::getInstance()->fecharStyle();
PaginaSEI::getInstance()->montarJavaScript();
PaginaSEI::getInstance()->abrirJavaScript();
?>

function inicializar(){
  infraEfeitoTabelas();
}

<?
PaginaSEI::getInstance()->fecharJavaScript();
PaginaSEI::getInstance()->fecharHead();
PaginaSEI::getInstance()->abrirBody($strTitulo,'onload="inicializar();"');
?>
<form id="frmMdCguEouvDeparaImportacaoLista" method="post" action="<?=SessaoSEI::getInstance()->assinarLink('controlador.php?acao='.$_GET['acao'].'&acao_origem='.$_GET['acao'])?>">
  <?
  PaginaSEI::getInstance()->montarBarraComandosSuperior($arrComandos);
  PaginaSEI::getInstance()->montarAreaTabela($strResultado,$numRegistros);
  //PaginaSEI::getInstance()->montarAreaDebug();
  PaginaSEI::getInstance()->montarBarraComandosInferior($arrComandos);
  ?>
</form>
<?
PaginaSEI::getInstance()->fecharBody();
PaginaSEI::getInstance()->fecharHtml();
?>