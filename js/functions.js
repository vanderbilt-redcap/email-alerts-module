/**
 * Function to preview the message on the alerts table
 * @param index, the alert id
 */
function previewEmailAlert(index,alertnumber){
    var data = "&index_modal_preview="+index;
    $.ajax({
        type: "POST",
        url: _preview_url,
        data: data,
        error: function (xhr, status, error) {
            alert(xhr.responseText);
        },
        success: function (result) {
            // console.log(result)
            $('#modal_message_preview').html(result);
            $('#modalPreviewNumber').text("- Alert #"+alertnumber);
            $('#external-modules-configure-modal-preview').modal('show');
        }
    });
}

function previewEmailAlertRecord(index,alertnumber){
    $('#index_modal_record_preview').val(index)
    $('#modalRecordNumber').text("- Alert #"+alertnumber);


    var data = "&index_modal_alert="+index;
    $.ajax({
        type: "POST",
        url: _preview_record_modal_url,
        data: data,
        error: function (xhr, status, error) {
            alert(xhr.responseText);
        },
        success: function (result) {
            $('#load_preview_record').html(result);
            $('#external-modules-configure-modal-record').modal('show');
        }
    });

}

function previewEmailAlertQueue(index,alertnumber){
    var data = "&index_modal_queue="+index;
    $.ajax({
        type: "POST",
        url: _preview_queue_url,
        data: data,
        error: function (xhr, status, error) {
            alert(xhr.responseText);
        },
        success: function (result) {
            // console.log(result)
            $('#modal_message_queue').html(result);
            $('#modalQueueNumber').text("- Alert #"+alertnumber);
            $('#external-modules-configure-modal-queue').modal('show');
            $('#alertid').val(index);
        }
    });
}
function deleteEmailAlertQueue(index,alertid){
    ajaxLoadOptionAndMessage("&index_modal_queue="+index+"&pid="+project_id+"&alertid="+alertid,_delete_queue_url,"O");
}

function loadPreviewEmailAlertRecord(value){
    if(value == ""){
        $('#modal_message_record_preview').html("");
    }else{
        var data = $('#selectPreviewRecord').serialize();
        $.ajax({
            type: "POST",
            url: _preview_record_url,
            data: data,
            error: function (xhr, status, error) {
                alert(xhr.responseText);
            },
            success: function (result) {
                $('#modal_message_record_preview').html(result);
            }
        });
    }
}

