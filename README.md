# M�dulo de Integra��o SEI x e-Ouv

## Requisitos:
- SEI 3.0.0 instalado/atualizado ou vers�o superior (verificar valor da constante de vers�o do SEI no arquivo /sei/web/SEI.php).

- Utilizar o Sistema de Ouvidorias do Governo Federal e-Ouv(sistema.ouvidorias.gov.br). Caso ainda n�o tenha aderido ao e-Ouv e queira saber mais informa��es acesse www.ouvidorias.gov.br.
		
- Antes de executar os scripts de instala��o/atualiza��o (itens 4 e 5 abaixo), o usu�rio de acesso aos bancos de dados do SEI e do SIP, constante nos arquivos ConfiguracaoSEI.php e ConfiguracaoSip.php, dever� ter permiss�o de acesso total ao banco de dados, permitindo, por exemplo, cria��o e exclus�o de tabelas.

- Instalar na pasta infra/infra_php a biblioteca nusoap. Como o sistema e-Ouv utiliza versionamento de WebServices a biblioteca padr�o do SEI para consumir webservices n�o consegue resolver essa quest�o. A mesma pode ser baixada em: https://sourceforge.net/projects/nusoap/files/?source=navbar
	- Ap�s a instala��o � necess�rio fazer uma corre��o na biblioteca conforme abaixo:
	
	

> 
	alterar o arquivo nusoap.php na linha 4694
		de:$this->schemas[$ns]->imports[$ns2][$ii]['loaded'] = true; 
		para:$this->schemas[$ns][$ns2]->imports[$ns2][$ii]['loaded'] = true; 


## Procedimentos para Instala��o:

1. Antes, fazer backup dos bancos de dados do SEI e do SIP.

2. Carregar no servidor os arquivos do m�dulo localizados na pasta "/sei/web/modulos/cgu/eouv" e os scripts de instala��o/atualiza��o "/sei/scripts/md_cgu_eouv_atualizar_modulo.php" e "/sip/scripts/md_cgu_eouv_atualizar_modulo.php".

3. Editar o arquivo "/sei/config/ConfiguracaoSEI.php", tomando o cuidado de usar editor que n�o altere o charset do arquivo, para adicionar a refer�ncia � classe de integra��o do m�dulo e seu caminho relativo dentro da pasta "/sei/web/modulos" na array 'Modulos' da chave 'SEI':

		'SEI' => array(
			'URL' => 'http://[Servidor_PHP]/sei',
			'Producao' => false,
			'RepositorioArquivos' => '/var/sei/arquivos',
			'Modulos' => array('MdCguEouvIntegracao' => 'cgu/mod-sei-eouv',)
			),

4. Rodar o script de banco "/sei/scripts/md_cgu_eouv_atualizar_modulo.php" em linha de comando no servidor do SEI, verificando se n�o houve erro em sua execu��o, em que ao final do log dever� ser informado "FIM". Exemplo de comando de execu��o:

		/usr/bin/php -c /etc/php.ini /opt/sei/scripts/md_cgu_eouv_atualizar_modulo.php > md_cgu_eouv_atualizar_modulo_1.log

5. Rodar o script de banco "/sip/scripts/md_cgu_eouv_atualizar_modulo.php" em linha de comando no servidor do SIP, verificando se n�o houve erro em sua execu��o, em que ao final do log dever� ser informado "FIM". Exemplo de comando de execu��o:

		/usr/bin/php -c /etc/php.ini /opt/sip/scripts/md_cgu_eouv_atualizar_modulo.php > md_cgu_eouv_atualizar_modulo-1.log

6. Ap�s a execu��o com sucesso, com um usu�rio com permiss�o de Administrador no SEI, seguir os passos dispostos no t�pico Orienta��es Negociais, abaixo.

