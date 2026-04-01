## Requisitos Funcionais (RF)

| ID | Nome do Requisito | Descrição | Prioridade |
| :--- | :--- | :--- | :--- |
| RF01 | Cadastro de Pacientes | O sistema deve permitir o cadastro de pacientes com validação de BI e Email únicos. | Must |
| RF02 | Marcação de Consultas | O sistema deve permitir o agendamento de consultas online selecionando médico e horário. | Must |
| RF03 | Painel do Médico | O sistema deve permitir que o médico visualize a sua agenda e histórico de consultas. | Must |
| RF04 | Painel da Recepcionista | O sistema deve permitir gerir o fluxo de pacientes e estados das consultas em tempo real. | Must |
| RF05 | Regra para marcar | O sistema deve impedir tecnicamente a marcação de dois pacientes no mesmo horário para o mesmo médico. | Must |
| RF06 | Conclusão de Consulta | O sistema deve permitir que o diretor clique em concluir consulta para o sistema libertar o horário como vazio. | Should |

## Requisitos Não Funcionais (RNF)

| ID | Nome do Requisito | Descrição | Prioridade |
| :--- | :--- | :--- | :--- |
| RNF01 | Desempenho | O sistema deve processar as confirmações de consultas em menos de 2 segundos. | High |
| RNF02 | Disponibilidade | O sistema deve estar disponível 99.5% do tempo para garantir o acesso às marcações. | High |
| RNF03 | Segurança | Os dados sensíveis como BI e histórico devem ser protegidos e acessíveis apenas por pessoal autorizado. | High |
| RNF04 | Usabilidade | A interface deve ser simples e intuitiva para facilitar o uso por pacientes e recepcionistas. | Medium |
