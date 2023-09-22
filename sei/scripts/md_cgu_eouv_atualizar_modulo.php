<?php

require_once dirname(__FILE__) . '/../web/SEI.php';

class MdCguEouvAtualizadorSeiRN extends InfraScriptVersao
{
  private $nomeModulo = 'EOUV - Integração com sistema FalaBR (E-ouv)';
  private $versaoAtual = '4.0.2';
  private $parametroVersao = 'VERSAO_MODULO_CGU_EOUV';
  private $arrayVersoes = array(
    '2.0.5' => 'instalarv205',
    '3.0.*' => 'instalarv300',
    '4.0.0' => 'instalarv400',
    '4.0.1' => 'instalarv401',
    '4.0.2' => 'instalarv402'
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
    return BancoSEI::getInstance();
  }

  protected function instalarv205()
  {
    SessaoInfra::setObjInfraSessao(SessaoSEI::getInstance());
    BancoInfra::setObjInfraIBanco(BancoSEI::getInstance());

    $objInfraMetaBD = new InfraMetaBD(BancoSEI::getInstance());

    //6.1	Para o mapeamento DE-PARA entre os Tipos de Manifestação E-ouv e Tipo de processo SEI
    $this->logar('CRIANDO A TABELA md_eouv_depara_importacao');

    BancoSEI::getInstance()->executarSql('CREATE TABLE md_eouv_depara_importacao(id_tipo_manifestacao_eouv ' . $objInfraMetaBD->tipoNumero() . ' NOT NULL ,
      id_tipo_procedimento ' . $objInfraMetaBD->tipoNumero() . ' NULL ,
      de_tipo_manifestacao_eouv ' . $objInfraMetaBD->tipoTextoVariavel(50) . ' NULL)');

    $objInfraMetaBD->adicionarChavePrimaria('md_eouv_depara_importacao', 'pk_md_eouv_depara_importacao', array('id_tipo_manifestacao_eouv'));
    $objInfraMetaBD->adicionarChaveEstrangeira('fk1_md_eouv_tipo_procedimento', 'md_eouv_depara_importacao', array('id_tipo_procedimento'), 'tipo_procedimento', array('id_tipo_procedimento'));
    $objInfraMetaBD->criarIndice('md_eouv_depara_importacao', 'i01_md_eouv_depara_importacao', array('id_tipo_procedimento'));

    $this->logar('CRIANDO REGISTROS PARA A TABELA md_eouv_depara_importacao');
    BancoSEI::getInstance()->executarSql('INSERT INTO md_eouv_depara_importacao (id_tipo_manifestacao_eouv, de_tipo_manifestacao_eouv, id_tipo_procedimento) VALUES (\'1\', \'Denúncia\', NULL)');
    BancoSEI::getInstance()->executarSql('INSERT INTO md_eouv_depara_importacao (id_tipo_manifestacao_eouv, de_tipo_manifestacao_eouv, id_tipo_procedimento) VALUES (\'2\', \'Reclamação\', NULL)');
    BancoSEI::getInstance()->executarSql('INSERT INTO md_eouv_depara_importacao (id_tipo_manifestacao_eouv, de_tipo_manifestacao_eouv, id_tipo_procedimento) VALUES (\'3\', \'Elogio\', NULL)');
    BancoSEI::getInstance()->executarSql('INSERT INTO md_eouv_depara_importacao (id_tipo_manifestacao_eouv, de_tipo_manifestacao_eouv, id_tipo_procedimento) VALUES (\'4\', \'Sugestão\', NULL)');
    BancoSEI::getInstance()->executarSql('INSERT INTO md_eouv_depara_importacao (id_tipo_manifestacao_eouv, de_tipo_manifestacao_eouv, id_tipo_procedimento) VALUES (\'5\', \'Solicitação\', NULL)');
    BancoSEI::getInstance()->executarSql('INSERT INTO md_eouv_depara_importacao (id_tipo_manifestacao_eouv, de_tipo_manifestacao_eouv, id_tipo_procedimento) VALUES (\'6\', \'Simplifique\', NULL)');
    BancoSEI::getInstance()->executarSql('INSERT INTO md_eouv_depara_importacao (id_tipo_manifestacao_eouv, de_tipo_manifestacao_eouv, id_tipo_procedimento) VALUES (\'7\', \'Comunicado\', NULL)');

    $this->logar('CRIANDO A TABELA md_eouv_rel_import');
    BancoSEI::getInstance()->executarSql('CREATE TABLE md_eouv_rel_import(id_md_eouv_rel_import ' . $objInfraMetaBD->tipoNumero() . ' NOT NULL ,
      dth_importacao ' . $objInfraMetaBD->tipoDataHora() . ' NOT NULL ,
      sin_sucesso ' . $objInfraMetaBD->tipoTextoFixo(1) . ' NOT NULL ,
      dth_periodo_inicial ' . $objInfraMetaBD->tipoDataHora() . ' NULL ,
      dth_periodo_final ' . $objInfraMetaBD->tipoDataHora() . ' NULL ,
      des_log_processamento ' . $objInfraMetaBD->tipoTextoVariavel(500) . ' NULL)');
    $objInfraMetaBD->adicionarChavePrimaria('md_eouv_rel_import', 'pk_md_eouv_rel_import', array('id_md_eouv_rel_import'));

    $this->logar('CRIANDO A TABELA md_eouv_rel_import_det');
    BancoSEI::getInstance()->executarSql('CREATE TABLE md_eouv_rel_import_det(id_md_eouv_rel_import ' . $objInfraMetaBD->tipoNumero() . ' NOT NULL ,
      num_protocolo_formatado ' . $objInfraMetaBD->tipoTextoFixo(50) . ' NOT NULL ,
      sin_sucesso ' . $objInfraMetaBD->tipoTextoFixo(1) . ' NOT NULL ,
      des_log_processamento ' . $objInfraMetaBD->tipoTextoVariavel(500) . ' NULL,
      dth_importacao ' . $objInfraMetaBD->tipoDataHora() . ' NULL)');

    $objInfraMetaBD->adicionarChavePrimaria('md_eouv_rel_import_det', 'pk_md_eouv_rel_import_det',
      array('id_md_eouv_rel_import', 'num_protocolo_formatado'));
    $objInfraMetaBD->adicionarChaveEstrangeira('fk1_md_eouv_rel_import_det', 'md_eouv_rel_import_det', array('id_md_eouv_rel_import'), 'md_eouv_rel_import', array('id_md_eouv_rel_import'));

    if (BancoSEI::getInstance() instanceof InfraMySql) {
      BancoSEI::getInstance()->executarSql('create table seq_md_eouv_rel_import (id bigint not null primary key AUTO_INCREMENT, campo char(1) null) AUTO_INCREMENT = 1');
    } else if (BancoSEI::getInstance() instanceof InfraSqlServer) {
      BancoSEI::getInstance()->executarSql('create table seq_md_eouv_rel_import (id bigint identity(1,1), campo char(1) null)');
    } else if (BancoSEI::getInstance() instanceof InfraOracle) {
      BancoSEI::getInstance()->criarSequencialNativa('seq_md_eouv_rel_import', 1);
    }

    $this->logar('CRIANDO Parâmetros do Sei');
    $objInfraParametro = new InfraParametro(BancoSEI::getInstance());
    $objInfraParametro->setValor('EOUV_URL_WEBSERVICE_IMPORTACAO_ANEXO_MANIFESTACAO', 'https://treinafalabr.cgu.gov.br/Servicos/ServicoAnexosManifestacao.svc');
    $objInfraParametro->setValor('EOUV_URL_WEBSERVICE_IMPORTACAO_MANIFESTACAO', 'https://treinafalabr.cgu.gov.br/Servicos/ServicoConsultaManifestacao.svc');
    $objInfraParametro->setValor('ID_UNIDADE_OUVIDORIA', '110000001');
    $objInfraParametro->setValor('ID_SERIE_EXTERNO_OUVIDORIA', '92');
    $objInfraParametro->setValor('EOUV_ID_SERIE_DOCUMENTO_EXTERNO_DADOS_MANIFESTACAO', '63');
    $objInfraParametro->setValor('EOUV_DATA_INICIAL_IMPORTACAO_MANIFESTACOES ', '01/12/2015');
    $objInfraParametro->setValor('EOUV_URL_DETALHE_MANIFESTACAO', '');
    $objInfraParametro->setValor('EOUV_USUARIO_ACESSO_WEBSERVICE', '');
    $objInfraParametro->setValor('EOUV_SENHA_ACESSO_WEBSERVICE', '');

    $this->logar('CRIANDO Agendamento da tarefa no Sei');
    $objInfraAgendamentoTarefaDTO = new InfraAgendamentoTarefaDTO();
    $objInfraAgendamentoTarefaDTO->setNumIdInfraAgendamentoTarefa(null);
    $objInfraAgendamentoTarefaDTO->setStrDescricao('Rotina responsável pela execução da importação de manifestações '.
      'e-Ouv cadastradas no FalaBR que serão importadas para o SEI/SUPER como um novo processo. '.
      'Se baseia na data da última execução com sucesso até a data atual.');
    $objInfraAgendamentoTarefaDTO->setStrComando('MdCguEouvAgendamentoRN::executarImportacaoManifestacaoEOuv');
    $objInfraAgendamentoTarefaDTO->setStrStaPeriodicidadeExecucao('D');

    $objInfraAgendamentoTarefaDTO->setStrPeriodicidadeComplemento('1');
    $objInfraAgendamentoTarefaDTO->setDthUltimaExecucao(null);
    $objInfraAgendamentoTarefaDTO->setDthUltimaConclusao(null);
    $objInfraAgendamentoTarefaDTO->setStrSinSucesso('N');
    $objInfraAgendamentoTarefaDTO->setStrParametro(null);
    $objInfraAgendamentoTarefaDTO->setStrEmailErro('');
    $objInfraAgendamentoTarefaDTO->setStrSinAtivo('S');
    $objInfraAgendamentoTarefaRN = new InfraAgendamentoTarefaRN();
    $objInfraAgendamentoTarefaRN->getObjInfraIBanco();
    $objInfraAgendamentoTarefaDTO = $objInfraAgendamentoTarefaRN->cadastrar($objInfraAgendamentoTarefaDTO);
    $this->logar('Tarefa cadastrada com sucesso.');

    SessaoInfra::setObjInfraSessao(SessaoSEI::getInstance());
    BancoInfra::setObjInfraIBanco(BancoSEI::getInstance());

    $this->logar('Primeiro verifica se já existe um usuário com nome EOUV');
    $objUsuarioDTOEouv = new UsuarioDTO();
    $objUsuarioDTOEouv->retTodos();
    $objUsuarioDTOEouv->setStrSigla('EOUV');
    $objUsuarioRN = new UsuarioRN();
    $objUsuarioDTOEouv = $objUsuarioRN->consultarRN0489($objUsuarioDTOEouv);

    if ($objUsuarioDTOEouv==null) {
      $this->logar('Criando Sistema EOUV NA BASE DO SEI...');
      $objUsuarioDTO = new UsuarioDTO();
      $objUsuarioDTO->setNumIdUsuario(null);
      $objUsuarioDTO->setNumIdOrgao(0);
      $objUsuarioDTO->setStrIdOrigem(null);
      $objUsuarioDTO->setStrSigla('EOUV');
      $objUsuarioDTO->setStrNome('Integração com sistema E-Ouv');
      $objUsuarioDTO->setNumIdContato(null);
      $objUsuarioDTO->setStrStaTipo(UsuarioRN::$TU_SISTEMA);
      $objUsuarioDTO->setStrSenha(null);
      $objUsuarioDTO->setStrSinAcessibilidade('N');
      $objUsuarioDTO->setStrSinAtivo('S');
      $objUsuarioRN = new UsuarioRN();
      $objUsuarioDTO = $objUsuarioRN->cadastrarRN0487($objUsuarioDTO);
    }
    else{
      $objUsuarioDTO = $objUsuarioDTOEouv;
    }

    $this->logar('Criando Serviço CadastrarManifestacao NA BASE DO SEI...');
    $objServicoDTO = new ServicoDTO();
    $objServicoDTO->setNumIdServico(null);
    $objServicoDTO->setNumIdUsuario($objUsuarioDTO->getNumIdUsuario());
    $objServicoDTO->setStrIdentificacao('CadastrarManifestacao');
    $objServicoDTO->setStrDescricao('Cadastrar Manifestação Importada do sistema E-Ouv');
    $objServicoDTO->setStrServidor('*');
    $objServicoDTO->setStrSinServidor('N');
    $objServicoDTO->setStrSinChaveAcesso('N');
    $objServicoDTO->setStrSinLinkExterno('N');
    $objServicoDTO->setStrSinAtivo('S');
    $objServicoRN = new ServicoRN();
    $objServicoDTO = $objServicoRN->cadastrar($objServicoDTO);

    $this->logar('Criando Operação NA BASE DO SEI...');
    $objOperacaoServicoDTO = new OperacaoServicoDTO();
    $objOperacaoServicoDTO->setNumIdOperacaoServico(null);
    $objOperacaoServicoDTO->setNumIdServico($objServicoDTO->getNumIdServico());
    $objOperacaoServicoDTO->setNumStaOperacaoServico(0); //Gerar Procedimento
    $objOperacaoServicoDTO->setNumIdUnidade(null);
    $objOperacaoServicoDTO->setNumIdSerie(null);
    $objOperacaoServicoDTO->setNumIdTipoProcedimento(null);
    $objOperacaoServicoRN = new OperacaoServicoRN();
    $objOperacaoServicoDTO = $objOperacaoServicoRN->cadastrar($objOperacaoServicoDTO);
  }

