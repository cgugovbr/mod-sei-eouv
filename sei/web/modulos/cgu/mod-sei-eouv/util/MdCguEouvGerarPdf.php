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

        $this->SetFont('arial', 'B', 15);
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
        $this->SetFont('arial', 'B', 10);
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
     * @param string $alinhamento 'L' se o nome do item deve ser alinhado à esquerda
     * ou 'R' se deve ser alinhado à direita
     * @return void
     */
    protected function item($nome, $valor, $quebrarLinha = false, $alinhamento = 'L')
    {
        $this->SetFont('arial', '', 10);

        $largura = $this->GetStringWidth($nome);
        if ($largura > 150 && !$quebrarLinha) {
            // Se a largura do nome ultrapassar 150 e o valor não ficar na linha
            // de baixo (quebrarLinha = false) renderiza como se fosse
            // uma tabela de 2 colunas
            $larguras = [150, 390];
            $matriz = $this->calculaMatrizDaLinha([$nome, $valor], $larguras);
            foreach ($matriz as $linha) {
                foreach ($linha as $j => $texto) {
                    $this->Cell($larguras[$j], 15, $texto, 0, 0, $j == 0 ? $alinhamento : 'J');
                }
                $this->Ln();
            }
        } else {
            $this->Cell(150, 15, $nome, 0, $quebrarLinha ? 1 : 0, $alinhamento);
            $this->MultiCell(0, 15, $valor, 0, 'J');
        }
        $this->Ln(5);
    }

    /**
     * Adiciona uma tabela
     * @param string $titulo Título da tabela
     * @param array<string> $cabecalhos Array em que cada elemento representa o
     * nome de exibição da coluna
     * @param array<array<string>> $dados Matriz em que cada elemento representa
     * o texto de uma célula da tabela
     */
    protected function tabela($dados, $cabecalhos = [], $titulo = '')
    {
        $this->SetFont('arial', '', 10);
        $this->SetDrawColor(0xd3, 0xd3, 0xd3);
        $this->SetLineWidth(1);
        $this->SetFillColor(0xd3, 0xd3, 0xd3);

        // Calcula largura das colunas identificando a maior palavra
        $dadosCombinados = $dados;
        if (count($cabecalhos) > 0) {
            $dadosCombinados = array_merge([$cabecalhos], $dadosCombinados);
        }
        $palavras = [];
        foreach ($dadosCombinados as $i => $celulasDaLinha) {
            foreach ($celulasDaLinha as $j => $texto) {
                if (!isset($palavras[$j])) {
                    $palavras[$j] = explode(' ', $texto);
                } else {
                    $palavras[$j] = array_merge($palavras[$j], explode(' ', $texto));
                }
            }
        }
        $larguras = [];
        $somaLarguras = 0;
        foreach ($palavras as $j => $palavrasDaColuna) {
            $larguraCol = 0;
            foreach ($palavrasDaColuna as $palavra) {
                $larguraCol = max($larguraCol, $this->GetStringWidth($palavra));
            }
            if ($larguraCol == 0) {
                $larguraCol = $this->GetStringWidth('AAA');
            }
            $larguras[$j] = $larguraCol;
            $somaLarguras += $larguraCol;
        }

        // Normaliza proporcionalmente, considerando um tamanho máximo de
        // largura de 540 para a tabela toda
        foreach ($larguras as $i => $largura) {
            $larguras[$i] = $largura / $somaLarguras * 540;
        }

        // Calcula distribuição do texto dentro de cada célula, considerando
        // a largura da coluna a necessidade de quebrar a linha dentro da célula
        $matrizesDeLinha = [];
        foreach ($dados as $j => $celulas) {
            $matrizesDeLinha[] = $this->calculaMatrizDaLinha($celulas, $larguras);
        }

        // Desenha tabela
        if ($titulo) {
            $this->MultiCell(540, 20, $titulo, 1, 'L', true);
        }
        if (count($cabecalhos) > 0) {
            foreach ($cabecalhos as $i => $cabecalho) {
                $this->Cell($larguras[$i], 20, $cabecalho, 1, 0, 'L', true);
            }
            $this->Ln();
        }
        foreach ($matrizesDeLinha as $matrizDeLinha) {
            $iUltimaLinha = count($matrizDeLinha) - 1;
            foreach ($matrizDeLinha as $i => $linha) {
                foreach ($linha as $j => $texto) {
                    $border = 'LR';
                    if ($i == 0) {
                        $border .= 'T';
                    }
                    if ($i == $iUltimaLinha) {
                        $border .= 'B';
                    }
                    $this->Cell($larguras[$j], 20, $texto, $border, 0, 'L', false);
                }
                $this->Ln();
            }
        }
    }

    /**
     * A partir das células que compões uma linha e das larguras de cada coluna
     * a função distribui o texto de cada célula quebrando-o se necessário.
     * @param array<string> $celulas Array em que cada elemento
     * representa o texto da célula
     * @param array<float> $larguraDasColunas Array em que cada elemento
     * representa a largura da coluna
     * @return array<array<string>> Matriz contendo a disposição dos textos para renderizar
     */
    private function calculaMatrizDaLinha($celulas, $largurasDasColunas) {
        $larguraDoEspaco = $this->GetStringWidth(' ');
        $layoutDasCelulas = [];
        $numeroDeLinhasDeTexto = 0;
        foreach ($celulas as $j => $celula) {
            $palavras = explode(' ', $celula);
            $linhasDeTexto = [];
            $bufferDePalavras = [];
            $x = -$larguraDoEspaco;
            foreach ($palavras as $palavra) {
                $larguraDaPalavra = $this->GetStringWidth($palavra);
                if ($x + $larguraDoEspaco + $larguraDaPalavra < $largurasDasColunas[$j]) {
                    $x += $larguraDoEspaco + $larguraDaPalavra;
                    $bufferDePalavras[] = $palavra;
                } else { // nova linha dentro da célula
                    $linhasDeTexto[] = implode(' ', $bufferDePalavras);
                    $x = $larguraDaPalavra;
                    $bufferDePalavras = [$palavra];
                }
            }

            if (count($bufferDePalavras) > 0) {
                $linhasDeTexto[] = implode(' ', $bufferDePalavras);
            }

            $layoutDasCelulas[] = $linhasDeTexto;
            $numeroDeLinhasDeTexto = max($numeroDeLinhasDeTexto, count($linhasDeTexto));
        }

        $layout = [];
        $numeroDeColunas = count($celulas);
        for ($i = 0; $i < $numeroDeLinhasDeTexto; ++$i) {
            $layout[$i] = [];
            for ($j = 0; $j < $numeroDeColunas; ++$j) {
                $layout[$i][$j] = '';
            }
        }

        foreach ($layoutDasCelulas as $j => $linhasDeTexto) {
            foreach ($linhasDeTexto as $i => $texto) {
               $layout[$i][$j] = $texto;
            }
        }

        return $layout;
    }

    /**
     * Adicionar linha de texto comum
     * @param string $texto Texto para adicionar
     * @param bool $negrito Se o texto deve ficar em negrito
     * @return void
     */
    protected function texto($texto, $negrito = false)
    {
        $this->SetFont('arial', $negrito ? 'B' : '', 10);
        $this->MultiCell(0, 15, $texto, 0, 'J');
    }

    /**
     * Adiciona um espaçamento maior entre duas linhas
     * @return void
     */
    protected function espacamento()
    {
        $this->Ln(15);
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
        $this->item('Tipo da Manifestação:', $manifestacao['TipoManifestacao']['DescTipoManifestacao'], false, 'R');
        $this->item('Esfera:', $manifestacao['OuvidoriaDestino']['Esfera']['DescEsfera'], false, 'R');
        $this->item('NUP:', $manifestacao['NumerosProtocolo'][0], false, 'R');
        $this->item('Órgão Destinatário:', $manifestacao['OuvidoriaDestino']['NomOuvidoria'], false, 'R');
        $this->item('Órgão de Interesse:', $manifestacao['OrgaoInteresse']['NomeOrgao'] ?? '', false, 'R');
        $this->item('Assunto:', $manifestacao['Assunto']['DescAssunto'], false, 'R');
        $this->item('Subassunto:', $manifestacao['SubAssunto']['DescSubAssunto'] ?? '', false, 'R');
        $this->item('Data de Cadastro:', $manifestacao['DataCadastro'], false, 'R');
        $this->item('Situação:', $manifestacao['Situacao']['DescSituacaoManifestacao'] ?? '', false, 'R');
        $this->item('Data limite para resposta:', $manifestacao['PrazoAtendimento'], false, 'R');
        $this->item('Canal de Entrada:', $manifestacao['CanalEntrada']['DescCanalEntrada'] ?? '', false, 'R');
        $this->item('Modo de Resposta:', $manifestacao['ModoResposta']['DescModoResposta'] ?? '', false, 'R');
        $this->item('Registrado Por:', $manifestacao['RegistradoPor'], false, 'R');
        $this->item('Tipo de formulário:', $manifestacao['TipoFormulario']['DescTipoFormulario'] ?? '', false, 'R');
        $servico = (isset($manifestacao['Servico']['IdServicoMPOG']) && $manifestacao['Servico']['IdServicoMPOG'] > 0) ?
            $manifestacao['Servico']['Nome'] : '';
        $this->item('Serviço:', $servico, false, 'R');
        $outroServico = (isset($manifestacao['Servico']['IdServicoMPOG']) && $manifestacao['Servico']['IdServicoMPOG'] == 0) ?
            $manifestacao['Servico']['Nome'] : '';
        $this->item('Outro Serviço:', $outroServico, false, 'R');
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

                $this->item('Nome:', $pessoa['Nome'], false, 'R');
                if (is_array($pessoa['FaixaEtaria'])) {
                    $this->item('Faixa Etária', $pessoa['FaixaEtaria']['DescFaixaEtaria']);
                }

                $chaveCorRaca = mb_convert_encoding('corRaça', 'UTF-8', 'ISO-8859-1');
                if (is_array($pessoa[$chaveCorRaca])) {
                    $this->item('Raça/Cor:', $pessoa[$chaveCorRaca]['DescRacaCor'], false, 'R');
                }

                if ($pessoa['genero']) {
                    $this->item('Sexo:', $pessoa['genero'], false, 'R');
                }

                if (is_array($pessoa['TipoDocumentoIdentificacao'])) {
                    $documento = $pessoa['TipoDocumentoIdentificacao']['DescTipoDocumentoIdentificacao'].' '.
                        $pessoa['NumeroDocumentoIdentificacao'];
                    $this->item('Documento de Identificação:', $documento, false, 'R');
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
                    $this->item('Endereço:', $endereco, true, 'R');
                }

                if (is_array($pessoa['Telefone'])) {
                    $telefone = '(' . $pessoa['Telefone']['ddd'] . ') ' . $pessoa['Telefone']['Numero'];
                    $this->item('Telefone:', $telefone, false, 'R');
                }

                if ($pessoa['Email']) {
                    $this->item('Email:', $pessoa['Email'], false, 'R');
                }
            } else {
                // Manifestação anônima
                $this->texto('Manifestação anônima');
            }
        } else {
            // Importação desabilitada
            $this->texto('Não importado devido à configuração do módulo');
        }
    }
}