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


class MdCguEouvRelatorioImportacaoDetalheDTO extends InfraDTO
{

    public function getStrNomeTabela()
    {
        return 'md_eouv_rel_import_det';
    }

    public function montar()
    {

        $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM,
            'IdRelatorioImportacao',
            'id_md_eouv_rel_import');

        $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR,
            'ProtocoloFormatado',
            'num_protocolo_formatado');

        $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR,
            'SinSucesso',
            'sin_sucesso');

        $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR,
            'TipManifestacao',
            'tip_manifestacao');

        $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR,
            'DescricaoLog',
            'des_log_processamento');

        $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_DTH,
            'DthPrazoAtendimento',
            'dth_prazo_atendimento');

        $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_DTH,
            'DthImportacao',
            'dth_importacao',
            'md_eouv_rel_import');

        $this->configurarPK('IdRelatorioImportacao', InfraDTO::$TIPO_PK_INFORMADO);
        $this->configurarPK('ProtocoloFormatado', InfraDTO::$TIPO_PK_INFORMADO);

        $this->configurarFK('IdRelatorioImportacao', 'md_eouv_rel_import', 'id_md_eouv_rel_import');
    }
}

?>