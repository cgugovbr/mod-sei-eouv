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
        parent::__construct();

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
        $this->item('Resumo:', $manifestacao['ResumoSolicitacao'], false, 'R');
        $this->item('Teor:', $teor['DescricaoAtosOuFatos'], false, 'R');
        $this->item('Proposta de melhoria:', $teor['PropostaMelhoria'] ?? '', false, 'R');
        $this->item('Município do local do fato:', $teor['LocalFato']['Municipio']['DescMunicipio'] ?? '', false, 'R');
        $this->item('UF do local do fato:', $teor['LocalFato']['Municipio']['Uf']['SigUf'] ?? '', false, 'R');
        $this->item('Local:', $teor['LocalFato']['DescricaoLocalFato'] ?? '', false, 'R');
        $this->espacamento();

        // Anexos
        $anexosOriginais = [];
        $anexosComplementares = [];
        if (is_array($teor['Anexos'])) {
            foreach ($teor['Anexos'] as $anexo) {
                if ($anexo['TipoAnexoManifestacao']['IdTipoAnexoManifestacao'] == 1) {
                    if ($anexo['IndComplementar']) {
                        $anexosComplementares[] = $anexo;
                    } else {
                        $anexosOriginais[] = $anexo;
                    }
                }
            }
        }

        if (count($anexosOriginais) > 0) {
            $itens = [];
            foreach ($anexosOriginais as $anexo) {
                $itens[] = [$anexo['NomeArquivo']];
            }
            $this->tabela($itens, ['Anexos Originais']);
        } else {
            $this->texto('Não há anexos originais da manifestação.');
        }
        $this->espacamento();

        if (count($anexosComplementares) > 0) {
            $itens = [];
            foreach ($anexosComplementares as $anexo) {
                $itens[] = [$anexo['NomeArquivo']];
            }
            $this->tabela($itens, ['Anexos Complementares']);
        } else {
            $this->texto('Não há anexos complementares.');
        }
        $this->espacamento();

        // Envolvidos
        $envolvidos = $teor['EnvolvidosManifestacao'];
        if (is_array($envolvidos) && count($envolvidos) > 0) {
            $itens = [];
            foreach ($envolvidos as $envolvido) {
                $itens[] = [
                    $envolvido['Nome'] ?? '',
                    $envolvido['Funcao'] ?? '',
                    $envolvido['CpfEnvolvido'] ?? '',
                    $envolvido['Orgao'] ?? '',
                ];
            }
            $this->tabela($itens, ['Nome', 'Função', 'CPF', 'Órgão/Empresa'], 'Envolvidos');
        } else {
            $this->texto('Não há envolvidos na manifestação.');
        }
        $this->espacamento();

        /**
         * Seção campos adicionais
         */
        $this->secao('Campos Adicionais');
        $camposAdicionais = $teor['CamposAdicionaisManifestacao'];
        if (is_array($camposAdicionais) && count($camposAdicionais) > 0) {
            $dados = [];
            foreach ($camposAdicionais as $campo) {
                $dados[] = [
                    $campo['NomeExibido'],
                    $campo['Valor'],
                ];
            }
            $this->tabela($dados);
        } else {
            $this->texto('Não há campos adicionais.');
        }

        /**
         * Seção Dados das Respostas
         */
        $this->secao('Dados das Respostas');
        if (is_array($manifestacao['InformacoesAdicionais'])) {
            $info = $manifestacao['InformacoesAdicionais'];
            $dados = [];
            if ($info['EnvolveCargoComissionadoDAS4OuSuperior']) {
                $dados[] = [
                    'Envolve ocupante de cargo comissionado DAS a partir do nível 4 ou equivalente?',
                    $info['EnvolveCargoComissionadoDAS4OuSuperior'],
                ];
            }
            if ($info['Apta']) {
                $dados[] = [
                    'Manifestação Apta?',
                    $info['Apta'],
                ];
            }
            if ($info['EnvolveEmpresa']) {
                $dados[] = [
                    'Há envolvimento de Empresa?',
                    $info['EnvolveEmpresa'],
                ];
            }
            if ($info['EnvolveServidorPublico']) {
                $dados[] = [
                    'Há envolvimento de Servidor Público?',
                    $info['EnvolveServidorPublico'],
                ];
            }
            if (count($dados) > 0) {
                $this->tabela($dados);
            }
        }

        /**
         * Respostas
         */
        $historico = $manifestacao['Historico'];
        if (is_array($historico)) {
            foreach ($historico as $evento) {
                if ($evento['HistoricoAcao']['DescTipoAcaoManifestacao'] == 'Registro Resposta') {
                    $this->secao('Resposta - ' . $evento['HistoricoAcao']['DataHoraAcao']);

                    $this->item('Tipo de Resposta', $evento['Resposta']['TipoRespostaManifestacao']['DescTipoRespostaManifestacao'] ?? '');
                    $this->item('Data/Hora', $evento['HistoricoAcao']['DataHoraAcao'] ?? '');
                    $this->item('Teor da Resposta', $evento['Resposta']['TxtResposta'] ?? '');
                    $this->item('Decisão', $evento['Resposta']['Decisao']['descricaoDecisao'] ?? '');
                    $this->item('Especificação da Decisão', $evento['Resposta']['EspecificacaoDecisao']['DescClassificacaoAvaliacaoManifestacao'] ?? '');
                    $this->item('Compromisso', $evento['Resposta']['DataCompromisso'] ?? '');

                    $nomesDosAnexos = [];
                    foreach ($teor['Anexos'] as $anexo) {
                        if ($anexo['IdObjeto'] == $evento['Resposta']['IdRespostaManifestacao']) {
                            $nomesDosAnexos[] = $anexo['NomeArquivo'];
                        }
                    }
                    $this->item('Anexos', implode("\n", $nomesDosAnexos));
                }
            }
        }

        /**
         * Seção de recursos
         * 
         * Neste item importamos as seguintes opções de recursos:
         * - Pedido de Revisão
         * - Recurso de Primeira Instância
         * - Recurso de Segunda Instância
         * - Recursos para CGU ou Terceira Instância
         */
        if (count($recursos) > 0) {
            foreach ($recursos as $recurso) {

                /**
                 * Somente gerará documento caso seja recursos 1ª, 2ª ou 3ª instancia ou pedido de revisão,
                 * url: https://falabr.cgu.gov.br/Help
                 */
                if (in_array($recurso['instancia']['IdInstanciaRecurso'], [1, 2, 3, 6, 7])) {
                    $this->secao('Dados do Recurso - ' . $recurso['instancia']['DescInstanciaRecurso']);

                    $this->item('Destinatário', $manifestacao['OuvidoriaDestino']['NomOuvidoria'] ?? '');
                    $this->item('Data de Abertura', $recurso['dataRecurso'] ?? '');
                    $this->item('Prazo de Atendimento', $recurso['prazoAtendimento'] ?? '');
                    $this->item('Tipo de Recurso', $recurso['tipoRecurso']['DescTipoRecurso'] ?? '');
                    $this->item('Origem da Solicitação', $recurso['origemSolicitacao']['descOrigemSolicitacao'] ?? '');
                    $this->item('Justificativa', $recurso['justificativa'] ?? '', true);

                    $nomesDosAnexos = [];
                    $anexosRecursos = $recurso['anexos'];
                    if (is_array($anexosRecursos)) {
                        foreach ($anexosRecursos as $anexoRecurso) {
                            $nomesDosAnexos[] = $anexoRecurso['nomeArquivo'];
                        }
                    }
                    $this->item('Anexos', implode("\n", $nomesDosAnexos));

                    if (is_array($recurso['respostasRecurso'])) {
                        foreach ($recurso['respostasRecurso'] as $resposta) {
                            $this->secao('Resposta do Recurso - ' . $recurso['instancia']['DescInstanciaRecurso']);

                            $this->item('Data da Resposta', $resposta['datRegistroResposta'] ?? '');
                            //$this->item('Prazo para disponibilizar informação', '');
                            $this->item('Tipo de Resposta', $resposta['tipoRespostaRecurso']['descTipoRespostaRecurso'] ?? '');
                            $this->item('Informação Recursal', $resposta['txtInformacaoRecursal'] ?? '');
                            $this->item('Justificativa', $resposta['txtJustificativa'] ?? '', true);
                            $this->item('Responsável pela Resposta', $resposta['responsavelResposta'] ?? '');
                            $this->item('Destinatário do Recurso da Próxima Instância', $resposta['destinatarioRecursoProximaInstancia'] ?? '');
                            $this->item('Prazo limite para recurso', $resposta['datPrazoRecurso'] ?? '');
                            // $this->item('Contém informações pessoais ou protegidas por outras hipóteses de sigilo?', '')

                            $nomesDosAnexos = [];
                            if (is_array($resposta['anexos'])) {
                                foreach ($resposta['anexos'] as $anexoResposta) {
                                    $nomesDosAnexos[] = $anexoResposta['nomeArquivo'];
                                }
                            }
                            $this->item('Anexos', implode("\n", $nomesDosAnexos));
                        }
                    }
                }
            }
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

                    $this->item('Data/Hora', $evento['Denuncia']['Data']);
                    $this->item('Teor', $evento['Denuncia']['TxtFato']);
                    $this->espacamento();
                }
            }
        }
        if (!$possuiDenuncia) {
            $this->texto('Não há registro de denúncias de descumprimento.');
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
