<?
/**
* TRIBUNAL REGIONAL FEDERAL DA 4� REGI�O
*
* 15/12/2011 - criado por tamir_db
*
* Vers�o do Gerador de C�digo: 1.32.1
*
* Vers�o no CVS: $Id$
*/

//try {

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
  	case 'infra_agendamento_tarefa_executar':
  		try{
	  		$objEouvRelatorioImportacaoDTO = new InfraAgendamentoTarefaDTO();
	  		$objEouvRelatorioImportacaoDTO->setBolExclusaoLogica(false);
	  		$objEouvRelatorioImportacaoDTO->setNumIdInfraAgendamentoTarefa($_GET['id_infra_agendamento_tarefa']);
	  		
	  		$objEouvRelatorioImportacaoDTO->retNumIdInfraAgendamentoTarefa();
	  		$objEouvRelatorioImportacaoDTO->retStrComando();
	  		$objEouvRelatorioImportacaoDTO->retStrParametro();
	  		
	  		$objEouvRelatorioImportacaoRN = new InfraAgendamentoTarefaRN();
	  		$objEouvRelatorioImportacaoDTO = $objEouvRelatorioImportacaoRN->consultar($objEouvRelatorioImportacaoDTO);
	  		
	  		// n�o � necess�rio gravar o log acesso manualmente aqui pois ele ser� gravado pelo pr�prio controlador qdo a tarefa � executada via centralrh
	  		
	  		// obt�m lista de par�metros
	  		$strParametros = explode(',', $objEouvRelatorioImportacaoDTO->getStrParametro());
	  		$arrParametros = array();
	  		foreach($strParametros as $strParametro){
	  			list($chave, $valor) = explode('=',$strParametro);
	  			$arrParametros[$chave][] = $valor;
	  		}
	  		
	  		// grava data de execu��o
	  		$objInfraAgendamentoTarefaDTO2 = new InfraAgendamentoTarefaDTO();
	  		$objInfraAgendamentoTarefaDTO2->setNumIdInfraAgendamentoTarefa($objEouvRelatorioImportacaoDTO->getNumIdInfraAgendamentoTarefa());
	  		$objInfraAgendamentoTarefaDTO2->setDthUltimaExecucao(InfraData::getStrDataHoraAtual());
	  		$objInfraAgendamentoTarefaDTO2->setStrSinSucesso('N');
	  		$objEouvRelatorioImportacaoRN->alterar($objInfraAgendamentoTarefaDTO2);
	  		
	  		// executa tarefa
	  		list($strClasse, $strMetodo) = explode('::',$objEouvRelatorioImportacaoDTO->getStrComando());
	  		eval('$objRN = new '.$strClasse.'();');
	  		eval('$objRN->'.$strMetodo.'($arrParametros);');
	  		
	  		// grava data de conclus�o e seta flag de sucesso
	  		$objInfraAgendamentoTarefaDTO2 = new InfraAgendamentoTarefaDTO();
	  		$objInfraAgendamentoTarefaDTO2->setNumIdInfraAgendamentoTarefa($objEouvRelatorioImportacaoDTO->getNumIdInfraAgendamentoTarefa());
	  		$objInfraAgendamentoTarefaDTO2->setDthUltimaConclusao(InfraData::getStrDataHoraAtual());
	  		$objInfraAgendamentoTarefaDTO2->setStrSinSucesso('S');
	  		$objEouvRelatorioImportacaoRN->alterar($objInfraAgendamentoTarefaDTO2);
	  		PaginaSEI::getInstance()->setStrMensagem('Execu��o conclu�da com sucesso.');
	  		
      }catch(Exception $e){
        PaginaSEI::getInstance()->processarExcecao($e);
      } 
      header('Location: '.SessaoSEI::getInstance()->assinarLink('controlador.php?acao='.$_GET['acao_origem'].'&acao_origem='.$_GET['acao'].PaginaSEI::getInstance()->montarAncora($objEouvRelatorioImportacaoDTO->getNumIdInfraAgendamentoTarefa())));
      die;
  		break;
  		
    case 'infra_agendamento_tarefa_excluir':
      try{
        $arrStrIds = PaginaSEI::getInstance()->getArrStrItensSelecionados();
        $arrObjEouvRelatorioImportacaoDTO = array();
        for ($i=0;$i<count($arrStrIds);$i++){
          $objEouvRelatorioImportacaoDTO = new InfraAgendamentoTarefaDTO();
          $objEouvRelatorioImportacaoDTO->setNumIdInfraAgendamentoTarefa($arrStrIds[$i]);
          $arrObjEouvRelatorioImportacaoDTO[] = $objEouvRelatorioImportacaoDTO;
        }
        $objEouvRelatorioImportacaoRN = new InfraAgendamentoTarefaRN();
        $objEouvRelatorioImportacaoRN->excluir($arrObjEouvRelatorioImportacaoDTO);
        PaginaSEI::getInstance()->adicionarMensagem('Opera��o realizada com sucesso.');
      }catch(Exception $e){
        PaginaSEI::getInstance()->processarExcecao($e);
      } 
      header('Location: '.SessaoSEI::getInstance()->assinarLink('controlador.php?acao='.$_GET['acao_origem'].'&acao_origem='.$_GET['acao']));
      die;


    case 'infra_agendamento_tarefa_desativar':
      try{
        $arrStrIds = PaginaSEI::getInstance()->getArrStrItensSelecionados();
        $arrObjEouvRelatorioImportacaoDTO = array();
        for ($i=0;$i<count($arrStrIds);$i++){
          $objEouvRelatorioImportacaoDTO = new InfraAgendamentoTarefaDTO();
          $objEouvRelatorioImportacaoDTO->setNumIdInfraAgendamentoTarefa($arrStrIds[$i]);
          $arrObjEouvRelatorioImportacaoDTO[] = $objEouvRelatorioImportacaoDTO;
        }
        $objEouvRelatorioImportacaoRN = new InfraAgendamentoTarefaRN();
        $objEouvRelatorioImportacaoRN->desativar($arrObjEouvRelatorioImportacaoDTO);
        PaginaSEI::getInstance()->adicionarMensagem('Opera��o realizada com sucesso.');
      }catch(Exception $e){
        PaginaSEI::getInstance()->processarExcecao($e);
      } 
      header('Location: '.SessaoSEI::getInstance()->assinarLink('controlador.php?acao='.$_GET['acao_origem'].'&acao_origem='.$_GET['acao']));
      die;

    case 'infra_agendamento_tarefa_reativar':
      $strTitulo = 'Reativar Agendamentos de Tarefas';
      
      if ($_GET['acao_confirmada']=='sim'){
        try{
          $arrStrIds = PaginaSEI::getInstance()->getArrStrItensSelecionados();
          $arrObjEouvRelatorioImportacaoDTO = array();
          for ($i=0;$i<count($arrStrIds);$i++){
            $objEouvRelatorioImportacaoDTO = new InfraAgendamentoTarefaDTO();
            $objEouvRelatorioImportacaoDTO->setNumIdInfraAgendamentoTarefa($arrStrIds[$i]);
            $arrObjEouvRelatorioImportacaoDTO[] = $objEouvRelatorioImportacaoDTO;
          }
          $objEouvRelatorioImportacaoRN = new InfraAgendamentoTarefaRN();
          $objEouvRelatorioImportacaoRN->reativar($arrObjEouvRelatorioImportacaoDTO);
          PaginaSEI::getInstance()->adicionarMensagem('Opera��o realizada com sucesso.');
        }catch(Exception $e){
          PaginaSEI::getInstance()->processarExcecao($e);
        } 
        header('Location: '.SessaoSEI::getInstance()->assinarLink('controlador.php?acao='.$_GET['acao_origem'].'&acao_origem='.$_GET['acao']));
        die;
      } 
      break;


    case 'md_cgu_eouv_relatorio_importacao_listar':
      $strTitulo = 'Lista de Importa��es Realizadas';
      break;

    default:
      throw new InfraException("A��o '".$_GET['acao']."' n�o reconhecida.");
  }



  $arrComandos = array();
  $arrComandos[] = '<button type="button" accesskey="P" onclick="pesquisar();" id="btnPesquisar" value="Pesquisar" class="infraButton"><span class="infraTeclaAtalho">P</span>esquisar</button>';

  $objEouvRelatorioImportacaoDTO = new MdCguEouvRelatorioImportacaoDTO();
  $objEouvRelatorioImportacaoDTO->retNumIdRelatorioImportacao();
  $objEouvRelatorioImportacaoDTO->retDthDthImportacao();
  $objEouvRelatorioImportacaoDTO->retStrSinSucesso();
  $objEouvRelatorioImportacaoDTO->retDthDthPeriodoInicial();
  $objEouvRelatorioImportacaoDTO->retDthDthPeriodoFinal();
  $objEouvRelatorioImportacaoDTO->retStrDeLogProcessamento();



  if (isset($_POST['txtTextoPesquisa']) && ($_POST['txtTextoPesquisa']) != ''){

      $objEouvRelatorioImportacaoDetalheDTO = new MdCguEouvRelatorioImportacaoDetalheDTO();
      $objEouvRelatorioImportacaoDetalheDTO -> retNumIdRelatorioImportacao();
      $objEouvRelatorioImportacaoDetalheDTO -> setStrProtocoloFormatado(trim($_POST['txtTextoPesquisa']));

      $objEouvRelatorioImportacaoDetalheRN = new MdCguEouvRelatorioImportacaoDetalheRN();

      $arrRelaorios = InfraArray::converterArrInfraDTO($objEouvRelatorioImportacaoDetalheRN->listar($objEouvRelatorioImportacaoDetalheDTO),'IdRelatorioImportacao');

      $objEouvRelatorioImportacaoDTO->adicionarCriterio(array('IdRelatorioImportacao'),
          array(InfraDTO::$OPER_IN),
          array($arrRelaorios));
  }

  PaginaSEI::getInstance()->prepararOrdenacao($objEouvRelatorioImportacaoDTO, 'IdRelatorioImportacao', InfraDTO::$TIPO_ORDENACAO_DESC);
  PaginaSEI::getInstance()->prepararPaginacao($objEouvRelatorioImportacaoDTO);

  $objEouvRelatorioImportacaoRN = new MdCguEouvRelatorioImportacaoRN();
  $arrObjEouvRelatorioImportacaoDTO = $objEouvRelatorioImportacaoRN->listar($objEouvRelatorioImportacaoDTO);


  PaginaSEI::getInstance()->processarPaginacao($objEouvRelatorioImportacaoDTO);
  $numRegistros = count($arrObjEouvRelatorioImportacaoDTO);

  if ($numRegistros > 0){
  	
    $bolCheck = false;

    if ($_GET['acao']=='md_cgu_eouv_relatorio_importacao_listar'){
      $bolAcaoConsultar = true;//SessaoSEI::getInstance()->verificarPermissao('infra_agendamento_tarefa_consultar');
      //$bolCheck = true;
      $bolAcaoExecutar = false;
    }else{
      $bolAcaoConsultar = SessaoSEI::getInstance()->verificarPermissao('md_cgu_eouv_relatorio_importacao_detalhe');

    }

    $strResultado = '';

    if ($_GET['acao']!='infra_agendamento_tarefa_reativar'){
      $strSumarioTabela = 'Tabela de Execu�oes.';
      $strCaptionTabela = 'Agendamentos';
    }else{
      $strSumarioTabela = 'Tabela de Agendamentos Inativos.';
      $strCaptionTabela = 'Agendamentos Inativos';
    }


    $strResultado .= '<table width="99%" class="infraTable" summary="'.$strSumarioTabela.'">'."\n";
    $strResultado .= '<caption class="infraCaption">'.PaginaSEI::getInstance()->gerarCaptionTabela($strCaptionTabela,$numRegistros).'</caption>';
    $strResultado .= '<tr>';

    $strResultado .= '<th class="infraTh" width="5%">'.PaginaSEI::getInstance()->getThOrdenacao($objEouvRelatorioImportacaoDTO,'ID','IdRelatorioImportacao',$arrObjEouvRelatorioImportacaoDTO).'</th>'."\n";
    $strResultado .= '<th class="infraTh" width="10%">'.PaginaSEI::getInstance()->getThOrdenacao($objEouvRelatorioImportacaoDTO,'Data da Execu��o','DthImportacao',$arrObjEouvRelatorioImportacaoDTO).'</th>'."\n";
    $strResultado .= '<th class="infraTh" width="7%">'.PaginaSEI::getInstance()->getThOrdenacao($objEouvRelatorioImportacaoDTO,'Sucesso','SinSucesso',$arrObjEouvRelatorioImportacaoDTO).'</th>'."\n";
    $strResultado .= '<th class="infraTh" width="10%">'.PaginaSEI::getInstance()->getThOrdenacao($objEouvRelatorioImportacaoDTO,'Per�odo Inicial','DthPeriodoInicial',$arrObjEouvRelatorioImportacaoDTO).'</th>'."\n";
    $strResultado .= '<th class="infraTh" width="10%">'.PaginaSEI::getInstance()->getThOrdenacao($objEouvRelatorioImportacaoDTO,'Per�odo Final','DthPeriodoFinal',$arrObjEouvRelatorioImportacaoDTO).'</th>'."\n";
    $strResultado .= '<th class="infraTh" >'.PaginaSEI::getInstance()->getThOrdenacao($objEouvRelatorioImportacaoDTO,'Log do Processamento','StrDeLogProcessamento',$arrObjEouvRelatorioImportacaoDTO).'</th>'."\n";
    $strResultado .= '<th class="infraTh" width="15%">A��es</th>'."\n";
    $strResultado .= '</tr>'."\n";
    $strCssTr='';
    for($i = 0;$i < $numRegistros; $i++){


      $strCssTr = ($strCssTr=='<tr class="infraTrClara">')?'<tr class="infraTrEscura">':'<tr class="infraTrClara">';
      $strResultado .= $strCssTr;

      if ($bolCheck){
        $strResultado .= '<td>'.PaginaSEI::getInstance()->getTrCheck($i,$arrObjEouvRelatorioImportacaoDTO[$i]->getNumIdInfraAgendamentoTarefa(),$arrObjEouvRelatorioImportacaoDTO[$i]->getNumIdRelatorioImportacao()).'</td>';
      }

      $strResultado .= '<td>'.$arrObjEouvRelatorioImportacaoDTO[$i]->getNumIdRelatorioImportacao().'</td>';
      $strResultado .= '<td>'.$arrObjEouvRelatorioImportacaoDTO[$i]->getDthDthImportacao().'</td>';
      $strResultado .= '<td align="center">'.$arrObjEouvRelatorioImportacaoDTO[$i]->getStrSinSucesso().'</td>';
      $strResultado .= '<td align="center">'.$arrObjEouvRelatorioImportacaoDTO[$i]->getDthDthPeriodoInicial().'</td>';
      $strResultado .= '<td align="center">'.$arrObjEouvRelatorioImportacaoDTO[$i]->getDthDthPeriodoFinal().'</td>';
      $strResultado .= '<td align="left">'.$arrObjEouvRelatorioImportacaoDTO[$i]->getStrDeLogProcessamento().'</td>';
      $strResultado .= '<td align="center">';

      $strResultado .= PaginaSEI::getInstance()->getAcaoTransportarItem($i,$arrObjEouvRelatorioImportacaoDTO[$i]->getNumIdRelatorioImportacao());

      $strId = $arrObjEouvRelatorioImportacaoDTO[$i]->getNumIdRelatorioImportacao();
      //$strDescricao = PaginaSEI::getInstance()->formatarParametrosJavaScript($arrObjEouvRelatorioImportacaoDTO[$i]->getStrComando());

      if ($bolAcaoConsultar){
        $strResultado .= '<a href="'.PaginaSEI::getInstance()->formatarXHTML(SessaoSEI::getInstance()->assinarLink('controlador.php?acao=md_cgu_eouv_relatorio_importacao_detalhar&acao_origem='.$_GET['acao'].'&acao_retorno='.$_GET['acao'].'&id_relatorio_importacao='.$arrObjEouvRelatorioImportacaoDTO[$i]->getNumIdRelatorioImportacao())).'" tabindex="'.PaginaSEI::getInstance()->getProxTabTabela().'"><img src="'.PaginaSEI::getInstance()->getDiretorioImagensGlobal().'/consultar.gif" title="Detalhar Importa��o" alt="Detalhar Importaca��o" class="infraImg" /></a>&nbsp;';
      }

      $strResultado .= '</td></tr>'."\n";
    }
    $strResultado .= '</table>';
  }

  if ($_GET['acao'] == 'infra_agendamento_tarefa_selecionar'){
    $arrComandos[] = '<button type="button" accesskey="F" id="btnFecharSelecao" value="Fechar" onclick="window.close();" class="infraButton"><span class="infraTeclaAtalho">F</span>echar</button>';
  }else{
    $arrComandos[] = '<button type="button" accesskey="F" id="btnFechar" value="Fechar" onclick="location.href=\''.PaginaSEI::getInstance()->formatarXHTML(SessaoSEI::getInstance()->assinarLink('controlador.php?acao='.PaginaSEI::getInstance()->getAcaoRetorno().'&acao_origem='.$_GET['acao'])).'\'" class="infraButton"><span class="infraTeclaAtalho">F</span>echar</button>';
  }

  $strLinkPesquisar = SessaoSEI::getInstance()->assinarLink('controlador.php?acao=md_cgu_eouv_relatorio_importacao_listar&acao_origem='.$_GET['acao']);

