<?php

function echoln($msg, $erro = false) {
    if ($erro) {
        echo "\e[31m".$msg."\e[0m".PHP_EOL;
    } else {
        echo $msg.PHP_EOL;
    }
}

function dump($var) {
    echoln(var_export($var, true));
}

$SEI_DIR = getenv('SEI_DIR');
if ($SEI_DIR === false) {
    echoln('Defina a variável de ambiente SEI_DIR para a pasta onde o SEI está instalado', true);
    exit(1);
}

$MODULO_WEB_DIR = $SEI_DIR . '/sei/web/modulos/cgu/mod-sei-eouv';

require_once $SEI_DIR . '/sei/web/SEI.php';
require_once $MODULO_WEB_DIR . '/util/MdCguEouvClient.php';
require_once $MODULO_WEB_DIR . '/util/MdCguEouvGerarPdfOuv.php';
require_once $MODULO_WEB_DIR . '/util/MdCguEouvGerarPdfLai.php';

function verificaArgumentoNUP($indice, $formatado = false) {
    global $argc, $argv;

    if ($argc <= $indice) {
        echoln('Faltando argumentos: número do protocolo', true);
        exit(1);
    }

    // Garante que não está formatado
    $nup = preg_replace('/[^0-9]+/', '', $argv[$indice]);

    // Formata se necessário
    if ($formatado) {
        if (strlen($nup) == 17) {
            $nup = substr($nup, 0, 5) . '.' .
                substr($nup, 5, 6) . '/' .
                substr($nup, 11, 4) . '-' .
                substr($nup, 15, 2);
        } else {
            echoln('Protocolo inválido', true);
            exit(1);
        }
    }

    return $nup;
}

if ($argc <= 1) {
    echoln('Erro: faltando comando', true);
    exit(1);
}

$cmd = $argv[1];
SessaoSEI::getInstance(false);

try {
    $client = new MdCguEouvClient();

    switch ($cmd) {
        case 'parametros':
            echoln('Parâmetros:');
            dump($client->parametros());
            break;
        case 'manifestacoes':
            if ($argc <= 3) {
                echoln('Faltando argumentos: data de início e fim', true);
                exit(1);
            }
            dump($client->consultaManifestacoesNoIntervalo($argv[2], $argv[3]));
            break;
        case 'manifestacao':
            $nup = verificaArgumentoNUP(2);
            dump($client->consultaManifestacao($nup));
            break;
        case 'manifestacao_detalhada':
            $nup = verificaArgumentoNUP(2);
            $manifestacao = $client->consultaManifestacao($nup);
            dump($client->consultaDetalhadaManifestacao($manifestacao));
            break;
        case 'recursos':
            if ($argc <= 3) {
                echoln('Faltando argumentos: data de início e fim', true);
                exit(1);
            }
            dump($client->consultaRecursosNoIntervalo($argv[2], $argv[3]));
            break;
        case 'recursos_manifestacao':
            $nup = verificaArgumentoNUP(2);
            dump($client->consultaRecursosDaManifestacao($nup));
            break;
        case 'download_anexos_manifestacao':
            $nup = verificaArgumentoNUP(2);
            $manifestacao = $client->consultaManifestacao($nup);
            $detalhada = $client->consultaDetalhadaManifestacao($manifestacao);
            $anexos = $detalhada['Teor']['Anexos'];
            if (count($anexos) == 0) {
                echoln('Manifestação não possui anexos');
                exit(1);
            }
            foreach ($anexos as $anexo) {
                echoln('Baixando arquivo '.$anexo['NomeArquivo']);
                $client->downloadAnexo($anexo, __DIR__ . '/' . $anexo['NomeArquivo']);
                echoln('Baixado');
            }
            break;
        case 'download_anexos_recursos':
            $nup = verificaArgumentoNUP(2);
            $recursos = $client->consultaRecursosDaManifestacao($nup);
            if (count($recursos) == 0) {
                echoln('Manifestação não possui recursos');
                exit(1);
            }
            foreach ($recursos as $recurso) {
                $anexos = $recurso['anexos'];
                if (count($anexos) == 0) {
                    echoln('Recurso '.$recurso['idRecurso'].' não possui anexos');
                    continue;
                }
                foreach ($anexos as $anexo) {
                    echoln('Baixando arquivo '.$anexo['nomeArquivo']);
                    $client->downloadAnexo($anexo, __DIR__ . '/' . $anexo['nomeArquivo']);
                    echoln('Baixado');
                }
            }
            break;
        case 'gerar_pdf_ouv':
            $nup = verificaArgumentoNUP(2);
            $manifestacao = $client->consultaManifestacao($nup);
            $detalhada = $client->consultaDetalhadaManifestacao($manifestacao);
            $recursos = $client->consultaRecursosDaManifestacao($nup);
            $pedidoRevisao = count($recursos) > 0 ? $recursos[0] : null;
            $geradorPdf = new MdCguEouvGerarPdfOuv($detalhada, $pedidoRevisao, true, false);
            $pdf = $geradorPdf->obterPDF();
            $arquivo = __DIR__ . '/Relatorio_'.$nup.'.pdf';
            echoln("Gerando arquivo PDF $arquivo");
            $pdf->Output($arquivo, 'F');
            echoln("PDF gerado");
            break;
        case 'gerar_pdf_lai':
            $nup = verificaArgumentoNUP(2);
            $manifestacao = $client->consultaManifestacao($nup);
            $detalhada = $client->consultaDetalhadaManifestacao($manifestacao);
            $recursos = $client->consultaRecursosDaManifestacao($nup);
            $geradorPdf = new MdCguEouvGerarPdfLai($detalhada, $recursos, true, false);
            $pdf = $geradorPdf->obterPDF();
            $arquivo = __DIR__ . '/Relatorio_'.$nup.'.pdf';
            echoln("Gerando arquivo PDF $arquivo");
            $pdf->Output($arquivo, 'F');
            echoln("PDF gerado");
            break;
        case 'importar_manifestacao':
            $nup = verificaArgumentoNUP(2);
            $manifestacao = $client->consultaManifestacao($nup);
            $agendamento = new MdCguEouvAgendamentoRN();
            $agendamento->inicializar();
            echoln('Importando manifestação');
            $agendamento->executarImportacaoLinha($manifestacao);
            $agendamento->gravarLogImportacao(null, null, 'T', 'Teste');
            echoln('Fim');
            break;
        default:
            echoln('Comando não econtrado', true);
            break;
    }
} catch (\Exception $e) {
    echo InfraException::inspecionar($e);
}