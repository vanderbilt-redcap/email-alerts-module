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

function editEmailAlert(modal, index){
    tinymce.remove();
    EMparentAux.configureSettings(configSettingsUpdate, configSettingsUpdate);
    $("#index_modal_update").val(index);

    //Add values
    $('#external-modules-configure-modal-update select[name="form-name-update"]').val(modal['form-name']);
    $('#external-modules-configure-modal-update input[name="email-to-update"]').val(modal['email-to']);
    $('#external-modules-configure-modal-update input[name="email-cc-update"]').val(modal['email-c']);
    $('#external-modules-configure-modal-update input[name="email-subject-update"]').val(modal['email-subject']);
    $('#external-modules-configure-modal-update textarea[name="email-text-update"]').val(modal['email-text']);
    $('#external-modules-configure-modal-update input[name="email-repetitive-update"]').val(modal['email-repetitive']);
    $('#external-modules-configure-modal-update input[name="email-timestamp-update"]').val(modal['email-timestamp']);
    $('#external-modules-configure-modal-update input[name="email-condition-update"]').val(modal['email-condition']);

    //Add Files
    for(i=1; i<6 ; i++){
        getFileFieldElement(modal['email-attachment'+i], i);
    }

    //Add checked
    $('#external-modules-configure-modal-update input[name="email-timestamp-update"]').prop('checked',false);
    if(modal['email-timestamp'] == '1'){
        $('#external-modules-configure-modal-update input[name="email-timestamp-update"]').prop('checked',true);
    }
    $('#external-modules-configure-modal-update input[name="email-repetitive-update"]').prop('checked',false);
    if(modal['email-repetitive'] == '1'){
        $('#external-modules-configure-modal-update input[name="email-repetitive-update"]').prop('checked',true);
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

//            tinymce.activeEditor.setContent(modal['email-text'])
    $(function() {
        for(var i=0; i<tinymce.editors.length; i++){
            var editor = tinymce.editors[i];
            editor.on('init', function () {
                editor.setContent(modal['email-text'])
            });
        }
    });
}

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

function deleteEmailAlert(index){
    $('#index_modal_delete').val(index);
    $('#external-modules-configure-modal-delete-confirmation').modal('show');
}

//Same as insertAtCursor but for tinyMCE element
function insertAtCursorTinyMCE(myValue) {
    if (tinymce.isIE) {
        tinyMCE.activeEditor.selection.moveToBookmark(actualCaretPositionBookmark);
        tinyMCE.execCommand('mceInsertContent',false, myValue);

    }else {
        tinyMCE.execCommand('insertHTML',false, myValue);
    }
}

function randomString() {
    var length = 7;
    return Math.round((Math.pow(36, length + 1) - Math.random() * Math.pow(36, length))).toString(36).slice(1);
}

function gerUtlMessageParam(letter){
    var url = window.location.href;
    if(window.location.href.match(/(&message=)([A-Z]{1})/)){
        url = window.location.href = window.location.href.replace( /(&message=)([A-Z]{1})/, "&message="+letter );
    }else{
        url = window.location.href + "&message="+letter;
    }
    // url = url + "&rand=" + randomString();
    return url;
}

function validateEmail(email) {
    var re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(email);
}

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
        return false;
    }
    else {
        return true;
    }
}

function ajaxLoadOptionAndMessage(data, url, message){
    // $.ajax({
    //     type: "POST",
    //     url: url,
    //     data: data
    //     ,
    //     error: function (xhr, status, error) {
    //         alert(xhr.responseText);
    //     },
    //     success: function (result) {
    //         // console.log(result)
    //         //refresh page to show changes
    //         window.location.href = gerUtlMessageParam(message);
    //     }
    // });

    $.post(url, data, function(returnData){
        if(returnData.status != 'success'){
            //refresh page to show changes
            window.location.href = gerUtlMessageParam(message);
        }
        else {
	        window.location.href = getUtlMessageParam(message);
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