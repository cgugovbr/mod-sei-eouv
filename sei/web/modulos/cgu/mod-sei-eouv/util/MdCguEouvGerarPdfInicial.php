<?php

/**
 * CONTROLADORIA GERAL DA UNIÃO- CGU
 *
 * 09/10/2015 - criado por Rafael Leandro
 *
 */

error_reporting(E_ALL); ini_set('display_errors', '1');

class MdCguEouvGerarPdfInicial extends InfraPDF
{
     protected $nup;
     protected $dt_cadastro;
     protected $desc_assunto;
     protected $desc_sub_assunto;
     protected $id_tipo_manifestacao;
     protected $desc_tipo_manifestacao;
     protected $desc_tipo_formulario;
     protected $envolve_das4_superior;
     protected $dt_prazo_atendimento;
     protected $esfera_orgao;
     protected $nome_orgao;
     protected $canal_entrada;
     protected $registrado_por;
     protected $importar_dados_manifestante;
     protected $nome;
     protected $desc_faixa_etaria;
     protected $desc_raca_cor;
     protected $sexo;
     protected $desc_documento_identificacao;
     protected $numero_documento_identificacao;
     protected $endereco;
     protected $bairro;
     protected $desc_municipio;
     protected $cep;
     protected $telefone;
     protected $email;
     protected $desc_municipio_fato;
     protected $descricao_local_fato;
     protected $descricao_fato;
     protected $envolvidos;
     protected $campos_adicionais;
     protected $ocorreuErroAdicionarAnexo;

