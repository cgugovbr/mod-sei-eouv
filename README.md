
# Módulo de Integração SEI & FalaBR (e-Ouv e e-Sic)

## Requisitos

- SEI versão 3.0.0 ou superior (verificar versão do SEI no arquivo /sei/web/SEI.php).

- Utilizar o Sistema FalaBR do Governo Federal (e-Ouv e e-Sic). Caso ainda não tenha aderido ao FalaBR e queira saber mais informações acesse https://falabr.cgu.gov.br/.

- [IMPORTANTE] Para executar os scripts de instalação/atualização (itens XX e XX abaixo), o usuário configurado nos arquivos **ConfiguracaoSEI.php** e **ConfiguracaoSip.php**, deverá ter permissão de acesso total ao banco de dados do SEI e do SIP, permitindo criação e exclusão de tabelas.

## Instalação/atualização e configuração

### Procedimentos antes da instalação

1. Fazer backup completo dos bancos de dados do SEI e do SIP.

2. Inserir os Tipos de Procedimento para cada tipo de Manifestação no SEI **[IMPORTANTE]**

Acesse no SEI o menu *Administração > Tipos de Processos > Listar* para verificar os tipos já existentes. Você poderá criar um novo tipo de documento para cada tipo de manifestação do FalaBR se for o caso.

Anote os IDs de cada *Tipo de Processo* que será vinculado os processos importados do FalaBR. Estes código deverão ser atualizados no arquivo `./sei/web/modulos/cgu/mod-se-eouv/rn/MdCguEouvAtualizadorBDRN.php` conforme descrito no item XX 

> Este ítem é pré-requisito para a execução do script no item XX

Abaixo os tipos de manifestação do FalaBR que poderão ser importadas para o SEI:

|id_tipo_manifestacao_eouv |id_tipo_procecimento    |de_tipo_manifestacao_eouv |
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

3. Caso esteja instalando pela primeira vez o módulo adicionar o móduloo **'MdCguEouvIntegracao' => 'cgu/mod-sei-eouv'** no *array* 'Modulos' no arquivo */sei/config/ConfiguracaoSEI.php* conforme abaixo:

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


4. Execute o *script* '*/sei/web/modulos/cgu/mod-sei-eouv/scripts/md_cgu_eouv_atualizar_modulo_sip.php*' em linha de comando no servidor SIP, verificando se não houve erro durante a execução. Ao final deve aparecer a mensagem "FIM".

Para executar o *script* execute o seguinte comando:

```bash
$ /usr/bin/php -c /etc/php.ini /sei/web/modulos/cgu/mod-sei-eouv/scripts/md_cgu_eouv_atualizar_modulo_sip.php > md_cgu_eouv_atualizar_modulo_sip_400.log
```

5. Execute o *script* '*/sei/web/modulos/cgu/mod-sei-eouv/scripts/md_cgu_eouv_atualizar_modulo_sei.php*' em linha de comando no servidor SEI, verificando se não houve erro durante a execução. Ao final deve aparecer a mensagem "FIM".

Para executar o *script* execute o seguinte comando:

```bash
$ /usr/bin/php -c /etc/php.ini /sei/web/modulos/cgu/mod-sei-eouv/scripts/md_cgu_eouv_atualizar_modulo_sei.php > md_cgu_eouv_atualizar_modulo_sei_400.log
```

> **[IMPORTANTE]** Ao final da execução dos dois *scripts* acima deve constar o termo "FIM" e informação de que a instalação ocorreu com sucesso (SEM ERROS). Do contrário, o script não foi executado até o final e algum dado não foi inserido/atualizado nos bancos de dados correspondentes. Neste caso, deve-se restaurar o backup do banco pertinente e repetir o procedimento.

> Constando o termo "FIM" e informação de que a instalação ocorreu com sucesso, pode logar no SEI e SIP e verificar no menu *Infra > Módulos* se consta o módulo "Módulo de Integração entre o sistema SEI e o E-ouv(Sistema de Ouvidorias)" com o valor da última versão do módulo.

**@todo - verificar nome do módulo**

### Configurações

6. Após do módulo o usuário com "Administrador" do SEI deverá parametrizar o módulo, conforme descrito abaixo:


6.1 Acessar o menu *E-Ouv > Parâmetros do Módulo E-ouv* ajustando os seguintes parãmetros:

