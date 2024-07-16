<?php

/**
 * CONTROLADORIA-GERAL DA UNIÃO - CGU
 *
 * Classe que gera PDFs com as informações das manifestações
 * de acesso à informação importadas do FalaBR
 */

 require_once __DIR__ . '/MdCguEouvGerarPdf.php';

class MdCguEouvGerarPdfLai extends MdCguEouvGerarPdf
{
    public function __construct($manifestacao, $recursos, $importarDadosDoManifestante, $ocorreuErroAdicionarAnexo)
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
         * Seção dados do manifestante
         */
        $this->secaoDadosManifestante($manifestacao, $importarDadosDoManifestante);

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
            foreach ($recursos as $recurso) {

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
            $this->texto('Não há registro de prorrogações.', true);
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
}
