<?php
/**
 * db.php
 * Gerenciador de Conexões (Versão MySQLi Nativo)
 * 
 * Lógica de Autocura: Caso as constantes do config.php não estejam definidas (dependência circular),
 * busca os dados diretamente do .env para garantir a conexão.
 */

require_once __DIR__ . '/config.php';

// --- LOGICA DE AUTOCURA: Recuperação de Constantes ---
if (!defined('DB_PROD_HOST')) {
    $envFile = __DIR__ . '/.env';
    if (file_exists($envFile)) {
        $env = parse_ini_file($envFile);
        if ($env) {
            foreach ($env as $k => $v) {
                if (!defined($k)) define($k, $v);
            }
        }
    }
}

// Fallback de Segurança caso nada funcione (Configurações vitais)
if (!defined('ENVIRONMENT')) define('ENVIRONMENT', 'production');

// Ativa relatório de erros estrito do MySQLi
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

class Database {

    private static $connProd = null;
    private static $connDemo = null;

    /**
     * Retorna conexão MySQLi com Produção
     * @return mysqli
     */
    public static function getProd() {
        if (self::$connProd === null) {
            self::$connProd = self::connect(
                defined('DB_PROD_HOST') ? DB_PROD_HOST : '',
                defined('DB_PROD_NAME') ? DB_PROD_NAME : '',
                defined('DB_PROD_USER') ? DB_PROD_USER : '',
                defined('DB_PROD_PASS') ? DB_PROD_PASS : ''
            );
        }
        return self::$connProd;
    }

    /**
     * Retorna conexão MySQLi com Demo
     * @return mysqli
     */
    public static function getDemo() {
        if (self::$connDemo === null) {
            self::$connDemo = self::connect(
                defined('DB_DEMO_HOST') ? DB_DEMO_HOST : '',
                defined('DB_DEMO_NAME') ? DB_DEMO_NAME : '',
                defined('DB_DEMO_USER') ? DB_DEMO_USER : '',
                defined('DB_DEMO_PASS') ? DB_DEMO_PASS : ''
            );
        }
        return self::$connDemo;
    }

    private static function connect($host, $dbname, $user, $pass) {
        if (empty($host) || empty($dbname)) {
            die("Erro Fatal: Credenciais de banco de dados não configuradas.");
        }

        try {
            // Cria conexão estilo "Antigo" (MySQLi) mas de forma orientada a objetos
            $mysqli = new mysqli($host, $user, $pass, $dbname);

            // Verifica erro de conexão
            if ($mysqli->connect_error) {
                throw new Exception($mysqli->connect_error);
            }

            // Define Charset
            $mysqli->set_charset("utf8mb4");
            return $mysqli;

        } catch (Exception $e) {
            if (ENVIRONMENT === 'development') {
                die("Erro de Conexão MySQLi ({$dbname}): " . $e->getMessage());
            } else {
                error_log("DB_ERROR: " . $e->getMessage());
                die("Sistema indisponível.");
            }
        }
    }
}

// ==========================================================
// CAMADA DE COMPATIBILIDADE (GLOBAL VARIABLE)
// ==========================================================
// Cria a variável $conn que o sistema legado espera.

// Detecção de ambiente via sessão (com supressão de erro para prevenir interrupção)
$is_demo_legacy = (isset($_SESSION['ambiente']) && $_SESSION['ambiente'] === 'demo');

try {
    if ($is_demo_legacy) {
        $conn = Database::getDemo();
    } else {
        $conn = Database::getProd();
    }
} catch (Exception $e) {
    if (ENVIRONMENT === 'development') {
        echo "Erro fatal no DB: " . $e->getMessage();
    } else {
        die("Erro de infraestrutura.");
    }
}
?>