<?php
/**
 * TEMPLATE ENGINE v3.0 - SGT PROPOSTAS
 * 
 * Regras:
 * 1. Uma chave, um padrão, sem exceções
 * 2. Falha explícita em desvio
 * 3. Mapeamento v3.0 → banco legado (transição suave)
 */

class TemplateEngineV3 {
    
    private $mapeamento;
    private $chavesPermitidas = [];
    private $erros = [];
    
    public function __construct() {
        $this->mapeamento = require __DIR__ . '/../config/mapeamento_v3.php';
        $this->chavesPermitidas = array_keys($this->mapeamento);
    }
    
    /**
     * Renderiza template DOCX substituindo chaves v3.0 por valores
     * 
     * @param string $caminhoDocx Caminho para arquivo .docx
     * @param array $dadosProposta Dados da proposta (chaves v3.0)
     * @return string Caminho do DOCX renderizado
     * @throws Exception Se chave inválida ou dado ausente
     */
    public function renderizar($caminhoDocx, array $dadosProposta) {
        
        // 1. Validar estrutura do DOCX
        $this->validarTemplate($caminhoDocx);
        
        // 2. Extrair e validar chaves do template
        $chavesTemplate = $this->extrairChavesDocx($caminhoDocx);
        $this->validarChavesContraPadrao($chavesTemplate);
        
        // 3. Validar dados fornecidos
        $this->validarDadosObrigatorios($dadosProposta);
        
        // 4. Preparar dados para substituição
        $dadosSubstituicao = $this->prepararDadosSubstituicao($dadosProposta);
        
        // 5. Executar substituição no DOCX
        $docxRenderizado = $this->executarSubstituicao($caminhoDocx, $dadosSubstituicao);
        
        return $docxRenderizado;
    }
    
    /**
     * Valida se template segue padrão v3.0 estritamente
     */
    private function validarChavesContraPadrao(array $chavesEncontradas) {
        $chavesInvalidas = [];
        
        foreach ($chavesEncontradas as $chave) {
            if (!in_array($chave, $this->chavesPermitidas)) {
                $chavesInvalidas[] = $chave;
            }
        }
        
        if (!empty($chavesInvalidas)) {
            $listaInvalidas = implode(', ', array_map(function($c) {
                return '${' . $c . '}';
            }, $chavesInvalidas));
            
            throw new Exception(
                "ERRO DE NORMATIZAÇÃO v3.0\n" .
                "========================================\n" .
                "Chaves inválidas detectadas no template:\n" .
                $listaInvalidas . "\n\n" .
                "AÇÃO REQUERIDA:\n" .
                "1. Abra o DOCX no editor\n" .
                "2. Substitua as chaves acima pelas chaves oficiais v3.0\n" .
                "3. Chaves permitidas: " . implode(', ', array_map(function($c) {
                    return '${' . $c . '}';
                }, $this->chavesPermitidas)) . "\n" .
                "========================================\n" .
                "Estado: REPROVADO. Edição humana obrigatória."
            );
        }
    }
    
    /**
     * Valida dados obrigatórios conforme mapeamento
     */
    private function validarDadosObrigatorios(array $dados) {
        $camposObrigatorios = array_filter($this->mapeamento, function($cfg) {
            return $cfg['obrigatorio'] === true && $cfg['banco'] !== null;
        });
        
        $ausentes = [];
        foreach ($camposObrigatorios as $chave => $config) {
            if (!isset($dados[$chave]) || empty($dados[$chave])) {
                $ausentes[] = $chave;
            }
        }
        
        if (!empty($ausentes)) {
            throw new Exception(
                "DADOS OBRIGATÓRIOS AUSENTES\n" .
                "========================================\n" .
                "Campos: " . implode(', ', $ausentes) . "\n\n" .
                "Verifique se o formulário de cadastro está\n" .
                "preenchendo todos os campos v3.0.\n" .
                "========================================"
            );
        }
    }
    
