<?php
namespace Vanderbilt\EmailTriggerExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

require_once ExternalModules::getProjectHeaderPath();
require_once 'EmailTriggerExternalModule.php';
require_once APP_PATH_EXTMOD . 'manager/templates/globals.php';

$config = $module->getConfig();
$prefix = $_GET['prefix'];

$pid = $_GET['pid'];
$from_default = empty($module->getProjectSetting('email-sender'))?array():$module->getProjectSetting('email-sender').',"'.$module->getProjectSetting('emailSender_var').'"';

$projectData= (array(
    'status' => 'success',
    'settings' => $module->getProjectSettings($pid)));

$simple_config = $config;
$simple_config_update = $config;

#Add DataBase values to settings
for($i=0;$i<sizeof($config['email-dashboard-settings']);$i++){
     $config['email-dashboard-settings'][$i]['value'] =  $projectData['settings'][$config['email-dashboard-settings'][$i]['key']]['value'];
}

#Add choices values to settings
foreach($config['email-dashboard-settings'] as $configKey => $configRow) {
    $config['email-dashboard-settings'][$configKey] = ExternalModules::getAdditionalFieldChoices($configRow,$_GET['pid']);
}

#Add choices values to simple settings
foreach($simple_config['email-dashboard-settings'] as $configKey => $configRow) {
    $simple_config['email-dashboard-settings'][$configKey] = ExternalModules::getAdditionalFieldChoices($configRow,$_GET['pid']);
    $simple_config_update['email-dashboard-settings'][$configKey]['key'] = $configRow['key']."-update";
}

$message="";
if(array_key_exists('message', $_REQUEST) && $_REQUEST['message'] === 'C'){
    $message='<strong>Success!</strong> The configuration has been saved.';
}else if(array_key_exists('message', $_REQUEST) && $_REQUEST['message'] === 'A'){
    $message='<strong>Success!</strong> New Email Added.';
}
else if(array_key_exists('message', $_REQUEST) && $_REQUEST['message'] === 'U'){
    $message='<strong>Success!</strong> Email Updated.';
}
else if(array_key_exists('message', $_REQUEST) && $_REQUEST['message'] === 'D'){
    $message='<strong>Success!</strong> Email Deleted.';
}
else if(array_key_exists('message', $_REQUEST) && $_REQUEST['message'] === 'T'){
    $message='<strong>Success!</strong> Email Activated.';
}
else if(array_key_exists('message', $_REQUEST) && $_REQUEST['message'] === 'E'){
    $message='<strong>Success!</strong> Email Deactivated.';
}
else if(array_key_exists('message', $_REQUEST) && $_REQUEST['message'] === 'P'){
    $message='<strong>Success!</strong> Email Duplicated.';
}
else if(array_key_exists('message', $_REQUEST) && ($_REQUEST['message'] === 'R' || $_REQUEST['message'] === 'N')){
    $message='<strong>Success!</strong> Email Re-Enabled.';
}

#get number of instances
$indexSubSet = sizeof($config['email-dashboard-settings'][0]['value']);

