<?
/**
 * TRIBUNAL REGIONAL FEDERAL DA 4ª REGIÃO
 *
 * 20/12/2007 - criado por mga
 *
 * Versão do Gerador de Código: 1.12.0
 *
 * Versão no CVS: $Id$
 */

class MdCguEouvAgendamentoINT extends InfraINT
{
    public static function retornarUltimaExecucaoSucesso()
    {

        $objEouvRelatorioImportacaoDTO=new MdCguEouvRelatorioImportacaoDTO();
        $objEouvRelatorioImportacaoDTO->retDthDthImportacao();
        $objEouvRelatorioImportacaoDTO->retDthDthPeriodoInicial();
        $objEouvRelatorioImportacaoDTO->retDthDthPeriodoFinal();
        $objEouvRelatorioImportacaoDTO->retNumIdRelatorioImportacao();
        $objEouvRelatorioImportacaoDTO->setStrSinSucesso('S');
        $objEouvRelatorioImportacaoDTO->setOrdDthDthImportacao(InfraDTO::$TIPO_ORDENACAO_DESC);
        $objEouvRelatorioImportacaoDTO->setNumMaxRegistrosRetorno(1);

        $objEouvRelatorioImportacaoRN = new MdCguEouvRelatorioImportacaoRN();

        try{
            $resultadoObjEouvRelatorioImportacaoDTO = $objEouvRelatorioImportacaoRN->consultar($objEouvRelatorioImportacaoDTO);
        } catch(Exception $e) {
            throw new InfraException('Erro obtendo última execução da Importacao SEI x EOuv ocorrida com Sucesso.',$e);
        }

        LogSEI::getInstance()->gravar('Última Execução com Sucesso:' . $resultadoObjEouvRelatorioImportacaoDTO);

        return $resultadoObjEouvRelatorioImportacaoDTO;
    }

    public static function retornarManifestacoesNaoImportadasPorProblema($idUltimaExecucao, $tipManifestacao = 'P'){

       $objEouvRelatorioImportacaoDetalheDTO=new MdCguEouvRelatorioImportacaoDetalheDTO();
       $objEouvRelatorioImportacaoDetalheDTO->retStrProtocoloFormatado();
       $objEouvRelatorioImportacaoDetalheDTO->setStrSinSucesso('N');
       $objEouvRelatorioImportacaoDetalheDTO->setStrTipManifestacao($tipManifestacao);
       $objEouvRelatorioImportacaoDetalheDTO->setNumIdRelatorioImportacao($idUltimaExecucao);

       $objEouvRelatorioImportacaoDetalheRN = new EouvRelatorioImportacaoDetalheRN();

       $arrObjEouvRelatorioImportacaoDetalheDTO = $objEouvRelatorioImportacaoDetalheRN->listar($objEouvRelatorioImportacaoDetalheDTO);

       return $arrObjEouvRelatorioImportacaoDetalheDTO;
   }

    public static function retornarUltimaDataPrazoAtendimento($protocoloFormatado, $tipManifestacao = false, $skipOrdenacaoPrazoAtencimento = true)
    {
        $objEouvRelatorioImportacaoDetalheDTO = new MdCguEouvRelatorioImportacaoDetalheDTO();
        $objEouvRelatorioImportacaoDetalheDTO->retStrProtocoloFormatado();
        $objEouvRelatorioImportacaoDetalheDTO->retDthDthPrazoAtendimento();
        $objEouvRelatorioImportacaoDetalheDTO->setStrSinSucesso('S');
        if ($tipManifestacao) {
            $objEouvRelatorioImportacaoDetalheDTO->setStrTipManifestacao($tipManifestacao);
        }
        if ($skipOrdenacaoPrazoAtencimento) {
            $objEouvRelatorioImportacaoDetalheDTO->setOrdDthDthPrazoAtendimento(InfraDTO::$TIPO_ORDENACAO_DESC);
        }
        $objEouvRelatorioImportacaoDetalheDTO->setOrdNumIdRelatorioImportacao(InfraDTO::$TIPO_ORDENACAO_DESC);
        $objEouvRelatorioImportacaoDetalheDTO->setNumMaxRegistrosRetorno(1);
        $objEouvRelatorioImportacaoDetalheDTO->setStrProtocoloFormatado($protocoloFormatado);

        $objEouvRelatorioImportacaoDetalheRN = new MdCguEouvRelatorioImportacaoDetalheRN();

        $resultadoObjEouvRelatorioImportacaoDetalheDTO = $objEouvRelatorioImportacaoDetalheRN->consultar($objEouvRelatorioImportacaoDetalheDTO);

        return $resultadoObjEouvRelatorioImportacaoDetalheDTO;
    }

    public static function retornarTipoManifestacao($idRelatorioImportacao, $protocoloFormatado)
    {
        $objEouvRelatorioImportacaoDetalheDTO = new MdCguEouvRelatorioImportacaoDetalheDTO();
        $objEouvRelatorioImportacaoDetalheDTO->retStrProtocoloFormatado();
        $objEouvRelatorioImportacaoDetalheDTO->retStrTipManifestacao();
        $objEouvRelatorioImportacaoDetalheDTO->setNumIdRelatorioImportacao($idRelatorioImportacao);
        $objEouvRelatorioImportacaoDetalheDTO->setStrProtocoloFormatado($protocoloFormatado);
        $objEouvRelatorioImportacaoDetalheDTO->setStrSinSucesso('S');
        $objEouvRelatorioImportacaoDetalheDTO->setNumMaxRegistrosRetorno(1);

        $objEouvRelatorioImportacaoDetalheRN = new MdCguEouvRelatorioImportacaoDetalheRN();

        $resultadoObjEouvRelatorioImportacaoDetalheDTO = $objEouvRelatorioImportacaoDetalheRN->consultar($objEouvRelatorioImportacaoDetalheDTO);

        return $resultadoObjEouvRelatorioImportacaoDetalheDTO;
    }

    public static function retornarUltimoTipoManifestacao($protocoloFormatado)
    {
        $objEouvRelatorioImportacaoDetalheDTO = new MdCguEouvRelatorioImportacaoDetalheDTO();
        $objEouvRelatorioImportacaoDetalheDTO->retStrProtocoloFormatado();
        $objEouvRelatorioImportacaoDetalheDTO->retStrTipManifestacao();
        $objEouvRelatorioImportacaoDetalheDTO->setStrProtocoloFormatado($protocoloFormatado);
        $objEouvRelatorioImportacaoDetalheDTO->setStrSinSucesso('S');
        $objEouvRelatorioImportacaoDetalheDTO->setOrdNumIdRelatorioImportacao(InfraDTO::$TIPO_ORDENACAO_DESC);
        $objEouvRelatorioImportacaoDetalheDTO->setNumMaxRegistrosRetorno(1);

        $objEouvRelatorioImportacaoDetalheRN = new MdCguEouvRelatorioImportacaoDetalheRN();

        $resultadoObjEouvRelatorioImportacaoDetalheDTO = $objEouvRelatorioImportacaoDetalheRN->consultar($objEouvRelatorioImportacaoDetalheDTO);

        if ($resultadoObjEouvRelatorioImportacaoDetalheDTO == null) {
            return null;
        } else {
            return $resultadoObjEouvRelatorioImportacaoDetalheDTO->getStrTipManifestacao();
        }
    }
}
?>