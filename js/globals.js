
//Polyfill from https://github.com/kevlatus/polyfill-array-includes/blob/3eb7ee5/array-includes.js
if (!Array.prototype.includes) {
    Object.defineProperty(Array.prototype, 'includes', {
        value: function (searchElement, fromIndex) {

            // 1. Let O be ? ToObject(this value).
            if (this == null) {
                throw new TypeError('"this" is null or not defined');
            }

            var o = Object(this);

            // 2. Let len be ? ToLength(? Get(O, "length")).
            var len = o.length >>> 0;

            // 3. If len is 0, return false.
            if (len === 0) {
                return false;
            }

            // 4. Let n be ? ToInteger(fromIndex).
            //    (If fromIndex is undefined, this step produces the value 0.)
            var n = fromIndex | 0;

            // 5. If n ≥ 0, then
            //  a. Let k be n.
            // 6. Else n < 0,
            //  a. Let k be len + n.
            //  b. If k < 0, let k be 0.
            var k = Math.max(n >= 0 ? n : len - Math.abs(n), 0);

            function sameValueZero(x, y) {
                return x === y || (typeof x === 'number' && typeof y === 'number' && isNaN(x) && isNaN(y));
            }

            // 7. Repeat, while k < len
            while (k < len) {
                // a. Let elementK be the result of ? Get(O, ! ToString(k)).
                // b. If SameValueZero(searchElement, elementK) is true, return true.
                // c. Increase k by 1.
                if (sameValueZero(o[k], searchElement)) {
                    return true;
                }
                k++;
            }

            // 8. Return false
            return false;
        }
    });
}

// It is possible that a module may have already defined the ExternalModules object
// via one more more initializeJavascriptModuleObject() calls.

if(!EmailAlerts){
    var EmailAlerts = {}
}

EmailAlerts = $.extend(ExternalModules, {
    sortModuleTable: function(table){
        table.find('tr').sort(function(a, b){
            a = $(a).find('.external-modules-title').text()
            b = $(b).find('.external-modules-title').text()

            return a.localeCompare(b)
        }).appendTo(table)
    },

    validateSettings: function(configureModal) {
        var errorMessages = [
            this.validateDateSettings(configureModal),
            this.validateEmailSettings(configureModal)
        ].filter(function(message){
            // Exclude any value that's not truthy from the array.
            return message
        })

        // Just return the first message, or null if there aren't any.
        return errorMessages[0]
    },

    validateEmailSettings: function(configureModal){
        var errorMessage = null
        configureModal.find('input.external-modules-input-element[type=email]').each(function(index, input){
            input = $(input)
            var value = input.val();
            if(value && !EmailAlerts.validateEmail(value)){
                var label = input.closest('tr').find('label').text()
                //= The email address entered for the following field is not valid:
                errorMessage = module.tt('em_errors_91') + '<br><br>' + label
            }
        })

        return errorMessage
    },

    // Taken from https://stackoverflow.com/questions/46155/how-to-validate-an-email-address-in-javascript
    validateEmail: function(email){
        var re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        return re.test(String(email).toLowerCase());
    },

    validateDateSettings: function(configureModal){
        var errorMessage = null
        configureModal.find('input.external-modules-input-element.datepicker').each(function(index, input){
            var value = $(input).val().trim()
            if(value === ''){
                return
            }

            var parts = value.split('/')
            if(parts.length === 3) {
                // parseInt() to remove leading zeros and make the types match
                var monthIndex = parseInt(parts[0]) - 1 // subtract one since Date's months are zero based
                var day = parseInt(parts[1])
                var year = parseInt(parts[2])

                var date = new Date(year, monthIndex, day)

                if (
                    date.getFullYear() === year &&
                    date.getMonth() === monthIndex &&
                    date.getDate() === day
                ) {
                    // The date is valid.  Let's make sure leading zeros are included just for normalization purposes.

                    var month = monthIndex+1
                    if(month < 10){
                        month = '0' + month
                    }

                    if(day < 10){
                        day = '0' + day
                    }

                    $(input).val(month + '/' + day + '/' + year)

                    return
                }
            }

            // Only support the jquery UI datepicker's default date format for now.
            //= Dates must be specified in MM/DD/YYYY format. The following value is not a valid date:
            errorMessage = module.tt('em_errors_92') + '<br><br>' + value
        })

        return errorMessage
    }
})

EmailAlerts.Settings = function(){}

EmailAlerts.Settings.prototype.addEscapedAttribute = function(elementHtml, name, value){
    var element = $(elementHtml)
    element.attr(name, value)

    return element[0].outerHTML
}

// Function to get the HTML for all the setting rows
EmailAlerts.Settings.prototype.getSettingRows = function(configSettings, savedSettings,instance){
    var rowsHtml = '';
    var settingsObject = this;
    configSettings.forEach(function(setting){
        var setting = $.extend({}, setting);
        rowsHtml += settingsObject.getSettingColumns(setting,savedSettings,instance);
    });

    return rowsHtml;
};