- **EOUV_DATA_INICIAL_IMPORTACAO_MANIFESTACOES** - Colocar a Data Inicial no formato (DD/MM/AAAA) para carregar as manifestações do FalaBR (antigo e-Ouv) dos tipos 1 à 7, conforme *Tabela 1 - Tipo de Manifestação*. Sugerimos que seja colocada a data atual para que apenas as novas manifestações sejam importadas para o SEI.

- **EOUV_ID_SERIE_DOCUMENTO_EXTERNO_DADOS_MANIFESTACAO** - Quando a rotina de importação for executada será gerado documento PDF com os dados da Manifestação do FalaBR que será anexado ao processo com o mesmo número de identificação do FalaBR. Este parâmetro será usado para indicar qual o Tipo de Documento no SEI será utilizado para este PDF. Lembrando que deve ser do Grupo de **Documentos Externos**. Para verificar os tipos existentes acesse *Administração > Tipos de Documento > Listar*.

- **ID_SERIE_EXTERNO_OUVIDORIA** - Quando a rotina de importação for executada o documento da Manifestação no FalaBR usará esse código para inserir no campo Tipo Docuemnto. Para verificar os tipos existentes acesse *Administração > Tipos de Documento > Listar*.

**@todo - verificar os dois campos acima no código** 

O conjunto de informações em destaque abaixo será fornecido pela CGU:

- EOUV_USUARIO_ACESSO_WEBSERVICE: Nome de usuário para acesso aos WebServices do e-Ouv. Este nome de usuário é gerado para cada órgão
 especificamente para consumir os Webservices do e-Ouv. Caso ainda não
> possua esse usuário e a senha abaixo entrar em contato através do
> e-mail abaixo solicitando o mesmo: marcos.silva@cgu.gov.br
>
> - EOUV_SENHA_ACESSO_WEBSERVICE: Senha do usuário para acesso aos WebServices do e-Ouv.
>
> - CLIENT_ID: Id gerado para acesso aos WebServices.
>
> - CLIENT_SECRET: Senha gerada para acesso aos WebServices.
>
> - TOKEN: Token gerado para acesso aos WebServices.

- EOUV_URL_WEBSERVICE_IMPORTACAO_MANIFESTACAO: Já vem configurado para o ambiente de produção do e-Ouv com https://sistema.ouvidorias.gov.br/api/manifestacoes
Obs: Para efeitos de testes e homologação utilizar o ambiente de treinamento: https://treinamentoouvidorias.cgu.gov.br/api/manifestacoes

- ID_UNIDADE_OUVIDORIA: Código da Unidade que deverá registrar os novos processos. Ao importar os processos do e-Ouv para o SEI essa será a unidade que receberá os Processos no SEI.

2. Foi criado um novo Agendamento de Tarefa com o nome "MdCguEouvAgendamentoRN :: executarImportacaoManifestacaoEOuv". O mesmo é configurado por padrão para ser executado apenas uma vez por dia e deverá ser configurado conforme desejado pelo órgão. Os agendamentos podem ser acessados em Infra > Agendamentos.

3. Foi criado um menu com o nome E-Ouv que possui um relatório das execuções de Importação executadas. A cada execução do agendamento é gerado um registro que contém os detalhes da execução informando se houve sucesso e os Protocolos que foram importados.

4. Foi criada uma tabela com o nome md_cgu_eouv_depara_importacao que serve para dizer para a rotina qual o Tipo de Processo será cadastrado para cada tipo de Manifestação do e-Ouv. Seguindo a tabela abaixo informe qual o código do tipo de processo(Administração > Tipos de Processo) para cada equivalente.

|id_tipo_manifestacao_eouv |id_tipo_procecimento    |de_tipo_manifestacao_eouv |
|--------------------------|------------------------|--------------------------|
|1                         |`xxx`                   |Denúncia                  |
|2                         |`xxx`                   |Reclamação                |
|3                         |`xxx`                   |Elogio                    |
|4                         |`xxx`                   |Sugestão                  |
|5                         |`xxx`                   |Solicitação               |
|6                         |`xxx`                   |Simplifique               |
|7                         |`xxx`                   |Comunicado                |



- Caso não seja possível identificar a causa, entrar em contato com: Rafael Leandro - rafael.ferreira@cgu.gov.br

## Orientações Negociais:

Criamos um vídeo com a demonstração do funcionamento do módulo focado na parte negocial, caso queira entender um pouco mais sobre o módulo acesse:

https://www.youtube.com/watch?v=geUCx7H79Gw

