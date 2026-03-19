<?php
/**
 * TESTE DE RENDERIZAÇÃO v3.0 - SGT PROPOSTAS (Versão Web/SaaS)
 * 
 * Valida o fluxo completo: dados v3.0 → mapeamento → substituição → DOCX Final
 * Uso: Navegador (ex: crm-propostas/scripts/testar_renderizacao.php?modelo=PropostaDrone.docx)
 */

require_once __DIR__ . '/../core/TemplateEngineV3.php';
require_once __DIR__ . '/../core/PropostaRepositoryV3.php';
require_once __DIR__ . '/../db.php';

class TestadorSaaS {
    
    private $engine;
    
    public function __construct() {
        $this->engine = new TemplateEngineV3();
    }
    
    public function rodarTeste($modeloNome) {
        $caminhoModelo = __DIR__ . '/../modelos_unificados/' . $modeloNome;
        
        if (!file_exists($caminhoModelo)) {
            throw new Exception("Modelo não encontrado na pasta modelos_unificados: {$modeloNome}");
        }

        $dadosTeste = [
            'nome_cliente' => 'Cliente Piloto SGT v3.0',
            'email_cliente' => 'piloto@sgt.com.br',
            'whatsapp_cliente' => '(11) 99999-8888',
            'empresa_nome' => 'Antigravity SGT Engineering',
            'empresa_cnpj' => '12.345.678/0001-99',
            'empresa_cidade' => 'São Paulo/SP',
            'numero_proposta' => 'SGT-2026-TESTE-001',
            'data_emissao' => date('Y-m-d'),
            'endereco_obra' => 'Av. Paulista, 1000, Bela Vista',
            'cidade_obra' => 'São Paulo',
            'estado_obra' => 'SP',
            'finalidade_obra' => 'Levantamento de Prova Real v3.0',
            'valor_total' => 25000.00,
            'valor_total_extenso' => 'VINTE E CINCO MIL REAIS',
            'valor_entrada' => 5000.00,
            'valor_restante' => 20000.00,
            'banco_nome' => 'SGT BANK',
            'banco_agencia' => '0001',
            'banco_conta' => '123456-7'
        ];

        return $this->engine->renderizar($caminhoModelo, $dadosTeste);
    }
}

// Interface Web de Teste
$testador = new TestadorSaaS();
$modelosParaTestar = glob(__DIR__ . '/../modelos_unificados/*.docx');
$modeloSelecionado = $_GET['modelo'] ?? null;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>SGT - Teste de Renderização v3.0 SaaS</title>
    <style>
        body { background: #0b0f19; color: #f8fafc; font-family: 'Outfit', sans-serif; display: flex; justify-content: center; padding: 40px; }
        .box { background: rgba(30, 41, 59, 0.5); backdrop-filter: blur(25px); border: 1px solid rgba(255,255,255,0.1); border-radius: 20px; padding: 35px; width: 100%; max-width: 800px; box-shadow: 0 10px 40px rgba(0,0,0,0.5); }
        h1 { color: #38bdf8; font-weight: 700; border-bottom: 2px solid #38bdf822; padding-bottom: 10px; }
        .modelo-list { list-style: none; padding: 0; margin-top: 20px; }
        .modelo-item { background: rgba(255,255,255,0.03); margin-bottom: 10px; border-radius: 12px; transition: 0.3s; }
        .modelo-item:hover { background: rgba(56, 189, 248, 0.1); border-color: #38bdf8; }
        .modelo-link { display: flex; justify-content: space-between; align-items: center; padding: 15px 20px; text-decoration: none; color: #f8fafc; font-weight: 500; }
        .btn-test { background: #38bdf8; color: #0b0f19; padding: 6px 14px; border-radius: 8px; font-weight: 800; font-size: 0.75rem; text-transform: uppercase; }
        .alert-sucesso { background: #065f46; color: #ecfdf5; padding: 20px; border-radius: 12px; margin-bottom: 25px; border-left: 5px solid #10b981; }
    </style>
</head>
<body>
    <div class="box">
        <h1>Renderização Piloto v3.0 (SaaS)</h1>
        
        <?php if ($modeloSelecionado): ?>
            <?php try { 
                $resultado = $testador->rodarTeste($modeloSelecionado);
            ?>
                <div class="alert-sucesso">
                    <strong>✓ 100% CONCLUÍDO</strong><br>
                    O modelo <code><?php echo htmlspecialchars($modeloSelecionado); ?></code> foi renderizado com dados fictícios v3.0.<br><br>
                    <strong>Arquivo gerado no servidor:</strong><br>
                    <code><?php echo htmlspecialchars($resultado); ?></code><br><br>
                    <a href="?" style="color:#38bdf8; font-weight:bold;">Voltar para lista</a>
                </div>
            <?php } catch (Exception $e) { ?>
                <div style="background:#7f1d1d; color:#fef2f2; padding:20px; border-radius:12px; margin-bottom:25px;">
                    <strong>✗ FALHA NA RENDERIZAÇÃO</strong><br>
                    <?php echo $e->getMessage(); ?>
                    <br><br><a href="?" style="color:#f87171; font-weight:bold;">Tentar outro</a>
                </div>
            <?php } ?>
        <?php endif; ?>

        <p style="color:#94a3b8;">Selecione um modelo da pasta <code>/modelos_unificados</code> para testar a substituição de chaves v3.0:</p>
        
        <ul class="modelo-list">
            <?php if (empty($modelosParaTestar)): ?>
                <li style="color:#f87171; text-align:center; padding:20px;">
                    Nenhum modelo encontrado em <code>/modelos_unificados</code>.<br>
                    Rode o <strong>Unificador v3.0</strong> primeiro!
                </li>
            <?php else: ?>
                <?php foreach ($modelosParaTestar as $m): $base = basename($m); ?>
                    <li class="modelo-item">
                        <a href="?modelo=<?php echo urlencode($base); ?>" class="modelo-link">
                            <span>📄 <?php echo $base; ?></span>
                            <span class="btn-test">Testar Render v3.0</span>
                        </a>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
        
        <p style="margin-top:30px; text-align:center; font-size:0.8rem; color:#475569;">SGT PROPOSTAS v3.0 - AMBIENTE PROTEGIDO SaaS</p>
    </div>
</body>
</html>
