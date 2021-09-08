<?php

/**
 * Created by PhpStorm.
 * User: flaviomy
 * Date: 25/10/2017
 * Time: 17:53
 */
require_once dirname(__FILE__) . '/../../../../SEI.php';

class MdCguEouvAtualizadorBDRN extends InfraRN
{
    private $numSeg = 0;
    private $versaoAtualDesteModulo = '4.0.0';
    private $nomeDesteModulo = 'EOUV - Integra��o com sistema FalaBR (E-ouv)';
    private $prefixoParametro = 'MD_CGU_EOUV';
    private $nomeParametroVersaoModulo = 'VERSAO_MODULO_CGU_EOUV';
    private $historicoVersoes = array('2.0.5', '3.0.0', '4.0.0');
    /**
     * 1. Come�amos a contralar a partir da vers�o 2.0.5 que � a �ltima est�vel para o SEI 3.0
     * 2. A vers�o 3.0.0 come�a a utilizar a vers�o REST dos webservices do E-Ouv
     * 3. A vers�o 4.0.0 importa manifesta��es do tipo 8 (acesso � informa��o) que s�o oriundas antigo e-Sic integrado
     * ao FalaBR, esta vers�o importa tambem os recursos de 1� e 2� inst�ncia
     */

    public function __construct()
    {
        parent::__construct();
    }

    protected function inicializarObjInfraIBanco()
    {
        return BancoSEI::getInstance();
    }

    private function inicializar($strTitulo)
    {

        ini_set('max_execution_time', '0');
        ini_set('memory_limit', '-1');

        try {
            @ini_set('zlib.output_compression', '0');
            @ini_set('implicit_flush', '1');
        } catch (Exception $e) {
        }

        ob_implicit_flush();

        InfraDebug::getInstance()->setBolLigado(true);
        InfraDebug::getInstance()->setBolDebugInfra(true);
        InfraDebug::getInstance()->setBolEcho(true);
        InfraDebug::getInstance()->limpar();

        $this->numSeg = InfraUtil::verificarTempoProcessamento();

        $this->logar($strTitulo);
    }

    private function logar($strMsg)
    {
        InfraDebug::getInstance()->gravar($strMsg);
        flush();
    }

    private function finalizar($strMsg = null, $bolErro)
    {

        if (!$bolErro) {
            $this->numSeg = InfraUtil::verificarTempoProcessamento($this->numSeg);
            $this->logar('TEMPO TOTAL DE EXECU��O: ' . $this->numSeg . ' s');
        } else {
            $strMsg = 'ERRO: ' . $strMsg;
        }

        if ($strMsg != null) {
            $this->logar($strMsg);
        }

        InfraDebug::getInstance()->setBolLigado(false);
        InfraDebug::getInstance()->setBolDebugInfra(false);
        InfraDebug::getInstance()->setBolEcho(false);
        $this->numSeg = 0;
        die;
    }

