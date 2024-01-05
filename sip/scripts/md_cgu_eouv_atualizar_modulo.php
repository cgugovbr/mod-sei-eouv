<?php

require_once dirname(__FILE__).'/../web/Sip.php';

class MdCguEouvAtualizadorSipRN extends InfraScriptVersao
{
  private $nomeModulo = 'EOUV - Integração com sistema FalaBR (E-ouv)';
  private $versaoAtual = '4.0.2';
  private $parametroVersao = 'VERSAO_MODULO_CGU_EOUV';
  private $arrayVersoes = array(
    '2.0.5' => 'instalarv205',
    '3.0.*' => 'instalarv300',
    '4.0.0' => 'instalarv400',
    '4.0.1' => 'semAlteracoes',
    '4.0.2' => 'semAlteracoes',
  );
  /**
   * 1. Começamos a contralar a partir da versão 2.0.5 que é a última estável para o SEI 3.0
   * 2. A versão 3.0.0 começa a utilizar a versão REST dos webservices do E-Ouv
   * 3. A versão 4.0.0 importa manifestações do tipo 8 (acesso à informação) que são oriundas antigo e-Sic integrado
   * ao FalaBR, esta versão importa tambem os recursos de 1ª e 2ª instância
   */

  public function __construct()
  {
    parent::__construct();

    $this->setStrNome($this->nomeModulo);
    $this->setStrVersaoAtual($this->versaoAtual);
    $this->setStrParametroVersao($this->parametroVersao);
    $this->setArrVersoes($this->arrayVersoes);

    $this->setStrVersaoInfra('1.595.1');
    $this->setBolMySql(true);
    $this->setBolOracle(true);
    $this->setBolSqlServer(true);
    $this->setBolPostgreSql(false);
    $this->setBolErroVersaoInexistente(false);
  }

  protected function inicializarObjInfraIBanco()
  {
    return BancoSip::getInstance();
  }

  protected function semAlteracoes() {}

  protected function instalarv205(){
    $objSistemaRN = new SistemaRN();
    $objPerfilRN = new PerfilRN();
    $objMenuRN = new MenuRN();
    $objItemMenuRN = new ItemMenuRN();
    $objRecursoRN = new RecursoRN();

    $objSistemaDTO = new SistemaDTO();
    $objSistemaDTO->retNumIdSistema();
    $objSistemaDTO->setStrSigla('SEI');

    $objSistemaDTO = $objSistemaRN->consultar($objSistemaDTO);

    if ($objSistemaDTO == null) {
      throw new InfraException('Sistema SEI não encontrado.');
    }

    $numIdSistemaSei = $objSistemaDTO->getNumIdSistema();

    $objPerfilDTO = new PerfilDTO();
    $objPerfilDTO->retNumIdPerfil();
    $objPerfilDTO->setNumIdSistema($numIdSistemaSei);
    $objPerfilDTO->setStrNome('Administrador');
    $objPerfilDTO = $objPerfilRN->consultar($objPerfilDTO);

    if ($objPerfilDTO == null) {
      throw new InfraException('Perfil Administrador do sistema SEI não encontrado.');
    }

    $this->logar('ATUALIZANDO RECURSOS, MENUS E PERFIS DO MODULO '. $this->nomeDesteModulo .' NA BASE DO SIP...');

    $numIdPerfilSeiAdministrador = $objPerfilDTO->getNumIdPerfil();
    $this->adicionarRecursoPerfil($numIdSistemaSei, $numIdPerfilSeiAdministrador,
      'md_cgu_eouv_relatorio_importacao', 'Relatório de importação de manifestações do EOUV',
      'controlador.php?acao=md_cgu_eouv_relatorio_importacao');
    $this->adicionarRecursoPerfil($numIdSistemaSei, $numIdPerfilSeiAdministrador,
      'md_cgu_eouv_depara_importacao_consultar', 'Consulta a tabela DePara referente a importação de Tipo de manifestação',
      'controlador.php?acao=md_cgu_eouv_depara_importacao_consultar');
    $this->adicionarRecursoPerfil($numIdSistemaSei, $numIdPerfilSeiAdministrador,
      'md_cgu_eouv_depara_importacao_excluir', 'Excluir item da tabela DePara referente a importação de Tipo de manifestação',
      'controlador.php?acao=md_cgu_eouv_depara_importacao_excluir');
    $numIdRecursoIntegracaoSei = $this->adicionarRecursoPerfil($numIdSistemaSei, $numIdPerfilSeiAdministrador,
      'md_cgu_eouv_integracao_sei', 'Integração entre E-ouv e SEI',
      'controlador.php?acao=md_cgu_eouv_integracao_sei');

    $this->logar('Valor id objeto' . $numIdRecursoIntegracaoSei);

    $this->adicionarRecursoPerfil($numIdSistemaSei, $numIdPerfilSeiAdministrador,
      'md_cgu_eouv_relatorio_importacao_detalhar', 'Relatório Detalhado de importação de manifestações do EOUV',
      'controlador.php?acao=md_cgu_eouv_relatorio_importacao_detalhar');
    $numIdRecursoRelatorioImportacaoEouvSei = $this->adicionarRecursoPerfil($numIdSistemaSei, $numIdPerfilSeiAdministrador,
      'md_cgu_eouv_relatorio_importacao_listar', 'Relatório de importação de manifestações do EOUV',
      'controlador.php?acao=md_cgu_eouv_relatorio_importacao_listar');

    $objMenuDTO = new MenuDTO();
    $objMenuDTO->retNumIdMenu();
    $objMenuDTO->setNumIdSistema($numIdSistemaSei);
    $objMenuDTO->setStrNome('Principal');
    $objMenuDTO = $objMenuRN->consultar($objMenuDTO);

    if ($objMenuDTO == null) {
      throw new InfraException('Menu do sistema SEI não encontrado.');
    }
    $numIdMenuSei = $objMenuDTO->getNumIdMenu();

    $menuEouv = $this->adicionarItemMenu($numIdSistemaSei, $numIdPerfilSeiAdministrador,
      $numIdMenuSei, null, $numIdRecursoIntegracaoSei, 'E-Ouv',
      'Integração entre E-ouv e SEI', 1100);

    $this->adicionarItemMenu($numIdSistemaSei, $numIdPerfilSeiAdministrador,
      $numIdMenuSei, $menuEouv->getNumIdItemMenu(),
      $numIdRecursoRelatorioImportacaoEouvSei, 'Importação de Manifestação', 'Relatório de Importação de Manifestação', 10);
  }

