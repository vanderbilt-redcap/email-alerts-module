<?php
namespace Vanderbilt\EmailTriggerExternalModule;
use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;
require_once APP_PATH_DOCROOT . 'ProjectGeneral/header.php';

$config = $module->getConfig();
$prefix = (htmlentities(htmlspecialchars($_REQUEST['prefix'], ENT_QUOTES), ENT_QUOTES));

$pid = (int)$_GET['pid'];
$from_default = empty($module->getProjectSetting('email-sender'))?array():$module->getProjectSetting('email-sender').',"'.$module->getProjectSetting('emailSender_var').'"';

$projectData= (array(
    'status' => 'success',
    'settings' => $module->getProjectSettings($pid)));

$simple_config = $config;
$simple_config_update = $config;

#Add DataBase values to settings
for($i=0;$i<sizeof($config['email-dashboard-settings']);$i++){
    if (isset($projectData['settings'][$config['email-dashboard-settings'][$i]['key']])) {
        $config['email-dashboard-settings'][$i]['value'] =  $projectData['settings'][$config['email-dashboard-settings'][$i]['key']]['value'];
    }
}

#Add choices values to settings
foreach($config['email-dashboard-settings'] as $configKey => $configRow) {
    $config['email-dashboard-settings'][$configKey] = $module->getAdditionalFieldChoices($configRow,$pid);
}

#Add choices values to simple settings
foreach($simple_config['email-dashboard-settings'] as $configKey => $configRow) {
    $simple_config['email-dashboard-settings'][$configKey] = $module->getAdditionalFieldChoices($configRow,$pid);
    $simple_config_update['email-dashboard-settings'][$configKey]['key'] = $configRow['key']."-update";
}

$message="";
$message_text = array('C'=>'<strong>Success!</strong> The configuration has been saved.','A'=>'<strong>Success!</strong> New Email Added.','U'=>'<strong>Success!</strong> Email Updated.',
    'D'=>'<strong>Success!</strong> Email Deleted.','B'=>'<strong>Success!</strong> Email Permanently Deleted.','T'=>'<strong>Success!</strong> Email Activated.','E'=>'<strong>Success!</strong> Email Deactivated.',
    'P'=>'<strong>Success!</strong> Email Duplicated.','R'=>'<strong>Success!</strong> Email Re-Enabled.','N'=>'<strong>Success!</strong> Email Re-Enabled.','Q'=>'<strong>Success!</strong> New Queued Email Added.','O'=>'<strong>Success!</strong> Queue Deleted.');

if(array_key_exists('message', $_REQUEST)){
    $message = $message_text[$_REQUEST['message']];
}

#get number of instances
$indexSubSet = 0;
if($config['email-dashboard-settings'][1]['value'] != null) {
    $indexSubSet = count($config['email-dashboard-settings'][1]['value']);
}

#User rights
$UserRights = \REDCap::getUserRights(USERID)[USERID];
$isAdmin = false;
if($UserRights['user_rights'] == '1'){
    $isAdmin = true;
}$UserRights = \REDCap::getUserRights(USERID)[USERID];
$isAdmin = false;
if($UserRights['user_rights'] == '1'){
    $isAdmin = true;
}

$super_user = false;
if(USERID != "") {
    $sql = "SELECT i.user_email, i.user_firstname, i.user_lastname, i.super_user, i.allow_create_db
					FROM redcap_user_information i
					WHERE i.username = '" . db_escape(USERID) . "'";
    $query = db_query($sql);
    if (!$query) throw new \Exception("Error looking up user information", self::$SQL_ERROR);

    if ($row = db_fetch_assoc($query)) {
        if ($row["super_user"] == 1) {
            $super_user = true;
        }
    }
}


$module->framework->initializeJavascriptModuleObject();


$language_errors =[
    "em_errors_91",
    "em_errors_92",
    "em_errors_93",
    "em_errors_94",
    "em_errors_95",
    "em_errors_96",
    "em_errors_97",
    "em_manage_13",
    "em_manage_72",
    "em_manage_73",
    "em_manage_74",
    "em_manage_75",
    "em_manage_76",
    "em_manage_77",
    "em_manage_78",
    "em_tinymce_language",
];
foreach ($language_errors as $err){
    $module->framework->tt_transferToJavascriptModuleObject($err);
}
?>
<script>
        var module = <?=$module->framework->getJavascriptModuleObjectName()?>;

