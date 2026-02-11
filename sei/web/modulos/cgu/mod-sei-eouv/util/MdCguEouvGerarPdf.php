<?php

/**
 * CONTROLADORIA-GERAL DA UNIÃO - CGU
 *
 * Classe base para gerar os PDFs com as informações
 * importadas do FalaBR
 */

class MdCguEouvGerarPdf extends InfraPDF
{
    private $numSecao;

    public function __construct() {
        parent::__construct('P', 'pt', 'A4');
        $this->AddPage();
        $this->numSecao = 1;
    }

    /**
     * Adicionar um título ao PDF
     * @param string|array $texto Texto do título ou array com texto de cada linha
     * @return void
     */
    protected function titulo($texto)
    {
        if (is_array($texto)) {
            $linhas = $texto;
        } else {
            $linhas = [$texto];
        }

        $this->SetFont('Arial', 'B', 15);
        $qtde = count($linhas);
        for ($i = 0; $i < $qtde; ++$i) {
            $this->Cell(0, 5, $linhas[$i], 0, 1, 'C');
            if ($i < ($qtde-1)) {
                $this->Ln(20);
            } else {
                $this->Ln(30); // Dá um espaço maior após a última linha
            }
        }
    }

    /**
     * Adicionar uma seção ao PDF
     * @param string $texto Título da seção
     * @return void
     */
    protected function secao($texto)
    {
        $this->Ln();
        $this->SetFont('Arial', 'B', 10);
        $this->SetFillColor(0xb0, 0xc4, 0xde);
        $this->Cell(0, 20, $texto, 0, 0, 'L', true);
        $this->Ln(30);
        $this->numSecao += 1;
    }

    /**
     * Adicionar um item de informação ao PDF
     * @param string $nome Nome do item
     * @param string $valor Valor do item
     * @param bool $quebrarLinha Indica se uma quebra de linha deve ser inserida
     * entre o nome e o valor do item
     * @return void
     */
    protected function item($nome, $valor, $quebrarLinha = false)
    {
        $this->SetFont('arial', 'B', 12);
        // Mede tamanho do nome
        $largura = $this->GetStringWidth($nome . ': ');
        if ($largura < 180) {
            $largura = 180; // Garante espaçamento mínimo
        }
        $this->Cell($largura, 20, $nome.':', 0, $quebrarLinha ? 1 : 0, 'L');
        $this->SetFont('arial', '', 12);
        $this->MultiCell(0, 20, $valor, 0, 'J');
    }

    /**
     * Adicionar linha de texto comum
     * @param string $texto Texto para adicionar
     * @param bool $negrito Se o texto deve ficar em negrito
     * @return void
     */
    protected function texto($texto, $negrito = false)
    {
        $this->SetFont('arial', $negrito ? 'B' : '', 12);
        $this->MultiCell(0, 20, $texto, 0, 'J');
    }

    /**
     * Adiciona um espaçamento maior entre duas linhas
     * @return void
     */
    protected function espacamento()
    {
        $this->Ln(20);
    }

    /**
     * Cabeçalho do documento
     * @return void
     */
    public function Header()
    {
        $this->titulo([
            'Plataforma Integrada de Ouvidoria e Acesso à Informação',
            'Detalhes da Manifestação',
        ]);
    }

    /**
     * Escreve no PDF a seção comum de dados básicos
     * da manifestação
     * @param array $manifestacao Estrutura ManifestacaoDTO
     * (https://falabr.cgu.gov.br/Help/ResourceModel?modelName=ManifestacaoDTO)
     * @return void
     */
    protected function secaoDadosBasicos($manifestacao)
    {
        $this->secao('Dados Básicos da Manifestação');
        $tipo_manifestacao = $manifestacao['TipoManifestacao']['IdTipoManifestacao'] . ' - ' .
            $manifestacao['TipoManifestacao']['DescTipoManifestacao'];
        $this->item('Tipo da Manifestação', $tipo_manifestacao);
        $this->item('Tipo de Formulário', $manifestacao['TipoFormulario']['DescTipoFormulario']);
        $this->item('NUP', $manifestacao['NumerosProtocolo'][0]);
        $this->item('Esfera', $manifestacao['OuvidoriaDestino']['Esfera']['DescEsfera']);
        $this->item('Órgão Destinatário', $manifestacao['OuvidoriaDestino']['NomOuvidoria']);
        if (is_array($manifestacao['OrgaoInteresse'])) {
            $this->item('Órgão de Interesse', $manifestacao['OrgaoInteresse']['NomeOrgao']);
        }
        if (is_array($manifestacao['Assunto'])) {
            $this->item('Assunto', $manifestacao['Assunto']['DescAssunto']);
        }
        if (is_array($manifestacao['SubAssunto'])) {
            $this->item('Subassunto', $manifestacao['SubAssunto']['DescSubAssunto']);
        }
        $this->item('Data do Cadastro', $manifestacao['DataCadastro']);
        if (is_array($manifestacao['Situacao'])) {
            $this->item('Situação', $manifestacao['Situacao']['DescSituacaoManifestacao']);
        }
        $this->item('Registrado Por', $manifestacao['RegistradoPor']);
        $this->item('Data Limite para Resposta', $manifestacao['PrazoAtendimento']);
        if (is_array($manifestacao['CanalEntrada'])) {
            $this->item('Canal de Entrada', $manifestacao['CanalEntrada']['IdCanalEntrada'] . " - " .
                $manifestacao['CanalEntrada']['DescCanalEntrada']);
        }
        if (is_array($manifestacao['ModoResposta'])) {
            $this->item('Modo de Resposta', $manifestacao['ModoResposta']['DescModoResposta']);
        }
        if (is_array($manifestacao['Servico'])) {
            $textoServico = $manifestacao['Servico']['IdServicoMPOG'];
            if ($manifestacao['Servico']['Nome']) {
                $textoServico .= ' - ' . $manifestacao['Servico']['Nome'];
            }
            $this->item('Serviço', $textoServico);
        }
    }

