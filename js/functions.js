/**
 * Function to preview the message on the alerts table
 * @param index, the alert id
 */
function previewEmailAlert(index){
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
            $('#external-modules-configure-modal-preview').modal('show');
        }
    });
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

    $('[name="email-attachment-variable-update"]').attr('placeholder','[variable1], [variable2], ...')
    $('[name="email-from-update"]').attr('placeholder','myemail@server.com, "Sender name"');

    //Add values
    $('#external-modules-configure-modal-update select[name="form-name-update"]').val(modal['form-name']);
    $('#external-modules-configure-modal-update input[name="email-from-update"]').val(modal['email-from']);
    $('#external-modules-configure-modal-update input[name="email-to-update"]').val(modal['email-to']);
    $('#external-modules-configure-modal-update input[name="email-cc-update"]').val(modal['email-cc']);
    $('#external-modules-configure-modal-update input[name="email-bcc-update"]').val(modal['email-bcc']);
    $('#external-modules-configure-modal-update input[name="email-subject-update"]').val(modal['email-subject']);
    $('#external-modules-configure-modal-update textarea[name="email-text-update"]').val(modal['email-text']);
    $('#external-modules-configure-modal-update input[name="email-attachment-variable-update"]').val(modal['email-attachment-variable']);
    $('#external-modules-configure-modal-update input[name="email-repetitive-update"]').val(modal['email-repetitive']);
    $('#external-modules-configure-modal-update input[name="email-condition-update"]').val(modal['email-condition']);
    $('#external-modules-configure-modal-update input[name="email-incomplete-update"]').val(modal['email-incomplete']);

    uploadLongitudinalEvent('project_id='+project_id+'&form='+modal['form-name']+'&index='+index,'[field=form-name-event]');

    //Add Files
    for(i=1; i<6 ; i++){
        getFileFieldElement(modal['email-attachment'+i], i);
    }

    //Checkboxes
    $('#external-modules-configure-modal-update input[name="email-repetitive-update"]').prop('checked',false);
    if(modal['email-repetitive'] == '1'){
        $('#external-modules-configure-modal-update input[name="email-repetitive-update"]').prop('checked',true);
    }

    $('#external-modules-configure-modal-update input[name="email-incomplete-update"]').prop('checked',false);
    if(modal['email-incomplete'] == '1'){
        $('#external-modules-configure-modal-update input[name="email-incomplete-update"]').prop('checked',true);
    }

    //clean up error messages
    $('#errMsgContainerModalUpdate').empty();
    $('#errMsgContainerModalUpdate').hide();
    $('#external-modules-configure-modal-update input[name=form-name]').removeClass('alert');
    $('#external-modules-configure-modal-update input[name=email-to]').removeClass('alert');
    $('#external-modules-configure-modal-update input[name=email-subject]').removeClass('alert');
    $('#external-modules-configure-modal-update [name=email-text]').removeClass('alert');

    //Show modal
    $('#external-modules-configure-modal-update').modal('show');

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
    $('#external-modules-configure-modal-update input[name="email-attachment'+file_number+'-update"]').parent().html(html);
}

