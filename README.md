# Sistema de Gestão de Marcações - Clínica Vida

Este projeto é um sistema de gestão de consultas médicas focado na **privacidade do paciente** e na **eficiência operacional**. O sistema elimina a necessidade de recolha de dados sensíveis (BI, Nome, Telefone) no ato da marcação, utilizando um sistema de **Tickets de Referência**.

## 📋 Requisitos do Sistema

### 1. Requisitos Funcionais (RF)
| ID | Nome | Descrição |
| :--- | :--- | :--- |
| **RF01** | Landing Page Informativa | O `index.php` deve exibir informações da clínica, fotos dos serviços e um botão de destaque para marcação. |
| **RF02** | Marcação Sem Login | O paciente realiza o agendamento apenas informando se é novo/antigo, a data e a urgência. |
| **RF03** | Geração de Ticket | O sistema gera automaticamente um código único (ex: #V-1234) que serve como identificação. |
| **RF04** | Restrição de Datas | O calendário de marcação limita a escolha aos próximos 15 dias, bloqueando anos futuros e datas passadas. |
| **RF05** | Painel da Rececionista | Interface para listar pendentes, atribuir o número de processo físico e definir o médico. |
| **RF06** | Gestão de Cancelamentos | A rececionista pode cancelar consultas e emitir avisos globais no sistema. |
| **RF07** | Painel do Médico/Diretor | Visualização da fila de espera em tempo real e gráficos de desempenho mensal. |
| **RF08** | Notificações de Alerta | Exibição de avisos na Landing Page caso haja alteração no funcionamento da clínica. |

### 2. Requisitos Não Funcionais (RNF)
| ID | Categoria | Descrição |
| :--- | :--- | :--- |
| **RNF01** | Privacidade | **Nenhum** dado pessoal (Nome, BI) é armazenado no servidor. O Ticket é a única ligação. |
| **RNF02** | Persistência | Os dados são armazenados na base de dados postgre |
| **RNF03** | Multi-idioma | A interface deve suportar Português (PT) e Inglês (EN). |
| **RNF04** | Disponibilidade | O sistema deve permitir marcações 24/7, mesmo fora do horário de atendimento da clínica. |

---

## 🛠️ Tecnologias Utilizadas
* **Frontend:** HTML5, CSS3 (Design Responsivo), JavaScript.
* **Backend:** PHP 8.x.
* **Armazenamento:** base de dados de postgre.
* **Gráficos:** Chart.js.

---

## 📂 Estrutura de Arquivos
* `index.php`: Landing Page e formulário de marcação.
* `login.php`: Acesso restrito para Staff.
* `recepcionista.php`: Gestão de tickets e atribuição de processos.
* `medico.php`: Dashboard estatístico e fila de chamada.
* `css/`: Estilos visuais separados por módulo.

