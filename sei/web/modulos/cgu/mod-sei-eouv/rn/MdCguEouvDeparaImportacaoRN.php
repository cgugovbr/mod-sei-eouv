<?
/**
* CONTROLADORIA-GERAL DA UNIУO
*/

require_once dirname(__FILE__).'/../../../../SEI.php';

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
      throw new InfraException('Erro cadastrando DePara Eouv Importaчуo.',$e);
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
      throw new InfraException('Erro alterando DePara Eouv Importaчуo.',$e);
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
      throw new InfraException('Erro listando DePara Eouv Importaчуo.',$e);
    }
  }

  protected function consultarConectado(MdCguEouvDeparaImportacaoDTO $objEouvDeparaImportacaoDTO)
  {
    try {

      //Valida Permissao
      SessaoSEI::getInstance()->validarPermissao('md_cgu_eouv_depara_importacao_consultar');

      //Regras de Negocio
      //$objInfraException = new InfraException();

      //$objInfraException->lancarValidacoes();

      $objEouvDeparaImportacaoBD = new MdEouvDeparaImportacaoBD($this->getObjInfraIBanco());
      $ret = $objEouvDeparaImportacaoBD->consultar($objEouvDeparaImportacaoDTO);

      //Auditoria

      return $ret;
    } catch (Exception $e) {
      throw new InfraException('Erro consultando DePara Eouv Importaчуo.',$e);
    }
  }

  private function validarNumIdTipoManifestacaoEouv(MdCguEouvDeparaImportacaoDTO $objEouvDeparaImportacaoDTO, InfraException $objInfraException){
    if (InfraString::isBolVazia($objEouvDeparaImportacaoDTO->getNumIdTipoManifestacaoEouv())){
      $objInfraException->adicionarValidacao('ID do Tipo da Manifestaчуo nуo informado.');
    }
  }
  
  private function validarNumIdTipoProcedimento(MdCguEouvDeparaImportacaoDTO $objEouvDeparaImportacaoDTO, InfraException $objInfraException){
    if (InfraString::isBolVazia($objEouvDeparaImportacaoDTO->getNumIdTipoProcedimento())){
      $objInfraException->adicionarValidacao('ID do Tipo de Processo nуo informado.');
    }
  }
  
}
?>