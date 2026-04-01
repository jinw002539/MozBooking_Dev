# Product Backlog – Sistema de Marcação Clínico

| ID | User Story | Critério de Aceitação | Prioridade | Estimativa |
| :--- | :--- | :--- | :--- | :--- |
| US01 | Como paciente quero criar uma conta no sistema para poder marcar consultas. | Deve permitir inserir nome, telefone, email e senha.<br>Email não pode estar duplicado.<br>Senha deve ter no mínimo 8 caracteres e pelo menos 1 caractere especial.<br>Sistema deve confirmar cadastro com mensagem de sucesso. | Must | 8h |
| US02 | Como usuário quero fazer login para acessar o sistema. | Deve validar email e senha.<br>Deve impedir login com dados incorretos.<br>Deve redirecionar para a área correta conforme o nível de acesso. | Must | 4h |
| US03 | Como paciente quero marcar uma consulta para ser atendido pelo médico. | Deve permitir escolher data.<br>Deve permitir escolher horário disponível.<br>Não deve permitir marcar horário ocupado. | Must | 12h |
| US04 | Como paciente quero cancelar uma consulta para liberar o horário. | Deve permitir cancelar até 24h antes.<br>Deve atualizar agenda automaticamente.<br>Deve enviar notificação de cancelamento. | Should | 6h |
| US05 | Como paciente quero reagendar consulta para alterar data ou horário. | Deve permitir escolher novo horário disponível.<br>Deve atualizar agenda.<br>Deve enviar confirmação. | Should | 8h |
| US06 | Como médico quero ver minha agenda diária para organizar meus atendimentos. | Deve mostrar lista de pacientes do dia.<br>Deve mostrar horário de cada consulta.<br>Deve permitir visualizar histórico. | Must | 10h |
| US07 | Como administrador quero cadastrar médicos para disponibilizar consultas. | Deve permitir inserir nome, especialidade e horário de trabalho.<br>Não pode permitir duplicação.<br>Deve salvar no sistema automaticamente. | Must | 6h |
| US08 | Como administrador quero definir horários disponíveis para organizar agenda da clínica. | Deve permitir definir horário inicial e final.<br>Não deve permitir conflitos de horário. | Should | 6h |
| US09 | O sistema deve enviar notificação de confirmação para lembrar o paciente. | Deve enviar email ou SMS.<br>Deve ser enviado após marcação.<br>Deve conter data e horário. | Should | 8h |
| US10 | Como paciente quero visualizar meu histórico para acompanhar consultas anteriores. | Deve mostrar lista de consultas passadas.<br>Deve mostrar médico e data. | Should | 4h |
| US11 | Como medico quero concluir uma consulta para que o sistema libere o horário como vazio. | Deve permitir clicar em "Concluir".<br>Deve alterar o status da consulta para "Concluída".<br>Deve atualizar a disponibilidade do médico no sistema. | Should | 4h |
| US12 | Como administrador quero gerar relatório diário para acompanhar atendimentos. | Deve mostrar número de consultas e o médico.<br>Deve permitir exportar PDF. | Should | 12h |
| US13 | Como recepcionista quero pesquisar paciente para localizar rapidamente cadastro. | Deve permitir pesquisa por nome, telefone ou email.<br>Deve mostrar resultados correspondentes. | Should | 4h |
| US14 | Como administrador quero bloquear horários específicos para evitar marcações. | Deve impedir marcação no horário bloqueado.<br>Deve mostrar mensagem de indisponível. | Must | 6h |
| US15 | Como administrador quero visualizar painel geral para acompanhar funcionamento da clínica. | Deve mostrar número de consultas do dia.<br>Deve mostrar médicos disponíveis.<br>Deve atualizar automaticamente os relatórios diários. | Should | 10h |