/*}catch(Exception $e){
  PaginaSEI::getInstance()->processarExcecao($e);
} */

PaginaSEI::getInstance()->montarDocType();
PaginaSEI::getInstance()->abrirHtml();
PaginaSEI::getInstance()->abrirHead();
PaginaSEI::getInstance()->montarMeta();
PaginaSEI::getInstance()->montarTitle(':: '.PaginaSEI::getInstance()->getStrNomeSistema().' - '.$strTitulo.' ::');
PaginaSEI::getInstance()->montarStyle();
PaginaSEI::getInstance()->abrirStyle();
?>
#lblHelp {position:absolute;left:0%;top:0%;width:95%;}

#lblStaPeriodicidadeExecucao {position:absolute;left:0%;top:0%;width:25%;}
#selStaPeriodicidadeExecucao {position:absolute;left:0%;top:40%;width:25%;}

tr.trVermelha{
background-color:#f59f9f; 
}

<?
PaginaSEI::getInstance()->fecharStyle();
PaginaSEI::getInstance()->montarJavaScript();
PaginaSEI::getInstance()->abrirJavaScript();
?>

function inicializar(){
  if ('<?=$_GET['acao']?>'=='infra_agendamento_tarefa_selecionar'){
    infraReceberSelecao();
    document.getElementById('btnFecharSelecao').focus();
  }else{
    document.getElementById('btnFechar').focus();
  }
  infraEfeitoTabelas();
}

