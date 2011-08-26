jQuery(document).ready(function($) {
    var i=1;
    $('.customEditor textarea').each(function(e) {
        var id = $(this).attr('id');
        
        if (!id) {
            id = 'customEditor-' + i++;
            $(this).attr('id',id);
        }
        
        tinyMCE.execCommand('mceAddControl', false, id);
    });
});