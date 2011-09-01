jQuery(document).ready(function($) {
    $(".date").each(function () {
        $element = $(this);

        if (!$element.attr("readonly")) {
            $element.datepicker({
                changeMonth: true,
                changeYear: true,
                showOtherMonths: true,
                selectOtherMonths: true
            });
        }
    });

    $(".data").dataTable({
        "iDisplayLength": 25
    });
    
    var i=1;
    $('.customEditor textarea').each(function(e) {
        var id = $(this).attr('id');
        
        if (!id) {
            id = 'customEditor-' + i++;
            $(this).attr('id',id);
        }
        
        tinyMCE.execCommand('mceAddControl', false, id);
    });

    var formfield = null;
    var control = null;
    jQuery('.upload').click(function() {
        control = jQuery(this).data("control");
        jQuery('html').addClass('Image');
        formfield = jQuery('#' + control).attr('name');
        tb_show('', 'media-upload.php?type=image&TB_iframe=true');
        return false;
    });

    window.original_send_to_editor = window.send_to_editor;
    window.send_to_editor = function(html) {
        var fileurl;
        if (formfield != null) {
            fileurl = jQuery('img',html).attr('src');
            jQuery('#' + control).val(fileurl);
            tb_remove();
            jQuery('html').removeClass('Image');
            formfield = null;
            control = null;
        } else {
            window.original_send_to_editor(html);
        }
    };
});