7. **IMPORTANTE**: Na execu��o dos dois scripts acima, ao final deve constar o termo "FIM" e informa��o de que a instala��o ocorreu com sucesso (SEM ERROS). Do contr�rio, o script n�o foi executado at� o final e algum dado n�o foi inserido/atualizado no banco de dados correspondente, devendo recuperar o backup do banco pertinente e repetir o procedimento.
		- Constando o termo "FIM" e informa��o de que a instala��o ocorreu com sucesso, pode logar no SEI e SIP e verificar no menu Infra > M�dulos se consta o m�dulo "M�dulo de Integra��o entre o sistema SEI e o E-ouv(Sistema de Ouvidorias)" com o valor da �ltima vers�o do m�dulo.

8. Em caso de erro durante a execu��o do script verificar (lendo as mensagens de erro e no menu Infra > Log do SEI e do SIP) se a causa � algum problema na infra-estrutura local. Neste caso, ap�s a corre��o, deve recuperar o backup do banco pertinente e repetir o procedimento, especialmente a execu��o dos scripts indicados nos itens 4 e 5 acima.
	- Caso n�o seja poss�vel identificar a causa, entrar em contato com: Rafael Leandro - rafael.ferreira@cgu.gov.br

## Orienta��es Negociais:

1. Imediatamente ap�s a instala��o com sucesso, com usu�rio com permiss�o de "Administrador" do SEI, � necess�rio realizar as parametriza��es do m�dulo no menu Infra > Par�metros alterando os seguintes Par�metros:

- EOUV_DATA_INICIAL_IMPORTACAO_MANIFESTACOES: Colocar a Data Inicial no formato (DD/MM/AAAA) para carregar as manifesta��es do e-Ouv. Sugerimos que seja colocada a data atual para que apenas as novas manifesta��es sejam importadas para o SEI.

- EOUV_ID_SERIE_DOCUMENTO_EXTERNO_DADOS_MANIFESTACAO: Quando a rotina for executada ela criar� um documento PDF com os dados da Manifesta��o do EOUV que ser� anexada ao processo. Esse par�metro ser� usado para dizer qual o Tipo de Documento ser� usado para criar esse documento. Lembrando que deve ser do Grupo de Documentos Externos. Para verificar os tipos existentes acesse Administra��o > Tipos de Documento > Listar.

- EOUV_USUARIO_ACESSO_WEBSERVICE: Nome de usu�rio para acesso aos WebServices do e-Ouv.

- EOUV_SENHA_ACESSO_WEBSERVICE: Senha do usu�rio para acesso aos WebServices do e-Ouv.

- EOUV_URL_WEBSERVICE_IMPORTACAO_MANIFESTACAO: J� vem configurado para o ambiente de produ��o do e-Ouv com https://sistema.ouvidorias.gov.br/Servicos/ServicoConsultaManifestacao.svc

- EOUV_URL_WEBSERVICE_IMPORTACAO_ANEXO_MANIFESTACAO: J� vem configurado para o ambiente de produ��o do e-Ouv com https://sistema.ouvidorias.gov.br/Servicos/ServicoAnexosManifestacao.svc

2. Foi criado um novo Agendamento de Tarefa com o nome "MdCguEouvAgendamentoRN :: executarImportacaoManifestacaoEOuv". O mesmo � configurado por padr�o para ser executado apenas uma vez por dia e dever� ser configurado conforme desejado pelo �rg�o. Os agendamentos podem ser acessados em Infra > Agendamentos.

3. Foi criado um menu com o nome E-Ouv que possui um relat�rio das execu��es de Importa��o executadas. A cada execu��o do agendamento � gerado um registro que cont�m os detalhes da execu��o informando se houve sucesso e os Protocolos que foram importados.

4. Foi criada uma tabela com o nome md_cgu_eouv_depara_importacao que serve para dizer para a rotina qual o Tipo de Processo ser� cadastrado para cada tipo de Manifesta��o do e-Ouv. Seguindo a tabela abaixo informe qual o c�digo do tipo de processo(Administra��o > Tipos de Processo) para cada equivalente. 



