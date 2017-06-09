<?php
namespace ExternalModules;
require_once __DIR__ . '/../../external_modules/classes/ExternalModules.php';
require_once ExternalModules::getProjectHeaderPath();
require_once 'EmailTriggerExternalModule.php';
require_once __DIR__ . '/../../external_modules/manager/templates/globals.php';

define('_path_external_modules_ajax','/../../external_modules/manager/ajax');

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

#get number of instances
$indexSubSet = sizeof($config['email-dashboard-settings'][0]['value']);

//printf("<pre>%s</pre>",print_r($projectData['settings']['email-sent'],TRUE));

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
        //Dashboard info
        var datapipe_enable = <?=json_encode($emailTriggerModule->getProjectSetting('datapipe_enable'))?>;
        var datapipe_label = <?=json_encode($emailTriggerModule->getProjectSetting('datapipe_label'))?>;
        var datapipe_var = <?=json_encode($emailTriggerModule->getProjectSetting('datapipe_var'))?>;
        var emailFromForm_enable = <?=json_encode($emailTriggerModule->getProjectSetting('emailFromForm_enable'))?>;
        var emailFromForm_var = <?=json_encode($emailTriggerModule->getProjectSetting('emailFromForm_var'))?>;

        //Url
        var pid = '<?=$pid?>';
        var _preview_url = '<?=$emailTriggerModule->getUrl('previewForm.php')?>';
        var _edoc_name_url = '<?=$emailTriggerModule->getUrl('get-edoc-name.php')?>';
        var _path_external_modules_ajax = <?=json_encode(_path_external_modules_ajax)?>;

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
            }).on('div.dataTables_filter input','keydown', function( e ){
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
            EMSettings.prototype.getSettingColumns  = function(setting, instance, header){
                instance = undefined;
                var name = EMparent.getInstanceName(setting.key, instance);
                if(setting.type == "checkbox") {
                    if (setting.value == "false" || setting.value == undefined) {
                        setting.value = 0;
                    }
                }

                //We customize depending on the field type
                if(setting.type == 'rich-text'){
                    //We add the Data Pipping buttons
                    var inputHtml = EMparent.getSettingColumns.call(this, setting, instance, header);
                    var buttonsHtml = "";
                    if(datapipe_enable == 'on'){
                        var pipeVar = datapipe_var.split(",");
                        var pipeName = datapipe_label.split(",");
                        for (var i = 0; i < pipeVar.length; i++) {
                            buttonsHtml += "<a class='btn btn_datapining' style='margin: 10px 10px 10px 0px;' onclick='insertAtCursorTinyMCE(\"" + pipeVar[i] + "\");'>" + pipeName[i] + "</a>";
                        }
                        inputHtml = inputHtml.replace("<td class='external-modules-input-td'>","<td class='external-modules-input-td'><div>"+buttonsHtml+"<div>");
                    }
                    return inputHtml;
                }else if(setting.type == 'text' && (setting.key == 'email-to' || setting.key == 'email-to-update' || setting.key == 'email-cc' || setting.key == 'email-cc-update')){
                    //We add the datalist for the emails
                    inputHtml += "<tr class='"+customClass+"'><td><span class='external-modules-instance-label'></span><label>" + setting.name + ":</label></td>";
                    var datalistname = "json-datalist-"+setting.key;
                    var inputProperties = {'list':datalistname,'class':'flexdatalist','multiple':'multiple','data-min-length':'1','id':setting.key};
                    inputHtml += "<td class='external-modules-input-td'>"+this.getInputElement(setting.type, setting.key, setting.value, inputProperties);
                    inputHtml += "<datalist id='"+datalistname+"'></datalist></td><td></td><tr>";

                    return inputHtml;
                } else{
                    return EMparent.getSettingColumns.call(this, setting, instance, header);
                }
            }

            var EMSettingsInstance = new EMSettings();
            var rowsHtml = '';
            var rowsHtmlUpdate = '';
            configSettings.forEach(function(setting){
                var setting = $.extend({}, setting);
                rowsHtml += EMSettingsInstance.getProjectSettingHTML(setting,false, <?=json_encode($indexSubSet)?>,'', customClass);

                //We change names for the second modal elements so the rich text works
                setting.key = setting.key+'-update';
                rowsHtmlUpdate += EMSettingsInstance.getProjectSettingHTML(setting,false, <?=json_encode($indexSubSet)?>,'', customClass);

            });
            EMparentAux = EMparent;

            //We add the HTML code to the respective modal windows
            $('#code_modal_table').html('<tr>'+rowsHtml+'</tr>');
            $('#code_modal_table_update').html('<tr>'+rowsHtmlUpdate+'</tr>');

            //Show Add New Email modal
            $('#btnViewCodes').on('click', function(e){
                EMparent.configureSettings(configSettings, configSettings);

                $('#external-modules-configure-modal').modal('show');
                e.preventDefault();

            });

            $('#mainForm').submit(function () {
                $('#errMsgContainer').hide();
                $('#succMsgContainer').hide();

                var errMsg = [];
                if ($("#datapipe_enable").is(":checked") == true) {
                    if ($('#datapipe_label').val() === "" || $('#datapipe_label').val() === "0") {
                        errMsg.push('Please insert at least one <strong>Custom Label</strong>.');
                    }
                    if ($('#datapipe_var').val() === "" || $('#datapipe_var').val() === "0") {
                        errMsg.push('Please insert at least one <strong>Variable Name</strong>.');
                    }else{
                        var result = $('#datapipe_var').val().split(",");
//                        console.log("[37][email]".match(/\[\w*\]+/))
                        for(var i=0;i<result.length;i++){
                            if(!(trim(result[i]).startsWith("[")) || !(trim(result[i]).endsWith("]"))){
                                errMsg.push('<strong>Data Piping Variable Name</strong> must be follow the format: [variable_name].');
                            }
                        }
                    }
                }

                if ($("#emailFromForm_enable").is(":checked") == true) {
                    if ($('#emailFromForm_var').val() === "" || $('#emailFromForm_var').val() === "0") {
                        errMsg.push('Please insert at least one <strong>Variable Name</strong>.');
                    }else{
                        var result = $('#emailFromForm_var').val().split(",");
                        for(var i=0;i<result.length;i++){
                            if(!(trim(result[i]).startsWith("[")) || !(trim(result[i]).endsWith("]"))){
                                errMsg.push('<strong>Email Addresses Variable Name</strong> must be follow the format: [variable_name].');
                            }
                        }
                    }
                }

                if (errMsg.length > 0) {
                    $('#errMsgContainer').empty();
                    $.each(errMsg, function (i, e) {
                        $('#errMsgContainer').append('<div>' + e + '</div>');
                    });
                    $('#errMsgContainer').show();
                    $('html,body').scrollTop(0);
                    return false;
                }else{
                    if ($("#datapipe_enable").is(":checked") == false) {
                        $('#datapipe_label').val("");
                        $('#datapipe_var').val("");
                    }

                    var data = $('#mainForm').serialize();
                    ajaxLoadOptionAndMessage(data,'<?=$emailTriggerModule->getUrl('configureAJAX.php')?>',"C");
                }
                return false;
            });

            //we call first the flexalist function to create the options for the email
            $('.flexdatalist').flexdatalist({
                minLength: 1
            });
            //we call each time a letter is typed to search in the DB for the options and load them
            $('#email-to-flexdatalist, #email-to-update-flexdatalist, #email-cc-flexdatalist, #email-cc-update-flexdatalist').on('keyup', function(e){
                if(emailFromForm_enable == 'on'){
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
//                        console.log("returnData: "+JSON.stringify(returnData));
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
<form class="form-inline" action="" method="post" id='mainForm'>
    <div class="container-fluid wiki">
        <div class='row' style=''>
            <div class="col-md-12 page_title">Configure Email Alerts</div>
            <div id='errMsgContainer' class="alert alert-danger col-md-12" role="alert" style="display:none;margin-bottom:20px;"></div>
            <div class="alert alert-success fade in col-md-12" style="border-color: #b2dba1 !important;display: none;" id="succMsgContainer"></div>

            <div class="col-md-12">
                <table class="table table-bordered table-hover">
                    <tr class="table_header">
                        <td>Enable</td>
                        <td>Field 1</td>
                        <td>Field 2</td>
                        <td>Description</td>
                    </tr>
                    <tr>
                        <td><input type="checkbox" name="datapipe_enable" id="datapipe_enable" <?=($emailTriggerModule->getProjectSetting('datapipe_enable') == "on")?"checked":"";?>><span style="padding-left: 5px;">Data Piping<span></td>
                        <td>Custom label<br/><span class="table_example">Example: name, surname, ...</span><br/><input type="text" name="datapipe_label" id="datapipe_label" style="width: 100%;" placeholder="name, surname, ..." value="<?=$emailTriggerModule->getProjectSetting('datapipe_label');?>"></td>
                        <td>Variable name<br/><span class="table_example">Example: [name_var], [surname_var], ...</span><br/><input type="text"  name="datapipe_var" id="datapipe_var" style="width: 100%;" placeholder="[name_var], [surname_var], ..." value="<?=$emailTriggerModule->getProjectSetting('datapipe_var');?>"></td>
                        <td>Enables the option to create workflow messages that allow to pipe data from the form.</td>
                    </tr>
<?php /*?>
                    <tr class="table_header">
                        <td>Enable</td>
                        <td>Field</td>
                        <td>Description</td>
                    </tr>
                    <tr>
                        <td><input type="checkbox" name="datapipe_enable" id="datapipe_enable" <?=($emailTriggerModule->getProjectSetting('datapipe_enable') == "on")?"checked":"";?>><span style="padding-left: 5px;">Data Piping<span></td>
                        <td>Variable name<br/><span class="table_example">Example: [name_var], [surname_var], ...</span><br/><textarea type="text"  name="datapipe_var" id="datapipe_var" style="width: 100%;" placeholder="[name_var], name ..." value="<?=$emailTriggerModule->getProjectSetting('datapipe_var');?>"><?=$emailTriggerModule->getProjectSetting('datapipe_var');?></textarea></td>
                        <td>Enables the option to create workflow messages that allow to pipe data from the form.</td>
                    </tr>
 <?php */?>
                    <tr>
                        <td><input type="checkbox" name="emailFromForm_enable" id="emailFromForm_enable" <?=($emailTriggerModule->getProjectSetting('emailFromForm_enable') == "on")?"checked":"";?>><span style="padding-left: 5px;">Email Addresses<span></td>
                        <td></td>
                        <td>Variable name<br/><span class="table_example">Example: [email_var], ...</span><br/><input type="text"  name="emailFromForm_var" id="emailFromForm_var" style="width: 100%;" placeholder="[name_var], [surname_var], ..." value="<?=$emailTriggerModule->getProjectSetting('emailFromForm_var');?>"></td>
                        <td>Enables the option to preload email addresses from form variables. Activating this option also allows to the form variable as 'To' or 'CC' options. </td>
                    </tr>

                </table>
            </div>
        </div>
    </div>
</form>
<div>
    <button type="submit" form="mainForm" class="btn btn-info pull-right email_forms_button" id="SubmitNewConfigureBtn">Submit</button>
<!--    <a href="#external-modules-configure-modal" id='btnViewCodes' type="button" class="btn btn-info pull-right email_forms_button_color email_forms_button open-codesModal" style="font-size:14px;color:#fff" data-toggle="modal" data-target="#external-modules-configure-modal">Add New Email</a>-->
    <a href="" id='btnViewCodes' type="button" class="btn btn-info pull-right email_forms_button_color email_forms_button open-codesModal" style="font-size:14px;color:#fff">Add New Email</a>
</div>
<?PHP require('codes_modal.php');?>

<div style="padding-top:100px">
    <div class="col-md-12">
        <?php  if($indexSubSet>0) { ?>
        <table class="table table-bordered table-hover email_preview_forms_table" id="customizedAlertsPreview">
            <thead>
            <tr class="table_header">
                <th>Form <span class="glyphicon glyphicon-align-right glyphicon-sort concepts-table-sortable" aria-hidden="true"></span></th>
                <th>Email Addresses <span class="glyphicon glyphicon-align-right glyphicon-sort concepts-table-sortable" aria-hidden="true"></span></th>
                <th>Subject <span class="glyphicon glyphicon-align-right glyphicon-sort concepts-table-sortable" aria-hidden="true"></span></th>
                <th>Message <span class="glyphicon glyphicon-align-right glyphicon-sort concepts-table-sortable" aria-hidden="true"></span></th>
                <th>More than one time/instrument? <span class="glyphicon glyphicon-align-right glyphicon-sort concepts-table-sortable" aria-hidden="true"></span></th>
                <th>Leave Timestamp? <span class="glyphicon glyphicon-align-right glyphicon-sort concepts-table-sortable" aria-hidden="true"></span></th>
                <th>REDCap logic <span class="glyphicon glyphicon-align-right glyphicon-sort concepts-table-sortable" aria-hidden="true"></span></th>
                <th>#Attachments <span class="glyphicon glyphicon-align-right glyphicon-sort concepts-table-sortable" aria-hidden="true"></span></th>
                <th class="table_header_options">Options</th>
            </tr>
            </thead>
            <tbody>
            <?php
            $alerts = "";
            for ($index = 0; $index < $indexSubSet; $index++) {
                $email_sent = $projectData['settings']['email-sent']['value'][$index];
                $class_sent = "";
                $message_sent = "";
                if($email_sent == "1"){
                    $class_sent = "email_sent";
                    if($projectData['settings']['email-timestamp']['value'][$index] == "1" && !empty($projectData['settings']['email-timestamp-sent']['value'][$index])){
                        $message_sent = "<span style='display:block;font-style:italic'>Most recently activated on: ".$projectData['settings']['email-timestamp-sent']['value'][$index]."</span>";
                    }else{
                        $message_sent = "<span style='display:block;font-style:italic'>Email activated</span>";
                    }

                }
                $alerts .= '<tr class="'.$class_sent.'">';
                $fileAttachments = 0;
                foreach ($config['email-dashboard-settings'] as $configKey => $configRow) {

                    if ($configRow['type'] == 'file') {
                        if(!empty($configRow['value'][$index])){
                            $fileAttachments++;
                        }
                    } else if ($configRow['type'] == 'checkbox') {
                        $value = ($configRow['value'][$index] == 0) ? "No" : "Yes";
                        $alerts .= '<td  style="width: 150px;text-align: center"><span>' . $value . '</span></td>';
                    } else if ($configRow['type'] == 'rich-text') {
                       $alerts .= '<td  style="width: 150px;text-align: center"><span><a onclick="previewEmailAlert('.$index.')" style="cursor:pointer" >Preview</a></span></td>';
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
                            $alerts .= '<td style="width: 350px;"><span>' . $value . '</span>'.$message_sent.'</td>';
                        }else{
                            $alerts .= '<td style="width: 350px;"><span>' . $value . '</span></td>';
                        }
                    }
                    $info_modal[$index][$configRow['key']] = $configRow['value'][$index];
                }
                $fileAttachments = ($fileAttachments == 0) ? "None" : $fileAttachments;
                $alerts .= "<td><span style='text-align: center'>" . $fileAttachments . "</span></td>";
                $alerts .= "<td style='text-align: center'><strong><a onclick='editEmailAlert(".json_encode($info_modal[$index]).",".$index.")' style='cursor:pointer' ><img src='" . APP_PATH_WEBROOT_FULL . APP_PATH_WEBROOT . "Resources/images/pencil.png'/></a></strong>";
                $alerts .= "<br/><br/><strong><a onclick='deleteEmailAlert(".$index.")' style='cursor:pointer' >Delete</a></strong></td>";
                $alerts .= "</tr>";
            }
            echo $alerts;
        }
        ?>
            <tbody>
        </table>
    </div>

    <div class="col-md-12">
        <form class="form-horizontal" action="" method="post" id='updateForm'>
            <div class="modal fade" id="external-modules-configure-modal-update" tabindex="-1" role="dialog" aria-labelledby="Codes">
                <div class="modal-dialog" role="document">
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
                            <button type="button" class="btn btn-default" id='btnCloseCodesModal' data-dismiss="modal">CLOSE</button>
                            <button type="submit" form="updateForm" class="btn btn-default save" id='btnModalUpdateForm'>Save changes</button>
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

    <div class="modal fade" id="external-modules-configure-modal-preview" tabindex="-1" role="dialog" aria-labelledby="Codes">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close closeCustomModal" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="myModalLabel">Preview Email Alert</h4>
                </div>
                <div class="modal-body">
                    <div id="modal_message_preview"></div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default" id='btnCloseCodesModalDelete' data-dismiss="modal">Cose</button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once ExternalModules::getProjectFooterPath();