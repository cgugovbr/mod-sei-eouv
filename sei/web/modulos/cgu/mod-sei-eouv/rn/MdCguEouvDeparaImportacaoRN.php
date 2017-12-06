<?
/**
* TRIBUNAL REGIONAL FEDERAL DA 4� REGI�O
*
* 12/02/2008 - criado por marcio_db
*
* Vers�o do Gerador de C�digo: 1.13.1
*
* Vers�o no CVS: $Id$
*/

require_once dirname(__FILE__).'/../../../../SEI.php';

class MdCguEouvDeparaImportacaoRN extends InfraRN {

  public function __construct(){
    parent::__construct();
  }

  protected function inicializarObjInfraIBanco(){
    return BancoSEI::getInstance();
  }

  protected function cadastrarRN0171Controlado(MdCguEouvDeparaImportacaoDTO $objEouvDeparaImportacaoDTO) {
    try{

      //Valida Permissao
      SessaoSEI::getInstance()->validarAuditarPermissao('md_cgu_eouv_depara_importacao',__METHOD__,$objEouvDeparaImportacaoDTO);

      //Regras de Negocio
      $objInfraException = new InfraException();

      /*$this->validarDblIdProtocoloRN0242($objRelProtocoloAssuntoDTO, $objInfraException);
      $this->validarNumIdAssuntoRN0243($objRelProtocoloAssuntoDTO, $objInfraException);
      $this->validarNumIdUnidadeRN0885($objRelProtocoloAssuntoDTO, $objInfraException);
      $this->validarNumSequenciaRN1176($objRelProtocoloAssuntoDTO, $objInfraException);
      */
      $objInfraException->lancarValidacoes();

      $objEouvDeparaImportacaoBD = new MdEouvDeparaImportacaoBD($this->getObjInfraIBanco());
      $ret = $objEouvDeparaImportacaoBD->cadastrar($objEouvDeparaImportacaoDTO);

      //Auditoria

      return $ret;

    }catch(Exception $e){
      throw new InfraException('Erro cadastrando associa��o entre Tipo de Manifesta��o do E-OUV e Tipo de Procedimento do SEI.',$e);
    }
  }

  protected function alterarRN1177Controlado(RelProtocoloAssuntoDTO $objRelProtocoloAssuntoDTO){
    try {

      //Valida Permissao
  	   SessaoSEI::getInstance()->validarAuditarPermissao('rel_protocolo_assunto_alterar',__METHOD__,$objRelProtocoloAssuntoDTO);

      //Regras de Negocio
      $objInfraException = new InfraException();

      if ($objRelProtocoloAssuntoDTO->isSetNumIdUnidade()){
        $this->validarNumIdUnidadeRN0885($objRelProtocoloAssuntoDTO, $objInfraException);
      }
      
      if ($objRelProtocoloAssuntoDTO->isSetNumSequencia()){
        $this->validarNumSequenciaRN1176($objRelProtocoloAssuntoDTO, $objInfraException);
      }
      
      $objInfraException->lancarValidacoes();

      $objRelProtocoloAssuntoBD = new RelProtocoloAssuntoBD($this->getObjInfraIBanco());
      $objRelProtocoloAssuntoBD->alterar($objRelProtocoloAssuntoDTO);

      //Auditoria

    }catch(Exception $e){
      throw new InfraException('Erro alterando associa��o entre Protocolo e Assunto.',$e);
    }
  }

  protected function excluirRN0224Controlado($arrObjRelProtocoloAssuntoDTO){
    try {

      //Valida Permissao
      SessaoSEI::getInstance()->validarAuditarPermissao('rel_protocolo_assunto_excluir',__METHOD__,$arrObjRelProtocoloAssuntoDTO);

      //Regras de Negocio
      //$objInfraException = new InfraException();

      //$objInfraException->lancarValidacoes();

      $objRelProtocoloAssuntoBD = new RelProtocoloAssuntoBD($this->getObjInfraIBanco());
      for($i=0;$i<count($arrObjRelProtocoloAssuntoDTO);$i++){
        $objRelProtocoloAssuntoBD->excluir($arrObjRelProtocoloAssuntoDTO[$i]);
      }

      //Auditoria

    }catch(Exception $e){
      throw new InfraException('Erro excluindo .',$e);
    }
  }

  /*
  protected function consultarConectado(RelProtocoloAssuntoDTO $objRelProtocoloAssuntoDTO){
    try {

      //Valida Permissao
      SessaoSEI::getInstance()->validarAuditarPermissao('rel_protocolo_assunto_consultar');

      //Regras de Negocio
      //$objInfraException = new InfraException();

      //$objInfraException->lancarValidacoes();

      $objRelProtocoloAssuntoBD = new RelProtocoloAssuntoBD($this->getObjInfraIBanco());
      $ret = $objRelProtocoloAssuntoBD->consultar($objRelProtocoloAssuntoDTO);

      //Auditoria

      return $ret;
    }catch(Exception $e){
      throw new InfraException('Erro consultando associa��o entre Protocolo e Assunto.',$e);
    }
  }
  */
   
