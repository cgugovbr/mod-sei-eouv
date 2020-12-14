<?
/**
* CONTROLADORIA GERAL DA UNI�O
*
* 18/10/2015 - criado por Rafaele Leandro
*
* Vers�o do Gerador de C�digo: 1.29.1
*
* Vers�o no CVS: $Id$
*/

require_once dirname(__FILE__) . '/../../../../SEI.php';

class MdCguEouvRelatorioImportacaoDTO extends InfraDTO {

  public function getStrNomeTabela() {
  	 return 'md_eouv_rel_import';
  }

  public function montar() {

    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM,
                                   'IdRelatorioImportacao',
                                   'id_md_eouv_rel_import');

    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_DTH,
                                   'DthImportacao',
                                   'dth_importacao');

    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR,
                                   'SinSucesso',
                                   'sin_sucesso');

    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_DTH,
                                  'DthPeriodoInicial',
                                  'dth_periodo_inicial');

    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_DTH,
                                  'DthPeriodoFinal',
                                  'dth_periodo_final');

    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR,
                                  'DeLogProcessamento',
                                  'des_log_processamento');

    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR,
                                  'TipManifestacao',
                                  'tip_manifestacao');

    $this->configurarPK('IdRelatorioImportacao', InfraDTO::$TIPO_PK_NATIVA);
    
  }
}
?>