    protected function atualizarVersaoConectado()
    {

        try {
            $this->inicializar('INICIANDO INSTALA��O/ATUALIZACAO DO MODULO ' . $this->nomeDesteModulo . ' NO SEI VERSAO ' . SEI_VERSAO);

            //testando versao do framework
            $numVersaoInfraRequerida = '1.502';
            $versaoInfraFormatada = (int) str_replace('.','', VERSAO_INFRA);
            $versaoInfraReqFormatada = (int) str_replace('.','', $numVersaoInfraRequerida);

            if ($versaoInfraFormatada < $versaoInfraReqFormatada){
            $this->finalizar('VERSAO DO FRAMEWORK PHP INCOMPATIVEL (VERSAO ATUAL '.VERSAO_INFRA.', SENDO REQUERIDA VERSAO IGUAL OU SUPERIOR A '.$numVersaoInfraRequerida.')',true);
            }

            //checando BDs suportados
            if (!(BancoSEI::getInstance() instanceof InfraMySql) &&
                !(BancoSEI::getInstance() instanceof InfraSqlServer) &&
                !(BancoSEI::getInstance() instanceof InfraOracle)
            ) {
                $this->finalizar('BANCO DE DADOS NAO SUPORTADO: ' . get_parent_class(BancoSEI::getInstance()), true);
            }

            //checando permissoes na base de dados
            $objInfraMetaBD = new InfraMetaBD(BancoSEI::getInstance());

            if (count($objInfraMetaBD->obterTabelas('sei_teste')) == 0) {
                BancoSEI::getInstance()->executarSql('CREATE TABLE sei_teste (id ' . $objInfraMetaBD->tipoNumero() . ' null)');
            }

            BancoSEI::getInstance()->executarSql('DROP TABLE sei_teste');

            $objInfraParametro = new InfraParametro(BancoSEI::getInstance());

            $strVersaoModuloEOuv = $objInfraParametro->getValor($this->nomeParametroVersaoModulo, false);

            //VERIFICANDO QUAL VERSAO DEVE SER INSTALADA NESTA EXECUCAO
            //nao tem nenhuma versao ainda, instalar primeira vers�o
            if (InfraString::isBolVazia($strVersaoModuloEOuv)) {
                $this->instalarv205();
                $this->instalarv300();
                $this->instalarv400();
                $this->logar('INSTALA��O/ATUALIZA��O DA VERS�O ' . $this->versaoAtualDesteModulo . ' DO MODULO ' . $this->nomeDesteModulo . ' INSTALADAS COM SUCESSO NA BASE DO SEI');
                $this->finalizar('FIM', false);
            } elseif ($strVersaoModuloEOuv == '2.0.5') {
                $this->instalarv300();
                $this->instalarv400();
                $this->logar('INSTALA��O/ATUALIZA��O DA VERS�O ' . $this->versaoAtualDesteModulo . ' DO MODULO ' . $this->nomeDesteModulo . ' INSTALADAS COM SUCESSO NA BASE DO SEI');
                $this->finalizar('FIM', false);
            } elseif ($strVersaoModuloEOuv == '3.0.0') {
                $this->instalarv400();
                $this->logar('INSTALA��O/ATUALIZA��O DA VERS�O ' . $this->versaoAtualDesteModulo . ' DO MODULO ' . $this->nomeDesteModulo . ' INSTALADAS COM SUCESSO NA BASE DO SEI');
                $this->finalizar('FIM', false);
            }

            InfraDebug::getInstance()->setBolLigado(false);
            InfraDebug::getInstance()->setBolDebugInfra(false);
            InfraDebug::getInstance()->setBolEcho(false);

        } catch (Exception $e) {
            InfraDebug::getInstance()->setBolLigado(true);
            InfraDebug::getInstance()->setBolDebugInfra(true);
            InfraDebug::getInstance()->setBolEcho(true);
            throw new InfraException('Erro atualizando vers�o.', $e);
            $this->logar($e->getTraceAsString());
            $this->finalizar($e, true);

        }

    }

