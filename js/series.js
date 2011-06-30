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
    
    setupQuestionEvents();
});

////////////////////////////////////////////////////////////////////////////
// setupQuestionEvents()
////////////////////////////////////////////////////////////////////////////
function setupQuestionEvents() {
    jQuery(".questions .delete").click(deleteQuestion);
    jQuery(".questions .edit").click(editQuestion);
    jQuery("#question_form .save").click(editQuestion);
    
    oQuestions = jQuery(".questions").dataTable({
         "iDisplayLength": 25
    });
}

////////////////////////////////////////////////////////////////////////////
// deleteQuestion()
////////////////////////////////////////////////////////////////////////////
function deleteQuestion() {
   if(!confirm("Are you sure you want to remove this question from the session?")) 
       return false;
   
   $element = jQuery(this);
   
   var data = {
       id: $element.data("id"),
       action: 'cf_delete_question'
   }
   
   jQuery.post('http://localhost/wordpress/wp-admin/admin-ajax.php', data, function() {
       $element.parent().parent().find("td").fadeOut('slow');
   }); 
   
   return false;
}

////////////////////////////////////////////////////////////////////////////
// editQuestion()
////////////////////////////////////////////////////////////////////////////
function editQuestion() { 
    var data = {
       action: 'cf_edit_question',
       number: jQuery("#cf_number").val(),
       question: jQuery("#cf_question").val(),
       comments: jQuery("#cf_comments").val(),
       post: jQuery("#cf_post").val(),
       question_id: jQuery("#cf_question_id").val()
   };

   jQuery.post('http://localhost/wordpress/wp-admin/admin-ajax.php', data, function(results) {
      tb_remove();
      jQuery("#discussion_questions .inside").html(results);
      setupQuestionEvents();
   }); 
}