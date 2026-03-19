<?php
/**
 * UNIFICADOR v3.0 - SGT PROPOSTAS (Versão Web/SaaS)
 * 
 * Este script faz a substituição em massa das chaves antigas pelas chaves v3.0 oficiais.
 * Destino: Pasta de Validação (modelos_unificados)
 */

class PreparadorV3 {
    
    private $dePara = [
        'data_extenso'          => 'data_emissao_extenso',
        'data'                  => 'data_emissao',
        'Data'                  => 'data_emissao',
        'empresa'               => 'empresa_nome',
        'Empresa'               => 'empresa_nome',
        'valor_extenso'         => 'valor_total_extenso',
        'celular_cliente'       => 'whatsapp_cliente',
        'cnpj'                  => 'empresa_cnpj',
        'CNPJ'                  => 'empresa_cnpj',
        'Cidade'                => 'empresa_cidade',
        'Banco'                 => 'banco_nome',
        'Agencia'               => 'banco_agencia',
        'Conta'                 => 'banco_conta',
        'chave_pix'             => 'banco_conta',
        'PIX'                   => 'banco_conta',
        'finalidade'            => 'finalidade_obra',
        'bairro_obra'           => 'endereco_obra',
        'numero_proposta'       => 'numero_proposta',
    ];

    public function processarDiretorio($diretorioEntrada, $diretorioSaida) {
        $resultados = [];
        if (!is_dir($diretorioSaida)) @mkdir($diretorioSaida, 0777, true);
        
        $arquivos = glob($diretorioEntrada . '/*.docx');
        
        foreach ($arquivos as $arquivo) {
            $nome = basename($arquivo);
            $destino = $diretorioSaida . '/' . $nome;
            $sucesso = $this->limparDocx($arquivo, $destino);
            $resultados[] = ['arquivo' => $nome, 'sucesso' => $sucesso];
        }
        return $resultados;
    }

    private function limparDocx($origem, $destino) {
        $tempDir = sys_get_temp_dir() . '/sgt_unific_' . uniqid();
        @mkdir($tempDir, 0777, true);

        $zip = new ZipArchive();
        if ($zip->open($origem) !== true) return false;
        $zip->extractTo($tempDir);
        $zip->close();

        $xmlPath = $tempDir . '/word/document.xml';
        if (file_exists($xmlPath)) {
            $xml = file_get_contents($xmlPath);
            // Limpa sujeira interna <w:proofErr/>, <w:noProof/>, etc que quebra placeholders
            $xml = preg_replace('/(\$\{.*?)<[^>]*>(.*?\})/', '$1$2', $xml);
            $xml = preg_replace('/(\$\{.*?)<[^>]*>(.*?\})/', '$1$2', $xml);

            foreach ($this->dePara as $velha => $nova) {
                // Substituição literal da chave ${velha} por ${nova}
                $xml = str_replace('${' . $velha . '}', '${' . $nova . '}', $xml);
            }
            file_put_contents($xmlPath, $xml);
        }

        $newZip = new ZipArchive();
        if ($newZip->open($destino, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) return false;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($tempDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($iterator as $file) {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($tempDir) + 1);
            $newZip->addFile($filePath, $relativePath);
        }
        $newZip->close();
        $this->rrmdir($tempDir);
        return true;
    }

    private function rrmdir($dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir . "/" . $object)) $this->rrmdir($dir . "/" . $object);
                    else @unlink($dir . "/" . $object);
                }
            }
            @rmdir($dir);
        }
    }
}

// Lógica de Saída Web
$preparador = new PreparadorV3();
// Origem: Pasta legada no servidor
$diretorioEntrada = __DIR__ . '/../modelos_prod_LEGACY_20260317';
// Destino: Pasta de modelos a validar (conforme seu requisito de bloqueio)
$diretorioSaida = __DIR__ . '/../modelos_unificados'; 

