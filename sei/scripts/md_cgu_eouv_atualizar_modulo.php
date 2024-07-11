<?php

require_once dirname(__FILE__) . '/../web/SEI.php';

class MdCguEouvAtualizadorSeiRN extends InfraScriptVersao
{
  private $nomeModulo = 'Integração com o sistema FalaBR';
  private $versaoAtual = '4.1.0';
  private $parametroVersao = 'VERSAO_MODULO_CGU_EOUV';
  private $arrayVersoes = array(
    '2.0.5' => 'instalarv205',
    '3.0.*' => 'instalarv300',
    '4.0.0' => 'instalarv400',
    '4.0.1' => 'semAlteracoes',
    '4.0.2' => 'instalarv402',
    '4.1.0' => 'instalarv410',
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

    SessaoInfra::setObjInfraSessao(SessaoSEI::getInstance());
    BancoInfra::setObjInfraIBanco(BancoSEI::getInstance());
  }

  protected function inicializarObjInfraIBanco()
  {
    return BancoSEI::getInstance();
  }

  protected function semAlteracoes() {}

  protected function instalarv205()
  {
    $objInfraIBanco = $this->inicializarObjInfraIBanco();
    $objInfraMetaBD = new InfraMetaBD($objInfraIBanco);

    //Cria tabela para o mapeamento DE-PARA entre os Tipos de Manifestação e Tipo de processo SEI
    $this->logar('Criando tabela md_eouv_depara_importacao');

    $objInfraIBanco->executarSql('CREATE TABLE md_eouv_depara_importacao(id_tipo_manifestacao_eouv ' . $objInfraMetaBD->tipoNumero() . ' NOT NULL ,
      id_tipo_procedimento ' . $objInfraMetaBD->tipoNumero() . ' NULL ,
      de_tipo_manifestacao_eouv ' . $objInfraMetaBD->tipoTextoVariavel(50) . ' NULL)');

    $objInfraMetaBD->adicionarChavePrimaria('md_eouv_depara_importacao', 'pk_md_eouv_depara_importacao', array('id_tipo_manifestacao_eouv'));
    $objInfraMetaBD->adicionarChaveEstrangeira('fk1_md_eouv_tipo_procedimento', 'md_eouv_depara_importacao', array('id_tipo_procedimento'), 'tipo_procedimento', array('id_tipo_procedimento'));
    $objInfraMetaBD->criarIndice('md_eouv_depara_importacao', 'i01_md_eouv_depara_importacao', array('id_tipo_procedimento'));

    $this->logar('Criando registros para a tabela md_eouv_depara_importacao');
    $objInfraIBanco->executarSql('INSERT INTO md_eouv_depara_importacao (id_tipo_manifestacao_eouv, de_tipo_manifestacao_eouv, id_tipo_procedimento) VALUES (\'1\', \'Denúncia\', NULL)');
    $objInfraIBanco->executarSql('INSERT INTO md_eouv_depara_importacao (id_tipo_manifestacao_eouv, de_tipo_manifestacao_eouv, id_tipo_procedimento) VALUES (\'2\', \'Reclamação\', NULL)');
    $objInfraIBanco->executarSql('INSERT INTO md_eouv_depara_importacao (id_tipo_manifestacao_eouv, de_tipo_manifestacao_eouv, id_tipo_procedimento) VALUES (\'3\', \'Elogio\', NULL)');
    $objInfraIBanco->executarSql('INSERT INTO md_eouv_depara_importacao (id_tipo_manifestacao_eouv, de_tipo_manifestacao_eouv, id_tipo_procedimento) VALUES (\'4\', \'Sugestão\', NULL)');
    $objInfraIBanco->executarSql('INSERT INTO md_eouv_depara_importacao (id_tipo_manifestacao_eouv, de_tipo_manifestacao_eouv, id_tipo_procedimento) VALUES (\'5\', \'Solicitação\', NULL)');
    $objInfraIBanco->executarSql('INSERT INTO md_eouv_depara_importacao (id_tipo_manifestacao_eouv, de_tipo_manifestacao_eouv, id_tipo_procedimento) VALUES (\'6\', \'Simplifique\', NULL)');
    $objInfraIBanco->executarSql('INSERT INTO md_eouv_depara_importacao (id_tipo_manifestacao_eouv, de_tipo_manifestacao_eouv, id_tipo_procedimento) VALUES (\'7\', \'Comunicado\', NULL)');

    $this->logar('Criando a tabela md_eouv_rel_import');
    $objInfraIBanco->executarSql('CREATE TABLE md_eouv_rel_import(id_md_eouv_rel_import ' . $objInfraMetaBD->tipoNumero() . ' NOT NULL ,
      dth_importacao ' . $objInfraMetaBD->tipoDataHora() . ' NOT NULL ,
      sin_sucesso ' . $objInfraMetaBD->tipoTextoFixo(1) . ' NOT NULL ,
      dth_periodo_inicial ' . $objInfraMetaBD->tipoDataHora() . ' NULL ,
      dth_periodo_final ' . $objInfraMetaBD->tipoDataHora() . ' NULL ,
      des_log_processamento ' . $objInfraMetaBD->tipoTextoVariavel(500) . ' NULL)');
    $objInfraMetaBD->adicionarChavePrimaria('md_eouv_rel_import', 'pk_md_eouv_rel_import', array('id_md_eouv_rel_import'));

    $this->logar('Criando a tabela md_eouv_rel_import_det');
    $objInfraIBanco->executarSql('CREATE TABLE md_eouv_rel_import_det(id_md_eouv_rel_import ' . $objInfraMetaBD->tipoNumero() . ' NOT NULL ,
      num_protocolo_formatado ' . $objInfraMetaBD->tipoTextoFixo(50) . ' NOT NULL ,
      sin_sucesso ' . $objInfraMetaBD->tipoTextoFixo(1) . ' NOT NULL ,
      des_log_processamento ' . $objInfraMetaBD->tipoTextoVariavel(500) . ' NULL,
      dth_importacao ' . $objInfraMetaBD->tipoDataHora() . ' NULL)');

    $objInfraMetaBD->adicionarChavePrimaria('md_eouv_rel_import_det', 'pk_md_eouv_rel_import_det',
      array('id_md_eouv_rel_import', 'num_protocolo_formatado'));
    $objInfraMetaBD->adicionarChaveEstrangeira('fk1_md_eouv_rel_import_det', 'md_eouv_rel_import_det', array('id_md_eouv_rel_import'), 'md_eouv_rel_import', array('id_md_eouv_rel_import'));

    // Cria sequência para tabela de relatórios de importação
    $objInfraIBanco->criarSequencialNativa('seq_md_eouv_rel_import', 1);

    $this->logar('Criando Parâmetros do SEI');
    $objInfraParametro = new InfraParametro($objInfraIBanco);
    $objInfraParametro->setValor('EOUV_URL_WEBSERVICE_IMPORTACAO_ANEXO_MANIFESTACAO', 'https://falabr.cgu.gov.br/Servicos/ServicoAnexosManifestacao.svc');
    $objInfraParametro->setValor('EOUV_URL_WEBSERVICE_IMPORTACAO_MANIFESTACAO', 'https://falabr.cgu.gov.br/Servicos/ServicoConsultaManifestacao.svc');
    $objInfraParametro->setValor('ID_UNIDADE_OUVIDORIA', '110000001');
    $objInfraParametro->setValor('ID_SERIE_EXTERNO_OUVIDORIA', '92');
    $objInfraParametro->setValor('EOUV_ID_SERIE_DOCUMENTO_EXTERNO_DADOS_MANIFESTACAO', '63');
    $objInfraParametro->setValor('EOUV_DATA_INICIAL_IMPORTACAO_MANIFESTACOES ', date('d/m/Y'));
    $objInfraParametro->setValor('EOUV_URL_DETALHE_MANIFESTACAO', '');
    $objInfraParametro->setValor('EOUV_USUARIO_ACESSO_WEBSERVICE', '');
    $objInfraParametro->setValor('EOUV_SENHA_ACESSO_WEBSERVICE', '');

    $this->logar('Criando Agendamento da tarefa no SEI');
    $objInfraAgendamentoTarefaDTO = new InfraAgendamentoTarefaDTO();
    $objInfraAgendamentoTarefaDTO->setNumIdInfraAgendamentoTarefa(null);
    $objInfraAgendamentoTarefaDTO->setStrDescricao('Rotina responsável pela execução da importação de manifestações '.
      'cadastradas no FalaBR que serão importadas para o SEI como um novo processo. '.
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
    $objInfraAgendamentoTarefaDTO = $objInfraAgendamentoTarefaRN->cadastrar($objInfraAgendamentoTarefaDTO);
    $this->logar('Tarefa cadastrada com sucesso.');

    $this->logar('Verifica se já existe um usuário com nome EOUV');
    $objUsuarioDTOEouv = new UsuarioDTO();
    $objUsuarioDTOEouv->retTodos();
    $objUsuarioDTOEouv->setStrSigla('EOUV');
    $objUsuarioRN = new UsuarioRN();
    $objUsuarioDTOEouv = $objUsuarioRN->consultarRN0489($objUsuarioDTOEouv);

    if ($objUsuarioDTOEouv==null) {
      $this->logar('Criando Sistema EOUV na base do SEI...');
      $objUsuarioDTO = new UsuarioDTO();
      $objUsuarioDTO->setNumIdUsuario(null);
      $objUsuarioDTO->setNumIdOrgao(0);
      $objUsuarioDTO->setStrIdOrigem(null);
      $objUsuarioDTO->setStrSigla('EOUV');
      $objUsuarioDTO->setStrNome('Integração com sistema FalaBR');
      $objUsuarioDTO->setNumIdContato(null);
      $objUsuarioDTO->setStrStaTipo(UsuarioRN::$TU_SISTEMA);
      $objUsuarioDTO->setStrSenha(null);
      $objUsuarioDTO->setStrSinAtivo('S');
      $objUsuarioDTO = $objUsuarioRN->cadastrarRN0487($objUsuarioDTO);
    }
    else{
      $objUsuarioDTO = $objUsuarioDTOEouv;
    }

    $this->logar('Verifica se já existe um serviço CadastrarManifestacao no sistema EOUV');
    $objServicoDTO = new ServicoDTO();
    $objServicoDTO->retTodos();
    $objServicoDTO->setStrIdentificacao('CadastrarManifestacao');
    $objServicoDTO->setNumIdUsuario($objUsuarioDTO->getNumIdUsuario());
    $objServicoRN = new ServicoRN();
    $objServicoDTO = $objServicoRN->consultar($objServicoDTO);

    // Caso exista exclui e recria para garantir que está correto
    if ($objServicoDTO) {
      $this->logar('Excluindo serviço CadastrarManifestacao existente');
      $objServicoRN->excluir([$objServicoDTO]);
    }

    $this->logar('Criando Serviço CadastrarManifestacao na base do SEI...');
    $objServicoDTO = new ServicoDTO();
    $objServicoDTO->setNumIdServico(null);
    $objServicoDTO->setNumIdUsuario($objUsuarioDTO->getNumIdUsuario());
    $objServicoDTO->setStrIdentificacao('CadastrarManifestacao');
    $objServicoDTO->setStrDescricao('Cadastrar Manifestação Importada do sistema FalaBR');
    $objServicoDTO->setStrServidor('*');
    $objServicoDTO->setStrSinServidor('N');
    $objServicoDTO->setStrSinChaveAcesso('N');
    $objServicoDTO->setStrSinLinkExterno('N');
    $objServicoDTO->setStrSinAtivo('S');
    $objServicoDTO = $objServicoRN->cadastrar($objServicoDTO);

    $this->logar('Verifica se existem operações no serviço CadastrarManifestacao');
    $objOperacaoServicoDTO = new OperacaoServicoDTO();
    $objOperacaoServicoDTO->retTodos();
    $objOperacaoServicoDTO->setNumIdServico($objServicoDTO->getNumIdServico());
    $objOperacaoServicoRN = new OperacaoServicoRN();
    $arrOperacaoServicoDTO = $objOperacaoServicoRN->listar($objOperacaoServicoDTO);

    if (count($arrOperacaoServicoDTO) > 0) {
      $this->logar('Excluindo operações do serviço CadastrarManifestacao');
      $objOperacaoServicoRN->excluir($arrOperacaoServicoDTO);
    }

    $this->logar('Criando Operação na base do SEI...');
    $objOperacaoServicoDTO = new OperacaoServicoDTO();
    $objOperacaoServicoDTO->setNumIdOperacaoServico(null);
    $objOperacaoServicoDTO->setNumIdServico($objServicoDTO->getNumIdServico());
    $objOperacaoServicoDTO->setNumStaOperacaoServico(0); //Gerar Procedimento
    $objOperacaoServicoDTO->setNumIdUnidade(null);
    $objOperacaoServicoDTO->setNumIdSerie(null);
    $objOperacaoServicoDTO->setNumIdTipoProcedimento(null);
    $objOperacaoServicoDTO = $objOperacaoServicoRN->cadastrar($objOperacaoServicoDTO);
  }

  protected function instalarv300()
  {
    $objInfraIBanco = $this->inicializarObjInfraIBanco();
    $objInfraMetaBD = new InfraMetaBD($objInfraIBanco);

    $this->logar('Criando a tabela md_eouv_parametros');
    //Tabela criada para retirar os Parâmetros do Infra>Parametros do SEI

    $objInfraIBanco->executarSql('CREATE TABLE md_eouv_parametros(id_parametro ' . $objInfraMetaBD->tipoNumero() . ' NOT NULL ,
      no_parametro ' . $objInfraMetaBD->tipoTextoVariavel(100) . ' NOT NULL ,
      de_valor_parametro ' . $objInfraMetaBD->tipoTextoVariavel(455) . ' NOT NULL)');

    $objInfraMetaBD->adicionarChavePrimaria('md_eouv_parametros', 'pk_md_eouv_parametro', array('id_parametro'));

    $this->logar('Criando registros para a tabela md_eouv_parametro');
    $objInfraParametro = new InfraParametro($objInfraIBanco);
    $objInfraIBanco->executarSql('INSERT INTO md_eouv_parametros (id_parametro, no_parametro, de_valor_parametro) '.
      "VALUES ('1', 'EOUV_DATA_INICIAL_IMPORTACAO_MANIFESTACOES', '".$objInfraParametro->getValor('EOUV_DATA_INICIAL_IMPORTACAO_MANIFESTACOES')."')");
    $objInfraIBanco->executarSql('INSERT INTO md_eouv_parametros (id_parametro, no_parametro, de_valor_parametro) '.
      "VALUES ('2', 'EOUV_ID_SERIE_DOCUMENTO_EXTERNO_DADOS_MANIFESTACAO', '".$objInfraParametro->getValor('EOUV_ID_SERIE_DOCUMENTO_EXTERNO_DADOS_MANIFESTACAO')."')");
    $objInfraIBanco->executarSql('INSERT INTO md_eouv_parametros (id_parametro, no_parametro, de_valor_parametro) '.
      "VALUES ('3', 'ID_SERIE_EXTERNO_OUVIDORIA', '".$objInfraParametro->getValor('ID_SERIE_EXTERNO_OUVIDORIA')."')");
    $objInfraIBanco->executarSql('INSERT INTO md_eouv_parametros (id_parametro, no_parametro, de_valor_parametro) '.
      "VALUES ('4', 'EOUV_USUARIO_ACESSO_WEBSERVICE', '".$objInfraParametro->getValor('EOUV_USUARIO_ACESSO_WEBSERVICE')."')");
    $objInfraIBanco->executarSql('INSERT INTO md_eouv_parametros (id_parametro, no_parametro, de_valor_parametro) '.
      "VALUES ('5', 'EOUV_SENHA_ACESSO_WEBSERVICE', '".$objInfraParametro->getValor('EOUV_SENHA_ACESSO_WEBSERVICE')."')");
    $objInfraIBanco->executarSql('INSERT INTO md_eouv_parametros (id_parametro, no_parametro, de_valor_parametro) '.
      "VALUES ('6', 'CLIENT_ID', 'XXX')");
    $objInfraIBanco->executarSql('INSERT INTO md_eouv_parametros (id_parametro, no_parametro, de_valor_parametro) '.
      "VALUES ('7', 'CLIENT_SECRET', 'XXX')");
    $objInfraIBanco->executarSql('INSERT INTO md_eouv_parametros (id_parametro, no_parametro, de_valor_parametro) '.
      "VALUES ('8', 'EOUV_URL_WEBSERVICE_IMPORTACAO_MANIFESTACAO', 'https://falabr.cgu.gov.br/api/manifestacoes')");
    $objInfraIBanco->executarSql('INSERT INTO md_eouv_parametros (id_parametro, no_parametro, de_valor_parametro) '.
      "VALUES ('9', 'ID_UNIDADE_OUVIDORIA', '".$objInfraParametro->getValor('ID_UNIDADE_OUVIDORIA')."')");
    $objInfraIBanco->executarSql('INSERT INTO md_eouv_parametros (id_parametro, no_parametro, de_valor_parametro) '.
      "VALUES ('10', 'TOKEN', 'XXX')");
    $objInfraIBanco->executarSql('INSERT INTO md_eouv_parametros (id_parametro, no_parametro, de_valor_parametro) '.
      "VALUES ('11', 'IMPORTAR_DADOS_MANIFESTANTE', '1')");

    $this->logar('Apagando os registros da tabela infra_parametros que foram migrados para tabela específica');

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
    $objInfraIBanco = $this->inicializarObjInfraIBanco();
    $objInfraMetaBD = new InfraMetaBD($objInfraIBanco);

    // Atualiza chave primária da tabela depara_importacao para deixar apenas o campo de id_tipo_manifestacao_eouv
    $this->logar('Atualizando chave primária da tabela md_eouv_depara_importacao');
    $objInfraMetaBD->excluirChavePrimaria('md_eouv_depara_importacao', 'pk_md_eouv_depara_importacao');
    $objInfraMetaBD->adicionarChavePrimaria('md_eouv_depara_importacao', 'pk_md_eouv_depara_importacao', array('id_tipo_manifestacao_eouv'));

    // Atualiza coluna id_tipo_procedimento na tabela depara_importacao para permitir nulos
    $objInfraMetaBD->alterarColuna('md_eouv_depara_importacao', 'id_tipo_procedimento', $objInfraMetaBD->tipoNumero(), 'null');

    // Atualiza descrição do depara_importacao tipo 6
    $this->logar('Atualizando tipo de manifestação com ID = 6 para não classificada');
    $objInfraIBanco->executarSql('UPDATE md_eouv_depara_importacao SET '.
      "de_tipo_manifestacao_eouv = 'Não Classificada', ".
      "id_tipo_procedimento = NULL ".
      "WHERE id_tipo_manifestacao_eouv = 6");

    // Criar um "depara_importação" para a Manifestação e-Sic
    $this->logar('Criando registro de Acesso à Informação para a tabela md_eouv_depara_importacao');
    $objInfraIBanco->executarSql('INSERT INTO md_eouv_depara_importacao (id_tipo_manifestacao_eouv, de_tipo_manifestacao_eouv, id_tipo_procedimento) '.
      "VALUES (8, 'Acesso à Informação', NULL)");

    /**
     * Criar coluna na tabela md_eouv_rel_import para identificar qual o tipo de manifestação
     *
     * - 'P' (e-Ouv) - manifestações e-ouv padrão - tipos 1 a 7
     * - 'R' (e-Sic) - manifestações e-sic com 'R'ecursos - tipo 8
     */
    $this->logar('Criando coluna para tipo de manifestação na tabela md_eouv_rel_import');
    $objInfraMetaBD->adicionarColuna('md_eouv_rel_import', 'tip_manifestacao', $objInfraMetaBD->tipoTextoFixo(2), 'null');
    $objInfraIBanco->executarSql("UPDATE md_eouv_rel_import SET tip_manifestacao = 'P'");
    $objInfraMetaBD->alterarColuna('md_eouv_rel_import', 'tip_manifestacao', $objInfraMetaBD->tipoTextoFixo(2), 'not null');

    /**
     * Criar coluna na tabela md_eouv_rel_import_det para identificar qual o tipo de manifestação
     *
     * - 'P' (e-Ouv) - manifestações e-ouv padrão - tipos 1 a 7
     * - 'R' (e-Sic) - manifestações e-sic com 'R'ecursos - tipo 8
     */
    $this->logar('Criando coluna para tipo de manifestação na na tabela md_eouv_rel_import_det');
    $objInfraMetaBD->adicionarColuna('md_eouv_rel_import_det', 'tip_manifestacao', $objInfraMetaBD->tipoTextoFixo(2), 'null');
    $objInfraIBanco->executarSql("UPDATE md_eouv_rel_import_det SET tip_manifestacao = 'P'");
    $objInfraMetaBD->alterarColuna('md_eouv_rel_import_det', 'tip_manifestacao', $objInfraMetaBD->tipoTextoFixo(2), 'not null');
    $objInfraMetaBD->adicionarColuna('md_eouv_rel_import_det', 'dth_prazo_atendimento', $objInfraMetaBD->tipoDataHora(), 'null');

    /**
     * Criar coluna na tabela md_eouv_parametros para identificar qual o tipo de parâmetro
     *
     * - 'eouv' (e-Ouv) - parâmetros do e-ouv [padrão]
     * - 'esicR' (e-Sic) - parâmetros do e-sic
     */
    $this->logar('Criando coluna para tipo de parâmetro na tabela md_eouv_parametros');
    $objInfraMetaBD->adicionarColuna('md_eouv_parametros', 'de_tipo', $objInfraMetaBD->tipoTextoVariavel(10), 'null');
    $objInfraIBanco->executarSql("UPDATE md_eouv_parametros SET de_tipo = 'eouv'");
    $objInfraMetaBD->alterarColuna('md_eouv_parametros', 'de_tipo', $objInfraMetaBD->tipoTextoVariavel(10), 'not null');

    /**
     * Cria parâmetros na tabela md_eouv_parametros para manifestações do e-Sic (tipo 8)
     */
    $this->logar('Cria registros na tabela md_eouv_parametros para manifestações e-Sic (tipo 8)');
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
      $id = $arrParametro['id'];
      $nome = $arrParametro['parametro'];
      $valor = $arrParametro['valor'];
      $objInfraIBanco->executarSql('INSERT INTO md_eouv_parametros (id_parametro, no_parametro, de_valor_parametro, de_tipo) '.
        "VALUES ($id, '$nome', '$valor', 'esic')");
    }

    // Cria agendamento para importação de tarefas e-Sic
    $this->logar('Cria agendamento para importar manifestações e-Sic');
    $objInfraAgendamentoTarefaDTO = new InfraAgendamentoTarefaDTO();
    $objInfraAgendamentoTarefaDTO->setStrDescricao('Rotina responsável pela execução da '.
      'importação de manifestações de acesso à informação cadastradas no FalaBR '.
      'que serão importadas para o SEI como um novo processo. Se baseia na data da '.
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
    $this->logar('Remove parâmetro ID_SERIE_EXTERNO_OUVIDORIA, que não é mais utilizado');
    $objInfraIBanco->executarSql("DELETE FROM md_eouv_parametros WHERE no_parametro = 'ID_SERIE_EXTERNO_OUVIDORIA'");
  }

  protected function instalarv402() {
    $objInfraIBanco = $this->inicializarObjInfraIBanco();
    $objInfraMetaBD = new InfraMetaBD($objInfraIBanco);

    // Altera tipo da coluna 'valor' da tabela parâmetros para texto grande, a fim de
    // evitar problemas com o tamanho do token da API
    $objInfraMetaBD->alterarColuna('md_eouv_parametros', 'de_valor_parametro', $objInfraMetaBD->tipoTextoGrande(), 'not null');
  }

  protected function instalarv410(){
    $objInfraIBanco = $this->inicializarObjInfraIBanco();
    $objInfraMetaBD = new InfraMetaBD($objInfraIBanco);

    $this->logar('Criando coluna sin_ativo para tabela md_eouv_depara_importacao');
    $objInfraMetaBD->adicionarColuna('md_eouv_depara_importacao', 'sin_ativo', $objInfraMetaBD->tipoTextoFixo(1), 'null');
    $objInfraIBanco->executarSql("UPDATE md_eouv_depara_importacao SET sin_ativo = 'S'");
    $objInfraMetaBD->alterarColuna('md_eouv_depara_importacao', 'sin_ativo', $objInfraMetaBD->tipoTextoFixo(1), 'not null');

    $this->logar('Excluindo coluna tip_manifestacao da tabela md_eouv_rel_import');
    $objInfraMetaBD->excluirColuna('md_eouv_rel_import', 'tip_manifestacao');

    $this->logar('Excluindo coluna dth_importacao da tabela md_eouv_rel_import_det');
    $objInfraMetaBD->excluirColuna('md_eouv_rel_import_det', 'dth_importacao');

    $infraAgendamentoTarefaRN = new InfraAgendamentoTarefaRN();

    $this->logar('Removendo agendamentos antigos');

    $infraAgendamentoTarefaDTO = new InfraAgendamentoTarefaDTO();
    $infraAgendamentoTarefaDTO->retTodos();
    $infraAgendamentoTarefaDTO->setStrComando('MdCguEouvAgendamentoRN::executarImportacaoManifestacaoEOuv');
    $infraAgendamentoTarefaDTO->setBolExclusaoLogica(false);
    $arrAgendamentoEouv = $infraAgendamentoTarefaRN->listar($infraAgendamentoTarefaDTO);
    if (count($arrAgendamentoEouv) > 0) {
        $infraAgendamentoTarefaRN->excluir($arrAgendamentoEouv);
    }

    $infraAgendamentoTarefaDTO = new InfraAgendamentoTarefaDTO();
    $infraAgendamentoTarefaDTO->retTodos();
    $infraAgendamentoTarefaDTO->setStrComando('MdCguEouvAgendamentoRN::executarImportacaoManifestacaoESic');
    $infraAgendamentoTarefaDTO->setBolExclusaoLogica(false);
    $arrAgendamentoEsic = $infraAgendamentoTarefaRN->listar($infraAgendamentoTarefaDTO);
    if (count($arrAgendamentoEsic) > 0) {
        $infraAgendamentoTarefaRN->excluir($arrAgendamentoEsic);
    }

    $this->logar('Criando novo agendamento unificado');

    $infraAgendamentoNovaTarefaDTO = new InfraAgendamentoTarefaDTO();
    $infraAgendamentoNovaTarefaDTO->setStrDescricao('Rotina responsável pela execução da importação de manifestações '.
      'cadastradas no FalaBR que serão importadas para o SEI como um novo processo. '.
      'Se baseia na data da última execução com sucesso até a data atual.');
    $infraAgendamentoNovaTarefaDTO->setStrComando('MdCguEouvAgendamentoRN::executarImportacaoManifestacaoFalaBr');
    $infraAgendamentoNovaTarefaDTO->setStrSinAtivo('S');
    $infraAgendamentoNovaTarefaDTO->setStrStaPeriodicidadeExecucao('D');
    $infraAgendamentoNovaTarefaDTO->setStrPeriodicidadeComplemento('1');
    $infraAgendamentoNovaTarefaDTO->setStrSinSucesso('S');
    $infraAgendamentoNovaTarefaDTO->setDthUltimaExecucao(null);
    $infraAgendamentoNovaTarefaDTO->setDthUltimaConclusao(null);
    $infraAgendamentoNovaTarefaDTO->setStrParametro(null);
    $infraAgendamentoNovaTarefaDTO->setStrEmailErro('');
    $infraAgendamentoTarefaRN->cadastrar($infraAgendamentoNovaTarefaDTO);

    $this->logar('Remove coluna de_tipo da tabela de parâmetros');
    $objInfraMetaBD->excluirColuna('md_eouv_parametros', 'de_tipo');

    $this->logar('Remove parâmetro ESIC_URL_WEBSERVICE_IMPORTACAO_RECURSOS');
    $objInfraIBanco->executarSql("DELETE FROM md_eouv_parametros WHERE no_parametro = 'ESIC_URL_WEBSERVICE_IMPORTACAO_RECURSOS'");

    $this->logar('Ajusta parâmetro EOUV_URL_WEBSERVICE_IMPORTACAO_MANIFESTACAO');
    $arrParametros = $objInfraIBanco->consultarSql("SELECT * FROM md_eouv_parametros WHERE no_parametro = 'EOUV_URL_WEBSERVICE_IMPORTACAO_MANIFESTACAO'");
    if (count($arrParametros) == 0) {
      throw new InfraException('Parâmetro EOUV_URL_WEBSERVICE_IMPORTACAO_MANIFESTACAO não encontrado');
    }
    $strURLWebService = $arrParametros[0]['de_valor_parametro'];
    if (trim($strURLWebService) != '') {
      $arrParsedURL = parse_url($strURLWebService);
      $strURLWebService = $arrParsedURL['scheme'] . '://' . $arrParsedURL['host'];
      $objInfraIBanco->executarSql("UPDATE md_eouv_parametros SET de_valor_parametro = '$strURLWebService' ".
        "WHERE no_parametro = 'EOUV_URL_WEBSERVICE_IMPORTACAO_MANIFESTACAO'");
    }

    $this->logar('Remove parâmetro ESIC_DATA_INICIAL_IMPORTACAO_MANIFESTACOES');
    $objInfraIBanco->executarSql("DELETE FROM md_eouv_parametros WHERE no_parametro = 'ESIC_DATA_INICIAL_IMPORTACAO_MANIFESTACOES'");
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