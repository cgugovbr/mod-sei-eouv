<?php

require_once dirname(__FILE__).'/../web/Sip.php';

class MdCguEouvAtualizadorSipRN extends InfraScriptVersao
{
  private $nomeModulo = 'EOUV - Integra��o com sistema FalaBR (E-ouv)';
  private $versaoAtual = '4.1.0';
  private $parametroVersao = 'VERSAO_MODULO_CGU_EOUV';
  private $arrayVersoes = array(
    '2.0.5' => 'instalarv205',
    '3.0.*' => 'instalarv300',
    '4.0.0' => 'instalarv400',
    '4.0.1' => 'semAlteracoes',
    '4.0.2' => 'semAlteracoes',
    '4.1.0' => 'instalarv410',
  );
  /**
   * 1. Come�amos a contralar a partir da vers�o 2.0.5 que � a �ltima est�vel para o SEI 3.0
   * 2. A vers�o 3.0.0 come�a a utilizar a vers�o REST dos webservices do E-Ouv
   * 3. A vers�o 4.0.0 importa manifesta��es do tipo 8 (acesso � informa��o) que s�o oriundas antigo e-Sic integrado
   * ao FalaBR, esta vers�o importa tambem os recursos de 1� e 2� inst�ncia
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
      throw new InfraException('Sistema SEI n�o encontrado.');
    }

    $numIdSistemaSei = $objSistemaDTO->getNumIdSistema();

    $objPerfilDTO = new PerfilDTO();
    $objPerfilDTO->retNumIdPerfil();
    $objPerfilDTO->setNumIdSistema($numIdSistemaSei);
    $objPerfilDTO->setStrNome('Administrador');
    $objPerfilDTO = $objPerfilRN->consultar($objPerfilDTO);

    if ($objPerfilDTO == null) {
      throw new InfraException('Perfil Administrador do sistema SEI n�o encontrado.');
    }

    $this->logar('ATUALIZANDO RECURSOS, MENUS E PERFIS DO MODULO '. $this->nomeDesteModulo .' NA BASE DO SIP...');

    $numIdPerfilSeiAdministrador = $objPerfilDTO->getNumIdPerfil();
    $this->adicionarRecursoPerfil($numIdSistemaSei, $numIdPerfilSeiAdministrador,
      'md_cgu_eouv_relatorio_importacao', 'Relat�rio de importa��o de manifesta��es do EOUV',
      'controlador.php?acao=md_cgu_eouv_relatorio_importacao');
    $this->adicionarRecursoPerfil($numIdSistemaSei, $numIdPerfilSeiAdministrador,
      'md_cgu_eouv_depara_importacao_consultar', 'Consulta a tabela DePara referente a importa��o de Tipo de manifesta��o',
      'controlador.php?acao=md_cgu_eouv_depara_importacao_consultar');
    $this->adicionarRecursoPerfil($numIdSistemaSei, $numIdPerfilSeiAdministrador,
      'md_cgu_eouv_depara_importacao_excluir', 'Excluir item da tabela DePara referente a importa��o de Tipo de manifesta��o',
      'controlador.php?acao=md_cgu_eouv_depara_importacao_excluir');
    $numIdRecursoIntegracaoSei = $this->adicionarRecursoPerfil($numIdSistemaSei, $numIdPerfilSeiAdministrador,
      'md_cgu_eouv_integracao_sei', 'Integra��o entre E-ouv e SEI',
      'controlador.php?acao=md_cgu_eouv_integracao_sei');

    $this->logar('Valor id objeto' . $numIdRecursoIntegracaoSei);

    $this->adicionarRecursoPerfil($numIdSistemaSei, $numIdPerfilSeiAdministrador,
      'md_cgu_eouv_relatorio_importacao_detalhar', 'Relat�rio Detalhado de importa��o de manifesta��es do EOUV',
      'controlador.php?acao=md_cgu_eouv_relatorio_importacao_detalhar');
    $numIdRecursoRelatorioImportacaoEouvSei = $this->adicionarRecursoPerfil($numIdSistemaSei, $numIdPerfilSeiAdministrador,
      'md_cgu_eouv_relatorio_importacao_listar', 'Relat�rio de importa��o de manifesta��es do EOUV',
      'controlador.php?acao=md_cgu_eouv_relatorio_importacao_listar');

    $objMenuDTO = new MenuDTO();
    $objMenuDTO->retNumIdMenu();
    $objMenuDTO->setNumIdSistema($numIdSistemaSei);
    $objMenuDTO->setStrNome('Principal');
    $objMenuDTO = $objMenuRN->consultar($objMenuDTO);

    if ($objMenuDTO == null) {
      throw new InfraException('Menu do sistema SEI n�o encontrado.');
    }
    $numIdMenuSei = $objMenuDTO->getNumIdMenu();

    $menuEouv = $this->adicionarItemMenu($numIdSistemaSei, $numIdPerfilSeiAdministrador,
      $numIdMenuSei, null, $numIdRecursoIntegracaoSei, 'E-Ouv',
      'Integra��o entre E-ouv e SEI', 1100);

    $this->adicionarItemMenu($numIdSistemaSei, $numIdPerfilSeiAdministrador,
      $numIdMenuSei, $menuEouv->getNumIdItemMenu(),
      $numIdRecursoRelatorioImportacaoEouvSei, 'Importa��o de Manifesta��o', 'Relat�rio de Importa��o de Manifesta��o', 10);
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
      throw new InfraException('Sistema SEI n�o encontrado.');
    }

    $numIdSistemaSei = $objSistemaDTO->getNumIdSistema();

    $objPerfilDTO = new PerfilDTO();
    $objPerfilDTO->retNumIdPerfil();
    $objPerfilDTO->setNumIdSistema($numIdSistemaSei);
    $objPerfilDTO->setStrNome('Administrador');
    $objPerfilDTO = $objPerfilRN->consultar($objPerfilDTO);

    if ($objPerfilDTO == null) {
      throw new InfraException('Perfil Administrador do sistema SEI n�o encontrado.');
    }

    $objMenuDTO = new MenuDTO();
    $objMenuDTO->retNumIdMenu();
    $objMenuDTO->setNumIdSistema($numIdSistemaSei);
    $objMenuDTO->setStrNome('Principal');
    $objMenuDTO = $objMenuRN->consultar($objMenuDTO);

    if ($objMenuDTO == null) {
      throw new InfraException('Menu do sistema SEI n�o encontrado.');
    }
    $numIdMenuSei = $objMenuDTO->getNumIdMenu();

    $numIdPerfilSeiAdministrador = $objPerfilDTO->getNumIdPerfil();

    $this->adicionarRecursoPerfil($numIdSistemaSei, $numIdPerfilSeiAdministrador,
      'md_cgu_eouv_parametro', 'Controle de Par�metros m�dulo SEI x E-ouv',
      'controlador.php?acao=md_cgu_eouv_parametro');

    $this->adicionarRecursoPerfil($numIdSistemaSei, $numIdPerfilSeiAdministrador,
      'md_cgu_eouv_parametro_consultar', 'Consulta de Par�metros m�dulo SEI x E-ouv',
      'controlador.php?acao=md_cgu_eouv_parametro_consultar');

    $numIdRecursoParametro = $this->adicionarRecursoPerfil($numIdSistemaSei, $numIdPerfilSeiAdministrador,
      'md_cgu_eouv_parametro_listar', 'Lista de Par�metros m�dulo SEI x E-ouv',
      'controlador.php?acao=md_cgu_eouv_parametro_listar');

    $this->adicionarRecursoPerfil($numIdSistemaSei, $numIdPerfilSeiAdministrador,
      'md_cgu_eouv_parametro_cadastrar', 'Cadastro de Par�metros m�dulo SEI x E-ouv',
      'controlador.php?acao=md_cgu_eouv_parametro_cadastrar');

    $this->adicionarRecursoPerfil($numIdSistemaSei, $numIdPerfilSeiAdministrador,
      'md_cgu_eouv_parametro_alterar', 'Altera��o de Par�metros m�dulo SEI x E-ouv',
      'controlador.php?acao=md_cgu_eouv_parametro_alterar');

    $this->logar('RECUPERANDO MENU DO E-OUV');
    $objItemMenuDTOEouv = new ItemMenuDTO();
    $objItemMenuDTOEouv->retNumIdItemMenu();
    $objItemMenuDTOEouv->setNumIdSistema($numIdSistemaSei);
    $objItemMenuDTOEouv->setStrRotulo('E-Ouv');
    $objItemMenuDTOEouv = $objItemMenuRN->consultar( $objItemMenuDTOEouv );

    $this->logar('CRIANDO e VINCULANDO ITEM MENU A PERFIL - E-Ouv->Par�metros');

    $this->adicionarItemMenu($numIdSistemaSei, $numIdPerfilSeiAdministrador,
      $numIdMenuSei, $objItemMenuDTOEouv->getNumIdItemMenu(),
      $numIdRecursoParametro, 'Par�metros do M�dulo E-ouv', 'Par�metros', 20);
  }

  protected function instalarv400()
  {
    $objSistemaRN = new SistemaRN();
    $objPerfilRN = new PerfilRN();
    $objMenuRN = new MenuRN();
    $objItemMenuRN = new ItemMenuRN();

    $objSistemaDTO = new SistemaDTO();
    $objSistemaDTO->retNumIdSistema();
    $objSistemaDTO->setStrSigla('SEI');

    $objSistemaDTO = $objSistemaRN->consultar($objSistemaDTO);

    if ($objSistemaDTO == null) {
      throw new InfraException('Sistema SEI n�o encontrado.');
    }

    $numIdSistemaSei = $objSistemaDTO->getNumIdSistema();

    $objPerfilDTO = new PerfilDTO();
    $objPerfilDTO->retNumIdPerfil();
    $objPerfilDTO->setNumIdSistema($numIdSistemaSei);
    $objPerfilDTO->setStrNome('Administrador');
    $objPerfilDTO = $objPerfilRN->consultar($objPerfilDTO);

    if ($objPerfilDTO == null) {
      throw new InfraException('Perfil Administrador do sistema SEI n�o encontrado.');
    }

    $objMenuDTO = new MenuDTO();
    $objMenuDTO->retNumIdMenu();
    $objMenuDTO->setNumIdSistema($numIdSistemaSei);
    $objMenuDTO->setStrNome('Principal');
    $objMenuDTO = $objMenuRN->consultar($objMenuDTO);

    if ($objMenuDTO == null) {
      throw new InfraException('Menu do sistema SEI n�o encontrado.');
    }
    $numIdMenuSei = $objMenuDTO->getNumIdMenu();

    $numIdPerfilSeiAdministrador = $objPerfilDTO->getNumIdPerfil();

    $numIdRecursoParametro = $this->adicionarRecursoPerfil($numIdSistemaSei, $numIdPerfilSeiAdministrador,
      'md_cgu_eouv_parametro_listar_esic', 'Lista de Par�metros e-Sic',
      'controlador.php?acao=md_cgu_eouv_parametro_listar_esic');

    $this->logar('RECUPERANDO MENU DO E-OUV');
    $objItemMenuDTOEouv = new ItemMenuDTO();
    $objItemMenuDTOEouv->retNumIdItemMenu();
    $objItemMenuDTOEouv->setNumIdSistema($numIdSistemaSei);
    $objItemMenuDTOEouv->setStrRotulo('E-Ouv');
    $objItemMenuDTOEouv = $objItemMenuRN->consultar( $objItemMenuDTOEouv );

    $this->logar('CRIANDO e VINCULANDO ITEM MENU A PERFIL - E-Ouv->Par�metros');

    $this->adicionarItemMenu($numIdSistemaSei, $numIdPerfilSeiAdministrador,
      $numIdMenuSei, $objItemMenuDTOEouv->getNumIdItemMenu(),
      $numIdRecursoParametro, 'Par�metros do M�dulo e-Sic', 'Par�metros', 30);

    // Recursos e menu para edi��o do De Para entre tipos FalaBR e Processo
    $this->logar('ADICIONANDO MENU DE CONFIGURA��O DE TIPOS DE MANIFESTA��O E PROCESSOS');
    $this->adicionarRecursoPerfil($numIdSistemaSei, $numIdPerfilSeiAdministrador,
      'md_cgu_eouv_depara_importacao_alterar', 'Alterar uma Associa��o entre Tipo de Manifesta��o FalaBR e Processo',
      'controlador.php?acao=md_cgu_eouv_depara_importacao_alterar');
    $numIdRecursoDeParaListar = $this->adicionarRecursoPerfil($numIdSistemaSei, $numIdPerfilSeiAdministrador,
      'md_cgu_eouv_depara_importacao_listar', 'Listar Associa��es entre o Tipo de Manifesta��o FalaBR e Processo',
      'controlador.php?acao=md_cgu_eouv_depara_importacao_listar');
    $this->adicionarItemMenu($numIdSistemaSei, $numIdPerfilSeiAdministrador,
      $numIdMenuSei, $objItemMenuDTOEouv->getNumIdItemMenu(),
      $numIdRecursoDeParaListar, 'Tipos de Manifesta��o', 'Associa��es entre o Tipo de Manifesta��o no Falabr e o Tipo de Processo', 40);
  }

  protected function instalarv410() {
      $objSistemaRN = new SistemaRN();
      $objPerfilRN = new PerfilRN();
      $objItemMenuRN = new ItemMenuRN();

      $objSistemaDTO = new SistemaDTO();
      $objSistemaDTO->retNumIdSistema();
      $objSistemaDTO->setStrSigla('SEI');

      $objSistemaDTO = $objSistemaRN->consultar($objSistemaDTO);

      if ($objSistemaDTO == null) {
          throw new InfraException('Sistema SEI n�o encontrado.');
      }

      $numIdSistemaSei = $objSistemaDTO->getNumIdSistema();

      $objPerfilDTO = new PerfilDTO();
      $objPerfilDTO->retNumIdPerfil();
      $objPerfilDTO->setNumIdSistema($numIdSistemaSei);
      $objPerfilDTO->setStrNome('Administrador');
      $objPerfilDTO = $objPerfilRN->consultar($objPerfilDTO);

      if ($objPerfilDTO == null) {
          throw new InfraException('Perfil Administrador do sistema SEI n�o encontrado.');
      }

      $numIdPerfilSeiAdministrador = $objPerfilDTO->getNumIdPerfil();
      $this->logar('Concedendo a permiss�o para ativar e desativar o tipo de manifesta��o');

      $this->adicionarRecursoPerfil($numIdSistemaSei, $numIdPerfilSeiAdministrador,
          'md_cgu_eouv_depara_importacao_desativar', 'Desativar Tipo de Manifesta��o FalaBR',
          'controlador.php?acao=md_cgu_eouv_depara_importacao_desativar');
      $this->adicionarRecursoPerfil($numIdSistemaSei, $numIdPerfilSeiAdministrador,
          'md_cgu_eouv_depara_importacao_reativar', 'Reativar Tipo de Manifesta��o FalaBR',
          'controlador.php?acao=md_cgu_eouv_depara_importacao_reativar');

      $this->logar('Alterar item de par�metros do menu');
      $objItemMenuDTOParametros = $this->localizarMenu($numIdSistemaSei, 'Par�metros do M�dulo E-ouv', 'E-Ouv');
      if ($objItemMenuDTOParametros != null) {
          $objItemMenuDTOParametros->setStrRotulo('Par�metros do M�dulo');
          $objItemMenuRN->alterar($objItemMenuDTOParametros);
      }

      $this->logar('Remover item *Par�metros do M�dulo e-Sic* do menu');
      $objItemMenuDTOParametros = $this->localizarMenu($numIdSistemaSei, 'Par�metros do M�dulo e-Sic', 'E-Ouv');
      if ($objItemMenuDTOParametros != null) {
        $this->removerItemMenu($numIdSistemaSei, $objItemMenuDTOParametros->getNumIdMenu(), $objItemMenuDTOParametros->getNumIdItemMenu());
      }

      $this->logar('Remover recurso md_cgu_eouv_parametro_listar_esic');
      $this->removerRecurso($numIdSistemaSei, 'md_cgu_eouv_parametro_listar_esic');
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

  private function removerItemMenu($numIdSistema, $numIdMenu, $numIdItemMenu)
  {
    $objItemMenuDTO = new ItemMenuDTO();
    $objItemMenuDTO->retNumIdMenu();
    $objItemMenuDTO->retNumIdItemMenu();
    $objItemMenuDTO->setNumIdSistema($numIdSistema);
    $objItemMenuDTO->setNumIdMenu($numIdMenu);
    $objItemMenuDTO->setNumIdItemMenu($numIdItemMenu);

    $objItemMenuRN = new ItemMenuRN();
    $objItemMenuDTO = $objItemMenuRN->consultar($objItemMenuDTO);

    if ($objItemMenuDTO != null) {
      $objRelPerfilItemMenuDTO = new RelPerfilItemMenuDTO();
      $objRelPerfilItemMenuDTO->retTodos();
      $objRelPerfilItemMenuDTO->setNumIdSistema($numIdSistema);
      $objRelPerfilItemMenuDTO->setNumIdMenu($objItemMenuDTO->getNumIdMenu());
      $objRelPerfilItemMenuDTO->setNumIdItemMenu($objItemMenuDTO->getNumIdItemMenu());

      $objRelPerfilItemMenuRN = new RelPerfilItemMenuRN();
      $objRelPerfilItemMenuRN->excluir($objRelPerfilItemMenuRN->listar($objRelPerfilItemMenuDTO));

      $objItemMenuRN->excluir(array($objItemMenuDTO));
    }
  }

  private function localizarMenu($numIdSistema, $strRotulo, $strRotuloMenuPai = null)
  {
    $objItemMenuRN = new ItemMenuRN();

    // Monta busca pelo item de menu
    $objItemMenuDTO = new ItemMenuDTO();
    $objItemMenuDTO->retTodos();
    $objItemMenuDTO->setNumIdSistema($numIdSistema);
    $objItemMenuDTO->setStrRotulo($strRotulo);

    // Localiza item pai, se passado
    if ($strRotuloMenuPai != null) {
      $objItemMenuPaiDTO = new ItemMenuDTO();
      $objItemMenuPaiDTO->retTodos();
      $objItemMenuPaiDTO->setNumIdSistema($numIdSistema);
      $objItemMenuPaiDTO->setStrRotulo($strRotuloMenuPai);
      $objItemMenuPaiDTO = $objItemMenuRN->consultar($objItemMenuPaiDTO);
      if ($objItemMenuPaiDTO == null) {
        return null;
      }

      $objItemMenuDTO->setNumIdItemMenuPai($objItemMenuPaiDTO->getNumIdItemMenu());
    }

    // Busca item de menu
    $objItemMenuDTO = $objItemMenuRN->consultar($objItemMenuDTO);
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

  private function removerRecurso($numIdSistema, $strNome)
  {
    $objRecursoDTO = new RecursoDTO();
    $objRecursoDTO->setBolExclusaoLogica(false);
    $objRecursoDTO->retNumIdRecurso();
    $objRecursoDTO->setNumIdSistema($numIdSistema);
    $objRecursoDTO->setStrNome($strNome);

    $objRecursoRN = new RecursoRN();
    $objRecursoDTO = $objRecursoRN->consultar($objRecursoDTO);

    if ($objRecursoDTO != null) {
      $objRelPerfilRecursoDTO = new RelPerfilRecursoDTO();
      $objRelPerfilRecursoDTO->retTodos();
      $objRelPerfilRecursoDTO->setNumIdSistema($numIdSistema);
      $objRelPerfilRecursoDTO->setNumIdRecurso($objRecursoDTO->getNumIdRecurso());

      $objRelPerfilRecursoRN = new RelPerfilRecursoRN();
      $objRelPerfilRecursoRN->excluir($objRelPerfilRecursoRN->listar($objRelPerfilRecursoDTO));

      $objItemMenuDTO = new ItemMenuDTO();
      $objItemMenuDTO->retNumIdMenu();
      $objItemMenuDTO->retNumIdItemMenu();
      $objItemMenuDTO->setNumIdSistema($numIdSistema);
      $objItemMenuDTO->setNumIdRecurso($objRecursoDTO->getNumIdRecurso());

      $objItemMenuRN = new ItemMenuRN();
      $arrObjItemMenuDTO = $objItemMenuRN->listar($objItemMenuDTO);

      $objRelPerfilItemMenuRN = new RelPerfilItemMenuRN();

      foreach ($arrObjItemMenuDTO as $objItemMenuDTO) {
        $objRelPerfilItemMenuDTO = new RelPerfilItemMenuDTO();
        $objRelPerfilItemMenuDTO->retTodos();
        $objRelPerfilItemMenuDTO->setNumIdSistema($numIdSistema);
        $objRelPerfilItemMenuDTO->setNumIdItemMenu($objItemMenuDTO->getNumIdItemMenu());

        $objRelPerfilItemMenuRN->excluir($objRelPerfilItemMenuRN->listar($objRelPerfilItemMenuDTO));
      }

      $objItemMenuRN->excluir($arrObjItemMenuDTO);

      $objRecursoRN->excluir(array($objRecursoDTO));
    }
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