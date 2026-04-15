<?
/**
 * CONTROLADORIA GERAL DA UNIÃO
 *
 * 18/10/2015 - criado por Rafaele Leandro
 *
 * Versão do Gerador de Código: 1.29.1
 *
 * Versão no CVS: $Id$
 */

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

    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM,
                                  'IdHipoteseLegal',
                                  'id_hipotese_legal');

    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM,
                                  'IdUnidadeDestino',
                                  'id_unidade_destino');

    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR,
                                  'SinAtivo',
                                  'sin_ativo');

    $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_STR,
                                              'TipoProcedimento',
                                              'nome',
                                              'tipo_procedimento');

    $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_STR,
                                              'HipoteseLegal',
                                              'nome',
                                              'hipotese_legal');

    $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_STR,
                                              'SiglaUnidadeDestino',
                                              'sigla',
                                              'unidade');

    $this->configurarPK('IdTipoManifestacaoEouv',InfraDTO::$TIPO_PK_INFORMADO);

    $this->configurarFK('IdTipoProcedimento', 'tipo_procedimento', 'id_tipo_procedimento', InfraDTO::$TIPO_FK_OPCIONAL);
    $this->configurarFK('IdHipoteseLegal', 'hipotese_legal', 'id_hipotese_legal', InfraDTO::$TIPO_FK_OPCIONAL);
    $this->configurarFK('IdUnidadeDestino', 'unidade', 'id_unidade', InfraDTO::$TIPO_FK_OPCIONAL);

    $this->configurarExclusaoLogica('SinAtivo', 'N');

  }
}
?>