<?php

/**
 * CONTROLADORIA-GERAL DA UNIÃO - CGU
 *
 * Classe que gera PDFs com as informações das manifestações
 * de acesso à informação importadas do FalaBR
 */

 require_once __DIR__ . '/MdCguEouvGerarPdf.php';

class MdCguEouvGerarPdfEsic extends MdCguEouvGerarPdf
{
    public function __construct($manifestacao, $recursos, $ocorreuErroAdicionarAnexo)
    {
        $this->criarPDF();

        $this->titulo([
            'Plataforma Integrada de Ouvidoria e Acesso à Informação',
            'Detalhes da Manifestação',
        ]);

        /**
         * Seção dados básicos
         */
        $this->secaoDadosBasicos($manifestacao);

        /**
         * Seção teor da manifestação
         */
        $this->secao('Teor da Manifestação');
        $teor = $manifestacao['Teor'];
        $this->item('Resumo', $manifestacao['ResumoSolicitacao'], true);
        $this->item('Extrato', $teor['DescricaoAtosOuFatos'], true);

        /**
         * Seção Anexos
         */
        $this->secao('Anexo(s) do Pedido Inicial');
        $anexoTipoOriginal = 0;
        $anexoTipoComplementar = 0;
        if (is_array($teor['Anexos'])) {
            foreach ($teor['Anexos'] as $anexo) {
                if ($anexo['TipoAnexoManifestacao']['IdTipoAnexoManifestacao'] == 1) {
                    if ($anexo['IndComplementar']) {
                        $anexoTipoComplementar++;
                    } else {
                        $anexoTipoOriginal++;
                    }

                    $this->item('Nome do Arquivo', $anexo['NomeArquivo']);
                    $this->item('Tipo de Anexo', $anexo['TipoAnexoManifestacao']['DescTipoAnexoManifestacao']);
                    $this->item('Anexo Complementar', $anexo['IndComplementar'] ? 'Sim' : 'Não');
                    $this->espacamento();
                }
            }
        }
        if ($anexoTipoOriginal == 0) {
            $this->texto('Não há anexos originais da manifestação.', true);
        }
        if ($anexoTipoComplementar == 0) {
            $this->texto('Não há anexos complementares.', true);
        }

        /**
         * Seção campos adicionais
         */
        $this->secao('Campos Adicionais');
        $camposAdicionais = $teor['CamposAdicionaisManifestacao'];
        if (is_array($camposAdicionais) && count($camposAdicionais) > 0) {
            foreach ($camposAdicionais as $campo) {
                $this->item($campo['NomeExibido'], $campo['Valor']);
            }
        } else {
            $this->texto('Não há campos adicionais.', true);
        }

        /**
         * Seção respostas
         */
        $this->secao('Resposta(s)');
        $historico = $manifestacao['Historico'];
        $iResposta = 1;
        if (is_array($historico)) {
            foreach ($historico as $evento) {
                if ($evento['HistoricoAcao']['DescTipoAcaoManifestacao'] == 'Registro Resposta') {
                    $this->texto('Resposta ' . $iResposta, true);

                    $this->item('Tipo de Resposta', $evento['Resposta']['TipoRespostaManifestacao']['DescTipoRespostaManifestacao']);
                    $this->item('Data e Hora', $evento['HistoricoAcao']['DataHoraAcao']);
                    $this->item('Decisão', $evento['Resposta']['Decisao']['descricaoDecisao']);
                    $this->item('Teor da Resposta', $evento['Resposta']['TxtResposta']);
                    $this->espacamento();

                    ++$iResposta;
                }
            }
        }

        if ($iResposta == 1) {
            $this->texto('Não há registro de respostas.', true);
        }

        /**
         * Seção anexo das respostas
         */
        $this->secao('Anexos das Respostas');
        $possuiAnexoResposta = false;
        foreach ($teor['Anexos'] as $anexo) {
            if ($anexo['TipoAnexoManifestacao']['IdTipoAnexoManifestacao'] == 2) {
                $possuiAnexoResposta = true;

                $this->item('Nome do Arquivo', $anexo['NomeArquivo']);
                $this->item('Tipo de Anexo', $anexo['TipoAnexoManifestacao']['DescTipoAnexoManifestacao']);
                $this->item('Anexo Complementar', $anexo['IndComplementar'] ? 'Sim' : 'Não');
                $this->espacamento();
            }
        }

        if (!$possuiAnexoResposta) {
            $this->texto('Não há anexos de respostas.', true);
        }

        /**
         * Seção de recursos
         * 
         * Neste item importamos as seguintes opções de recursos:
         * - Pedido de Revisão
         * - Recurso de Primeira Instância
         * - Recurso de Segunda Instância
         */
        $this->secao('Recursos');
        if (count($recursos) > 0) {
            $reversedRecursos = array_reverse($recursos);

            foreach ($reversedRecursos as $recurso) {

                /**
                 * Somente gerará documento caso seja recursos 1ª ou 2ª instancia ou pedido de revisão,
                 * IdInstanciaRecurso = [1, 2, 6] conforme API FalaBR consultado dia 01/12/2020
                 * url: https://falabr.cgu.gov.br/Help
                 */
                if (in_array($recurso['instancia']['IdInstanciaRecurso'], [1, 2, 6])) {
                    $this->texto('Dados do Recurso -  ' . $recurso['instancia']['DescInstanciaRecurso'], true);

                    $this->item('Destinatário', $manifestacao['OuvidoriaDestino']['NomOuvidoria']);
                    $this->item('Data de abertura', $recurso['dataRecurso']);
                    $this->item('Prazo de Atendimento', $recurso['prazoAtendimento']);
                    $this->item('Tipo de Recurso', $recurso['tipoRecurso']['DescTipoRecurso']);
                    $this->item('Justificativa', $recurso['justificativa']);

                    // Anexos
                    $textoAnexos = '';
                    $anexosRecursos = $recurso['anexos'];
                    if (is_array($anexosRecursos) && count($anexosRecursos) > 0) {
                        foreach ($anexosRecursos as $anexoRecurso) {
                            $textoAnexos .= $anexoRecurso['nomeArquivo'] . "\n";
                        }
                    } else {
                        $textoAnexos = 'Recurso não possui anexos.';
                    }
                    $this->item('Anexos', $textoAnexos, true);

                    $this->espacamento();
                }
            }
        } else {
            $this->texto('Não há recursos.', true);
        }

        /**
         * Seção denúncia de descumprimento
         */
        $this->secao('Denúncia de Descumprimento');
        $historico = $manifestacao['Historico'];
        $possuiDenuncia = false;
        if (is_array($historico)) {
            foreach ($historico as $evento) {
                if (is_array($evento['Denuncia']) && $evento['Denuncia']['TxtFato']) {
                    $possuiDenuncia = true;

                    $this->texto($evento['Denuncia']['TxtFato']);
                    $this->espacamento();
                }
            }
        }
        if (!$possuiDenuncia) {
            $this->texto('Não há registro de denúncias de descumprimento.', true);
        }

        /**
         * Seção encaminhamento
         */
        $this->secao('Dados de Encaminhamento');
        $historico = $manifestacao['Historico'];
        $possuiEncaminhamento = false;
        if (is_array($historico)) {
            foreach ($historico as $evento) {
                if (is_array($evento['Encaminhamento'])) {
                    $possuiEncaminhamento = true;

                    $this->item('Órgão/Entidade de Origem',
                        $evento['Encaminhamento']['OuvidoriaOrigem']['NomOuvidoria']);
                    $this->item('Órgão/Entidade Destinatária',
                        $evento['Encaminhamento']['OuvidoriaDestino']['NomOuvidoria']);
                    $this->item('Mensagem ao Destinatário',
                        $evento['Encaminhamento']['TxtNotificacaoDestinatario']);
                    $this->item('Mensagem ao Cidadão',
                        $evento['Encaminhamento']['TxtNotificacaoSolicitante']);

                    $this->espacamento();
                }
            }
        }

        if (!$possuiEncaminhamento) {
            $this->texto('Não há registro de encaminhamentos.', true);
        }

        /**
         * Seção prorrogação
         */
        $this->secao('Dados de Prorrogação');
        $historico = $manifestacao['historico'];
        $possuiProrrogacao = false;
        if (is_array($historico)) {
            foreach ($historico as $evento) {
                if (is_array($evento['Prorrogacao'])) {
                    $possuiProrrogacao = true;

                    $this->item('Prazo Original',
                        $evento['Prorrogacao']['PrazoOriginal']);
                    $this->item('Novo Prazo',
                        $evento['Prorrogacao']['NovoPrazo']);
                    $this->item('Motivo',
                        $evento['Prorrogacao']['MotivoProrrogacaoManifestacao']['DescMotivoProrrogacaoManifestacao']);
                    $this->item('Justificativa', $evento['Prorrogacao']['TxtJustificativaProrrogacao']);

                    $this->espacamento();
                }
            }
        }

        if (!$possuiProrrogacao) {
            $this->texto('Não há registro de prorrogações.');
        }

        /**
         * Seção observações
         */
        if ($ocorreuErroAdicionarAnexo) {
            $this->secao('Observações');
            $this->texto('Um ou mais anexos da manifestação não foram '.
                'importados para o SEI devido a restrições da extensão '.
                'do arquivo.');
        }
    }

