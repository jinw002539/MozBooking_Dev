#  Arquitetura do Sistema - Clínica Vida

## Requisitos Principais do MVP (Minimum Viable Product)

| ID | Requisito Funcional | Estratégia de Mitigação |
| :--- | :--- | :--- |
| **RF01** | **Agendamento por Ticket** | Garante a privacidade total do paciente, eliminando a necessidade de recolha de dados sensíveis no FrontEnd. |
| **RF02** | **Controlo de Fluxo (Receção)** | Permite que a rececionista vincule o Ticket ao Processo Físico de papel, mantendo a compatibilidade com o arquivo da clínica. |
| **RF03** | **Fila de Chamada Médica** | Isolamento de dados onde cada médico visualiza apenas os tickets que lhe foram atribuídos pela receção para o dia atual. |
| **RF04** | **Sistema de Alertas Globais** | Permite a comunicação imediata de cancelamentos ou alterações de horário diretamente na Landing Page. |
| **RF05** | **Gestão de Urgências** | Algoritmo simples que destaca marcações "Urgentes" para priorização visual na fila de espera. |

## 🛠️ Escolha de Tecnologia

* **FrontEnd:** HTML5, CSS3, JavaScript (Interatividade e Gráficos).
* **BackEnd:** PHP 8.x (Lógica de servidor e sessões).
* **Base de Dados:** PostgreSQL (PGSQL) para persistência robusta e escalável.

## 📊 Diagrama da Arquitetura
![Diagrama da Arquitetura](diagrama.jpg)

##  Justificação Detalhada da Arquitetura

A fundamentação desta arquitetura baseia-se em três pilares críticos: **Privacidade por Design**, **Desacoplamento de Responsabilidades** e **Escalabilidade de Dados**.

### 1. Separação de Camadas e Padrão MVC
A adoção do padrão **MVC (Model-View-Controller)** permite que a lógica de negócio (atribuição de médicos, regras de urgência e geração de tickets) esteja isolada da interface do utilizador. 
* **Vantagem:** Isto facilita a manutenção do código. Se for necessário alterar o algoritmo de geração de tickets, a interface (View) do paciente permanece intacta, minimizando o risco de regressões no sistema.
* **Segurança:** Garante que as regras de acesso (quem pode ver o quê) sejam processadas no servidor (Controller) antes de qualquer dado ser enviado para o navegador.

### 2. Estratégia de Persistência com PostgreSQL (PGSQL)
Embora a fase inicial utilize JSON para prototipagem rápida, a transição para **PostgreSQL** é uma decisão estratégica para o ambiente clínico:
* **Integridade Referencial:** O PGSQL garante que um ticket nunca fique "órfão" sem um médico ou processo associado, utilizando chaves estrangeiras e restrições de base de dados.
* **Concorrência:** Essencial para evitar que dois médicos ou rececionistas tentem atualizar o estado do mesmo ticket simultaneamente, algo que ficheiros planos (JSON) não gerem com eficiência.
* **Auditoria:** Permite manter um histórico imutável de atendimentos, fundamental para métricas de desempenho e conformidade legal.

### 3. Foco na Privacidade (Compliance GDPR)
Diferente de sistemas de gestão hospitalar genéricos, esta arquitetura foi desenhada para **minimizar a pegada de dados sensíveis**.
* Ao utilizar o **Ticket de Referência** como chave primária de comunicação no FrontEnd, o sistema protege a identidade do paciente contra ataques de interceção de dados. 
* A vinculação ao **Processo Físico** ocorre apenas na camada interna da clínica (Receção), criando uma barreira de segurança entre o ambiente digital e a identidade real do cidadão.

### 4. Flexibilidade e Interoperabilidade
O uso de **PHP 8.x** aliado a uma estrutura modular permite que o sistema seja extremamente leve, correndo em hardware modesto (comum em clínicas locais). Simultaneamente, a arquitetura está preparada para futuras integrações via APIs, caso a clínica decida conectar o sistema de tickets a painéis de TV na sala de espera ou sistemas de faturação externa.