function checkSchedule(repetitive,suffix,cron_send_email_on,cron_send_email_on_field,cron_repeat_for,cron_expiration_date,cron_expiration_date_field){
    if(repetitive == '1'){
        $('.email-schedule-title'+suffix).hide();
        $('[field="cron-send-email-on'+suffix+'"]').hide();
        $('[field="cron-send-email-on-field'+suffix+'"]').hide();
        $('[field="cron-repeat-for'+suffix+'"]').hide();
        $('[field="cron-queue-expiration-date'+suffix+'"]').hide();
        $('[field="cron-queue-expiration-date-field'+suffix+'"]').hide();
        $('[field="cron-queue-update"]').hide();
        $('[field="cron-queue-send-label'+suffix+'"]').hide();
    }else{
        $('.email-schedule-title'+suffix).show();
        $('[field="cron-send-email-on'+suffix+'"]').show();
        $('[field="cron-queue-expiration-date'+suffix+'"]').show();
        $('[field="cron-queue-expiration-date-field'+suffix+'"]').show();
        $('[field="cron-queue-update"]').show();
        $('[field="cron-queue-send-label'+suffix+'"]').show();

        if(cron_repeat_for == ""){
            cron_repeat_for = "0";
        }

        $('[field="cron-repeat-for'+suffix+'"]').show();
        $('[name=external-modules-configure-modal'+suffix+'] input[name="cron-repeat-for'+suffix+'"]').val(cron_repeat_for);

        if(cron_send_email_on == "" || cron_send_email_on == undefined || cron_send_email_on == null){
            $('[name=external-modules-configure-modal'+suffix+'] input[name="cron-send-email-on'+suffix+'"][value="now"]').prop('checked',true);
            $('[field="cron-send-email-on-field'+suffix+'"]').hide();
            $('[field="cron-send-email-on-field'+suffix+'"]').val('');
        }else{
            $('[name=external-modules-configure-modal'+suffix+'] input[name="cron-send-email-on'+suffix+'"][value="'+cron_send_email_on+'"]').prop('checked',true);
            if(cron_send_email_on == 'date' || cron_send_email_on == 'calc'){
                $('[field="cron-send-email-on-field'+suffix+'"]').show();
                $('[name=external-modules-configure-modal'+suffix+'] input[name="cron-send-email-on-field'+suffix+'"]').val(cron_send_email_on_field);
                if(cron_send_email_on == 'date'){
                    $('[field="cron-send-email-on-field'+suffix+'"] td input').addClass('datepicker_aux');
                    $('[field="cron-send-email-on-field'+suffix+'"] td input').addClass('datepicker');
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
                }
            }else{
                $('[field="cron-send-email-on-field'+suffix+'"]').hide();
            }
        }

        if(cron_expiration_date == "" || cron_expiration_date == undefined || cron_expiration_date == null || cron_expiration_date == 'never') {
            $('[name=external-modules-configure-modal'+suffix+'] input[name="cron-queue-expiration-date'+suffix+'"][value="never"]').prop('checked',true);
            $('[field="cron-queue-expiration-date-field'+suffix+'"]').hide();
        }else if(cron_expiration_date == 'date'){
            $('[field="cron-queue-expiration-date-field'+suffix+'"]').show();
            $('[name=external-modules-configure-modal'+suffix+'] input[name="cron-queue-expiration-date'+suffix+'"][value="date"]').prop('checked',true);
            $('[field="cron-queue-expiration-date-field'+suffix+'"] td input').addClass('datepicker_aux_expire');
            $('[field="cron-queue-expiration-date-field'+suffix+'"] td input').addClass('datepicker');
            $(".datepicker_aux_expire").datepicker({
                showOn: "button",
                buttonImage: calendarimg,
                buttonImageOnly: true,
                buttonText: "Select date",
                dateFormat: "yy-mm-dd"
            });
        }else{
            $('[field="cron-queue-expiration-date-field'+suffix+'"]').show();
            $('[name=external-modules-configure-modal'+suffix+'] input[name="cron-queue-expiration-date'+suffix+'"][value="'+cron_expiration_date+'"]').prop('checked',true);
            $('[field="cron-queue-expiration-date-field'+suffix+'"] td input').datepicker("destroy");
            $('[field="cron-queue-expiration-date-field'+suffix+'"] td input').removeClass('datepicker');
            $('[field="cron-queue-expiration-date-field'+suffix+'"] td input').removeClass('datepicker_aux_expire');
            $('[field="cron-queue-expiration-date-field'+suffix+'"] td input').removeClass('hasDatepicker').removeAttr('id');
        }
        $('[name=external-modules-configure-modal'+suffix+'] input[name="cron-queue-expiration-date-field'+suffix+'"]').val(cron_expiration_date_field);
    }
}

/**
 * Function that shows the modal with the alert information to modify it
 * @param modal, array with the data from a specific aler
 * @param index, the alert id
 */
