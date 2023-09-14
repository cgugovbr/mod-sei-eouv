
# Módulo de Integração SUPER & FalaBR (e-Ouv e e-Sic)

## Requisitos

- SUPER ou SEI versão 4.0.3 ou superior (verificar versão no arquivo /sei/web/SEI.php).

- Utilizar o Sistema FalaBR do Governo Federal (e-Ouv e e-Sic). Caso ainda não tenha aderido ao FalaBR e queira saber mais informações acesse https://falabr.cgu.gov.br/.

- [IMPORTANTE] Ao executar os scripts de instalação/atualização (seção [Executar scripts](#executar-scripts) abaixo), você precisará informar um usuário e senha para se conectar ao banco de dados do SEI e do SIP. Tal usuário precisa ter permissão de acesso total, sendo capaz de criar e excluir tabelas.

## Instalação/atualização e configuração

### Procedimentos antes da instalação

Fazer backup completo dos bancos de dados do SEI e do SIP.

### Download do módulo

Baixar o arquivo zip da versão desejada na página: https://github.com/cgugovbr/mod-sei-eouv/releases
 
A estrutura de pastas deste módulo é a seguinte:

```bash
./mod-sei-eouv
 --/sei
 --/sip
 --/README.md
```
  
> Os arquivos contidos dentro dos diretórios sei e sip não substituem nenhum código-fonte original do sistema. Eles apenas posicionam os arquivos do módulo nas pastas corretas de *scripts* e *modulos*.
  
### Copiar arquivos

Copie o arquivo zip para o servidor do SEI e descompacte-o na raiz de instalação do sistema usando o comando abaixo:
  
```bash
$ cd <DIRETORIDIO_RAZ_DE_INSTALAÇÃO_DO_SEI>
$ unzip mod-sei-eouv-VERSAO.zip
```

> Lembre-se de substituir o termo **'VERSAO'** pelo nome do arquivo zip com a versão que está sendo instalada.

Os arquivos serão colocados nas pastas */sip/scripts*, */sei/scripts* e */sei/web/modulos/cgu/mod-sei-eouv*
 
### Instalação/atualização

#### Habilitar módulo

Caso esteja instalando pela primeira vez o módulo, adicionar a linha **'MdCguEouvIntegracao' => 'cgu/mod-sei-eouv'** no *array* 'Modulos' do arquivo */sei/config/ConfiguracaoSEI.php* conforme abaixo:

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

> Utilize sempre editores de texto que não alterem o *charset* do arquivo

#### Executar scripts

Execute os *scripts* '*/sip/scripts/md_cgu_eouv_atualizar_modulo.php*' e '*/sei/scripts/md_cgu_eouv_atualizar_modulo.php*' em linha de comando no servidor do sistema, verificando se não houve erro durante a execução. Ao final deve aparecer a mensagem "FIM".

Para executar os *scripts* digite os comandos a seguir:

```bash
/usr/bin/php -c /etc/php.ini sip/scripts/md_cgu_eouv_atualizar_modulo.php > md_cgu_eouv_atualizar_modulo_sip.log
/usr/bin/php -c /etc/php.ini sei/scripts/md_cgu_eouv_atualizar_modulo.php > md_cgu_eouv_atualizar_modulo_sei.log
```

> **[IMPORTANTE]** Ao final da execução de cada um dos *scripts* acima deve constar o termo "FIM" e informação de que a instalação ocorreu com sucesso (SEM ERROS). Do contrário, o script não foi executado até o final e algum dado não foi inserido/atualizado nos bancos de dados correspondentes. Neste caso, deve-se restaurar o backup do banco pertinente e repetir o procedimento.

> Constando o termo "FIM" e a informação de que a instalação ocorreu com sucesso, pode logar no SEI e verificar no menu *Infra > Módulos* se consta o módulo "Módulo de Integração entre o sistema SEI e o FalaBR (Sistema de Ouvidorias - e-Ouv|e-Sic)" com o valor da última versão do módulo.

### Configurações

Parametrizar o módulo, usando o usuário com perfil "Administrador" do SEI, conforme descrito a seguir.

#### Tipos de Manifestação

Acessar o menu *E-Ouv > Tipos de Manifestação* e associar cada tipo de manifestação do FalaBR com um tipo de processo existente no SEI. Você poderá criar um novo tipo de processo para cada tipo de manifestação do FalaBR se for o caso.

Abaixo estão os tipos de manifestações do FalaBR que serão importadas para o SEI:

| ID FalaBR                | Tipo de Manifestação |
| :-:                      | :-:                  |
|1                         |Denúncia              |
|2                         |Reclamação            |
|3                         |Elogio                |
|4                         |Sugestão              |
|5                         |Solicitação           |
|6                         |Não Classificada      |
|7                         |Comunicado            |
|8                         |Acesso à Informação   |

> Obs: manifestações do tipo "Simplifique" não são suportadas.

#### Parâmetros E-Ouv

Acessar o menu *E-Ouv > Parâmetros do Módulo E-ouv* ajustando os seguintes parâmetros:

- **EOUV_DATA_INICIAL_IMPORTACAO_MANIFESTACOES** - Inserir neste campo a Data Inicial, no formato (DD/MM/AAAA), para carregar as manifestações do FalaBR (e-Ouv) dos tipos 1 à 7. Sugerimos que seja colocada a **data atual** para que apenas as novas manifestações sejam importadas para o SEI.

- **EOUV_ID_SERIE_DOCUMENTO_EXTERNO_DADOS_MANIFESTACAO** - Quando a rotina de importação for executada, será gerado um documento PDF com os dados da manifestação que será anexado ao processo com o mesmo número de identificação do FalaBR. Este parâmetro será usado para indicar qual o Tipo de Documento no SEI será utilizado para este PDF. Lembrando que deve ser do Grupo de **Documentos Externos**. Para verificar os tipos existentes acesse *Administração > Tipos de Documento > Listar*.

- **EOUV_USUARIO_ACESSO_WEBSERVICE** - Nome de usuário para acesso aos WebServices do FalaBR, gerado especificamente para cada órgão. Caso ainda não possua este usuário e a senha abaixo, solicitar via formulário para o [Suporte Técnico do Fala.BR](https://formularios.cgu.gov.br/index.php/679625?lang=pt-BR)

- **EOUV_SENHA_ACESSO_WEBSERVICE** - Senha do usuário para acesso aos WebServices do FalaBR

- **CLIENT_ID** - Id gerado para acesso aos WebServices.

- **CLIENT_SECRET** - Senha gerada para acesso aos WebServices.

- **TOKEN** - Token gerado para acesso aos WebServices. Se não for preenchido, o módulo pede ao sistema FalaBR um novo token automaticamente.

- **EOUV_URL_WEBSERVICE_IMPORTACAO_MANIFESTACAO** - Já vem configurado para o ambiente de produção do FalaBR com https://falabr.cgu.gov.br/api/manifestacoes

> Para efeitos de testes e homologação utilizar o ambiente de treinamento: https://treinafalabr.cgu.gov.br/api/manifestacoes

- **ID_UNIDADE_OUVIDORIA** - Código da Unidade no SEI que deverá registrar os novos processos 'e-Ouv' importados do FalaBR

> Caso esteja atualizando a versão, já deverá constar os *ids* corretos, portanto siga para o próximo item

#### Parâmetros e-Sic

Acessar o menu *E-Ouv > Parâmetros do Módulo e-Sic* ajustando os seguintes parâmetros:

- **ESIC_DATA_INICIAL_IMPORTACAO_MANIFESTACOES** - Inserir neste campo a Data Inicial, no formato (DD/MM/AAAA), para carregar as manifestações do FalaBR (e-Sic) do tipo 8. Sugerimos que seja colocada a **data atual** para que apenas as novas manifestações sejam importadas para o SEI.

- **ESIC_URL_WEBSERVICE_IMPORTACAO_RECURSOS** - Já vem configurado para o ambiente de produção do FalaBR com 'https://falabr.cgu.gov.br/api/recursos'

> Para efeitos de testes e homologação utilizar o ambiente de treinamento: https://treinafalabr.cgu.gov.br/api/recursos

- **ESIC_ID_UNIDADE_PRINCIPAL** - Código da Unidade no SEI que deverá registrar os novos processos 'e-Sic' importados do FalaBR

- **ESIC_ID_UNIDADE_RECURSO_PRIMEIRA_INSTANCIA** - Código da Unidade no SEI que deverá registrar os recursos de **primeira** instância

- **ESIC_ID_UNIDADE_RECURSO_SEGUNDA_INSTANCIA** - Código da Unidade no SEI que deverá registrar os recursos de **segunda** instância

- **ESIC_ID_UNIDADE_RECURSO_TERCEIRA_INSTANCIA** - Código da Unidade no SEI que deverá registrar os recursos de **terceira** instância

- **ESIC_ID_UNIDADE_RECURSO_PEDIDO_REVISAO** - Código da Unidade no SEI que deverá registrar os pedidos de **revisão**

#### Agendamentos

Este módulo possui duas funções para importação das manifestações 'e-Ouv' (tipo 1 a 7) e 'e-Sic' (tipo 8), indicadas abaixo:

- **MdCguEouvAgendamentoRN::executarImportacaoManifestacaoEOuv**
- **MdCguEouvAgendamentoRN::executarImportacaoManifestacaoESic**

Os agendamentos são criados automaticamente pelos scripts de instalação. Ajuste a periodicidade de execução das importações no menu Infra > Agendamentos.
> Sugerimos que os agendamentos sejam executados uma vez por dia

## Orientações Gerais

### Tutorial

Criamos um vídeo com a demonstração do funcionamento do módulo focado na parte negocial:

[![Tutorial módulo integração SEI & FalaBR](https://img.youtube.com/vi/geUCx7H79Gw/0.jpg)](https://www.youtube.com/watch?v=geUCx7H79Gw)

> Em caso dúvidas favor enviar um email para [DISOL - disol@cgu.gov.br](mailto:disol@cgu.gov.br?subject=[DUVIDA]%20SEI%20-%20módulo%20FalaBR)
