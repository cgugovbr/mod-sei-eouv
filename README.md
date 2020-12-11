
# Módulo de Integração SEI & FalaBR (e-Ouv e e-Sic)

## Requisitos

- SEI versão 3.0.0 ou superior (verificar versão do SEI no arquivo /sei/web/SEI.php).

- Utilizar o Sistema FalaBR do Governo Federal (e-Ouv e e-Sic). Caso ainda não tenha aderido ao FalaBR e queira saber mais informações acesse https://falabr.cgu.gov.br/.

- [IMPORTANTE] Para executar os scripts de instalação/atualização (itens 7 e 8 abaixo), o usuário configurado nos arquivos **ConfiguracaoSEI.php** e **ConfiguracaoSip.php**, deverá ter permissão de acesso total ao banco de dados do SEI e do SIP, permitindo criação e exclusão de tabelas.

## Instalação/atualização e configuração

### Procedimentos antes da instalação

1. Fazer backup completo dos bancos de dados do SEI e do SIP.

2. **[IMPORTANTE]** Inserir os Tipos de Procedimento para cada tipo de Manifestação no SEI

Acesse no SEI o menu *Administração > Tipos de Processos > Listar* para verificar os tipos já existentes, conforme tela abaixo:

![SEI - Listar tipos de documentos](https://github.com/cgugovbr/imagens/blob/main/listar_tipo_documentos.jpg?raw=true)

> Você poderá criar um novo tipo de documento para cada tipo de manifestação do FalaBR se for o caso. 

Anote os IDs de cada *Tipo de Processo* que será vinculado os processos importados do FalaBR. Estes código deverão ser atualizados no arquivo `./sei/web/modulos/cgu/mod-se-eouv/rn/MdCguEouvAtualizadorBDRN.php` conforme descrito no item 6

> Este ítem é pré-requisito para a execução do script no item 8

Abaixo os tipos de manifestações do FalaBR que serão importadas para o SEI:

|id_tipo_manifestacao_eouv |id_tipo_procedimento    |de_tipo_manifestacao_eouv |
| :-: 			   | :-: 		    | :-- 		       |
|1                         |`xxx`                   |Denúncia                  |
|2                         |`xxx`                   |Reclamação                |
|3                         |`xxx`                   |Elogio                    |
|4                         |`xxx`                   |Sugestão                  |
|5                         |`xxx`                   |Solicitação               |
|6                         |`xxx`                   |Simplifique               |
|7                         |`xxx`                   |Comunicado                |
|8 			   |`xxx`		    |Acesso à Informação       |


### Instalação/atualização

3. Baixar os arquivos deste repositório e colocar na pasta */sei/web/modulos/cgu/mod-sei-eouv*

Poderá baixar usando *git* (verificar o caminho onde o SEI está instalado, no exemplo abaixo segue o padrão '/opt'):

```bash
$ cd /opt/sei/web/modulos
$ mkdir -p cgu
$ git clone git@github.com:cgugovbr/mod-sei-eouv.git
```

Ou baixar a versão desejada usando o link:

https://github.com/cgugovbr/mod-sei-eouv/archive/4.0.0.zip

4. Copiar os scripts de instalação para as pastas do *sip* e do *sei*, conforme segue:

*SEI*
```bash
$ cp /sei/web/modulos/cgu/mod-sei-eouv/scripts/sei/md_cgu_eouv_atualizar_modulo_sei.php /sei/scripts/
```

*SIP*
```bash
$ cp /sei/web/modulos/cgu/mod-sei-eouv/scripts/sip/md_cgu_eouv_atualizar_modulo_sip.php /sip/scripts/
```

> Repare que são **DOIS** scripts, um para o SEI e outro para o SIP, é necessário copiar os dois para suas respectivas pastas

5. Caso esteja instalando pela primeira vez o módulo adicionar o móduloo **'MdCguEouvIntegracao' => 'cgu/mod-sei-eouv'** no *array* 'Modulos' no arquivo */sei/config/ConfiguracaoSEI.php* conforme abaixo:

```text
'SEI' => array(
	'URL' => 'http://[Servidor_PHP]/sei',
	'Producao' => false,
	'RepositorioArquivos' => '/var/sei/arquivos',
	'Modulos' => array(
		[...],
		'MdCguEouvIntegracao' => 'cgu/mod-sei-eouv',
	)
),

```

> Utilize sempre editores de texto que não altere o *charset* do arquivo

6. **[IMPORTANTE]** Atualizar as informações do tipo de procedimento, inseridas no SEI conforme item 2, no arquivo `./sei/web/modulos/cgu/mod-se-eouv/rn/MdCguEouvAtualizadorBDRN.php`, conforme segue:

	6.1 Dentro do método **instalarv205** atualizar onde está 'XXXXXXXX' com o *ID* correspondente para o 'tipo de procedimento' referente aos tipos de 1 à 7, conforme *ID* abaixo:

	```bash
	$this->logar('CRIANDO REGISTROS PARA A TABELA md_eouv_depara_importacao');
		BancoSEI::getInstance()->executarSql('INSERT INTO md_eouv_depara_importacao (id_tipo_manifestacao_eouv, de_tipo_manifestacao_eouv, id_tipo_procedimento) VALUES (\'1\', \'Denúncia\', \'XXXXXXXX\');');
		BancoSEI::getInstance()->executarSql('INSERT INTO md_eouv_depara_importacao (id_tipo_manifestacao_eouv, de_tipo_manifestacao_eouv, id_tipo_procedimento) VALUES (\'2\', \'Reclamação\', \'XXXXXXXX\');');
		BancoSEI::getInstance()->executarSql('INSERT INTO md_eouv_depara_importacao (id_tipo_manifestacao_eouv, de_tipo_manifestacao_eouv, id_tipo_procedimento) VALUES (\'3\', \'Elogio\', \'XXXXXXXX\');');
		BancoSEI::getInstance()->executarSql('INSERT INTO md_eouv_depara_importacao (id_tipo_manifestacao_eouv, de_tipo_manifestacao_eouv, id_tipo_procedimento) VALUES (\'4\', \'Sugestão\', \'XXXXXXXX\');');
		BancoSEI::getInstance()->executarSql('INSERT INTO md_eouv_depara_importacao (id_tipo_manifestacao_eouv, de_tipo_manifestacao_eouv, id_tipo_procedimento) VALUES (\'5\', \'Solicitação\', \'XXXXXXXX\');');
		BancoSEI::getInstance()->executarSql('INSERT INTO md_eouv_depara_importacao (id_tipo_manifestacao_eouv, de_tipo_manifestacao_eouv, id_tipo_procedimento) VALUES (\'6\', \'Simplifique\', \'XXXXXXXX\');');
		BancoSEI::getInstance()->executarSql('INSERT INTO md_eouv_depara_importacao (id_tipo_manifestacao_eouv, de_tipo_manifestacao_eouv, id_tipo_procedimento) VALUES (\'7\', \'Comunicado\', \'XXXXXXXX\');');
	```

	> Caso esteja atualizando a versão, o item 6.1 já deverá constar os *ids* corretos, portanto siga para o item 6.2

	6.2 Dentro do método **instalarv400** atualizar onde está 'XXXXXXXX' com o *ID* correspondente para o 'tipo de procedimento' referente ao tipo 8, acesso à informação, conforme abaixo:

	```bash
	/**
	 * Criar um "depara_importação" para a Manifestação e-Sic
	 */
	$this->logar('CRIANDO REGISTROS PARA A TABELA md_eouv_depara_importacao');
	BancoSEI::getInstance()->executarSql('INSERT INTO md_eouv_depara_importacao (id_tipo_manifestacao_eouv, id_tipo_procedimento, de_tipo_manifestacao_eouv) VALUES (8, XXXXXXXX, \'Acesso à Informação\');');
	```

7. Execute o *script* '*/sip/scripts/md_cgu_eouv_atualizar_modulo_sip.php*' em linha de comando no servidor SIP, verificando se não houve erro durante a execução. Ao final deve aparecer a mensagem "FIM".

Para executar o *script* execute o seguinte comando:

```bash
$ /usr/bin/php -c /etc/php.ini /sip/scripts/md_cgu_eouv_atualizar_modulo_sip.php > md_cgu_eouv_atualizar_modulo_sip_400.log
```

8. Execute o *script* '*/sei/scripts/md_cgu_eouv_atualizar_modulo_sei.php*' em linha de comando no servidor SEI, verificando se não houve erro durante a execução. Ao final deve aparecer a mensagem "FIM".

Para executar o *script* execute o seguinte comando:

```bash
$ /usr/bin/php -c /etc/php.ini /sei/scripts/md_cgu_eouv_atualizar_modulo_sei.php > md_cgu_eouv_atualizar_modulo_sei_400.log
```

> **[IMPORTANTE]** Ao final da execução dos dois *scripts* acima deve constar o termo "FIM" e informação de que a instalação ocorreu com sucesso (SEM ERROS). Do contrário, o script não foi executado até o final e algum dado não foi inserido/atualizado nos bancos de dados correspondentes. Neste caso, deve-se restaurar o backup do banco pertinente e repetir o procedimento.

> Constando o termo "FIM" e informação de que a instalação ocorreu com sucesso, pode logar no SEI e SIP e verificar no menu *Infra > Módulos* se consta o módulo "Módulo de Integração entre o sistema SEI e o E-ouv(Sistema de Ouvidorias)" com o valor da última versão do módulo.

### Configurações

9. Parametrizar o módulo, usando o usuário com perfil "Administrador" do SEI, conforme descrito abaixo:

	9.1 Acessar o menu *E-Ouv > Parâmetros do Módulo E-ouv* ajustando os seguintes parâmetros:

	- **EOUV_DATA_INICIAL_IMPORTACAO_MANIFESTACOES** - Inserir neste campo a Data Inicial, no formato (DD/MM/AAAA), para carregar as manifestações do FalaBR (e-Ouv) dos tipos 1 à 7, conforme *Tabela 1 - Tipo de Manifestação*. Sugerimos que seja colocada a **data atual** para que apenas as novas manifestações sejam importadas para o SEI.

	- **EOUV_ID_SERIE_DOCUMENTO_EXTERNO_DADOS_MANIFESTACAO** - Quando a rotina de importação for executada será gerado um documento PDF com os dados da manifestação que será anexado ao processo com o mesmo número de identificação do FalaBR. Este parâmetro será usado para indicar qual o Tipo de Documento no SEI será utilizado para este PDF. Lembrando que deve ser do Grupo de **Documentos Externos**. Para verificar os tipos existentes acesse *Administração > Tipos de Documento > Listar*.

	- **ID_SERIE_EXTERNO_OUVIDORIA** - Este parâmetro não está sendo utilizado, poderá ser ignorado.

	- **EOUV_USUARIO_ACESSO_WEBSERVICE** - Nome de usuário para acesso aos WebServices do FalaBR, gerado especificamente para cada órgão. Caso ainda não possua este usuário e a senha abaixo, solicitar via e-mail para [Marcos Silva - marcos.silva@cgu.gov.br](mailto:marcos.silva@cgu.gov.br?subject=[SOLICITAÇÃO]%20Usuário%20e%20Senha%20API%20FalaBR)

	- **EOUV_SENHA_ACESSO_WEBSERVICE** - Senha do usuário para acesso aos WebServices do FalaBR

	- **CLIENT_ID** - Id gerado para acesso aos WebServices.

	- **CLIENT_SECRET** - Senha gerada para acesso aos WebServices.

	- **TOKEN** - Token gerado para acesso aos WebServices.

	- **EOUV_URL_WEBSERVICE_IMPORTACAO_MANIFESTACAO** - Já vem configurado para o ambiente de produção do FalaBR com https://sistema.ouvidorias.gov.br/api/manifestacoes

	> Para efeitos de testes e homologação utilizar o ambiente de treinamento: https://treinamentoouvidorias.cgu.gov.br/api/manifestacoes

	- **ID_UNIDADE_OUVIDORIA** - Código da Unidade no SEI que deverá registrar os novos processos 'e-Ouv' importados do FalaBR

	> Caso esteja atualizando a versão, o item 6.1 já deverá constar os *ids* corretos, portanto siga para o item 9.2

	9.2 Acessar o menu *E-Ouv > Parâmetros do Módulo e-Sic* ajustando os seguintes parâmetros:

	- **ESIC_DATA_INICIAL_IMPORTACAO_MANIFESTACOES** - Inserir neste campo a Data Inicial, no formato (DD/MM/AAAA), para carregar as manifestações do FalaBR (e-Sic) dos tipos 8, conforme *Tabela 1 - Tipo de Manifestação*. Sugerimos que seja colocada a **data atual** para que apenas as novas manifestações sejam importadas para o SEI.

	- **ESIC_URL_WEBSERVICE_IMPORTACAO_RECURSOS** - Já vem configurado para o ambiente de produção do FalaBR com 'https://sistema.ouvidorias.gov.br/api/recursos?NumProtocolo='

	> Para efeitos de testes e homologação utilizar o ambiente de treinamento: https://treinamentoouvidorias.cgu.gov.br/api/recursos?NumProtocolo=

	- **ESIC_ID_UNIDADE_PRINCIPAL** - Código da Unidade no SEI que deverá registrar os novos processos 'e-Sic' importados do FalaBR

	- **ESIC_ID_UNIDADE_RECURSO_PRIMEIRA_INSTANCIA** - Código da Unidade no SEI que deverá registrar os recursos de **primeira** instância

	- **ESIC_ID_UNIDADE_RECURSO_SEGUNDA_INSTANCIA** - Código da Unidade no SEI que deverá registrar os recursos de **segunda** instância

10. Criar agendamento para as funções desejadas

Este móduo possui duas funções para importação das manifestações 'e-Ouv' (tipo 1 a 7) e 'e-Sic' (tipo 8). Segue abaixo as respectivas funções para agendamento:

10.1 Para importar do FalaBR as manifestações 'e-Ouv' faça o agendamento da função **"MdCguEouvAgendamentoRN::executarImportacaoManifestacaoEOuv"**

10.2 Para importar do FalaBR as manifestações 'e-Sic' faça o agendamento da função **"MdCguEouvAgendamentoRN::executarImportacaoManifestacaoESic"**

> Sugerimos fazer o agendamento para ser executado uma vez por dia

> Os agendamentos podem ser acessados em Infra > Agendamentos

## Orientações Gerais

### Tutorial 

Criamos um vídeo com a demonstração do funcionamento do módulo focado na parte negocial:

[![Tutorial módulo integração SEI & FalaBR](https://img.youtube.com/vi/geUCx7H79Gw/0.jpg)](https://www.youtube.com/watch?v=geUCx7H79Gw)

> Em caso dúvidas favor enviar um email para [SESOL - sesol@cgu.gov.br](mailto:sesol@cgu.gov.br?subject=[DUVIDA]%20SEI%20-%20módulo%20FalaBR)