  protected function instalarv300()
  {
    SessaoInfra::setObjInfraSessao(SessaoSEI::getInstance());
    BancoInfra::setObjInfraIBanco(BancoSEI::getInstance());

    $objInfraMetaBD = new InfraMetaBD(BancoSEI::getInstance());

    $this->logar('CRIANDO A TABELA md_eouv_parametros');
    //Tabela criada para retirar os Parâmetros do Infra>Parametros do SEI

    BancoSEI::getInstance()->executarSql('CREATE TABLE md_eouv_parametros(id_parametro ' . $objInfraMetaBD->tipoNumero() . ' NOT NULL ,
      no_parametro ' . $objInfraMetaBD->tipoTextoVariavel(100) . ' NOT NULL ,
      de_valor_parametro ' . $objInfraMetaBD->tipoTextoVariavel(455) . ' NOT NULL)');

    $objInfraMetaBD->adicionarChavePrimaria('md_eouv_parametros', 'pk_md_eouv_parametro', array('id_parametro'));

    $this->logar('CRIANDO REGISTROS PARA A TABELA md_eouv_parametro');
    BancoSEI::getInstance()->executarSql('INSERT INTO md_eouv_parametros (id_parametro, no_parametro, de_valor_parametro) VALUES (\'1\', \'EOUV_DATA_INICIAL_IMPORTACAO_MANIFESTACOES\', \''.date('d/m/Y').'\')');
    BancoSEI::getInstance()->executarSql('INSERT INTO md_eouv_parametros (id_parametro, no_parametro, de_valor_parametro) VALUES (\'2\', \'EOUV_ID_SERIE_DOCUMENTO_EXTERNO_DADOS_MANIFESTACAO\', \'63\')');
    BancoSEI::getInstance()->executarSql('INSERT INTO md_eouv_parametros (id_parametro, no_parametro, de_valor_parametro) VALUES (\'3\', \'ID_SERIE_EXTERNO_OUVIDORIA\', \'92\')');
    BancoSEI::getInstance()->executarSql('INSERT INTO md_eouv_parametros (id_parametro, no_parametro, de_valor_parametro) VALUES (\'4\', \'EOUV_USUARIO_ACESSO_WEBSERVICE\', \'nomeUsuarioWebService\')');
    BancoSEI::getInstance()->executarSql('INSERT INTO md_eouv_parametros (id_parametro, no_parametro, de_valor_parametro) VALUES (\'5\', \'EOUV_SENHA_ACESSO_WEBSERVICE\', \'senhaUsuarioWebService\')');
    BancoSEI::getInstance()->executarSql('INSERT INTO md_eouv_parametros (id_parametro, no_parametro, de_valor_parametro) VALUES (\'6\', \'CLIENT_ID\', \'XXX\')');
    BancoSEI::getInstance()->executarSql('INSERT INTO md_eouv_parametros (id_parametro, no_parametro, de_valor_parametro) VALUES (\'7\', \'CLIENT_SECRET\', \'XXX\')');
    BancoSEI::getInstance()->executarSql('INSERT INTO md_eouv_parametros (id_parametro, no_parametro, de_valor_parametro) VALUES (\'8\', \'EOUV_URL_WEBSERVICE_IMPORTACAO_MANIFESTACAO\', \'https://falabr.cgu.gov.br/api/manifestacoes\')');
    BancoSEI::getInstance()->executarSql('INSERT INTO md_eouv_parametros (id_parametro, no_parametro, de_valor_parametro) VALUES (\'9\', \'ID_UNIDADE_OUVIDORIA\', \'110000001\')');
    BancoSEI::getInstance()->executarSql('INSERT INTO md_eouv_parametros (id_parametro, no_parametro, de_valor_parametro) VALUES (\'10\', \'TOKEN\', \'XXX\')');
    BancoSEI::getInstance()->executarSql('INSERT INTO md_eouv_parametros (id_parametro, no_parametro, de_valor_parametro) VALUES (\'11\', \'IMPORTAR_DADOS_MANIFESTANTE\', \'1\')');

    $this->logar('APAGANDO OS REGISTROS DA TABELA INFRA_PARAMETROS USADOS NA VERSÃO 2.0.5 E QUE AGORA NÃO SÃO MAIS NECESSÁRIOS');

    $arrItensParametrosAExcluir = array(
      'EOUV_URL_WEBSERVICE_IMPORTACAO_ANEXO_MANIFESTACAO',
      'EOUV_URL_WEBSERVICE_IMPORTACAO_MANIFESTACAO',
      'ID_UNIDADE_OUVIDORIA',
      'ID_SERIE_EXTERNO_OUVIDORIA',
      'EOUV_ID_SERIE_DOCUMENTO_EXTERNO_DADOS_MANIFESTACAO',
      'EOUV_DATA_INICIAL_IMPORTACAO_MANIFESTACOES',
      'EOUV_URL_DETALHE_MANIFESTACAO',
      'EOUV_USUARIO_ACESSO_WEBSERVICE',
      'EOUV_SENHA_ACESSO_WEBSERVICE'
    );

    $arrObjInfraParametroDTO = array();

    for ($i = 0; $i < count($arrItensParametrosAExcluir); $i++) {
      $objInfraParametroDTO = new InfraParametroDTO();
      $objInfraParametroDTO->setStrNome($arrItensParametrosAExcluir[$i]);
      $arrObjInfraParametroDTO[] = $objInfraParametroDTO;
    }

    $objInfraParametroRN = new InfraParametroRN();
    $objInfraParametroRN->excluir($arrObjInfraParametroDTO);
  }