#User rights
$isAdmin = false;
if(\REDCap::getUserRights(USERID)[USERID]['user_rights'] == '1'){
    $isAdmin = true;
}
?>
    <link rel="stylesheet" type="text/css" href="<?=$module->getUrl('css/style.css')?>">
    <link rel="stylesheet" type="text/css" href="<?=$module->getUrl('css/jquery.flexdatalist.min.css')?>">

    <script type="text/javascript" src="<?=$module->getUrl('js/jquery.dataTables.min.js')?>"></script>
    <script type="text/javascript" src="<?=$module->getUrl('js/jquery.flexdatalist.js')?>"></script>
    <script type="text/javascript" src="<?=$module->getUrl('js/functions.js')?>"></script>

    <script type="text/javascript">
        var EMparentAux;
        var configSettings = <?=json_encode($simple_config['email-dashboard-settings'])?>;
        var configSettingsUpdate = <?=json_encode($simple_config_update['email-dashboard-settings'])?>;
        var project_id = <?=json_encode($_GET['pid'])?>;
        var isLongitudinal = <?=json_encode(\REDCap::isLongitudinal())?>;
        var from_default = <?=json_encode($from_default)?>;
        var message_letter = <?=json_encode($_REQUEST['message'])?>;

        //Dashboard info
        var datapipe_var = <?=json_encode($module->getProjectSetting('datapipe_var'))?>;
        var emailFromForm_var = <?=json_encode($module->getProjectSetting('emailFromForm_var'))?>;
        var emailSender_var = <?=json_encode($module->getProjectSetting('emailSender_var'))?>;
        var datapipeEmail_var = <?=json_encode($module->getProjectSetting('datapipeEmail_var'))?>;
        var surveyLink_var = <?=json_encode($module->getProjectSetting('surveyLink_var'))?>;

        //Url
        var pid = '<?=$pid?>';
        var _duplicateform_url = '<?=$module->getUrl('duplicateForm.php')?>';
        var _reenableform_url = '<?=$module->getUrl('reEnableForm.php')?>';
        var _preview_url = '<?=$module->getUrl('previewForm.php')?>';
        var _edoc_name_url = '<?=$module->getUrl('get-edoc-name.php')?>';
        var _longitudinal_url = '<?=$module->getUrl('getLongitudinal_forms_event_AJAX.php')?>';
        var _getProjectList_url = '<?=$module->getUrl('get-project-list.php')?>';
        var lastClick = null;
        var startPos = 0;
        var endPos = 0;

        $(function(){
            //to use rich text with the modal
            $(document).on('focusin', function(e) {
                if ($(e.target).closest(".mce-window").length) {
                    e.stopImmediatePropagation();
                }
            });

            //For Entries
            var rtable = $('#customizedAlertsPreview').DataTable({"pageLength": 50});
            //So it adds the X in the search in DataTables
            $('div.dataTables_filter input').addClass('clearable');
            function tog(v){
                return v?'addClass':'removeClass';
            }
            $(document).on('div.dataTables_filter input', '.clearable', function(){
                $(this)[tog(this.value)]('x');
            }).on('mousemove', '.x', function( e ){
                $(this)[tog(this.offsetWidth-18 < e.clientX-this.getBoundingClientRect().left)]('onX');
            }).on('touchstart click change', '.onX', function( ev ){
                var temp =  $(this).removeClass('x onX').val('');
                if(temp.length == 1){
                    rtable
                        .search( '' )
                        .columns().search( '' )
                        .draw();
                }
            });
            $('div.dataTables_filter input').on('keydown', function(e){
                if(e.keyCode == 8){
                    $('div.dataTables_filter input').val($('div.dataTables_filter input').val().slice(0, -1));
                }
            });

            //Messages on reload
            var message = <?=json_encode($message)?>;
            if(message != "") {
                $('#succMsgContainer').show();
                $("#succMsgContainer").html(message);
            }

            var customClass = "form-control-custom";
            var inputHtml = "";
            var EMparent = ExternalModules.Settings.prototype;
            var EMSettings = function(){}
            EMSettings.prototype = Object.create(ExternalModules.Settings.prototype);
            EMSettings.prototype.getSettingColumns  = function(setting,savedSettings,previousInstance) {
				previousInstance = undefined;
                if (setting.type == "checkbox") {
                    if (setting.value == "false" || setting.value == undefined) {
                        setting.value = 0;
                    }
                }

                //We customize depending on the field type
                if (setting.type == 'rich-text') {
                    //We add the Data Pipping buttons
					if(typeof ExternalModules.Settings.prototype.getColumnHtml === "undefined") {
						var inputHtml = EMparent.getSettingColumns.call(this, setting, instance, header);
					}
	                else {
						var inputHtml = EMparent.getColumnHtml(setting);
					}
                    var buttonsHtml = "";
                    if ((datapipeEmail_var != '' && datapipeEmail_var != null) || (datapipe_var != '' && datapipe_var != null) || (surveyLink_var != '' && surveyLink_var != null)) {
                        if (datapipe_var != '' && datapipe_var != null) {
                            var pipeVar = datapipe_var.split("\n");
                            for (var i = 0; i < pipeVar.length; i++) {
                                var pipeName = pipeVar[i].split(",");
                                buttonsHtml += "<a class='btn btn_datapiping btn-sm  btn_piping' onclick='insertAtCursorTinyMCE(\"" + trim(pipeName[0]) + "\",1);'>" + trim(pipeName[1]) + "</a>";
                            }
                        }

                        if (datapipeEmail_var != '' && datapipeEmail_var != null) {
                            var pipeVar = datapipeEmail_var.split("\n");
                            buttonsHtml += "<div style='padding-top:5px'></div>";
                            for (var i = 0; i < pipeVar.length; i++) {
                                var pipeName = pipeVar[i].split(",");
                                buttonsHtml += "<a class='btn btn_datapiping btn-sm btn_piping btn_color_datapipeEmail' onclick='insertAtCursorTinyMCE(\"" + trim(pipeName[0]) + "\",0);'>" + trim(pipeName[1]) + "</a>";
                            }

                        }

                        if (surveyLink_var != '' && surveyLink_var != null) {
                            var pipeVar = surveyLink_var.split("\n");
                            for (var i = 0; i < pipeVar.length; i++) {
                                var pipeName = pipeVar[i].split(",");
                                buttonsHtml += "<a class='btn btn_datapiping btn-sm btn_color_surveyLink btn_piping' onclick='insertAtCursorTinyMCE(\"" + trim(pipeName[0]) + "\",1);'>" + trim(pipeName[1]) + "</a>";
                            }
                        }

                        var buttonLegend = "<div style=''><div class='btn_legend'><div class='btn_color_square btn_color_datapipe'></div>Data variable</div>";
                        buttonLegend += "<div class='btn_legend'><div class='btn_color_square btn_color_datapipeEmail'></div>Email address</div>";
                        buttonLegend += "<div class='btn_legend'><div class='btn_color_square btn_color_surveyLink'></div>Survey link</div><div>";

                        inputHtml = inputHtml.replace("<td class='external-modules-input-td'>", "<td class='external-modules-input-td'>" + buttonLegend + "<div>" + buttonsHtml + "<div>");
                    }
                    return inputHtml;
                }else if (setting.type == 'text' && (setting.key == 'email-to' || setting.key == 'email-to-update' || setting.key == 'email-cc' || setting.key == 'email-cc-update' || setting.key == 'email-bcc' || setting.key == 'email-bcc-update')) {
                    //We add the datalist for the emails
                    inputHtml += "<tr class='" + customClass + "'><td><span class='external-modules-instance-label'></span><label>" + setting.name + ":</label></td>";
                    var datalistname = "json-datalist-" + setting.key;
                    var inputProperties = {
                        'list': datalistname,
                        'class': 'flexdatalist',
                        'multiple': 'multiple',
                        'data-min-length': '1',
                        'onFocus': 'flexalistFocus(this)',
                        'onkeyup': 'preloadEmail(this)',
                        'id': setting.key
                    };
                    inputHtml += "<td class='external-modules-input-td'>" + this.getInputElement(setting.type, setting.key, setting.value, inputProperties);
                    inputHtml += "<datalist id='" + datalistname + "'></datalist></td><td></td><tr>";
                    return inputHtml;
                }else if((setting.key == 'form-name' || setting.key == 'form-name-update') && isLongitudinal){
                    if(typeof ExternalModules.Settings.prototype.getColumnHtml === "undefined") {
                        var inputHtml = EMparent.getSettingColumns.call(this, setting, instance, header);
                    }
                    else {
                        var inputHtml = EMparent.getColumnHtml(setting);
                    }
                    inputHtml += '<tr field="form-name-event" class="form-control-custom" style="display:none"></tr>';
                    return inputHtml;
                }else {
					if(typeof ExternalModules.Settings.prototype.getColumnHtml === "undefined") {
						return EMparent.getSettingColumns.call(this, setting, instance, header);
					}
					else {
						return EMparent.getColumnHtml(setting);
					}
                }
            }

            var EMSettingsInstance = new EMSettings();
            var rowsHtml = '';
            var rowsHtmlUpdate = '';
            configSettings.forEach(function(setting){
                var setting = $.extend({}, setting);

                if(setting.type == 'form-list' && setting.key == 'form-name') {
                    rowsHtml += "<tr class='form-control-custom'><td colspan='4'><div class='form-control-custom-title'>Email Triggers</div></td></tr>";
                    rowsHtmlUpdate += "<tr class='form-control-custom'><td colspan='4'><div class='form-control-custom-title'>Email Triggers</div></td></tr>";
                }else if(setting.type == 'text' && setting.key == 'email-from') {
                    rowsHtml += "<tr class='form-control-custom'><td colspan='4'><div class='form-control-custom-title'>Email Content</div></td></tr>";
                    rowsHtmlUpdate += "<tr class='form-control-custom'><td colspan='4'><div class='form-control-custom-title'>Email Content</div></td></tr>";
                }else if(setting.type == 'text' && setting.key == 'email-attachment-variable') {
                    rowsHtml += "<tr class='form-control-custom'><td colspan='4'><div class='form-control-custom-title'>Email Attachments</div></td></tr>";
                    rowsHtmlUpdate += "<tr class='form-control-custom'><td colspan='4'><div class='form-control-custom-title'>Email Attachments</div></td></tr>";
                }

				if(typeof ExternalModules.Settings.prototype.getColumnHtml === "undefined") {
					rowsHtml += EMSettingsInstance.getProjectSettingHTML(setting,false, <?=json_encode($indexSubSet)?>,'', customClass);

					//We change names for the second modal elements so the rich text works
					setting.key = setting.key+'-update';
					rowsHtmlUpdate += EMSettingsInstance.getProjectSettingHTML(setting,false, <?=json_encode($indexSubSet)?>,'', customClass);
				}
				else {
					rowsHtml += EMSettingsInstance.getSettingColumns(setting);

					//We change names for the second modal elements so the rich text works
					setting.key = setting.key+'-update';
					rowsHtmlUpdate += EMSettingsInstance.getSettingColumns(setting);
				}
            });
            EMparentAux = EMparent;
            EMparent.getPrefix = function() {
                var prefix = <?=json_encode($_REQUEST['prefix'])?>;
                return prefix;
            };
            //we call the doBranching but do nothing to avoid getting errors as Email alerts goes differently
            EMparent.doBranching = function(){}

            //We add the HTML code to the respective modal windows
            $('#code_modal_table').html(rowsHtml);
            $('#code_modal_table_update').html(rowsHtmlUpdate);

			$('#code_modal_table tr').addClass(customClass);
			$('#code_modal_table_update tr').addClass(customClass);

            //Show Add New Email modal
            $('#btnViewCodes').on('click', function(e){
				// configureSettings now expects this variable to be defined because it's filled by 2 ajax calls
				// So if calling configureSettings without going through getSettingsRows, muct manually define
                ExternalModules.Settings.projectList = [];
                EMparent.configureSettings(configSettings, configSettings);

                for(var i=0; i<tinymce.editors.length; i++){
                    var editor = tinymce.editors[i];
                    editor.on('focus', function(e) {
                        lastClick = null;
                    })
                }

                $('[name="email-attachment-variable"]').attr('placeholder','[variable1], [variable2], ...');
                $('[name="email-from"]').attr('placeholder','myemail@server.com, "Sender name"');
                $('[name="email-from"]').val(from_default);

                //Clean up values
                $('#email-to').val("");
                $('#email-cc').val("");
                $('#email-bcc').val("");

                $('#external-modules-configure-modal').modal('show');
                e.preventDefault();

            });

            $('#mainForm').submit(function () {
                $('#errMsgContainer').hide();
                $('#succMsgContainer').hide();

                var errMsg = [];
                if ($('#datapipe_var').val() != "" && $('#datapipe_var').val() != "0") {
                    var pipeVar = $('#datapipe_var').val().split("\n");
                    for (var i = 0; i < pipeVar.length; i++) {
                        var pipeName = pipeVar[i].split(",");
                        if(trim(pipeName[0]).substring(0, 1) != "[" || trim(pipeName[0]).substring(trim(pipeName[0]).length-1, trim(pipeName[0]).length) != "]"){
                            errMsg.push('<strong>Data Piping field</strong> must be follow the format: <i>[variable_name],label</i> .');
                        }
                    }
                }


                if ($('#datapipeEmail_var').val() != "" && $('#datapipeEmail_var').val() != "0") {
                    var pipeVar = $('#datapipeEmail_var').val().split("\n");
                    for (var i = 0; i < pipeVar.length; i++) {
                        var pipeName = pipeVar[i].split(",");
                        if(trim(pipeName[0]).substring(0, 1) != "[" || trim(pipeName[0]).substring(trim(pipeName[0]).length-1, trim(pipeName[0]).length) != "]"){
                            errMsg.push('<strong>Data Piping Email field</strong> must be follow the format: <i>[variable_name],label</i> .');
                        }
                    }
                }


                if ($('#emailFromForm_var').val() != "" && $('#emailFromForm_var').val() != "0") {
                    var result = $('#emailFromForm_var').val().split(",");
                    for(var i=0;i<result.length;i++){
                        if(trim(result[i]).substring(0, 1) != "[" || trim(result[i]).substring(trim(result[i]).length-1, trim(result[i]).length) != "]"){
                            errMsg.push('<strong>Email Addresses field</strong> must be follow the format: <i>[variable_name]</i>.');
                        }
                    }
                }

                if ($('#surveyLink_var').val() != "" && $('#surveyLink_var').val() != "0") {
                    var pipeVar = $('#surveyLink_var').val().split("\n");
                    for (var i = 0; i < pipeVar.length; i++) {
                        var pipeName = pipeVar[i].split(",");
                        var matches = pipeName[0].match(/\[(.*?)\]/g);

                        if (isLongitudinal && matches && matches.length >1) {
                            if(trim(matches[1]).substring(0, 1) != "[" || trim(matches[1]).substring(trim(matches[1]).length-1, trim(matches[1]).length) != "]" || trim(matches[1]).substring(trim(result[i]).length-14, trim(matches[1]).length) != "[__SURVEYLINK_" || trim(matches[0]).substring(0, 1) != "[" || trim(matches[0]).substring(trim(matches[0]).length-1, trim(matches[0]).length) != "]"){
                                errMsg.push('<strong>Longitudinal Survey Link field</strong> must be follow the format: <i>[event_name][__SURVEYLINK_variable_name],label</i> .');
                            }
                        }
                        else if(trim(pipeName[0]).substring(0, 1) != "[" || trim(pipeName[0]).substring(trim(pipeName[0]).length-1, trim(pipeName[0]).length) != "]" || trim(pipeName[0]).substring(0, 14) != "[__SURVEYLINK_"){
                            errMsg.push('<strong>Survey Link field</strong> must be follow the format: <i>[__SURVEYLINK_variable_name],label</i> .');
                        }
                    }
                }

                if ($('#emailFailed_var').val() != "" && $('#emailFailed_var').val() != "0") {
                    var result = $('#emailFailed_var').val().split(/[;,]+/);
                    for(var i=0;i<result.length;i++){
                        if(!validateEmail(trim(result[i]))){
                            errMsg.push('<strong>Email '+result[i]+'</strong> is not a valid email.');
                            break;
                        }
                    }
                }

                $('#errMsgContainer').empty();
                if (errMsg.length > 0) {
                    $.each(errMsg, function (i, e) {
                        $('#errMsgContainer').append('<div>' + e + '</div>');
                    });
                    checkIfSurveyIsSaveAndReturn("surveyLink_var="+$('#surveyLink_var').val()+'&project_id='+project_id,'<?=$module->getUrl('check_survey_save_return_AJAX.php')?>','');
                    $('#errMsgContainer').show();
                    $('html,body').scrollTop(0);
                    return false;
                }else{
                    if ($('#surveyLink_var').val() != "" && $('#surveyLink_var').val() != "0") {
                        checkIfSurveyIsSaveAndReturn("surveyLink_var="+$('#surveyLink_var').val()+'&project_id='+project_id,'<?=$module->getUrl('check_survey_save_return_AJAX.php')?>','<?=$module->getUrl('configureAJAX.php')?>');
                    }else{
                        var data = $('#mainForm').serialize();
                        ajaxLoadOptionAndMessage(data, '<?=$module->getUrl('configureAJAX.php')?>', "C");
                    }
                }
                return false;
            });

            $('[name=form-name],[name=form-name-update]').on('change', function(e){
                uploadLongitudinalEvent('project_id='+project_id+'&form='+$(this).val(),'[field=form-name-event]');
            });

            $('[name=survey_form_name]').on('change', function(e){
                uploadLongitudinalEvent('project_id='+project_id+'&form='+$(this).val(),'[name=survey-name-event]');
            });

            //we call first the flexalist function to create the options for the email
            $('.flexdatalist').flexdatalist({
                minLength: 1
            });

            $('#updateForm').submit(function () {
                var data = $('#updateForm').serialize();
                var editor_text = tinymce.activeEditor.getContent();
                data += "&email-text-update-editor="+encodeURIComponent(editor_text);
                data += "&email-to-update="+$('#email-to-update').val();
                data += "&email-cc-update="+$('#email-cc-update').val();
                data += "&email-bcc-update="+$('#email-bcc-update').val();

                var files = {};
                $('#updateForm').find('input, select, textarea').each(function(index, element){
                    var element = $(element);
                    var name = element.attr('name');
                    var type = element[0].type;

                    if (type == 'file') {
                        name = name.replace("-update", "");
                        // only store one file per variable - the first file
                        jQuery.each(element[0].files, function(i, file) {
                            if (typeof files[name] == "undefined") {
                                files[name] = file;
                            }
                        });
                    }
                });

                 if(checkRequiredFieldsAndLoadOption('-update','Update')){
                     var index = $('#index_modal_update').val();
                     deleteFile(index);
                     saveFilesIfTheyExist('<?=$module->getUrl('save-file.php')?>&index='+index, files);
                     ajaxLoadOptionAndMessage(data,'<?=$module->getUrl('updateForm.php')?>',"U");
                 }
				return false;
            });

            $('#deleteUserForm').submit(function () {
                var data = $('#deleteUserForm').serialize();
                ajaxLoadOptionAndMessage(data,'<?=$module->getUrl('deleteForm.php')?>',"D");
                return false;
            });

            $('#deleteForm').submit(function () {
                var data = $('#deleteForm').serialize();
                ajaxLoadOptionAndMessage(data,'<?=$module->getUrl('deleteFormAdmin.php')?>',"D");
                return false;
            });

            $('#deactivateForm').submit(function () {
                var data = $('#deactivateForm').serialize();
                ajaxLoadOptionAndMessage(data,'<?=$module->getUrl('activateDeactivateForm.php')?>',"");
                return false;
            });

            $('#AddSurveyForm').submit(function () {
                $('#errMsgModalContainer').hide();
                var errMsg = [];

                if($('#survey_form_name').val() == '') {
                    errMsg.push('Please insert a form name.');
                    $('#survey_form_name').addClass('alert');
                }else{
                    $('#survey_form_name').removeClass('alert');
                }

                if($('#survey_label').val() == ''){
                    errMsg.push('Please insert a label name.');
                    $('#survey_label').addClass('alert');
                }else{
                    $('#survey_label').removeClass('alert');
                }

                if (errMsg.length > 0) {
                    $('#errMsgModalContainer').empty();
                    $.each(errMsg, function (i, e) {
                        $('#errMsgModalContainer').append('<div>' + e + '</div>');
                    });
                    $('#errMsgModalContainer').show();
                    $('html,body').scrollTop(0);
                    return false;
                }else{
                    var form_alert = '[__SURVEYLINK_'+$('#survey_form_name').val()+'],'+$('#survey_label').val();
                    var event_arm = $('[name=form-name-event] option:selected').attr('event_name');
                    if(isLongitudinal && event_arm != "" && event_arm != undefined){
                        form_alert = '['+event_arm+']'+form_alert;
                    }

                    if($('#surveyLink_var').val() == ''){
                        $('#surveyLink_var').val(form_alert);
                    }else{
                        $('#surveyLink_var').val($('#surveyLink_var').val()+'\n'+form_alert);
                        $('#survey_form_name').val('');
                        $('#survey_label').val('');
                        $('#addLink').modal('toggle');
                    }
                }
                return false;
            });

            /***PIPING BUTTONS INTERACTION***/
            $('input[name="email-subject"], input[name="email-subject-update"], input[name="email-attachment-variable"], input[name="email-attachment-variable-update"], input[name="email-condition"], input[name="email-condition-update"]').on('focus', function(e){
                flexalistFocus(this);
            });
            //save the cursor position
            $('#email-to-flexdatalist, #email-to-update-flexdatalist, #email-cc-flexdatalist, #email-cc-update-flexdatalist, #email-bcc-flexdatalist, #email-bcc-update-flexdatalist, input[name="email-subject"], input[name="email-subject-update"], input[name="email-attachment-variable"], input[name="email-attachment-variable-update"], input[name="email-condition"], input[name="email-condition-update"]').on('keyup click', function(e){
                startPos = this.selectionStart;
                endPos = this.selectionEnd;
            });

            //To filter the data
            $.fn.dataTable.ext.search.push(
                function( settings, data, dataIndex ) {
                    var active = $('#concept_active').is(':checked');
                    var deleted = $('#deleted_alerts').is(':checked');
                    var column_active = data[7];
                    var column_deleted = data[8];

                    if(active == true && column_active == 'Y'){
                        if(deleted == true && column_deleted == 'Y'){
                            return true;
                        }else if(deleted == false && column_deleted == 'N'){
                            return true;
                        }
                    }else if(active == false){
                        if(deleted == true && column_deleted == 'Y'){
                            return true;
                        }else if(deleted == false && column_deleted == 'N'){
                            return true;
                        }
                    }

                    return false;
                }
            );

            $(document).ready(function() {
                var loadConceptsAJAX_table = $('#customizedAlertsPreview').DataTable();

                //we hide the columns that we use only as filters
                var column_active = loadConceptsAJAX_table.column(7);
                column_active.visible(false);
                var column_deleted = loadConceptsAJAX_table.column(8);
                column_deleted.visible(false);

                var table = $('#customizedAlertsPreview').DataTable();
                table.draw();

                //when any of the filters is called upon change datatable data
                $('#concept_active, #deleted_alerts').change( function() {
                    var table = $('#customizedAlertsPreview').DataTable();
                    table.draw();
                } );

                //When message reactivated reload on the Deleted status
                if(message_letter == 'R' || message_letter === 'N'){
                    $('#deleted_alerts').prop('checked',true);
                    if(message_letter === 'N'){
                        $('#concept_active').prop('checked',false);
                    }
                    var table = $('#customizedAlertsPreview').DataTable();
                    table.draw();
                }


            } );
        });

        function saveFilesIfTheyExist(url, files) {
            var lengthOfFiles = 0;
            var formData = new FormData();
            for (var name in files) {
                lengthOfFiles++;
                formData.append(name, files[name]);   // filename agnostic
            }
            if (lengthOfFiles > 0) {
                $.ajax({
                    url: url,
                    data: formData,
                    processData: false,
                    contentType: false,
                    async: false,
                    type: 'POST',
                    success: function(returnData) {
                        if (returnData.status != 'success') {
                            alert(returnData.status+" One or more of the files could not be saved."+JSON.stringify(returnData));
                        }
                    },
                    error: function(e) {
                        alert("One or more of the files could not be saved."+JSON.stringify(e));
                    }
                });
            }
        }

        function deleteFile(index) {
            $('.deletedFile').each(function() {
                $.post("<?=$module->getUrl('delete-file.php')?>?pid="+pid, { key: $(this).attr('name'), edoc: $(this).val(), index: index }, function(data) {
                    if (data.status != "success") {
                        // failure
                        alert("The file was not able to be deleted. "+JSON.stringify(data));
                    }

                });

            });
        };
    </script>