    public function __construct($retornoWsLinha)
    {
        $this->nup = $retornoWsLinha['NumerosProtocolo'][0];
        $this->dt_cadastro = $retornoWsLinha['DataCadastro'];

        if (is_array($retornoWsLinha['Assunto'])) {
            $this->desc_assunto = $retornoWsLinha['Assunto']['DescAssunto'];
        }

        if (is_array($retornoWsLinha['SubAssunto']) && isset($retornoWsLinha['SubAssunto']['DescSubAssunto'])) {
            $this->desc_sub_assunto = $retornoWsLinha['SubAssunto']['DescSubAssunto'];
        }

        $this->id_tipo_manifestacao = $retornoWsLinha['TipoManifestacao']['IdTipoManifestacao'];
        $this->desc_tipo_manifestacao = $retornoWsLinha['TipoManifestacao']['DescTipoManifestacao'];
        $this->desc_tipo_formulario = $retornoWsLinha['TipoFormulario']['DescTipoFormulario'];
        $this->envolve_das4_superior = $retornoWsLinha['InformacoesAdicionais']['EnvolveCargoComissionadoDAS4OuSuperior'];
        $this->dt_prazo_atendimento = $retornoWsLinha['PrazoAtendimento'];
        $this->esfera_orgao = $retornoWsLinha['OuvidoriaDestino']['Esfera']['DescEsfera'];
        $this->nome_orgao = $retornoWsLinha['OuvidoriaDestino']['NomOuvidoria'];

        if (is_array($retornoWsLinha['CanalEntrada'])) {
            $this->canal_entrada = $retornoWsLinha['CanalEntrada']['IdCanalEntrada'] . " - " . $retornoWsLinha['CanalEntrada']['DescCanalEntrada'];
        }

        $this->registrado_por = $retornoWsLinha['RegistradoPor'];

        if (is_array($retornoWsLinha['Manifestante'])) {

            $this->nome = $retornoWsLinha['Manifestante']['Nome'];
            if (is_array($retornoWsLinha['Manifestante']['FaixaEtaria'])) {
                $this->desc_faixa_etaria = $retornoWsLinha['Manifestante']['FaixaEtaria']['DescFaixaEtaria'];
            }
            if (is_array($retornoWsLinha['Manifestante'][utf8_encode('corRaça')])) {
                $this->desc_raca_cor = $retornoWsLinha['Manifestante'][utf8_encode('corRaça')]['DescRacaCor'];
            }
            $this->sexo = $retornoWsLinha['Manifestante']['genero'];
            if (is_array($retornoWsLinha['Manifestante']['TipoDocumentoIdentificacao'])) {
                $this->desc_documento_identificacao = $retornoWsLinha['Manifestante']['TipoDocumentoIdentificacao']['DescTipoDocumentoIdentificacao'];
            }
            $this->numero_documento_identificacao = $retornoWsLinha['Manifestante']['NumeroDocumentoIdentificacao'];

            if (is_array($retornoWsLinha['Manifestante']['Endereco'])) {
                $endereco = $retornoWsLinha['Manifestante']['Endereco']['Logradouro'] . ", " . $retornoWsLinha['Manifestante']['Endereco']['Numero'];
                if ($retornoWsLinha['Manifestante']['Endereco']['Complemento']) {
                    $endereco .= ", " . $retornoWsLinha['Manifestante']['Endereco']['Complemento'];
                }
                $this->endereco = $endereco;
                $this->bairro = $retornoWsLinha['Manifestante']['Endereco']['Bairro'];

                if (is_array($retornoWsLinha['Manifestante']['Endereco']['Municipio'])) {
                    $this->desc_municipio =$retornoWsLinha['Manifestante']['Endereco']['Municipio']['DescMunicipio'] . " / " . $retornoWsLinha['Manifestante']['Endereco']['Municipio']['Uf']['SigUf'] . " - " . $retornoWsLinha['Manifestante']['Endereco']['Municipio']['Uf']['DescUf'];
                }

                $this->cep = $retornoWsLinha['Manifestante']['Endereco']['CEP'];
            }

            if (is_array($retornoWsLinha['Manifestante']['Telefone'])) {
                $this->telefone = ("(" . $retornoWsLinha['Manifestante']['Telefone']['ddd'] . ") " . $retornoWsLinha['Manifestante']['Telefone']['Numero']);
            }

            $this->email = $retornoWsLinha['Manifestante']['Email'];
        }

        if (is_array($retornoWsLinha['Teor']['LocalFato'])) {
            if (is_array($retornoWsLinha['Teor']['LocalFato']['Municipio'])) {
                $this->desc_municipio_fato = $retornoWsLinha['Teor']['LocalFato']['Municipio']['DescMunicipio'] . " / " . $retornoWsLinha['Teor']['LocalFato']['Municipio']['Uf']['SigUf'] . " - " . $retornoWsLinha['Teor']['LocalFato']['Municipio']['Uf']['DescUf'];
            }

            $this->descricao_local_fato = $retornoWsLinha['Teor']['LocalFato']['DescricaoLocalFato'];
        }

        $this->descricao_fato = $retornoWsLinha['Teor']['DescricaoAtosOuFatos'];

        $envolvidos = array();
        if (is_array($retornoWsLinha['Teor']['EnvolvidosManifestacao']) && isset($retornoWsLinha['Teor']['EnvolvidosManifestacao'])) {
            $iEnvolvido = 0;
            foreach ($retornoWsLinha['Teor']['EnvolvidosManifestacao'] as $envolvidosFatoManifestacao) {
                $envolvidos[$iEnvolvido][0] = $envolvidosFatoManifestacao['IdFuncaoEnvolvidoManifestacao'] . " - " . $envolvidosFatoManifestacao['Funcao'];
                $envolvidos[$iEnvolvido][1] = $envolvidosFatoManifestacao['Nome'];
                $envolvidos[$iEnvolvido][2] = $envolvidosFatoManifestacao['Orgao'];
                $iEnvolvido++;
            }
        }
        $this->envolvidos = $envolvidos;

        $campos_adicionais = array();

        if (is_array($retornoWsLinha['Teor']['CamposAdicionaisManifestacao']) && isset($retornoWsLinha['Teor']['CamposAdicionaisManifestacao'])) {
            $iCamposAdicionais = 0;
            foreach ($retornoWsLinha['Teor']['CamposAdicionaisManifestacao'] as $camposAdicionais) {
                $campos_adicionais[$iCamposAdicionais][0] = $camposAdicionais['NomeExibido'];
                $campos_adicionais[$iCamposAdicionais][1] = $camposAdicionais['Valor'];
                $iCamposAdicionais++;
            }
        }
        $this->campos_adicionais = $campos_adicionais;

    }