</script>

    <link type='text/css' href='<?=$module->getUrl('css/font-awesome.min.css')?>' rel='stylesheet' media='screen' />
    <link type='text/css' href='<?=$module->getUrl('css/style_arrangement.css')?>' rel='stylesheet' media='screen' />

    <link rel="stylesheet" type="text/css" href="<?=$module->getUrl('css/style.css')?>">
    <link rel="stylesheet" type="text/css" href="<?=$module->getUrl('css/jquery.flexdatalist.min.css')?>">

    <script type="text/javascript" src="<?=$module->getUrl('js/globals.js')?>"></script>
    <?php
    if (version_compare(REDCAP_VERSION, '9.8.0', '>=')) {
        ?>
        <link rel='stylesheet' href='<?php echo APP_PATH_CSS ?>spectrum.css'>
        <script type='text/javascript' src='<?php echo APP_PATH_JS ?>Libraries/spectrum.js'></script>
        <?php
    } else {
        ?>
        <script src="<?php echo APP_PATH_JS ?>tinymce/tinymce.min.js"></script>
        <link rel="stylesheet" type="text/css" href="<?php echo APP_PATH_CSS ?>select2.css">
        <script type="text/javascript" src="<?php echo APP_PATH_JS ?>select2.js"></script>
        <link rel='stylesheet' href='<?php echo APP_PATH_CSS ?>spectrum.css'>
        <script type='text/javascript' src='<?php echo APP_PATH_JS ?>spectrum.js'></script>
        <?php
    }
    ?>

    <script type="text/javascript" src="<?=$module->getUrl('js/jquery.dataTables.min.js')?>"></script>
    <script type="text/javascript" src="<?=$module->getUrl('js/jquery.flexdatalist.js')?>"></script>
    <script type="text/javascript" src="<?=$module->getUrl('js/functions.js')?>"></script>

    <script type="text/javascript">
        var EMparentAux;
        var configSettings = <?=json_encode($simple_config['email-dashboard-settings'])?>;
        var configSettingsUpdate = <?=json_encode($simple_config_update['email-dashboard-settings'])?>;
        var project_id = <?=$pid?>;
        var isLongitudinal = <?=json_encode(\REDCap::isLongitudinal())?>;
        var from_default = <?=json_encode($from_default)?>;
        var message_letter = <?=json_encode(htmlspecialchars(isset($_REQUEST['message']) ? $_REQUEST['message'] : "",ENT_QUOTES))?>;
        var isAdmin = <?=json_encode($isAdmin)?>;

        //Dashboard info
        var datapipe_var = <?=json_encode($module->getProjectSetting('datapipe_var'))?>;
        var emailFromForm_var = <?=json_encode($module->getProjectSetting('emailFromForm_var'))?>;
        var emailSender_var = <?=json_encode($module->getProjectSetting('emailSender_var'))?>;
        var datapipeEmail_var = <?=json_encode($module->getProjectSetting('datapipeEmail_var'))?>;
        var surveyLink_var = <?=json_encode($module->getProjectSetting('surveyLink_var'))?>;
        var formLink_var = <?=json_encode($module->getProjectSetting('formLink_var'))?>;

        //Url
        var pid = '<?=$pid?>';
        var _duplicateform_url = '<?=$module->getUrl('duplicateForm.php')?>';
        var _reenableform_url = '<?=$module->getUrl('reEnableForm.php')?>';
        var _preview_url = '<?=$module->getUrl('previewForm.php')?>';
        var _update_queue_url = '<?=$module->getUrl('updateQueue.php')?>';
        var _delete_queue_url = '<?=$module->getUrl('deleteQueue.php')?>';
        var _delete_queue_all_url = '<?=$module->getUrl('deleteQueueAll.php')?>';
        var _preview_queue_url = '<?=$module->getUrl('previewQueue.php')?>';
        var _preview_record_url = '<?=$module->getUrl('previewRecordForm.php')?>';
        var _preview_record_modal_url = '<?=$module->getUrl('previewRecordModal.php')?>';
        var _edoc_name_url = '<?=$module->getUrl('get-edoc-name.php')?>';
        var _longitudinal_url = '<?=$module->getUrl('getLongitudinal_forms_event_AJAX.php')?>';
        var _repeatable_url = '<?=$module->getUrl('getRepeatableInstances_AJAX.php')?>';
        var _repeating_url = '<?=$module->getUrl('isRepeatingForm_AJAX.php')?>';
        var _getProjectList_url = '<?=$module->getUrl('get-project-list.php')?>';
        var lastClick = null;
        var startPos = 0;
        var endPos = 0;
        var calendarimg = '<?=$module->getUrl('img/date.png')?>';

        $(function(){
            // Prevent Bootstrap dialog from blocking focusin
            document.addEventListener('focusin', (e) => {
                if (e.target.closest(".tox-tinymce-aux, .moxman-window, .tam-assetmanager-root") !== null) {
                    e.stopImmediatePropagation();
                }
            });

            jQuery('[data-toggle="popover"]').popover({
                html : true,
                content: function() {
                    return $(this).data('content');
                },
                title: function(){
                    return '<span style="padding-top:0px;">'+jQuery(this).data('title')+'<span class="close" style="line-height: 0.5;padding-top:0px;padding-left: 10px">&times;</span></span>';
                }
            }).on('shown.bs.popover', function(e){
                var popover = jQuery(this);
                jQuery(this).parent().find('div.popover .close').on('click', function(e){
                    popover.popover('hide');
                    // Hide any other opened popups first
                    if ($('.popover:visible').length > 0) {
                        $('.popover').hide();
                    }
                });
                $('div.popover .close').on('click', function(e){
                    popover.popover('hide');
                    // Hide any other opened popups first
                    if ($('.popover:visible').length > 0) {
                        $('.popover').hide();
                    }
                });
            });

            $('[data-toggle="popover"]').click(function(e) {
                // Hide any other opened popups first
                if ($('.popover:visible').length > 0) {
                    $('.popover').hide();
                }
                bootstrap.Popover.getOrCreateInstance(this).dispose();
                // Show popup
                popover = new bootstrap.Popover(e.target, {
                    html: true,
                    title: '<span style="padding-top:0px;">'+$(this).data('title')+'<span class="close" style="line-height: 0.5;padding-top:0px;padding-left: 10px">&times;</span></span>',
                    content: $(this).data('content')
                });
                popover.show();
                $('.close').css('cursor', 'pointer');
            });

            // Hide popup if clicked anywhere on page (outside popup)
            $('html').on('click', function (e) {
                if(!$(e.target).is('[data-bs-toggle="popover"]') && $(e.target).closest('.popover').length == 0) {
                    $('[data-bs-toggle="popover"]').popover('hide');
                }
            });

            //To prevent the popover from scrolling up on click
            $("a[rel=popover]")
                .popover()
                .click(function(e) {
                    e.preventDefault();
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
            var EMparent = EmailAlerts.Settings.prototype;
            var EMSettings = function(){}
            EMSettings.prototype = Object.create(EmailAlerts.Settings.prototype);
            EMSettings.prototype.getSettingColumns  = function(setting,savedSettings,previousInstance) {
				previousInstance = undefined;
                if (setting.type == "checkbox") {
                    if (setting.value == "false" || setting.value == undefined) {
                        setting.value = 0;
                    }
                }

                //We customize depending on the field type
                if (setting.type == 'rich-text') {
                    //We add the Data Piping buttons
					if(typeof EmailAlerts.Settings.prototype.getColumnHtml === "undefined") {
						var inputHtml = EMparent.getSettingColumns.call(this, setting, instance, header);
					}
	                else {
						var inputHtml = EMparent.getColumnHtml(setting);
					}
                    var buttonsHtml = "";
                    if ((datapipeEmail_var != '' && datapipeEmail_var != null) || (datapipe_var != '' && datapipe_var != null) || (surveyLink_var != '' && surveyLink_var != null) || (formLink_var != '' && formLink_var != null)) {
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
                                buttonsHtml += "<a class='btn btn_datapiping btn-sm btn_color_surveyLink btn_piping' onclick='insertAtCursorTinyMCE(\"" + trim(pipeName[0]) + "\",2);'>" + trim(pipeName[1]) + "</a>";
                            }
                        }

                        if (formLink_var != '' && formLink_var != null) {
                            var pipeVar = formLink_var.split("\n");
                            for (var i = 0; i < pipeVar.length; i++) {
                                var pipeName = pipeVar[i].split(",");
                                buttonsHtml += "<a class='btn btn_datapiping btn-sm btn_color_formLink btn_piping' onclick='insertAtCursorTinyMCE(\"" + trim(pipeName[0]) + "\",2);'>" + trim(pipeName[1]) + "</a>";
                            }
                        }

                        var buttonLegend = "<div style=''><div class='btn_legend'><div class='btn_color_square btn_color_datapipe'></div>Data variable</div>";
                        buttonLegend += "<div class='btn_legend'><div class='btn_color_square btn_color_datapipeEmail'></div>Email address</div>";
                        buttonLegend += "<div class='btn_legend'><div class='btn_color_square btn_color_surveyLink'></div>Survey link</div>";
                        buttonLegend += "<div class='btn_legend'><div class='btn_color_square btn_color_formLink'></div>Data-Form link</div>";
                        buttonLegend += "<div>";

                        inputHtml = inputHtml.replace("<td class='external-modules-input-td'>", "<td class='external-modules-input-td'>" + buttonLegend + "<div>" + buttonsHtml + "<div>");
                    }
                    return inputHtml;
                }else if (setting.type == 'text' && (setting.key == 'email-to' || setting.key == 'email-to-update' || setting.key == 'email-cc' || setting.key == 'email-cc-update' || setting.key == 'email-bcc' || setting.key == 'email-bcc-update')) {
                    var reqLabel = '';
                    if(setting.key == 'email-to' || setting.key == 'email-to-update'){
                        reqLabel = '<div class="requiredlabel">* must provide value</div>';
                    }
                    //We add the datalist for the emails
                    inputHtml += "<tr class='" + customClass + "'><td><span class='external-modules-instance-label'></span><label>" + setting.name + ":</label>"+reqLabel+"</td>";
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
                }else if((setting.key == 'form-name' || setting.key == 'form-name-update') && isLongitudinal) {
                    if (typeof ExternalModules.Settings.prototype.getColumnHtml === "undefined") {
                        var inputHtml = EMparent.getSettingColumns.call(this, setting, instance, header);
                    }
                    else {
                        var inputHtml = EMparent.getColumnHtml(setting);
                    }
                    inputHtml += '<tr field="form-name-event" class="form-control-custom" style="display:none"></tr>';
                    return inputHtml;
                }else if(!isAdmin && (setting.key == 'cron-send-email-on' || setting.key == 'cron-queue-send-label' || setting.key == 'cron-send-email-on-field' || setting.key == 'cron-queue-expiration-date' || setting.key == 'cron-queue-expiration-date-field' || setting.key == 'cron-repeat-for' || setting.key == 'cron-send-email-on-update' || setting.key == 'cron-queue-send-label-update' || setting.key == 'cron-queue-expiration-label-update' || setting.key == 'cron-send-email-on-field-update' || setting.key == 'cron-repeat-for-update' || setting.key == 'cron-queue-expiration-date-update' || setting.key == 'cron-queue-expiration-date-field-update')){
                    //if it's not admin we don't show the Schedule editing
                }else if(isAdmin && setting.key == 'cron-repeat-for-update'){

                    if(typeof ExternalModules.Settings.prototype.getColumnHtml === "undefined") {
                        inputHtml += EMparent.getSettingColumns.call(this, setting, instance, header);
                    }
                    else {
                        inputHtml += EMparent.getColumnHtml(setting);
                    }
                    inputHtml += '<tr field="cron-queue-update" class="form-control-custom"><td><span class="external-modules-instance-label"> </span><label>Select this if <i>Send email on</i> parameters<br> have changed and you would like<br> to update the queue</label></td>';
                    inputHtml += '<td class="external-modules-input-td"><input type="checkbox" name="cron-queue-update" class="external-modules-input-element" value=""></td></tr>';
                    return inputHtml;
                }else if(isAdmin && setting.key == 'cron-repeat-for-update'){

                    if(typeof ExternalModules.Settings.prototype.getColumnHtml === "undefined") {
                        inputHtml += EMparent.getSettingColumns.call(this, setting, instance, header);
                    }
                    else {
                        inputHtml += EMparent.getColumnHtml(setting);
                    }
                    inputHtml += '<tr field="cron-queue-update" class="form-control-custom"><td><span class="external-modules-instance-label"> </span><label>Select this if <i>Send email on</i> parameters<br> have changed and you would like<br> to update the queue</label></td>';
                    inputHtml += '<td class="external-modules-input-td"><input type="checkbox" name="cron-queue-update" class="external-modules-input-element" value=""></td></tr>';
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
                }else if(setting.type == 'descriptive' && setting.key == 'cron-queue-send-label' && isAdmin) {
                    rowsHtml += "<tr class='form-control-custom email-schedule-title'><td colspan='4'><div class='form-control-custom-title'>Email Schedule</div></td></tr>";
                    rowsHtmlUpdate += "<tr class='form-control-custom email-schedule-title-update'><td colspan='4'><div class='form-control-custom-title'>Email Schedule</div></td></tr>";
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
                var prefix = <?=json_encode($prefix)?>;
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

                var editor_update = tinymce.get("email-text");
                editor_update.on('focus', function(e) {
                    lastClick = null;
                });

                $('[name="email-attachment-variable"]').attr('placeholder','[variable1], [variable2], ...');
                $('[name="email-from"]').attr('placeholder','myemail@server.com, "Sender name"');
                $('[name="email-from"]').val(from_default);

                if(isAdmin) {
                    $('[name="cron-send-email-on"][value="now"]').prop('checked', true);
                    $('[name="cron-queue-expiration-date"][value="never"]').prop('checked', true);
                    $('[name="cron-repeat-for"]').val('0');
                    $('[name="cron-repeat-for"]').attr('placeholder','0 to do not repeat, a number to repeat');
                    $('[name="cron-repeat-for-update"]').attr('placeholder','0 to do not repeat, a number to repeat');
                    $('[field="cron-queue-expiration-date-field"]').hide();
                }

                //Add calendar on expiration by default
                var suffix='-update';
                $('[field="cron-queue-expiration-date-field"] td input').addClass('datepicker_aux_expire');
                $('[field="cron-queue-expiration-date-field"] td input').addClass('datepicker');
                $('[field="cron-queue-expiration-date-field"] td input').attr('placeholder','YYYY-MM-DD');
                $('[field="cron-queue-expiration-date-field'+suffix+'"] td input').addClass('datepicker_aux_expire');
                $('[field="cron-queue-expiration-date-field'+suffix+'"] td input').addClass('datepicker');
                $('[field="cron-queue-expiration-date-field'+suffix+'"] td input').attr('placeholder','YYYY-MM-DD');

                $(".datepicker_aux_expire").datepicker({
                    showOn: "button",
                    buttonImage: calendarimg,
                    buttonImageOnly: true,
                    buttonText: "Select date",
                    dateFormat: "yy-mm-dd"
                });

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

                const errMsg = [];
                if ($('#datapipe_var').val() !== "" && $('#datapipe_var').val() !== "0") {
                    const pipeVar = $('#datapipe_var').val().split("\n");
                    for (let i = 0; i < pipeVar.length; i++) {
                        const pipeName = pipeVar[i].split(",");
                        if(pipeName[0] !== '' && (trim(pipeName[0]).substring(0, 1) !== "[" || trim(pipeName[0]).substring(trim(pipeName[0]).length-1, trim(pipeName[0]).length) !== "]")){
                            errMsg.push('<strong>Data Piping field</strong> must follow the format: <i>[variable_name],label</i> or <i>[variable_name][smart_variable],label</i>.');
                        }
                    }
                }

                if ($('#datapipeEmail_var').val() !== "" && $('#datapipeEmail_var').val() !== "0") {
                    const pipeVar = $('#datapipeEmail_var').val().split("\n");
                    for (let i = 0; i < pipeVar.length; i++) {
                        const pipeName = pipeVar[i].split(",");
                        if(pipeName[0] !== '' && (trim(pipeName[0]).substring(0, 1) !== "[" || trim(pipeName[0]).substring(trim(pipeName[0]).length-1, trim(pipeName[0]).length) !== "]")){
                            errMsg.push('<strong>Data Piping Email field</strong> must follow the format: <i>[variable_name],label</i> or <i>[variable_name][smart_variable],label</i>.');
                        }
                    }
                }

                if ($('#emailFromForm_var').val() != "" && $('#emailFromForm_var').val() != "0") {
                    const result = $('#emailFromForm_var').val().split(",");
                    for(let i=0;i<result.length;i++){
                        if(result[i] !== '' && (trim(result[i]).substring(0, 1) !== "[" || trim(result[i]).substring(trim(result[i]).length-1, trim(result[i]).length) != "]")){
                            errMsg.push('<strong>Email Addresses field</strong> must follow the format: <i>[variable_name]</i> or <i>[variable_name][smart_variable]</i>.');
                        }
                    }
                }

                const pipeVarLocs = { "#surveyLink_var" :
                                                          {
                                                              "prefixes" : [
                                                                  "[__SURVEYLINK_",
                                                                  "[survey-link:",
                                                                  "[survey-url:",
                                                                  "[survey-queue-link:",
                                                                  "[survey-queue-url]",
                                                                  "[survey-return-code:",
                                                              ],
                                                              "type" : "Survey"
                                                          },
                                    "#formLink_var" :
                                                          {
                                                              "prefixes" : [
                                                                  "[__FORMLINK_",
                                                              ],
                                                              "type" : "Data-Form"
                                                          }
                                  }
                for (const pipeVarLoc in pipeVarLocs) {
                    const formPrefixes = pipeVarLocs[pipeVarLoc]['prefixes'];
                    const options = [];
                    for (let j = 0; j < formPrefixes.length; j++) {
                        const formPrefix = formPrefixes[j];
                        if ((formPrefix === "[__SURVEYLINK_") || (formPrefix === "[__FORMLINK_")) {
                            options.push('['+formPrefix+'variable_name],label');
                        } else if (formPrefix === "[survey-link:") {
                            options.push('['+formPrefix+'instrument:Custom Text for Link],label');
                        } else if ((formPrefix === "[survey-url:") || (formPrefix === "[survey-return-code:")) {
                            options.push('['+formPrefix+'instrument],label');
                        } else if (formPrefix === "[survey-queue-link:") {
                            options.push('['+formPrefix+'Custom Text for Link],label');
                        } else if (formPrefix === "[survey-queue-url]") {
                            options.push('['+formPrefix+',label');
                        }
                    }

                    const type = pipeVarLocs[pipeVarLoc]['type'];
                    if ($(pipeVarLoc).val() !== "" && $(pipeVarLoc).val() !== "0") {
                        const pipeVar = $(pipeVarLoc).val().split("\n");
                        for (let i = 0; i < pipeVar.length; i++) {
                            const pipeName = pipeVar[i].split(",");
                            const matches = pipeName[0].match(/\[(.*?)\]/g);
                            let found = false;
                            for (let j = 0; j < formPrefixes.length; j++) {
                                const formPrefix = formPrefixes[j];
                                if (isLongitudinal && matches && (matches.length >1)) {
                                    if(
                                        trim(matches[1]).substring(0, 1) === "["
                                        && trim(matches[1]).substring(trim(matches[1]).length-1, trim(matches[1]).length) === "]"
                                        && trim(matches[1]).substring(0, formPrefix.length) === formPrefix
                                        && trim(matches[0]).substring(0, 1) === "["
                                        && trim(matches[0]).substring(trim(matches[0]).length-1, trim(matches[0]).length) === "]"
                                    ){
                                        found = true;
                                    }
                                }
                                else if(
                                    trim(pipeName[0]).substring(0, 1) === "["
                                    && trim(pipeName[0]).substring(trim(pipeName[0]).length-1, trim(pipeName[0]).length) === "]"
                                    && trim(pipeName[0]).substring(0, formPrefix.length) === formPrefix
                                ){
                                    found = true;
                                }
                            }
                            if (!found && isLongitudinal) {
                                errMsg.push('<strong>Longitudinal '+type+' Link field</strong> must follow one of the following formats: <i>[event_name]'+options.join('</i>; <i>[event_name]')+'</i>.');
                            } else if (!found) {
                                errMsg.push('<strong>Link '+type+' field</strong> must follow one of the following formats: <i>'+options.join('</i>; <i>')+'</i> .');
                            }
                        }
                    }
                }

                if ($('#emailFailed_var').val() !== "" && $('#emailFailed_var').val() !== "0") {
                    const result = $('#emailFailed_var').val().split(/[;,]+/);
                    for(let i=0;i<result.length;i++){
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
                }else{
                    if ($('#surveyLink_var').val() != "" && $('#surveyLink_var').val() != "0") {
                        checkIfSurveyIsSaveAndReturn("surveyLink_var="+$('#surveyLink_var').val()+'&project_id='+project_id,'<?=$module->getUrl('check_survey_save_return_AJAX.php')?>','<?=$module->getUrl('configureAJAX.php')?>');
                    }else{
                        const data = $('#mainForm').serialize();
                        ajaxLoadOptionAndMessage(data, '<?=$module->getUrl('configureAJAX.php')?>', "C");
                    }
                }
                return false;
            });

            /***SCHEDULED EMAIL OPTIONS***/
            $('[name="cron-send-email-on"],[name="cron-send-email-on-update"]').on('click', function(e){
                var suffix = '';
                if($(this).attr('name').includes("-update")){
                    suffix = '-update';
                }
                if($(this).val() == 'now'){
                    $('[field="cron-send-email-on-field'+suffix+'"]').hide();
                    $('[name="cron-send-email-on-field'+suffix+'"]').val('');
                }else if($(this).val() == 'date' || $(this).val() == 'calc'){
                    $('[field="cron-send-email-on-field'+suffix+'"]').show();
                    if($(this).val() == 'date'){
                        $('[field="cron-send-email-on-field'+suffix+'"] td input').addClass('datepicker_aux');
                        $('[field="cron-send-email-on-field'+suffix+'"] td input').addClass('datepicker');
                        $('[field="cron-send-email-on-field'+suffix+'"] td input').attr('placeholder','YYYY-MM-DD');
                        $(".datepicker_aux").datepicker({
                            showOn: "button",
                            buttonImage: calendarimg,
                            buttonImageOnly: true,
                            buttonText: "Select date",
                            dateFormat: "yy-mm-dd"
                        });
                    }else{
                        $('[field="cron-send-email-on-field'+suffix+'"] td input').datepicker("destroy");
                        $('[field="cron-send-email-on-field'+suffix+'"] td input').removeClass('datepicker');
                        $('[field="cron-send-email-on-field'+suffix+'"] td input').removeClass('datepicker_aux');
                        $('[field="cron-send-email-on-field'+suffix+'"] td input').removeClass('hasDatepicker').removeAttr('id');
                        $('[field="cron-send-email-on-field'+suffix+'"] td input').attr('placeholder','');
                    }
                }
            });
            $('[name="cron-queue-expiration-date"],[name="cron-queue-expiration-date-update"]').on('click', function(e){
                var suffix = '';
                if($(this).attr('name').includes("-update")){
                    suffix = '-update';
                }

                if($(this).val() == 'date'){
                    $('[field="cron-queue-expiration-date-field'+suffix+'"]').show();
                    $('[field="cron-queue-expiration-date-field'+suffix+'"] td input').addClass('datepicker_aux_expire');
                    $('[field="cron-queue-expiration-date-field'+suffix+'"] td input').addClass('datepicker');
                    $('[field="cron-queue-expiration-date-field'+suffix+'"] td input').attr('placeholder','YYYY-MM-DD');
                    $(".datepicker_aux_expire").datepicker({
                        showOn: "button",
                        buttonImage: calendarimg,
                        buttonImageOnly: true,
                        buttonText: "Select date",
                        dateFormat: "yy-mm-dd"
                    });
                }else if($(this).val() == 'cond'){
                    $('[field="cron-queue-expiration-date-field'+suffix+'"]').show();
                    $('[field="cron-queue-expiration-date-field'+suffix+'"] td input').datepicker("destroy");
                    $('[field="cron-queue-expiration-date-field'+suffix+'"] td input').removeClass('datepicker');
                    $('[field="cron-queue-expiration-date-field'+suffix+'"] td input').removeClass('datepicker_aux_expire');
                    $('[field="cron-queue-expiration-date-field'+suffix+'"] td input').removeClass('hasDatepicker').removeAttr('id');
                    $('[field="cron-queue-expiration-date-field'+suffix+'"] td input').attr('placeholder','');
                }else{
                    $('[field="cron-queue-expiration-date-field'+suffix+'"]').hide();
                    $('[field="cron-queue-expiration-date-field'+suffix+'"]').val("");
                }
            });

            $('[name="email-repetitive"],[name="email-repetitive-update"]').on('click', function(e){
                var suffix = '';
                if($(this).attr('name').includes("-update")){
                    suffix = '-update';
                }
                checkSchedule($(this).is(':checked'),suffix,$('[name=cron-send-email-on'+suffix+']:checked').val(),$('[name=cron-send-email-on-field'+suffix+']').val(),$('[name=cron-repeat-for'+suffix+']').val(),$('[name=cron-queue-expiration-date'+suffix+']').val(),$('[name=cron-queue-expiration-date-field'+suffix+']').val());
            });

            $('#addQueue .close').on('click', function () {
                $('#addQueueInstance').html('');
            });

            /***LONGITUDINAL***/
            $('[name=form-name],[name=form-name-update]').on('change', function(e){
                uploadLongitudinalEvent('project_id='+project_id+'&form='+$(this).val(),'[field=form-name-event]');
            });

            $('[name=form_form_name]').on('change', function(e){
                uploadLongitudinalEvent('project_id='+project_id+'&form='+$(this).val(),'[name=form-name-event]');
            });

            $('[name=survey_form_name]').on('change', function(e){
                uploadLongitudinalEvent('project_id='+project_id+'&form='+$(this).val(),'[name=survey-name-event]');
            });

            //we call first the flexalist function to create the options for the email
            $('.flexdatalist').flexdatalist({
                minLength: 1
            });

            $('#btnModalUpdateForm').click(function() {
                if($('[name=cron-queue-update]').is(':checked')){
                    $('#external-modules-configure-modal-schedule-confirmation').modal('show');
                }else{
                    $('#updateForm').submit();
                    $('#external-modules-configure-modal').modal('hide');
                }
            });

            $('#external-modules-configure-modal-record').on('hidden.bs.modal', function () {
                //clean up
                $('[name=preview_record_id]').val('');
                $('#modal_message_record_preview').html('');
            });

            $('#updateForm').submit(function () {
                var data = $('#updateForm').serialize();
                var editor_text = tinymce.activeEditor.getContent();
                data += "&email-text-update-editor="+encodeURIComponent(editor_text);
                data += "&email-to-update="+ encodeURIComponent($('#email-to-update').val());
                data += "&email-cc-update="+encodeURIComponent($('#email-cc-update').val());
                data += "&email-bcc-update="+encodeURIComponent($('#email-bcc-update').val());
                data += "&cron-send-email-on-update="+encodeURIComponent($('input[name="cron-send-email-on-update"]:checked').val());
                data += "&cron-queue-expiration-date="+encodeURIComponent($('input[name="cron-queue-expiration-date"]:checked').val());

                var files = {};
                var max_size_file = "<?=EmailTriggerExternalModule::MAX_FILE_SIZE?>";
                var errMsg = [];
                $('#updateForm').find('input, select, textarea').each(function(index, element){
                    var element = $(element);
                    var name = element.attr('name');
                    var type = element[0].type;
                    if (type == 'file') {
                        name = name.replace("-update", "");
                        // only store one file per variable - the first file
                        jQuery.each(element[0].files, function(i, file) {
                            if (typeof files[name] == "undefined") {
                                if(file.size > max_size_file){
                                    errMsg.push('File <strong>'+file.name+ ' <em>('+file.size+' bytes)</em></strong> is too big. Please reupload a smaller file.');
                                }else{
                                    files[name] = file;
                                }
                            }
                        });
                        if(!showErrorMessage(errMsg, '-update','Update')){
                            return false;
                        }
                    }
                });

                if(checkRequiredFieldsAndLoadOption('-update','Update')){
                    var checkBranchingLogic = '&logic='+$('[name=email-condition-update]').val()+'&logicQueueField='+$('[name=cron-send-email-on-field-update]').val()+'&logicQueueCond='+$('[name=cron-send-email-on-update]:checked').val()+'&logicExpQueueField='+$('[name=cron-queue-expiration-date-field-update]').val()+'&logicExpQueueCond='+$('[name=cron-queue-expiration-date-update]:checked').val();
                    var urlFile = '<?=$module->getUrl('save-file.php')?>';
                    var urlUpdateForm = '<?=$module->getUrl('updateForm.php')?>';
                    var logic = checkBranchingLogicValidAndSave(data, checkBranchingLogic,'<?=$module->getUrl('isBranchingLogicValid.php')?>',urlFile, urlUpdateForm, files, '-update','Update', "U");
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
                ajaxLoadOptionAndMessage(data,'<?=$module->getUrl('deleteFormAdmin.php')?>',"B");
                return false;
            });

            $('#deactivateForm').submit(function () {
                var data = $('#deactivateForm').serialize();
                ajaxLoadOptionAndMessage(data,'<?=$module->getUrl('activateDeactivateForm.php')?>',"");
                return true;
            });

            $('#addQueue').submit(function () {
                var data = $('#addQueue').serialize();

                var errMsg = [];
                $('errMsgContainer').hide();

                if($('#queue_ids').val() == ""){
                    errMsg.push('Please enter a record.');
                }
                if($('#queue_event_select').val() == ""){
                    errMsg.push('Please select an event.');
                }

                if (errMsg.length > 0) {
                    $('#errMsgContainer_modal').empty();
                    $.each(errMsg, function (i, e) {
                        $('#errMsgContainer_modal').append('<div>' + e + '</div>');
                    });
                    $('#errMsgContainer_modal').show();
                    return false;
                }
                else {
                    var queueInstance = $('#queue_instances').val();
                    if(queueInstance == undefined){
                        queueInstance = 1;
                    }
                    var data = "&queue_ids="+$('#queue_ids').val()+"&index_modal_queue="+$('#index_modal_queue').val()+"&times_sent="+$('#times_sent').val()+"&last_sent="+$('#last_sent').val()+"&queue_event_select="+$('#queue_event_select').val()+"&queue_instances="+queueInstance;
                    ajaxLoadOptionAndMessageQueue(data,'<?=$module->getUrl('addQueue.php')?>',"Q");
                    return true;
                }

                return false;
            });

            $('#AddFormForm').submit(function () {
                $('#errMsgModalContainer').hide();
                var errMsg = [];

                if($('#form_form_name').val() == '') {
                    errMsg.push('Please insert a form name.');
                    $('#form_form_name').addClass('alert');
                }else{
                    $('#form_form_name').removeClass('alert');
                }

                if($('#form_label').val() == ''){
                    errMsg.push('Please insert a label name.');
                    $('#form_label').addClass('alert');
                }else{
                    $('#form_label').removeClass('alert');
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
                    var form_alert = '[__FORMLINK_'+$('#form_form_name').val()+']';
                    var event_arm = $('[name=form-name-event] option:selected').attr('event_name');
                    if(isLongitudinal && event_arm != "" && event_arm != undefined){
                        form_alert = '['+event_arm+']'+form_alert;
                    }

                    if ($('[name=form-name-instance]').is(":visible")) {
                        form_alert = form_alert+'['+$('[name=form_instance]').val()+']';
                    }

                    form_alert = form_alert+','+$('#form_label').val();

                    if($('#formLink_var').val() == ''){
                        $('#formLink_var').val(form_alert);
                    }else{
                        $('#formLink_var').val($('#formLink_var').val()+'\n'+form_alert);
                        $('#form_form_name').val('');
                        $('#form_label').val('');
                    }
                    $('#addFormLink').modal('toggle');
                }
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
                    var event_arm = $('#form_event option:selected').attr('event_name');
                    if(isLongitudinal && event_arm != "" && event_arm != undefined){
                        form_alert = '['+event_arm+']'+form_alert;
                    }

                    if($('#surveyLink_var').val() == ''){
                        $('#surveyLink_var').val(form_alert);
                    }else{
                        $('#surveyLink_var').val($('#surveyLink_var').val()+'\n'+form_alert);
                        $('#survey_form_name').val('');
                        $('#survey_label').val('');
                    }
                    $('#addSurveyLink').modal('toggle');
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
                    var column_active = data[5];
                    var column_deleted = data[6];

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
                loadConceptsAJAX_table.column(5).visible(false);
                loadConceptsAJAX_table.column(6).visible(false);

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

        function getInstances(element){
            uploadRepeatableInstances('project_id='+project_id+'&event='+element.value+'&index_modal_queue='+$('#index_modal_queue').val());
        }
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
            <div class="alert alert-success col-md-12" style="border-color: #b2dba1 !important;display: none;" id="succMsgContainer"></div>
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
                        <td style="width: 15%;"><span style="padding-left: 5px;">Enable <strong>Survey Links</strong> in email content</span><div class="description_config">Allows links to REDCap surveys for any survey-enabled form to be inserted into email messages.</div></td>
                        <td style="width: 25%;padding: 10px 30px;">
                            <span class="table_example">Examples: [__SURVEYLINK_form_name], name ...<br/>
                                [survey-link:instrument:Custom Text for Link], button name ...<br/>
                                [survey-url:instrument], button name ...<br/>
                                [survey-queue-link:Custom Text for Link], button name ...<br/>
                                [survey-queue-url], button name ...<br/>
                                [survey-return-code:instrument], button name ...</span><br/>
                            <a id="addLinkBtn" onclick="javascript:$('#addSurveyLink').modal('show');" type="button" class="btn btn-sm pull-right btn_color_surveyLink open-codesModal btn_datapiping" style="margin-bottom:5px;">Add Link</a>
                            <textarea type="text"  name="surveyLink_var" id="surveyLink_var" style="width: 100%;height: 100px;" placeholder="[__SURVEYLINK_form_name], name ..." value="<?=$module->getProjectSetting('surveyLink_var');?>"><?=$module->getProjectSetting('surveyLink_var');?></textarea>
                            <div class="btn_color_square btn_color_surveyLink"></div>Survey link button (orange)
                        </td>
                    </tr>
                    <tr class="panel-collapse collapse EC_collapsed <?=$tr_class?>" aria-expanded="true">
                        <td style="width: 15%;"><span style="padding-left: 5px;">Enable <strong>Data-Form Links</strong> in email content</span><div class="description_config">Allows REDCap links to REDCap Data-Entry Forms to be inserted into email messages.</div></td>
                        <td style="width: 25%;padding: 10px 30px;">
                            <span class="table_example">Example: [__FORMLINK_form_name], name ...</span><br/>
                            <a id="addLinkBtn" onclick="javascript:$('#addFormLink').modal('show');" type="button" class="btn btn-sm pull-right btn_color_formLink open-codesModal btn_datapiping" style="margin-bottom:5px;">Add Link</a>
                            <textarea type="text"  name="formLink_var" id="formLink_var" style="width: 100%;height: 100px;" placeholder="[__FORMLINK_form_name], name ..." value="<?=$module->getProjectSetting('formLink_var');?>"><?=$module->getProjectSetting('formLink_var');?></textarea>
                            <div class="btn_color_square btn_color_formLink"></div>Form link button (yellow)
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
    <div style="width:100%">
        <button type="submit" form="mainForm" class="btn btn-info pull-right email_forms_button" id="SubmitNewConfigureBtn">Save Settings</button>
    </div>
</form>

<?PHP require('codes_modal.php');?>
<!-- ALERTS TABLE -->
<div style="padding-top:50px" class="col-md-12">
    <div style="padding-left: 15px;">
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
                <th style="min-width: 130px">Form</th>
                <th>Scheduled for</th>
                <th>Message</th>
                <th>Attachments</th>
                <th>Options</th>
                <th>Active</th>
                <th>Deleted</th>
                <th class="table_header_options">Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php
            $alerts = "";
            $email_repetitive_sent = $module->getProjectSettingLog($pid,"email-repetitive-sent");
            $email_records_sent = $module->getProjectSettingLog($pid,"email-records-sent");
            $email_timestamp_sent = $module->getProjectSettingLog($pid,"email-timestamp-sent");
            $email_sent_all = $module->getProjectSettingLog($pid,"email-sent");

            $alert_id = $projectData['settings']['alert-id']['value'];
            if (isset($projectData['settings']['email-queue'])) {
                $email_queue = $projectData['settings']['email-queue']['value'];
            } else {
                $email_queue = [];
            }
            for ($index = 0; $index < $indexSubSet; $index++) {
                if (isset($email_sent_all[$index])) {
                    $email_sent = $email_sent_all[$index];
                } else {
                    $email_sent = "";
                }
                $message_sent = "";
                if($email_sent == "1"){
                    if(!empty($email_timestamp_sent[$index])){
                        $message_sent = "<span style='display:block;font-style:italic'>Most recently activated on: ".$email_timestamp_sent[$index]."</span>";
                    }else{
                        $message_sent = "<span style='display:block;font-style:italic'>Email activated</span>";
                    }

                }

                //DEACTIVATE
                if($projectData['settings']['email-deactivate']['value'][$index] == '1'){
                    //Only show when message is not deleted
                    if($projectData['settings']['email-deleted']['value'][$index] != '1'){
                        $message_sent .= "<span style='display:block;font-style:italic;color:red;'>Email deactivated</span>";
                    }

                    $deactivate = "Activate";
                    $active_col = "N";
                    $show_button_active = "display:none;";
                }else{
                    $deactivate = "Deactivate";
                    $active_col = "Y";
                    $show_button_active = "";
                }

                $show_queue = "";
                if($projectData['settings']['email-repetitive']['value'][$index] == '1' || $projectData['settings']['email-deactivate']['value'][$index] == '1' || $projectData['settings']['email-deleted']['value'][$index] == '1'){
                    $show_queue = "display:none;";
                }

                //DELETE
                $deleted_text = "Delete";
                if($projectData['settings']['email-deleted']['value'][$index] == '1'){
                    $deactivated_deleted_text = ($active_col == 'N')?'Email was INNACTIVE when deleted':'Email was ACTIVE when deleted';
                    $message_sent .= "<div><span style='font-style:italic;color:red;line-height: 2;float: left;'>".$deactivated_deleted_text."</span></div><br><br>";
                    $deleted_modal = "external-modules-configure-modal-delete-confirmation";
                    $deleted_index = "index_modal_delete";
                    $deleted_col = "Y";
                    $deleted_text = "Permanently Delete";
                    $show_button = "display:none;";
                    $reactivate_button = "<div><a onclick='reactivateEmailAlert(\"".$index."\",$(\"#concept_active\").is(\":checked\"));' type='button' class='btn btn-success btn-new-email btn-new-email-deactivate'>Re-Enable</a></div>";
                }else{
                    $deleted_modal = "external-modules-configure-modal-delete-user-confirmation";
                    $deleted_index = "index_modal_delete_user";
                    $deleted_col = "N";
                    $show_button = "";
                    $reactivate_button = "";
                }

                if(empty($alert_id)){
                    $alert_number = $index;
                }else{
                    $alert_number = $alert_id[$index];
                }

                if(!empty($email_repetitive_sent)){
                    if(array_key_exists($projectData['settings']['form-name']['value'][$index],$email_repetitive_sent)){
                        $form = $email_repetitive_sent[$projectData['settings']['form-name']['value'][$index]];
                        foreach ($form as $alert =>$value){
                            if((int)$alert == (int)$index){
                                if(!empty($email_records_sent[$alert])){
                                    $record_sent_list = array_unique(explode(', ',$email_records_sent[$index]));
                                    $total_activated = count($record_sent_list);
                                    sort($record_sent_list);
                                    $message_sent .= '<div style="float:left"><a href="#" rel="popover" data-toggle="popover" data-content="'.implode(", ",$record_sent_list).'" data-title="Records for Alert #'.$alert_number.'">Records activated:</a> '.$total_activated.'</div><br/>';
                                    $message_sent .= '<div id="records-activated'.$index.'" class="hidden">
                                                            <p>'.implode(", ",$record_sent_list).'</p>
                                                       </div>';
                                }else{
                                    $results = $module->queryLogs("
                                        select log_id, message, id, value 
                                        where project_id = ? and id = ?
                                        and message = ?
                                        order by log_id desc
                                        limit 2000
                                    ",[$pid,$alert,"email-records-sent"]);

                                    $record_sent_list = array();
                                    while($row = $results->fetch_assoc()){
                                        if(!in_array($row['value'], $record_sent_list, true)){
                                            array_push($record_sent_list, $row['value']);
                                        }
                                    }

                                    $total_activated = count((array)$form[$alert]);
                                    if(!empty($record_sent_list) && $record_sent_list != ""){
                                        sort($record_sent_list);
                                        $message_sent .= '<div style="float:left"><a href="#" rel="popover" data-toggle="popover" data-content="'.implode(", ",$record_sent_list).'" data-title="Records for Alert #'.$alert_number.'">Records activated:</a> '.$total_activated.'</div><br/>';
                                        $message_sent .= '<div id="records-activated'.$alert.'" class="hidden">
                                                            <p>'.implode(", ",$record_sent_list).'</p>
                                                       </div>';
                                    }else{
                                        $message_sent .= "<div style='float:left'>Records activated: ".$total_activated."</div><br/>";
                                    }
                                }
                            }
                        }
                    }
                }

                if(!empty($email_queue)){
                    $queue_count = 0;
                    $scheduled_records_activated = "";
                    foreach ($email_queue as $id=>$email){
                        if($email['project_id'] == $pid && $email['alert'] == $index){
                            $queue_count++;
                            $scheduled_records_activated .= $email['record'].", ";
                        }
                    }

                    if($queue_count > 0){
                        $scheduled_records_activated = explode(",",rtrim($scheduled_records_activated,", "));
                        sort($scheduled_records_activated);
                        $message_sent .= '<div style="float:left"><a href="#" rel="popover" data-toggle="popover" data-content="'.implode(", ",$scheduled_records_activated).'" data-title="Scheduled Records for Alert #'.$alert_number.'">Scheduled records activated:</a> '.$queue_count.'</div><br/>';
                        $message_sent .= '<div id="scheduled-activated'.$index.'" class="hidden">
                                                <p>'.implode(", ",$scheduled_records_activated).'</p>
                                           </div>';
                    }
                }

                $fileAttachments = 0;
                $attachmentVar ='';
                $attachmentFile ='';
                $scheduled_email = '';
                $checkboxes = '';
                $formName = '';
                $msg = '';
                $redcapLogic = '<br>REDCap Logic: <strong>None</strong>';
                $never = false;
                foreach ($config['email-dashboard-settings'] as $configKey => $configRow) {
                    if ($configRow['key'] == 'cron-send-email-on' || $configRow['key'] == 'cron-send-email-on-field' || $configRow['key'] == 'cron-repeat-until-field' || $configRow['key'] == 'cron-repeat-for' || $configRow['key'] == 'cron-queue-expiration-date' || $configRow['key'] == 'cron-queue-expiration-date-field') {
                        //SHCEDULE EMAIL INFO
                        if($projectData['settings']['email-repetitive']['value'][$index] != '1') {
                            if ($configRow['key'] == 'cron-send-email-on') {
                                if ($configRow['value'][$index] == "now" || $configRow['value'][$index] == "") {
                                    $scheduled_email = "Send <strong>now</strong>";
                                } else if ($configRow['value'][$index] == "date") {
                                    $scheduled_email = "Send on " . $configRow['value'][$index];
                                } else if ($configRow['value'][$index] == "calc") {
                                    $scheduled_email = "Send on condition";
                                }
                            }
                            if ($configRow['key'] == 'cron-send-email-on-field' && $configRow['value'][$index] != "") {
                                $scheduled_email .= ": <strong>" . $configRow['value'][$index] . "</strong>";
                            }

                            if ($configRow['key'] == 'cron-repeat-for' && $configRow['value'][$index] != "" && $configRow['value'][$index] != "0") {
                                if($configRow['value'][$index] == 1){
                                    $scheduled_email .= "<br><br>Repeat every day";
                                }else{
                                    $scheduled_email .= "<br><br>Repeat every " . $configRow['value'][$index] . " days";
                                }
                            }
                            if ($configRow['key'] == "cron-queue-expiration-date" && $configRow['value'][$index] != "" && $configRow['value'][$index] != null) {
                                $scheduled_email .= " ";
                                if ($configRow['value'][$index] == "cond") {
                                    $scheduled_email .= "<br><br>Expires on condition: ";
                                }else if ($configRow['value'][$index] == "date") {
                                    $scheduled_email .= "<br><br> Expires on: ";
                                } else {
                                    $scheduled_email .= "<br><br><b>Never</b> Expires";
                                    $never = true;
                                }
                            }
                            if ($configRow['key'] == "cron-queue-expiration-date-field" && $configRow['value'][$index] != "" && !$never) {
                                $scheduled_email .= $configRow['value'][$index] . "";
                            }
                        }else{
                            $scheduled_email = "<i>No scheduled alerts</i>";
                        }
                    }else{
                        //NORMAL EMAIL
                        if ($configRow['type'] == 'file') {
                            if(!empty($configRow['value'][$index])) {
                                $fileAttachments++;
                                $q = $module->query("SELECT stored_name,doc_name,doc_size FROM redcap_edocs_metadata WHERE doc_id=?", [$configRow['value'][$index]]);
                                while ($row = $q->fetch_assoc()) {
                                    $url = "downloadFile.php?sname=".htmlentities($row['stored_name'],ENT_QUOTES)."&file=".htmlentities($row['doc_name'],ENT_QUOTES)."&NOAUTH";
                                    $attachmentFile .= '- <a href="'.$module->getUrl($url).'" target="_blank">'.htmlentities($row['doc_name'],ENT_QUOTES).$module->formatBytes($row['doc_size']).'</a><br/>';
                                }
                            }
                        } else if ($configRow['type'] == 'checkbox') {
                            $value = ($configRow['value'][$index] == 0) ? "No" : "Yes";
                            $checkboxes .= '<span>' .$configRow['name'].' <strong>'. $value . '</strong></span>';
                        } else if ($configRow['key'] == 'alert-name') {
                            $formName .= '<span><strong>'.$configRow['value'][$index].'</strong></span>';
                        } else {
                            if($configRow['key'] == 'form-name') {
                                $event_selected = "";
                                if(\REDCap::isLongitudinal()){
                                    $Project = new \Project($pid);
                                    $event_selected = "<div><strong>Event:</strong> ".$Project->eventInfo[$projectData['settings']['form-name-event']['value'][$index]]['name_ext']."</div>";
                                }

                                if($projectData['settings']['email-deleted']['value'][$index] == '1'){
                                    if($email_sent == "1"){
                                        $formName .= '<span class="email_deleted"><i>Alert #'.$alert_number.'</i> <i class="fas fa-check email_sent" aria-hidden="true"></i></span><span>' . $configRow['value'][$index] . $event_selected . '</span>'.$message_sent.'</td>';
                                    }else{
                                        $formName .= '<span class="email_deleted"><i>Alert #'.$alert_number.'</i></span><span>' . $configRow['value'][$index] . $event_selected . '</span>'.$message_sent.'</td>';
                                    }
                                }else if($email_sent == "1"){
                                    $formName .= '<span class="email_sent"><i>Alert #'.$alert_number.'</i> <i class="fas fa-check" aria-hidden="true"></i></span><span>' . $configRow['value'][$index] . $event_selected . '</span>'.$message_sent.'</td>';
                                }else{
                                    $formName .= '<span><i>Alert #'.$alert_number.'</i></span><span>' . $configRow['value'][$index] . $event_selected . '</span>'.$message_sent.'</td>';
                                }

                            }else if($configRow['key'] == 'email-attachment-variable'){
                                $attchVar = preg_split("/[;,]+/",  $configRow['value'][$index]);
                                foreach ($attchVar as $var){
                                    if(!empty($var)){
                                        $fileAttachments++;
                                        $attachmentVar .= '- '.$var.'<br/>';
                                    }
                                }
                            }else if($configRow['key'] == 'email-to') {
                                $attchVar = preg_split("/[;,]+/",  $configRow['value'][$index]);
                                $dots = "";
                                if(count($attchVar) >= 2){
                                    $dots = "...";
                                }
                                $to_text = substr($configRow['value'][$index], 0, 30) . $dots;
                                $msg .= '<div><span>Send to: '.$to_text . '</span></div>';
                            }else if($configRow['key'] == 'email-subject') {
                                $msg .= '<div><span>'.$configRow['value'][$index] . '</span></div><br>';
                            }else if ($configRow['key'] == 'email-text'){
                                $msg .= '<span><a onclick="previewEmailAlert('.$index.','.$alert_number.')" style="cursor:pointer" >Preview Message</a></span>';
                                if($isAdmin) {
                                    $msg .= '<span><a onclick="previewEmailAlertRecord(' . $index . ','.$alert_number.')" style="cursor:pointer" >Preview Message by Record</a></span>';
                                    $msg .= '<span><a onclick="previewEmailAlertQueue(' . $index . ','.$alert_number.')" style="cursor:pointer" >Preview Queued Emails</a></span>';
                                }
                            }else if ($configRow['key'] == 'email-condition' && $configRow['value'][$index] != ""){
                                $redcapLogic = '<br>REDCap Logic: <strong>'.$configRow['value'][$index].'</strong>';
                            }
                        }
                    }
                    if($configRow['key'] == 'form-name' || $configRow['key'] == 'email-condition' || $configRow['key'] == 'email-subject' || $configRow['key'] == 'email-attachment-variable' || $configRow['key'] == 'cron-send-email-on-field' || $configRow['key'] == 'cron-queue-expiration-date-field'){
                        $info_modal[$index][$configRow['key']] = htmlspecialchars_decode($configRow['value'][$index],ENT_QUOTES);
                    }else if (isset($configRow['value'])) {
                        $info_modal[$index][$configRow['key']] = $configRow['value'][$index];
                    }
                }
                $alerts .= "<tr>";
                $alerts .= "<td data-order='".$alert_number."'>".$formName."</td>";
                $alerts .= "<td>".$scheduled_email."</td>";
                $alerts .= "<td>".$msg."</td>";
                $alerts .= "<td><span style='text-align: center;width: 200px;'><strong>" . $fileAttachments . " files</strong><br/></span>".$attachmentVar.$attachmentFile."</td>";
                $alerts .= "<td>".$checkboxes.$redcapLogic."</td>";
                $alerts .= "<td style='visibility: hidden;'>".$active_col."</td>";
                $alerts .= "<td style='visibility: hidden;'>".$deleted_col."</td>";
                $alerts .= "<td>".$reactivate_button."<div style='".$show_button.$show_button_active."'><a id='emailRow$index' type='button' class='btn btn-info btn-new-email btn-new-email-edit'>Edit Email</a></div>";
                $alerts .= "<div style='".$show_button."'><a onclick='deactivateEmailAlert(".$index.",\"".$deactivate."\");return true;' type='button' class='btn btn-info btn-new-email btn-new-email-deactivate' >".$deactivate."</a></div>";
                $alerts .= "<div style='".$show_button."'><a onclick='duplicateEmailAlert(\"".$index."\");return true;' type='button' class='btn btn-success btn-new-email btn-new-email-deactivate' >Duplicate</a></div>";
                if($isAdmin) {
                    $alerts .= "<div><a onclick='addQueue(\"".$index."\",\"".$info_modal[$index]['form-name']."\");return true;' style='".$show_queue."' id='addQueueBtn' type='button' class='btn btn-warning btn-new-email' >Add Queue</a></div>";
                }
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

    <div class="modal fade" id="addFormLink" tabindex="-1" role="dialog" aria-labelledby="Codes">
        <form class="form-horizontal" action="" method="post" id='AddFormForm'>
            <div class="modal-dialog" role="document" style="width: 500px">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close closeCustomModal" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title" id="myModalLabel">Add a data-form link</h4>
                    </div>
                    <div class="modal-body">
                        <div id='errMsgModalContainer' class="alert alert-danger col-md-12" role="alert" style="display:none;margin-bottom:20px;"></div>
                        <div><i>Once the data-form link is added, remember to click on <strong>Save Settings</strong> button to save the changes.</i></div>
                        <br/>
                        <table class="code_modal_table">
                            <tr class="form-control-custom">
                                <td>Form name:</td>
                                <td>
                                    <select class="external-modules-input-element" name="form_form_name" id="form_form_name">
                                        <option value=""></option>
                                        <?php
                                        foreach ($simple_config['email-dashboard-settings'][1]['choices'] as $choice){
                                            echo '<option value="'.$choice['value'].'">'.$choice['name'].'</option>';
                                        }
                                        ?>
                                    </select>
                                </td>
                            </tr>
                            <tr name="form-name-event" class="form-control-custom" style="display:none"></tr>
                            <tr name="form-name-instance" class="form-control-custom" style="display:none">
                                <td></td>
                                <td><select class='external-modules-input-element' id='form_instance' name='form_instance'>
                                    <option value='next-instance' selected>New instance</option>
                                    <option value='first-instance'>First instance</option>
                                    <option value='last-instance'>Last instance</option>
                                </select></td>
                            </tr>

                            <tr class="form-control-custom">
                                <td>Label:</td>
                                <td><input type="text" name="form_label" id="form_label" placeholder="Name"></td>
                            </tr>
                        </table>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                        <button type="submit" form="AddFormForm" class="btn btn-default btn_color_formLink" id='btnModalAddFormForm'>Add data-form link</button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="modal fade" id="addSurveyLink" tabindex="-1" role="dialog" aria-labelledby="Codes">
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
                                        foreach ($simple_config['email-dashboard-settings'][1]['choices'] as $choice){
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
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                        <button type="submit" form="AddSurveyForm" class="btn btn-default btn_color_surveyLink" id='btnModalAddSurveyForm'>Add survey link</button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="col-md-12">
        <form class="form-horizontal" action="" method="post" id='updateForm'>
            <div class="modal fade" id="external-modules-configure-modal-update" name="external-modules-configure-modal-update" data-module="<?=$prefix;?>" tabindex="-1" role="dialog" aria-labelledby="Codes">
                <div class="modal-dialog" role="document" style="width: 800px">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title float-left" id="myModalLabel">Configure Email Alerts</h4>
                            <button type="button" class="close closeCustomModal float-right" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        </div>

                        <div class="modal-body">
                            <div id='errMsgContainerModalUpdate' class="alert alert-danger col-md-12" role="alert" style="display:none;margin-bottom:20px;"></div>
                            <table class="code_modal_table" id="code_modal_table_update"></table>
                            <input type="hidden" value="" id="index_modal_update" name="index_modal_update">
                        </div>

                        <div class="modal-footer">
                            <a class="btn btn-default btn-cancel" id='btnCloseCodesModal' data-dismiss="modal">Cancel</a>
                            <a class="btn btn-default save" id='btnModalUpdateForm'>Save</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="external-modules-configure-modal-schedule-confirmation" tabindex="-1" role="dialog" aria-labelledby="Codes">
                <form class="form-horizontal" action="" method="post" id='scheduleForm'>
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="close closeCustomModal" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                <h4 class="modal-title" id="myModalLabel">Reschedule Email Alert</h4>
                            </div>
                            <div class="modal-body">
                                <span>Are you sure you want to reschedule emails?</span>
                                <br/>
                                <span style="color:red;font-weight: bold">*This will update piping content and also update logic.</span>
                            </div>

                            <div class="modal-footer">
                                <button type="submit" form="updateForm" class="btn btn-default btn-delete" id='btnModalRescheduleForm'>Reschedule</button>
                                <a class="btn btn-default btn-cancel" data-dismiss="modal">Cancel</a>
                            </div>
                        </div>
                    </div>
                </form>
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
                        <a class="btn btn-default btn-cancel" data-dismiss="modal">Cancel</a>
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
                    </div>

                    <div class="modal-footer">
                        <button type="submit" form="deleteForm" class="btn btn-default btn-delete" id='btnModalDeleteForm'>Delete</button>
                        <a class="btn btn-default btn-cancel" data-dismiss="modal">Cancel</a>
                    </div>
                </div>
            </div>
        </form>
    </div>


    <div class="modal fade" id="external-modules-configure-modal-record" tabindex="-1" role="dialog" aria-labelledby="Codes">
        <form class="form-horizontal" action="" method="post" id='selectPreviewRecord'>
            <div class="modal-dialog" role="document" style="width: 800px">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close closeCustomModal" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title" id="myModalLabel">Record Preview <span id="modalRecordNumber"></span></h4>
                    </div>
                    <div class="modal-body form-control-custom">
                        <div style="padding-bottom: 10px;">Select a record to preview the email</div>
                        <div id="load_preview_record"></div>
                        <div>
                            <input type="hidden" value="" id="index_modal_record_preview" name="index_modal_record_preview">
                            <input type="hidden" value="<?=$module->getUrl('previewFormRecord.php')?>" id="url_modal_delete_user" name="url_modal_delete_user">
                            <div id="modal_message_record_preview"></div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="modal fade" id="external-modules-configure-modal-queue" tabindex="-1" role="dialog" aria-labelledby="Codes">
        <form class="form-horizontal" action="" method="post" id='selectPreviewQueue'>
            <div class="modal-dialog modal-dialog-preview-queue" role="document" style="width: 950px">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close closeCustomModal" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title" id="myModalLabel">Preview Queued Emails <span id="modalQueueNumber"></span></h4>
                        <input type="hidden" id="alertid" name="alertid" value="">
                    </div>
                    <div class="modal-body">
                        <div id="modal_message_queue"></div>
                    </div>

                    <div class="modal-footer">
                        <?php
                        if($isAdmin) {
                        ?>
                        <button type="button" class="btn btn-danger" onclick="
                                       $('#external-modules-configure-modal-queue').modal('hide');
									simpleDialog('Are you sure you want to delete All queued emails? This means that ALL queued emails for this alert will be permanently deleted.','ARE YOU SURE?',null,null,function(){},'Cancel',function(){deleteAllQueue();},'Yes, I understand');
                               ">Delete All</button>
                        <?php } ?>
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="modal fade" id="external-modules-configure-modal-addQueue" tabindex="-1" role="dialog" aria-labelledby="Codes">
        <form class="form-horizontal" action="" method="post" id='addQueue'>
            <div class="modal-dialog modal-dialog-queue" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close closeCustomModal" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title" id="myModalLabel">Add a Queue Email</h4>
                    </div>
                    <div class="modal-body">
                        <div id='errMsgContainer_modal' class="alert alert-danger col-md-12" role="alert" style="display:none;margin-bottom:20px;"></div>
                        <div style="padding: 20px;">
                            <div class="form-group">
                                <div style="float: left;"><label style="font-weight: normal;padding-left: 15px;padding-right: 15px;color:red">*This is to add records that are not in the queue or that have been deleted.</label></div>
                            </div>
                            <div class="form-group" style="display: inline-block;">
                                <div style="float:left;width: 280px;"><label style="font-weight: normal;padding-left: 15px;padding-right: 15px">Event ID</label></div>
                                <div id='event_queue' class="float-left"></div>
                            </div>
                            <div class="form-group" style="display: inline-block;" id="addQueueInstance"></div>
                            <div class="form-group" style="display: inline-block;">
                                <div style="float: left;width: 280px;"><label style="font-weight: normal;padding-left: 15px;padding-right: 15px">Date record was last sent via the queue<br><span style="color:red">*This value only needs to be entered if record was previously in queue</span></label></div>
                                <div class="float-left"><input type="text" id='last_sent' class="external-modules-input-element" placeholder="YYYY-MM-DD"></div>
                            </div>
                            <div class="form-group" style="display: inline-block;">
                                <div style="float: left;width: 280px;"><label style="font-weight: normal;padding-left: 15px;padding-right: 15px">Number of times the email has previously been sent for the records added below.<br><span style="color:red">*0 if you want to send it right now, otherwise enter a number.</span></div>
                                <div class="float-left"><input type="text" id='times_sent' value="0"></div>
                            </div>
                            <div class="form-group" style="display: inline-block;">
                                <div>
                                    <label style="font-weight: normal;padding-left: 15px;padding-right: 15px">
                                        <span style="color:red">Example of use:<br>Date the email will be sent = Alert date + (Alert repeating days * <b>Times Sent</b>)<br>2018-07-15 = 2018-07-09 + (3 * 2)</span>
                                    </label>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="exampleFormControlTextarea1" style="font-weight: normal;padding-left: 15px;">Insert the <strong>Record ID's</strong> to automatically Add the Queues Emails.<br/>
                                    <em>ID's can be separated by comma, semicolon or line break (not mixed).</em></label>
                            </div>
                            <div class="form-group">
                                <textarea class="form-control" id="queue_ids" rows="6"></textarea>
                            </div>
                        </div>
                        <input type="hidden" value="" id="index_modal_queue" name="index_modal_queue">
                    </div>

                    <div class="modal-footer">
                        <a class="btn btn-default btn-cancel" data-dismiss="modal">Cancel</a>
                        <button type="submit" form="addQueue" class="btn btn-default btn-delete" id='btnModalAddQueue'>Add Queue</button>
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
                        <a class="btn btn-default btn-cancel" data-dismiss="modal">Cancel</a>
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
                    <h4 class="modal-title" id="myModalLabel">Preview Email <span id="modalPreviewNumber"></span></h4>
                </div>
                <div class="modal-body">
                    <div id="modal_message_preview"></div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>
