<?
/**
* CONTROLADORIA-GERAL DA UNIÃO
*/

class MdCguEouvDeparaImportacaoRN extends InfraRN
{
  public static $ID_TIPO_DENUNCIA = 1;
  public static $ID_TIPO_RECLAMACAO = 2;
  public static $ID_TIPO_ELOGIO = 3;
  public static $ID_TIPO_SUGESTAO = 4;
  public static $ID_TIPO_SOLICITACAO = 5;
  public static $ID_TIPO_NAO_CLASSIFICADA = 6;
  public static $ID_TIPO_COMUNICADO = 7;
  public static $ID_TIPO_ACESSO_A_INFORMACAO = 8;
  public static $ID_TIPO_PEDIDO_DE_REVISAO = 80;
  public static $ID_TIPO_RECURSO_1 = 81;
  public static $ID_TIPO_RECURSO_2 = 82;
  public static $ID_TIPO_RECURSO_3 = 83;

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

      $this->validarNumIdTipoManifestacaoEouv($objEouvDeparaImportacaoDTO, $objInfraException);
      $this->validarNumIdTipoProcedimento($objEouvDeparaImportacaoDTO, $objInfraException);
      $this->validarNumIdHipoteseLegal($objEouvDeparaImportacaoDTO, $objInfraException);
      $this->validarNumIdUnidadeDestino($objEouvDeparaImportacaoDTO, $objInfraException);
      
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
    if (is_null($objEouvDeparaImportacaoDTO->getNumIdTipoManifestacaoEouv())){
      $objInfraException->adicionarValidacao('ID do Tipo da Manifestação não informado.');
    }
  }
  
  private function validarNumIdTipoProcedimento(MdCguEouvDeparaImportacaoDTO $objEouvDeparaImportacaoDTO, InfraException $objInfraException){
    if (is_null($objEouvDeparaImportacaoDTO->getNumIdTipoProcedimento())){
      $objInfraException->adicionarValidacao('ID do Tipo de Processo não informado.');
    }
  }

  private function validarNumIdHipoteseLegal(MdCguEouvDeparaImportacaoDTO $objEouvDeparaImportacaoDTO, InfraException $objInfraException){
    if (is_null($objEouvDeparaImportacaoDTO->getNumIdHipoteseLegal())){
      $objInfraException->adicionarValidacao('ID de Hipótese Legal não informado.');
    }
  }

  private function validarNumIdUnidadeDestino(MdCguEouvDeparaImportacaoDTO $objEouvDeparaImportacaoDTO, InfraException $objInfraException){
    if (is_null($objEouvDeparaImportacaoDTO->getNumIdUnidadeDestino())){
      $objInfraException->adicionarValidacao('ID da Unidade de Destino não informado.');
    }
  }
  
}
?>