    public static function gerarPdf($retornoWsLinha, $retornoWsRecursos, $ocorreuErroAdicionarAnexo)
    {
        $pdf = new InfraPDF("P", "pt", "A4");

        $pdf->AddPage();

        /**
         * Arquivo PDF - manifestação e-Sic
         */

        // Cabeçalho
        $pdf->SetFont('arial', 'B', 18);
        $pdf->Cell(0, 5, "Plataforma Integrada de Ouvidoria e Acesso à Informação", 0, 1, 'C');
        $pdf->Ln(20);
        $pdf->Cell(0, 5, "Detalhes da Manifestação", 0, 1, 'C');
        $pdf->Ln(30);

        /**
         * Dados básicos da manifestação
         */
        $menu_count = 1;
        $pdf->SetFont('arial', 'B', 14);
        $pdf->Cell(0, 20, $menu_count . ". Dados Básicos da Manifestação", 1, 0, 'L');
        $pdf->Ln(30);

        // Tipo de Manifestação
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(180, 20, "Tipo da Manifestação:", 0, 0, 'R');
        $pdf->setFont('arial', '', 12);
        $pdf->Cell(0, 20, $retornoWsLinha['TipoManifestacao']['IdTipoManifestacao'] . " - " . $retornoWsLinha['TipoManifestacao']['DescTipoManifestacao'], 0, 1, 'L');

        // Tipo de formulario
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(180, 20, "Tipo de Formulário:", 0, 0, 'R');
        $pdf->setFont('arial', '', 12);
        $pdf->Cell(0, 20, $retornoWsLinha['TipoFormulario']['DescTipoFormulario'], 0, 1, 'L');

        // NUP
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(180, 20, "NUP:", 0, 0, 'R');
        $pdf->setFont('arial', '', 12);
        $pdf->Cell(0, 20, $retornoWsLinha['NumerosProtocolo'][0], 0, 1, 'L');

        // Esfera
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(180, 20, "Esfera:", 0, 0, 'R');
        $pdf->setFont('arial', '', 12);
        $pdf->Cell(0, 20, $retornoWsLinha['OuvidoriaDestino']['Esfera']['DescEsfera'], 0, 1, 'L');

        // Órgão Destinatário - NomeOuvidoria
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(180, 20, "Órgão Destinatário:", 0, 0, 'R');
        $pdf->setFont('arial', '', 12);
        $pdf->MultiCell(0, 20, $retornoWsLinha['OuvidoriaDestino']['NomOuvidoria'], 0, 'L');

        // Órgão de Interesse
        if ($retornoWsLinha['OrgaoInteresse'] && $retornoWsLinha['OrgaoInteresse']['NomeOrgao']) {
            $pdf->SetFont('arial', 'B', 12);
            $pdf->Cell(180, 20, "Órgão de Interesse:", 0, 0, 'R');
            $pdf->setFont('arial', '', 12);
            $pdf->Cell(0, 20, $retornoWsLinha['OrgaoInteresse']['NomeOrgao'], 0, 1, 'L');
        }

        // Assunto
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(180, 20, "Assunto:", 0, 0, 'R');
        $pdf->setFont('arial', '', 12);
        $pdf->Cell(0, 20, isset($retornoWsLinha['Assunto']['DescAssunto']) ? $retornoWsLinha['Assunto']['DescAssunto'] : '', 0, 1, 'L');

        // SubAssunto
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(180, 20, "SubAssunto:", 0, 0, 'R');
        if (is_array($retornoWsLinha['SubAssunto']) && isset($retornoWsLinha['SubAssunto']['DescSubAssunto'])) {
            $subAssunto = $retornoWsLinha['SubAssunto']['DescSubAssunto'];
        } else {
            $subAssunto = '';
        }
        $pdf->setFont('arial', '', 12);
        $pdf->Cell(0, 20, $subAssunto, 0, 1, 'L');

        // Data Cadastro
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(180, 20, "Data do Cadastro:", 0, 0, 'R');
        $pdf->setFont('arial', '', 12);
        $pdf->Cell(0, 20, $retornoWsLinha['DataCadastro'], 0, 1, 'L');

        // Situação
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(180, 20, "Situação:", 0, 0, 'R');
        $pdf->setFont('arial', '', 12);
        $pdf->Cell(0, 20, $retornoWsLinha['Situacao']['DescSituacaoManifestacao'], 0, 1, 'L');

        // Registrado por
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(180, 20, "Registrado por:", 0, 0, 'R');
        $pdf->setFont('arial', '', 12);
        $pdf->Cell(0, 20, $retornoWsLinha['RegistradoPor'], 0, 1, 'L');

        // Data limite para resposta
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(180, 20, "Data limite para resposta:", 0, 0, 'R');
        $pdf->setFont('arial', '', 12);
        $pdf->Cell(70, 20, $retornoWsLinha['PrazoAtendimento'], 0, 1, 'L');

        // Canal Entrada
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(180, 20, "Canal de Entrada:", 0, 0, 'R');
        $pdf->setFont('arial', '', 12);
        $pdf->Cell(70, 20, $retornoWsLinha['CanalEntrada']['IdCanalEntrada'] . " - " . $retornoWsLinha['CanalEntrada']['DescCanalEntrada'], 0, 1, 'L');

        // Modo de Resposta
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(180, 20, "Modo de Resposta:", 0, 0, 'R');
        $pdf->setFont('arial', '', 12);
        $pdf->Cell(70, 20, $retornoWsLinha['ModoResposta']['DescModoResposta'], 0, 1, 'L');

        // Serviço
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(180, 20, "Serviço:", 0, 0, 'R');
        $pdf->setFont('arial', '', 12);
        $pdf->Cell(70, 20, $retornoWsLinha['Servico'], 0, 1, 'L');

        /**
         * Dados básicos da manifestação
         */
        $menu_count++;
        $pdf->Ln(30);
        $pdf->SetFont('arial', 'B', 14);
        $pdf->Cell(0, 20, $menu_count . ". Teor da Manifestação", 1, 0, 'L');
        $pdf->Ln(30);

        // Resumo
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(180, 20, "Resumo:", 0, 0, 'R');
        $pdf->setFont('arial', '', 12);
        $pdf->MultiCell(0, 20, $retornoWsLinha['ResumoSolicitacao'], 0, 'J');

        // Extrato
        $pdf->SetFont('arial', 'B', 12);
        $pdf->Cell(180, 20, "Extrato:", 0, 0, 'R');
        $pdf->setFont('arial', '', 12);
        $pdf->MultiCell(0, 20, $retornoWsLinha['Teor']['DescricaoAtosOuFatos'], 0, 'J');

        /**
         * Anexos
         *
         * - IdTipoAnexoManifestacao : DescTipoAnexoManifestacao
         * - 1 : "Anexo Manifestação"
         * - 2 : "Anexo Resposta"
         */
        // Anexos
        $menu_count++;
        $pdf->Ln(30);
        $pdf->SetFont('arial', 'B', 14);
        $pdf->Cell(0, 20, $menu_count . ". Anexo(s) do Pedido Inicial", true, 0, 'L');
        $pdf->Ln(30);

        $anexos = $retornoWsLinha['Teor']['Anexos'];
        if (count($anexos) > 0) {

            $anexo_tipo_original = 0;
            $anexo_tipo_complementar = 0;

            foreach ($anexos as $anexo) {

                if ($anexo['TipoAnexoManifestacao']['IdTipoAnexoManifestacao'] == 1) {
                    if ($anexo['IndComplementar'] == false) {
                        $anexo_tipo_original++;
                    } else {
                        $anexo_tipo_complementar++;
                    }

                    // Nome do Arquivo
                    $pdf->SetFont('arial', 'B', 12);
                    $pdf->Cell(180, 20, "Nome do arquivo:", 0, 0, 'R');
                    $pdf->setFont('arial', '', 12);
                    $pdf->Cell(70, 20, $anexo['NomeArquivo'], 0, 1, 'L');

                    // Tipo de Anexo
                    $pdf->SetFont('arial', 'B', 12);
                    $pdf->Cell(180, 20, "Tipo de Anexo:", 0, 0, 'R');
                    $pdf->setFont('arial', '', 12);
                    $pdf->Cell(70, 20, $anexo['TipoAnexoManifestacao']['DescTipoAnexoManifestacao'], 0, 1, 'L');

                    // Anexo Complementar
                    $pdf->SetFont('arial', 'B', 12);
                    $pdf->Cell(180, 20, "Anexo Complementar:", 0, 0, 'R');
                    $pdf->setFont('arial', '', 12);
                    $pdf->Cell(70, 20, $anexo['IndComplementar'] ? 'sim' : 'não', 0, 1, 'L');

                    $pdf->Ln(20);
                }
            }
        }
        if ($anexo_tipo_original == 0) {
            // Sem anexo original
            $pdf->SetFont('arial', 'B', 12);
            $pdf->Cell(180, 20, "Não há anexos originais da manifestação.", 0, 0, 'L');
            $pdf->Ln(20);
        }
        if ($anexo_tipo_complementar == 0) {
            // Sem anexo complementar
            $pdf->SetFont('arial', 'B', 12);
            $pdf->Cell(180, 20, "Não há anexos complementares.", 0, 0, 'L');
            $pdf->Ln(20);
        }

        /**
         * Campos Adicionais
         */
        $menu_count++;
        $pdf->Ln(30);
        $pdf->SetFont('arial', 'B', 14);
        $pdf->Cell(0, 20, $menu_count . ". Campos Adicionais", true, 0, 'L');
        $pdf->Ln(30);

        $campos_adicionais = $retornoWsLinha['Teor']['CamposAdicionaisManifestacao'];
        if (count($campos_adicionais) > 0) {

            foreach ($campos_adicionais as $campo_adicional) {

                // Campo - NomeExibido: Valor
                $pdf->SetFont('arial', 'B', 12);
                $pdf->Cell(180, 20, $campo_adicional['IdCampoAdicional'] . '. ' . $campo_adicional['Nome'] . ':', 0, 0, 'R');
                $pdf->setFont('arial', '', 12);
                $pdf->Cell(70, 20, $campo_adicional['Valor'], 0, 1, 'L');
                $pdf->Ln(20);
            }
        } else {
            // Sem envolvidos
            $pdf->SetFont('arial', 'B', 12);
            $pdf->Cell(180, 20, "Não há campos adicionais.", 0, 0, 'L');
            $pdf->Ln(20);
        }

        /**
         * Respostas
         */
        $menu_count++;
        $pdf->Ln(30);
        $pdf->SetFont('arial', 'B', 14);
        $pdf->Cell(0, 20, $menu_count . ". Resposta(s)", true, 0, 'L');
        $pdf->Ln(30);

        $historicos = $retornoWsLinha['Historico'];
        if (count($historicos) > 0) {
            $i = 1;
            foreach ($historicos as $historico) {
                if ($historico['HistoricoAcao']['DescTipoAcaoManifestacao'] == 'Registro Resposta') {

                    $pdf->SetFont('arial', 'B', 12);
                    $pdf->Cell(0, 20, "Resposta " . $i, true, 1, 'L');

                    // Tipo de Resposta
                    $pdf->SetFont('arial', 'B', 12);
                    $pdf->Cell(180, 20, "Tipo de Resposta:", 0, 0, 'R');
                    $pdf->setFont('arial', '', 12);
                    $pdf->Cell(70, 20, $historico['Resposta']['TipoRespostaManifestacao']['DescTipoRespostaManifestacao'], 0, 1, 'L');

                    // Data e Hora
                    $pdf->SetFont('arial', 'B', 12);
                    $pdf->Cell(180, 20, "Data e hora:", 0, 0, 'R');
                    $pdf->setFont('arial', '', 12);
                    $pdf->Cell(70, 20, $historico['HistoricoAcao']['DataHoraAcao'], 0, 1, 'L');

                    // Decisão
                    $pdf->SetFont('arial', 'B', 12);
                    $pdf->Cell(180, 20, "Decisão:", 0, 0, 'R');
                    $pdf->setFont('arial', '', 12);
                    $pdf->Cell(70, 20, $historico['Resposta']['Decisao']['descricaoDecisao'], 0, 1, 'L');

                    // Teor da Resposta
                    $pdf->SetFont('arial', 'B', 12);
                    $pdf->Cell(180, 20, "Teor da Resposta:", 0, 0, 'R');
                    $pdf->setFont('arial', '', 12);
                    $pdf->MultiCell(0, 20, $historico['Resposta']['TxtResposta'], 0, 'L');

                    $pdf->Ln(20);

                    $i++;
                }
            }
        }

        if (!count($historicos) > 0 || (isset($i) && $i == 1)) {
            // Sem respostas
            $pdf->SetFont('arial', 'B', 12);
            $pdf->Cell(180, 20, "Não há registro de respostas.", 0, 0, 'L');
            $pdf->Ln(20);
        }

        /**
         * Anexo das Respostas
         */
        $menu_count++;
        $pdf->Ln(30);
        $pdf->SetFont('arial', 'B', 14);
        $pdf->Cell(0, 20, $menu_count . ". Anexos das Respostas", true, 0, 'L');
        $pdf->Ln(30);

        $anexos = $retornoWsLinha['Teor']['Anexos'];
        if (count($anexos) > 0) {

            foreach ($anexos as $anexo) {

                if ($anexo['TipoAnexoManifestacao']['IdTipoAnexoManifestacao'] == 2) {

                    $possui_anexo_resposta = true;

                    // Nome do Arquivo
                    $pdf->SetFont('arial', 'B', 12);
                    $pdf->Cell(180, 20, "Nome do arquivo:", 0, 0, 'R');
                    $pdf->setFont('arial', '', 12);
                    $pdf->Cell(70, 20, $anexo['NomeArquivo'], 0, 1, 'L');

                    // Tipo de Anexo
                    $pdf->SetFont('arial', 'B', 12);
                    $pdf->Cell(180, 20, "Tipo de Anexo:", 0, 0, 'R');
                    $pdf->setFont('arial', '', 12);
                    $pdf->Cell(70, 20, $anexo['TipoAnexoManifestacao']['DescTipoAnexoManifestacao'], 0, 1, 'L');

                    // Anexo Complementar
                    $pdf->SetFont('arial', 'B', 12);
                    $pdf->Cell(180, 20, "Anexo Complementar:", 0, 0, 'R');
                    $pdf->setFont('arial', '', 12);
                    $pdf->Cell(70, 20, $anexo['IndComplementar'] ? 'sim' : 'não', 0, 1, 'L');

                    $pdf->Ln(20);
                }
            }
        }
        if (!isset($possui_anexo_resposta)) {
            // Sem anexo resposta
            $pdf->SetFont('arial', 'B', 12);
            $pdf->Cell(180, 20, "Não há anexos de respostas.", 0, 0, 'L');
            $pdf->Ln(20);
        }

        /**
         * Recursos
         *
         * Neste item importamos as seguintes opções de recursos:
         * - Pedido de Revisão
         * - Recurso de Primeira Instância
         * - Recurso de Segunda Instãncia
         */
        if (count($retornoWsRecursos) > 0) {
            $menu_count++;
            $pdf->Ln(30);
            $pdf->SetFont('arial', 'B', 14);
            $pdf->Cell(0, 20, $menu_count . ". Recursos", true, 0, 'L');
            $pdf->Ln(30);

            $recursos = $retornoWsRecursos;

            if (count($recursos) > 0) {

                $reversedRecursos = array_reverse($recursos);

                foreach ($reversedRecursos as $recurso) {

                    /**
                     * Somente gerará documento caso seja recursos 1ª ou 2ª instancia ou pedido de revisão,
                     * IdInstanciaRecurso = [1, 2, 6] conforme API FalaBR consultado dia 01/12/2020
                     * url: https://falabr.cgu.gov.br/Help
                     */
                    if (in_array($recurso['instancia']['IdInstanciaRecurso'], [1, 2, 6])) {
                        $pdf->SetFont('arial', 'B', 12);
                        $pdf->Cell(0, 20, "Dados do Recurso -  " . $recurso['instancia']['DescInstanciaRecurso'], true, 1, 'L');

                        // Destinatário
                        $pdf->SetFont('arial', 'B', 12);
                        $pdf->Cell(180, 20, "Destinatário:", 0, 0, 'R');
                        $pdf->setFont('arial', '', 12);
                        $pdf->Cell(70, 20, $retornoWsLinha['OuvidoriaDestino']['NomOuvidoria'], 0, 1, 'L');

                        // Data de Abertura
                        $pdf->SetFont('arial', 'B', 12);
                        $pdf->Cell(180, 20, "Data de abertura:", 0, 0, 'R');
                        $pdf->setFont('arial', '', 12);
                        $pdf->Cell(70, 20, $recurso['dataRecurso'], 0, 1, 'L');

                        // Prazo de Atendimento
                        $pdf->SetFont('arial', 'B', 12);
                        $pdf->Cell(180, 20, "Prazo de Atendimento:", 0, 0, 'R');
                        $pdf->setFont('arial', '', 12);
                        $pdf->Cell(70, 20, $recurso['prazoAtendimento'], 0, 1, 'L');

                        // Tipo Recurso
                        $pdf->SetFont('arial', 'B', 12);
                        $pdf->Cell(180, 20, "Tipo de Recurso:", 0, 0, 'R');
                        $pdf->setFont('arial', '', 12);
                        $pdf->Cell(70, 20, $recurso['tipoRecurso']['DescTipoRecurso'], 0, 1, 'L');

                        // Justificativa
                        $pdf->SetFont('arial', 'B', 12);
                        $pdf->Cell(180, 20, "Justificativa:", 0, 0, 'R');
                        $pdf->setFont('arial', '', 12);
                        $pdf->MultiCell(0, 20, $recurso['justificativa'], 0, 'J');

                        // Anexos
                        $pdf->SetFont('arial', 'B', 12);
                        $pdf->Cell(180, 20, "Anexos:", 0, 0, 'R');
                        $pdf->setFont('arial', '', 12);
                        $anexosRecursos = $recurso['anexos'];
                        if (is_array($anexosRecursos) && count($anexosRecursos) > 0) {
                            $pdf->Cell(70, 20, ' ', 0, 1, 'L');
                            foreach ($anexosRecursos as $anexoRecurso) {
                                $pdf->Cell(180, 20, '-', 0, 0, 'R');
                                $pdf->Cell(70, 20, $anexoRecurso['nomeArquivo'], 0, 1, 'L');
                            }
                        } else {
                            $pdf->Cell(70, 20, 'Não possui anexos', 0, 1, 'L');
                        }

                        $pdf->Ln(20);
                    }
                }
            }
        }

        /**
         * Denúncia de Descumprimento
         */
        $menu_count++;
        $pdf->Ln(30);
        $pdf->SetFont('arial', 'B', 14);
        $pdf->Cell(0, 20, $menu_count . " Denúncia de Descumprimento", true, 0, 'L');
        $pdf->Ln(30);

        $denuncias = $retornoWsLinha['Historico'];
        if (count($denuncias) > 0) {
            foreach ($denuncias as $denuncia) {
                if ($denuncia['Denuncia']['TxtFato'] <> '') {

                    $possui_denuncia = true;

                    // Denúncia
                    $pdf->setFont('arial', '', 12);
                    $pdf->MultiCell(0, 20, $denuncia['HistoricoAcao']['Denuncia']['TxtFato'], 0, 'L');

                    $pdf->Ln(20);
                }
            }
        }
        if (!count($denuncias) > 0 || !isset($possui_denuncia)) {
            // Sem respostas
            $pdf->SetFont('arial', 'B', 12);
            $pdf->Cell(180, 20, "Não há registro de denúncias de descumprimento.", 0, 0, 'L');
            $pdf->Ln(20);
        }

        /**
         * Encaminhamento
         */
        $menu_count++;
        $pdf->Ln(30);
        $pdf->SetFont('arial', 'B', 14);
        $pdf->Cell(0, 20, $menu_count . ". Dados de Encaminhamento", true, 0, 'L');
        $pdf->Ln(30);

        $encaminhamentos = $retornoWsLinha['Historico'];
        if (count($encaminhamentos) > 0) {
            foreach ($encaminhamentos as $encaminhamento) {
                if (isset($encaminhamento['Encaminhamento'])) {

                    $possui_denuncia = true;

                    // Órgão Origem
                    $pdf->SetFont('arial', 'B', 12);
                    $pdf->Cell(180, 20, "Órgão/Entidade de Origem:", 0, 0, 'R');
                    $pdf->setFont('arial', '', 12);
                    $pdf->MultiCell(0, 20, $encaminhamento['Encaminhamento']['OuvidoriaOrigem']['NomOuvidoria'], 0, 'L');

                    // Órgão Destino
                    $pdf->SetFont('arial', 'B', 12);
                    $pdf->Cell(180, 20, "Órgão/Entidade Destinatária:", 0, 0, 'R');
                    $pdf->setFont('arial', '', 12);
                    $pdf->MultiCell(0, 20, $encaminhamento['Encaminhamento']['OuvidoriaDestino']['NomOuvidoria'], 0, 'L');

                    // Mensagem ao Destinatário
                    $pdf->SetFont('arial', 'B', 12);
                    $pdf->Cell(180, 20, "Mensagem ao Destinatário:", 0, 0, 'R');
                    $pdf->setFont('arial', '', 12);
                    $pdf->MultiCell(0, 20, $encaminhamento['Encaminhamento']['TxtNotificacaoDestinatario'], 0, 'L');

                    // Mensagem ao Cidadão
                    $pdf->SetFont('arial', 'B', 12);
                    $pdf->Cell(180, 20, "Mensagem ao Cidadão:", 0, 0, 'R');
                    $pdf->setFont('arial', '', 12);
                    $pdf->MultiCell(0, 20, $encaminhamento['Encaminhamento']['TxtNotificacaoSolicitante'], 0, 'L');

                    $pdf->Ln(20);
                }
            }
        }
        if (!count($encaminhamentos) > 0 || !isset($possui_denuncia)) {
            // Sem respostas
            $pdf->SetFont('arial', 'B', 12);
            $pdf->Cell(180, 20, "Não há registro de encaminhamentos.", 0, 0, 'L');
            $pdf->Ln(20);
        }

        /**
         * Prorrogação
         */
        $menu_count++;
        $pdf->Ln(30);
        $pdf->SetFont('arial', 'B', 14);
        $pdf->Cell(0, 20, $menu_count . ". Dados de Prorrogação", true, 0, 'L');
        $pdf->Ln(30);

        $prorrogacoes = $retornoWsLinha['Historico'];
        if (count($prorrogacoes) > 0) {
            foreach ($prorrogacoes as $prorrogacao) {
                if ($prorrogacao['Prorrogacao'] <> '') {

                    $possui_prorrogacao = true;

                    // Prazo Original
                    $pdf->SetFont('arial', 'B', 12);
                    $pdf->Cell(180, 20, "Prazo Original:", 0, 0, 'R');
                    $pdf->setFont('arial', '', 12);
                    $pdf->Cell(70, 20, $prorrogacao['Prorrogacao']['PrazoOriginal'], 0, 1, 'L');

                    // Novo Prazo
                    $pdf->SetFont('arial', 'B', 12);
                    $pdf->Cell(180, 20, "Novo Prazo:", 0, 0, 'R');
                    $pdf->setFont('arial', '', 12);
                    $pdf->Cell(70, 20, $prorrogacao['Prorrogacao']['NovoPrazo'], 0, 1, 'L');

                    // Motivo da Prorrogação
                    $pdf->SetFont('arial', 'B', 12);
                    $pdf->Cell(180, 20, "Motivo:", 0, 0, 'R');
                    $pdf->setFont('arial', '', 12);
                    $pdf->Cell(70, 20, $prorrogacao['Prorrogacao']['MotivoProrrogacaoManifestacao']['DescMotivoProrrogacaoManifestacao'], 0, 1, 'L');

                    // Justificativa da Prorrogação
                    $pdf->SetFont('arial', 'B', 12);
                    $pdf->Cell(180, 20, "Justificativa:", 0, 0, 'R');
                    $pdf->setFont('arial', '', 12);
                    $pdf->MultiCell(0, 20, $prorrogacao['Prorrogacao']['TxtJustificativaProrrogacao'], 0, 'L');

                    $pdf->Ln(20);
                }
            }
        }
        if (!count($prorrogacoes) > 0 || !isset($possui_prorrogacao)) {
            // Sem respostas
            $pdf->SetFont('arial', 'B', 12);
            $pdf->Cell(180, 20, "Não há registro de prorrogações.", 0, 0, 'L');
            $pdf->Ln(20);
        }

        /**
         * Observações finais
         */
        if ($ocorreuErroAdicionarAnexo == true) {
            $pdf->Ln(20);
            $pdf->SetFont('arial', 'B', 14);
            $pdf->Cell(70, 20, "12. Observações:", 0, 0, 'L');
            $pdf->Ln(20);

            $pdf->SetFont('arial', '', 12);
            $pdf->MultiCell(0, 20, "Um ou mais anexos da manifestação não foram importados para o SEI devido a restrições da extensão do arquivo. Acesse a manifestação através do link abaixo para mais detalhes. ", 0, 'J');
        }
        return $pdf;
    }

}
