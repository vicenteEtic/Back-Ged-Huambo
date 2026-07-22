<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted'             => 'O campo :attribute deve ser aceito.',
    'accepted_if'          => 'O :attribute deve ser aceito quando :other for :value.',
    'active_url'           => 'O campo :attribute não é uma URL válida.',
    'after'                => 'O campo :attribute deve ser uma data posterior a :date.',
    'after_or_equal'       => 'O campo :attribute deve ser uma data posterior ou igual a :date.',
    'alpha'                => 'O campo :attribute só pode conter letras.',
    'alpha_dash'           => 'O campo :attribute só pode conter letras, números e traços.',
    'alpha_num'            => 'O campo :attribute só pode conter letras e números.',
    'array'                => 'O campo :attribute deve ser uma matriz.',
    'before'               => 'O campo :attribute deve ser uma data anterior :date.',
    'before_or_equal'      => 'O campo :attribute deve ser uma data anterior ou igual a :date.',
    'between'              => [
        'numeric' => 'O campo :attribute deve ser entre :min e :max.',
        'file'    => 'O campo :attribute deve ser entre :min e :max kilobytes.',
        'string'  => 'O campo :attribute deve ser entre :min e :max caracteres.',
        'array'   => 'O campo :attribute deve ter entre :min e :max itens.',
    ],
    'boolean'              => 'O campo :attribute deve ser verdadeiro ou falso.',
    'confirmed'            => 'O campo :attribute de confirmação não confere.',
    'current_password'     => 'A senha está incorreta.',
    'date'                 => 'O campo :attribute não é uma data válida.',
    'date_equals'          => 'O campo :attribute deve ser uma data igual a :date.',
    'date_format'          => 'O campo :attribute não corresponde ao formato :format.',
    'decimal'              => 'O campo :attribute deve conter entre :decimal números decimais.',
    'declined'             => 'O :attribute deve ser recusado.',
    'declined_if'          => 'O :attribute deve ser recusado quando :other for :value.',
    'different'            => 'Os campos :attribute e :other devem ser diferentes.',
    'digits'               => 'O campo :attribute deve ter :digits dígitos.',
    'digits_between'       => 'O campo :attribute deve ter entre :min e :max dígitos.',
    'dimensions'           => 'O campo :attribute tem dimensões de imagem inválidas.',
    'distinct'             => 'O campo :attribute campo tem um valor duplicado.',
    'doesnt_start_with'    => 'O :attribute não pode começar com um dos seguintes: :values.',
    'email'                => 'O campo :attribute deve ser um endereço de e-mail válido.',
    'ends_with'            => 'O campo :attribute deve terminar com um dos seguintes: :values',
    'enum'                 => 'O :attribute selecionado é inválido.',
    'exists'               => 'O campo :attribute selecionado é inválido.',
    'file'                 => 'O campo :attribute deve ser um arquivo.',
    'filled'               => 'O campo :attribute deve ter um valor.',
    'gt' => [
        'numeric' => 'O campo :attribute deve ser maior que :value.',
        'file'    => 'O campo :attribute deve ser maior que :value kilobytes.',
        'string'  => 'O campo :attribute deve ser maior que :value caracteres.',
        'array'   => 'O campo :attribute deve conter mais de :value itens.',
    ],
    'gte' => [
        'numeric' => 'O campo :attribute deve ser maior ou igual a :value.',
        'file'    => 'O campo :attribute deve ser maior ou igual a :value kilobytes.',
        'string'  => 'O campo :attribute deve ser maior ou igual a :value caracteres.',
        'array'   => 'O campo :attribute deve conter :value itens ou mais.',
    ],
    'image'                => 'O campo :attribute deve ser uma imagem.',
    'in'                   => 'O campo :attribute selecionado é inválido.',
    'in_array'             => 'O campo :attribute não existe em :other.',
    'integer'              => 'O campo :attribute deve ser um número inteiro.',
    'ip'                   => 'O campo :attribute deve ser um endereço de IP válido.',
    'ipv4'                 => 'O campo :attribute deve ser um endereço IPv4 válido.',
    'ipv6'                 => 'O campo :attribute deve ser um endereço IPv6 válido.',
    'json'                 => 'O campo :attribute deve ser uma string JSON válida.',
    'lt' => [
        'numeric' => 'O campo :attribute deve ser menor que :value.',
        'file'    => 'O campo :attribute deve ser menor que :value kilobytes.',
        'string'  => 'O campo :attribute deve ser menor que :value caracteres.',
        'array'   => 'O campo :attribute deve conter menos de :value itens.',
    ],
    'lte' => [
        'numeric' => 'O campo :attribute deve ser menor ou igual a :value.',
        'file'    => 'O campo :attribute deve ser menor ou igual a :value kilobytes.',
        'string'  => 'O campo :attribute deve ser menor ou igual a :value caracteres.',
        'array'   => 'O campo :attribute não deve conter mais que :value itens.',
    ],
    'max' => [
        'numeric' => 'O campo :attribute não pode ser superior a :max.',
        'file'    => 'O campo :attribute não pode ser superior a :max kilobytes.',
        'string'  => 'O campo :attribute não pode ser superior a :max caracteres.',
        'array'   => 'O campo :attribute não pode ter mais do que :max itens.',
    ],
    'max_digits'           => 'O campo :attribute não pode ser superior a :max dígitos',
    'mimes'                => 'O campo :attribute deve ser um arquivo do tipo: :values.',
    'mimetypes'            => 'O campo :attribute deve ser um arquivo do tipo: :values.',
    'min' => [
        'numeric' => 'O campo :attribute deve ser pelo menos :min.',
        'file'    => 'O campo :attribute deve ter pelo menos :min kilobytes.',
        'string'  => 'O campo :attribute deve ter pelo menos :min caracteres.',
        'array'   => 'O campo :attribute deve ter pelo menos :min itens.',
    ],
    'missing_with' => 'O campo :attribute não deve estar presente quando houver :values.',
    'min_digits'           => 'O campo :attribute deve ter pelo menos :min dígitos',
    'not_in'               => 'O campo :attribute selecionado é inválido.',
    'multiple_of'          => 'O campo :attribute deve ser um múltiplo de :value.',
    'not_regex'            => 'O campo :attribute possui um formato inválido.',
    'numeric'              => 'O campo :attribute deve ser um número.',
    'password' => [
        'letters'          => 'O campo :attribute deve conter pelo menos uma letra.',
        'mixed'            => 'O campo :attribute deve conter pelo menos uma letra maiúscula e uma letra minúscula.',
        'numbers'          => 'O campo :attribute deve conter pelo menos um número.',
        'symbols'          => 'O campo :attribute deve conter pelo menos um símbolo.',
        'uncompromised'    => 'A senha que você inseriu em :attribute está em um vazamento de dados.'
            . ' Por favor escolha uma senha diferente.',
    ],
    'present'              => 'O campo :attribute deve estar presente.',
    'regex'                => 'O campo :attribute tem um formato inválido.',
    'required'             => 'O campo :attribute é obrigatório.',
    'required_array_keys'  => 'O campo :attribute deve conter entradas para: :values.',
    'required_if'          => 'O campo :attribute é obrigatório quando :other for :value.',
    'required_unless'      => 'O campo :attribute é obrigatório exceto quando :other for :values.',
    'required_with'        => 'O campo :attribute é obrigatório quando :values está presente.',
    'required_with_all'    => 'O campo :attribute é obrigatório quando :values está presente.',
    'required_without'     => 'O campo :attribute é obrigatório quando :values não está presente.',
    'required_without_all' => 'O campo :attribute é obrigatório quando nenhum dos :values estão presentes.',
    'prohibited'           => 'O campo :attribute é proibido.',
    'prohibited_if'        => 'O campo :attribute é proibido quando :other for :value.',
    'prohibited_unless'    => 'O campo :attribute é proibido exceto quando :other for :values.',
    'prohibits'            => 'O campo :attribute proíbe :other de estar presente.',
    'same'                 => 'Os campos :attribute e :other devem corresponder.',
    'size'                 => [
        'numeric' => 'O campo :attribute deve ser :size.',
        'file'    => 'O campo :attribute deve ser :size kilobytes.',
        'string'  => 'O campo :attribute deve ser :size caracteres.',
        'array'   => 'O campo :attribute deve conter :size itens.',
    ],
    'starts_with'          => 'O campo :attribute deve começar com um dos seguintes valores: :values',
    'string'               => 'O campo :attribute deve ser uma string.',
    'timezone'             => 'O campo :attribute deve ser uma zona válida.',
    'unique'               => 'O campo :attribute já está sendo utilizado.',
    'uploaded'             => 'Ocorreu uma falha no upload do campo :attribute.',
    'url'                  => 'O campo :attribute tem um formato inválido.',
    'uuid' => 'O campo :attribute deve ser um UUID válido.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'failed_validation' => 'Falha na validação',

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [
        'address'   => 'endereço',
        'age'       => 'idade',
        'body'      => 'conteúdo',
        'cell'      => 'célula',
        'city'      => 'cidade',
        'country'   => 'país',
        'date'      => 'data',
        'day'       => 'dia',
        'excerpt'   => 'resumo',
        'first_name' => 'primeiro nome',
        'gender'    => 'género',
        'marital_status' => 'estado civil',
        'profession' => 'profissão',
        'nationality' => 'nacionalidade',
        'hour'      => 'hora',
        'last_name' => 'sobrenome',
        'message'   => 'mensagem',
        'minute'    => 'minuto',
        'mobile'    => 'celular',
        'month'     => 'mês',
        'name'      => 'nome',
        'zipcode'   => 'cep',
        'company_name'   => 'razão social',
        'neighborhood' => 'bairro',
        'number'    => 'número',
        'password'  => 'senha',
        'phone'     => 'telefone',
        'second'    => 'segundo',
        'sex'       => 'sexo',
        'state'     => 'estado',
        'street'    => 'rua',
        'subject'   => 'assunto',
        'text'      => 'texto',
        'time'      => 'hora',
        'title'     => 'título',
        'username'  => 'usuário',
        'year'      => 'ano',
        'description' => 'descrição',
        'password_confirmation' => 'confirmação da senha',
        'current_password' => 'senha actual',
        'complement' => 'complemento',
        'modality' => 'modalidade',
        'category' => 'categoria',
        'blood_type' => 'tipo sanguíneo',
        'birth_date' => 'data de nascimento',

        // RH - Funcionários
        'employee_id' => 'funcionário',
        'employee_number' => 'número de funcionário',
        'full_name' => 'nome completo',
        'date_of_birth' => 'data de nascimento',
        'marital_status' => 'estado civil',
        'document_type' => 'tipo de documento',
        'document_number' => 'número do documento',
        'nif' => 'NIF',
        'personal_email' => 'email pessoal',
        'department_id' => 'departamento',
        'position_id' => 'cargo',
        'hire_date' => 'data de admissão',
        'effective_date' => 'data efectiva',
        'contract_type' => 'tipo de contrato',
        'base_salary' => 'salário base',
        'bank_name' => 'nome do banco',
        'bank_iban' => 'IBAN',
        'photo_url' => 'foto',
        'user_id' => 'utilizador',

        // RH - Departamentos e Cargos
        'code' => 'código',
        'responsible_id' => 'responsável',
        'parent_id' => 'departamento superior',
        'is_active' => 'activo',
        'level' => 'nível',
        'requirements' => 'requisitos',

        // RH - Áreas
        'department_id' => 'departamento',

        // RH - Férias e Licenças
        'leave_type_id' => 'tipo de licença',
        'leave_plan_id' => 'plano de férias',
        'start_date' => 'data de início',
        'end_date' => 'data de fim',
        'total_days_entitled' => 'dias totais direitos',
        'days_used' => 'dias utilizados',
        'days_pending' => 'dias pendentes',
        'allows_carryover' => 'permite transferência',
        'max_carryover_days' => 'máximo de dias transferidos',
        'requires_attachment' => 'requer anexo',
        'default_days' => 'dias padrão',
        'reason' => 'motivo',
        'approved_by' => 'aprovado por',
        'approved_at' => 'aprovado em',
        'rejection_reason' => 'motivo da rejeição',
        'status' => 'estado',
        'observed_at' => 'data de observação',

        // RH - Ponto e Assiduidade
        'check_in' => 'hora de entrada',
        'check_out' => 'hora de saída',
        'absence_type' => 'tipo de falta',
        'absence_reason' => 'motivo da falta',
        'is_justified' => 'justificado',
        'notes' => 'observações',
        'shift_id' => 'turno',
        'start_time' => 'hora de início',
        'end_time' => 'hora de fim',
        'grace_minutes' => 'minutos de tolerância',
        'duration_hours' => 'duração em horas',

        // RH - Pagamento
        'payroll_period_id' => 'período de pagamento',
        'base_salary' => 'salário base',
        'transport_allowance' => 'subsídio de transporte',
        'meal_allowance' => 'subsídio de alimentação',
        'overtime' => 'horas extra',
        'other_earnings' => 'outros ganhos',
        'inss_deduction' => 'desconto INSS',
        'irt_deduction' => 'desconto IRT',
        'other_deductions' => 'outros descontos',
        'gross_pay' => 'vencimento bruto',
        'total_deductions' => 'total de descontos',
        'net_pay' => 'vencimento líquido',
        'payment_date' => 'data de pagamento',
        'code' => 'código',

        // RH - Recrutamento
        'job_opening_id' => 'vaga',
        'candidate_id' => 'candidato',
        'application_id' => 'candidatura',
        'interviewer_id' => 'entrevistador',
        'scheduled_at' => 'data agendada',
        'location' => 'local',
        'feedback' => 'feedback',
        'rating' => 'classificação',
        'vacancies' => 'vagas',
        'published_at' => 'data de publicação',
        'closes_at' => 'data de encerramento',
        'cover_letter' => 'carta de apresentação',
        'source' => 'fonte',
        'resume_path' => 'currículo',
        'interview_date' => 'data da entrevista',
        'interview_type' => 'tipo de entrevista',

        // RH - Formação
        'course_id' => 'curso',
        'session_id' => 'sessão',
        'duration_hours' => 'duração em horas',
        'provider' => 'provedor',
        'max_participants' => 'máximo de participantes',
        'instructor' => 'instrutor',
        'start_date' => 'data de início',
        'end_date' => 'data de fim',
        'grade' => 'nota',

        // RH - Desempenho
        'cycle_id' => 'ciclo',
        'evaluator_id' => 'avaliador',
        'overall_score' => 'pontuação global',
        'strengths' => 'pontos fortes',
        'improvements' => 'áreas de melhoria',
        'submitted_at' => 'submetido em',
        'weight' => 'peso',
        'score' => 'pontuação',
        'max_score' => 'pontuação máxima',
        'section' => 'seção',
        'criterion_id' => 'critério',
        'evaluation_id' => 'avaliação',

        // RH - Benefícios
        'benefit_type_id' => 'tipo de benefício',
        'amount_requested' => 'valor solicitado',
        'amount_approved' => 'valor aprovado',
        'requested_date' => 'data de solicitação',
        'approved_date' => 'data de aprovação',
        'assistance_type' => 'tipo de assistência',
        'assistance_date' => 'data da assistência',
        'amount' => 'valor',

        // RH - Disciplina
        'disciplinary_type_id' => 'tipo disciplinar',
        'occurred_at' => 'data da ocorrência',
        'evidence_path' => 'evidência',
        'reported_by' => 'reportado por',
        'resolution' => 'resolução',
        'sanction' => 'sanção',
        'sanction_start' => 'início da sanção',
        'sanction_end' => 'fim da sanção',

        // RH - Carreira
        'rule_id' => 'regra',
        'to_category' => 'categoria destino',
        'to_position_id' => 'cargo destino',
        'new_salary' => 'novo salário',
        'justification' => 'justificação',
        'type' => 'tipo',
        'min_months_in_category' => 'mínimo de meses na categoria',
        'min_performance_score' => 'pontuação mínima de desempenho',
        'requires_training' => 'requer formação',
        'requires_evaluation' => 'requer avaliação',
        'from_category' => 'categoria origem',
        'from_level' => 'nível origem',
        'to_level' => 'nível destino',
        'salary_increase_percent' => 'percentual de aumento salarial',

        // RH - Reforma
        'request_date' => 'data de solicitação',
        'final_salary' => 'salário final',
        'pension_amount' => 'valor da pensão',
        'pension_type' => 'tipo de pensão',
        'documents' => 'documentos',
        'approved_date' => 'data de aprovação',

        // RH - Arquivo
        'category_id' => 'categoria',
        'document_number' => 'número do documento',
        'reference_number' => 'número de referência',
        'issuing_authority' => 'entidade emissora',
        'file' => 'arquivo',
        'file_path' => 'caminho do arquivo',
        'file_type' => 'tipo de arquivo',
        'file_size' => 'tamanho do arquivo',
        'mime_type' => 'tipo MIME',
        'confidentiality' => 'confidencialidade',
        'metadata' => 'metadados',
        'issued_date' => 'data de emissão',
        'expiry_date' => 'data de validade',
        'is_physical_copy' => 'cópia física',
        'physical_location' => 'localização física',
        'tags' => 'etiquetas',
        'version_number' => 'número da versão',

        // RH - Documentos do Funcionário
        'document_type' => 'tipo de documento',
        'is_verified' => 'verificado',

        // RH - Regras e Pedidos de Progressão
        'min_months_in_category' => 'mínimo de meses na categoria',
        'min_performance_score' => 'pontuação mínima de desempenho',

        // RH - Histórico Funcional
        'previous_value' => 'valor anterior',
        'new_value' => 'novo valor',
        'document_reference' => 'referência do documento',
        'created_by' => 'criado por',

        // Genéricos
        'email' => 'email',
        'title' => 'título',
        'url' => 'URL',
        'token' => 'token',
        'sort_order' => 'ordem',
        'icon' => 'ícone',
    ],

];
