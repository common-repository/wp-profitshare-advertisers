(function($) {
    $(document).ready(function(){
        // activate tooltips
        $('[data-toggle="tooltip"]').tooltip();

        // copy link to clipboard
        $("#feedFileName").on("click", function(){
            var text = $(this).attr("value");

            var textArea = document.createElement("textarea");
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand("Copy");
            textArea.remove();

            $("#linkCopied").slideDown("fast");
            setTimeout(function(){
                $("#linkCopied").slideUp("fast");
            }, 3000);
        })
    });
}(jQuery));