EmailAlerts.Settings.prototype.getSettingColumns = function(setting,savedSettings,previousInstance) {
    var settingsObject = this;
    var rowsHtml = '';

    if(typeof previousInstance === 'undefined') {
        previousInstance = [];
    }

    var thisSavedSettings = savedSettings[setting.key];
    if(typeof(EmailAlerts.resetFileFields) == "undefined") {
        EmailAlerts.resetFileFields = [];
    }

    if(typeof thisSavedSettings === "undefined") {
        thisSavedSettings = [{}];
    }
    else if (thisSavedSettings.value.length == 0) {
        thisSavedSettings = [{}];
    }
    else {
        thisSavedSettings = thisSavedSettings.value;
        for(var i = 0; i < previousInstance.length; i++) {
            // If this setting is currently a string because of prior saves, but now it's in a repeating sub-setting
            // make it an array
            if(typeof(thisSavedSettings) == "string" && previousInstance[i] === 0) {
                thisSavedSettings = [thisSavedSettings];
                if(setting.type == "file") {
                    EmailAlerts.resetFileFields.push(setting.key + "____0");
                }
            }

            if(thisSavedSettings.hasOwnProperty(previousInstance[i]) && thisSavedSettings[previousInstance[i]] !== null) {
                thisSavedSettings = thisSavedSettings[previousInstance[i]];
            }
            else {
                thisSavedSettings = [{}];
            }

        }
    }

    if(typeof thisSavedSettings === 'undefined') {
        thisSavedSettings = [{}];
    }

    if(!Array.isArray(thisSavedSettings)) {
        thisSavedSettings = [thisSavedSettings];
    }

    thisSavedSettings.forEach(function(settingValue,instance) {
        var subInstance  = previousInstance.slice();
        subInstance.push(instance);
        if(setting.type == "sub_settings") {
            rowsHtml += settingsObject.getColumnHtml(setting);
            setting.sub_settings.forEach(function(settingDetails){
                rowsHtml += settingsObject.getSettingRows([settingDetails],savedSettings,subInstance);
            });
            rowsHtml += "<tr style='display:none' class='sub_end' field='" + setting.key + "'></tr>";
        }
        else {
            if(['string', 'boolean'].indexOf(typeof settingValue) == -1) {
                settingValue = "";
            }

            rowsHtml += settingsObject.getColumnHtml(setting, settingValue);
        }
    });

    return rowsHtml;
};

EmailAlerts.Settings.prototype.processBranchingLogicCondition = function(condition) {
    if (typeof condition.field === 'undefined' || typeof condition.value === 'undefined') {
        return false;
    }

    var info = EmailAlerts.Settings.__branchingLogicInfo[condition.field];
    if (info.repeatable || info.type === 'sub_settings') {
        // Repeatable and sub settings fields are not supported as
        // condition fields.
        return false;
    }

    var fieldName = condition.field;
    if (info.isSubSetting) {
        // Adding suffix to fetch sub settings condition field.
        fieldName += '____0';
    }

    var $field = $('#external-modules-configure-modal [name="' + fieldName + '"]');
    if ($field.length === 0) {
        return false;
    }

    if (info.type === 'checkbox') {
        var isChecked = $field.is(':checked');
        return (isChecked && condition.value != false) || (!isChecked && condition.value == false);
    }

    if (info.type == 'radio') {
        return $field.filter('[value="' + condition.value + '"]').is(':checked');
    }

    var val = $field.val();
    var op = typeof condition.op === 'undefined' ? '=' : condition.op;
    switch (op) {
        case '=':
            return val == condition.value;
        case '>':
            return val > condition.value;
        case '>=':
            return val >= condition.value;
        case '<':
            return val < condition.value;
        case '<=':
            return val <= condition.value;
        case '<>':
        case '!=':
            return val != condition.value;
    }

    return false;
}

EmailAlerts.Settings.prototype.getConfigFieldElement = function(fieldName) {
    return $('#external-modules-configure-modal [field="' + fieldName + '"]');
}

EmailAlerts.Settings.prototype.hideConfigField = function(fieldName) {
    var $row = EmailAlerts.Settings.prototype.getConfigFieldElement(fieldName);
    if ($row.hasClass('requiredm')) {
        $row.data('required', true);
    }

    $row.hide();
    $row.removeClass('requiredm');
}

EmailAlerts.Settings.prototype.showConfigField = function(fieldName) {
    var $row = EmailAlerts.Settings.prototype.getConfigFieldElement(fieldName);
    $row.show();
    if ($row.data('required')) {
        $row.addClass('requiredm');
    }
}

EmailAlerts.Settings.prototype.doBranching = function(settings) {
    if (!settings) {
        var $modal = $('#external-modules-configure-modal');
        var settings = EmailAlerts.configsByPrefix[$modal.data('module')];
        settings = EmailAlerts.PID ? settings['project-settings'] : settings['system-settings'];

        var blInfo = {};
        settings.forEach(function(setting) {
            blInfo[setting.key] = {
                type: setting.type,
                isSubSetting: false,
                repeatable: typeof setting.repeatable === 'undefined' ? false : setting.repeatable
            };

            if (setting.type === 'sub_settings' && typeof setting.sub_settings !== false) {
                setting.sub_settings.forEach(function(subSetting) {
                    blInfo[subSetting.key] = {
                        type: subSetting.type,
                        isSubSetting: true,
                        repeatable: typeof subSetting.repeatable === 'undefined' ? false : subSetting.repeatable
                    };
                });
            }
        });

        EmailAlerts.Settings.__branchingLogicInfo = blInfo;
    }

    settings.forEach(function(setting) {
        if (typeof setting.branchingLogic !== 'undefined') {
            var bl = setting.branchingLogic;

            if (typeof bl.conditions === 'undefined') {
                elementVisible = EmailAlerts.Settings.prototype.processBranchingLogicCondition(bl);
            }
            else {
                if (typeof bl.type === 'undefined' || bl.type.toLowerCase() !== 'or') {
                    for (i = 0; i < bl.conditions.length; i++) {
                        var elementVisible = true;
                        if (!EmailAlerts.Settings.prototype.processBranchingLogicCondition(bl.conditions[i])) {
                            elementVisible = false;
                            break;
                        }
                    }
                }
                else {
                    for (i = 0; i < bl.conditions.length; i++) {
                        var elementVisible = false;
                        if (EmailAlerts.Settings.prototype.processBranchingLogicCondition(bl.conditions[i])) {
                            elementVisible = true;
                            break;
                        }
                    }
                }
            }

            if (elementVisible) {
                EmailAlerts.Settings.prototype.showConfigField(setting.key);

                if (setting.type === 'sub_settings') {
                    setting.sub_settings.forEach(function(subSetting) {
                        EmailAlerts.Settings.prototype.showConfigField(subSetting.key);
                    });
                }
            }
            else {
                EmailAlerts.Settings.prototype.hideConfigField(setting.key);

                if (setting.type === 'sub_settings') {
                    setting.sub_settings.forEach(function(subSetting) {
                        EmailAlerts.Settings.prototype.hideConfigField(subSetting.key);
                    });

                    return;
                }
            }
        }

        if (setting.type === 'sub_settings') {
            // Applying branching logic to sub settings.
            EmailAlerts.Settings.prototype.doBranching(setting.sub_settings);
        }
    });
}

