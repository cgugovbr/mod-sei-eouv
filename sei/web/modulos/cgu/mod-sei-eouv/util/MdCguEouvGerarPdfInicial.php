<?php

/**
 * CONTROLADORIA-GERAL DA UNIÃO - CGU
 *
 * Classe que gera PDFs com as informações das manifestações
 * de ouvidoria importadas do FalaBR
 */

require_once __DIR__ . '/MdCguEouvGerarPdf.php';

class MdCguEouvGerarPdfInicial extends MdCguEouvGerarPdf
{
    public function __construct($manifestacao, $importarDadosDoManifestante, $ocorreuErroAdicionarAnexo)
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
        $this->secao('Dados do Solicitante');
        if ($importarDadosDoManifestante) {
            if (is_array($manifestacao['Manifestante'])) {
                // Manifestante identificado
                $pessoa = $manifestacao['Manifestante'];

                $this->item('Nome do Solicitante', $pessoa['Nome']);
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

                if (is_array($pessoa['Endereco'])) {
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
                        $endereco .= "\n" . $pessoa['Endereco']['CEP'];
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

            $this->item('Local', $localFato['DescricaoLocalFato']);
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
            $this->text('Não há campos adicionais.', true);
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