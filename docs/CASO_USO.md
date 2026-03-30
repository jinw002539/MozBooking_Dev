## Diagrama de caso de uso
![Diagrama de caso de uso](clinica.png)
## Especificações textuais dos Casos de Uso
---

### CU01: Marcar Consulta
| Campo | Descrição |
| :--- | :--- |
| **Ator** | Paciente |
| **Pré-condição** | Paciente autenticado no sistema |
| **Pós-condição** | Consulta registada e visível na lista de marcações |
| **Fluxo Principal** | 1. Paciente inicia a marcação escolhendo a especialidade médica.<br>2. Sistema exibe médicos disponíveis para a especialidade.<br>3. Paciente escolhe o médico.<br>4. Sistema apresenta calendário com dias e horários livres.<br>5. Paciente seleciona dia e horário.<br>6. Sistema solicita confirmação.<br>7. Paciente confirma a marcação. |

---

### CU02: Cancelar Consulta
| Campo | Descrição |
| :--- | :--- |
| **Ator** | Paciente |
| **Pré-condição** | Paciente autenticado e com consulta marcada |
| **Pós-condição** | Estado da consulta atualizado para "Cancelada" |
| **Fluxo Principal** | 1. Paciente visualiza consultas marcadas.<br>2. Sistema apresenta a lista.<br>3. Paciente escolhe a consulta que deseja cancelar.<br>4. Paciente pressiona o botão cancelar.<br>5. Sistema pede confirmação.<br>6. Paciente confirma.<br>7. Sistema atualiza o estado e liberta o horário. |

---

### CU03: Visualizar Agenda
| Campo | Descrição |
| :--- | :--- |
| **Ator** | Médico |
| **Pré-condição** | Médico autenticado no sistema |
| **Pós-condição** | Médico visualiza os seus compromissos do dia/semana |
| **Fluxo Principal** | 1. Médico pressiona o botão "Visualizar Agenda".<br>2. Sistema procura as consultas na Base de Dados filtrando pelo ID do Médico.<br>3. Sistema exibe a lista de consultas confirmadas. |

---

### CU04: Gerir Fluxo de Consultas (Painel de Controlo)
| Campo | Descrição |
| :--- | :--- |
| **Ator** | Rececionista |
| **Pré-condição** | Rececionista autenticada |
| **Pós-condição** | Estado do paciente atualizado no fluxo da clínica |
| **Fluxo Principal** | 1. Rececionista visualiza a Agenda do Dia.<br>2. Sistema apresenta lista organizada por horário, médico e status.<br>3. Paciente chega à clínica e identifica-se.<br>4. Rececionista localiza a consulta na lista.<br>5. Rececionista altera estado para "Paciente em espera".<br>6. Sistema notifica o médico no painel dele. |

---

### CU05: Receber Notificação
| Campo | Descrição |
| :--- | :--- |
| **Ator Principal** | Sistema (Automático) |
| **Ator Passivo** | Paciente |
| **Pré-condição** | Consulta marcada no sistema |
| **Pós-condição** | Paciente informado sobre o lembrete |
| **Fluxo Principal** | 1. Sistema monitoriza a Base de Dados em intervalos regulares.<br>2. Sistema identifica consultas que ocorrerão em 24h.<br>3. Sistema formata a mensagem de lembrete.<br>4. Sistema envia notificação (Email/SMS).<br>5. Paciente recebe o alerta. |