  protected function instalarv300()
  {
    $objSistemaRN = new SistemaRN();
    $objPerfilRN = new PerfilRN();
    $objMenuRN = new MenuRN();
    $objItemMenuRN = new ItemMenuRN();
    $objRecursoRN = new RecursoRN();

    $objSistemaDTO = new SistemaDTO();
    $objSistemaDTO->retNumIdSistema();
    $objSistemaDTO->setStrSigla('SEI');

    $objSistemaDTO = $objSistemaRN->consultar($objSistemaDTO);

    if ($objSistemaDTO == null) {
      throw new InfraException('Sistema SEI não encontrado.');
    }

    $numIdSistemaSei = $objSistemaDTO->getNumIdSistema();

    $objPerfilDTO = new PerfilDTO();
    $objPerfilDTO->retNumIdPerfil();
    $objPerfilDTO->setNumIdSistema($numIdSistemaSei);
    $objPerfilDTO->setStrNome('Administrador');
    $objPerfilDTO = $objPerfilRN->consultar($objPerfilDTO);

    if ($objPerfilDTO == null) {
      throw new InfraException('Perfil Administrador do sistema SEI não encontrado.');
    }

    $objMenuDTO = new MenuDTO();
    $objMenuDTO->retNumIdMenu();
    $objMenuDTO->setNumIdSistema($numIdSistemaSei);
    $objMenuDTO->setStrNome('Principal');
    $objMenuDTO = $objMenuRN->consultar($objMenuDTO);

    if ($objMenuDTO == null) {
      throw new InfraException('Menu do sistema SEI não encontrado.');
    }
    $numIdMenuSei = $objMenuDTO->getNumIdMenu();

    $numIdPerfilSeiAdministrador = $objPerfilDTO->getNumIdPerfil();

    $this->adicionarRecursoPerfil($numIdSistemaSei, $numIdPerfilSeiAdministrador,
      'md_cgu_eouv_parametro', 'Controle de Parâmetros módulo SEI x E-ouv',
      'controlador.php?acao=md_cgu_eouv_parametro');

    $this->adicionarRecursoPerfil($numIdSistemaSei, $numIdPerfilSeiAdministrador,
      'md_cgu_eouv_parametro_consultar', 'Consulta de Parâmetros módulo SEI x E-ouv',
      'controlador.php?acao=md_cgu_eouv_parametro_consultar');

    $numIdRecursoParametro = $this->adicionarRecursoPerfil($numIdSistemaSei, $numIdPerfilSeiAdministrador,
      'md_cgu_eouv_parametro_listar', 'Lista de Parâmetros módulo SEI x E-ouv',
      'controlador.php?acao=md_cgu_eouv_parametro_listar');

    $this->adicionarRecursoPerfil($numIdSistemaSei, $numIdPerfilSeiAdministrador,
      'md_cgu_eouv_parametro_cadastrar', 'Cadastro de Parâmetros módulo SEI x E-ouv',
      'controlador.php?acao=md_cgu_eouv_parametro_cadastrar');

    $this->adicionarRecursoPerfil($numIdSistemaSei, $numIdPerfilSeiAdministrador,
      'md_cgu_eouv_parametro_alterar', 'Alteração de Parâmetros módulo SEI x E-ouv',
      'controlador.php?acao=md_cgu_eouv_parametro_alterar');

    $this->logar('RECUPERANDO MENU DO E-OUV');
    $objItemMenuDTOEouv = new ItemMenuDTO();
    $objItemMenuDTOEouv->retNumIdItemMenu();
    $objItemMenuDTOEouv->setNumIdSistema($numIdSistemaSei);
    $objItemMenuDTOEouv->setStrRotulo('E-Ouv');
    $objItemMenuDTOEouv = $objItemMenuRN->consultar( $objItemMenuDTOEouv );

    $this->logar('CRIANDO e VINCULANDO ITEM MENU A PERFIL - E-Ouv->Parâmetros');

    $this->adicionarItemMenu($numIdSistemaSei, $numIdPerfilSeiAdministrador,
      $numIdMenuSei, $objItemMenuDTOEouv->getNumIdItemMenu(),
      $numIdRecursoParametro, 'Parâmetros do Módulo E-ouv', 'Parâmetros', 20);
  }