function pesquisar(){
    document.getElementById('frmEouvRelatorioImportacaoLista').action='<?=$strLinkPesquisar?>';
    document.getElementById('frmEouvRelatorioImportacaoLista').submit();
}

function executarAgendamento(comando, link){
  if (confirm('Confirma execu��o do comando ' + comando + '?')){
    document.getElementById('frmInfraAgendamentoTarefaLista').action=link;
    document.getElementById('frmInfraAgendamentoTarefaLista').submit();
    infraExibirAviso(false);
  }
}

<?
PaginaSEI::getInstance()->fecharJavaScript();
PaginaSEI::getInstance()->fecharHead();
PaginaSEI::getInstance()->abrirBody($strTitulo,'onload="inicializar();"');
?>
<form id="frmEouvRelatorioImportacaoLista" method="post" action="<?=PaginaSEI::getInstance()->formatarXHTML(SessaoSEI::getInstance()->assinarLink('controlador.php?acao='.$_GET['acao'].'&acao_origem='.$_GET['acao']))?>">
  <?
  PaginaSEI::getInstance()->montarBarraComandosSuperior($arrComandos);

    ?>
  <label id="lblTextoPesquisa" class="infraLabel" tabindex="<?=PaginaSEI::getInstance()->getProxTabDados()?>">Pesquisar por NUP:</label>
  <input type="text" name="txtTextoPesquisa" id="txtTextoPesquisa" onkeyup="return tratarEnter(event);" class="infraText" value="<?php echo $_POST['txtTextoPesquisa']; ?>"/>

    <?
  PaginaSEI::getInstance()->montarAreaTabela($strResultado,$numRegistros);
  //PaginaSEI::getInstance()->montarAreaDebug();
  PaginaSEI::getInstance()->montarBarraComandosInferior($arrComandos);
  ?>
</form>
<?
PaginaSEI::getInstance()->fecharBody();
PaginaSEI::getInstance()->fecharHtml();
?>