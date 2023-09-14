<?
/*
 * CONTROLADORIA GERAL DA UNIУO - CGU
 *
 * 23/06/2015 - criado por Rafael Leandro Ferreira
 *
 *
 *Este WebService tem o objetivo de atender a necessidade da CGU que nуo estс suportada dentro dos mщtodos
 *existentes em SeiWS.php.
 *Foi criado este arquivo para nуo fazer alteraчѕes neste arquivo. O ideal щ que posteriormente estes mщtodos sejam incorporados
 *ao SeiWS para estar disponэvel como um mщtodo homologado pelo SEI.
 */



require_once dirname(__FILE__) . '/../../../../SEI.php';

error_reporting(E_ALL); ini_set('display_errors', '1');

class MdCguEouvWS extends InfraWS {

    public function getObjInfraLog(){
        return LogSEI::getInstance();
    }

    /**
     * @param $objWS
     * @param $usuarioWebService
     * @param $senhaUsuarioWebService
     * @param $ultimaDataExecucao
     * @param $dataAtual
     * @return mixed
     * @throws Exception
     */
    public static function apiRestRequest($url, $token, $tipo)
    {
        $curl = curl_init();

        /**
         * @test - teste de opчѕes do Curl
         */

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "UTF-8",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_SSLVERSION => 6,
            CURLOPT_HTTPHEADER => array(
                "Accept: */*",
                "Authorization: Bearer " . $token,
                "Cache-Control: no-cache"
            ),
        ));

        $response = curl_exec($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        // Verifica erro ao fazer requisiчуo
        if ($response === false) {
            $err = curl_error($curl);
            throw new Exception($err);
        }
        curl_close($curl);

        switch ($httpcode) {
            case 200:
                $response = json_decode($response, true);
                // Verifica erro na decodificaчуo JSON
                if ($response === null) {
                    throw new Exception('Erro ao decodificar resposta JSON da API ('. json_last_error_msg(). ')');
                }
                $response = self::decode_result($response);
                break;
            case 401:
                $response = 'Token Invalidado. HTTP Status: ' . $httpcode;
                break;
            case 404: // Nenhum retorno encontrado...
                $response = 'Nenhum retorno encontrado! HTTP Status: ' . $httpcode;
                break;
            default:
                $response = "Erro: Ocorreu algum erro nуo tratado. HTTP Status: " . $httpcode;
                throw new Exception($response);
                break;
        }

        return $response;
    }

    static function decode_result($array)
    {
        foreach($array as $key => $value) {
            if(is_array($value)) {
                $array[$key] = self::decode_result($value);
            } else {
                //$array[$key] = mb_convert_encoding($value, 'Windows-1252', 'UTF-8');
                $array[$key] = utf8_decode($value);
            }
        }

        return $array;
    }

    public static function apiValidarToken($url, $username, $password, $client_id, $client_secret)
    {
        //get Url Ambiente
        $url = parse_url($url);
        $urlAmbiente = $url['scheme'] . '://' . $url['host'] . '/oauth/token';

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $urlAmbiente,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "UTF-8",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_SSLVERSION => 6,
            CURLOPT_POSTFIELDS => "client_id=".$client_id."&client_secret=".$client_secret."&grant_type=password&username=".$username."&password=".$password."&undefined=",
            //CURLOPT_POSTFIELDS => "client_id=15&client_secret=rwkp6899&grant_type=password&username=wsIntegracaoSEI&password=teste1235&undefined=",
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json",
                "cache-control: no-cache"
            ),
        ));

        $response = curl_exec($curl);

        $err = curl_error($curl);

        curl_close($curl);

        $response = json_decode($response, true);

        return $response;

    }
    public static function verificaRetornoWS($retornoWsLista)
    {
        /*
        funчуo criada para tratar o retorno de dados do WS, pois quando existe apenas um unico resultado retorna um objeto e
        quando tem mais de um resultado retorna um array ocasionando falhas na exibiчуo dos dados.
        */
        if (isset($retornoWsLista) and key_exists(0, $retornoWsLista)) {
            $resultado = $retornoWsLista;
        } else {
            $resultado = array ( $retornoWsLista );
        }
        return $resultado;
    }

}

?>