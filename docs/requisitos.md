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

| ID | Categoria | Requisito Não-Funcional | Métrica/Critério |
| :--- | :--- | :--- | :--- |
| RNF01 | Desempenho | Tempo de resposta das marcações | O sistema deve processar as confirmações de consultas em menos de 2 segundos. |
| RNF02 | Disponibilidade | Tempo de atividade do sistema | O sistema deve estar disponível 99.5% do tempo para garantir o acesso às marcações. |
| RNF03 | Segurança | Proteção de dados sensíveis | Os dados como BI e histórico devem ser acessíveis apenas por pessoal autorizado através de criptografia. |
| RNF04 | Usabilidade | Facilidade de uso da interface | A interface deve ser intuitiva, permitindo que um novo usuário realize uma marcação em menos de 3 minutos sem ajuda. |