<?php
    $tr_class = 'in';
    if($indexSubSet>0) {
        //collapse columns as there is some existing info
        $tr_class = '';
    }
?>
<!-- CONFIGURATION TABLE -->
<form class="form-inline" action="" method="post" id='mainForm'>
    <div class="container-fluid wiki">
        <div class='row' style=''>
            <div class="col-md-12 page_title">Configure Email Alerts</div>
            <div id='errMsgContainer' class="alert alert-danger col-md-12" role="alert" style="display:none;margin-bottom:20px;"></div>
            <div class="alert alert-success fade in col-md-12" style="border-color: #b2dba1 !important;display: none;" id="succMsgContainer"></div>
              <div class="col-md-12">
                <table class="table table-bordered table-hover" style="margin-bottom: 0">
                    <tr class="table_header">
                        <td>Option</td>
                        <td>Field Mappings</td>
                    </tr>

                    <tr class="table_subheader panel-heading" data-toggle="collapse" data-target=".EA_collapsed">
                        <td colspan="2">
                            Email Addresses
                            <span class="sorting_icon">
                                <span class="glyphicon glyphicon-triangle-top" style="line-height: 0.7;"></span>
                                <span class="glyphicon glyphicon-triangle-bottom" style="line-height: 0.7;"></span>
                            </span>
                        </td>
                    </tr>
                    <tr class="panel-collapse collapse EA_collapsed <?=$tr_class?>" aria-expanded="true">
                        <td style="width: 15%;"><span style="padding-left: 5px;">Enable <strong>Data Piping</strong> to email addresses. </span><div class="description_config">Allows email fields from the REDCap form(s) to be piped into the TO and CC fields of email messages. </div></td>
                        <td style="width: 25%;padding: 10px 30px;">
                            <span class="table_example">Format: [variable_name], Button Name</span><br/>
                            <textarea type="text"  name="datapipeEmail_var" id="datapipeEmail_var" style="width: 100%;height: 100px;" placeholder="[variable_name], Button Name ..." value="<?=$module->getProjectSetting('datapipeEmail_var');?>"><?=$module->getProjectSetting('datapipeEmail_var');?></textarea>
                            <div class="btn_color_square btn_color_datapipeEmail"></div>Email button (blue)
                        </td>
                    </tr>
                    <tr class="panel-collapse collapse EA_collapsed <?=$tr_class?>" aria-expanded="true">
                        <td style="width: 15%;"><span style="padding-left: 5px;"><strong>Preload email addresses</strong> from existing REDCap records. </span><div class="description_config">Enables autocomplete of email addresses in the TO and CC email fields. The list of email addresses is pulled from the specified variables in already existing REDCap records. </div></td>
                        <td style="width: 25%;padding: 10px 30px;"><span class="table_example">Format: [email_var], ...</span><br/><input type="text"  name="emailFromForm_var" id="emailFromForm_var" style="width: 100%;" placeholder="[email_var], ..." value="<?=$module->getProjectSetting('emailFromForm_var');?>"></td>
                    </tr>
                    <tr class="panel-collapse collapse EA_collapsed <?=$tr_class?>" aria-expanded="true">
                        <td style="width: 15%;"><span style="padding-left: 5px;">Define <strong>Sender Email Name</strong> for email alerts</span><div class="description_config">Allows the user to set a default custom sender name for the email alerts. This only affects the sender name, not the sender email address, that will appear in the alert by default.</div></td>
                        <td style="width: 25%;padding: 10px 30px;">
                            Sender name<br/><input type="text"  name="emailSender_var" id="emailSender_var" style="width: 100%;" placeholder='Sender name' value='<?=$module->getProjectSetting('emailSender_var');?>'/>
                        </td>
                    </tr>

                    <tr class="table_subheader panel-heading" data-toggle="collapse" data-target=".EC_collapsed">
                        <td colspan="2">
                            Email Content
                            <span class="sorting_icon">
                                <span class="glyphicon glyphicon-triangle-top" style="line-height: 0.7;"></span>
                                <span class="glyphicon glyphicon-triangle-bottom" style="line-height: 0.7;"></span>
                            </span>
                        </td>
                    </tr>
                    <tr class="panel-collapse collapse EC_collapsed <?=$tr_class?>" aria-expanded="true">
                        <td style="width: 15%;"><span style="padding-left: 5px;">Enable <strong>Data Piping</strong> in email content. </span><div class="description_config">Allows data from the REDCap form(s) to be piped into the email messages. Project variables must be mapped to labels to be used in email piping. Enter one mapping per line.</div></td>
                        <td style="width: 25%;padding: 10px 30px;">
                            <span class="table_example">Format: [email_variable], Button Name</span><br/>
                            <textarea type="text"  name="datapipe_var" id="datapipe_var" style="width: 100%;height: 100px;" placeholder="[email_variable], Button Name" value="<?=$module->getProjectSetting('datapipe_var');?>"><?=$module->getProjectSetting('datapipe_var');?></textarea>
                            <div class="btn_color_square btn_color_datapipe"></div>Data variable button (gray)
                        </td>
                    </tr>
                    <tr class="panel-collapse collapse EC_collapsed <?=$tr_class?>" aria-expanded="true">
                        <td style="width: 15%;"><span style="padding-left: 5px;">Enable <strong>Survey Links</strong> in email content</span><div class="description_config">Allows REDCap survey links for any survey-enabled form to be inserted into email messages.</div></td>
                        <td style="width: 25%;padding: 10px 30px;">
                            <span class="table_example">Example: [__SURVEYLINK_form_name], name ...</span><br/>
                            <a id="addLinkBtn" onclick="javascript:$('#addLink').modal('show');" type="button" class="btn btn-sm pull-right btn_color_surveyLink open-codesModal btn_datapiping" style="margin-bottom:5px;">Add Link</a>
                            <textarea type="text"  name="surveyLink_var" id="surveyLink_var" style="width: 100%;height: 100px;" placeholder="[__SURVEYLINK_form_name], name ..." value="<?=$module->getProjectSetting('surveyLink_var');?>"><?=$module->getProjectSetting('surveyLink_var');?></textarea>
                            <div class="btn_color_square btn_color_surveyLink"></div>Survey link button (orange)
                        </td>
                    </tr>

                    <tr class="table_subheader panel-heading" data-toggle="collapse" data-target=".EE_collapsed">
                        <td colspan="2">
                            Email Errors
                            <span class="sorting_icon">
                                <span class="glyphicon glyphicon-triangle-top" style="line-height: 0.7;"></span>
                                <span class="glyphicon glyphicon-triangle-bottom" style="line-height: 0.7;"></span>
                            </span>
                        </td>
                    </tr>
                    <tr class="panel-collapse collapse EE_collapsed <?=$tr_class?>" aria-expanded="true">
                        <td style="width: 15%;"><span style="padding-left: 5px;">Send <strong>Failed Email Alerts</strong> to specified address</span></td>
                        <td style="width: 25%;padding: 10px 30px;">Email addresses<br/><input type="text"  name="emailFailed_var" id="emailFailed_var" style="width: 100%;" placeholder="myemail@server.com, myemail2@server.com,..." value="<?=$module->getProjectSetting('emailFailed_var');?>"/></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    <div>
        <button type="submit" form="mainForm" class="btn btn-info pull-right email_forms_button" id="SubmitNewConfigureBtn">Save Settings</button>
    </div>