  protected function instalarv400()
  {
    $objSistemaRN = new SistemaRN();
    $objPerfilRN = new PerfilRN();
    $objMenuRN = new MenuRN();
    $objItemMenuRN = new ItemMenuRN();
    $objRecursoRN = new RecursoRN();

    $objSistemaDTO = new SistemaDTO();
    $objSistemaDTO->retNumIdSistema();
    $objSistemaDTO->setStrSigla('SEI');

    $objSistemaDTO = $objSistemaRN->consultar($objSistemaDTO);

    if ($objSistemaDTO == null) {
      throw new InfraException('Sistema SEI não encontrado.');
    }

    $numIdSistemaSei = $objSistemaDTO->getNumIdSistema();

    $objPerfilDTO = new PerfilDTO();
    $objPerfilDTO->retNumIdPerfil();
    $objPerfilDTO->setNumIdSistema($numIdSistemaSei);
    $objPerfilDTO->setStrNome('Administrador');
    $objPerfilDTO = $objPerfilRN->consultar($objPerfilDTO);

    if ($objPerfilDTO == null) {
      throw new InfraException('Perfil Administrador do sistema SEI não encontrado.');
    }

    $objMenuDTO = new MenuDTO();
    $objMenuDTO->retNumIdMenu();
    $objMenuDTO->setNumIdSistema($numIdSistemaSei);
    $objMenuDTO->setStrNome('Principal');
    $objMenuDTO = $objMenuRN->consultar($objMenuDTO);

    if ($objMenuDTO == null) {
      throw new InfraException('Menu do sistema SEI não encontrado.');
    }
    $numIdMenuSei = $objMenuDTO->getNumIdMenu();

    $numIdPerfilSeiAdministrador = $objPerfilDTO->getNumIdPerfil();

    $numIdRecursoParametro = $this->adicionarRecursoPerfil($numIdSistemaSei, $numIdPerfilSeiAdministrador,
      'md_cgu_eouv_parametro_listar_esic', 'Lista de Parâmetros e-Sic',
      'controlador.php?acao=md_cgu_eouv_parametro_listar_esic');

    $this->logar('RECUPERANDO MENU DO E-OUV');
    $objItemMenuDTOEouv = new ItemMenuDTO();
    $objItemMenuDTOEouv->retNumIdItemMenu();
    $objItemMenuDTOEouv->setNumIdSistema($numIdSistemaSei);
    $objItemMenuDTOEouv->setStrRotulo('E-Ouv');
    $objItemMenuDTOEouv = $objItemMenuRN->consultar( $objItemMenuDTOEouv );

    $this->logar('CRIANDO e VINCULANDO ITEM MENU A PERFIL - E-Ouv->Parâmetros');

    $this->adicionarItemMenu($numIdSistemaSei, $numIdPerfilSeiAdministrador,
      $numIdMenuSei, $objItemMenuDTOEouv->getNumIdItemMenu(),
      $numIdRecursoParametro, 'Parâmetros do Módulo e-Sic', 'Parâmetros', 30);

    // Recursos e menu para edição do De Para entre tipos FalaBR e Processo
    $this->logar('ADICIONANDO MENU DE CONFIGURAÇÃO DE TIPOS DE MANIFESTAÇÃO E PROCESSOS');
    $this->adicionarRecursoPerfil($numIdSistemaSei, $numIdPerfilSeiAdministrador,
      'md_cgu_eouv_depara_importacao_alterar', 'Alterar uma Associação entre Tipo de Manifestação FalaBR e Processo',
      'controlador.php?acao=md_cgu_eouv_depara_importacao_alterar');
    $numIdRecursoDeParaListar = $this->adicionarRecursoPerfil($numIdSistemaSei, $numIdPerfilSeiAdministrador,
      'md_cgu_eouv_depara_importacao_listar', 'Listar Associações entre o Tipo de Manifestação FalaBR e Processo',
      'controlador.php?acao=md_cgu_eouv_depara_importacao_listar');
    $this->adicionarItemMenu($numIdSistemaSei, $numIdPerfilSeiAdministrador,
      $numIdMenuSei, $objItemMenuDTOEouv->getNumIdItemMenu(),
      $numIdRecursoDeParaListar, 'Tipos de Manifestação', 'Associações entre o Tipo de Manifestação no Falabr e o Tipo de Processo', 40);
  }

