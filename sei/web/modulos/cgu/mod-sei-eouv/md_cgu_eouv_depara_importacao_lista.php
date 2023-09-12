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

  PaginaSEI::getInstance()->prepararSelecao('md_cgu_eouv_depara_importacao_selecionar');

  SessaoSEI::getInstance()->validarPermissao($_GET['acao']);

  switch($_GET['acao']){
      case 'md_cgu_eouv_depara_importacao_listar':
        $strTitulo = 'Tipos de Manifestação do FalaBR e Tipos de Processo Associados';
      break;

      case 'md_cgu_eouv_depara_importacao_excluir':
          try{
              $arrStrIds = PaginaSEI::getInstance()->getArrStrItensSelecionados();
              $arrObjDeParaDTO = array();
              for ($i=0, $iMax = count($arrStrIds); $i<$iMax; $i++){
                  $objMdCguEouvDeparaImportacaoDTO = new MdCguEouvDeparaImportacaoDTO();
                  $objMdCguEouvDeparaImportacaoDTO->setIdTipoManifestacaoEouv($arrStrIds[$i]);
                  $arrObjDeParaDTO[] = $objMdCguEouvDeparaImportacaoDTO;
              }
              $objMdCguEouvDeparaImportacaoRN = new MdCguEouvDeparaImportacaoRN();
              $objMdCguEouvDeparaImportacaoRN->excluir($arrObjDeParaDTO);
              PaginaSEI::getInstance()->adicionarMensagem('Operação realizada com sucesso.');
          }catch(Exception $e){
              PaginaSEI::getInstance()->processarExcecao($e);
          }
          header('Location: '.SessaoSei::getInstance()->assinarLink('controlador.php?acao='.$_GET['acao_origem'].'&acao_origem='.$_GET['acao']));
          die;

      case 'md_cgu_eouv_depara_importacao_desativar':
          try{
              $arrStrIds = PaginaSEI::getInstance()->getArrStrItensSelecionados();
              $arrObjDeParaDTO = array();
              for ($i=0, $iMax = count($arrStrIds); $i<$iMax; $i++){
                  $objMdCguEouvDeparaImportacaoDTO = new MdCguEouvDeparaImportacaoDTO();
                  $objMdCguEouvDeparaImportacaoDTO->setIdTipoManifestacaoEouv($arrStrIds[$i]);
                  $arrObjDeParaDTO[] = $objMdCguEouvDeparaImportacaoDTO;
              }
              $objMdCguEouvDeparaImportacaoRN = new MdCguEouvDeparaImportacaoRN();
              $objMdCguEouvDeparaImportacaoRN->desativar($arrObjDeParaDTO);
              PaginaSEI::getInstance()->adicionarMensagem('Operação realizada com sucesso.');
          }catch(Exception $e){
              PaginaSEI::getInstance()->processarExcecao($e);
          }
          header('Location: '.SessaoSei::getInstance()->assinarLink('controlador.php?acao='.$_GET['acao_origem'].'&acao_origem='.$_GET['acao']));
          die;

      case 'md_cgu_eouv_depara_importacao_reativar':
          $strTitulo = 'Reativar Agendamentos de Tarefas';

          if ($_GET['acao_confirmada']==='sim'){
              try{
                  $arrStrIds = PaginaSEI::getInstance()->getArrStrItensSelecionados();
                  $arrObjDeParaDTO = array();
                  for ($i=0, $iMax = count($arrStrIds); $i<$iMax; $i++){
                      $objMdCguEouvDeparaImportacaoDTO = new MdCguEouvDeparaImportacaoDTO();
                      $objMdCguEouvDeparaImportacaoDTO->setIdTipoManifestacaoEouv($arrStrIds[$i]);
                      $arrObjDeParaDTO[] = $objMdCguEouvDeparaImportacaoDTO;
                  }
                  $objMdCguEouvDeparaImportacaoRN = new MdCguEouvDeparaImportacaoRN();
                  $objMdCguEouvDeparaImportacaoRN->reativar($arrObjDeParaDTO);
                  PaginaSEI::getInstance()->adicionarMensagem('Operação realizada com sucesso.');
              }catch(Exception $e){
                  PaginaSEI::getInstance()->processarExcecao($e);
              }
              header('Location: '.SessaoSei::getInstance()->assinarLink('controlador.php?acao='.$_GET['acao_origem'].'&acao_origem='.$_GET['acao']));
              die;
          }
          break;

      case 'md_cgu_eouv_depara_importacao_selecionar':
          $strTitulo = PaginaSEI::getInstance()->getTituloSelecao('Selecionar Agendamento de Tarefa','Selecionar Agendamentos de Tarefas');

          //Se cadastrou alguem
          if ($_GET['acao_origem']==='md_cgu_eouv_depara_importacao_cadastrar'){
              if (isset($_GET['id_md_cgu_eouv_depara_importacao'])){
                  PaginaSEI::getInstance()->adicionarSelecionado($_GET['id_md_cgu_eouv_depara_importacao']);
              }
          }
          break;

    default:
      throw new InfraException("Ação '".$_GET['acao']."' não reconhecida.");
  }

  $arrComandos = array();
  if ($_GET['acao'] === 'md_cgu_eouv_depara_importacao_listar' || $_GET['acao'] === 'md_cgu_eouv_depara_importacao_selecionar'){
        $bolAcaoCadastrar = SessaoSei::getInstance()->verificarPermissao('md_cgu_eouv_depara_importacao_cadastrar');
        if ($bolAcaoCadastrar){
            $arrComandos[] = '<button type="button" accesskey="N" id="btnNovo" value="Novo" onclick="location.href=\''.SessaoSei::getInstance()->assinarLink('controlador.php?acao=md_cgu_eouv_depara_importacao_cadastrar&acao_origem='.$_GET['acao'].'&acao_retorno='.$_GET['acao']).'\'" class="infraButton"><span class="infraTeclaAtalho">N</span>ovo</button>';
        }
  }
  $objDeParaDTO = new MdCguEouvDeparaImportacaoDTO();
  $objDeParaDTO->retTodos();
  $objDeParaDTO->retStrTipoProcedimento();
  $objDeParaDTO->setBolExclusaoLogica(false);
  PaginaSEI::getInstance()->prepararOrdenacao($objDeParaDTO, 'IdTipoManifestacaoEouv', InfraDTO::$TIPO_ORDENACAO_ASC);

  $objMdCguEouvDeparaImportacaoRN = new MdCguEouvDeparaImportacaoRN();
  $arrObjDeParaDTO = $objMdCguEouvDeparaImportacaoRN->listar($objDeParaDTO);

  $numRegistros = count($arrObjDeParaDTO);

  if ($numRegistros > 0) {
    $bolAcaoAlterar = SessaoSEI::getInstance()->verificarPermissao('md_cgu_eouv_depara_importacao_alterar');

      $bolCheck = false;

      if ($_GET['acao']==='md_cgu_eouv_depara_importacao_selecionar'){
          $bolAcaoReativar = false;
          $bolAcaoAlterar = SessaoSei::getInstance()->verificarPermissao('md_cgu_eouv_depara_importacao_alterar');
          $bolAcaoExcluir = false;
          $bolAcaoDesativar = false;
          $bolCheck = true;
      }else{
          $bolAcaoReativar = SessaoSei::getInstance()->verificarPermissao('md_cgu_eouv_depara_importacao_reativar');
          $bolAcaoAlterar = SessaoSei::getInstance()->verificarPermissao('md_cgu_eouv_depara_importacao_alterar');
          $bolAcaoExcluir = SessaoSei::getInstance()->verificarPermissao('md_cgu_eouv_depara_importacao_excluir');
          $bolAcaoDesativar = SessaoSei::getInstance()->verificarPermissao('md_cgu_eouv_depara_importacao_desativar');
      }

      if ($bolAcaoDesativar){
          $arrComandos[] = '<button type="button" accesskey="t" id="btnDesativar" value="Desativar" onclick="acaoDesativacaoMultipla();" class="infraButton">Desa<span class="infraTeclaAtalho">t</span>ivar</button>';
          $strLinkDesativar = SessaoSei::getInstance()->assinarLink('controlador.php?acao=md_cgu_eouv_depara_importacao_desativar&acao_origem='.$_GET['acao']);
      }

      if ($bolAcaoReativar){
          $arrComandos[] = '<button type="button" accesskey="R" id="btnReativar" value="Reativar" onclick="acaoReativacaoMultipla();" class="infraButton"><span class="infraTeclaAtalho">R</span>eativar</button>';
          $strLinkReativar = SessaoSei::getInstance()->assinarLink('controlador.php?acao=md_cgu_eouv_depara_importacao_reativar&acao_origem='.$_GET['acao'].'&acao_confirmada=sim');
      }

      if ($bolAcaoExcluir){
          $bolCheck = true;
          $arrComandos[] = '<button type="button" accesskey="E" id="btnExcluir" value="Excluir" onclick="acaoExclusaoMultipla();" class="infraButton"><span class="infraTeclaAtalho">E</span>xcluir</button>';
          $strLinkExcluir = SessaoSei::getInstance()->assinarLink('controlador.php?acao=md_cgu_eouv_depara_importacao_excluir&acao_origem='.$_GET['acao']);
      }

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
        if ($arrObjDeParaDTO[$i]->getStrSinAtivo()=='S'){
                $strCssTr = ($strCssTr=='<tr class="infraTrClara">')?'<tr class="infraTrEscura">':'<tr class="infraTrClara">';
                $strResultado .= $strCssTr;
        }else{
                $strCssTr = '<tr class="trVermelha">';
                $strResultado .= $strCssTr;
        }


        $strResultado .= '<td width="15%" align="center">'.$arrObjDeParaDTO[$i]->getNumIdTipoManifestacaoEouv().'</td>';
        $strResultado .= '<td width="20%" align="center">'.PaginaSEI::tratarHTML($arrObjDeParaDTO[$i]->getStrDeTipoManifestacaoEouv()).'</td>';
        $strResultado .= '<td align="center">'.PaginaSEI::tratarHTML($arrObjDeParaDTO[$i]->getStrTipoProcedimento()).'</td>';
        $strResultado .= '<td width="15%" align="center">';

        $strId = $arrObjDeParaDTO[$i]->getNumIdTipoManifestacaoEouv();
        $strDescricao = PaginaSEI::getInstance()->formatarParametrosJavaScript($arrObjDeParaDTO[$i]->getStrDeTipoManifestacaoEouv());

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
        if ($bolAcaoDesativar && $arrObjDeParaDTO[$i]->getStrSinAtivo()=='S'){
            $strResultado .= '<a href="'.PaginaSEI::getInstance()->montarAncora($strId).'" onclick="acaoDesativar(\''.$strId.'\',\''.$strDescricao.
                '\');" tabindex="'.PaginaSEI::getInstance()->getProxTabTabela().
                '"><img src="'.PaginaSEI::getInstance()->getIconeDesativar().'" title="Desativar" alt="Desativar" class="infraImg" /></a>&nbsp;';
        }

        if ($bolAcaoReativar && $arrObjDeParaDTO[$i]->getStrSinAtivo()=='N'){
            $strResultado .= '<a href="'.PaginaSEI::getInstance()->montarAncora($strId).'" onclick="acaoReativar(\''.$strId.'\',\''.$strDescricao.'\');" tabindex="'.PaginaSEI::getInstance()->getProxTabTabela().'"><img src="'.PaginaSEI::getInstance()->getIconeReativar().'" title="Reativar" alt="Reativar" class="infraImg" /></a>&nbsp;';
        }

        if ($bolAcaoExcluir){
            $strResultado .= '<a href="'.PaginaSEI::getInstance()->montarAncora($strId).'" onclick="acaoExcluir(\''.$strId.'\',\''.$strDescricao.'\');" tabindex="'.PaginaSEI::getInstance()->getProxTabTabela().'"><img src="'.PaginaSEI::getInstance()->getIconeExcluir().'" title="Excluir" alt="Excluir" class="infraImg" /></a>&nbsp;';
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
<?if(0){?><style><?}?>
#lblComandoListar {position:absolute;left:0%;top:0%;width:31%;}
#txtComandoListar {position:absolute;left:0%;top:40%;width:31%;}

#lblComplementoListar {position:absolute;left:34%;top:0%;width:17%;}
#txtComplementoListar {position:absolute;left:34%;top:40%;width:17%;}

#lblSinSucessoListar {position:absolute;left:54%;top:0%;width:10%;}
#selSinSucessoListar {position:absolute;left:54%;top:40%;width:10%;}

#lblSinAtivoListar {position:absolute;left:67%;top:0%;width:10%;}
#selSinAtivoListar  {position:absolute;left:67%;top:40%;width:10%;}

#lblStaPeriodicidadeExecucaoListar {position:absolute;left:80%;top:0%;width:15%;}
#selStaPeriodicidadeExecucaoListar {position:absolute;left:80%;top:40%;width:15%;}

tr.trVermelha{
    background-color:#f59f9f;
}
<?if(0){?></style><?}?>
<?
PaginaSEI::getInstance()->fecharStyle();
PaginaSEI::getInstance()->montarJavaScript();
PaginaSEI::getInstance()->abrirJavaScript();
?>
<?if(0){?><script type="text/javascript"><?}?>

    function inicializar() {
        if ('<?=$_GET['acao']?>'=='md_cgu_eouv_depara_importacao_selecionar') {
            infraReceberSelecao();
            document.getElementById('btnFecharSelecao').focus();
        } else {
            document.getElementById('btnFechar').focus();
        }
        infraEfeitoTabelas();
    }

<? if ($bolAcaoDesativar){ ?>
    function acaoDesativar(id, desc) {
        if (confirm("Confirma desativação do Agendamento de Tarefa \"" + desc + "\"?")) {
            document.getElementById('hdnInfraItemId').value = id;
            document.getElementById('frmInfraAgendamentoTarefaLista').action = '<?=$strLinkDesativar?>';
            document.getElementById('frmInfraAgendamentoTarefaLista').submit();
        }
    }

    function acaoDesativacaoMultipla() {
        if (document.getElementById('hdnInfraItensSelecionados').value=='') {
        alert('Nenhuma  selecionada.');
        return;
        }
        if (confirm("Confirma desativação dos Agendamentos de Tarefas selecionados?")) {
            document.getElementById('hdnInfraItemId').value = '';
            document.getElementById('frmInfraAgendamentoTarefaLista').action = '<?=$strLinkDesativar?>';
            document.getElementById('frmInfraAgendamentoTarefaLista').submit();
        }
    }
<? } ?>

<? if ($bolAcaoReativar){ ?>
    function acaoReativar(id, desc) {
        if (confirm("Confirma reativação do Agendamento de Tarefa  \"" + desc + "\"?")) {
            document.getElementById('hdnInfraItemId').value = id;
            document.getElementById('frmInfraAgendamentoTarefaLista').action = '<?=$strLinkReativar?>';
            document.getElementById('frmInfraAgendamentoTarefaLista').submit();
        }
    }

    function acaoReativacaoMultipla() {
        if (document.getElementById('hdnInfraItensSelecionados').value=='') {
            alert('Nenhum Agendamento de Tarefa selecionado.');
            return;
        }
        if (confirm("Confirma reativação dos Agendamentos de Tarefas selecionados?")) {
            document.getElementById('hdnInfraItemId').value = '';
            document.getElementById('frmInfraAgendamentoTarefaLista').action = '<?=$strLinkReativar?>';
            document.getElementById('frmInfraAgendamentoTarefaLista').submit();
        }
    }
<? } ?>

<? if ($bolAcaoExcluir){ ?>
    function acaoExcluir(id, desc) {
        if (confirm("Confirma exclusão de \"" + desc + "\"?")) {
            document.getElementById('hdnInfraItemId').value = id;
            document.getElementById('frmInfraAgendamentoTarefaLista').action = '<?=$strLinkExcluir?>';
            document.getElementById('frmInfraAgendamentoTarefaLista').submit();
        }
    }

    function acaoExclusaoMultipla() {
        if (document.getElementById('hdnInfraItensSelecionados').value=='') {
            alert('Nenhuma  selecionada.');
            return;
        }
        if (confirm("Confirma exclusão dos itens selecionados?")) {
            document.getElementById('hdnInfraItemId').value = '';
            document.getElementById('frmInfraAgendamentoTarefaLista').action = '<?=$strLinkExcluir?>';
            document.getElementById('frmInfraAgendamentoTarefaLista').submit();
        }
    }
<? } ?>
<?if(0){?></script><?}?>

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