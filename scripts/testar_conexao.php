<?php
/**
 * TESTE DE CONEXÃO v3.0 (Ambiente Web/SaaS)
 * 
 * Verifica se os scripts v3.0 conseguem:
 * 1. Carregar o db.php
 * 2. Conectar ao MySQL de Produção
 * 3. Instanciar o PropostaRepositoryV3
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../core/PropostaRepositoryV3.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== DIAGNÓSTICO DE INFRAESTRUTURA v3.0 ===\n";
echo "Ambiente: " . (defined('ENVIRONMENT') ? ENVIRONMENT : 'Não definido') . "\n";
echo "Root Path: " . __DIR__ . "\n\n";

try {
    echo "1. Tentando obter conexão via Database::getProd()...\n";
    $mysqli = Database::getProd();
    
    if ($mysqli->ping()) {
        echo "   [✓] Conexão MySQLi ativa e operando.\n";
        echo "   [i] Host: " . (defined('DB_PROD_HOST') ? DB_PROD_HOST : '???') . "\n";
        echo "   [i] Banco: " . (defined('DB_PROD_NAME') ? DB_PROD_NAME : '???') . "\n";
    } else {
        echo "   [✗] Falha no ping da conexão.\n";
    }

    echo "\n2. Testando PropostaRepositoryV3...\n";
    $repo = new PropostaRepositoryV3($mysqli);
    echo "   [✓] Repositório instanciado com sucesso.\n";

    echo "\n3. Testando Mapeamento V3...\n";
    $mapeamento = require __DIR__ . '/../config/mapeamento_v3.php';
    echo "   [✓] Mapeamento carregado (" . count($mapeamento) . " campos).\n";

    echo "\n=== RESULTADO: INFRAESTRUTURA SAUDÁVEL NO AMBIENTE WEB ===\n";

} catch (Exception $e) {
    echo "\n!!! ERRO CRÍTICO !!!\n";
    echo "Mensagem: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . " na linha " . $e->getLine() . "\n";
    exit(1);
}