    /**
     * Escreve no PDF a seção comum de dados do manifestante
     * @param array $manifestacao Estrutura ManifestacaoDTO
     * (https://falabr.cgu.gov.br/Help/ResourceModel?modelName=ManifestacaoDTO)
     * @param bool $importarDadosDoManifestante Se o módulo está configurado para
     * importar dados do manifestante
     * @return void
     */
    protected function secaoDadosManifestante($manifestacao, $importarDadosDoManifestante)
    {
        $this->secao('Dados do Manifestante');
        if ($importarDadosDoManifestante) {
            if (is_array($manifestacao['Manifestante']) && is_array($manifestacao['Manifestante']['TipoPessoa'])) {
                // Manifestante identificado
                $pessoa = $manifestacao['Manifestante'];

                $this->item('Nome', $pessoa['Nome']);
                if (is_array($pessoa['FaixaEtaria'])) {
                    $this->item('Faixa Etária', $pessoa['FaixaEtaria']['DescFaixaEtaria']);
                }

                $chaveCorRaca = mb_convert_encoding('corRaça', 'UTF-8', 'ISO-8859-1');
                if (is_array($pessoa[$chaveCorRaca])) {
                    $this->item('Raça/Cor', $pessoa[$chaveCorRaca]['DescRacaCor']);
                }

                if ($pessoa['genero']) {
                    $this->item('Sexo', $pessoa['genero']);
                }

                if (is_array($pessoa['TipoDocumentoIdentificacao'])) {
                    $documento = $pessoa['TipoDocumentoIdentificacao']['DescTipoDocumentoIdentificacao'].' '.
                        $pessoa['NumeroDocumentoIdentificacao'];
                    $this->item('Documento de Identificação', $documento);
                }

                if (is_array($pessoa['Endereco']) && $pessoa['Endereco']['Logradouro']) {
                    $endereco = $pessoa['Endereco']['Logradouro'];
                    $endereco .= ', ' . $pessoa['Endereco']['Numero'];
                    if ($pessoa['Endereco']['Complemento']) {
                        $endereco .= ', ' . $pessoa['Endereco']['Complemento'];
                    }
                    if ($pessoa['Endereco']['Bairro']) {
                        $endereco .= "\n" . $pessoa['Endereco']['Bairro'];
                    }
                    if (is_array($pessoa['Endereco']['Municipio'])) {
                        $endereco .= "\n" . $pessoa['Endereco']['Municipio']['DescMunicipio'] . ' - ' .
                            $pessoa['Endereco']['Municipio']['Uf']['SigUf'];
                    }
                    if ($pessoa['Endereco']['CEP']) {
                        $endereco .= "\nCEP: " . $pessoa['Endereco']['CEP'];
                    }
                    $this->item('Endereço', $endereco, true);
                }

                if (is_array($pessoa['Telefone'])) {
                    $telefone = '(' . $pessoa['Telefone']['ddd'] . ') ' . $pessoa['Telefone']['Numero'];
                    $this->item('Telefone', $telefone);
                }

                if ($pessoa['Email']) {
                    $this->item('Email', $pessoa['Email']);
                }
            } else {
                // Manifestação anônima
                $this->texto('Manifestação anônima', true);
            }
        } else {
            // Importação desabilitada
            $this->texto('Não importado devido à configuração do módulo', true);
        }
    }
}