  private function adicionarItemMenu($numIdSistema, $numIdPerfil, $numIdMenu, $numIdItemMenuPai, $numIdRecurso, $strRotulo, $strDescricao, $numSequencia)
  {
    $objItemMenuDTO = new ItemMenuDTO();
    $objItemMenuDTO->retNumIdMenu();
    $objItemMenuDTO->retNumIdItemMenu();
    $objItemMenuDTO->setNumIdMenu($numIdMenu);
    $objItemMenuDTO->setNumIdMenuPai($numIdMenu);

    if ($numIdItemMenuPai==null) {
      $objItemMenuDTO->setNumIdItemMenuPai(null);
    } else {
      $objItemMenuDTO->setNumIdItemMenuPai($numIdItemMenuPai);
    }

    $objItemMenuDTO->setNumIdSistema($numIdSistema);
    $objItemMenuDTO->setNumIdRecurso($numIdRecurso);
    $objItemMenuDTO->setStrRotulo($strRotulo);

    $objItemMenuRN = new ItemMenuRN();
    $objItemMenuDTO = $objItemMenuRN->consultar($objItemMenuDTO);

    if ($objItemMenuDTO==null) {
      $objItemMenuDTO = new ItemMenuDTO();
      $objItemMenuDTO->setNumIdItemMenu(null);
      $objItemMenuDTO->setNumIdMenu($numIdMenu);

      if ($numIdItemMenuPai==null) {
        $objItemMenuDTO->setNumIdMenuPai(null);
        $objItemMenuDTO->setNumIdItemMenuPai(null);
      } else {
        $objItemMenuDTO->setNumIdMenuPai($numIdMenu);
        $objItemMenuDTO->setNumIdItemMenuPai($numIdItemMenuPai);
      }

      $objItemMenuDTO->setNumIdSistema($numIdSistema);
      $objItemMenuDTO->setNumIdRecurso($numIdRecurso);
      $objItemMenuDTO->setStrRotulo($strRotulo);
      $objItemMenuDTO->setStrDescricao($strDescricao);
      $objItemMenuDTO->setNumSequencia($numSequencia);
      $objItemMenuDTO->setStrSinNovaJanela('N');
      $objItemMenuDTO->setStrSinAtivo('S');
      $objItemMenuDTO->setStrIcone('');

      $objItemMenuDTO = $objItemMenuRN->cadastrar($objItemMenuDTO);
    }

    if ($numIdPerfil!=null && $numIdRecurso!=null) {
      $objRelPerfilRecursoDTO = new RelPerfilRecursoDTO();
      $objRelPerfilRecursoDTO->setNumIdSistema($numIdSistema);
      $objRelPerfilRecursoDTO->setNumIdPerfil($numIdPerfil);
      $objRelPerfilRecursoDTO->setNumIdRecurso($numIdRecurso);

      $objRelPerfilRecursoRN = new RelPerfilRecursoRN();

      if ($objRelPerfilRecursoRN->contar($objRelPerfilRecursoDTO)==0) {
        $objRelPerfilRecursoRN->cadastrar($objRelPerfilRecursoDTO);
      }

      $objRelPerfilItemMenuDTO = new RelPerfilItemMenuDTO();
      $objRelPerfilItemMenuDTO->setNumIdPerfil($numIdPerfil);
      $objRelPerfilItemMenuDTO->setNumIdSistema($numIdSistema);
      $objRelPerfilItemMenuDTO->setNumIdRecurso($numIdRecurso);
      $objRelPerfilItemMenuDTO->setNumIdMenu($objItemMenuDTO->getNumIdMenu());
      $objRelPerfilItemMenuDTO->setNumIdItemMenu($objItemMenuDTO->getNumIdItemMenu());

      $objRelPerfilItemMenuRN = new RelPerfilItemMenuRN();

      if ($objRelPerfilItemMenuRN->contar($objRelPerfilItemMenuDTO)==0) {
        $objRelPerfilItemMenuRN->cadastrar($objRelPerfilItemMenuDTO);
      }
    }

    return $objItemMenuDTO;
  }

