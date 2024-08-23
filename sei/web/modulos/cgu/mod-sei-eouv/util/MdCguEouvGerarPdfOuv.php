<?php

/**
 * CONTROLADORIA-GERAL DA UNIÃO - CGU
 *
 * Classe que gera PDFs com as informações das manifestações
 * de ouvidoria importadas do FalaBR
 */

require_once __DIR__ . '/MdCguEouvGerarPdf.php';

class MdCguEouvGerarPdfOuv extends MdCguEouvGerarPdf
{
    public function __construct($manifestacao, $pedidoRevisao, $importarDadosDoManifestante, $ocorreuErroAdicionarAnexo)
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
         * Seção do fato da manifestação
         */
        $this->secao('Fato da Manifestação');
        $teor = $manifestacao['Teor'];
        if (is_array($teor['LocalFato'])) {
            $localFato = $teor['LocalFato'];
            if (is_array($localFato['Municipio'])) {
                $this->item('Município/UF', $localFato['Municipio']['DescMunicipio'] . ' - ' .
                    $localFato['Municipio']['Uf']['SigUf']);
            }

            if ($localFato['DescricaoLocalFato']) {
                $this->item('Local', $localFato['DescricaoLocalFato']);
            }
        }

        $this->item('Descrição', $teor['DescricaoAtosOuFatos'], true);

        $envolvidos = $teor['EnvolvidosManifestacao'];
        if (is_array($envolvidos)) {
            $textoEnvolvidos = '';
            foreach ($envolvidos as $envolvido) {
                $textoEnvolvidos .= 'Função: ' . $envolvido['IdFuncaoEnvolvidoManifestacao'] . ' - ' . $envolvido['Funcao'] . "\n";
                $textoEnvolvidos .= 'Nome: ' . $envolvido['Nome'] . "\n";
                $textoEnvolvidos .= 'Órgão: ' . $envolvido['Orgao'] . "\n\n";
            }
            $this->item('Envolvidos', $textoEnvolvidos, true);
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
         * Seção Pedido de Revisão
         */
        if ($pedidoRevisao && is_array($pedidoRevisao['instancia']) &&
                $pedidoRevisao['instancia']['IdInstanciaRecurso'] == 6) {
            $this->secao('Pedido de Revisão');

            $this->item('Data de abertura', $pedidoRevisao['dataRecurso']);
            $this->item('Prazo de Atendimento', $pedidoRevisao['prazoAtendimento']);
            $this->item('Tipo', $pedidoRevisao['tipoRecurso']['DescTipoRecurso']);
            $this->item('Justificativa', $pedidoRevisao['justificativa']);

            // Anexos
            $textoAnexos = '';
            $anexosPedido = $pedidoRevisao['anexos'];
            if (is_array($anexosPedido) && count($anexosPedido) > 0) {
                foreach ($anexosPedido as $anexo) {
                    $textoAnexos .= $anexo['nomeArquivo'] . "\n";
                }
            } else {
                $textoAnexos = 'Pedido não possui anexos.';
            }
            $this->item('Anexos', $textoAnexos, true);
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