<?php
namespace Vanderbilt\EmailTriggerExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;
require_once 'EmailTriggerExternalModule.php';

?>

<script>
    $(function(){
        $('#btnModalAddForm').click(function () {
            $('#AddNewForm').submit();
        });
        $('#AddNewForm').submit(function () {
            var data = $('#AddNewForm').serialize();
            var editor_text = tinymce.activeEditor.getContent();
            data += "&email-text-editor="+encodeURIComponent(editor_text);
            data += "&email-to="+encodeURIComponent($('#email-to').val());
            data += "&email-cc="+encodeURIComponent($('#email-cc').val());
            data += "&email-bcc="+encodeURIComponent($('#email-bcc').val());

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
<form class="form-horizontal" action="" method="post" id='AddNewForm'>
    <div class="modal fade" id="external-modules-configure-modal" name="external-modules-configure-modal" data-module="<?=$_REQUEST['prefix']?>" tabindex="-1" role="dialog" aria-labelledby="Codes">
        <div class="modal-dialog" role="document" style="width: 800px">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title float-left" id="myModalLabel">Configure Email Alerts</h4>
                        <button type="button" class="close closeCustomModal float-right" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>

                <div class="modal-body">
                    <div id='errMsgContainerModal' class="alert alert-danger col-md-12" role="alert" style="display:none;margin-bottom:20px;"></div>
                    <table class="code_modal_table" id="code_modal_table"></table>
                </div>

                <div class="modal-footer">
                    <a class="btn btn-default btn-cancel" id='btnCloseCodesModal' data-dismiss="modal">Cancel</a>
                    <a class="btn btn-default save" id='btnModalAddForm'>Save</a>
                </div>
            </div>
        </div>
    </div>
</form>
