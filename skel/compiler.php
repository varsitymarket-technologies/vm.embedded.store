<?php 
#   TITLE   : Application Compiler   
#   DESC    : The Interface handling the Application GUI as well the micro-services 
#   PROPRIETOR: VARSITYMARKET_TECHNOLOGIES
#   VERSION : 1.0.1.1
#   AUTHOR  : HARDY HASTINGS  
#   RELEASE : 2026/07/12

@include_once __DIR__."/style.kit"; #GUI of the Website Not including The HTML
@include_once __DIR__."/script.kit"; #The Script That The Website Will execute 
@include_once __DIR__."/api.kit";    # How The System communicates with the API
@include_once __DIR__."/structure.kit"; #The processing Structure For The Base Applications

class compiler{
    public $html; 
    public function __construct($html){
        $this->html = $html;        
    }
    public function run() {
        // Pattern captures everything between '<!-- #!/engine/node/' and '-->'
        $pattern = '/<!--\s*#!\/engine\/node\/\s*(.*?)\s*-->/s';

        $result = preg_replace_callback($pattern, function ($matches) {
            // Clean up extra whitespace/newlines inside the PHP code
            $code = trim(preg_replace('/\s+/', ' ', $matches[1]));
            return "<?php {$code} ?>";
        }, $this->html);
        
        $tmp_file = dirname(__FILE__)."/exec.".hash("sha256","executable").".tmp.php"; 

        file_put_contents($tmp_file, $result); 
        include_once $tmp_file; 
        @unlink($tmp_file); 
    }
}
?>