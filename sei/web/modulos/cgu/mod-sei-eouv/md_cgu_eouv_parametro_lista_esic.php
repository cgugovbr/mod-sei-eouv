<?
/**
* TRIBUNAL REGIONAL FEDERAL DA 4� REGI�O
*
* 17/12/2007 - criado por fbv
*
* Vers�o do Gerador de C�digo: 1.10.1
*
* Vers�o no CVS: $Id$
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

  PaginaSEI::getInstance()->prepararSelecao('md_cgu_eouv_parametro_selecionar');

  SessaoSEI::getInstance()->validarPermissao($_GET['acao']);

    switch($_GET['acao']){
    case 'md_cgu_eouv_parametro_excluir':
      try{
        $arrStrIds = PaginaSEI::getInstance()->getArrStrItensSelecionados();
        $arrObjMdCguEouvParametroDTO = array();
        for ($i=0;$i<count($arrStrIds);$i++){
          $objMdCguEouvParametroDTO = new MdCguEouvParametroDTO();
          $objMdCguEouvParametroDTO->setNumIdParametro($arrStrIds[$i]);          
          $arrObjMdCguEouvParametroDTO[] = $objMdCguEouvParametroDTO;
        }
        $objMdCguEouvParametroRN = new MdCguEouvParametroRN();
        $objMdCguEouvParametroRN->excluirParametro($arrObjMdCguEouvParametroDTO);
        PaginaSEI::getInstance()->setStrMensagem('Opera��o realizada com sucesso.');
      }catch(Exception $e){
        PaginaSEI::getInstance()->processarExcecao($e);
      } 
      header('Location: '.SessaoSEI::getInstance()->assinarLink('controlador.php?acao='.$_GET['acao_origem'].'&acao_origem='.$_GET['acao']));
      die;

    case 'md_cgu_eouv_parametro_selecionar':
      $strTitulo = PaginaSEI::getInstance()->getTituloSelecao('Selecionar Param�tro','Selecionar Param�tros');

      //Se cadastrou alguem
      if ($_GET['acao_origem']=='md_cgu_eouv_parametro_cadastrar'){
        if (isset($_GET['id_md_cgu_eouv_parametro'])){
          PaginaSEI::getInstance()->adicionarSelecionado($_GET['id_md_cgu_eouv_parametro']);
        }
      }
      break;

    case 'md_cgu_eouv_parametro_listar_esic':
      $strTitulo = 'Param�tros do M�dulo de Integra��o SEI x FalaBR (e-Sic)';
      break;

    default:
      throw new InfraException("A��o '".$_GET['acao']."' n�o reconhecida.");
  }

  $arrComandos = array();
  if ($_GET['acao'] == 'md_cgu_eouv_parametro_selecionar'){
    $arrComandos[] = '<button type="button" accesskey="T" id="btnTransportarSelecao" value="Transportar" onclick="infraTransportarSelecao();" class="infraButton"><span class="infraTeclaAtalho">T</span>ransportar</button>';
  }

  if ($_GET['acao'] == 'md_cgu_eouv_parametro_listar' || $_GET['acao'] == 'md_cgu_eouv_parametro_selecionar'){
    $bolAcaoCadastrar = false; //SessaoSEI::getInstance()->verificarPermissao('md_cgu_eouv_parametro_cadastrar');
    if ($bolAcaoCadastrar){
      $arrComandos[] = '<button type="button" accesskey="N" id="btnNovo" value="Novo" onclick="location.href=\''.SessaoSEI::getInstance()->assinarLink('controlador.php?acao=md_cgu_eouv_parametro_cadastrar&acao_origem='.$_GET['acao'].'&acao_retorno='.$_GET['acao']).'\'" class="infraButton"><span class="infraTeclaAtalho">N</span>ovo</button>';
    }
  }
   
  $objMdCguEouvParametroDTO = new MdCguEouvParametroDTO();
  $objMdCguEouvParametroDTO->setStrDeTipo('esic');
  $objMdCguEouvParametroDTO->retNumIdParametro();
  $objMdCguEouvParametroDTO->retStrNoParametro();
  $objMdCguEouvParametroDTO->retStrDeValorParametro();

  PaginaSEI::getInstance()->prepararOrdenacao($objMdCguEouvParametroDTO, 'IdParametro', InfraDTO::$TIPO_ORDENACAO_ASC);
  //PaginaSEI::getInstance()->prepararPaginacao($objMdCguEouvParametroDTO);

  $objMdCguEouvParametroRN = new MdCguEouvParametroRN();
  $arrObjMdCguEouvParametroDTO = $objMdCguEouvParametroRN->listarParametroESic($objMdCguEouvParametroDTO);

  //PaginaSEI::getInstance()->processarPaginacao($objMdCguEouvParametroDTO);
  $numRegistros = count($arrObjMdCguEouvParametroDTO);

  if ($numRegistros > 0){

    $bolCheck = false;

    $bolAcaoReativar = false;
    $bolAcaoConsultar = false;
    $bolAcaoAlterar = SessaoSEI::getInstance()->verificarPermissao('md_cgu_eouv_parametro_alterar');
    $bolAcaoImprimir = false;
    $bolAcaoExcluir = false; //SessaoSEI::getInstance()->verificarPermissao('md_cgu_eouv_parametro_excluir');
    $bolAcaoDesativar = false;

    if ($bolAcaoDesativar){
      $bolCheck = true;
      $arrComandos[] = '<button type="button" accesskey="T" id="btnDesativar" value="Desativar" onclick="acaoDesativacaoMultipla();" class="infraButton">Desa<span class="infraTeclaAtalho">t</span>ivar</button>';
      $strLinkDesativar = SessaoSEI::getInstance()->assinarLink('controlador.php?acao=md_cgu_eouv_parametro_desativar&acao_origem='.$_GET['acao']);
    }

    if ($bolAcaoReativar){
      $bolCheck = true;
      $arrComandos[] = '<button type="button" accesskey="R" id="btnReativar" value="Reativar" onclick="acaoReativacaoMultipla();" class="infraButton"><span class="infraTeclaAtalho">R</span>eativar</button>';
      $strLinkReativar = SessaoSEI::getInstance()->assinarLink('controlador.php?acao=md_cgu_eouv_parametro_reativar&acao_origem='.$_GET['acao'].'&acao_confirmada=sim');
    }
    
    if ($bolAcaoExcluir){
      $bolCheck = true;
      $arrComandos[] = '<button type="button" accesskey="E" id="btnExcluir" value="Excluir" onclick="acaoExclusaoMultipla();" class="infraButton"><span class="infraTeclaAtalho">E</span>xcluir</button>';
      $strLinkExcluir = SessaoSEI::getInstance()->assinarLink('controlador.php?acao=md_cgu_eouv_parametro_excluir&acao_origem='.$_GET['acao']);
    }

    if ($bolAcaoImprimir){
      $bolCheck = true;
      $arrComandos[] = '<button type="button" accesskey="I" id="btnImprimir" value="Imprimir" onclick="infraImprimirTabela();" class="infraButton"><span class="infraTeclaAtalho">I</span>mprimir</button>';

    }

    $strResultado = '';

    if ($_GET['acao']!='md_cgu_eouv_parametro_reativar'){
      $strSumarioTabela = 'Tabela de Param�tros.';
      $strCaptionTabela = 'Param�tros';
    }else{
      $strSumarioTabela = 'Tabela de Param�tros Inativos.';
      $strCaptionTabela = 'Param�tros Inativos';
    }

    $strResultado .= '<table width="99%" class="infraTable" summary="'.$strSumarioTabela.'">'."\n";
    $strResultado .= '<caption class="infraCaption">'.PaginaSEI::getInstance()->gerarCaptionTabela($strCaptionTabela,$numRegistros).'</caption>';
    $strResultado .= '<tr>';
    if ($bolCheck) {
      $strResultado .= '<th class="infraTh" width="1%">'.PaginaSEI::getInstance()->getThCheck().'</th>'."\n";
    }
    $strResultado .= '<th class="infraTh">'.PaginaSEI::getInstance()->getThOrdenacao($objMdCguEouvParametroDTO,'ID','IdParametro',$arrObjMdCguEouvParametroDTO).'</th>'."\n";
    $strResultado .= '<th class="infraTh">'.PaginaSEI::getInstance()->getThOrdenacao($objMdCguEouvParametroDTO,'Nome','NoParametro',$arrObjMdCguEouvParametroDTO).'</th>'."\n";
    $strResultado .= '<th class="infraTh">Descri��o</th>'."\n";
    $strResultado .= '<th class="infraTh">A��es</th>'."\n";
    $strResultado .= '</tr>'."\n";
    $strCssTr='';
    for($i = 0;$i < $numRegistros; $i++){

      $strCssTr = ($strCssTr=='<tr class="infraTrClara">')?'<tr class="infraTrEscura">':'<tr class="infraTrClara">';
      $strResultado .= $strCssTr;

      if ($bolCheck){
        $strResultado .= '<td valign="top">'.PaginaSEI::getInstance()->getTrCheck($i,$arrObjMdCguEouvParametroDTO[$i]->getNumIdParametro(),$arrObjMdCguEouvParametroDTO[$i]->getStrNoParametro()).'</td>';
      }
      $strResultado .= '<td width="10%" align="center">'.$arrObjMdCguEouvParametroDTO[$i]->getNumIdParametro().'</td>';
      $strResultado .= '<td width="30%">'.PaginaSEI::tratarHTML($arrObjMdCguEouvParametroDTO[$i]->getStrNoParametro()).'</td>';
      $strResultado .= '<td>'.nl2br(PaginaSEI::tratarHTML($arrObjMdCguEouvParametroDTO[$i]->getStrDeValorParametro())).'</td>';
      $strResultado .= '<td width="15%" align="center">';
      
      $strResultado .= PaginaSEI::getInstance()->getAcaoTransportarItem($i,$arrObjMdCguEouvParametroDTO[$i]->getNumIdParametro());
      
      if ($bolAcaoConsultar){
        $strResultado .= '<a href="'.SessaoSEI::getInstance()->assinarLink('controlador.php?acao=md_cgu_eouv_parametro_consultar&acao_origem='.$_GET['acao'].'&acao_retorno='.$_GET['acao'].'&id_md_cgu_eouv_parametro='.$arrObjMdCguEouvParametroDTO[$i]->getNumIdParametro()).'" tabindex="'.PaginaSEI::getInstance()->getProxTabTabela().'"><img src="'.PaginaSEI::getInstance()->getIconeConsultar().'" title="Consultar Param�tro" alt="Consultar Param�tro" class="infraImg" /></a>&nbsp;';
      }

      if ($bolAcaoAlterar){
        $strResultado .= '<a href="'.SessaoSEI::getInstance()->assinarLink('controlador.php?acao=md_cgu_eouv_parametro_alterar&acao_origem='.$_GET['acao'].'&acao_retorno='.$_GET['acao'].'&id_md_cgu_eouv_parametro='.$arrObjMdCguEouvParametroDTO[$i]->getNumIdParametro()).'" tabindex="'.PaginaSEI::getInstance()->getProxTabTabela().'"><img src="'.PaginaSEI::getInstance()->getIconeAlterar().'" title="Alterar Param�tro" alt="Alterar Param�tro" class="infraImg" /></a>&nbsp;';
      }


      if ($bolAcaoDesativar){
        $strResultado .= '<a href="#ID-'.$arrObjMdCguEouvParametroDTO[$i]->getNumIdParametro().'"  onclick="acaoDesativar(\''.$arrObjMdCguEouvParametroDTO[$i]->getNumIdParametro().'\',\''.$arrObjMdCguEouvParametroDTO[$i]->getStrNoParametro().'\');" tabindex="'.PaginaSEI::getInstance()->getProxTabTabela().'"><img src="'.PaginaSEI::getInstance()->getIconeDesativar().'" title="Desativar Param�tro" alt="Desativar Param�tro" class="infraImg" /></a>&nbsp;';
      }

      if ($bolAcaoReativar){
        $strResultado .= '<a href="#ID-'.$arrObjMdCguEouvParametroDTO[$i]->getNumIdParametro().'"  onclick="acaoReativar(\''.$arrObjMdCguEouvParametroDTO[$i]->getNumIdParametro().'\',\''.$arrObjMdCguEouvParametroDTO[$i]->getStrNoParametro().'\');" tabindex="'.PaginaSEI::getInstance()->getProxTabTabela().'"><img src="'.PaginaSEI::getInstance()->getIconeReativar().'" title="Reativar Param�tro" alt="Reativar Param�tro" class="infraImg" /></a>&nbsp;';
      }


      if ($bolAcaoExcluir){
        $strResultado .= '<a href="#ID-'.$arrObjMdCguEouvParametroDTO[$i]->getNumIdParametro().'"  onclick="acaoExcluir(\''.$arrObjMdCguEouvParametroDTO[$i]->getNumIdParametro().'\',\''.$arrObjMdCguEouvParametroDTO[$i]->getStrNoParametro().'\');" tabindex="'.PaginaSEI::getInstance()->getProxTabTabela().'"><img src="'.PaginaSEI::getInstance()->getIconeExcluir().'" title="Excluir Param�tro" alt="Excluir Param�tro" class="infraImg" /></a>&nbsp;';
      }

      $strResultado .= '</td></tr>'."\n";
    }
    $strResultado .= '</table>';
  }
  if ($_GET['acao'] == 'md_cgu_eouv_parametro_selecionar'){
    $arrComandos[] = '<button type="button" accesskey="F" id="btnFecharSelecao" value="Fechar" onclick="window.close();" class="infraButton"><span class="infraTeclaAtalho">F</span>echar</button>';
  }else{
    $arrComandos[] = '<button type="button" accesskey="F" id="btnFechar" value="Fechar" onclick="location.href=\''.SessaoSEI::getInstance()->assinarLink('controlador.php?acao='.PaginaSEI::getInstance()->getAcaoRetorno().'&acao_origem='.$_GET['acao']).'\'" class="infraButton"><span class="infraTeclaAtalho">F</span>echar</button>';
  }

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
<?
PaginaSEI::getInstance()->fecharStyle();
PaginaSEI::getInstance()->montarJavaScript();
PaginaSEI::getInstance()->abrirJavaScript();
?>

function inicializar(){

  if ('<?=$_GET['acao']?>'=='md_cgu_eouv_parametro_selecionar'){
    infraReceberSelecao();
    document.getElementById('btnFecharSelecao').focus();
 }
  
  infraEfeitoTabelas();
}

<? if ($bolAcaoDesativar){ ?>
function acaoDesativar(id,desc){
  if (confirm("Confirma desativa��o do Param�tro \""+desc+"\"?")){
    document.getElementById('hdnInfraItemId').value=id;
    document.getElementById('frmMdCguEouvParametroLista').action='<?=$strLinkDesativar?>';
    document.getElementById('frmMdCguEouvParametroLista').submit();
  }
}

function acaoDesativacaoMultipla(){
  if (document.getElementById('hdnInfraItensSelecionados').value==''){
    alert('Nenhum Param�tro selecionado.');
    return;
  }
  if (confirm("Confirma desativa��o dos Param�tros selecionados?")){
    document.getElementById('hdnInfraItemId').value='';
    document.getElementById('frmMdCguEouvParametroLista').action='<?=$strLinkDesativar?>';
    document.getElementById('frmMdCguEouvParametroLista').submit();
  }
}
<? } ?>

<? if ($bolAcaoReativar){ ?>
function acaoReativar(id,desc){
  if (confirm("Confirma reativa��o do Param�tro \""+desc+"\"?")){
    document.getElementById('hdnInfraItemId').value=id;
    document.getElementById('frmMdCguEouvParametroLista').action='<?=$strLinkReativar?>';
    document.getElementById('frmMdCguEouvParametroLista').submit();
  }
}

function acaoReativacaoMultipla(){
  if (document.getElementById('hdnInfraItensSelecionados').value==''){
    alert('Nenhum Param�tro selecionado.');
    return;
  }
  if (confirm("Confirma reativa��o dos Param�tros selecionados?")){
    document.getElementById('hdnInfraItemId').value='';
    document.getElementById('frmMdCguEouvParametroLista').action='<?=$strLinkReativar?>';
    document.getElementById('frmMdCguEouvParametroLista').submit();
  }
}
<? } ?>

<? if ($bolAcaoExcluir){ ?>
function acaoExcluir(id,desc){
  if (confirm("Confirma exclus�o do Param�tro \""+desc+"\"?")){
    document.getElementById('hdnInfraItemId').value=id;
    document.getElementById('frmMdCguEouvParametroLista').action='<?=$strLinkExcluir?>';
    document.getElementById('frmMdCguEouvParametroLista').submit();
  }
}

function acaoExclusaoMultipla(){
  if (document.getElementById('hdnInfraItensSelecionados').value==''){
    alert('Nenhum Param�tro selecionado.');
    return;
  }
  if (confirm("Confirma exclus�o dos Param�tros selecionados?")){
    document.getElementById('hdnInfraItemId').value='';
    document.getElementById('frmMdCguEouvParametroLista').action='<?=$strLinkExcluir?>';
    document.getElementById('frmMdCguEouvParametroLista').submit();
  }
}
<? } ?>

<?
PaginaSEI::getInstance()->fecharJavaScript();
PaginaSEI::getInstance()->fecharHead();
PaginaSEI::getInstance()->abrirBody($strTitulo,'onload="inicializar();"');
?>
<form id="frmMdCguEouvParametroLista" method="post" action="<?=SessaoSEI::getInstance()->assinarLink('controlador.php?acao='.$_GET['acao'].'&acao_origem='.$_GET['acao'])?>">
  <?
  //PaginaSEI::getInstance()->montarBarraLocalizacao($strTitulo);
  PaginaSEI::getInstance()->montarBarraComandosSuperior($arrComandos);
  //PaginaSEI::getInstance()->abrirAreaDados('5em');
  //PaginaSEI::getInstance()->fecharAreaDados();
  PaginaSEI::getInstance()->montarAreaTabela($strResultado,$numRegistros);
  //PaginaSEI::getInstance()->montarAreaDebug();
  PaginaSEI::getInstance()->montarBarraComandosInferior($arrComandos);
  ?>
</form>
<?
PaginaSEI::getInstance()->fecharBody();
PaginaSEI::getInstance()->fecharHtml();
?>