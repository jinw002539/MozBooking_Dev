# Sistema de Gestão de Marcações — Clínica Vida

O Sistema de Gestão de Marcações da Clínica Vida foi concebido como uma solução tecnológica avançada que prioriza a privacidade absoluta do cidadão e a agilidade no fluxo clínico. Ao contrário dos sistemas convencionais que exigem o preenchimento de extensos formulários de dados pessoais, esta plataforma opera sob o conceito de anonimato digital no agendamento. O paciente interage com uma interface simplificada onde a sua única identidade perante o sistema é um Ticket de Referência gerado aleatoriamente, eliminando qualquer armazenamento de nomes, números de identificação ou contactos no servidor.

## Descrição dos Requisitos Funcionais

A arquitetura funcional do sistema inicia-se na Landing Page Informativa que serve como o rosto digital da clínica, apresentando serviços como ECG e Ecografias. O módulo de Marcação Sem Login permite que o utilizador agende uma consulta definindo apenas o seu perfil de cliente, a urgência do ato médico e a data desejada. Existe uma regra de negócio rígida de Restrição de Datas que limita o agendamento aos próximos quinze dias, garantindo a atualidade da agenda. No núcleo administrativo, o Painel da Rececionista permite a gestão destes tickets, onde é feita a ponte entre o digital e o físico através da atribuição de números de processo e seleção do médico assistente. O fluxo encerra-se no Painel do Médico que oferece uma visão em tempo real da fila de espera e ferramentas de análise estatística para o Diretor Clínico, além de um sistema centralizado de Notificações de Alerta para gerir cancelamentos globais.

## Especificações dos Requisitos Não Funcionais

No âmbito da segurança e conformidade, o pilar central é a Privacidade, garantindo que o servidor permaneça livre de dados sensíveis. A Persistência de dados é assegurada por uma infraestrutura baseada em PostgreSQL, conferindo integridade e robustez às transações. O sistema foi desenhado para ser inclusivo através do suporte Multi-idioma em Português e Inglês, mantendo uma Disponibilidade total que permite ao paciente solicitar o seu ticket a qualquer hora do dia ou da noite, independentemente do horário de funcionamento físico da instituição.

## Tecnologias e Infraestrutura

A camada de interface utiliza HTML5 e CSS3 com design responsivo para adaptação a dispositivos móveis, apoiada por JavaScript para validações dinâmicas. O processamento lógico é executado em PHP 8.x no lado do servidor, comunicando diretamente com a base de dados PostgreSQL para o armazenamento seguro de tickets e configurações. Para a componente de gestão e inteligência de dados, o sistema integra a biblioteca Chart.js, que transforma os dados brutos de atendimento em gráficos visuais de fácil interpretação para a direção.

## Organização da Estrutura de Arquivos

O projeto está organizado de forma modular para facilitar a manutenção e escalabilidade. O ficheiro index.php concentra a experiência do paciente e o formulário de entrada. O acesso à equipa interna é processado pelo login.php, que redireciona o Staff para as suas áreas de competência: recepcionista.php para a gestão operacional de fluxo e medico.php para o atendimento clínico e visualização de dashboards. Todos os recursos visuais e folhas de estilo estão centralizados no diretório css, garantindo uma identidade visual coesa em todos os módulos do sistema.