  private function adicionarRecursoPerfil($numIdSistema, $numIdPerfil, $strNome, $strDescricao, $strCaminho = null)
  {
    $objRecursoDTO = new RecursoDTO();
    $objRecursoDTO->retNumIdRecurso();
    $objRecursoDTO->setNumIdSistema($numIdSistema);
    $objRecursoDTO->setStrNome($strNome);

    $objRecursoRN = new RecursoRN();
    $objRecursoDTO = $objRecursoRN->consultar($objRecursoDTO);

    if ($objRecursoDTO==null) {
      $objRecursoDTO = new RecursoDTO();
      $objRecursoDTO->setNumIdRecurso(null);
      $objRecursoDTO->setNumIdSistema($numIdSistema);
      $objRecursoDTO->setStrNome($strNome);
      $objRecursoDTO->setStrDescricao($strDescricao);

      if ($strCaminho == null) {
        $objRecursoDTO->setStrCaminho('controlador.php?acao='.$strNome);
      } else {
        $objRecursoDTO->setStrCaminho($strCaminho);
      }
      $objRecursoDTO->setStrSinAtivo('S');
      $objRecursoDTO = $objRecursoRN->cadastrar($objRecursoDTO);
    }
    if ($numIdPerfil!=null) {
      $objRelPerfilRecursoDTO = new RelPerfilRecursoDTO();
      $objRelPerfilRecursoDTO->setNumIdSistema($numIdSistema);
      $objRelPerfilRecursoDTO->setNumIdPerfil($numIdPerfil);
      $objRelPerfilRecursoDTO->setNumIdRecurso($objRecursoDTO->getNumIdRecurso());

      $objRelPerfilRecursoRN = new RelPerfilRecursoRN();

      if ($objRelPerfilRecursoRN->contar($objRelPerfilRecursoDTO)==0) {
        $objRelPerfilRecursoRN->cadastrar($objRelPerfilRecursoDTO);
      }
    }

    return $objRecursoDTO->getNumIdRecurso();
  }
}

try {
  session_start();

  SessaoSip::getInstance(false);

  $objVersaoRN = new MdCguEouvAtualizadorSipRN();
  $objVersaoRN->atualizarVersao();
} catch (Exception $e) {
    echo(InfraException::inspecionar($e));
    try {
      LogSip::getInstance()->gravar(InfraException::inspecionar($e));
    } catch (Exception $e) {
    }
    exit(1);
}
?>