#Requisitos Funcionais
| ID | Descricao                                                                                                             |
|----|-----------------------------------------------------------------------------------------------------------------------|
|RF1 | O sistema deve permitir o cadastro de pacientes atraves de nome, e-mail, BI, contacto, validado obrigatoriamente que o e-mail e BI não estejam duplicados.|
|RF2 | O sistema deve permitir que o paciente pesquise médicos por especialidade e visualize apenas os horários que estejam efectivamente livres.|
|RF3 | O sistema deve permitir que o medico visualize a sua agenda diária e aceda ao histórico clinico, apenas dos pacientes que tem consulta marcada com ele.|
|RF4 | O sistema deve permitir que a recepcionista cancele ou reagende consultas através do acesso a agenda global dos médicos, validando a disponibilidade de horários de vada profissional em tempo real.|
|RF5 | O sistema deve impedir a marcação de multiplas consultas para o mesmo horario, garantindo que o médico não tenha mais de um paciente e que um paciente não tenha mais de uma consula na mesma hora.|


#Requisitos Nao Funcionais
|ID | Descricao                                                                                                              |
|---|------------------------------------------------------------------------------------------------------------------------|
|RNF1|A verificação de disponibilidade e o bloqueio de horário devem ser confirmados em até 5 segundos.|
|RNF2|O sistema deve estar operacional 24h por 7 dias para marcações online, com copias de segurança diárias de dados|
|RNF3|Todos os dados clínicos devem ser protegidos por criptografia, garantindo que apenas pessoal autorizado aceda a informação sensíveis.|
|RNF4|Em caso de falha de energia ou internet durante uma marcação, o sistema não deve permitir que o registo fique incompleto ou corrompido.|
|RNF5|A interface deve ser optimizada para ser rápida, permitindo finalizar um agendamento em até 4 cliques.|