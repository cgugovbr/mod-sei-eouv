<?
/**
* CONTROLADORIA-GERAL DA UNIÃO
*/

class MdCguEouvDeparaImportacaoRN extends InfraRN
{

  public function __construct()
  {
    parent::__construct();
  }

  protected function inicializarObjInfraIBanco()
  {
    return BancoSEI::getInstance();
  }

  protected function cadastrarControlado(MdCguEouvDeparaImportacaoDTO $objEouvDeparaImportacaoDTO)
  {
    try {
      //Valida Permissao
      SessaoSEI::getInstance()->validarAuditarPermissao('md_cgu_eouv_depara_importacao_cadastrar',__METHOD__,$objEouvDeparaImportacaoDTO);

      //Regras de Negocio
      $objInfraException = new InfraException();

      if ($objEouvDeparaImportacaoDTO->isSetNumIdTipoManifestacaoEouv()){
        $this->validarNumIdTipoManifestacaoEouv($objEouvDeparaImportacaoDTO, $objInfraException);
      }

      $objInfraException->lancarValidacoes();

      $objEouvDeparaImportacaoBD = new MdEouvDeparaImportacaoBD($this->getObjInfraIBanco());
      $objEouvDeparaImportacaoBD->cadastrar($objEouvDeparaImportacaoDTO);

      //Auditoria

    } catch (Exception $e) {
      throw new InfraException('Erro cadastrando DePara Eouv Importação.',$e);
    }
  }

  protected function alterarControlado(MdCguEouvDeparaImportacaoDTO $objEouvDeparaImportacaoDTO)
  {
    try {
      //Valida Permissao
      SessaoSEI::getInstance()->validarAuditarPermissao('md_cgu_eouv_depara_importacao_alterar',__METHOD__,$objEouvDeparaImportacaoDTO);

      //Regras de Negocio
      $objInfraException = new InfraException();

      if ($objEouvDeparaImportacaoDTO->isSetNumIdTipoManifestacaoEouv()){
        $this->validarNumIdTipoManifestacaoEouv($objEouvDeparaImportacaoDTO, $objInfraException);
      }
      
      if ($objEouvDeparaImportacaoDTO->isSetNumIdTipoProcedimento()){
        $this->validarNumIdTipoProcedimento($objEouvDeparaImportacaoDTO, $objInfraException);
      }
      
      $objInfraException->lancarValidacoes();

      $objEouvDeparaImportacaoBD = new MdEouvDeparaImportacaoBD($this->getObjInfraIBanco());
      $objEouvDeparaImportacaoBD->alterar($objEouvDeparaImportacaoDTO);

      //Auditoria

    } catch (Exception $e) {
      throw new InfraException('Erro alterando DePara Eouv Importação.',$e);
    }
  }

  protected function listarConectado(MdCguEouvDeparaImportacaoDTO $objEouvDeparaImportacaoDTO)
  {
    try {
      //Valida Permissao
      SessaoSEI::getInstance()->validarAuditarPermissao('md_cgu_eouv_depara_importacao_listar',__METHOD__,$objEouvDeparaImportacaoDTO);

      $objEouvDeparaImportacaoBD = new MdEouvDeparaImportacaoBD($this->getObjInfraIBanco());
      $ret = $objEouvDeparaImportacaoBD->listar($objEouvDeparaImportacaoDTO);

      return $ret;

    } catch (Exception $e) {
      throw new InfraException('Erro listando DePara Eouv Importação.',$e);
    }
  }

  protected function consultarConectado(MdCguEouvDeparaImportacaoDTO $objEouvDeparaImportacaoDTO)
  {
    try {

      //Valida Permissao
      SessaoSEI::getInstance()->validarPermissao('md_cgu_eouv_depara_importacao_consultar');

      $objEouvDeparaImportacaoBD = new MdEouvDeparaImportacaoBD($this->getObjInfraIBanco());
      $ret = $objEouvDeparaImportacaoBD->consultar($objEouvDeparaImportacaoDTO);

      //Auditoria

      return $ret;
    } catch (Exception $e) {
      throw new InfraException('Erro consultando DePara Eouv Importação.',$e);
    }
  }

  protected function desativarControlado($arrObjEouvDeparaImportacaoDTO){
        try {
            //Valida Permissao
            SessaoSEI::getInstance()->validarPermissao('md_cgu_eouv_depara_importacao_desativar');

            $objEouvDeparaImportacaoBD = new MdEouvDeparaImportacaoBD($this->getObjInfraIBanco());
            for($i=0;$i<count($arrObjEouvDeparaImportacaoDTO);$i++){
                $objEouvDeparaImportacaoBD->desativar($arrObjEouvDeparaImportacaoDTO[$i]);
            }

            //Auditoria

        }catch(Exception $e){
            throw new InfraException('Erro desativando DePara Eouv Importação.',$e);
        }
    }

  protected function reativarControlado($arrObjEouvDeparaImportacaoDTO){
        try {

            //Valida Permissao
            SessaoSEI::getInstance()->validarPermissao('md_cgu_eouv_depara_importacao_reativar');

            $objEouvDeparaImportacaoBD = new MdEouvDeparaImportacaoBD($this->getObjInfraIBanco());
            for($i=0;$i<count($arrObjEouvDeparaImportacaoDTO);$i++){
                $objEouvDeparaImportacaoBD->reativar($arrObjEouvDeparaImportacaoDTO[$i]);
            }

            //Auditoria

        }catch(Exception $e){
            throw new InfraException('Erro reativando DePara Eouv Importação.',$e);
        }
    }

  private function validarNumIdTipoManifestacaoEouv(MdCguEouvDeparaImportacaoDTO $objEouvDeparaImportacaoDTO, InfraException $objInfraException){
    if (InfraString::isBolVazia($objEouvDeparaImportacaoDTO->getNumIdTipoManifestacaoEouv())){
      $objInfraException->adicionarValidacao('ID do Tipo da Manifestação não informado.');
    }
  }
  
  private function validarNumIdTipoProcedimento(MdCguEouvDeparaImportacaoDTO $objEouvDeparaImportacaoDTO, InfraException $objInfraException){
    if (InfraString::isBolVazia($objEouvDeparaImportacaoDTO->getNumIdTipoProcedimento())){
      $objInfraException->adicionarValidacao('ID do Tipo de Processo não informado.');
    }
  }
  
}
?>