function editEmailAlert(modal, index){
    tinymce.remove();
	ExternalModules.Settings.projectList = [];
    EMparentAux.configureSettings(configSettingsUpdate, configSettingsUpdate);

    for(var i=0; i<tinymce.editors.length; i++){
        var editor = tinymce.editors[i];
        editor.on('focus', function(e) {
            lastClick = null;
        });
        editor.on('init', function () {
            editor.setContent(modal['email-text'])
        });
    }
    $("#index_modal_update").val(index);

    $('[name="email-attachment-variable-update"]').attr('placeholder','[variable1], [variable2], ...');
    $('[name="email-from-update"]').attr('placeholder','myemail@server.com, "Sender name"');
    $('[name="cron-queue-update"]').prop('checked',false);

    //Add values
    $('[name=external-modules-configure-modal-update] input[name="alert-name-update"]').val(modal['alert-name']);
    $('[name=external-modules-configure-modal-update] select[name="form-name-update"]').val(modal['form-name']);
    $('[name=external-modules-configure-modal-update] input[name="email-from-update"]').val(modal['email-from']);

    $('#email-to-update').val(modal['email-to']);
    $('#email-cc-update').val(modal['email-cc']);
    $('#email-bcc-update').val(modal['email-bcc']);

    $('[name=external-modules-configure-modal-update] input[name="email-cc-update"]').val(modal['email-cc']);
    $('[name=external-modules-configure-modal-update] input[name="email-bcc-update"]').val(modal['email-bcc']);
    $('[name=external-modules-configure-modal-update] input[name="email-subject-update"]').val(modal['email-subject']);
    $('[name=external-modules-configure-modal-update] textarea[name="email-text-update"]').val(modal['email-text']);
    $('[name=external-modules-configure-modal-update] input[name="email-attachment-variable-update"]').val(modal['email-attachment-variable']);
    $('[name=external-modules-configure-modal-update] input[name="email-repetitive-update"]').val(modal['email-repetitive']);
    $('[name=external-modules-configure-modal-update] input[name="email-condition-update"]').val(modal['email-condition']);
    $('[name=external-modules-configure-modal-update] input[name="email-incomplete-update"]').val(modal['email-incomplete']);
    $('[name=external-modules-configure-modal-update] input[name="cron-queue-update"]').val(modal['cron-queue-update']);


    //If logic fails due to quotes and single quotes together. Remove double quotes and reassess option
    modal['cron-send-email-on'].replaceAll('"', "'")
    if(modal['cron-send-email-on'] != 'date' && modal['cron-send-email-on'] != 'calc' && modal['cron-send-email-on'] != 'now'){
        if(dateIsValid(modal['cron-send-email-on-field'])){
            modal['cron-send-email-on'] = "date";
        }else if(modal['cron-send-email-on-field'] != ""){
            modal['cron-send-email-on'] = "calc";
        }else{
            modal['cron-send-email-on'] = "now";
        }
    }
    modal['cron-queue-expiration-date'].replaceAll('"', "'")
    if(modal['cron-queue-expiration-date'] != 'date' && modal['cron-queue-expiration-date'] != 'cond' && modal['cron-queue-expiration-date'] != 'never'){
        if(dateIsValid(modal['cron-queue-expiration-date-field'])){
            modal['cron-queue-expiration-date'] = "date";
        }else if(modal['cron-queue-expiration-date-field'] != ""){
            modal['cron-queue-expiration-date'] = "cond";
        }else{
            modal['cron-queue-expiration-date'] = "never";
        }
    }

    checkSchedule(modal['email-repetitive'],'-update',modal['cron-send-email-on'],modal['cron-send-email-on-field'],modal['cron-repeat-for'],modal['cron-queue-expiration-date'],modal['cron-queue-expiration-date-field']);

    uploadLongitudinalEvent('project_id='+project_id+'&form='+modal['form-name']+'&index='+index,'[field=form-name-event]');

    //Add Files
    for(i=1; i<6 ; i++){
        getFileFieldElement(modal['email-attachment'+i], i);
    }

    //Checkboxes
    $('[name=external-modules-configure-modal-update] input[name="email-repetitive-update"]').prop('checked',false);
    if(modal['email-repetitive'] == '1'){
        $('[name=external-modules-configure-modal-update] input[name="email-repetitive-update"]').prop('checked',true);
    }

    $('[name=external-modules-configure-modal-update] input[name="email-incomplete-update"]').prop('checked',false);
    if(modal['email-incomplete'] == '1'){
        $('[name=external-modules-configure-modal-update] input[name="email-incomplete-update"]').prop('checked',true);
    }

    //clean up error messages
    $('#errMsgContainerModalUpdate').empty();
    $('#errMsgContainerModalUpdate').hide();
    $('[name=external-modules-configure-modal-update] input[name=form-name]').removeClass('alert');
    $('[name=external-modules-configure-modal-update] input[name=email-to]').removeClass('alert');
    $('[name=external-modules-configure-modal-update] input[name=email-subject]').removeClass('alert');
    $('[name=external-modules-configure-modal-update] [name=email-text]').removeClass('alert');


    //Show modal
    $('[name=external-modules-configure-modal-update]').modal('show');

}

