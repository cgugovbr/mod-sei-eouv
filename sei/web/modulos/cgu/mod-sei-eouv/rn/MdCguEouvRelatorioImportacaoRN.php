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

class MdCguEouvRelatorioImportacaoRN extends InfraRN
{


    public function __construct()
    {
        parent::__construct();
    }

    protected function inicializarObjInfraIBanco()
    {
        return BancoSEI::getInstance();
    }

    protected function cadastrarConectado(MdCguEouvRelatorioImportacaoDTO $objEouvRelatorioImportacaoDTO)
    {
        try {


            //Valida Permissao
            SessaoInfra::getInstance()->validarPermissao('md_cgu_eouv_relatorio_importacao');

            //Regras de Negocio
            $objInfraException = new InfraException();

            $objInfraException->lancarValidacoes();

            $objEouvRelatorioImportacaoBD = new MdCguEouvRelatorioImportacaoBD($this->getObjInfraIBanco());
            $ret = $objEouvRelatorioImportacaoBD->cadastrar($objEouvRelatorioImportacaoDTO);

            return $ret;

        } catch (Exception $e) {
            throw new InfraException('Erro cadastrando EouvRelatorioImportacao.', $e);
        }
    }

    protected function alterarConectado(MdCguEouvRelatorioImportacaoDTO $objEouvRelatorioImportacaoDTO){
        try {

            //Valida Permissao
            SessaoInfra::getInstance()->validarPermissao('md_cgu_eouv_relatorio_importacao');

            //Regras de Negocio
            $objInfraException = new InfraException();

            $objInfraException->lancarValidacoes();

            $objEouvRelatorioImportacaoBD = new MdCguEouvRelatorioImportacaoBD($this->getObjInfraIBanco());
            $objEouvRelatorioImportacaoBD->alterar($objEouvRelatorioImportacaoDTO);

        }catch(Exception $e){
            throw new InfraException('Erro alterando EouvRelatorioImportacao.',$e);
        }
    }

    protected function listarConectado(MdCguEouvRelatorioImportacaoDTO $objEouvRelatorioImportacaoDTO)
    {
        try {

            //Valida Permissao
            SessaoSEI::getInstance()->validarPermissao('md_cgu_eouv_relatorio_importacao_listar');

            $objEouvRelatorioImportacaoBD = new MdCguEouvRelatorioImportacaoBD($this->getObjInfraIBanco());
            $ret = $objEouvRelatorioImportacaoBD->listar($objEouvRelatorioImportacaoDTO);

            return $ret;

        } catch (Exception $e) {
            throw new InfraException('Erro listando EouvRelatorioImportacao.', $e);
        }
    }

    protected function consultarConectado(MdCguEouvRelatorioImportacaoDTO $objEouvRelatorioImportacaoDTO)
    {
        try {
            //Valida Permissao
            SessaoSEI::getInstance()->validarPermissao('md_cgu_eouv_relatorio_importacao_listar');

            $objEouvRelatorioImportacaoBD = new MdCguEouvRelatorioImportacaoBD($this->getObjInfraIBanco());
            $ret = $objEouvRelatorioImportacaoBD->consultar($objEouvRelatorioImportacaoDTO);

            return $ret;

        } catch (Exception $e) {
            throw new InfraException('Erro consultando EouvRelatorioImportacao.', $e);
        }
    }

}

?>