</form>

<?PHP require('codes_modal.php');?>
<!-- ALERTS TABLE -->
<div style="padding-top:50px" class="col-md-12">
    <div>
        <a href="" id='btnViewCodes' type="button" class="btn btn-info pull-left email_forms_button_color email_forms_button open-codesModal" style="font-size:14px;color:#fff;margin-top: 0;margin-bottom: 10px;">Add New Email</a>
        <div style="float:right;margin-top: 5px;">
            <input value="" id="concept_active" checked class="auto-submit" type="checkbox" name="concept_active"> Active only
        </div>
        <?php if($isAdmin){?>
        <div style="float:right;margin-top: 5px;padding-right: 10px">
            <input value="" id="deleted_alerts" class="auto-submit" type="checkbox" name="deleted_alerts"> Deleted (user)
        </div>
        <?php } ?>
    </div>
    <div style="padding-left:15px">
        <?php  if($indexSubSet>0) { ?>
        <table class="table table-bordered table-hover email_preview_forms_table" id="customizedAlertsPreview" style="width: 100%;">
            <thead>
            <tr class="table_header">
                <th>Form</th>
                <th>REDCap logic</th>
                <th>Send emails for any Form status?</th>
                <th>Email Addresses</th>
                <th>Message</th>
                <th>Resend Emails on Form Re-save?</th>
                <th>Attachments</th>
                <th>Active</th>
                <th>Deleted</th>
                <th class="table_header_options">Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php
            $alerts = "";
            $email_repetitive_sent = json_decode($projectData['settings']['email-repetitive-sent']['value']);
            for ($index = 0; $index < $indexSubSet; $index++) {
                $email_sent = $projectData['settings']['email-sent']['value'][$index];
                $class_sent = "";
                $message_sent = "";
                if($email_sent == "1"){
                    $class_sent = "email_sent";
                    if(!empty($projectData['settings']['email-timestamp-sent']['value'][$index])){
                        $message_sent = "<span style='display:block;font-style:italic'>Most recently activated on: ".$projectData['settings']['email-timestamp-sent']['value'][$index]."</span>";
                    }else{
                        $message_sent = "<span style='display:block;font-style:italic'>Email activated</span>";
                    }

                }

                //DEACTIVATE
                if($projectData['settings']['email-deactivate']['value'][$index] == '1'){
                    //Only show when message is not deleted
                    if($projectData['settings']['email-deleted']['value'][$index] != '1'){
                        $message_sent .= "<span style='display:block;font-style:italic'>Email deactivated</span>";
                    }

                    $class_sent = "email_deactivated";
                    $deactivate = "Activate";
                    $active_col = "N";
                }else{
                    $deactivate = "Deactivate";
                    $active_col = "Y";
                }

                //DELETE
                $deleted_text = "Delete";
                if($projectData['settings']['email-deleted']['value'][$index] == '1'){
                    $deactivated_deleted_text = ($active_col == 'N')?'Email was INNACTIVE when deleted':'Email was ACTIVE when deleted';
                    $message_sent .= "<span style='font-style:italic;color:red;line-height: 2;float: left;'><strong>".$deactivated_deleted_text."</strong></span>";
                    $class_sent = "email_deleted";
                    $deleted_modal = "external-modules-configure-modal-delete-confirmation";
                    $deleted_index = "index_modal_delete";
                    $deleted_col = "Y";
                    $deleted_text = "Permanently Delete";
                    $show_button = "display:none";
                    $reactivate_button = "<div><a onclick='reactivateEmailAlert(\"".$index."\",$(\"#concept_active\").is(\":checked\"));' type='button' class='btn btn-success btn-new-email btn-new-email-deactivate'>Re-Enable</a></div>";
                }else{
                    $deleted_modal = "external-modules-configure-modal-delete-user-confirmation";
                    $deleted_index = "index_modal_delete_user";
                    $deleted_col = "N";
                    $show_button = "";
                    $reactivate_button = "";
                }

                if(!empty($email_repetitive_sent)){
                    if(array_key_exists($projectData['settings']['form-name']['value'][$index],$email_repetitive_sent)){
                        $form = $email_repetitive_sent->$projectData['settings']['form-name']['value'][$index];
                        foreach ($form as $alert =>$value){
                            if($alert == $index){
                                $message_sent .= "Records activated: ".count((array)$form->$alert)."<br/>";
                            }
                        }
                    }
                }

                $alerts .= '<tr>';
                $fileAttachments = 0;
                $attachmentVar ='';
                $attachmentFile ='';
                $alerts_from = '';
                foreach ($config['email-dashboard-settings'] as $configKey => $configRow) {

                    if ($configRow['type'] == 'file') {
                        if(!empty($configRow['value'][$index])) {
                            $fileAttachments++;

                            if (!empty($configRow['value'][$index])) {
                                $sql = "SELECT stored_name,doc_name,doc_size FROM redcap_edocs_metadata WHERE doc_id=" . $configRow['value'][$index];
                                $q = db_query($sql);

                                if ($error = db_error()) {
                                    die($sql . ': ' . $error);
                                }

                                while ($row = db_fetch_assoc($q)) {
                                    $url = "downloadFile.php?sname=".$row['stored_name']."&file=".$row['doc_name']."&NOAUTH";
                                    $attachmentFile .= '- <a href="'.$module->getUrl($url).'" target="_blank">'.$row['doc_name'].'</a><br/>';
                                }
                            }
                        }
                    } else if ($configRow['type'] == 'checkbox') {
                        $value = ($configRow['value'][$index] == 0) ? "No" : "Yes";
                        $alerts .= '<td  style="text-align: center"><span>' . $value . '</span></td>';
                    } else {
                        $value = preg_replace('/([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6})/', '<a href="mailto:$1">$1</a>', $configRow['value'][$index]);
                        if ($configRow['key'] == 'email-to') {
                            $alerts .= '<td><em>' . $configRow['name'] . '</em><span>' . str_replace (',',', ',$value) . '</span><br/>';
                        } else if ($configRow['key'] == 'email-cc' || $configRow['key'] == 'email-bcc') {
                            if(!empty($value)){
                                $alerts .= '<em>' . $configRow['name'] . '</em><span>' . str_replace (',',', ',$value) . '</span>';
                            }
                            if($configRow['key'] == 'email-bcc'){
                                $alerts .= '</td>';
                            }
                        } else if($configRow['key'] == 'form-name') {
                            $alerts .= '<td class="'.$class_sent.'"><span><i>Alert #'.$index.'</i></span><span>' . $configRow['value'][$index] . '</span>'.$message_sent.'</td>';
                        }else if($configRow['key'] == 'email-attachment-variable'){
                            $attchVar = preg_split("/[;,]+/",  $configRow['value'][$index]);
                            foreach ($attchVar as $var){
                                if(!empty($var)){
                                    $fileAttachments++;
                                    $attachmentVar .= '- '.$var.'<br/>';
                                }
                            }
                        }else if ($configRow['key'] == 'email-from'){
                            if(empty($configRow['value'][$index])){
                                $alerts_from = '<span>From: '.$from_default . '</span><br/>';
                            }else{
                                $alerts_from = '<span>From: '.$configRow['value'][$index] . '</span><br/>';
                            }
                        }else if($configRow['key'] == 'email-subject') {
                            $alerts .= '<td>'.$alerts_from.'<span>'.$configRow['value'][$index] . '</span><br/>';
                        }else if ($configRow['key'] == 'email-text'){
                            $alerts .= '<span><a onclick="previewEmailAlert('.$index.')" style="cursor:pointer" >Preview Message</a></span></td>';
                        }else{
                            $alerts .= '<td><span>'.$configRow['value'][$index] . '</span></td>';
                        }
                    }
                    $info_modal[$index][$configRow['key']] = $configRow['value'][$index];


                }
                $alerts .= "<td><span style='text-align: center;width: 200px;'><strong>" . $fileAttachments . " files</strong><br/></span>".$attachmentVar.$attachmentFile."</td>";
                $alerts .= "<td style='visibility: hidden;'>".$active_col."</td>";
                $alerts .= "<td style='visibility: hidden;'>".$deleted_col."</td>";
                $alerts .= "<td>".$reactivate_button."<div style='".$show_button."'><a id='emailRow$index' type='button' class='btn btn-info btn-new-email btn-new-email-edit'>Edit Email</a></div>";
                $alerts .= "<div style='".$show_button."'><a onclick='deactivateEmailAlert(".$index.",\"".$deactivate."\")' type='button' class='btn btn-info btn-new-email btn-new-email-deactivate' >".$deactivate."</a></div>";
                $alerts .= "<div style='".$show_button."'><a onclick='duplicateEmailAlert(\"".$index."\")' type='button' class='btn btn-success btn-new-email btn-new-email-deactivate' >Duplicate</a></div>";
                $alerts .= "<div><a onclick='deleteEmailAlert(\"".$index."\",\"".$deleted_modal."\",\"".$deleted_index."\")' type='button' class='btn btn-info btn-new-email btn-new-email-delete' >".$deleted_text."</a></div></td>";
                $alerts .= "</tr>";
                $alerts .= "<script>$('#emailRow$index').click(function() { editEmailAlert(".json_encode($info_modal[$index]).",".$index."); });</script>";
            }
            echo $alerts;
        }
        ?>
            <tbody>
        </table>
    </div>

    <div class="modal fade" id="addLink" tabindex="-1" role="dialog" aria-labelledby="Codes">
        <form class="form-horizontal" action="" method="post" id='AddSurveyForm'>
            <div class="modal-dialog" role="document" style="width: 500px">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close closeCustomModal" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title" id="myModalLabel">Add a survey link</h4>
                    </div>
                    <div class="modal-body">
                        <div id='errMsgModalContainer' class="alert alert-danger col-md-12" role="alert" style="display:none;margin-bottom:20px;"></div>
                        <div><i>Once the survey link is added, remember to click on <strong>Save Settings</strong> button to save the changes.</i></div>
                        <br/>
                        <table class="code_modal_table">
                            <tr class="form-control-custom">
                                <td>Form name:</td>
                                <td>
                                    <select class="external-modules-input-element" name="survey_form_name" id="survey_form_name">
                                        <option value=""></option>
                                        <?php
                                        foreach ($simple_config['email-dashboard-settings'][0]['choices'] as $choice){
//                                            echo '<option value="__SURVEYLINK_'.$choice['value'].'">'.$choice['name'].'</option>';
                                            echo '<option value="'.$choice['value'].'">'.$choice['name'].'</option>';
                                        }
                                        ?>
                                    </select>
                                </td>
                            </tr>
                            <tr name="survey-name-event" class="form-control-custom" style="display:none"></tr>

                            <tr class="form-control-custom">
                                <td>Label:</td>
                                <td><input type="text" name="survey_label" id="survey_label" placeholder="Name"></td>
                            </tr>
                        </table>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" id='btnCloseCodesModalDelete' data-dismiss="modal">Close</button>
                        <button type="submit" form="AddSurveyForm" class="btn btn-default btn_color_surveyLink" id='btnModalAddSurveyForm'>Add survey link</button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="col-md-12">
        <form class="form-horizontal" action="" method="post" id='updateForm'>
            <div class="modal fade" id="external-modules-configure-modal" name="external-modules-configure-modal-update" data-module="<?=$_REQUEST['prefix']?>" tabindex="-1" role="dialog" aria-labelledby="Codes">
                <div class="modal-dialog" role="document" style="width: 800px">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close closeCustomModal" data-dismiss="modal"
                                    aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            <h4 class="modal-title" id="myModalLabel">Configure Email Alerts</h4>
                        </div>
                        <div class="modal-body">
                            <div id='errMsgContainerModalUpdate' class="alert alert-danger col-md-12" role="alert"
                                 style="display:none;margin-bottom:20px;"></div>
                            <table class="code_modal_table" id="code_modal_table_update"></table>
                            <input type="hidden" value="" id="index_modal_update" name="index_modal_update">
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" id='btnCloseCodesModal' data-dismiss="modal">Cancel</button>
                            <button type="submit" form="updateForm" class="btn btn-default save" id='btnModalUpdateForm'>Save</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="modal fade" id="external-modules-configure-modal-delete-confirmation" tabindex="-1" role="dialog" aria-labelledby="Codes">
        <form class="form-horizontal" action="" method="post" id='deleteForm'>
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close closeCustomModal" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title" id="myModalLabel">Delete Email Alert</h4>
                    </div>
                    <div class="modal-body">
                        <span>Are you sure you want to delete this Email Alert?</span>
                        <br/>
                        <span style="color:red;font-weight: bold">*This will permanently delete the email.</span>
                        <input type="hidden" value="" id="index_modal_delete" name="index_modal_delete">
                        <input type="hidden" value="<?=$module->getUrl('deleteFormAdmin.php')?>" id="url_modal_delete" name="url_modal_delete">
                    </div>

                    <div class="modal-footer">
                        <button type="submit" form="deleteForm" class="btn btn-default btn-delete" id='btnModalDeleteForm'>Delete</button>
                        <button type="button" class="btn btn-default" id='btnCloseCodesModalDelete' data-dismiss="modal">Cancel</button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="modal fade" id="external-modules-configure-modal-delete-user-confirmation" tabindex="-1" role="dialog" aria-labelledby="Codes">
        <form class="form-horizontal" action="" method="post" id='deleteUserForm'>
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close closeCustomModal" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title" id="myModalLabel">Delete Email Alert</h4>
                    </div>
                    <div class="modal-body">
                        <span>Are you sure you want to delete this Email Alert?</span>
                        <input type="hidden" value="" id="index_modal_delete_user" name="index_modal_delete_user">
                        <input type="hidden" value="<?=$module->getUrl('deleteForm.php')?>" id="url_modal_delete_user" name="url_modal_delete_user">
                    </div>

                    <div class="modal-footer">
                        <button type="submit" form="deleteUserForm" class="btn btn-default btn-delete" id='btnModalDeleteForm'>Delete</button>
                        <button type="button" class="btn btn-default" id='btnCloseCodesModalDelete' data-dismiss="modal">Cancel</button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="modal fade" id="external-modules-configure-modal-deactivate-confirmation" tabindex="-1" role="dialog" aria-labelledby="Codes">
        <form class="form-horizontal" action="" method="post" id='deactivateForm'>
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close closeCustomModal" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title" id="myModalLabel">Activate/Deactivate Email Alert</h4>
                    </div>
                    <div class="modal-body">
                        <span id="index_modal_message"></span>
                        <input type="hidden" value="" id="index_modal_deactivate" name="index_modal_deactivate">
                        <input type="hidden" value="" id="index_modal_status" name="index_modal_status">
                    </div>

                    <div class="modal-footer">
                        <button type="submit" form="deactivateForm" class="btn btn-default btn-delete" id='btnModalDeactivateForm'></button>
                        <button type="button" class="btn btn-default" id='btnCloseCodesModalDelete' data-dismiss="modal">Cancel</button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="modal fade" id="external-modules-configure-modal-preview" tabindex="-1" role="dialog" aria-labelledby="Codes">
        <div class="modal-dialog" role="document" style="width: 800px">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close closeCustomModal" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="myModalLabel">Preview Email Alert</h4>
                </div>
                <div class="modal-body">
                    <div id="modal_message_preview"></div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default" id='btnCloseCodesModalDelete' data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once ExternalModules::getProjectFooterPath();