  protected function listarRN0188Conectado(RelProtocoloAssuntoDTO $objRelProtocoloAssuntoDTO) {
    try {

      //Valida Permissao
      SessaoSEI::getInstance()->validarAuditarPermissao('rel_protocolo_assunto_listar',__METHOD__,$objRelProtocoloAssuntoDTO);

      //Regras de Negocio
      //$objInfraException = new InfraException();

      //$objInfraException->lancarValidacoes();

      $objRelProtocoloAssuntoBD = new RelProtocoloAssuntoBD($this->getObjInfraIBanco());
      $ret = $objRelProtocoloAssuntoBD->listar($objRelProtocoloAssuntoDTO);

      //Auditoria

      return $ret;

    }catch(Exception $e){
      throw new InfraException('Erro listando associa��es entre Protocolo e Assunto.',$e);
    }
  }

  protected function contarRN0257Conectado(RelProtocoloAssuntoDTO $objRelProtocoloAssuntoDTO){
    try {

      //Valida Permissao
      SessaoSEI::getInstance()->validarAuditarPermissao('rel_protocolo_assunto_listar',__METHOD__,$objRelProtocoloAssuntoDTO);

      //Regras de Negocio
      //$objInfraException = new InfraException();

      //$objInfraException->lancarValidacoes();

      $objRelProtocoloAssuntoBD = new RelProtocoloAssuntoBD($this->getObjInfraIBanco());
      $ret = $objRelProtocoloAssuntoBD->contar($objRelProtocoloAssuntoDTO);

      //Auditoria

      return $ret;
    }catch(Exception $e){
      throw new InfraException('Erro contando associa��es entre Protocolo e Assunto.',$e);
    }
  }

 protected function consultarRN0186Conectado(MdCguEouvDeparaImportacaoDTO $objEouvDeparaImportacaoDTO){
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
    }catch(Exception $e){
      throw new InfraException('Erro consultando DePara Eouv Importa��o.',$e);
    }
  }

  /*protected function desativarControlado($arrObjRelProtocoloAssuntoDTO){
    try {

      //Valida Permissao
      SessaoSEI::getInstance()->validarAuditarPermissao('rel_protocolo_assunto_desativar');

      //Regras de Negocio
      //$objInfraException = new InfraException();

      //$objInfraException->lancarValidacoes();

      $objRelProtocoloAssuntoBD = new RelProtocoloAssuntoBD($this->getObjInfraIBanco());
      for($i=0;$i<count($arrObjRelProtocoloAssuntoDTO);$i++){
        $objRelProtocoloAssuntoBD->desativar($arrObjRelProtocoloAssuntoDTO[$i]);
      }

      //Auditoria

    }catch(Exception $e){
      throw new InfraException('Erro desativando associa��o entre Protocolo e Assunto.',$e);
    }
  }

  protected function reativarControlado($arrObjRelProtocoloAssuntoDTO){
    try {

      //Valida Permissao
      SessaoSEI::getInstance()->validarAuditarPermissao('rel_protocolo_assunto_reativar');

      //Regras de Negocio
      //$objInfraException = new InfraException();

      //$objInfraException->lancarValidacoes();

      $objRelProtocoloAssuntoBD = new RelProtocoloAssuntoBD($this->getObjInfraIBanco());
      for($i=0;$i<count($arrObjRelProtocoloAssuntoDTO);$i++){
        $objRelProtocoloAssuntoBD->reativar($arrObjRelProtocoloAssuntoDTO[$i]);
      }

      //Auditoria

    }catch(Exception $e){
      throw new InfraException('Erro reativando associa��o entre Protocolo e Assunto.',$e);
    }
  }

 */
  private function validarDblIdProtocoloRN0242(RelProtocoloAssuntoDTO $objRelProtocoloAssuntoDTO, InfraException $objInfraException){
    if (InfraString::isBolVazia($objRelProtocoloAssuntoDTO->getDblIdProtocolo())){
      $objInfraException->adicionarValidacao('Protocolo n�o informado na associa��o com Assunto.');
    }
  }

  private function validarNumIdAssuntoRN0243(RelProtocoloAssuntoDTO $objRelProtocoloAssuntoDTO, InfraException $objInfraException){
    if (InfraString::isBolVazia($objRelProtocoloAssuntoDTO->getNumIdAssunto())){
      $objInfraException->adicionarValidacao('Assunto n�o informado na associa��o com Protocolo.');
    }
  }

  private function validarNumIdUnidadeRN0885(RelProtocoloAssuntoDTO $objRelProtocoloAssuntoDTO, InfraException $objInfraException){
    if (InfraString::isBolVazia($objRelProtocoloAssuntoDTO->getNumIdUnidade())){
      $objInfraException->adicionarValidacao('Unidade n�o informada na associa��o entre Protocolo e Assunto.');
    }
  }
  
  private function validarNumSequenciaRN1176(RelProtocoloAssuntoDTO $objRelProtocoloAssuntoDTO, InfraException $objInfraException){
    if (InfraString::isBolVazia($objRelProtocoloAssuntoDTO->getNumSequencia())){
      $objInfraException->adicionarValidacao('Sequ�ncia n�o informada na associa��o entre Protocolo e Assunto.');
    }
  }
  
}
?>