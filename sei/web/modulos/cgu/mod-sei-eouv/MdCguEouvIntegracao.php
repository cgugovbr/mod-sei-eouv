<?php
/**
 * CONTROLADORIA GERAL DA UNIAO
 *
 * 03/10/2017 - criado por rafael.ferreira@cgu.gov.br
 *
 */

class MdCguEouvIntegracao extends SeiIntegracao
{

//    public function __construct()
//    {
//    }

    public function getNome()
    {
        return 'M�dulo de Integra��o entre o sistema SEI e o FalaBR (Sistema de Ouvidorias - e-Ouv|e-Sic)';
    }

    public function getVersao()
    {
        return '4.0.0';
    }

    public function getInstituicao()
    {
        return 'CGU - Controladoria Geral da Uni�o';
    }

//    public function inicializar($strVersaoSEI)
//    {
//        /*
//        if (substr($strVersaoSEI, 0, 2) != '3.'){
//          die('M�dulo "'.$this->getNome().'" ('.$this->getVersao().') n�o � compat�vel com esta vers�o do SEI ('.$strVersaoSEI.').');
//        }
//        */
//    }

    public function processarControladorWebServices($strServico)
    {
        $strArq = null;
        switch ($strServico) {
            case 'eouv':
                $strArq = 'eouv.wsdl';
                break;
        }

        if ($strArq!=null){
            $strArq = dirname(__FILE__).'/ws/'.$strArq;
        }
        return $strArq;
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

            case 'md_cgu_eouv_parametro_listar':
                require_once dirname(__FILE__).'/md_cgu_eouv_parametro_lista.php';
                return true;

            case 'md_cgu_eouv_parametro_listar_esic':
                require_once dirname(__FILE__).'/md_cgu_eouv_parametro_lista_esic.php';
                return true;

            case 'md_cgu_eouv_parametro_alterar':
                require_once dirname(__FILE__).'/md_cgu_eouv_parametro_cadastro.php';
                return true;

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