EmailAlerts.Settings.prototype.addSelectedProjectIdChoiceIfNeeded = function(setting, value){
    if(value == "") {
        return
    }

    for(var i=0; i<setting.choices.length; i++){
        var choiceValue = setting.choices[i].value
        if(choiceValue == value){
            // The current user has design rights for the selected project, so it will already appear in their dropdown list.
            return
        }
    }

    // The current user does not have design rights for the selected project.
    // Add it to the list so it can be selected and displayed correctly in the config dialog.
    setting.choices.splice(1, 0, {
        value: value,
        name: '(' + value + ') You do not have design rights for the selected project'
    });
}

EmailAlerts.Settings.prototype.getColumnHtml = function(setting,value,className){
    var type = setting.type;
    var key = setting.key;

    if(setting['super-users-only'] && !EmailAlerts.SUPER_USER){
        return '';
    }

    if(typeof className === "undefined") {
        className = "";
    }
    var trClass = className;

    var colspan = '';
    if(type == 'descriptive'){
        colspan = " colspan='3'";
    }

    var instanceLabel = "";
    if (typeof instance != "undefined") {
        instanceLabel = (instance+1)+". ";
    }
    var html = "<td></td>";
    if(type != 'sub_settings') {
        var reqLabel = '';
        if(setting.required) {
            //= * must provide value
            reqLabel = '<div class="requiredlabel">' + module.tt('em_manage_72') + '</div>';
        }
        html = "<td" + colspan + "><span class='external-modules-instance-label'>" + instanceLabel + "</span><label>" + setting.name + (type == 'descriptive' ? '' : ':') + "</label>" + reqLabel + "</td>";
    }

    if (typeof instance != "undefined") {
        // for looping for repeatable elements
        if (typeof header == "undefined" && typeof value != "undefined" && value !== null) {
            value = value[instance];
        }
        key = this.getInstanceName(key, instance);
    }

    var inputHtml;
    if(['dropdown', 'field-list', 'form-list', 'event-list', 'arm-list', 'user-list', 'user-role-list', 'dag-list', 'project-id'].includes(type)){
        inputHtml = this.getSelectElement(key, setting, value);
    }
    else if(type == 'textarea'){
        inputHtml = this.getTextareaElement(key, value, {"rows" : "6"});
    }
    else if(type == 'rich-text') {
        inputHtml = this.getRichTextElement(key, value);
    }
    else if(type == 'sub_settings'){
        inputHtml = "<span class='external-modules-instance-label'>"+instanceLabel+"</span><label name='"+key+"'>" + setting.name + ":</label><input type='hidden' value='true' name='key' />";
        trClass += ' sub_start sub_parent';
    }
    else if(type == 'radio'){
        inputHtml = "";
        for(var i in setting.choices ){
            var choice = setting.choices[i];

            var inputAttributes = [];
            if(choice.value == value) {
                inputAttributes["checked"] = "true";
            }

            inputHtml += this.getInputElement(type, key, choice.value, inputAttributes) + '<label>' + choice.name + '</label><br>';
        }
    }else if(type == 'button'){
        inputHtml = this.getButtonElement(type, key, setting.url.name,setting.url.value, inputAttributes);
    }
    else if(type == 'custom') {
        inputHtml = this.getInputElement(type, key, value, inputAttributes);
    } else {
        var inputAttributes = [];
        if(type == 'checkbox' && value == 1){
            inputAttributes['checked'] = 'checked';
        } else if (type == 'text' && typeof setting.validation != "undefined") {
            var validation = setting.validation;
            var validation_min = (typeof setting.validation_min == "undefined") ? "" : setting.validation_min;
            var validation_max = (typeof setting.validation_max == "undefined") ? "" : setting.validation_max;
            inputAttributes['onblur'] = "redcap_validate(this,'"+validation_min+"','"+validation_max+"','soft_typed','"+validation+"',1);";
        }

        inputHtml = this.getInputElement(type, key, value, inputAttributes);
    }

    if (typeof setting.functionName !== 'undefined') {
        inputHtml += "<script type='text/javascript'>" + setting.functionName + "($('input[name=\"" + key + "\"]'));</script>";
    }

    if(type != 'descriptive'){
        html += "<td class='external-modules-input-td'>" + inputHtml + "</td>";
    }

    if(setting.required) {
        trClass += ' requiredm';
    }

    if(setting.repeatable) {
        // Add repeatable buttons
        html += "<td class='external-modules-add-remove-column'>";
        html += "<button class='external-modules-add-instance' setting='" + setting.key + "'>+</button>";
        html += "<button class='external-modules-remove-instance' >-</button>";
        html += "</td>";

        trClass += ' repeatable';
    }
    else {
        html += "<td></td>";
    }

    var outputHtml = "<tr" + (trClass === "" ? "" : " class='" + trClass + "'") + " field='" + setting.key + "'>" + html + "</tr>";

    return outputHtml;
};

