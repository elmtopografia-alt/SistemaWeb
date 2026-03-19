<?php
/**
 * config.php
 * BLINDAGEM TOTAL - Versão SGT (Segurança e Autocura)
 * 
 * Este arquivo é projetado para nunca derrubar o site, independentemente do ambiente.
 * Caso faltem variáveis, ele assume padrões seguros para o seu XAMPP.
 */

// 1. Prevenção contra Inclusão Duplicada (Blindagem de Redefinição)
if (defined('SGT_CONFIG_LOADED')) return;
define('SGT_CONFIG_LOADED', true);

// 2. Proteção de Acesso Direto
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    exit('acesso direto negado');
}

// 3. Detecção de Ambiente Ultra-Resiliente
if (!defined('ENVIRONMENT')) {
    $remoteAddr = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
    $httpHost = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    $isLocal = ($remoteAddr == '127.0.0.1' || $remoteAddr == '::1' || $httpHost == 'localhost' || strpos($httpHost, '192.168.') === 0);
    define('ENVIRONMENT', $isLocal ? 'development' : 'production');
}

if (!defined('SISTEMA_VERSAO')) define('SISTEMA_VERSAO', '01');

// 4. Carregador de Ambiente com Silenciador (Evita 'die' em local)
if (!function_exists('loadEnv')) {
    function loadEnv($var, $default = '') {
        // Tenta pegar do sistema/shell
        $val = getenv($var);
        if ($val !== false) return $val;

        // Cache estático para o arquivo .env
        static $envCache = null;
        if ($envCache === null) {
            $envFile = __DIR__ . '/.env';
            if (@file_exists($envFile)) {
                $parsed = @parse_ini_file($envFile);
                $envCache = is_array($parsed) ? $parsed : array();
            } else {
                $envCache = array();
            }
        }

        return isset($envCache[$var]) ? $envCache[$var] : $default;
    }
}

// 5. Credenciais de Banco - Lógica de Autocura (Padrão XAMPP se .env falhar)
if (!defined('DB_PROD_HOST')) define('DB_PROD_HOST', loadEnv('DB_PROD_HOST', 'localhost'));
if (!defined('DB_PROD_NAME')) define('DB_PROD_NAME', loadEnv('DB_PROD_NAME', 'sgt_propostas'));
if (!defined('DB_PROD_USER')) define('DB_PROD_USER', loadEnv('DB_PROD_USER', 'root'));
if (!defined('DB_PROD_PASS')) define('DB_PROD_PASS', loadEnv('DB_PROD_PASS', ''));

if (!defined('DB_DEMO_HOST')) define('DB_DEMO_HOST', loadEnv('DB_DEMO_HOST', 'localhost'));
if (!defined('DB_DEMO_NAME')) define('DB_DEMO_NAME', loadEnv('DB_DEMO_NAME', 'sgt_demo'));
if (!defined('DB_DEMO_USER')) define('DB_DEMO_USER', loadEnv('DB_DEMO_USER', 'root'));
if (!defined('DB_DEMO_PASS')) define('DB_DEMO_PASS', loadEnv('DB_DEMO_PASS', ''));

// SMTP com Fallbacks Silenciosos
if (!defined('SMTP_HOST')) define('SMTP_HOST', loadEnv('SMTP_HOST', 'localhost'));
if (!defined('SMTP_USER')) define('SMTP_USER', loadEnv('SMTP_USER', 'noreply@sgt.com.br'));
if (!defined('SMTP_PASS')) define('SMTP_PASS', loadEnv('SMTP_PASS', ''));
if (!defined('SMTP_PORT')) define('SMTP_PORT', loadEnv('SMTP_PORT', '587'));
if (!defined('SMTP_FROM_EMAIL')) define('SMTP_FROM_EMAIL', loadEnv('SMTP_FROM_EMAIL', 'noreply@sgt.com.br'));
if (!defined('SMTP_FROM_NAME')) define('SMTP_FROM_NAME', 'SGT - Sistema de Gestão');
if (!defined('EMAIL_FINANCEIRO')) define('EMAIL_FINANCEIRO', 'financeiro.sgt@elmtopografia.com.br');

// 6. Cálculo Seguro de BASE_URL
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
$script = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '/';
$dir = str_replace('\\', '/', dirname($script));
$path = ($dir === '/') ? '' : $dir;
$base = rtrim($protocol . "://" . $host . $path, '/');
if (!defined('BASE_URL')) define('BASE_URL', $base);

// 7. Configurações de Fuso Horário e Erros
if (ENVIRONMENT === 'development') {
    @error_reporting(E_ALL);
    @ini_set('display_errors', '1');
} else {
    @error_reporting(0);
    @ini_set('display_errors', '0');
}

@date_default_timezone_set('America/Sao_Paulo');
@setlocale(LC_ALL, 'pt_BR', 'pt_BR.utf-8', 'pt_BR.UTF-8', 'portuguese');

// 8. SGT Tracer ( GPS de Rotas) - BLINDAGEM DE CARREGAMENTO
// O uso do @ garante que se o Tracer tiver qualquer erro de sintaxe, o site não para.
$tracerPath = __DIR__ . '/src/Security/Tracer.php';
if (@file_exists($tracerPath)) {
    @include_once $tracerPath;
    if (class_exists('SGT\Security\Tracer')) {
        // Tentativa segura de inicialização
        try {
            @\SGT\Security\Tracer::init();
        } catch (Exception $e) {
            // Ignora silenciosamente para não quebrar o site
        }
    }
}

// FIM DA BLINDAGEM - SGT Config
