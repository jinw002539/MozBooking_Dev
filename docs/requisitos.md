# 📋 Especificação de Requisitos - Sistema Vida
## 1. Requisitos Funcionais (RF)


| ID | Requisito | Descrição | Prioridade |
| :--- | :--- | :--- | :--- |
| **RF01** | **Landing Page Contextual** | O sistema deve apresentar uma página inicial com serviços (ECG, Ecografia, etc.) e acesso rápido ao agendamento. | Alta |
| **RF02** | **Agendamento por Ticket** | O paciente deve marcar consulta sem fornecer dados pessoais, recebendo um Ticket gerado aleatoriamente (`#V-XXXX`). | Crítica |
| **RF03** | **Seleção de Urgência** | O formulário deve permitir escolher entre consulta "Normal" ou "Urgente" (com aviso de taxa adicional). | Média |
| **RF04** | **Restrição Temporal** | O calendário deve limitar a escolha de datas aos próximos 15 dias, bloqueando anos futuros e datas passadas. | Alta |
| **RF05** | **Gestão de Processo Físico** | A rececionista deve poder associar o número do processo físico (ex: `P-500`) a um ticket pendente. | Alta |
| **RF06** | **Atribuição de Médico** | A rececionista deve selecionar qual médico atenderá o ticket antes de o enviar para a fila de espera. | Alta |
| **RF07** | **Painel de Chamada (Médico)** | O médico deve visualizar apenas os pacientes atribuídos a ele para o dia atual. | Alta |
| **RF08** | **Finalização de Consulta** | O médico/diretor deve poder marcar uma consulta como "Concluída" para atualizar as estatísticas e limpar a fila. | Média |
| **RF09** | **Sistema de Notificações** | A rececionista deve poder ativar um aviso global de "Consultas Canceladas/Remarcadas" visível na página inicial. | Alta |
| **RF10** | **Dashboard Estatístico** | O diretor clínico deve visualizar gráficos de volume de consultas por mês e por tipo de urgência. | Baixa |

## 2. Requisitos Não Funcionais (RNF)


| ID | Categoria | Requisito | Métrica |
| :--- | :--- | :--- | :--- |
| **RNF01** | **Privacidade (GDPR)** | O sistema não deve solicitar Nome, BI, Telefone ou Email do paciente. | 0 dados pessoais em disco. |
| **RNF02** | **Portabilidade** | O sistema deve ser baseado em ficheiros JSON para evitar dependência de servidores de BD SQL complexos. | Compatível com PHP 8.0+. |
| **RNF03** | **Interface (UX)** | O formulário de marcação deve ser acessível via âncora para evitar scroll excessivo. | Máximo 2 cliques para iniciar. |
| **RNF04** | **Localização** | O sistema deve suportar interface bilingue: Português (PT) e Inglês (EN). | Mudança de idioma global. |
| **RNF05** | **Segurança** | O acesso às áreas de Receção e Direção deve ser protegido por chave de acesso única. | Encriptação simples de sessão. |

## 3. Regras de Negócio (RN)

1. **Consulta Urgente:** Considera-se urgente o atendimento das 07h-09h e após as 16h, embora o sistema aceite a marcação em qualquer horário.
2. **Identificação:** O Ticket é a única chave de comunicação entre o paciente e a clínica.
3. **Ano Corrente:** O sistema assume sempre o ano atual de forma automática para evitar erros de introdução manual.
4. **Cancelamento:** Se um dia for cancelado pela receção, o sistema deve impedir novas marcações para essa data específica.