EmailAlerts.Settings.prototype.getSelectElement = function(name, setting, selectedValue){
    var choices = setting.choices
    var isProjectId = setting.type === 'project-id'

    var selectAttributes = {
        class: ''
    }

    if (
        isProjectId
        ||
        typeof setting.autocomplete !== 'undefined' && setting.autocomplete == true
    ){
        selectAttributes.class += " external-modules-autocomplete-dropdown";
    }

    if(isProjectId){
        this.addSelectedProjectIdChoiceIfNeeded(setting, selectedValue)
    }

    var optionsHtml = '';
    var choiceHasBlankValue = false;
    for(var i in choices ){
        var choice = choices[i];
        var value = choice.value;
        if (value == '') choiceHasBlankValue = true;

        var optionAttributes = ''
        if(value == selectedValue){
            optionAttributes += 'selected'
        }

        var option = '<option ' + optionAttributes + '>' + choice.name + '</option>'
        option = this.addEscapedAttribute(option, 'value', value)

        optionsHtml += option
    }

    if (!choiceHasBlankValue) {
        optionsHtml = '<option value=""></option>' + optionsHtml;
    }

    var defaultAttributes = {"class" : "external-modules-input-element"};
    var attributeString = this.getElementAttributes(defaultAttributes,selectAttributes);

    return '<select ' + attributeString + ' name="' + name + '" >' + optionsHtml + '</select>';
}

EmailAlerts.Settings.prototype.getInputElement = function(type, name, value, inputAttributes){
    if (typeof value == "undefined") {
        value = "";
    }

    if (type == "file") {
        if (EmailAlerts.PID) {
            return this.getProjectFileFieldElement(name, value, inputAttributes);
        } else {
            return this.getSystemFileFieldElement(name, value, inputAttributes);
        }
    } else {
        var classes = 'external-modules-input-element'
        if(type === 'date'){
            // Switch the type back to text so that built-in browser date pickers aren't used.
            // We use jquery UI's datepicker instead, since it supports IE.
            type = 'text'
            classes += ' datepicker'
        }

        var input = '<input type="' + type + '" name="' + name + '" ' + this.getElementAttributes({"class":classes},inputAttributes) + '>';
        input = this.addEscapedAttribute(input, 'value', value)
        return input
    }
}

// abstracted because file fields need to be reset in multiple places
EmailAlerts.Settings.prototype.getSystemFileFieldElement = function(name, value, inputAttributes) {
    return this.getFileFieldElement(name, value, inputAttributes, "");
}

// abstracted because file fields need to be reset in multiple places
EmailAlerts.Settings.prototype.getProjectFileFieldElement = function(name, value, inputAttributes) {
    return this.getFileFieldElement(name, value, inputAttributes, "pid=" + EmailAlerts.PID);
}

// abstracted because file fields need to be reset in multiple places
EmailAlerts.Settings.prototype.getFileFieldElement = function(name, value, inputAttributes, pid) {
    var attributeString = this.getElementAttributes([],inputAttributes);
    var type = "file";
    if ((typeof value != "undefined") && (value !== "")) {
        var input = $('<input type="hidden" name="' + name + '">');
        var html = this.addEscapedAttribute(input, 'value', value);
        html += '<span class="external-modules-edoc-file"></span>';
        html += '<button class="external-modules-delete-file" '+attributeString+'>'+module.tt('em_manage_73')+'</button>'; //= Delete File
        $.post('ajax/get-edoc-name.php?' + pid, { edoc : value }, function(data) {
            //Name starts with
            $("[name^='"+name+"'][value='"+value+"']").closest("tr").find(".external-modules-edoc-file").html("<b>" + data.doc_name + "</b><br>");
        });
        return html;
    } else {
        attributeString = this.getElementAttributes({"class":"external-modules-input-element"},inputAttributes);
        return '<input type="' + type + '" name="' + name + '" ' + attributeString + '>';
    }
}

EmailAlerts.Settings.prototype.getButtonElement = function(name, value, btnname,btnvalue, inputAttributes){
    var btn = '<a name="' + value + '" class="btn btn-primary btn-sm" style="color:#fff" onclick="">'+btnname+'</a>';
    $.post('ajax/get-url.php', { pid:pid,moduleDirectoryPrefix: this.getPrefix(),page:btnvalue}, function(result){
        var data = jQuery.parseJSON(result);
        var url = data.url;
        if(data.status == 'success'){
            url = "javascript:$.post('"+data.url+"',''," + "function(result){" +
                "var mdata = jQuery.parseJSON(result);"+
                "if(mdata.status == 'success' || mdata.status == 'warning' || mdata.status == 'danger'){"+
                "var border = '';"+
                "if(mdata.status == 'success'){border = 'border-color:#d0e9c6 !important';}"+
                "else if(mdata.status == 'warning'){border = 'border-color:#faebcc !important';}"+
                "if(mdata.message != '' && mdata.message != undefined){"+
                "$('[name ="+value+"]').parent().html( '<div class=\"alert alert-'+mdata.status+'\" style=\"margin-bottom:0;'+border+'\">'+mdata.message+'</div>')" +
                "}"+
                "}"+
                "});";
            $('[name ='+value+']').attr('onclick',url);
        }else{
            return;
        }
    });
    return btn;
}

