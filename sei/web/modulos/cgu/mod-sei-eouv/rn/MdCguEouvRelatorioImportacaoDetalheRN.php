<?
/**
 * TRIBUNAL REGIONAL FEDERAL DA 4ª REGIÃO
 *
 * 09/10/2009 - criado por mga
 *
 * Versão do Gerador de Código: 1.29.1
 *
 * Versão no CVS: $Id$
 */

class MdCguEouvRelatorioImportacaoDetalheRN extends InfraRN {


  public function __construct(){
    parent::__construct();
  }

  protected function inicializarObjInfraIBanco(){
    return BancoSEI::getInstance();
  }

    protected function cadastrarConectado(MdCguEouvRelatorioImportacaoDetalheDTO $objEouvRelatorioImportacaoDetalheDTO) {
        try{

            //Valida Permissao
            SessaoInfra::getInstance()->validarPermissao('md_cgu_eouv_relatorio_importacao');

            //Regras de Negocio
            $objInfraException = new InfraException();

            $objInfraException->lancarValidacoes();

            $objEouvRelatorioImportacaoDetalheBD = new MdCguEouvRelatorioImportacaoDetalheBD($this->getObjInfraIBanco());
            $ret = $objEouvRelatorioImportacaoDetalheBD->cadastrar($objEouvRelatorioImportacaoDetalheDTO);

            return $ret;

        }catch(Exception $e){
            throw new InfraException('Erro cadastrando EouvRelatorioImportacaoDetalhe.',$e);
        }
    }

    protected function alterarConectado(MdCguEouvRelatorioImportacaoDetalheDTO $objEouvRelatorioImportacaoDetalheDTO) {
        try{

            //Valida Permissao
            SessaoInfra::getInstance()->validarPermissao('md_cgu_eouv_relatorio_importacao');

            //Regras de Negocio
            $objInfraException = new InfraException();

            $objInfraException->lancarValidacoes();

            $objEouvRelatorioImportacaoDetalheBD = new MdCguEouvRelatorioImportacaoDetalheBD($this->getObjInfraIBanco());
            $ret = $objEouvRelatorioImportacaoDetalheBD->alterar($objEouvRelatorioImportacaoDetalheDTO);

            return $ret;

        }catch(Exception $e){
            throw new InfraException('Erro cadastrando EouvRelatorioImportacaoDetalhe.',$e);
        }
    }
    
    protected function listarConectado(MdCguEouvRelatorioImportacaoDetalheDTO $objEouvRelatorioImportacaoDetalheDTO)
    {
        try {

            //Valida Permissao
            SessaoSEI::getInstance()->validarPermissao('md_cgu_eouv_relatorio_importacao_listar');

            $objEouvRelatorioImportacaoDetalheBD = new MdCguEouvRelatorioImportacaoDetalheBD($this->getObjInfraIBanco());
            $ret = $objEouvRelatorioImportacaoDetalheBD->listar($objEouvRelatorioImportacaoDetalheDTO);

            return $ret;

        } catch (Exception $e) {
            throw new InfraException('Erro listando EouvRelatorioImportacaoDetalhe.', $e);
        }
    }

    protected function consultarConectado(MdCguEouvRelatorioImportacaoDetalheDTO $objEouvRelatorioImportacaoDetalheDTO)
    {
        try {

            //Valida Permissao
            SessaoSEI::getInstance()->validarPermissao('md_cgu_eouv_relatorio_importacao_listar');

            $objEouvRelatorioImportacaoDetalheBD = new MdCguEouvRelatorioImportacaoDetalheBD($this->getObjInfraIBanco());
            $ret = $objEouvRelatorioImportacaoDetalheBD->consultar($objEouvRelatorioImportacaoDetalheDTO);

            return $ret;

        } catch (Exception $e) {
            throw new InfraException('Erro Consultando EouvRelatorioImportacaoDetalhe.', $e);
        }
    }

    protected function excluirControlado($arrObjEouvRelatorioImportacaoDetalheDTO){
        try {

            //Valida Permissao
            SessaoSEI::getInstance()->validarAuditarPermissao('md_cgu_eouv_relatorio_importacao_excluir',__METHOD__,$arrObjEouvRelatorioImportacaoDetalheDTO);

            $objMdCguEouvRelatorioImportacaoDetalheBD = new MdCguEouvRelatorioImportacaoDetalheBD($this->getObjInfraIBanco());
            for($i=0;$i<count($arrObjEouvRelatorioImportacaoDetalheDTO);$i++){
                $objMdCguEouvRelatorioImportacaoDetalheBD->excluir($arrObjEouvRelatorioImportacaoDetalheDTO[$i]);
            }

            //Auditoria

        }catch(Exception $e){
            throw new InfraException('Erro excluindo Processos.',$e);
        }
    }
}
?>