function hideFile(value,file_number){
    var name = "email-attachment"+file_number;
    var html = '<input type="file" name="' + name + '-update" value="" class="external-modules-input-element">';
    html += '<input type="hidden" name="'+name+'" value="'+value+'" class="external-modules-input-element deletedFile">';
    $('#external-modules-configure-modal-update input[name="email-attachment'+file_number+'-update"]').parent().html(html);
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

//We insert the button text depending on which field we are
/**
 * Function to add the cursor position so the text from the data piping buttons can be added on those specific fields
 * @param myValue
 * @param option
 */
function insertAtCursorTinyMCE(myValue,option) {
    if(lastClick != '') {
        if (lastClick != null) {
            // logic on to add button content
            if((lastClick !='#email-cc-flexdatalist' && lastClick !='#email-to-flexdatalist' && lastClick !='#email-bcc-flexdatalist' && lastClick !='#email-cc-update-flexdatalist' && lastClick !='#email-bcc-update-flexdatalist' && lastClick !='#email-to-update-flexdatalist' && option == 1) || option == 0) {
                var myField = $(lastClick);
                //IE support
                if (document.selection) {
                    myField.focus();
                    sel = document.selection.createRange();
                    sel.text = myValue;
                }
                //MOZILLA and others
                else if (startPos || startPos == '0') {
                    myField.val(myField.val().substring(0, startPos) + myValue + myField.val().substring(endPos, myField.val().length));
                    myField.selectionStart = startPos + myValue.length;
                    myField.selectionEnd = startPos + myValue.length;
                } else {
                    myField.val(myField.val() + myValue);
                }

                //We update positions to add next text after the new one
                startPos = startPos + myValue.length;
                endPos = startPos + myValue.length;
            }
        } else {
            if (tinymce.isIE) {
                tinyMCE.activeEditor.selection.moveToBookmark(actualCaretPositionBookmark);
                tinyMCE.execCommand('mceInsertContent', false, myValue);
            } else {
                tinyMCE.execCommand('insertHTML', false, myValue);
            }
        }
    }

}

/**
 * Function that reloads the page and updates the success message
 * @param letter
 * @returns {string}
 */
function gerUtlMessageParam(letter){
    var url = window.location.href;
    if(window.location.href.match(/(&message=)([A-Z]{1})/)){
        url = window.location.href = window.location.href.replace( /(&message=)([A-Z]{1})/, "&message="+letter );
    }else{
        url = window.location.href + "&message="+letter;
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
    if ($('#external-modules-configure-modal'+suffix+' input[name=email-to'+suffix+']').val() === "" || $('#external-modules-configure-modal'+suffix+' input[name=email-to'+suffix+']').val() === "0") {
        errMsg.push('Please insert an <strong>email receiver</strong>.');
        $('#external-modules-configure-modal'+suffix+' input[name=email-to'+suffix+']').addClass('alert');
    }else{ $('#external-modules-configure-modal'+suffix+' input[name=email-to'+suffix+']').removeClass('alert');}

    if ($('#external-modules-configure-modal'+suffix+' input[name=email-subject'+suffix+']').val() === "" || $('#external-modules-configure-modal'+suffix+' input[name=email-subject'+suffix+']').val() === "0") {
            errMsg.push('Please insert an <strong>email subject</strong>.');
            $('#external-modules-configure-modal' + suffix + ' input[name=email-subject' + suffix + ']').addClass('alert');
    }else{ $('#external-modules-configure-modal'+suffix+' input[name=email-subject'+suffix+']').removeClass('alert');}

    if ($('#external-modules-configure-modal'+suffix+' select[name=form-name'+suffix+']').val() === "" || $('#external-modules-configure-modal'+suffix+' select[name=form-name'+suffix+']').val() === "0") {
        errMsg.push('Please select a <strong>Form</strong>.');
        $('#external-modules-configure-modal'+suffix+' select[name=form-name'+suffix+']').addClass('alert');
    }else{ $('#external-modules-configure-modal'+suffix+' select[name=form-name'+suffix+']').removeClass('alert');}

    if ($('#external-modules-configure-modal'+suffix+' input[name=email-attachment-variable'+suffix+']').val() != "") {
        var result = $('#external-modules-configure-modal'+suffix+' input[name=email-attachment-variable'+suffix+']').val().split(",");
        var errorInField = false;
        for(var i=0;i<result.length;i++){
            if(trim(result[i]).substring(0, 1) != "[" || trim(result[i]).substring(trim(result[i]).length-1, trim(result[i]).length) != "]"){
                errorInField = true;
                break;
            }
        }
        if(errorInField == true) {
            errMsg.push('<strong>Email Attachment as variables</strong> must follow the format: <i>[variable1],[variable2],...</i>.');
            $('#external-modules-configure-modal'+suffix+' input[name=email-attachment-variable'+suffix+']').addClass('alert');
        }else{
            $('#external-modules-configure-modal'+suffix+' input[name=email-attachment-variable'+suffix+']').removeClass('alert');
        }
    }

    if ($('#external-modules-configure-modal'+suffix+' input[name=email-from'+suffix+']').val() === "" || $('#external-modules-configure-modal'+suffix+' input[name=email-from'+suffix+']').val() === "0") {
        errMsg.push('Please insert an <strong>email sender</strong>.');
        $('#external-modules-configure-modal'+suffix+' input[name=email-from'+suffix+']').addClass('alert');
    }else{ $('#external-modules-configure-modal'+suffix+' input[name=email-from'+suffix+']').removeClass('alert');}

    var editor_text = tinymce.activeEditor.getContent();
    if(editor_text == ""){
        errMsg.push('Please insert an <strong>email message</strong>.');
        $('#external-modules-rich-text-field_email-text'+suffix+'_ifr').addClass('alert');
    }else{ $('#external-modules-rich-text-field_email-text'+suffix+'_ifr').removeClass('alert');}

    if (errMsg.length > 0) {
        $('#errMsgContainerModal'+errorContainerSuffix).empty();
        $.each(errMsg, function (i, e) {
            $('#errMsgContainerModal'+errorContainerSuffix).append('<div>' + e + '</div>');
        });
        $('#errMsgContainerModal'+errorContainerSuffix).show();
        $('html,body').scrollTop(0);
        $('#external-modules-configure-modal'+suffix).scrollTop(0);
        return false;
    }
    else {
        return true;
    }
}

function ajaxLoadOptionAndMessage(data, url, message){
    $.post(url, data, function(returnData){
        jsonAjax = jQuery.parseJSON(returnData);
        if(jsonAjax.status == 'success'){
            //refresh page to show changes
            if(jsonAjax.message != '' && jsonAjax.message != undefined){
                message = jsonAjax.message;
            }
            window.location.href = gerUtlMessageParam(message);
        }
        else {
	        alert("An error ocurred");
        }
    });
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
                $(field).show();
            }
            else {
                alert("An error ocurred");
            }
        });
    }
}