    /**
     * Prepara dados para substituição (inclui cálculos)
     */
    private function prepararDadosSubstituicao(array $dadosProposta) {
        $substituicao = [];
        
        foreach ($this->mapeamento as $chaveV3 => $config) {
            
            // Campo calculado (ex: data_extenso)
            if ($config['tipo'] === 'calculado') {
                $substituicao[$chaveV3] = $this->calcularCampo($config['calculo'], $dadosProposta);
                continue;
            }
            
            // Campo normal
            if (isset($dadosProposta[$chaveV3])) {
                $valor = $dadosProposta[$chaveV3];
                
                // Formatação específica
                if ($config['tipo'] === 'moeda' && is_numeric($valor)) {
                    $valor = $this->formatarMoeda((float)$valor);
                }
                
                if ($config['tipo'] === 'date' && !empty($valor)) {
                    $valor = $this->formatarData($valor, $config['formato'] ?? 'd/m/Y');
                }
                
                $substituicao[$chaveV3] = $valor;
            } else {
                $substituicao[$chaveV3] = '';
            }
        }
        
        return $substituicao;
    }
    
    /**
     * Executa substituição física no DOCX
     */
    private function executarSubstituicao($caminhoDocx, array $dados) {
        $tempDir = sys_get_temp_dir() . '/sgt_render_' . uniqid();
        mkdir($tempDir, 0777, true);
        
        // Extrair DOCX
        $zip = new ZipArchive();
        $zip->open($caminhoDocx);
        $zip->extractTo($tempDir);
        $zip->close();
        
        // Ler document.xml
        $xmlPath = $tempDir . '/word/document.xml';
        if (file_exists($xmlPath)) {
            $xml = file_get_contents($xmlPath);
            
            // Substituir chaves
            foreach ($dados as $chave => $valor) {
                $placeholder = '${' . $chave . '}';
                $xml = str_replace($placeholder, $this->escapeXml((string)$valor), $xml);
            }
            
            // Salvar XML modificado
            file_put_contents($xmlPath, $xml);
        }
        
        // Reempacotar DOCX
        $caminhoSaida = dirname($caminhoDocx) . '/renderizado_' . basename($caminhoDocx);
        $this->reempacotarDocx($tempDir, $caminhoSaida);
        
        // Limpar temp
        $this->rrmdir($tempDir);
        
        return $caminhoSaida;
    }
    
    /**
     * Extrai chaves ${xxx} de um DOCX
     */
    private function extrairChavesDocx($caminhoDocx) {
        $zip = new ZipArchive();
        $zip->open($caminhoDocx);
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        
        if (!$xml) return [];
        
        preg_match_all('/\$\{(\w+)\}/', $xml, $matches);
        return array_unique($matches[1]);
    }
    
    /**
     * Cálculos automáticos
     */
    private function calcularCampo($tipoCalculo, $dados) {
        switch ($tipoCalculo) {
            case 'dataExtenso':
                $data = $dados['data_emissao'] ?? date('Y-m-d');
                return $this->dataPorExtenso($data);
            
            default:
                return '';
        }
    }
    
    private function dataPorExtenso($data) {
        $meses = [
            'janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho',
            'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'
        ];
        
        $timestamp = is_numeric($data) ? $data : strtotime((string)$data);
        if (!$timestamp) $timestamp = time();
        
        $dia = date('j', $timestamp);
        $mes = $meses[date('n', $timestamp) - 1];
        $ano = date('Y', $timestamp);
        
        return "{$dia} de {$mes} de {$ano}";
    }
    
    private function formatarMoeda(float $valor) {
        return 'R$ ' . number_format($valor, 2, ',', '.');
    }
    
    private function formatarData($data, $formato) {
        $timestamp = is_numeric($data) ? $data : strtotime((string)$data);
        if (!$timestamp) return $data;
        return date($formato, $timestamp);
    }
    
    private function escapeXml($texto) {
        return htmlspecialchars($texto, ENT_XML1, 'UTF-8');
    }
    
    private function reempacotarDocx($dir, $saida) {
        $zip = new ZipArchive();
        $zip->open($saida, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $name => $file) {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($dir) + 1);
            $zip->addFile($filePath, $relativePath);
        }
        
        $zip->close();
    }
    
    private function rrmdir($dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir . "/" . $object)) {
                        $this->rrmdir($dir . "/" . $object);
                    } else {
                        unlink($dir . "/" . $object);
                    }
                }
            }
            rmdir($dir);
        }
    }
    
    private function validarTemplate($caminho) {
        if (!file_exists($caminho)) {
            throw new Exception("Template não encontrado: {$caminho}");
        }
        
        $ext = strtolower(pathinfo($caminho, PATHINFO_EXTENSION));
        if ($ext !== 'docx') {
            throw new Exception("Template deve ser .docx, não .{$ext}");
        }
    }
}
