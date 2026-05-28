<style>
        :root {
            --bg-overlay:rgba(0, 0, 0, 0.9);
        }


        /* Modal Styles */
        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: var(--bg-overlay);
            display: none; justify-content: center; align-items: center;
        }

        .modal-overlay.active { display: flex; }

        .modal-content {
            background: var(--card-bg); padding: 2rem; border-radius: 12px;
            width: 100%; max-width: 450px; box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        /* Progress Indicator */
        .steps-indicator { display: flex; justify-content: space-between; margin: 2rem 4rem 2rem 4rem; }
        .step-dot { width: 30px; height: 30px; border-radius: 50%; background: #e5e7eb; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: bold; }
        .step-dot.active { background: var(--purple-primary); color: white; }

        /* Form Steps */
        .form-step { display: none; }
        .form-step.active { display: block; }

        .form-group { margin-bottom: 1rem; }
        label { display: block; margin-bottom: 0.5rem; font-size: 0.9rem; font-weight: 600; }
        input { width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; box-sizing: border-box; }

        /* Buttons */
        .btn-group { display: flex; justify-content: space-between; margin-top: 2rem; }
        button { padding: 0.75rem 1.5rem; border-radius: 6px; border: none; cursor: pointer; font-weight: 600; }
        .btn-next { background: var(--purple-primary); color: white; margin-left: auto; }
        .btn-prev { background: #e5e7eb; color: #374151; }
        .hidden { display: none; }

        .input-group {
            margin-bottom:1rem;
        }
</style>

<style>
            pre {
                width: 100%;
                height: max-content;
                background-color: #1f1f1f;
                outline:none;
                font-size: 15px;
            }

            code{
                color: #b0c8ed;
                outline: none;
            }

            .anchor{
                width: fit-content;
                background: #000000 !important;
                color: #14a12d !important;
            }
</style>

<div class="modal-overlay active" id="modalOverlay">
    <div class="modal-content" style="max-width: 45rem; ">
        <div>
            <p>Page Export Link</p>
            <pre>
                <code id="vs-editor" contenteditable="true">
                    <?php 
                    $domain = __WEBSITE_DOMAIN__; 
                    $target = __DOMAIN__; 
                    @include dirname(dirname(__FILE__)). "/services/export.store.link.php"; 
                    function format_to_editor($html_content){
                        $search = ["<",">","&",'"',"'"];
                        $replace = ["&#60;","&#62;","&#38;","&#34;","&#39;"];
                        $replace = ["&lt;","&gt;","&amp;","&quot;","&apas;"];

                        $search = ["<"] ;
                        $replace = ["&#60"];
                        $e = str_ireplace($search,$replace,$html_content);
                        return $e ;
                    }
                    $e = format_to_editor(export_application($target,$domain)); 
                    echo "\n".$e; 
                    ?>
                </code>
            </pre>
            <br>
            <span>This Store is only active for 30 days</span>
            <br>
            <br>
            <div style="display:flex;">
                <button onclick="copy_code()">Copy Code</button>
                <button onclick="window.location='/home/'" style="margin-left:3rem; background-color:#7a1aab; color:white; ">Back </button>
            </div>
        </div>

        <script>
            function copy_code(){
                const code_element = document.getElementById('vs-editor'); 
                const htmltocopy = code_element.innerHTML;
                navigator.clipboard.writeText(htmltocopy).then(()=> {
                    alert("Text Copied")
                }); 
            }
        </script>
    </div>
</div>