    private function instalarv205()
    {
        SessaoInfra::setObjInfraSessao(SessaoSEI::getInstance());
        BancoInfra::setObjInfraIBanco(BancoSEI::getInstance());

        $objInfraMetaBD = new InfraMetaBD(BancoSEI::getInstance());
        $this->logar('EXECUTANDO A INSTALACAO DA VERSAO 2.0.5 DO MODULO ' . $this->nomeDesteModulo . ' NA BASE DO SEI');

        //6.1	Para o mapeamento DE-PARA entre os Tipos de Manifesta��o E-ouv e Tipo de processo SEI
        $this->logar('CRIANDO A TABELA md_eouv_depara_importacao');

        BancoSEI::getInstance()->executarSql('CREATE TABLE md_eouv_depara_importacao(id_tipo_manifestacao_eouv ' . $objInfraMetaBD->tipoNumero() . ' NOT NULL ,
            id_tipo_procedimento ' . $objInfraMetaBD->tipoNumero() . ' NOT NULL ,
		    de_tipo_manifestacao_eouv ' . $objInfraMetaBD->tipoTextoVariavel(50) . ' NULL)');

        $objInfraMetaBD->adicionarChavePrimaria('md_eouv_depara_importacao', 'pk_md_eouv_depara_importacao', array('id_tipo_manifestacao_eouv', 'id_tipo_procedimento'));
        $objInfraMetaBD->adicionarChaveEstrangeira('fk1_md_eouv_tipo_procedimento', 'md_eouv_depara_importacao', array('id_tipo_procedimento'), 'tipo_procedimento', array('id_tipo_procedimento'));
        $objInfraMetaBD->criarIndice('md_eouv_depara_importacao', 'i01_md_eouv_depara_importacao', array('id_tipo_procedimento'));

        $this->logar('CRIANDO REGISTROS PARA A TABELA md_eouv_depara_importacao');
        BancoSEI::getInstance()->executarSql('INSERT INTO md_eouv_depara_importacao (id_tipo_manifestacao_eouv, de_tipo_manifestacao_eouv, id_tipo_procedimento) VALUES (\'1\', \'Den�ncia\', \'100000335\');');
        BancoSEI::getInstance()->executarSql('INSERT INTO md_eouv_depara_importacao (id_tipo_manifestacao_eouv, de_tipo_manifestacao_eouv, id_tipo_procedimento) VALUES (\'2\', \'Reclama��o\', \'100000336\');');
        BancoSEI::getInstance()->executarSql('INSERT INTO md_eouv_depara_importacao (id_tipo_manifestacao_eouv, de_tipo_manifestacao_eouv, id_tipo_procedimento) VALUES (\'3\', \'Elogio\', \'100000333\');');
        BancoSEI::getInstance()->executarSql('INSERT INTO md_eouv_depara_importacao (id_tipo_manifestacao_eouv, de_tipo_manifestacao_eouv, id_tipo_procedimento) VALUES (\'4\', \'Sugest�o\', \'100000338\');');
        BancoSEI::getInstance()->executarSql('INSERT INTO md_eouv_depara_importacao (id_tipo_manifestacao_eouv, de_tipo_manifestacao_eouv, id_tipo_procedimento) VALUES (\'5\', \'Solicita��o\', \'100000334\');');
        BancoSEI::getInstance()->executarSql('INSERT INTO md_eouv_depara_importacao (id_tipo_manifestacao_eouv, de_tipo_manifestacao_eouv, id_tipo_procedimento) VALUES (\'6\', \'Simplifique\', \'100000334\');');
        BancoSEI::getInstance()->executarSql('INSERT INTO md_eouv_depara_importacao (id_tipo_manifestacao_eouv, de_tipo_manifestacao_eouv, id_tipo_procedimento) VALUES (\'7\', \'Comunicado\', \'100000334\');');

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

        $this->logar('CRIANDO Par�metros do Sei');
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
        $objInfraAgendamentoTarefaDTO->setStrDescricao('Rotina respons�vel pela execu��o da importa��o de manifesta��es cadastradas no E-Ouv que ser�o importadas para o SEI como um novo processo. Se baseia na data da �ltima execu��o com sucesso at� a data atual.');
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

        $this->logar('Primeiro verifica se j� existe um usu�rio com nome EOUV');
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
            $objUsuarioDTO->setStrNome('Integra��o com sistema E-Ouv');
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

        $this->logar('Criando Servi�o CadastrarManifestacao NA BASE DO SEI...');
        $objServicoDTO = new ServicoDTO();
        $objServicoDTO->setNumIdServico(null);
        $objServicoDTO->setNumIdUsuario($objUsuarioDTO->getNumIdUsuario());
        $objServicoDTO->setStrIdentificacao('CadastrarManifestacao');
        $objServicoDTO->setStrDescricao('Cadastrar Manifesta��o Importada do sistema E-Ouv');
        $objServicoDTO->setStrServidor('*');
        $objServicoDTO->setStrSinLinkExterno('N');
        $objServicoDTO->setStrSinAtivo('S');
        $objServicoRN = new ServicoRN();
        $objServicoDTO = $objServicoRN->cadastrar($objServicoDTO);

        $this->logar('Criando Opera��o NA BASE DO SEI...');
        $objOperacaoServicoDTO = new OperacaoServicoDTO();
        $objOperacaoServicoDTO->setNumIdOperacaoServico(null);
        $objOperacaoServicoDTO->setNumIdServico($objServicoDTO->getNumIdServico());
        $objOperacaoServicoDTO->setNumStaOperacaoServico(0); //Gerar Procedimento
        $objOperacaoServicoDTO->setNumIdUnidade(null);
        $objOperacaoServicoDTO->setNumIdSerie(null);
        $objOperacaoServicoDTO->setNumIdTipoProcedimento(null);
        $objOperacaoServicoRN = new OperacaoServicoRN();
        $objOperacaoServicoDTO = $objOperacaoServicoRN->cadastrar($objOperacaoServicoDTO);

        $this->logar('ADICIONANDO PAR�METRO '.$this->nomeParametroVersaoModulo.' NA TABELA infra_parametro PARA CONTROLAR A VERS�O DO M�DULO');
        BancoSEI::getInstance()->executarSql('INSERT INTO infra_parametro (valor, nome ) VALUES( \'2.0.5\',  \'' . $this->nomeParametroVersaoModulo . '\' )');

    }

