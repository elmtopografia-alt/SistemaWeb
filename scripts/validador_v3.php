<?php
/**
 * VALIDADOR v3.0 - SGT PROPOSTAS (Versão Web/SaaS Dashboard)
 * 
 * Audita se os arquivos DOCX na pasta models seguem o contrato estrito v3.0.
 * Gera relatório visual em HTML (Glassmorphism SGT Theme).
 */

require_once __DIR__ . '/../core/TemplateEngineV3.php';

class ValidadorDashboard {
    
    private $mapeamento;
    private $chavesPermitidas;
    
    public function __construct() {
        $this->mapeamento = require __DIR__ . '/../config/mapeamento_v3.php';
        $this->chavesPermitidas = array_keys($this->mapeamento);
    }
    
    public function auditar($caminhoDir) {
        $arquivos = glob($caminhoDir . '/*.docx');
        $relatorio = [];
        
        foreach ($arquivos as $arquivo) {
            $relatorio[] = $this->validarArquivo($arquivo);
        }
        return $relatorio;
    }
    
    private function validarArquivo($caminho) {
        $nome = basename($caminho);
        try {
            $zip = new ZipArchive();
            if ($zip->open($caminho) !== true) return ['nome' => $nome, 'status' => 'ERRO', 'msg' => 'ZIP inválido'];
            
            $xml = $zip->getFromName('word/document.xml');
            $zip->close();
            
            if (!$xml) return ['nome' => $nome, 'status' => 'ERRO', 'msg' => 'XML não encontrado'];
            
            preg_match_all('/\$\{(\w+)\}/', $xml, $matches);
            $chavesEncontradas = array_unique($matches[1]);
            
            $invalidas = [];
            $validas = [];
            foreach ($chavesEncontradas as $chave) {
                if (in_array($chave, $this->chavesPermitidas)) $validas[] = $chave;
                else $invalidas[] = $chave;
            }
            
            return [
                'nome' => $nome,
                'status' => empty($invalidas) ? 'SUCESSO' : 'REPROVADO',
                'validas' => count($validas),
                'invalidas' => $invalidas,
                'total' => count($chavesEncontradas)
            ];
            
        } catch (Exception $e) {
            return ['nome' => $nome, 'status' => 'ERRO', 'msg' => $e->getMessage()];
        }
    }
}

// Lógica de Renderização
$validador = new ValidadorDashboard();
// Pasta de produção no servidor
$caminhoAudit = __DIR__ . '/../modelos_prod'; 
$relatorio = $validador->auditar($caminhoAudit);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>SGT - Dashboard Auditoria v3.0</title>
    <style>
        body { background: #0b0f19; color: #f1f5f9; font-family: 'Outfit', sans-serif; margin: 0; padding: 40px; }
        .dashboard { max-width: 1200px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .status-pill { padding: 4px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: bold; }
        .status-SUCESSO { background: #059669; color: #ecfdf5; }
        .status-REPROVADO { background: #dc2626; color: #fef2f2; }
        .status-ERRO { background: #94a3b8; color: #1e293b; }
        .card { background: rgba(30, 41, 59, 0.5); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.08); border-radius: 20px; padding: 25px; margin-bottom: 20px; transition: 0.3s; }
        .card:hover { border-color: #38bdf8; transform: translateY(-3px); }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .invalid-keys { color: #f87171; font-size: 0.85rem; margin-top: 10px; background: rgba(220, 38, 38, 0.1); padding: 10px; border-radius: 8px; border: 1px dashed #ef4444; }
        .btn-refresh { background: #38bdf8; color: #0b0f19; padding: 10px 20px; border-radius: 10px; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="header">
            <div>
                <h1 style="margin:0; color:#38bdf8;">Auditoria de Modelos v3.0</h1>
                <p style="color:#94a3b8;">Monitoramento estrito de conformidade de chaves DOCX</p>
            </div>
            <a href="?" class="btn-refresh">RE-AUDITAR SERVIDOR</a>
        </div>

        <div class="grid">
            <?php foreach ($relatorio as $r): ?>
                <div class="card">
                    <div style="display:flex; justify-content:space-between;">
                        <span style="font-weight:600;"><?php echo $r['nome']; ?></span>
                        <span class="status-pill status-<?php echo $r['status']; ?>"><?php echo $r['status']; ?></span>
                    </div>
                    
                    <div style="margin-top:15px; font-size:0.9rem; color:#94a3b8;">
                        <span>Válidas: <?php echo $r['validas'] ?? 0; ?></span> | 
                        <span>Total: <?php echo $r['total'] ?? 0; ?></span>
                    </div>

                    <?php if (!empty($r['invalidas'])): ?>
                        <div class="invalid-keys">
                            <strong>🚫 Chaves Fora do Padrão:</strong><br>
                            <?php echo implode(', ', array_map(fn($k) => "\${{$k}}", $r['invalidas'])); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($r['status'] === 'ERRO'): ?>
                        <div style="color:#ef4444; margin-top:10px;">Erro: <?php echo $r['msg']; ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div style="margin-top:40px; text-align:center; color:#475569; font-size:0.8rem;">
            SGT PROPOSTAS v3.0 - AMBIENTE WEB SEGURO
        </div>
    </div>
</body>
</html>