  protected function instalarv400()
  {
    SessaoInfra::setObjInfraSessao(SessaoSEI::getInstance());
    BancoInfra::setObjInfraIBanco(BancoSEI::getInstance());

    $objInfraMetaBD = new InfraMetaBD(BancoSEI::getInstance());

    // Atualiza chave primária da tabela depara_importacao para deixar apenas o campo de id_tipo_manifestacao_eouv
    $this->logar('ATUALIZANDO CHAVE PRIMÁRIA DA TABELA md_eouv_depara_importacao');
    $objInfraMetaBD->excluirChavePrimaria('md_eouv_depara_importacao', 'pk_md_eouv_depara_importacao');
    $objInfraMetaBD->adicionarChavePrimaria('md_eouv_depara_importacao', 'pk_md_eouv_depara_importacao', array('id_tipo_manifestacao_eouv'));

    // Atualiza coluna id_tipo_procedimento na tabela depara_importacao para permitir nulos
    $objInfraMetaBD->alterarColuna('md_eouv_depara_importacao', 'id_tipo_procedimento', $objInfraMetaBD->tipoNumero(), 'null');

    // Atualiza descrição do depara_importacao tipo 6
    $this->logar('ATUALIZANDO TIPO DE MANIFESTACAO COM ID = 6 PARA NÃO CLASSIFICADA');
    $objDeParaDTO = new MdCguEouvDeparaImportacaoDTO();
    $objDeParaDTO->setNumIdTipoManifestacaoEouv(6);
    $objDeParaDTO->retTodos();
    $objDeParaRN = new MdCguEouvDeparaImportacaoRN();
    $objDeParaDTO = $objDeParaRN->consultar($objDeParaDTO);
    if ($objDeParaDTO != null) {
      $objDeParaDTO->setStrDeTipoManifestacaoEouv('Não Classificada');
      $objDeParaDTO->unSetNumIdTipoProcedimento();
      $objDeParaRN->alterar($objDeParaDTO);
    }

    /**
     * Criar um "depara_importação" para a Manifestação e-Sic
     */
    $this->logar('CRIANDO REGISTRO DE ACESSO À INFORMAÇÃO PARA A TABELA md_eouv_depara_importacao');
    $objDeParaDTO = new MdCguEouvDeparaImportacaoDTO();
    $objDeParaDTO->setNumIdTipoManifestacaoEouv(8);
    $objDeParaDTO->setStrDeTipoManifestacaoEouv('Acesso à Informação');
    $objDeParaRN->cadastrar($objDeParaDTO);

    /**
     * Criar coluna na tabela md_eouv_rel_import para identificar qual o tipo de manifestação
     *
     * - 'P' (e-Ouv) - manifestações e-ouv padrão - tipos 1 a 7
     * - 'R' (e-Sic) - manifestações e-sic com 'R'ecursos - tipo 8
     */
    $this->logar('CRIANDO COLUNA PARA TIPO DE MANIFESTAÇÃO PARA A TABELA md_eouv_rel_import');
    $objInfraMetaBD->adicionarColuna('md_eouv_rel_import', 'tip_manifestacao', $objInfraMetaBD->tipoTextoFixo(2), 'null');
    BancoSEI::getInstance()->executarSql('UPDATE md_eouv_rel_import SET tip_manifestacao = \'P\'');
    $objInfraMetaBD->alterarColuna('md_eouv_rel_import', 'tip_manifestacao', $objInfraMetaBD->tipoTextoFixo(2), 'not null');

    /**
     * Criar coluna na tabela md_eouv_rel_import_det para identificar qual o tipo de manifestação
     *
     * - 'P' (e-Ouv) - manifestações e-ouv padrão - tipos 1 a 7
     * - 'R' (e-Sic) - manifestações e-sic com 'R'ecursos - tipo 8
     */
    $this->logar('CRIANDO COLUNA PARA TIPO DE MANIFESTAÇÃO PARA A TABELA md_eouv_rel_import_det');
    $objInfraMetaBD->adicionarColuna('md_eouv_rel_import_det', 'tip_manifestacao', $objInfraMetaBD->tipoTextoFixo(2), 'null');
    BancoSEI::getInstance()->executarSql('UPDATE md_eouv_rel_import_det SET tip_manifestacao = \'P\'');
    $objInfraMetaBD->alterarColuna('md_eouv_rel_import_det', 'tip_manifestacao', $objInfraMetaBD->tipoTextoFixo(2), 'not null');
    $objInfraMetaBD->adicionarColuna('md_eouv_rel_import_det', 'dth_prazo_atendimento', $objInfraMetaBD->tipoDataHora(), 'null');

    /**
     * Criar coluna na tabela md_eouv_parametros para identificar qual o tipo de parâmetro
     *
     * - 'eouv' (e-Ouv) - parâmetros do e-ouv [padrão]
     * - 'esicR' (e-Sic) - parâmetros do e-sic
     */
    $this->logar('CRIANDO COLUNA PARA TIPO DE PARÂMETRO PARA A TABELA md_eouv_parametros');
    $objInfraMetaBD->adicionarColuna('md_eouv_parametros', 'de_tipo', $objInfraMetaBD->tipoTextoVariavel(10), 'null');
    BancoSEI::getInstance()->executarSql('UPDATE md_eouv_parametros SET de_tipo = \'eouv\'');
    $objInfraMetaBD->alterarColuna('md_eouv_parametros', 'de_tipo', $objInfraMetaBD->tipoTextoVariavel(10), 'not null');

    /**
     * Cria parâmetros na tabela md_eouv_parametros para manifestações do e-Sic (tipo 8)
     */
    $this->logar('CRIA REGISTROS NA TABELA md_eouv_parametros PARA MANIFESTAÇÕES E-SIC (TIPO 8)');
    $objParametroRN = new MdCguEouvParametroRN();
    $arrNovosParametros = [
      ['id' => 12, 'parametro' => 'ESIC_DATA_INICIAL_IMPORTACAO_MANIFESTACOES', 'valor' => date('d/m/Y')],
      ['id' => 13, 'parametro' => 'ESIC_URL_WEBSERVICE_IMPORTACAO_RECURSOS', 'valor' => 'https://falabr.cgu.gov.br/api/recursos'],
      ['id' => 14, 'parametro' => 'ESIC_ID_UNIDADE_PRINCIPAL', 'valor' => '110000001'],
      ['id' => 15, 'parametro' => 'ESIC_ID_UNIDADE_RECURSO_PRIMEIRA_INSTANCIA', 'valor' => '110000001'],
      ['id' => 16, 'parametro' => 'ESIC_ID_UNIDADE_RECURSO_SEGUNDA_INSTANCIA', 'valor' => '110000001'],
      ['id' => 17, 'parametro' => 'ESIC_ID_UNIDADE_RECURSO_TERCEIRA_INSTANCIA', 'valor' => '110000001'],
      ['id' => 18, 'parametro' => 'ESIC_ID_UNIDADE_RECURSO_PEDIDO_REVISAO', 'valor' => '110000001']
    ];
    foreach ($arrNovosParametros as $arrParametro) {
      $objParametroDTO = new MdCguEouvParametroDTO();
      $objParametroDTO->setNumIdParametro($arrParametro['id']);
      $objParametroDTO->setStrNoParametro($arrParametro['parametro']);
      $objParametroDTO->setStrDeValorParametro($arrParametro['valor']);
      $objParametroDTO->setStrDeTipo('esic');
      $objParametroRN->cadastrarParametro($objParametroDTO);
    }

    // Cria agendamento para importação de tarefas e-Sic
    $this->logar('CRIA AGENDAMENTO PARA IMPORTAR MANIFESTAÇÕES E-SIC');
    $objInfraAgendamentoTarefaDTO = new InfraAgendamentoTarefaDTO();
    $objInfraAgendamentoTarefaDTO->setStrDescricao('Rotina responsável pela execução da '.
      'importação de manifestações de acesso à informação (e-Sic) cadastradas no FalaBR '.
      'que serão importadas para o SEI/SUPER como um novo processo. Se baseia na data da '.
      'última execução com sucesso até a data atual.');
    $objInfraAgendamentoTarefaDTO->setStrComando('MdCguEouvAgendamentoRN::executarImportacaoManifestacaoESic');
    $objInfraAgendamentoTarefaDTO->setStrParametro('');
    $objInfraAgendamentoTarefaDTO->setStrStaPeriodicidadeExecucao('D');
    $objInfraAgendamentoTarefaDTO->setStrPeriodicidadeComplemento('1');
    $objInfraAgendamentoTarefaDTO->setStrSinSucesso('N');
    $objInfraAgendamentoTarefaDTO->setDthUltimaExecucao(null);
    $objInfraAgendamentoTarefaDTO->setDthUltimaConclusao(null);
    $objInfraAgendamentoTarefaDTO->setStrEmailErro('');
    $objInfraAgendamentoTarefaDTO->setStrSinAtivo('S');
    $objInfraAgendamentoTarefaRN = new InfraAgendamentoTarefaRN();
    $objInfraAgendamentoTarefaDTO = $objInfraAgendamentoTarefaRN->cadastrar($objInfraAgendamentoTarefaDTO);

    // Remove parâmetro ID_SERIE_EXTERNO_OUVIDORIA (não mais utilizado)
    $this->logar('REMOVE PARÂMETRO ID_SERIE_EXTERNO_OUVIDORIA (NÃO UTILIZADO)');
    $objParametroDTO = new MdCguEouvParametroDTO();
    $objParametroDTO->setStrNoParametro('ID_SERIE_EXTERNO_OUVIDORIA');
    $objParametroDTO->retTodos();
    $objParametroDTO = $objParametroRN->consultarParametro($objParametroDTO);
    if ($objParametroDTO) {
      $objParametroRN->excluirParametro($objParametroDTO);
    }
  }

