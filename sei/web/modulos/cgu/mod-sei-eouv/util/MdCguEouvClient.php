<?
/*
 * CONTROLADORIA-GERAL DA UNIÃO - CGU
 *
 * Este arquivo implementa um cliente de comunicação com a API do FalaBR
 */

require_once __DIR__ . '/../rn/MdCguEouvParametroRN.php';
require_once __DIR__ . '/../dto/MdCguEouvParametroDTO.php';

class MdCguEouvClient {
    private $url; // URL do FalaBR

    // Parâmetros de autenticação na API
    private $usuario;
    private $senha;
    private $clientID;
    private $secret;
    private $token;

    public function __construct()
    {
        $this->carregaParametros();
    }

    /**
     * Retorna os parâmetros utilizados pelo cliente
     * @return array
     */
    public function parametros()
    {
        return [
            'url' => $this->url,
            'usuario' => $this->usuario,
            'senha' => $this->senha,
            'clientID' => $this->clientID,
            'secret' => $this->secret,
            'token' => $this->token,
        ];
    }

    /**
     * Carrega os parâmetros salvos no banco de dados para acesso à API
     * do FalaBR
     * @return void
     */
    public function carregaParametros()
    {
        $objEouvParametroDTO = new MdCguEouvParametroDTO();
        $objEouvParametroDTO->retTodos();

        $objEouvParametroRN = new MdCguEouvParametroRN();
        $arrObjEouvParametroDTO = $objEouvParametroRN->listarParametro($objEouvParametroDTO);

        foreach($arrObjEouvParametroDTO as $objEouvParametroDTO) {
            $parametro = $objEouvParametroDTO->getStrNoParametro();
            $valor = $this->converteStrSeiApi($objEouvParametroDTO->getStrDeValorParametro());

            switch ($parametro) {
                case 'EOUV_URL_WEBSERVICE_IMPORTACAO_MANIFESTACAO':
                    $this->url = $valor;
                    break;
                case 'EOUV_USUARIO_ACESSO_WEBSERVICE':
                    $this->usuario = $valor;
                    break;
                case 'EOUV_SENHA_ACESSO_WEBSERVICE':
                    $this->senha = $valor;
                    break;
                case 'CLIENT_ID':
                    $this->clientID = $valor;
                    break;
                case 'CLIENT_SECRET':
                    $this->secret = $valor;
                    break;
                case 'TOKEN':
                    $this->token = $valor;
                    break;
            }
        }
    }

    /**
     * Consulta manifestações cadastradas em um intervalo de datas
     * @param string $inicio Data no formato DD/MM/YYYY representando o início do intervalo
     * @param string $fim Data no formato DD/MM/YYYY representando o fim do intervalo
     * @return array Lista de estruturas DadosBasicosManifestacaoDTO
     * (https://falabr.cgu.gov.br/Help/ResourceModel?modelName=DadosBasicosManifestacaoDTO)
     */
    public function consultaManifestacoesNoIntervalo($inicio, $fim)
    {
        $params = [
            'DataCadastroInicio' => $inicio,
            'DataCadastroFim' => $fim,
        ];

        try {
            return $this->apiRestRequest('/api/manifestacoes', $params);
        } catch (MdCguEouvExceptionHttpNotFound $e) {
            return [];
        }
    }

    /**
     * Consulta manifestação por protocolo específico
     * @param string $protocolo NUP sem formatação (apenas dígitos)
     * @return array|null Estrutura DadosBasicosManifestacaoDTO
     * (https://falabr.cgu.gov.br/Help/ResourceModel?modelName=DadosBasicosManifestacaoDTO)
     * ou null caso a manifestação não seja encontrada 
     */
    public function consultaManifestacao($protocolo)
    {
        $params = [
            'NumProtocolo' => $protocolo,
        ];

        try {
            $resposta = $this->apiRestRequest('/api/manifestacoes', $params);
            if (count($resposta) == 0) {
                return null;
            } else {
                return $resposta[0];
            }
        } catch (MdCguEouvExceptionHttpNotFound $e) {
            return null;
        }
    }

    /**
     * Consulta detalhada da manifestação
     * @param array $manifestacao Estrutura DadosBasicosManifestacaoDTO
     * retornada pela API (https://falabr.cgu.gov.br/Help/ResourceModel?modelName=DadosBasicosManifestacaoDTO)
     * @return array Estrutura ManifestacaoDTO
     * (https://falabr.cgu.gov.br/Help/ResourceModel?modelName=ManifestacaoDTO)
     */
    public function consultaDetalhadaManifestacao($manifestacao)
    {
        return $this->apiRestRequest($manifestacao['Links'][0]['href']);
    }