EmailAlerts.Settings.prototype.getTextareaElement = function(name, value, inputAttributes){
    if (typeof value == "undefined") {
        value = "";
    }

    var textarea = $('<textarea contenteditable="true" name="' + name + '" ' + this.getElementAttributes([],inputAttributes) + '></textarea>');
    textarea.html(value)
    return textarea[0].outerHTML
}

EmailAlerts.Settings.prototype.getRichTextElement = function(name, value) {
    if (!value) {
        value = '';
    }

    return '<textarea class="external-modules-rich-text-field" id="'+name+'" name="' + name + '">' + value + '</textarea>';
};

EmailAlerts.Settings.prototype.getElementAttributes = function(defaultAttributes, additionalAttributes) {
    var attributeString = "";

    for (var tag in additionalAttributes) {
        if(defaultAttributes[tag]) {
            attributeString += tag + '="' + defaultAttributes[tag] + ' ' + additionalAttributes[tag] + '" ';
            delete defaultAttributes[tag];
        }
        else {
            attributeString += tag + '="' + additionalAttributes[tag] + '" ';
        }
    }

    for (var tag in defaultAttributes) {
        attributeString += tag + '="' + defaultAttributes[tag] + '" ';
    }

    return attributeString;
}

EmailAlerts.Settings.prototype.getInstanceSymbol = function(){
    return "____";
}

EmailAlerts.Settings.prototype.findSettings = function(config,name) {
    var configSettings = [config['project-settings'],config['system-settings']];
    var activeSetting = false;

    configSettings.forEach(function(configType) {
        var matchedSetting = EmailAlerts.Settings.prototype.parseSettings(configType, name);

        if(matchedSetting !== false) {
            activeSetting = matchedSetting;
        }
    });

    return activeSetting;
};

EmailAlerts.Settings.prototype.parseSettings = function(configType, name) {
    var activeSetting = false;
    configType.forEach(function(setting) {
        if(setting.key == name) {
            activeSetting = setting;
        }
        else if(setting.type == 'sub_settings') {
            var matchedSetting = EmailAlerts.Settings.prototype.parseSettings(setting.sub_settings,name);

            if(matchedSetting !== false) {
                activeSetting = matchedSetting;
            }
        }
    });

    return activeSetting;
};

EmailAlerts.Settings.prototype.getEndOfSub = function(startTr) {
    var currentTr = startTr;
    var reachedEnd = false;
    var currentDepth = 1;

    // Loop through subsequent <tr> elements until finding its end element
    while(!reachedEnd) {
        currentTr = currentTr.next();

        // If reaching end of a sub-setting, decrement depth and check if reached
        // the end of the original element
        if(currentTr.hasClass("sub_end")) {
            currentDepth--;
            reachedEnd = currentDepth < 1;
        }

        // If nested sub-setting, increment the depth counter
        if(currentTr.hasClass("sub_start")) {
            currentDepth++;
        }
    }

    return currentTr;
};

//Returns prefix. Added so it can be called globally for tinymce
EmailAlerts.Settings.prototype.getPrefix = function() {
    return $('#external-modules-configure-modal').data('module');
};

EmailAlerts.Settings.prototype.configureSettings = function() {
    // Reset the instances so that things will be saved correctly
    // This has to run before initializing rich text fields so that the names are correct
    this.resetConfigInstances();

    // Set up other functions that need configuration
    this.initializeRichTextFields();

    $('input.external-modules-input-element.datepicker').datepicker()

    $('input.external-modules-input-element[type=color-picker]').spectrum({
        showAlpha: true,
        allowEmpty: true,
        preferredFormat: "hex",
        chooseText: "Save",
        cancelText: "Cancel",

        // If the following is not set, the color is not actually saved on clickout (even though it looks like it is).
        clickoutFiresChange: false
    })
}

EmailAlerts.Settings.prototype.resetConfigInstances = function() {
    var currentInstance = [];
    var currentFields = [];
    var lastWasEndNode = false;

    // Sync textarea and rich text divs before renaming
    tinyMCE.triggerSave();


    // Loop through each config row to find it's place in the loop
    $("#external-modules-configure-modal tr").each(function() {
        var lastField = currentFields.slice(-1);
        lastField = (lastField.length > 0 ? lastField[0] : false);

        // End current count if next node is different field
        if(lastWasEndNode) {
            if($(this).attr("field") != lastField) {
                // If there's only one instance of the previous field, hide "-" button
                if(currentInstance[currentInstance.length - 1] == 0) {
                    var previousLoopField = currentFields[currentFields.length - 1];
                    var currentTr = $(this).prev();

                    // If merely a single repeating field
                    if(!currentTr.hasClass("sub_end")) {
                        currentTr.find(".external-modules-remove-instance").hide();
                    }
                    else {
                        // Loop backwards until finding a start element matching the previousLoopField
                        while((typeof currentTr !== "undefined") && !(currentTr.hasClass("sub_start") && (currentTr.attr("field") == previousLoopField))) {
                            currentTr = currentTr.prev();
                        }
                        currentTr.find(".external-modules-remove-instance").hide();
                    }
                }
                currentInstance.pop();
                currentFields.pop();
            }
        }

        // Increment or start count on current loop
        if($(this).hasClass("sub_start") || $(this).hasClass("repeatable")) {
            if(lastField == $(this).attr("field")) {
                currentInstance[currentInstance.length - 1]++;
            }
            else {
                currentInstance.push(0);
                currentFields.push($(this).attr("field"));
            }

            if($(this).hasClass("sub_start") && currentInstance.length > 1){
                $(this).addClass("sub_child");
                $(this).removeClass("sub_parent");
            }
        }

        lastWasEndNode = ($(this).hasClass("repeatable") && !$(this).hasClass("sub_start")) || $(this).hasClass("sub_end");

        // Update the number scheme on label and input names
        var currentLabel = "";
        var currentName = "";
        // Use PHP/JSON instance keys, so need to add one to make it look normal
        for(var i = 0; i < currentInstance.length; i++) {
            currentLabel += (currentInstance[i] + 1) + ".";
            currentName += EmailAlerts.Settings.prototype.getInstanceSymbol() + currentInstance[i];
        }

        $(this).find(".external-modules-instance-label").html(currentLabel + " ");
        $(this).find("input, select, textarea").attr("name",$(this).attr("field") + currentName);

        // The following is required because browsers will only check the last radio button
        // if two checked radio buttons are inserted into the DOM with the same name.  For example:
        //    document.body.innerHtml = '<input type="radio" name="test" checked><input type="radio" name="test" checked>'
        // We must go back through and check them manually AFTER field names have been adjusted to account for repeatable/subsetting instances.
        $(this).find("input[type=radio][checked]").prop('checked', true)
    });
};