  protected function instalarv401(){

  }

  protected function instalarv402(){
      SessaoInfra::setObjInfraSessao(SessaoSEI::getInstance());
      BancoInfra::setObjInfraIBanco(BancoSEI::getInstance());

      $objInfraMetaBD = new InfraMetaBD(BancoSEI::getInstance());

      $this->logar('CRIANDO coluna PARA A TABELA md_eouv_depara_importacao');
      $objInfraMetaBD->adicionarColuna('md_eouv_depara_importacao', 'sin_ativo', $objInfraMetaBD->tipoTextoFixo(1), 'null');
      BancoSEI::getInstance()->executarSql('UPDATE md_eouv_depara_importacao SET sin_ativo = \'S\'');
      $objInfraMetaBD->alterarColuna('md_eouv_depara_importacao', 'sin_ativo', $objInfraMetaBD->tipoTextoFixo(1), 'not null');

      $this->logar('EXCLUINDO COLUNA tip_manifestacao DA TABELA md_eouv_rel_import');
      $objInfraMetaBD->excluirColuna('md_eouv_rel_import', 'tip_manifestacao');

      $this->logar('EXCLUINDO COLUNA tip_manifestacao DA TABELA md_eouv_rel_import_det');
      $objInfraMetaBD->excluirColuna('md_eouv_rel_import', 'tip_manifestacao');

      $this->logar('Criando nova tarefa no agendamento');
      $infraAgendamentoTarefaRN = new InfraAgendamentoTarefaRN();

      $infraAgendamentoTarefaDTO = new InfraAgendamentoTarefaDTO();
      $infraAgendamentoTarefaDTO->retTodos();
      $infraAgendamentoTarefaDTO->setStrComando('MdCguEouvAgendamentoRN::executarImportacaoManifestacaoEOuv');
      $tarefaEouv = $infraAgendamentoTarefaRN->consultar($infraAgendamentoTarefaDTO);
      $numRegistrosEouv = count($tarefaEouv);

      $infraAgendamentoTarefaDTO = new InfraAgendamentoTarefaDTO();
      $infraAgendamentoTarefaDTO->retTodos();
      $infraAgendamentoTarefaDTO->setStrComando('MdCguEouvAgendamentoRN::executarImportacaoManifestacaoESic');
      $tarefaEsic = $infraAgendamentoTarefaRN->consultar($infraAgendamentoTarefaDTO);
      $numRegistrosEsic = count($tarefaEsic);

      if($numRegistrosEouv == 0 && $numRegistrosEsic == 0) {
          $infraAgendamentoNovaTarefaDTO = new InfraAgendamentoTarefaDTO();
          $infraAgendamentoNovaTarefaDTO->setStrDescricao('Rotina responsável pela execução da importação de manifestações cadastradas no FalaBR que serão importadas para o SEI/SUPER como um novo processo. Se baseia na data da última execução com sucesso até a data atual.');
          $infraAgendamentoNovaTarefaDTO->setStrComando('MdCguEouvAgendamentoRN::executarImportacaoManifestacaoFalaBr');
          $infraAgendamentoNovaTarefaDTO->setStrSinAtivo('S');
          $infraAgendamentoNovaTarefaDTO->setStrStaPeriodicidadeExecucao('D');
          $infraAgendamentoNovaTarefaDTO->setStrPeriodicidadeComplemento('1');
          $infraAgendamentoNovaTarefaDTO->setStrSinSucesso('S');

          $infraAgendamentoTarefaRN->cadastrar($infraAgendamentoNovaTarefaDTO);
      }else{
          if($numRegistrosEouv>0){
              $tarefa = $tarefaEouv;
          }else{
              $tarefa = $tarefaEsic;
          }
          $infraAgendamentoNovaTarefaDTO = new InfraAgendamentoTarefaDTO();
          $infraAgendamentoNovaTarefaDTO->setStrDescricao('Rotina responsável pela execução da importação de manifestações cadastradas no FalaBR que serão importadas para o SEI/SUPER como um novo processo. Se baseia na data da última execução com sucesso até a data atual.');
          $infraAgendamentoNovaTarefaDTO->setStrComando('MdCguEouvAgendamentoRN::executarImportacaoManifestacaoFalaBr');
          $infraAgendamentoNovaTarefaDTO->setStrSinAtivo('S');
          $infraAgendamentoNovaTarefaDTO->setStrStaPeriodicidadeExecucao($tarefa[0]->getStrStaPeriodicidadeExecucao());
          $infraAgendamentoNovaTarefaDTO->setStrPeriodicidadeComplemento($tarefa[0]->getStrPeriodicidadeComplemento());
          $infraAgendamentoNovaTarefaDTO->setStrSinSucesso('S');

          $infraAgendamentoTarefaRN->cadastrar($infraAgendamentoNovaTarefaDTO);
      }
      $this->logar('removendo tarefas antidas do agendamento');
      if($numRegistrosEouv>0) {
          $infraAgendamentoTarefaRN->excluir($tarefaEouv);
      }
      if($numRegistrosEsic>0) {
          $infraAgendamentoTarefaRN->excluir($tarefaEsic);
      }
  }
}

try {
  session_start();

  SessaoSEI::getInstance(false);

  $objVersaoRN = new MdCguEouvAtualizadorSeiRN();
  $objVersaoRN->atualizarVersao();
} catch (Exception $e) {
  echo(InfraException::inspecionar($e));
  try {
    LogSEI::getInstance()->gravar(InfraException::inspecionar($e));
  } catch (Exception $e) {
  }
  exit(1);
}
?>