    public function gerarPdfInicial()
    {
        $pdf = new InfraPDF("P", "pt", "A4");

        $pdf->AddPage();
        //$pdf->Image('logog8.jpg');

        $pdf->SetFont('arial', 'B', 18);
        $pdf->Cell(0, 5, "Dados da Manifestação", 0, 1, 'C');
        $pdf->Cell(0, 5, "", "B", 1, 'C');
        $pdf->Ln(20);

        //***********************************************************************************************
        //1. Dados INICIAIS
        //***********************************************************************************************
        $pdf->SetFont('arial', 'B', 14);
        $pdf->Cell(0, 20, "1. Dados Iniciais da Manifestação", 0, 0, 'L');
        $pdf->Ln(20);

        //NUP
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(150, 20, "NUP:", 0, 0, 'L');
        $pdf->setFont('arial', '', 12);
        $pdf->Cell(0, 20, $this->nup, 0, 1, 'L');

        //Data Cadastro
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(150, 20, "Data do Cadastro:", 0, 0, 'L');
        $pdf->setFont('arial', '', 12);
        $pdf->Cell(0, 20, $this->dt_cadastro, 0, 1, 'L');

        //Assunto / SubAssunto
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(150, 20, "Assunto/SubAssunto:", 0, 0, 'L');
        $pdf->setFont('arial', '', 12);
        $pdf->Cell(0, 20, $this->desc_assunto . " / " . $this->desc_sub_assunto, 0, 1, 'L');

        //Tipo de Manifestação
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(150, 20, "Tipo da Manifestação:", 0, 0, 'L');
        $pdf->setFont('arial', '', 12);
        $pdf->Cell(0, 20, $this->id_tipo_manifestacao . " - " . $this->desc_tipo_manifestacao, 0, 1, 'L');

        // Tipo de formulário
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(150, 20, "Tipo de Formulário:", 0, 0, 'L');
        $pdf->setFont('arial', '', 12);
        $pdf->Cell(0, 20, $this->desc_tipo_formulario, 0, 1, 'L');

        //EnvolveDas4OuSuperior
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(450, 20, "Denúncia Envolvendo Ocupante de Cargo Comissionado DAS4 ou Superior?:", 0, 0, 'L');
        $pdf->setFont('arial', '', 12);
        $pdf->Cell(20, 20, $this->envolve_das4_superior, 0, 1, 'L');

        //Prazo de Atendimento
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(150, 20, "Prazo de Atendimento:", 0, 0, 'L');
        $pdf->setFont('arial', '', 12);
        $pdf->Cell(70, 20, $this->dt_prazo_atendimento, 0, 1, 'L');

        // Esfera do Órgão
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(150, 20, "Esfera:", 0, 0, 'L');
        $pdf->setFont('arial', '', 12);
        $pdf->Cell(0, 20, $this->esfera_orgao, 0, 1, 'L');

        //Nome do Órgão
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(150, 20, "Nome do Órgão:", 0, 0, 'L');
        $pdf->setFont('arial', '', 12);
        $pdf->MultiCell(0, 20, $this->nome_orgao, 0, 'L');

        //Canal Entrada
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(150, 20, "Canal de Entrada:", 0, 0, 'L');
        $pdf->setFont('arial', '', 12);
        $pdf->Cell(70, 20, $this->canal_entrada, 0, 1, 'L');

        //Registrado Por
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(150, 20, "Registrado Por:", 0, 0, 'L');
        $pdf->setFont('arial', '', 12);
        $pdf->Cell(70, 20, $this->registrado_por, 0, 1, 'L');

        //***********************************************************************************************
        //2. Dados do Solicitante
        //***********************************************************************************************
        $pdf->Ln(20);
        $pdf->SetFont('arial', 'B', 14);
        $pdf->Cell(70, 20, "2. Dados do Solicitante:", 0, 0, 'L');
        $pdf->Ln(20);

        if ($this->importar_dados_manifestante) {
            //Nome do Solicitante
            $pdf->SetFont('arial', 'B', 12);
            $pdf->Cell(150, 20, "Nome do Solicitante:", 0, 0, 'L');
            $pdf->setFont('arial', '', 12);
            $pdf->Cell(70, 20, $this->nome, 0, 1, 'L');

            //Faixa Etária
            $pdf->SetFont('arial', 'B', 12);
            $pdf->Cell(150, 20, "Faixa Etária:", 0, 0, 'L');
            $pdf->setFont('arial', '', 12);
            $pdf->Cell(70, 20, $this->desc_faixa_etaria, 0, 1, 'L');

            //Raça Cor
            $pdf->SetFont('arial', 'B', 12);
            $pdf->Cell(150, 20, "Raça/Cor:", 0, 0, 'L');
            $pdf->setFont('arial', '', 12);
            $pdf->Cell(70, 20, $this->desc_raca_cor, 0, 1, 'L');

            //Sexo
            $pdf->SetFont('arial', 'B', 12);
            $pdf->Cell(150, 20, "Sexo:", 0, 0, 'L');
            $pdf->setFont('arial', '', 12);
            $pdf->Cell(70, 20, $this->sexo, 0, 1, 'L');

            //Documento Identificação
            $pdf->SetFont('arial', 'B', 12);
            $pdf->Cell(170, 20, "Documento de Identificação:", 0, 0, 'L');
            $pdf->setFont('arial', '', 12);
            $pdf->Cell(70, 20, $this->desc_documento_identificacao, 0, 1, 'L');

            //NÃºmero do Documento Identificação
            $pdf->SetFont('arial', 'B', 12);
            $pdf->Cell(150, 20, "Número do Documento:", 0, 0, 'L');
            $pdf->setFont('arial', '', 12);
            $pdf->Cell(70, 20, $this->numero_documento_identificacao, 0, 1, 'L');

            $pdf->ln(4);
            //Endereço
            $pdf->SetFont('arial', 'B', 12);
            $pdf->Cell(70, 20, "Endereço:", 0, 1, 'L');
            $pdf->setFont('arial', '', 12);
            $pdf->Cell(70, 20, $this->endereco, 0, 1, 'L');
            $pdf->Cell(70, 20, $this->bairro, 0, 1, 'L');
            $pdf->Cell(70, 20, $this->desc_municipio, 0, 1, 'L');

            //CEP
            $pdf->Cell(70, 20, "CEP:", 0, 0, 'L');
            $pdf->setFont('arial', '', 12);
            $pdf->Cell(70, 20, $this->cep, 0, 1, 'L');

            //Telefone
            $pdf->SetFont('arial', 'B', 12);
            $pdf->Cell(70, 20, "Telefone:", 0, 0, 'L');
            $pdf->setFont('arial', '', 12);
            $pdf->Cell(70, 20, $this->telefone, 0, 1, 'L');

            //Email
            $pdf->SetFont('arial', 'B', 12);
            $pdf->Cell(70, 20, "E-mail:", 0, 0, 'L');
            $pdf->setFont('arial', '', 12);
            $pdf->Cell(70, 20, $this->email, 0, 1, 'L');
        } else {
            $pdf->SetFont('arial', 'B', 12);
            $pdf->Cell(150, 20, "Não importado do E-Ouv devido a configuração no módulo.", 0, 0, 'L');
        }
        $pdf->Ln(20);

        //***********************************************************************************************
        //3. Dados do Fato da Manifestação
        //***********************************************************************************************
        $pdf->Ln(20);
        $pdf->SetFont('arial', 'B', 14);
        $pdf->Cell(70, 20, "3. Fato da Manifestação:", 0, 0, 'L');
        $pdf->Ln(20);

        //Município/UF
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(115, 20, "Município/UF:", 0, 0, 'L');
        $pdf->setFont('arial', '', 12);
        $pdf->Cell(70, 20, $this->desc_municipio_fato, 0, 1, 'L');

        //Descricao Local
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(115, 20, "Local:", 0, 0, 'L');
        $pdf->setFont('arial', '', 12);
        $pdf->Cell(70, 20, $this->descricao_local_fato, 0, 1, 'L');

        //Descrição
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(115, 20, "Descrição:", 0, 1, 'L');
        $pdf->setFont('arial', '', 12);
        $pdf->MultiCell(0, 20, $this->descricao_fato, 0, 'J');

        //Envolvidos
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(115, 20, "Envolvidos:", 0, 1, 'L');
        $pdf->setFont('arial', '', 12);

        for ($x = 0; $x < count($this->envolvidos); $x++) {
            $pdf->Cell(70, 20, "Função:", 0, 0, 'L');
            $pdf->Cell(0, 20, $this->envolvidos[$x][0], 0, 1, 'L');
            $pdf->Cell(70, 20, "Nome:", 0, 0, 'L');
            $pdf->Cell(0, 20, $this->envolvidos[$x][1], 0, 1, 'L');
            $pdf->Cell(70, 20, "Órgão:", 0, 0, 'L');
            $pdf->Cell(0, 20, $this->envolvidos[$x][2], 0, 1, 'L');
            $pdf->Ln(10);
        }

        //***********************************************************************************************
        //4. Campos Adicionais
        //***********************************************************************************************
        $pdf->Ln(20);
        $pdf->SetFont('arial', 'B', 14);
        $pdf->Cell(70, 20, "4. Campos Adicionais:", 0, 0, 'L');
        $pdf->Ln(20);

        for ($y = 0; $y < count($this->campos_adicionais); $y++) {
            $pdf->SetFont('arial', 'B', 12);
            $pdf->Cell(180, 20, $this->campos_adicionais[$y][0] . ":", 0, 0, 'L');
            $pdf->setFont('arial', '', 12);
            $pdf->Cell(0, 20, $this->campos_adicionais[$y][1], 0, 1, 'L');
        }

        if ($this->ocorreuErroAdicionarAnexo == true) {
            $pdf->Ln(20);
            $pdf->SetFont('arial', 'B', 14);
            $pdf->Cell(70, 20, "5. Observações:", 0, 0, 'L');
            $pdf->Ln(20);

            $pdf->SetFont('arial', '', 12);
            $pdf->MultiCell(0, 20, "Um ou mais anexos da manifestação não foram importados para o SEI devido a restrições da extensão do arquivo. Acesse a manifestação através do link abaixo para mais detalhes. ", 0, 'J');
        }
        return $pdf;
    }

}