if (php_sapi_name() === 'cli') {
    $preparador->processarDiretorio($diretorioEntrada, $diretorioSaida);
} else {
    ?>
    <!DOCTYPE html>
    <html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <title>SGT - Unificador v3.0 SaaS</title>
        <style>
            body { background: #0f172a; color: #f1f5f9; font-family: 'Inter', sans-serif; display: flex; justify-content: center; padding: 40px; }
            .card { background: rgba(30, 41, 59, 0.4); backdrop-filter: blur(16px); border: 1px solid rgba(255,255,255,0.1); border-radius: 24px; padding: 40px; width: 100%; max-width: 900px; box-shadow: 0 15px 40px rgba(0,0,0,0.6); }
            h1 { color: #38bdf8; border-bottom: 3px solid #38bdf8; padding-bottom: 12px; font-weight: 800; letter-spacing: -1px; }
            .status-ok { background: rgba(74, 222, 128, 0.1); color: #4ade80; border: 1px solid #4ade80; padding: 4px 10px; border-radius: 6px; }
            .status-error { background: rgba(248, 113, 113, 0.1); color: #f87171; border: 1px solid #f87171; padding: 4px 10px; border-radius: 6px; }
            table { width: 100%; border-collapse: separate; border-spacing: 0 10px; margin-top: 25px; }
            th { text-align: left; padding: 15px; color: #94a3b8; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 1px; }
            td { background: rgba(15, 23, 42, 0.3); padding: 15px; border-top: 1px solid rgba(255,255,255,0.05); border-bottom: 1px solid rgba(255,255,255,0.05); }
            td:first-child { border-left: 1px solid rgba(255,255,255,0.05); border-radius: 12px 0 0 12px; font-weight: 600; }
            td:last-child { border-right: 1px solid rgba(255,255,255,0.05); border-radius: 0 12px 12px 0; text-align: right; }
            .btn { background: #38bdf8; color: #0b0f19; padding: 14px 28px; border-radius: 12px; text-decoration: none; font-weight: 800; display: inline-block; transition: 0.3s; box-shadow: 0 4px 15px rgba(56, 189, 248, 0.3); }
            .btn:hover { background: #7dd3fc; transform: translateY(-2px); }
        </style>
    </head>
    <body>
        <div class="card">
            <h1>Kit de Unificação SGT v3.0</h1>
            <p style="color:#94a3b8;">Limpando sujeiras de XML e normatizando chaves Legado ➔ SaaS/Web</p>
            
            <?php if (isset($_GET['run'])): 
                $res = $preparador->processarDiretorio($diretorioEntrada, $diretorioSaida);
            ?>
                <table>
                    <thead><tr><th>Modelo</th><th>Processamento</th></tr></thead>
                    <tbody>
                        <?php foreach ($res as $r): ?>
                        <tr>
                            <td>📄 <?php echo $r['arquivo']; ?></td>
                            <td><span class="<?php echo $r['sucesso'] ? 'status-ok' : 'status-error'; ?>">
                                <?php echo $r['sucesso'] ? 'Normatizado v3.0' : 'Falha Crítica'; ?>
                            </span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p style="margin-top:30px; text-align:center;">
                    🏆 <strong>PRÓXIMO PASSO:</strong> Acesse o <a href="validador_v3.php" style="color:#38bdf8;">Validador de Modelos</a> para auditar a pasta <code>/modelos_unificados</code>.
                </p>
            <?php else: ?>
                <div style="text-align:center; padding: 60px 0;">
                    <p style="margin-bottom:30px;">Esta operação irá processar os 18 modelos da pasta <code>modelos_prod_LEGACY_20260317</code> e gerá-los na pasta <code>modelos_unificados</code>.</p>
                    <a href="?run=1" class="btn">INICIAR NORMATIZAÇÃO NO SERVIDOR WEB</a>
                </div>
            <?php endif; ?>
        </div>
    </body>
    </html>
    <?php
}
