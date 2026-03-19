<?php
/**
 * PROPOSTA REPOSITORY v3.0 (MySQLi Nativo)
 * 
 * Faz a ponte entre:
 * - Formulário (chaves v3.0)
 * - Banco de dados (colunas legado)
 * 
 * 100% Compatível com db.php (Ambiente Web/SaaS Locaweb)
 */

class PropostaRepositoryV3 {
    
    private $db;
    private $mapeamento;
    
    /**
     * @param mysqli $db Conexão oficial vinda do Database::getProd()
     */
    public function __construct(mysqli $db) {
        $this->db = $db;
        $this->mapeamento = require __DIR__ . '/../config/mapeamento_v3.php';
    }
    
    /**
     * Salva proposta recebendo dados em formato v3.0
     * Mas persiste em colunas legado do banco
     */
    public function salvar(array $dadosV3) {
        
        $dadosBanco = $this->traduzirParaBanco($dadosV3);
        
        $colunas = [
            'nome_cliente_salvo', 'email_salvo', 'whatsapp_salvo', 'telefone_salvo',
            'empresa', 'cnpj', 'cidade', 'numero_proposta', 'data_emissao',
            'endereco_obra', 'cidade_obra', 'estado_obra', 'finalidade',
            'valor_final_proposta', 'valorextenso', 'mobilizacao_valor', 'restante_valor',
            'banco', 'agencia', 'conta', 'created_at'
        ];
        
        $placeholders = array_fill(0, count($colunas), '?');
        $sql = "INSERT INTO propostas (" . implode(', ', $colunas) . ") VALUES (" . implode(', ', $placeholders) . ")";
        
        $stmt = $this->db->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Erro no prepare do MySQLi: " . $this->db->error);
        }
        
        // Preparar valores para o bind_param
        $vals = [];
        $tipos = "";
        
        foreach ($colunas as $col) {
            if ($col === 'created_at') {
                $vals[] = date('Y-m-d H:i:s');
                $tipos .= "s";
                continue;
            }
            
            $valor = $dadosBanco[$col] ?? null;
            $vals[] = $valor;
            $tipos .= is_numeric($valor) ? "d" : "s";
        }
        
        // No MySQLi, bind_param precisa de referências
        $stmt->bind_param($tipos, ...$vals);
        
        if (!$stmt->execute()) {
            throw new Exception("Erro na execução do MySQLi: " . $stmt->error);
        }
        
        $insertId = $this->db->insert_id;
        $stmt->close();
        
        return $insertId;
    }
    
    /**
     * Busca proposta e retorna em formato v3.0
     */
    public function buscarPorId($id) {
        $sql = "SELECT * FROM propostas WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Erro no prepare: " . $this->db->error);
        }
        
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $dadosBanco = $result->fetch_assoc();
        $stmt->close();
        
        if (!$dadosBanco) {
            return null;
        }
        
        return $this->traduzirParaV3($dadosBanco);
    }
    
    /**
     * Traduz array v3.0 → colunas banco legado
     */
    private function traduzirParaBanco(array $v3) {
        $banco = [];
        foreach ($this->mapeamento as $chaveV3 => $config) {
            if ($config['banco'] === null) continue;
            $banco[$config['banco']] = $v3[$chaveV3] ?? null;
        }
        return $banco;
    }
    
    /**
     * Traduz colunas banco legado → array v3.0
     */
    private function traduzirParaV3(array $banco) {
        $v3 = [];
        foreach ($this->mapeamento as $chaveV3 => $config) {
            if ($config['banco'] === null) {
                $v3[$chaveV3] = null;
                continue;
            }
            $v3[$chaveV3] = $banco[$config['banco']] ?? null;
        }
        
        // Processar campos calculados
        if (isset($v3['data_emissao'])) {
            $v3['data_emissao_extenso'] = $this->calcularDataExtenso($v3['data_emissao']);
        }
        
        return $v3;
    }
    
    private function calcularDataExtenso($data) {
        if (empty($data)) return "";
        $meses = ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
        $ts = strtotime($data);
        if (!$ts) return (string)$data;
        return date('j', $ts) . ' de ' . $meses[date('n', $ts) - 1] . ' de ' . date('Y', $ts);
    }
}
