<?php
namespace Vanderbilt\EmailTriggerExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

//require_once __DIR__ . '/../../external_modules/classes/ExternalModules.php';
require_once ExternalModules::getProjectHeaderPath();
require_once 'EmailTriggerExternalModule.php';
require_once __DIR__ . '/../../external_modules/manager/templates/globals.php';


$emailTriggerModule = new EmailTriggerExternalModule();
$config = $emailTriggerModule->getConfig();
$prefix = ExternalModules::getPrefixForID($_GET['id']);
$pid = $_GET['pid'];

$projectData= (array(
    'status' => 'success',
    'settings' => ExternalModules::getProjectSettingsAsArray($prefix, $pid)));

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

#get number of instances
$indexSubSet = sizeof($config['email-dashboard-settings'][0]['value']);

//print_array($simple_config);

?>
    <link rel="stylesheet" type="text/css" href="<?=$emailTriggerModule->getUrl('css/style.css')?>">
    <link rel="stylesheet" type="text/css" href="<?=$emailTriggerModule->getUrl('css/jquery.flexdatalist.min.css')?>">

    <script type="text/javascript" src="<?=$emailTriggerModule->getUrl('js/jquery.dataTables.min.js')?>"></script>
    <script type="text/javascript" src="<?=$emailTriggerModule->getUrl('js/jquery.flexdatalist.js')?>"></script>
    <script type="text/javascript" src="<?=$emailTriggerModule->getUrl('js/functions.js')?>"></script>

    <script type="text/javascript">
        var EMparentAux;
        var configSettings = <?=json_encode($simple_config['email-dashboard-settings'])?>;
        var configSettingsUpdate = <?=json_encode($simple_config_update['email-dashboard-settings'])?>;
        var project_id = <?=json_encode($_GET['pid'])?>;
        var isLongitudinal = <?=json_encode(\REDCap::isLongitudinal())?>;
        //Dashboard info
        var datapipe_var = <?=json_encode($emailTriggerModule->getProjectSetting('datapipe_var'))?>;
        var emailFromForm_var = <?=json_encode($emailTriggerModule->getProjectSetting('emailFromForm_var'))?>;
        var emailSender_var = <?=json_encode($emailTriggerModule->getProjectSetting('emailSender_var'))?>;
        var datapipeEmail_var = <?=json_encode($emailTriggerModule->getProjectSetting('datapipeEmail_var'))?>;
        var surveyLink_var = <?=json_encode($emailTriggerModule->getProjectSetting('surveyLink_var'))?>;
        var email_sender = <?=json_encode($emailTriggerModule->getProjectSetting('email-sender'))?>;

        //Url
        var pid = '<?=$pid?>';
        var _preview_url = '<?=$emailTriggerModule->getUrl('previewForm.php')?>';
        var _edoc_name_url = '<?=$emailTriggerModule->getUrl('get-edoc-name.php')?>';
        var _longitudinal_url = '<?=$emailTriggerModule->getUrl('getLongitudinal_forms_event_AJAX.php')?>';
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
            var rtable = $('#customizedAlertsPreview').DataTable();
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
                                buttonsHtml += "<a class='btn btn_datapiping btn-sm btn_color_surveyLink btn_piping' onclick='insertAtCursorTinyMCE(\"" + trim(pipeName[0]) + "\",0);'>" + trim(pipeName[1]) + "</a>";
                            }
                        }

                        var buttonLegend = "<div style=''><div class='btn_legend'><div class='btn_color_square btn_color_datapipe'></div>Data variable</div>";
                        buttonLegend += "<div class='btn_legend'><div class='btn_color_square btn_color_datapipeEmail'></div>Email address</div>";
                        buttonLegend += "<div class='btn_legend'><div class='btn_color_square btn_color_surveyLink'></div>Survey link</div><div>";

                        inputHtml = inputHtml.replace("<td class='external-modules-input-td'>", "<td class='external-modules-input-td'>" + buttonLegend + "<div>" + buttonsHtml + "<div>");
                    }
                    return inputHtml;
                } else if (setting.type == 'text' && (setting.key == 'email-to' || setting.key == 'email-to-update' || setting.key == 'email-cc' || setting.key == 'email-cc-update')) {
                    //We add the datalist for the emails
                    inputHtml += "<tr class='" + customClass + "'><td><span class='external-modules-instance-label'></span><label>" + setting.name + ":</label></td>";
                    var datalistname = "json-datalist-" + setting.key;
                    var inputProperties = {
                        'list': datalistname,
                        'class': 'flexdatalist',
                        'multiple': 'multiple',
                        'data-min-length': '1',
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
                }
                else {
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
                }else if(setting.type == 'text' && setting.key == 'email-to') {
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

                $('[name="email-attachment-variable"]').attr('placeholder','[variable1], [variable2], ...')

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
                        if(!(trim(pipeName[0]).startsWith("[")) || !(trim(pipeName[0]).endsWith("]"))){
                            errMsg.push('<strong>Data Piping field</strong> must be follow the format: <i>[variable_name],label</i> .');
                        }
                    }
                }


                if ($('#datapipeEmail_var').val() != "" && $('#datapipeEmail_var').val() != "0") {
                    var pipeVar = $('#datapipeEmail_var').val().split("\n");
                    for (var i = 0; i < pipeVar.length; i++) {
                        var pipeName = pipeVar[i].split(",");
                        if(!(trim(pipeName[0]).startsWith("[")) || !(trim(pipeName[0]).endsWith("]"))){
                            errMsg.push('<strong>Data Piping Email field</strong> must be follow the format: <i>[variable_name],label</i> .');
                        }
                    }
                }


                if ($('#emailFromForm_var').val() != "" && $('#emailFromForm_var').val() != "0") {
                    var result = $('#emailFromForm_var').val().split(",");
                    for(var i=0;i<result.length;i++){
                        if(!(trim(result[i]).startsWith("[")) || !(trim(result[i]).endsWith("]"))){
                            errMsg.push('<strong>Email Addresses field</strong> must be follow the format: <i>[variable_name]</i>.');
                        }
                    }
                }

                if ($('#surveyLink_var').val() != "" && $('#surveyLink_var').val() != "0") {
                    var pipeVar = $('#surveyLink_var').val().split("\n");
                    for (var i = 0; i < pipeVar.length; i++) {
                        var pipeName = pipeVar[i].split(",");
                        if(!(trim(pipeName[0]).startsWith("[")) || !(trim(pipeName[0]).endsWith("]")) || !(trim(pipeName[0]).startsWith("[SURVEYLINK_"))){
                            errMsg.push('<strong>Survey Link field</strong> must be follow the format: <i>[SURVEYLINK_variable_name],label</i> .');
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

                if ($('#emailSender_var').val() == "" && $('#emailSender_var').val() == "0") {
                    errMsg.push('<strong>Sender Email</strong> is a required field.');
                }

                if (email_sender == "" || email_sender == null) {
                    var control_center = <?=json_encode(APP_PATH_WEBROOT_FULL . "external_modules/manager/control_center.php")?>;
                    errMsg.push('<strong>Email Sender </strong> is empty. Contact your REDCap administrator to update the module settings in the Control Center.');
                }else if(!validateEmail(email_sender)){
                    var control_center = <?=json_encode(APP_PATH_WEBROOT_FULL . "external_modules/manager/control_center.php")?>;
                    errMsg.push('<strong>Email '+email_sender+'</strong> is not a valid Sender email. Contact your REDCap administrator to update the module settings in the Control Center.');
                }

                $('#errMsgContainer').empty();
                if (errMsg.length > 0) {
                    $.each(errMsg, function (i, e) {
                        $('#errMsgContainer').append('<div>' + e + '</div>');
                    });
                    checkIfSurveyIsSaveAndReturn("surveyLink_var="+$('#surveyLink_var').val()+'&project_id='+project_id,'<?=$emailTriggerModule->getUrl('check_survey_save_return_AJAX.php')?>','');
                    $('#errMsgContainer').show();
                    $('html,body').scrollTop(0);
                    return false;
                }else{
                    if ($('#surveyLink_var').val() != "" && $('#surveyLink_var').val() != "0") {
                        checkIfSurveyIsSaveAndReturn("surveyLink_var="+$('#surveyLink_var').val()+'&project_id='+project_id,'<?=$emailTriggerModule->getUrl('check_survey_save_return_AJAX.php')?>','<?=$emailTriggerModule->getUrl('configureAJAX.php')?>');
                    }else{
                        var data = $('#mainForm').serialize();
                        ajaxLoadOptionAndMessage(data, '<?=$emailTriggerModule->getUrl('configureAJAX.php')?>', "C");
                    }
                }
                return false;
            });

            $('[name=form-name],[name=form-name-update]').on('change', function(e){
                uploadLongitudinalEvent('project_id='+project_id+'&form='+$(this).val());
            });
            //we call first the flexalist function to create the options for the email
            $('.flexdatalist').flexdatalist({
                minLength: 1
            });
            //we call each time a letter is typed to search in the DB for the options and load them
            $('#email-to-flexdatalist, #email-to-update-flexdatalist, #email-cc-flexdatalist, #email-cc-update-flexdatalist').on('keyup', function(e){
                if(emailFromForm_var != ''){
                    var cutword = "-flexdatalist";
                    var id = $(this).attr('id').substr(0, $(this).attr('id').length-cutword.length);
                    var value = $(this).val();
                    loadAjax('parameters='+value+'&project_id='+project_id+'&variables='+emailFromForm_var, '<?=$emailTriggerModule->getUrl('get-project-list.php')?>', id);
                }
            });

            $('#updateForm').submit(function () {
                var data = $('#updateForm').serialize();
                var editor_text = tinymce.activeEditor.getContent();
                data += "&email-text-update-editor="+encodeURIComponent(editor_text);

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
                     saveFilesIfTheyExist('<?=$emailTriggerModule->getUrl('save-file.php')?>&index='+index, files);
                     ajaxLoadOptionAndMessage(data,'<?=$emailTriggerModule->getUrl('updateForm.php')?>',"U");
                 }
				return false;
            });

            $('#deleteForm').submit(function () {
                var data = $('#deleteForm').serialize();
                ajaxLoadOptionAndMessage(data,'<?=$emailTriggerModule->getUrl('deleteForm.php')?>',"D");
                return false;
            });

            $('#deactivateForm').submit(function () {
                var data = $('#deactivateForm').serialize();
                ajaxLoadOptionAndMessage(data,'<?=$emailTriggerModule->getUrl('activateDeactivateForm.php')?>',"");
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
                    var form_alert = '['+$('#survey_form_name').val()+'],'+$('#survey_label').val();
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

            /***PIPPING BUTTONS INTERACTION***/
            //Saves the field id/name in which field we are
            $('#email-to-flexdatalist, #email-to-update-flexdatalist, #email-cc-flexdatalist, #email-cc-update-flexdatalist, input[name="email-subject"], input[name="email-subject-update"], input[name="email-attachment-variable"], input[name="email-attachment-variable-update"], input[name="email-condition"], input[name="email-condition-update"]').on('focus', function(e){
                var id = $(this).attr("id");
                if(id == undefined){
                    var name = '[name="'+$(this).attr("name")+'"]';
                    var id = $(this).attr("name");
                }else{
                    var name = '#'+$(this).attr("id");
                }
                lastClick = name;
            });

            //save the cursor position
            $('#email-to-flexdatalist, #email-to-update-flexdatalist, #email-cc-flexdatalist, #email-cc-update-flexdatalist, input[name="email-subject"], input[name="email-subject-update"], input[name="email-attachment-variable"], input[name="email-attachment-variable-update"], input[name="email-condition"], input[name="email-condition-update"]').on('keyup click', function(e){
                startPos = this.selectionStart;
                endPos = this.selectionEnd;
            });
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
                $.post("<?=$emailTriggerModule->getUrl('delete-file.php')?>?pid="+pid, { key: $(this).attr('name'), edoc: $(this).val(), index: index }, function(data) {
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
                            <textarea type="text"  name="datapipeEmail_var" id="datapipeEmail_var" style="width: 100%;height: 100px;" placeholder="[dob], Fake email ..." value="<?=$emailTriggerModule->getProjectSetting('datapipeEmail_var');?>"><?=$emailTriggerModule->getProjectSetting('datapipeEmail_var');?></textarea>
                            <div class="btn_color_square btn_color_datapipeEmail"></div>Email button (blue)
                        </td>
                    </tr>
                    <tr class="panel-collapse collapse EA_collapsed <?=$tr_class?>" aria-expanded="true">
                        <td style="width: 15%;"><span style="padding-left: 5px;"><strong>Preload email addresses</strong> from existing REDCap records. <span><div class="description_config">Enables autocomplete of email addresses in the TO and CC email fields. The list of email addresses is pulled from the specified variables in already existing REDCap records. </div></td>
                        <td style="width: 25%;padding: 10px 30px;"><span class="table_example">Format: [email_var], ...</span><br/><input type="text"  name="emailFromForm_var" id="emailFromForm_var" style="width: 100%;" placeholder="[name_var], [surname_var], ..." value="<?=$emailTriggerModule->getProjectSetting('emailFromForm_var');?>"></td>
                    </tr>
                    <tr class="panel-collapse collapse EA_collapsed <?=$tr_class?>" aria-expanded="true">
                        <td style="width: 15%;"><span style="padding-left: 5px;">Define <strong>Sender Email Name</strong> for email alerts<span><div class="description_config">Allows the user to set a custom sender name for the email alerts. This only affects the sender name, not the sender email address. The sender email address used by this email alerts tool is configured by your REDCap administrator.</div></td>
                        <td style="width: 25%;padding: 10px 30px;">
                            Sender name<br/><input type="text"  name="emailSender_var" id="emailSender_var" style="width: 100%;" placeholder='Sender name' value='<?=$emailTriggerModule->getProjectSetting('emailSender_var');?>'/>
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
                        <td style="width: 15%;"><span style="padding-left: 5px;">Enable <strong>Data Piping</strong> in email content. <span><div class="description_config">Allows data from the REDCap form(s) to be piped into the email messages. Project variables must be mapped to labels to be used in email piping. Enter one mapping per line.</div></td>
                        <td style="width: 25%;padding: 10px 30px;">
                            <span class="table_example">Format: [email_variable], Button Name</span><br/>
                            <textarea type="text"  name="datapipe_var" id="datapipe_var" style="width: 100%;height: 100px;" placeholder="" value="<?=$emailTriggerModule->getProjectSetting('datapipe_var');?>"><?=$emailTriggerModule->getProjectSetting('datapipe_var');?></textarea>
                            <div class="btn_color_square btn_color_datapipe"></div>Data variable button (gray)
                        </td>
                    </tr>
                    <tr class="panel-collapse collapse EC_collapsed <?=$tr_class?>" aria-expanded="true">
                        <td style="width: 15%;"><span style="padding-left: 5px;">Enable <strong>Survey Links</strong> in email content<span><div class="description_config">Allows REDCap survey links for any survey-enabled form to be inserted into email messages.</div></td>
                        <td style="width: 25%;padding: 10px 30px;">
                            <span class="table_example">Example: [SURVEYLINK_form_name], name ...</span><br/>
                            <a id="addLinkBtn" onclick="javascript:$('#addLink').modal('show');" type="button" class="btn btn-sm pull-right btn_color_surveyLink open-codesModal btn_datapiping" style="margin-bottom:5px;">Add Link</a>
                            <textarea type="text"  name="surveyLink_var" id="surveyLink_var" style="width: 100%;height: 100px;" placeholder="[SURVEYLINK_form_name], name ..." value="<?=$emailTriggerModule->getProjectSetting('surveyLink_var');?>"><?=$emailTriggerModule->getProjectSetting('surveyLink_var');?></textarea>
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
                        <td style="width: 15%;"><span style="padding-left: 5px;">Send <strong>Failed Email Alerts</strong> to specified address<span></td>
                        <td style="width: 25%;padding: 10px 30px;">Email addresses<br/><input type="text"  name="emailFailed_var" id="emailFailed_var" style="width: 100%;" placeholder="myemail@server.com, myemail2@server.com,..." value="<?=$emailTriggerModule->getProjectSetting('emailFailed_var');?>"/></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</form>
<div>
    <button type="submit" form="mainForm" class="btn btn-info pull-right email_forms_button" id="SubmitNewConfigureBtn">Save Settings</button>
</div>
<?PHP require('codes_modal.php');?>

<div style="padding-top:50px" class="col-md-12">
    <div>
        <a href="" id='btnViewCodes' type="button" class="btn btn-info pull-left email_forms_button_color email_forms_button open-codesModal" style="font-size:14px;color:#fff;margin-top: 0;margin-bottom: 10px;">Add New Email</a>
    </div
    <div style="padding-left:15px">
        <?php  if($indexSubSet>0) { ?>
        <table class="table table-bordered table-hover email_preview_forms_table" id="customizedAlertsPreview" style="width: 100%;">
            <thead>
            <tr class="table_header">
                <th>Form</th>
                <th>REDCap logic</th>
                <th>Email Addresses</th>
                <th>Message</th>
                <th>Resend Emails on Form Re-save?</th>
                <th>Attachments</th>
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

                $deactivate = "";
                if($projectData['settings']['email-deactivate']['value'][$index] == '1'){
                    $message_sent .= "<span style='display:block;font-style:italic'>Email deactivated</span>";
                    $class_sent = "email_deactivated";
                    $deactivate = "Activate";
                }else{
                    $deactivate = "Deactivate";
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
                                    $attachmentFile .= '- '.$row['doc_name'].'<br/>';
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
                        } else if ($configRow['key'] == 'email-cc') {
                            if(empty($value)){
                                $alerts .= '</td>';
                            }else{
                                $alerts .= '<em>' . $configRow['name'] . '</em><span>' . str_replace (',',', ',$value) . '</span></td>';
                            }
                        } else if($configRow['key'] == 'form-name') {
                            $alerts .= '<td class="'.$class_sent.'"><span>' . $configRow['value'][$index] . '</span>'.$message_sent.'</td>';
                        }else if($configRow['key'] == 'email-attachment-variable'){
                            $attchVar = preg_split("/[;,]+/",  $configRow['value'][$index]);
                            foreach ($attchVar as $var){
                                if(!empty($var)){
                                    $fileAttachments++;
                                    $attachmentVar .= '- '.$var.'<br/>';
                                }
                            }
                        }else if($configRow['key'] == 'email-subject') {
                            $alerts .= '<td><span>'.$configRow['value'][$index] . '</span><br/>';
                        }else if ($configRow['key'] == 'email-text'){
                            $alerts .= '<span><a onclick="previewEmailAlert('.$index.')" style="cursor:pointer" >Preview Message</a></span></td>';
                        }else{
                            $alerts .= '<td><span>'.$configRow['value'][$index] . '</span></td>';
                        }
                    }
                    $info_modal[$index][$configRow['key']] = $configRow['value'][$index];


                }
                $alerts .= "<td><span style='text-align: center;width: 200px;'><strong>" . $fileAttachments . " files</strong><br/></span>".$attachmentVar.$attachmentFile."</td>";
                $alerts .= "<td><div><a id='emailRow$index' type='button' class='btn btn-info btn-new-email btn-new-email-edit'>Edit Email</a></div>";
                $alerts .= "<div><a onclick='deactivateEmailAlert(".$index.",\"".$deactivate."\")' type='button' class='btn btn-info btn-new-email btn-new-email-deactivate' >".$deactivate."</a></div>";
                $alerts .= "<div><a onclick='deleteEmailAlert(".$index.")' type='button' class='btn btn-info btn-new-email btn-new-email-delete' >Delete</a></div></td>";
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
                        </br>
                        <table class="code_modal_table">
                            <tr class="form-control-custom">
                                <td>Form name:</td>
                                <td>
                                    <select class="external-modules-input-element" name="survey_form_name" id="survey_form_name">
                                        <option value=""></option>
                                        <?php
                                        foreach ($simple_config['email-dashboard-settings'][0]['choices'] as $choice){
                                            echo '<option value="SURVEYLINK_'.$choice['value'].'">'.$choice['name'].'</option>';
                                        }
                                        ?>
                                    </select>
                                </td>
                            </tr>
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
            <div class="modal fade" id="external-modules-configure-modal-update" tabindex="-1" role="dialog" aria-labelledby="Codes">
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
                        <input type="hidden" value="" id="index_modal_delete" name="index_modal_delete">
                        <input type="hidden" value="<?=$emailTriggerModule->getUrl('deleteForm.php')?>" id="url_modal_delete" name="url_modal_delete">
                    </div>

                    <div class="modal-footer">
                        <button type="submit" form="deleteForm" class="btn btn-default btn-delete" id='btnModalDeleteForm'>Delete</button>
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