EmailAlerts.Settings.prototype.initializeRichTextFields = function(){
    $(".external-modules-autocomplete-dropdown").select2();

    var settingsObject = this;

    // The decision to use TinyMCE was not taken lightly.  We tried integrating Quill, Trix, and Summernote as well, but they either
    // didn't work as well out of the box when placed inside the configuration model, or were not as flexible/customizable.
    var tinyLang = module.tt('em_tinymce_language');
    if (tinyLang == null) tinyLang = 'en_US';
    // Since the corresponding en_US.js is missing in the 'tinymce/langs' directory, unset if en_US.
    if (tinyLang == "en_US") tinyLang = undefined;

    tinymce.init({
        selector: "textarea#email-text",
        language: tinyLang,
        height: 400,
        menubar: false,
        branding: false,
        elementpath: false, // Hide this, since it oddly renders below the textarea.
        plugins: ['autolink', 'lists', 'link', 'image', 'charmap', 'hr', 'anchor', 'pagebreak', 'searchreplace', 'code', 'fullscreen', 'insertdatetime', 'media', 'nonbreaking', 'table', 'directionality', 'imagetools'],
        toolbar1: 'undo redo | image | styles | bold italic | alignleft aligncenter alignright alignjustify',
        toolbar2: 'outdent indent | bullist numlist | table | link | forecolor backcolor | searchreplace fullscreen code',
        relative_urls : true, // force image urls to be absolute
        document_base_url : "http://www.example.com/path1/",
        file_picker_callback: function(callback, value, meta){
            var prefix = settingsObject.getPrefix();
            tinymce.activeEditor.windowManager.open({
                url: EmailAlerts.BASE_URL + '/manager/rich-text/get-uploaded-file-list.php?prefix=' + prefix + '&pid=' + pid,
                width: 500,
                height: 300,
                title: 'Files',
                onOpen: function(data){
                    // Show a loading indicator.

                    var window = data.target.$el
                    var iframe = window.find('.mce-window-body iframe')

                    var loading = $('<div></div>')
                    iframe.on('load', function(){
                        loading.hide()
                    })

                    iframe.before(loading)
                    new Spinner().spin(loading[0]);
                }
            });

            EmailAlerts.currentFilePickerCallback = function(url){
                tinymce.activeEditor.windowManager.close()
                callback(url)
            }
        }
    });
    //UPDATE
    tinymce.init({
        selector: "textarea#email-text-update",
        language: tinyLang,
        height: 400,
        menubar: false,
        branding: false,
        elementpath: false, // Hide this, since it oddly renders below the textarea.
        plugins: ['autolink', 'lists', 'link', 'image', 'charmap', 'hr', 'anchor', 'pagebreak', 'searchreplace', 'code', 'fullscreen', 'insertdatetime', 'media', 'nonbreaking', 'table', 'directionality', 'imagetools'],
        toolbar1: 'undo redo | image | styles | bold italic | alignleft aligncenter alignright alignjustify',
        toolbar2: 'outdent indent | bullist numlist | table | link | forecolor backcolor | searchreplace fullscreen code',
        relative_urls : true, // force image urls to be absolute
        document_base_url : "http://www.example.com/path1/",
        file_picker_callback: function(callback, value, meta){
            var prefix = settingsObject.getPrefix();
            tinymce.activeEditor.windowManager.open({
                url: EmailAlerts.BASE_URL + '/manager/rich-text/get-uploaded-file-list.php?prefix=' + prefix + '&pid=' + pid,
                width: 500,
                height: 300,
                title: 'Files',
                onOpen: function(data){
                    // Show a loading indicator.

                    var window = data.target.$el
                    var iframe = window.find('.mce-window-body iframe')

                    var loading = $('<div></div>')
                    iframe.on('load', function(){
                        loading.hide()
                    })

                    iframe.before(loading)
                    new Spinner().spin(loading[0]);
                }
            });

            EmailAlerts.currentFilePickerCallback = function(url){
                tinymce.activeEditor.windowManager.close()
                callback(url)
            }
        }
    });
}

