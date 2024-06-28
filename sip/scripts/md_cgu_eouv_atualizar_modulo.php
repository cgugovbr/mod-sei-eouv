<?php

require_once dirname(__FILE__).'/../web/Sip.php';

class MdCguEouvAtualizadorSipRN extends InfraScriptVersao
{
  private $nomeModulo = 'Integração com o sistema FalaBR';
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
   * 1. Começamos a controlar a partir da versão 2.0.5 que é a última estável para o SEI 3.0
   * 2. A versão 3.0.0 começa a utilizar a versão REST dos webservices do E-Ouv
   * 3. A versão 4.0.0 importa manifestações do tipo 8 (acesso à informação) que são oriundas antigo e-Sic integrado
   * ao FalaBR, esta versão importa tambem os recursos de 1ª e 2ª instância
   */

  private $numIdSistemaSei; // ID do sistema SEI
  private $numIdPerfilSeiAdministrador; // ID do perfil Administrador do SEI
  private $numIdMenuSei; // ID do menu principal do SEI

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

    // Busca sistema SEI
    $objSistemaDTO = new SistemaDTO();
    $objSistemaDTO->retNumIdSistema();
    $objSistemaDTO->setStrSigla('SEI');
    $objSistemaRN = new SistemaRN();
    $objSistemaDTO = $objSistemaRN->consultar($objSistemaDTO);
    if ($objSistemaDTO == null) {
      throw new InfraException('Sistema SEI não encontrado.');
    }
    $this->numIdSistemaSei = $objSistemaDTO->getNumIdSistema();

    // Busca perfil Administrador
    $objPerfilDTO = new PerfilDTO();
    $objPerfilDTO->retNumIdPerfil();
    $objPerfilDTO->setNumIdSistema($this->numIdSistemaSei);
    $objPerfilDTO->setStrNome('Administrador');
    $objPerfilRN = new PerfilRN();
    $objPerfilDTO = $objPerfilRN->consultar($objPerfilDTO);
    if ($objPerfilDTO == null) {
      throw new InfraException('Perfil Administrador do sistema SEI não encontrado.');
    }
    $this->numIdPerfilSeiAdministrador = $objPerfilDTO->getNumIdPerfil();

