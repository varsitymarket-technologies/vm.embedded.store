<?php 
#   TITLE   : Application Compiler   
#   DESC    : The Interface handling the Application GUI as well the micro-services 
#   PROPRIETOR: VARSITYMARKET_TECHNOLOGIES
#   VERSION : 1.0.1.1
#   AUTHOR  : HARDY HASTINGS  
#   RELEASE : 2026/07/12

class compiler{
    public $html; 
    public function __construct($html){
        $this->html = $html;        
    }
    
    public function ensureTempFileCleanup(string $dir = null): string {
        $tempDir = $G ?? sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'vm-demo-temp';

        if (!is_dir($tempDir) && !mkdir($tempDir, 0777, true) && !is_dir($tempDir)) {
            throw new RuntimeException('Unable to create temp directory: ' . $tempDir);
        }

        $cutoff = time() - 60;
        foreach (glob($tempDir . DIRECTORY_SEPARATOR . '*.tmp') as $file) {
            if (is_file($file) && filemtime($file) < $cutoff) {
                @unlink($file);
            }
        }

        $tempFile = $tempDir . DIRECTORY_SEPARATOR . 'vm-' . bin2hex(random_bytes(6)) . '.tmp';
        file_put_contents($tempFile, 'created at ' . date('c'));

        return $tempFile;
    }

    public function parseAndExecuteHtmlNodes(string $html): string {
    $pattern = '/<!--(.*?)-->/s';

        
    /**
     * Parses an HTML string, finds specific node comments, and executes them.
     * @param string $html The raw HTML content with comments.
     * @return string The processed HTML with executed code replacements.
     */

        return preg_replace_callback($pattern, function ($matches) {
            $commentContent = trim($matches[1]);

            if (strpos($commentContent, '#!/engine/node/') === 0) {
                $phpCode = str_replace('#!/engine/node/', '', $commentContent);
                $phpCode = trim($phpCode);

                if ($phpCode === '') {
                    return '';
                }

                if (substr($phpCode, -1) !== ';') {
                    $phpCode .= ';';
                }

                ob_start();
                try {
                    eval($phpCode);
                } catch (\Throwable $e) {
                    echo '';
                }
                return ob_get_clean();
            }

            return $matches[0];
        }, $html);
    }


    public function run() {
        $finalOutput = $this->parseAndExecuteHtmlNodes($this->html);
        $tempFile = $this->ensureTempFileCleanup();
        print($finalOutput);
        echo "\n<!-- Sytem Runing Perfect : {$tempFile} -->";
    }
}
?>