    private function instalarv300()
    {
        SessaoInfra::setObjInfraSessao(SessaoSEI::getInstance());
        BancoInfra::setObjInfraIBanco(BancoSEI::getInstance());

        $objInfraMetaBD = new InfraMetaBD(BancoSEI::getInstance());
        $this->logar('EXECUTANDO A INSTALACAO DA VERSAO 3.0.0 DO MODULO ' . $this->nomeDesteModulo . ' NA BASE DO SEI');

        $this->logar('CRIANDO A TABELA md_eouv_parametros');
        //Tabela criada para retirar os Par�metros do Infra>Parametros do SEI

        BancoSEI::getInstance()->executarSql('CREATE TABLE md_eouv_parametros(id_parametro ' . $objInfraMetaBD->tipoNumero() . ' NOT NULL ,
            no_parametro ' . $objInfraMetaBD->tipoTextoVariavel(100) . ' NOT NULL ,
		    de_valor_parametro ' . $objInfraMetaBD->tipoTextoVariavel(455) . ' NOT NULL)');

        $objInfraMetaBD->adicionarChavePrimaria('md_eouv_parametros', 'pk_md_eouv_parametro', array('id_parametro'));

        $this->logar('CRIANDO REGISTROS PARA A TABELA md_eouv_parametro');
        BancoSEI::getInstance()->executarSql('INSERT INTO md_eouv_parametros (id_parametro, no_parametro, de_valor_parametro) VALUES (\'1\', \'EOUV_DATA_INICIAL_IMPORTACAO_MANIFESTACOES\', \'01/01/1900\');');
        BancoSEI::getInstance()->executarSql('INSERT INTO md_eouv_parametros (id_parametro, no_parametro, de_valor_parametro) VALUES (\'2\', \'EOUV_ID_SERIE_DOCUMENTO_EXTERNO_DADOS_MANIFESTACAO\', \'63\');');
        BancoSEI::getInstance()->executarSql('INSERT INTO md_eouv_parametros (id_parametro, no_parametro, de_valor_parametro) VALUES (\'3\', \'ID_SERIE_EXTERNO_OUVIDORIA\', \'92\');');
        BancoSEI::getInstance()->executarSql('INSERT INTO md_eouv_parametros (id_parametro, no_parametro, de_valor_parametro) VALUES (\'4\', \'EOUV_USUARIO_ACESSO_WEBSERVICE\', \'nomeUsuarioWebService\');');
        BancoSEI::getInstance()->executarSql('INSERT INTO md_eouv_parametros (id_parametro, no_parametro, de_valor_parametro) VALUES (\'5\', \'EOUV_SENHA_ACESSO_WEBSERVICE\', \'senhaUsuarioWebService\');');
        BancoSEI::getInstance()->executarSql('INSERT INTO md_eouv_parametros (id_parametro, no_parametro, de_valor_parametro) VALUES (\'6\', \'CLIENT_ID\', \'XXX\');');
        BancoSEI::getInstance()->executarSql('INSERT INTO md_eouv_parametros (id_parametro, no_parametro, de_valor_parametro) VALUES (\'7\', \'CLIENT_SECRET\', \'XXX\');');
        BancoSEI::getInstance()->executarSql('INSERT INTO md_eouv_parametros (id_parametro, no_parametro, de_valor_parametro) VALUES (\'8\', \'EOUV_URL_WEBSERVICE_IMPORTACAO_MANIFESTACAO\', \'https://treinamentoouvidorias.cgu.gov.br/api/manifestacoes\');');
        BancoSEI::getInstance()->executarSql('INSERT INTO md_eouv_parametros (id_parametro, no_parametro, de_valor_parametro) VALUES (\'9\', \'ID_UNIDADE_OUVIDORIA\', \'110000001\');');
        BancoSEI::getInstance()->executarSql('INSERT INTO md_eouv_parametros (id_parametro, no_parametro, de_valor_parametro) VALUES (\'10\', \'TOKEN\', \'XXX\');');
        BancoSEI::getInstance()->executarSql('INSERT INTO md_eouv_parametros (id_parametro, no_parametro, de_valor_parametro) VALUES (\'10\', \'IMPORTAR_DADOS_MANIFESTANTE\', \'1\');');

        $this->logar('APAGANDO OS REGISTROS DA TABELA INFRA_PARAMETROS USADOS NA VERS�O 2.0.5 E QUE AGORA N�O S�O MAIS NECESS�RIOS');

        $arrItensParametrosAExcluir = array(
            'EOUV_URL_WEBSERVICE_IMPORTACAO_ANEXO_MANIFESTACAO',
            'EOUV_URL_WEBSERVICE_IMPORTACAO_MANIFESTACAO',
            'ID_UNIDADE_OUVIDORIA',
            'ID_SERIE_EXTERNO_OUVIDORIA',
            'EOUV_ID_SERIE_DOCUMENTO_EXTERNO_DADOS_MANIFESTACAO',
            'EOUV_DATA_INICIAL_IMPORTACAO_MANIFESTACOES ',
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


        $this->logar('ATUALIZANDO PAR�METRO '.$this->nomeParametroVersaoModulo.' NA TABELA infra_parametro PARA CONTROLAR A VERS�O DO M�DULO');
        BancoSEI::getInstance()->executarSql('UPDATE infra_parametro SET valor = \'3.0.0\' WHERE nome = \'' . $this->nomeParametroVersaoModulo . '\' ');

    }

    private function instalarv400()
    {
        SessaoInfra::setObjInfraSessao(SessaoSEI::getInstance());
        BancoInfra::setObjInfraIBanco(BancoSEI::getInstance());

        $objInfraMetaBD = new InfraMetaBD(BancoSEI::getInstance());
        $this->logar('EXECUTANDO A INSTALACAO DA VERSAO 4.0.0 DO MODULO ' . $this->nomeDesteModulo . ' NA BASE DO SEI');

        /**
         * @todo - Evert - Inserir no manual para criar o tipo_procedimento via sei e inserir aqui antes de rodar o script
         */
        /**
         * Criar um "tipo_procedimento" para a Manifesta��o e-Sic
         */
        $this->logar('CRIANDO REGISTROS PARA A TABELA tipo_procedimento');
        BancoSEI::getInstance()->executarSql('INSERT INTO tipo_procedimento (id_tipo_procedimento, nome, descricao, sin_ativo, sta_nivel_acesso_sugestao, sin_interno, sin_ouvidoria, sin_individual, id_hipotese_legal_sugestao, sta_grau_sigilo_sugestao) VALUES (100001514, \'e-Sic Ouvidoria: Acesso � Informa��o\', \'Ouvidoria: Manifesta��o Tipo 8\', \'S\', \'0\', \'N\', \'N\', \'N\', NULL, NULL);');

        /**
         * Criar um "depara_importa��o" para a Manifesta��o e-Sic
         */
        $this->logar('CRIANDO REGISTROS PARA A TABELA md_eouv_depara_importacao');
        BancoSEI::getInstance()->executarSql('INSERT INTO md_eouv_depara_importacao (id_tipo_manifestacao_eouv, id_tipo_procedimento, de_tipo_manifestacao_eouv) VALUES (8, 100001514, \'Acesso � Informa��o\');');

        /**
         * Criar coluna na tabela md_eouv_rel_import para identificar qual o tipo de manifesta��o
         *
         * - 'P' (e-Ouv) - manifesta��es e-ouv padr�o - tipos 1 a 7
         * - 'R' (e-Sic) - manifesta��es e-sic com 'R'ecursos - tipo 8
         */
        $this->logar('CRIANDO COLUNA PARA TIPO DE MANIFESTA��O PARA A TABELA md_eouv_rel_import');
        BancoSEI::getInstance()->executarSql('ALTER TABLE md_eouv_rel_import ADD tipo_manifestacao ' . $objInfraMetaBD->tipoTextoFixo(2) . ' NOT NULL DEFAULT (\'P\');');

        /**
         * Criar coluna na tabela md_eouv_rel_import_det para identificar qual o tipo de manifesta��o
         *
         * - 'P' (e-Ouv) - manifesta��es e-ouv padr�o - tipos 1 a 7
         * - 'R' (e-Sic) - manifesta��es e-sic com 'R'ecursos - tipo 8
         */
        $this->logar('CRIANDO COLUNA PARA TIPO DE MANIFESTA��O PARA A TABELA md_eouv_rel_import_det');
        BancoSEI::getInstance()->executarSql('ALTER TABLE md_eouv_rel_import_det ADD tip_manifestacao ' . $objInfraMetaBD->tipoTextoFixo(2) . ' NOT NULL DEFAULT (\'P\');');
        BancoSEI::getInstance()->executarSql('ALTER TABLE md_eouv_rel_import_det ADD dth_prazo_atendimento ' . $objInfraMetaBD->tipoDataHora() . ' NULL;');

        /**
         * Cria par�metros na tabela md_eouv_parametros para manifesta��es do e-Sic (tipo 8)
         */
        $this->logar('CRIA REGISTROS NA TABELA md_eouv_parametros PARA MANIFESTA��ES DO E-SIC (TIPO 8)');
        BancoSEI::getInstance()->executarSql('INSERT INTO md_eouv_parametros (id_parametro, no_parametro, de_valor_parametro, de_tipo) VALUES (12, \'ESIC_DATA_INICIAL_IMPORTACAO_MANIFESTACOES\', \'01/01/1900\', \'esic\');');
        BancoSEI::getInstance()->executarSql('INSERT INTO md_eouv_parametros (id_parametro, no_parametro, de_valor_parametro, de_tipo) VALUES (13, \'ESIC_URL_WEBSERVICE_IMPORTACAO_RECURSOS\', \'https://treinafalabr.cgu.gov.br/api/recursos\', \'esic\');');
        BancoSEI::getInstance()->executarSql('INSERT INTO md_eouv_parametros (id_parametro, no_parametro, de_valor_parametro, de_tipo) VALUES (14, \'ESIC_ID_UNIDADE_PRINCIPAL\', \'110000001\', \'esic\');');
        BancoSEI::getInstance()->executarSql('INSERT INTO md_eouv_parametros (id_parametro, no_parametro, de_valor_parametro, de_tipo) VALUES (15, \'ESIC_ID_UNIDADE_RECURSO_PRIMEIRA_INSTANCIA\', \'110000001\', \'esic\');');
        BancoSEI::getInstance()->executarSql('INSERT INTO md_eouv_parametros (id_parametro, no_parametro, de_valor_parametro, de_tipo) VALUES (16, \'ESIC_ID_UNIDADE_RECURSO_SEGUNDA_INSTANCIA\', \'110000001\', \'esic\');');
        BancoSEI::getInstance()->executarSql('INSERT INTO md_eouv_parametros (id_parametro, no_parametro, de_valor_parametro, de_tipo) VALUES (17, \'ESIC_ID_UNIDADE_RECURSO_TERCEIRA_INSTANCIA\', \'110000001\', \'esic\');');
        BancoSEI::getInstance()->executarSql('INSERT INTO md_eouv_parametros (id_parametro, no_parametro, de_valor_parametro, de_tipo) VALUES (18, \'ESIC_ID_UNIDADE_RECURSO_PEDIDO_REVISAO\', \'110000001\', \'esic\');');

        /**
         * Atualizar vers�o do m�dulo na tabela infra-parametro do SEI
         */
        $this->logar('ATUALIZANDO PAR�METRO '.$this->nomeParametroVersaoModulo.' NA TABELA infra_parametro PARA CONTROLAR A VERS�O DO M�DULO');
        BancoSEI::getInstance()->executarSql('UPDATE infra_parametro SET valor = \'4.0.0\' WHERE nome = \'' . $this->nomeParametroVersaoModulo . '\' ');
    }
}