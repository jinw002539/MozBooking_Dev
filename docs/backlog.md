# 📋 Product Backlog – Sistema Vida (Gestão de Consultas)

| ID | User Story | Critério de Aceitação | Prioridade | Estimativa |
| :--- | :--- | :--- | :--- | :--- |
| **US01** | Como paciente, quero marcar uma consulta sem fornecer dados pessoais para garantir a minha privacidade. | O formulário deve pedir apenas: Tipo de cliente (Novo/Antigo), Data e Urgência. | **Must** | 8h |
| **US02** | Como paciente, quero receber um Ticket automático após a marcação para usar como identificação na clínica. | Após o POST, deve exibir um modal com o código aleatório (ex: #V-2026) e instruções de uso. | **Must** | 4h |
| **US03** | Como sistema, quero limitar a escolha de datas a 15 dias úteis para evitar marcações a longo prazo. | O campo de data deve bloquear dias passados, fins de semana e datas além de 15 dias da data atual. | **Must** | 6h |
| **US04** | Como recepcionista, quero visualizar os tickets pendentes para organizar o fluxo de chegada. | Uma tabela em `recepcionista.php` que mostra todos os tickets do dia que ainda não têm médico atribuído. | **Must** | 8h |
| **US05** | Como recepcionista, quero atribuir um médico e o número de processo físico a cada ticket. | O sistema deve permitir editar a linha do ticket para inserir o ID do processo (ex: P-100) e escolher o médico. | **Must** | 6h |
| **US06** | Como médico, quero ver apenas os meus pacientes designados para o dia para iniciar o atendimento. | O painel do médico deve filtrar a lista de tickets pelo nome do médico logado e pela data atual. | **Must** | 8h |
| **US07** | Como médico/diretor, quero concluir uma consulta para que o ticket saia da fila de espera. | Botão "Concluir" que altera o estado do ticket para "Concluído" e regista o timestamp final. | **Must** | 4h |
| **US08** | Como recepcionista, quero ativar um aviso de cancelamento global visível no index para novos pacientes. | Um campo de texto no painel administrativo que publica um alerta vermelho no topo da landing page. | **Should** | 6h |
| **US09** | Como diretor clínico, quero ver gráficos de volume de atendimento para análise de produtividade. | Uso de Chart.js para mostrar consultas Urgentes vs Normais e volume mensal. | **Should** | 10h |
| **US10** | Como recepcionista, quero destacar visualmente "Clientes Novos" para saber quem precisa de abertura de processo. | Tickets marcados como "Novo" devem aparecer com uma tag amarela ou fundo diferenciado na tabela. | **Should** | 3h |
| **US11** | Como paciente, quero ver o sistema em Português ou Inglês para facilitar a navegação. | Implementação de um seletor de idioma que traduz labels do formulário e menus. | **Should** | 12h |
| **US12** | Como médico, quero ver o tempo de espera de cada paciente para gerir prioridades. | Mostrar um contador (ex: "Esperando há 20 min") calculado entre a criação do ticket e a hora atual. | **Could** | 6h |
| **US13** | Como recepcionista, quero cancelar uma marcação específica caso o paciente desista. | Botão "Cancelar" que move o ticket para um estado inativo, removendo-o das estatísticas de hoje. | **Could** | 4h |
| **US14** | Como diretor, quero exportar o relatório do dia para um ficheiro CSV para arquivo físico. | Botão "Exportar Relatório" que gera um arquivo com tickets, processos e médicos do dia. | **Could** | 8h |
| **US15** | Como sistema, quero migrar o armazenamento de JSON para PostgreSQL (PGSQL) para maior segurança. | Refatoração da camada de dados para suportar queries SQL em vez de `file_put_contents`. | **Won't** | 24h |