    /**
     * Consulta recursos abertos em um intervalo de data
     * @param string $inicio Data no formato DD/MM/YYYY representando o início do intervalo
     * @param string $fim Data no formato DD/MM/YYYY representando o fim do intervalo
     * @return array Lista de estruturas RecursoDTO
     * (https://falabr.cgu.gov.br/Help/ResourceModel?modelName=RecursoDTO)
     */
    public function consultaRecursosNoIntervalo($inicio, $fim)
    {
        $params = [
            'DataAberturaInicio' => $inicio,
            'DataAberturaFim' => $fim,
        ];

        try {
            $resposta = $this->apiRestRequest('/api/recursos', $params);
            return $resposta['Recursos'];
        } catch (MdCguEouvExceptionHttpNotFound $e) {
            return [];
        }
    }

    /**
     * Consulta recursos relacionados a uma manifestação
     * @param string $protocolo NUP sem formatação (apenas dígitos)
     * @return array Lista de estruturas RecursoDTO
     * (https://falabr.cgu.gov.br/Help/ResourceModel?modelName=RecursoDTO)
     * ou array vazio caso a manifestação não tenha recursos.
     */
    public function consultaRecursosDaManifestacao($protocolo)
    {
        $params = [
            'NumProtocolo' => $protocolo,
        ];

        try {
            $resposta = $this->apiRestRequest('/api/recursos', $params);
            return $resposta['Recursos'];
        } catch (MdCguEouvExceptionHttpNotFound $e) {
            return [];
        }
    }

    /**
     * Download de anexo
     * @param array $anexo Estrutura DadosBasicosAnexoDTO
     * (https://falabr.cgu.gov.br/Help/ResourceModel?modelName=DadosBasicosAnexoDTO)
     * ou DadosBasicosAnexoRecursoDTO
     * (https://falabr.cgu.gov.br/Help/ResourceModel?modelName=DadosBasicosAnexoRecursoDTO)
     * @param string $caminhoDestino Caminho no sistema de arquivos onde o anexo será salvo
     * @return void
     */
    public function downloadAnexo($anexo, $caminhoDestino)
    {
        //Busca o conteúdo do Anexo
        $resposta = $this->apiRestRequest($anexo['Links'][0]['href']);
        $conteudoCodificado = $resposta['ConteudoZipadoEBase64'];

        // Decodifica de base64 e salva o arquivo comprimido como gzip
        $caminhoGz = $caminhoDestino.'.gz';
        $gzf = fopen($caminhoGz, 'wb');
        for ($i = 0; $i < ceil(strlen($conteudoCodificado) / 256); $i++) {
            fwrite($gzf, base64_decode(substr($conteudoCodificado, $i * 256, 256)));
        }
        fclose($gzf);

        // Descomprime o arquivo gzip
        $fp = fopen($caminhoDestino, 'wb');
        $gzf = gzopen($caminhoGz, 'rb');
        while (!gzeof($gzf)) {
            fwrite($fp, gzread($gzf, 4096));
        }
        gzclose($gzf);
        unlink($caminhoGz);
        fclose($fp);
    }