$(function(){
    var settings = new EmailAlerts.Settings();

    var onValueChange = function() {
        var val;
        if (this.type == "checkbox") {
            val = $(this).is(":checked");
        } else {
            val = $(this).val();
        }

        EmailAlerts.Settings.prototype.doBranching();
    };

    $('#external-modules-configure-modal').on('change', '.external-modules-input-element', onValueChange);
    $('#external-modules-configure-modal').on('check', '.external-modules-input-element', onValueChange);

    /**
     * Function to add new elements
     */
    $('#external-modules-configure-modal').on('click', '.external-modules-add-instance-subsettings, .external-modules-add-instance', function(){
        // Get the full configuration for the active module from the global variable
        var config = EmailAlerts.configsByPrefix[configureModal.data('module')];

        // Find the setting currently being added to and its configuration
        var name = $(this).attr('setting');
        var setting = EmailAlerts.Settings.prototype.findSettings(config,name);
        //console.log(config);
        //console.log(name);
        //console.log(setting);
        if(typeof setting !== "undefined") {
            // Create new html for this setting
            var html = EmailAlerts.Settings.prototype.getSettingRows([setting],[{}]);

            var thisTr = $(this).closest("tr");

            if(thisTr.hasClass("sub_start")) {
                thisTr = EmailAlerts.Settings.prototype.getEndOfSub(thisTr);
            }
            thisTr.after(html);
        }

        settings.configureSettings();
    });

    /**
     * function to remove the elements
     */
    $('#external-modules-configure-modal').on('click', '.external-modules-remove-instance-subsettings, .external-modules-remove-instance', function(){
        var startTr = $(this).closest('tr');

        // If this element is a sub_setting element, loop through until reaching the end
        // of this setting's rows
        if(startTr.hasClass("sub_start")) {
            var lastTr = EmailAlerts.Settings.prototype.getEndOfSub(startTr);

            // Remove all the elements between start and end. Then remove last element.
            startTr.nextUntil(lastTr).remove();
            lastTr.remove();
        }

        // Clean up by removing the original element
        startTr.remove();

        tinymce.editors.forEach(function(editor, index){
            if(!document.body.contains(editor.getElement())){
                // The element for this editor was removed from the DOM.  Destroy the editor.
                editor.remove()
            }
        })

        settings.resetConfigInstances();
    });

    // Merged from updated enabled-modules, may need to reconfigure
    EmailAlerts.configsByPrefix = EmailAlerts.configsByPrefixJSON;
    EmailAlerts.versionsByPrefix = EmailAlerts.versionsByPrefixJSON;

    var pid = EmailAlerts.PID;
    var pidString = pid;
    if(pid === null){
        pidString = '';
    }
    var configureModal = $('#external-modules-configure-modal');
    // may need to reconfigure
    var isSuperUser = (EmailAlerts.SUPER_USER == 1);

    // Shared function for combining 2 arrays to produce an attribute string for an HTML object
    $('#external-modules-enabled').on('click', '.external-modules-configure-button', function(){
        // find the module directory prefix from the <tr>
        var moduleDirectoryPrefix = $(this).closest('tr').data('module');
        configureModal.data('module', moduleDirectoryPrefix);

        var config = EmailAlerts.configsByPrefix[moduleDirectoryPrefix];
        configureModal.find('.module-name').html(config.name);
        var tbody = configureModal.find('tbody');

        var loading = $('<div style="margin-top: 400px">')
        new Spinner().spin(loading[0]);
        tbody.html(loading);

        // Param list to pass to get-settings.php
        var params = {moduleDirectoryPrefix: moduleDirectoryPrefix};
        if (pid) {
            params['pid'] = pidString;
        }

        var getSettings = function(callback){
            // Get the existing values for this module through ajax
            $.post('ajax/get-settings.php', params, function(data){
                if(data.status != 'success'){
                    return;
                }

                var savedSettings = data.settings;

                // Get the html for the configuration
                var settingsHtml = "";

                if(pid) {
                    settingsHtml += settings.getSettingRows(config['project-settings'], savedSettings);
                }
                else {
                    settingsHtml += settings.getSettingRows(config['system-settings'], savedSettings);
                }

                // Add blank tr to end of table to make resetConfigInstances work better
                settingsHtml += "<tr style='display:none'></tr>";

                tbody.html(settingsHtml);

                callback()
            });
        }

        configureModal.on('shown.bs.modal', function () {
            configureModal.off('shown.bs.modal')

            getSettings(function(){
                settings.configureSettings();
                settings.doBranching();
            })
        })

        configureModal.modal('show');
    });


    var deleteFile = function(ob) {
        var moduleDirectoryPrefix = configureModal.data('module');

        var row = ob.closest("tr");
        var input = row.find("input[type=hidden]");
        var disabled = input.prop("disabled");
        var deleteFileButton = row.find("button.external-modules-delete-file");
        if (deleteFileButton) {
            deleteFileButton.hide();
        }

        $.post("ajax/delete-file.php?pid="+pidString, { moduleDirectoryPrefix: moduleDirectoryPrefix, key: input.attr('name'), edoc: input.val() }, function(data) {
            if (data.status == "success") {
                var inputAttributes = "";
                if (disabled) {
                    inputAttributes = "disabled";
                }
                row.find(".external-modules-edoc-file").html(settings.getProjectFileFieldElement(input.attr('name'), "", inputAttributes));
                input.remove();
            } else {		// failure
                //= The file was not able to be deleted.
                alert(module.tt('em_errors_94')+' '+JSON.stringify(data));
            }
        });
    };
    configureModal.on('click', '.external-modules-delete-file', function() {
        deleteFile($(this));
    });

    var resetSaveButton = function() {
        if ($(this).val() != "") {
            //= Save and Upload
            $(".save").html(module.tt('em_manage_74'));
        }
        var allEmpty = true;
        $("input[type=file]").each(function() {
            if ($(this).val() !== "") {
                allEmpty = false;
            }
        });
        if (allEmpty) {
            //= Save
            $(".save").html(module.tt('em_manage_13'));
        }
    }

    configureModal.on('change', 'input[type=file]', resetSaveButton);

    // helper method for saving
    var saveFilesIfTheyExist = function(url, files, callbackWithNoArgs) {
        var lengthOfFiles = 0;
        var formData = new FormData();
        for (var name in files) {
            lengthOfFiles++;
            formData.append(name, files[name]);   // filename agnostic
        }

        if (lengthOfFiles > 0) {
            // AJAX rather than $.post
            $.ajax({
                url: url,
                data: formData,
                processData: false,
                contentType: false,
                async: false,
                type: 'POST',
                success: function(returnData) {
                    if (returnData.status != 'success') {
                        //= One or more of the files could not be saved.
                        alert(module.tt('em_errors_95')+' '+JSON.stringify(returnData));
                    }
                    // proceed anyways to save data
                    callbackWithNoArgs();
                },
                error: function(e) {
                    //= One or more of the files could not be saved.
                    alert(module.tt('em_errors_95')+' '+JSON.stringify(e));
                    callbackWithNoArgs();
                }
            });
        } else {
            callbackWithNoArgs();
        }
    }

    // helper method for saving
    var saveSettings = function(pidString, moduleDirectoryPrefix, version, data) {
        $.post(
            'ajax/save-settings.php?pid=' + pidString + '&moduleDirectoryPrefix=' + moduleDirectoryPrefix,
            {
                // Pass the JSON data as a form parameter instead of directly so that REDCap automatic CSRF token adding/checking/removing works properly.
                settings: JSON.stringify(data)
            }
        ).done( function(returnData){
            if(returnData.status != 'success'){
                //= An error occurred while saving settings:
                alert(module.tt('em_errors_96')+'\n\n'+returnData);
                configureModal.show();
                return;
            }

            // Reload the page reload after saving settings,
            // in case a settings affects some page behavior (like which menu items are visible).
            location.reload();
        });
    }

    configureModal.on('click', 'button.save', function(){
        var moduleDirectoryPrefix = configureModal.data('module');
        var version = EmailAlerts.versionsByPrefix[moduleDirectoryPrefix];

        var errorMessage = EmailAlerts.validateSettings(configureModal)
        if(errorMessage){
            simpleDialog(errorMessage,
                //= Error
                module.tt('em_manage_75'))
            return
        }

        var data = {};
        var files = {};
        var requiredFieldErrors = 0;
        configureModal.find('tr.requiredm td.external-modules-input-td :input').each(function(index, element){
            if ($(this).val() == '' && $(this).attr('type') != 'checkbox' && !$(this).is('button')) {
                requiredFieldErrors++;
            }
        });
        //= SOME SETTINGS REQUIRE A VALUE!
        //=
        //= It appears that some settings are required but are missing a value. If you wish to go back and enter more values, click CANCEL. If you wish to save the current settings, click OK.
        if (requiredFieldErrors > 0 && !confirm(module.tt('em_manage_76'))) {
            return;
        }

        configureModal.hide();

        configureModal.find('input, select, textarea').each(function(index, element){
            var element = $(element);
            var name = element.attr('name');
            var type = element[0].type;

            if(!name || (type == 'radio' && !element.is(':checked'))){
                return;
            }

            if (type == 'file') {
                // We do not reach this point for previously saved file fields (is that intentional?!?!?!).
                // only store one file per variable - the first file
                jQuery.each(element[0].files, function(i, file) {
                    if (typeof files[name] == "undefined") {
                        files[name] = file;
                    }
                });
            } else if(EmailAlerts.resetFileFields.indexOf(name) !== -1) {
                var url = 'ajax/save-file.php?pid=' + pidString +
                    '&moduleDirectoryPrefix=' + moduleDirectoryPrefix +
                    '&moduleDirectoryVersion=' + version;

                var formData = name + "=" + element.val();

                $.ajax({
                    url: url,
                    data: formData,
                    type: 'POST',
                    success: function(returnData) {
                    },
                    error: function(e) {
                        //= Error cleaning {0}
                        alert(module.tt('em_errors_97', name));
                    }
                });
            } else {
                var value;
                if(type == 'checkbox'){
                    if(element.prop('checked')){
                        value = true;
                    }
                    else{
                        value = false;
                    }
                }
                else if(element.hasClass('external-modules-rich-text-field')){
                    var id = element.attr('id');
                    value = tinymce.get(id).getContent();
                }
                else{
                    value = element.val();
                }

                data[name] = value;
            }
        });

        var url = 'ajax/save-file.php?pid=' + pidString +
            '&moduleDirectoryPrefix=' + moduleDirectoryPrefix +
            '&moduleDirectoryVersion=' + version;
        saveFilesIfTheyExist(url, files, function() {
            saveSettings(pidString, moduleDirectoryPrefix, version, data);
        });
    });

    configureModal.on('hidden.bs.modal', function () {
        tinymce.remove()
    })

    $('.external-modules-usage-button').click(function(){
        var row = $(this).closest('tr');
        var prefix = row.data('module')
        $.get('ajax/usage.php', {prefix: prefix}, function(data){
            if(data == ''){
                data = module.tt('em_manage_77') //= None
            }

            var modal = $('#external-modules-usage-modal')
            //= Project Usage:
            modal.find('.modal-title').html(module.tt('em_manage_78')+'<br><b>' + row.find('.external-modules-title').text() + '</b>')
            modal.find('.modal-body').html(data)
            modal.modal('show')
        })
    })
});