<style>
    .fade-box {
        position: absolute;
        z-index: 99999999;
        opacity: 1;
        /* Initially hidden */
        animation: fadeInOut 1s forwards;
        /* Apply the animation */
        animation-delay: calc(0s + var(--delay, 0s));
        /* Optional delay using CSS variables */
    }

    @keyframes fadeInOut {
        0% {
            opacity: 1;
        }

        50% {
            opacity: 1;
        }

        /* Fade in to full opacity at 50% (halfway) */
        100% {
            opacity: 0;
            display: none;
        }

        /* Fade out back to transparent */
    }

    /* Example of applying a delay to a specific element */
    #delayed-box {
        --delay: 0.2s;
        /* 2-second delay for this element */
    }

    .prevent-container-select {
        -webkit-user-select: none;
        /* Safari */
        -ms-user-select: none;
        /* IE 10 and IE 11 */
        user-select: none;
        /* Standard syntax */
    }
</style>


<!-- Cover Branding Letter -->
<div class="fade-box prevent-container-select" id="delayed-box">
    <div style="display: block; position: fixed; z-index: 999999999; left: 0px; top: 0px; width: 100%; height: 100%; overflow: auto; background-color: #0a0a0ad3;">
        <div class="min-w-0 p-4 rounded-lg shadow-xs" style="width: 90%; max-width:400px; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);">
            <div style="margin:1.5rem">
                <div class="animate__animated animate__zoomIn mb-4 mb-4 text-lg font-semibold text-gray-600 dark:text-gray-300">
                    <img draggable="false" style="max-width: 13rem; width: 100%; margin: auto;" src="/assets/favicon.png" alt="Logo">
                    <h2 style="    font-size: 10px;
    text-align: center;
    font-weight: 400; color:#b7b1c1; " class="my-6 text-sm font-semibold text-gray-700 dark:text-gray-200 mb-4">
                        Powered By VARSITYMARKET<br>
                    </h2>
                </div>
            </div>
        </div>
    </div>
</div>


<script>
        function operate_loader(status = "spin") {
        var lazy_loader = window.document.getElementById('lazy_system_loader');
        if (status == "spin") {
            //window.document.getElementById('lazy_system_loader').style.display = "block"; 
            lazy_loader.style.display = "block";
        } else {
            lazy_loader.style.display = "none";
        }
    }

    document.addEventListener("DOMContentLoaded", function() {
        operate_loader();
    });


    window.addEventListener("load", function() {
        operate_loader('stop');
    });
</script>