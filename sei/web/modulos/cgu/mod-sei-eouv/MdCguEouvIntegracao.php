<?php
/**
 * CONTROLADORIA GERAL DA UNIAO
 *
 * 03/10/2017 - criado por rafael.ferreira@cgu.gov.br
 *
 */

class MdCguEouvIntegracao extends SeiIntegracao
{
    public function getNome()
    {
        return 'Módulo de Integração entre o sistema SEI e o FalaBR';
    }

    public function getVersao()
    {
        return '4.1.0';
    }

    public function getInstituicao()
    {
        return 'CGU - Controladoria Geral da União';
    }

    public function processarControlador($strAcao)
    {

        switch($strAcao) {

            case 'md_cgu_eouv_relatorio_importacao_listar':
                require_once dirname(__FILE__).'/md_cgu_eouv_relatorio_importacao.php';
                return true;

            case 'md_cgu_eouv_relatorio_importacao_detalhar':
                require_once dirname(__FILE__).'/md_cgu_eouv_relatorio_importacao_detalhar.php';
                return true;

            case 'md_cgu_eouv_relatorio_importacao_excluir':
                require_once dirname(__FILE__).'/md_cgu_eouv_relatorio_importacao_detalhar.php';
                return true;

            case 'md_cgu_eouv_integracao_sei':
                require_once dirname(__FILE__).'/md_cgu_eouv_relatorio_importacao.php';
                return true;
            case 'md_cgu_eouv_parametro_alterar':
            case 'md_cgu_eouv_parametro_listar':
                require_once dirname(__FILE__).'/md_cgu_eouv_parametro_lista.php';
                return true;

            case 'md_cgu_eouv_depara_importacao_desativar':
            case 'md_cgu_eouv_depara_importacao_reativar':
            case 'md_cgu_eouv_depara_importacao_listar':
                require_once dirname(__FILE__).'/md_cgu_eouv_depara_importacao_lista.php';
                return true;

            case 'md_cgu_eouv_depara_importacao_alterar':
                require_once dirname(__FILE__).'/md_cgu_eouv_depara_importacao_cadastro.php';
                return true;
        }
        return false;

    }
}

?>