/***FILES***/
function getAttributeValueHtml(s){
    if(typeof s == 'string'){
        s = s.replace(/"/g, '&quot;');
        s = s.replace(/'/g, '&apos;');
    }

    if (typeof s == "undefined") {
        s = "";
    }

    return s;
}

function getFileFieldElement(value, file_number){
    var name = "email-attachment"+file_number+"-update";
    if ((typeof value != "undefined") && (value !== "" && value != null)) {
        var html = '<input type="hidden" name="' + name + '" value="' + getAttributeValueHtml(value) + '" >';
        html += '<span class="external-modules-edoc-file"></span>';
        html += '<button class="external-modules-configure-modal-delete-file" onclick="hideFile('+value+','+file_number+')">Delete File</button>';

        $.post(_edoc_name_url+'?' + pid, { edoc : value }, function(data) {
            $("[name='"+name+"']").closest("tr").find(".external-modules-edoc-file").html("<b>" + data.doc_name + "</b><br>");
        });
    } else {
        var html = '<input type="file" name="' + name + '" value="' + getAttributeValueHtml(value) + '" class="external-modules-input-element">';
    }
    $('[name=external-modules-configure-modal-update] input[name="email-attachment'+file_number+'-update"]').parent().html(html);
}

function hideFile(value,file_number){
    var name = "email-attachment"+file_number;
    var html = '<input type="file" name="' + name + '-update" value="" class="external-modules-input-element">';
    html += '<input type="hidden" name="'+name+'" value="'+value+'" class="external-modules-input-element deletedFile">';
    $('[name=external-modules-configure-modal-update] input[name="email-attachment'+file_number+'-update"]').parent().html(html);
}

function deleteEmailAlert(index,modal,indexmodal){
    $('#'+indexmodal).val(index);
    $('#'+modal).modal('show');
}
function reactivateEmailAlert(index,active){
   ajaxLoadOptionAndMessage("&index_reenable="+index+"&active="+active,_reenableform_url,"R");
}
function deactivateEmailAlert(index, status){
    $('#index_modal_deactivate').val(index);
    $('#index_modal_status').val(status);
    $('#btnModalDeactivateForm').html(status);
    $('#index_modal_message').html('Are you sure you want to '+status+' this Email Alert?');
    $('#external-modules-configure-modal-deactivate-confirmation').modal('show');
}
function duplicateEmailAlert(index){
    ajaxLoadOptionAndMessage("&index_duplicate="+index,_duplicateform_url,"P");
}

function addQueue(index,form){
    $('#external-modules-configure-modal-addQueue').modal('show');
    $('#index_modal_queue').val(index);
    $('#last_sent').addClass('datepicker_queue');
    $(".datepicker_queue").datepicker({
        showOn: "button",
        buttonImage: calendarimg,
        buttonImageOnly: true,
        buttonText: "Select date",
        dateFormat: "yy-mm-dd"
    });
    $(".ui-datepicker-trigger").attr('style',' padding-bottom: 10px !important;');
    var data = "index="+index+"&project_id="+project_id+"&form="+form+"&queue=1";
    $.ajax({
        type: "POST",
        url: _longitudinal_url,
        data: data,
        error: function (xhr, status, error) {
            alert(xhr.responseText);
        },
        success: function (result) {
            jsonAjax = jQuery.parseJSON(result);
            // console.log(jsonAjax)
            $('#event_queue').html(jsonAjax.event);
        }
    });
}

function updateQueueData(index){
    var data = "&index_modal_queue="+index;
    $.ajax({
        type: "POST",
        url: _update_queue_url,
        data: data,
        error: function (xhr, status, error) {
            alert(xhr.responseText);
        },
        success: function (result) {
        }
    });
}

/**
 * We save the last click value on focus to know which element the button has to update to
 * @param element
 */
function flexalistFocus(element){
    var id = $(element).attr("id");
    if(id == undefined){
        var name = '[name="'+$(element).attr("name")+'"]';
        var id = $(element).attr("name");
    }else{
        var name = '#'+$(element).attr("id");
    }
    lastClick = name;
}

//We insert the button text depending on which field we are
/**
 * Function to add the cursor position so the text from the data piping buttons can be added on those specific fields
 * @param myValue
 * @param option
 */
function insertAtCursorTinyMCE(myValue,option) {
    if(lastClick != '') {
        if(letButtonAddContent(lastClick, option)) {
            if (lastClick != null) {
                var myField = $(lastClick);
                var elementflexalist = '<li class="value"><span class="text">' + myValue + '</span><span class="fdl-remove">×</span></li>';
                var varId = lastClick.replace(/-flexdatalist/g, '');

                //IE support
                if (document.selection) {
                    myField.focus();
                    sel = document.selection.createRange();
                    sel.text = myValue;
                }
                if (varId == '#email-cc' || varId == '#email-to' || varId == '#email-bcc' || varId == '#email-cc-update' || varId == '#email-bcc-update' || varId == '#email-to-update') {
                    myField.parent().before(elementflexalist);
                    if ($(varId).val() == "") {
                        $(varId).val(myValue);
                    } else {
                        $(varId).val($(varId).val() + "," + myValue);
                    }
                } else {
                    //MOZILLA and others
                    if (startPos || startPos == '0') {
                        myField.val(myField.val().substring(0, startPos) + myValue + myField.val().substring(endPos, myField.val().length));
                        myField.selectionStart = startPos + myValue.length;
                        myField.selectionEnd = startPos + myValue.length;
                    } else {
                        myField.val(myField.val() + myValue);
                    }
                }

                //We update positions to add next text after the new one
                startPos = startPos + myValue.length;
                endPos = startPos + myValue.length;
            } else {
                if (tinymce.isIE) {
                    // tinyMCE.activeEditor.selection.moveToBookmark(actualCaretPositionBookmark);
                    tinyMCE.execCommand('mceInsertContent', false, myValue);
                } else {
                    tinyMCE.execCommand('insertHTML', false, myValue);
                }
            }
        }
    }

}

/**
 * Function that controls the logic on the buttons
 * @param element
 * @param type
 * @returns {boolean}
 */
function letButtonAddContent(element, type){
    if(type == 0){
        //Email buttons
        if(element =='#email-cc-flexdatalist' || element =='#email-to-flexdatalist' || element =='#email-bcc-flexdatalist' || element =='#email-to-update-flexdatalist' || element =='#email-cc-update-flexdatalist' || element =='#email-bcc-update-flexdatalist'){
            return true;
        }
    }else if( type == 1){
        //Data piping buttons
        return true;
    }else if(type == 2){
        //Survey buttons
        if(element !='#email-cc-flexdatalist' && element !='#email-to-flexdatalist' && element !='#email-bcc-flexdatalist' && element !='#email-to-update-flexdatalist' && element !='#email-cc-update-flexdatalist' && element !='#email-bcc-update-flexdatalist' && element !='[name="email-attachment-variable"]' && element !='[name="email-attachment-variable-update"]' && element !='[name="email-subject"]' && element !='[name="email-subject-update"]'){
            return true;
        }
    }
    return false;
}

/**
 * Function that reloads the page and updates the success message
 * @param letter
 * @returns {string}
 */
function gerUtlMessageParam(letter){
    var url = window.location.href;
    if (url.substring(url.length-1) == "#")
    {
        url = url.substring(0, url.length-1);
    }
    if(window.location.href.match(/(&message=)([A-Z]{1})/)){
        url = url.replace( /(&message=)([A-Z]{1})/, "&message="+letter );
    }else{
        url = url + "&message="+letter;
    }
    return url;
}

function validateEmail(email) {
    var re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(email);
}

/**
 * Function that checks if all required fields form the alerts are filled @param errorContainerSuffix
 * @returns {boolean}
 */
function checkRequiredFieldsAndLoadOption(suffix, errorContainerSuffix){
    $('#succMsgContainer').hide();
    $('#errMsgContainerModal'+errorContainerSuffix).hide();

    var errMsg = [];
    if ($('[name=external-modules-configure-modal'+suffix+'] input[name=email-to'+suffix+']').val() === "" || $('[name=external-modules-configure-modal'+suffix+'] input[name=email-to'+suffix+']').val() === "0") {
        errMsg.push('Please insert an <strong>email receiver</strong>.');
        $('[name=external-modules-configure-modal'+suffix+'] input[name=email-to'+suffix+']').addClass('alert');
    }else{ $('[name=external-modules-configure-modal'+suffix+'] input[name=email-to'+suffix+']').removeClass('alert');}

    if ($('[name=external-modules-configure-modal'+suffix+'] input[name=email-subject'+suffix+']').val() === "" || $('[name=external-modules-configure-modal'+suffix+'] input[name=email-subject'+suffix+']').val() === "0") {
            errMsg.push('Please insert an <strong>email subject</strong>.');
            $('[name=external-modules-configure-modal' + suffix + '] input[name=email-subject' + suffix + ']').addClass('alert');
    }else{ $('[name=external-modules-configure-modal'+suffix+'] input[name=email-subject'+suffix+']').removeClass('alert');}

    if ($('[name=external-modules-configure-modal'+suffix+'] select[name=form-name'+suffix+']').val() === "" || $('[name=external-modules-configure-modal'+suffix+'] select[name=form-name'+suffix+']').val() === "0") {
        errMsg.push('Please select a <strong>Form</strong>.');
        $('[name=external-modules-configure-modal'+suffix+'] select[name=form-name'+suffix+']').addClass('alert');
    }else{ $('[name=external-modules-configure-modal'+suffix+'] select[name=form-name'+suffix+']').removeClass('alert');}

    if ($('[name=external-modules-configure-modal'+suffix+'] input[name=email-attachment-variable'+suffix+']').val() != "") {
        var result = $('[name=external-modules-configure-modal'+suffix+'] input[name=email-attachment-variable'+suffix+']').val().split(",");
        var errorInField = false;
        for(var i=0;i<result.length;i++){
            if(trim(result[i]).substring(0, 1) != "[" || trim(result[i]).substring(trim(result[i]).length-1, trim(result[i]).length) != "]"){
                errorInField = true;
                break;
            }
        }
        if(errorInField == true) {
            errMsg.push('<strong>Email Attachment as variables</strong> must follow the format: <i>[variable1],[variable2],...</i>.');
            $('[name=external-modules-configure-modal'+suffix+'] input[name=email-attachment-variable'+suffix+']').addClass('alert');
        }else{
            $('[name=external-modules-configure-modal'+suffix+'] input[name=email-attachment-variable'+suffix+']').removeClass('alert');
        }
    }

    if ($('[name=external-modules-configure-modal'+suffix+'] input[name=email-from'+suffix+']').val() === "" || $('[name=external-modules-configure-modal'+suffix+'] input[name=email-from'+suffix+']').val() === "0") {
        errMsg.push('Please insert an <strong>email sender</strong>.');
        $('[name=external-modules-configure-modal'+suffix+'] input[name=email-from'+suffix+']').addClass('alert');
    }else{ $('[name=external-modules-configure-modal'+suffix+'] input[name=email-from'+suffix+']').removeClass('alert');}

    if(!$('[name=external-modules-configure-modal'+suffix+'] input[name=email-repetitive-update'+suffix+']').is(':checked') && $('[name=external-modules-configure-modal'+suffix+'] input[name=cron-repeat-email'+suffix+']').is(':checked')){
        if (($('[name=external-modules-configure-modal'+suffix+'] input[name=cron-repeat-until'+suffix+']:checked').val() == "date" && $('[name=external-modules-configure-modal'+suffix+'] input[name=cron-queue-expiration-date'+suffix+']:checked').val() == "date") || ($('[name=external-modules-configure-modal'+suffix+'] input[name=cron-repeat-until'+suffix+']:checked').val() == "cond" && $('[name=external-modules-configure-modal'+suffix+'] input[name=cron-queue-expiration-date'+suffix+']:checked').val() == "cond")) {
            if($('[name=external-modules-configure-modal'+suffix+'] input[name=cron-repeat-until-field'+suffix+']').val() != $('[name=external-modules-configure-modal'+suffix+'] input[name=cron-queue-expiration-date-field'+suffix+']').val()){
                errMsg.push('If the Expiration is the same type as the Repeat Until, their values have to be the same.');
                $('[name=external-modules-configure-modal'+suffix+'] input[name=cron-repeat-until-field'+suffix+']').addClass('alert');
                $('[name=external-modules-configure-modal'+suffix+'] input[name=cron-queue-expiration-date-field'+suffix+']').addClass('alert');
            }
        }else{ $('[name=external-modules-configure-modal'+suffix+'] select[name=form-name'+suffix+']').removeClass('alert');}
    }

    if(!$('[name=external-modules-configure-modal'+suffix+'] input[name=email-repetitive'+suffix+']').is(':checked')){
        if($('[name=external-modules-configure-modal'+suffix+'] input[name=cron-repeat-for'+suffix+']').val() == ""){
            errMsg.push("<strong>Repeat every # number of days </strong>can't be blank. Please enter a number");
            $('[name=external-modules-configure-modal'+suffix+'] input[name=cron-repeat-for'+suffix+']').addClass('alert');
        }else{
            var integer = ($('[name=external-modules-configure-modal'+suffix+'] input[name=cron-repeat-for'+suffix+']').val()%1);
            if(integer != 0 || $('[name=external-modules-configure-modal'+suffix+'] input[name=cron-repeat-for'+suffix+']').val().indexOf(',') != -1 || $('[name=external-modules-configure-modal'+suffix+'] input[name=cron-repeat-for'+suffix+']').val().indexOf('.') != -1){
                errMsg.push('<strong> '+$('[name=external-modules-configure-modal'+suffix+'] input[name=cron-repeat-for'+suffix+']').val()+'</strong> is not a valid number for # of days.');
                $('[name=external-modules-configure-modal'+suffix+'] input[name=cron-repeat-for'+suffix+']').addClass('alert');
            }
        }
    }

    var editor_text = tinymce.activeEditor.getContent();
    if(editor_text == ""){
        if($('.external-modules-rich-text-field').val() == "") {
            errMsg.push('Please insert an <strong>email message</strong>.');
            $('#external-modules-rich-text-field_email-text' + suffix + '_ifr').addClass('alert');
        }
    }else{ $('#external-modules-rich-text-field_email-text'+suffix+'_ifr').removeClass('alert');}

    if (errMsg.length > 0) {
        $('#errMsgContainerModal'+errorContainerSuffix).empty();
        $.each(errMsg, function (i, e) {
            $('#errMsgContainerModal'+errorContainerSuffix).append('<div>' + e + '</div>');
        });
        $('#errMsgContainerModal'+errorContainerSuffix).show();
        $('html,body').scrollTop(0);
        $('[name=external-modules-configure-modal'+suffix+']').scrollTop(0);
        return false;
    }
    else {
        return true;
    }
}

function checkBranchingLogicValidAndSave(data, logicData, url, urlFile, urlForm, files, suffix, errorContainerSuffix, letter){
    var errMsg = [];
    $('#errMsgContainerModal'+errorContainerSuffix).empty();
    $.ajax({
        type: 'POST',
        url: url,
        data: logicData,
        dataType: 'json',
        success: function (result) {
            if(result.isBranchingValid && result.isBranchingValidQueue && result.isBranchingValidExpQueue){
                $('#errMsgContainerModal'+errorContainerSuffix).hide();

                var index = $('#index_modal_update').val();
                if(suffix != '') {
                    //close confirmation modal
                    $('#external-modules-configure-modal-schedule-confirmation').modal('hide');
                    $('#external-modules-configure-modal').modal('hide');
                    deleteFile(index);
                }
                saveFilesIfTheyExist(urlFile+'&index=' + index, files);
                ajaxLoadOptionAndMessage(data, urlForm, letter);
            }else{
                if(!result.isBranchingValid){
                    errMsg.push("<strong>Email Condition (REDCap logic)</strong> Incorrect");
                }
                if(!result.isBranchingValidQueue){
                    errMsg.push("<strong>Email Schedule Conditional</strong> Incorrect");
                }
                if(!result.isBranchingValidExpQueue){
                    errMsg.push("<strong>Email Schedule Expiration Conditional</strong> Incorrect");
                }

                $.each(errMsg, function (i, e) {
                    $('#errMsgContainerModal'+errorContainerSuffix).append('<div>' + e + '</div>');
                });
                $('#errMsgContainerModal'+errorContainerSuffix).show();
                $('[name=external-modules-configure-modal'+suffix+']').scrollTop(0);
            }
        }
    });
}

function ajaxLoadOptionAndMessage(data, url, message){
    $.post(url, data, function(returnData){
        jsonAjax = jQuery.parseJSON(returnData);
        if(jsonAjax.status == 'success'){
            //refresh page to show changes
            if(jsonAjax.message != '' && jsonAjax.message != undefined){
                message = jsonAjax.message;
            }

            var newUrl = gerUtlMessageParam(message);
            if (newUrl.substring(newUrl.length-1) == "#")
            {
                newUrl = newUrl.substring(0, newUrl.length-1);
            }

            window.location.href = newUrl;
        }
        else {
	        alert("An error ocurred");
        }
    });
}

function ajaxLoadOptionAndMessageQueue(data, url, message){
    $.post(url, data, function(returnData){
        jsonAjax = jQuery.parseJSON(returnData);
        if(jsonAjax.status == 'success'){
            //refresh page to show changes
            if(jsonAjax.failed_records != '' && jsonAjax.failed_records != undefined){
                alert("Couldn't add these records in the queue: "+jsonAjax.failed_records+"\nPlease check if they already exist or that the information is correct.");
            }

            var newUrl = gerUtlMessageParam(message);
            if (newUrl.substring(newUrl.length-1) == "#")
            {
                newUrl = newUrl.substring(0, newUrl.length-1);
            }

             window.location.href = newUrl;
        }
        else {
            alert("An error ocurred");
        }
    });
}

//we call each time a letter is typed to search in the DB for the options and load them
function preloadEmail(element){
    var cutword = "-flexdatalist";
    var id = $(element).attr('id').substr(0, $(element).attr('id').length-cutword.length);
    var value = $(element).val();
    loadAjax('parameters='+value+'&project_id='+project_id+'&variables='+emailFromForm_var, _getProjectList_url, id);
}

function loadAjax(parameters, url, id){
    var loadAJAX = 'json-datalist-'+id;
    $.ajax({
        type: "POST",
        url: url,
        data:parameters
        ,
        error: function (xhr, status, error) {
            alert(xhr.responseText);
        },
        success: function (result) {
            jsonAjax = jQuery.parseJSON(result);
            if(jsonAjax != '' && jsonAjax != undefined) {
                $("#" + loadAJAX).html(jQuery.parseJSON(result));
                $("#" + id).flexdatalist('reload');
            }

        }
    });
}

function checkIfSurveyIsSaveAndReturn(data,url,saveUrl){
    return $.ajax({
        type: "POST",
        url: url,
        data:data
        ,
        error: function (xhr, status, error) {
            alert(xhr.responseText);
        },
        success: function (result) {
            jsonAjax = jQuery.parseJSON(result);

            if(jsonAjax.status == 'success'){
                if(jsonAjax.message != '' && jsonAjax.message != undefined){
                    $('#errMsgContainer').append('<div>' + jsonAjax.message + '</div>');
                    $('#errMsgContainer').show();
                    $('html,body').scrollTop(0);
                }else if(saveUrl != ''){
                    var data = $('#mainForm').serialize();
                    ajaxLoadOptionAndMessage(data, saveUrl, "C");
                }
            }

        }
    });
}

/**
 * If the instrument is longitufinal we show the drop down to select the event
 * @param data
 */
function uploadLongitudinalEvent(data,field){
    if(isLongitudinal){
        $.post(_longitudinal_url, data, function(returnData){
            jsonAjax = jQuery.parseJSON(returnData);
            if(jsonAjax.status == 'success'){
                $(field).html(jsonAjax.event);
                $('#form_event').change(function() {
                    showIfRepeatingForm(data+'&event_id='+$('#form_event').val(), '[name=form-name-instance]');
                });
                $(field).show();
            }
            else {
                alert("An error ocurred");
            }
        });
    }
}

function uploadRepeatableInstances(data){
    $.post(_repeatable_url, data, function(returnData){
        jsonAjax = jQuery.parseJSON(returnData);
        if (jsonAjax.status == 'success') {
            $('#addQueueInstance').html(jsonAjax.instance);
        }
        else {
            alert("An error occurred");
        }
    });
}

function showIfRepeatingForm(data, field){
    $(field).hide();
    $.post(_repeating_url, data, function(returnData) {
        jsonAjax = jQuery.parseJSON(returnData);
        if (jsonAjax.status == 'success') {
            if (jsonAjax.repeating) {
                $(field).show();
            }
        }
        else {
            alert("An error occurred");
        }
    });
}

function deleteAllQueue(){
    ajaxLoadOptionAndMessage("&pid="+project_id+"&alertid="+$('#alertid').val(),_delete_queue_all_url,"O");
}

function dateIsValid(dateStr) {
    const regex = /^\d{4}-\d{2}-\d{2}$/;

    if (dateStr.match(regex) === null) {
        return false;
    }

    const date = new Date(dateStr);

    const timestamp = date.getTime();

    if (typeof timestamp !== 'number' || Number.isNaN(timestamp)) {
        // 👇️ this runs
        return false;
    }

    return date.toISOString().startsWith(dateStr);
}