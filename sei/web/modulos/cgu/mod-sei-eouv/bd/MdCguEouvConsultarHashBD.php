<?
/**
 * CONTROLADORIA GERAL DA UNIÃO
 *
 * 08/12/2020 - criado por Evert Ramos <evert.ramos@cgu.gov.br>
 */

require_once dirname(__FILE__) . '/../../../../SEI.php';

class MdCguEouvConsultarHashBD extends InfraBD {

    public function __construct(InfraIBanco $objInfraIBanco)
    {
        parent::__construct($objInfraIBanco);
    }

    public function consultarHash($hash) {
        try {
            $sql = "SELECT hash FROM anexo WHERE hash = '" . $hash . "';";

            return $this->getObjInfraIBanco()->consultarSql($sql);
        }catch(Exception $e){
            throw new InfraException("Erro verificando hash de arquivo.",$e);
        }
    }
}
?>
