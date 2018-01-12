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

class MdCguEouvDeparaImportacaoDTO extends InfraDTO {

  public function getStrNomeTabela() {
  	 return 'md_eouv_depara_importacao';
  }

  public function montar() {

  	 $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM,
                                   'IdTipoManifestacaoEouv',
                                   'id_tipo_manifestacao_eouv');

  	 $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM,
                                   'IdTipoProcedimento',
                                   'id_tipo_procedimento');
                                   
  	 $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR,
                                   'DeTipoManifestacaoEouv',
                                   'de_tipo_manifestacao_eouv');

  	 $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_STR,
                                              'TipoProcedimento',
                                              'descricao',
                                              'tipo_procedimento');

    $this->configurarPK('IdTipoManifestacaoEouv',InfraDTO::$TIPO_PK_INFORMADO);
    $this->configurarPK('IdTipoProcedimento',InfraDTO::$TIPO_PK_INFORMADO);

    $this->configurarFK('IdTipoProcedimento', 'tipo_procedimento', 'id_tipo_procedimento');

  }
}
?>