    // Busca menu principal do SEI
    $objMenuDTO = new MenuDTO();
    $objMenuDTO->retNumIdMenu();
    $objMenuDTO->setNumIdSistema($this->numIdSistemaSei);
    $objMenuDTO->setStrNome('Principal');
    $objMenuRN = new MenuRN();
    $objMenuDTO = $objMenuRN->consultar($objMenuDTO);
    if ($objMenuDTO == null) {
      throw new InfraException('Menu principal do sistema SEI não encontrado.');
    }
    $this->numIdMenuSei = $objMenuDTO->getNumIdMenu();
  }

  protected function inicializarObjInfraIBanco()
  {
    return BancoSip::getInstance();
  }

  protected function semAlteracoes() {}

  protected function instalarv205(){
    $this->logar('Criando recursos no SIP');

    $this->adicionarRecursoPerfil($this->numIdSistemaSei, $this->numIdPerfilSeiAdministrador,
      'md_cgu_eouv_relatorio_importacao', 'Relatório de importação de manifestações do EOUV',
      'controlador.php?acao=md_cgu_eouv_relatorio_importacao');
    $this->adicionarRecursoPerfil($this->numIdSistemaSei, $this->numIdPerfilSeiAdministrador,
      'md_cgu_eouv_depara_importacao_consultar', 'Consulta a tabela DePara referente a importação de Tipo de manifestação',
      'controlador.php?acao=md_cgu_eouv_depara_importacao_consultar');
    $this->adicionarRecursoPerfil($this->numIdSistemaSei, $this->numIdPerfilSeiAdministrador,
      'md_cgu_eouv_depara_importacao_excluir', 'Excluir item da tabela DePara referente a importação de Tipo de manifestação',
      'controlador.php?acao=md_cgu_eouv_depara_importacao_excluir');
    $numIdRecursoIntegracaoSei = $this->adicionarRecursoPerfil($this->numIdSistemaSei, $this->numIdPerfilSeiAdministrador,
      'md_cgu_eouv_integracao_sei', 'Integração entre E-ouv e SEI',
      'controlador.php?acao=md_cgu_eouv_integracao_sei');

    $this->adicionarRecursoPerfil($this->numIdSistemaSei, $this->numIdPerfilSeiAdministrador,
      'md_cgu_eouv_relatorio_importacao_detalhar', 'Relatório Detalhado de importação de manifestações do EOUV',
      'controlador.php?acao=md_cgu_eouv_relatorio_importacao_detalhar');
    $numIdRecursoRelatorioImportacaoEouvSei = $this->adicionarRecursoPerfil($this->numIdSistemaSei, $this->numIdPerfilSeiAdministrador,
      'md_cgu_eouv_relatorio_importacao_listar', 'Relatório de importação de manifestações do EOUV',
      'controlador.php?acao=md_cgu_eouv_relatorio_importacao_listar');

    $this->logar('Criando menu E-Ouv');
    $menuEouv = $this->adicionarItemMenu($this->numIdSistemaSei, $this->numIdPerfilSeiAdministrador,
      $this->numIdMenuSei, null, $numIdRecursoIntegracaoSei, 'E-Ouv',
      'Integração entre E-ouv e SEI', 1100);

    $this->adicionarItemMenu($this->numIdSistemaSei, $this->numIdPerfilSeiAdministrador,
      $this->numIdMenuSei, $menuEouv->getNumIdItemMenu(),
      $numIdRecursoRelatorioImportacaoEouvSei, 'Importação de Manifestação', 'Relatório de Importação de Manifestação', 10);
  }

  protected function instalarv300()
  {
    $this->logar('Adicionando novos recursos no SIP');

    $this->adicionarRecursoPerfil($this->numIdSistemaSei, $this->numIdPerfilSeiAdministrador,
      'md_cgu_eouv_parametro', 'Controle de Parâmetros módulo SEI x E-ouv',
      'controlador.php?acao=md_cgu_eouv_parametro');

    $this->adicionarRecursoPerfil($this->numIdSistemaSei, $this->numIdPerfilSeiAdministrador,
      'md_cgu_eouv_parametro_consultar', 'Consulta de Parâmetros módulo SEI x E-ouv',
      'controlador.php?acao=md_cgu_eouv_parametro_consultar');

    $numIdRecursoParametro = $this->adicionarRecursoPerfil($this->numIdSistemaSei, $this->numIdPerfilSeiAdministrador,
      'md_cgu_eouv_parametro_listar', 'Lista de Parâmetros módulo SEI x E-ouv',
      'controlador.php?acao=md_cgu_eouv_parametro_listar');

    $this->adicionarRecursoPerfil($this->numIdSistemaSei, $this->numIdPerfilSeiAdministrador,
      'md_cgu_eouv_parametro_cadastrar', 'Cadastro de Parâmetros módulo SEI x E-ouv',
      'controlador.php?acao=md_cgu_eouv_parametro_cadastrar');

    $this->adicionarRecursoPerfil($this->numIdSistemaSei, $this->numIdPerfilSeiAdministrador,
      'md_cgu_eouv_parametro_alterar', 'Alteração de Parâmetros módulo SEI x E-ouv',
      'controlador.php?acao=md_cgu_eouv_parametro_alterar');

    $this->logar('Recuperando menu do E-Ouv');
    $objItemMenuDTOEouv = $this->localizarMenu($this->numIdSistemaSei, 'E-Ouv');

    $this->logar('Criando e vinculando item de Parâmetros ao perfil Administrador');
    $this->adicionarItemMenu($this->numIdSistemaSei, $this->numIdPerfilSeiAdministrador,
      $this->numIdMenuSei, $objItemMenuDTOEouv->getNumIdItemMenu(),
      $numIdRecursoParametro, 'Parâmetros do Módulo E-ouv', 'Parâmetros', 20);
  }

  protected function instalarv400()
  {
    $this->logar('Adicionando novos recursos ao SIP');
    $numIdRecursoParametro = $this->adicionarRecursoPerfil($this->numIdSistemaSei, $this->numIdPerfilSeiAdministrador,
      'md_cgu_eouv_parametro_listar_esic', 'Lista de Parâmetros e-Sic',
      'controlador.php?acao=md_cgu_eouv_parametro_listar_esic');

    $this->logar('Recuperando menu do E-Ouv');
    $objItemMenuDTOEouv = $this->localizarMenu($this->numIdSistemaSei, 'E-Ouv');

    $this->logar('Criando e vinculando item de menu Parâmetros e-Sic ao perfil administrador');
    $this->adicionarItemMenu($this->numIdSistemaSei, $this->numIdPerfilSeiAdministrador,
      $this->numIdMenuSei, $objItemMenuDTOEouv->getNumIdItemMenu(),
      $numIdRecursoParametro, 'Parâmetros do Módulo e-Sic', 'Parâmetros', 30);

    // Recursos e menu para edição do De Para entre tipos FalaBR e Processo
    $this->logar('Adicionando recursos e menu de configuração de tipos de manifestação e processos');
    $this->adicionarRecursoPerfil($this->numIdSistemaSei, $this->numIdPerfilSeiAdministrador,
      'md_cgu_eouv_depara_importacao_alterar', 'Alterar uma Associação entre Tipo de Manifestação FalaBR e Processo',
      'controlador.php?acao=md_cgu_eouv_depara_importacao_alterar');
    $numIdRecursoDeParaListar = $this->adicionarRecursoPerfil($this->numIdSistemaSei, $this->numIdPerfilSeiAdministrador,
      'md_cgu_eouv_depara_importacao_listar', 'Listar Associações entre o Tipo de Manifestação FalaBR e Processo',
      'controlador.php?acao=md_cgu_eouv_depara_importacao_listar');
    $this->adicionarItemMenu($this->numIdSistemaSei, $this->numIdPerfilSeiAdministrador,
      $this->numIdMenuSei, $objItemMenuDTOEouv->getNumIdItemMenu(),
      $numIdRecursoDeParaListar, 'Tipos de Manifestação', 'Associações entre o Tipo de Manifestação no Falabr e o Tipo de Processo', 40);
  }

  protected function instalarv410()
  {
    $this->logar('Criando recuros para ativar e desativar o tipo de manifestação');
    $this->adicionarRecursoPerfil($this->numIdSistemaSei, $this->numIdPerfilSeiAdministrador,
        'md_cgu_eouv_depara_importacao_desativar', 'Desativar Tipo de Manifestação FalaBR',
        'controlador.php?acao=md_cgu_eouv_depara_importacao_desativar');
    $this->adicionarRecursoPerfil($this->numIdSistemaSei, $this->numIdPerfilSeiAdministrador,
        'md_cgu_eouv_depara_importacao_reativar', 'Reativar Tipo de Manifestação FalaBR',
        'controlador.php?acao=md_cgu_eouv_depara_importacao_reativar');

    $objItemMenuRN = new ItemMenuRN();

    $this->logar('Alterando item de parâmetros do menu');
    $objItemMenuDTOParametros = $this->localizarMenu($this->numIdSistemaSei, 'Parâmetros do Módulo E-ouv', 'E-Ouv');
    if ($objItemMenuDTOParametros != null) {
        $objItemMenuDTOParametros->setStrRotulo('Parâmetros da Integração');
        $objItemMenuRN->alterar($objItemMenuDTOParametros);
    }

    $this->logar('Removendo item *Parâmetros do Módulo e-Sic* do menu');
    $objItemMenuDTOParametros = $this->localizarMenu($this->numIdSistemaSei, 'Parâmetros do Módulo e-Sic', 'E-Ouv');
    if ($objItemMenuDTOParametros != null) {
      $this->removerItemMenu($this->numIdSistemaSei, $objItemMenuDTOParametros->getNumIdMenu(), $objItemMenuDTOParametros->getNumIdItemMenu());
    }

    $this->logar('Removendo recurso md_cgu_eouv_parametro_listar_esic');
    $this->removerRecurso($this->numIdSistemaSei, 'md_cgu_eouv_parametro_listar_esic');

    $this->logar('Renomeando menu E-Ouv e movendo-o para ficar dentro do menu Administração');
    $objItemMenuDTOEouv = $this->localizarMenu($this->numIdSistemaSei, 'E-Ouv', null);
    if ($objItemMenuDTOEouv) {
      $objItemMenuDTOAdm = $this->localizarMenu($this->numIdSistemaSei, 'Administração', null);
      if (!$objItemMenuDTOAdm) {
        throw new Exception('Menu Administração não encontrado');
      }
      $objItemMenuDTOEouv->setStrRotulo('Integração com FalaBR');
      $objItemMenuDTOEouv->setStrDescricao('Integração com o portal de Ouvidoria e Acesso à Informação - FalaBR');
      $objItemMenuDTOEouv->setNumIdItemMenuPai($objItemMenuDTOAdm->getNumIdItemMenu());
      $objItemMenuDTOEouv->setNumSequencia(0);
      $objItemMenuRN->alterar($objItemMenuDTOEouv);
    }
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