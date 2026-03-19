<?php
/**
 * MAPEAMENTO v3.0 - SGT PROPOSTAS
 * 
 * Define a tradução entre:
 * - Chaves DOCX (padrão v3.0)
 * - Colunas Banco (legado existente)
 * 
 * Regra: Chave DOCX sempre snake_case descritivo
 *        Coluna Banco pode ser legado até migração completa
 */

return [
    // ============================================
    // 1. DADOS DO CLIENTE
    // ============================================
    'nome_cliente' => [
        'docx' => '${nome_cliente}',
        'banco' => 'nome_cliente_salvo',      // legado
        'tipo' => 'string',
        'obrigatorio' => true,
        'validacao' => 'required|min:3'
    ],
    'email_cliente' => [
        'docx' => '${email_cliente}',
        'banco' => 'email_salvo',              // legado
        'tipo' => 'email',
        'obrigatorio' => true,
        'validacao' => 'required|email'
    ],
    'whatsapp_cliente' => [
        'docx' => '${whatsapp_cliente}',
        'banco' => 'whatsapp_salvo',           // legado
        'tipo' => 'string',
        'obrigatorio' => true,
        'validacao' => 'required'
    ],
    'telefone_cliente' => [
        'docx' => '${telefone_cliente}',
        'banco' => 'telefone_salvo',           // legado
        'tipo' => 'string',
        'obrigatorio' => false,
        'validacao' => 'nullable'
    ],

    // ============================================
    // 2. DADOS DA EMPRESA (Proponente)
    // ============================================
    'empresa_nome' => [
        'docx' => '${empresa_nome}',
        'banco' => 'empresa',                  // legado
        'tipo' => 'string',
        'obrigatorio' => true,
        'origem' => 'DadosEmpresa.nome'
    ],
    'empresa_cnpj' => [
        'docx' => '${empresa_cnpj}',
        'banco' => 'cnpj',                     // legado
        'tipo' => 'cnpj',
        'obrigatorio' => true,
        'origem' => 'DadosEmpresa.cnpj'
    ],
    'empresa_logo_url' => [
        'docx' => '${empresa_logo_url}',
        'banco' => 'logo_url',                 // novo ou DadosEmpresa
        'tipo' => 'imagem',
        'obrigatorio' => false,
        'origem' => 'DadosEmpresa.logo_url'
    ],
    'empresa_cidade' => [
        'docx' => '${empresa_cidade}',
        'banco' => 'cidade',                   // legado - ATENÇÃO: cidade da empresa!
        'tipo' => 'string',
        'obrigatorio' => true,
        'origem' => 'DadosEmpresa.cidade'
    ],

    // ============================================
    // 3. PROPOSTA E OBRA
    // ============================================
    'numero_proposta' => [
        'docx' => '${numero_proposta}',
        'banco' => 'numero_proposta',
        'tipo' => 'string',
        'obrigatorio' => true
    ],
    'data_emissao' => [
        'docx' => '${data_emissao}',
        'banco' => 'data_emissao',
        'tipo' => 'date',
        'obrigatorio' => true,
        'formato' => 'd/m/Y'
    ],
    'data_emissao_extenso' => [
        'docx' => '${data_emissao_extenso}',
        'banco' => null,                       // calculado, não persiste
        'tipo' => 'calculado',
        'obrigatorio' => true,
        'calculo' => 'dataExtenso'
    ],
    'endereco_obra' => [
        'docx' => '${endereco_obra}',
        'banco' => 'endereco_obra',
        'tipo' => 'text',
        'obrigatorio' => true
    ],
    'cidade_obra' => [
        'docx' => '${cidade_obra}',
        'banco' => 'cidade_obra',
        'tipo' => 'string',
        'obrigatorio' => true
    ],
    'estado_obra' => [
        'docx' => '${estado_obra}',
        'banco' => 'estado_obra',
        'tipo' => 'string',
        'obrigatorio' => true
    ],
    'finalidade_obra' => [
        'docx' => '${finalidade_obra}',
        'banco' => 'finalidade',
        'tipo' => 'text',
        'obrigatorio' => true
    ],

    // ============================================
    // 4. VALORES FINANCEIROS
    // ============================================
    'valor_total' => [
        'docx' => '${valor_total}',
        'banco' => 'valor_final_proposta',     // LEGADO - ver nota abaixo
        'tipo' => 'moeda',
        'obrigatorio' => true,
        'formato' => 'R$ #.##0,00'
    ],
    'valor_total_extenso' => [
        'docx' => '${valor_total_extenso}',
        'banco' => 'valorextenso',             // legado
        'tipo' => 'string',
        'obrigatorio' => true
    ],
    'valor_entrada' => [
        'docx' => '${valor_entrada}',
        'banco' => 'mobilizacao_valor',
        'tipo' => 'moeda',
        'obrigatorio' => false
    ],
    'valor_restante' => [
        'docx' => '${valor_restante}',
        'banco' => 'restante_valor',
        'tipo' => 'moeda',
        'obrigatorio' => false
    ],

    // ============================================
    // 5. DADOS BANCÁRIOS (Empresa)
    // ============================================
    'banco_nome' => [
        'docx' => '${banco_nome}',
        'banco' => 'banco',
        'tipo' => 'string',
        'obrigatorio' => false,
        'origem' => 'DadosEmpresa.banco'
    ],
    'banco_agencia' => [
        'docx' => '${banco_agencia}',
        'banco' => 'agencia',
        'tipo' => 'string',
        'obrigatorio' => false,
        'origem' => 'DadosEmpresa.agencia'
    ],
    'banco_conta' => [
        'docx' => '${banco_conta}',
        'banco' => 'conta',
        'tipo' => 'string',
        'obrigatorio' => false,
        'origem' => 'DadosEmpresa.conta'
    ]
];