    /**
     * Faz uma requisição GET a um endpoint da API
     * @param string $endpoint Entpoint a ser chamado. Pode ser a URL completa
     * (Ex: https://falabr.cgu.gov.br/api/manifestacoes) ou apenas o caminho sem
     * o nome de domínio (Ex: /api/manifestacoes)
     * @param array $parametros Array associativo com os parâmetros a serem passados
     * como query string na requisição
     * @param integer $tentativa uso interno apenas
     * @return array Array associativo resultado da decodificação da resposta do JSON
     * retornado pela API
     */
    private function apiRestRequest($endpoint, $parametros = [], $tentativa = 1)
    {
        $curl = curl_init();

        // Verifica se endpoint passado é uma URL completa
        if (substr($endpoint, 0, 4) == 'http') {
            $url = $endpoint;
        } else {
            // Caso não seja, usa a URL base configurada nos parâmetros
            $url = $this->url . $endpoint;
        }

        // Se houver parâmetros adiciona ao final da URL
        if (count($parametros) > 0) {
            $url .= '?' . http_build_query($parametros);
        }

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "UTF-8",
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_SSLVERSION => 6,
            CURLOPT_HTTPHEADER => array(
                "Accept: */*",
                "Authorization: Bearer " . $this->token,
                "Cache-Control: no-cache"
            ),
        ));

        $response = curl_exec($curl);

        // Verifica erro ao fazer requisição
        if ($response === false) {
            $err = curl_error($curl);
            throw new InfraException('Erro ao fazer requisição: '.$err);
        }

        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        switch ($httpcode) {
            case 200:
                $response = json_decode($response, true);
                // Verifica erro na decodificação JSON
                if ($response === null) {
                    throw new InfraException('Erro ao decodificar resposta JSON da API ('. json_last_error_msg(). ')'.' - '.$url);
                }
                $response = $this->decodeResult($response);
                break;
            case 401:
                // Token inválido, tenta gerar token e refazer requisição
                if ($tentativa == 1) {
                    $this->apiGerarToken();
                    return $this->apiRestRequest($endpoint, $parametros, 2);
                } else {
                    throw new InfraException('Token de acesso à API inválido');
                }
                break;
            case 404: // Nenhum retorno encontrado...
                throw new MdCguEouvExceptionHttpNotFound('HTTP 404: página não encontrada');
                break;
            default:
                throw new InfraException('Ocorreu algum erro não tratado. HTTP Status: ' . $httpcode);
                break;
        }

        return $response;
    }

    /**
     * Varre uma estrutura de array retornada pela API convertendo strings
     * do charset UTF-8 para ISO-8859-1
     * @param array $array Array a ser convertido
     * @return array Array convertido
     */
    private function decodeResult($array)
    {
        foreach($array as $key => $value) {
            if(is_array($value)) {
                $array[$key] = $this->decodeResult($value);
            } else if (is_string($value)) {
                $array[$key] = $this->converteStrApiSei($value);
            }
        }

        return $array;
    }

    /**
     * Converte uma string do charset da API (UTF-8) para o SEI (ISO-8859-1)
     * @param string $valor String codificada em UTF-8
     * @return string String convertida para ISO-8859-1
     */
    private function converteStrApiSei($valor)
    {
        return mb_convert_encoding($valor, 'ISO-8859-1', 'UTF-8');
    }

    /**
     * Converte uma string do charset do SEI (ISO-8859-1) para o da API (UTF-8)
     * @param string $valor String codificada em ISO-8859-1
     * @return string String convertida para UTF-8
     */
    private function converteStrSeiApi($valor)
    {
        return mb_convert_encoding($valor, 'UTF-8', 'ISO-8859-1');
    }

    /**
     * Gera um novo token de acesso à API baseado nos parâmetros
     * de autenticação configurados
     * @return void
     */
    private function apiGerarToken()
    {
        $curl = curl_init();

        $params = [
            'client_id' => $this->clientID,
            'client_secret' => $this->secret,
            'username' => $this->usuario,
            'password' => $this->senha,
            'grant_type' => 'password',
        ];

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->url . '/oauth/token',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "UTF-8",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_SSLVERSION => 6,
            CURLOPT_POSTFIELDS => http_build_query($params),
            CURLOPT_HTTPHEADER => array(
                "Cache-Control: no-cache"
            ),
        ));

        $response = curl_exec($curl);

        if ($response === false) {
            $err = curl_error($curl);
            throw new InfraException('Erro ao fazer requisição para gerar token: '.$err);
        }

        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($httpcode != 200) {
            throw new InfraException('Erro ao gerar token: código HTTP '.$httpcode);
        }

        $response = json_decode($response, true);
        // Verifica erro na decodificação JSON
        if ($response === null) {
            throw new InfraException('Erro ao decodificar resposta ao gerar token: '. json_last_error_msg());
        }

        // Verifica erros na resposta
        if (isset($response['error']) || !isset($response['access_token'])) {
            throw new InfraException('Não foi possível gerar o Token de acesso à API do FalaBR. '.
                'Verifique os parâmetros de autenticação nas configurações do módulo');
        } else {
            // Salva token recebido no objeto
            $this->token = $response['access_token'];

            // Salva token no banco de dados
            $objEouvParametroDTO = new MdCguEouvParametroDTO();
            $objEouvParametroDTO->setStrNoParametro('TOKEN');
            $objEouvParametroDTO->retTodos();
            $objEouvParametroRN = new MdCguEouvParametroRN();
            $objEouvParametroDTO = $objEouvParametroRN->consultarParametro($objEouvParametroDTO);

            $objEouvParametroDTO->setStrDeValorParametro($this->converteStrApiSei($this->token));
            $objEouvParametroRN->alterarParametro($objEouvParametroDTO);
        }
    }
}

class MdCguEouvExceptionHttpNotFound extends InfraException {}

?>