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

    public function consultarHash($hash, $numProtocoloFormatado) {
        try {
            $sql_protocolo = "SELECT id_protocolo FROM protocolo WHERE protocolo_formatado = '" . $numProtocoloFormatado . "';";
            $protocolo = $this->getObjInfraIBanco()->consultarSql($sql_protocolo);

            if (count($protocolo) > 0) {
                $id_protocolo = $protocolo[0]['id_protocolo'];
            } else {
                $id_protocolo = 0;
            }

            $sql = "SELECT hash FROM anexo WHERE hash = '" . $hash . "' AND id_protocolo = " . $id_protocolo . ";";

            return $this->getObjInfraIBanco()->consultarSql($sql);
        }catch(Exception $e){
            throw new InfraException("Erro verificando hash de arquivo.",$e);
        }
    }
}
?>
