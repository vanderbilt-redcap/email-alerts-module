<?php
namespace Vanderbilt\EmailTriggerExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;
require_once 'EmailTriggerExternalModule.php';

?>

<script>
    $(function(){
        $('#AddNewForm').submit(function () {
            var data = $('#AddNewForm').serialize();
            var editor_text = tinymce.activeEditor.getContent();
            data += "&email-text-editor="+encodeURIComponent(editor_text);
            data += "&email-to="+$('#email-to').val();
            data += "&email-cc="+$('#email-cc').val();

            var files = {};
            $('#AddNewForm').find('input, select, textarea').each(function(index, element){
               var element = $(element);
               var name = element.attr('name');
               var type = element[0].type;

               if (type == 'file') {
                   // only store one file per variable - the first file
                   jQuery.each(element[0].files, function(i, file) {
                       if (typeof files[name] == "undefined") {
                           files[name] = file;
                       }
                   });
               }
            });

            if(checkRequiredFieldsAndLoadOption('','')){
                saveFilesIfTheyExist('<?=$module->getUrl('save-file.php')?>', files);
                ajaxLoadOptionAndMessage(data,'<?=$module->getUrl('saveForm.php')?>',"A");
            }
			return false;
        });
    });
</script>
<!-- Modal -->
<form class="form-horizontal" action="" method="post"id='AddNewForm'>
    <div class="modal fade" id="external-modules-configure-modal" tabindex="-1" role="dialog" aria-labelledby="Codes">
        <div class="modal-dialog" role="document" style="width: 800px">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close closeCustomModal" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="myModalLabel">Configure Email Alerts</h4>
                </div>
                <div class="modal-body">
                    <div id='errMsgContainerModal' class="alert alert-danger col-md-12" role="alert" style="display:none;margin-bottom:20px;"></div>
                    <table class="code_modal_table" id="code_modal_table"></table>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default" id='btnCloseCodesModal' data-dismiss="modal">Cancel</button>
                    <button type="submit" form="AddNewForm" class="btn btn-default saveFormButton" id='btnModalAddForm'>Save</button>
                </div>
            </div>
        </div>
    </div>
</form>
