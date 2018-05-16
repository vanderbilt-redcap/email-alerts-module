<?php
namespace Vanderbilt\EmailTriggerExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

require_once APP_PATH_DOCROOT.'Classes/Files.php';
require_once 'vendor/autoload.php';


class EmailTriggerExternalModule extends AbstractExternalModule
{
	private $email_requested = false;

	public function __construct(){
		parent::__construct();
		$this->disableUserBasedSettingPermissions();
	}

	function hook_survey_complete ($project_id,$record = NULL,$instrument,$event_id, $group_id, $survey_hash,$response_id, $repeat_instance){
        $data = \REDCap::getData($project_id);
        $this->setEmailTriggerRequested(false);
        if(isset($project_id)){
            #Form Complete
            $forms_name = $this->getProjectSetting("form-name",$project_id);
            if(!empty($forms_name) && $record != NULL){
                foreach ($forms_name as $id => $form){
                    $sql="SELECT s.form_name FROM redcap_surveys_participants as sp LEFT JOIN redcap_surveys s ON (sp.survey_id = s.survey_id ) where s.project_id =".$project_id." AND sp.hash='".$_REQUEST['s']."'";
                    $q = $this->query($sql);

                    if($error = db_error()){
                        throw new \Exception($sql.': '.$error);
                    }

                    while($row = db_fetch_assoc($q)){
                        if ($row['form_name'] == $form) {
                            $isRepeatInstrument = false;
                            if((array_key_exists('repeat_instances',$data[$record]) && ($data[$record]['repeat_instances'][$event_id][$form][$repeat_instance][$form.'_complete'] != '' || $data[$record]['repeat_instances'][$event_id][''][$repeat_instance][$form.'_complete'] != ''))){
                                $isRepeatInstrument = true;
                            }

                            $this->setEmailTriggerRequested(true);
                            $email_sent = $this->getProjectSetting("email-sent",$project_id);
                            $email_timestamp_sent = $this->getProjectSetting("email-timestamp-sent",$project_id);
                            $email_repetitive_sent = $this->getProjectSetting("email-repetitive-sent",$project_id);
                            $this->sendEmailAlert($project_id, $id, $data, $record,$email_sent,$email_timestamp_sent,$email_repetitive_sent,$event_id,$instrument,$repeat_instance,$isRepeatInstrument);
                        }
                    }
                }
            }
        }
    }

    function hook_save_record ($project_id,$record = NULL,$instrument,$event_id, $group_id, $survey_hash,$response_id, $repeat_instance){
        $data = \REDCap::getData($project_id);
        $this->setEmailTriggerRequested(false);
        if(isset($project_id)){
            #Form Complete
            $forms_name = $this->getProjectSetting("form-name",$project_id);
            if(!empty($forms_name) && $record != NULL){
                foreach ($forms_name as $id => $form){
                    $form_name_event_id = $this->getProjectSetting("form-name-event", $project_id)[$id];
                    $isLongitudinalData = false;
                    if(\REDCap::isLongitudinal() && !empty($form_name_event_id)){
                        $isLongitudinalData = true;
                    }
                    $isRepeatInstrumentComplete = $this->isRepeatInstrumentComplete($data, $record, $event_id, $form, $repeat_instance);
                    $isRepeatInstrument = false;
                    if((array_key_exists('repeat_instances',$data[$record]) && ($data[$record]['repeat_instances'][$event_id][$form][$repeat_instance][$form.'_complete'] != '' || $data[$record]['repeat_instances'][$event_id][''][$repeat_instance][$form.'_complete'] != ''))){
                        $isRepeatInstrument = true;
                    }
                    $email_incomplete = $this->getProjectSetting("email-incomplete",$project_id)[$id];
                    if($data[$record][$event_id][$form.'_complete'] == '2' || $isRepeatInstrumentComplete || $email_incomplete == "1"){
                        if(($event_id == $form_name_event_id && $isLongitudinalData) || !$isLongitudinalData){
                            if ($_REQUEST['page'] == $form) {
                                $this->setEmailTriggerRequested(true);
                                $email_sent = $this->getProjectSetting("email-sent",$project_id);
                                $email_timestamp_sent = $this->getProjectSetting("email-timestamp-sent",$project_id);
                                $email_repetitive_sent = $this->getProjectSetting("email-repetitive-sent",$project_id);
                                $this->sendEmailAlert($project_id, $id, $data, $record,$email_sent,$email_timestamp_sent,$email_repetitive_sent,$event_id,$instrument,$repeat_instance,$isRepeatInstrument);
                            }
                        }
                    }
                }
            }
        }
    }

    function hook_every_page_before_render($project_id = null){
        if(strpos($_SERVER['REQUEST_URI'],'erase_project_data.php') !== false && $_POST['action'] == 'erase_data'){
            $this->setProjectSetting('email-repetitive-sent', '');
        }else if($_REQUEST['route'] == 'DataEntryController:deleteRecord'){
            $record_id = $_REQUEST['record'];

            #Delete email repetitive sent from JSON before deleting all data
            $email_repetitive_sent =  empty($this->getProjectSetting('email-repetitive-sent'))?array():$this->getProjectSetting('email-repetitive-sent');
            $email_repetitive_sent = json_decode($email_repetitive_sent,true);

            if(!empty($email_repetitive_sent)) {
                foreach ($email_repetitive_sent as $form => $form_value) {
                    foreach ($form_value as $alert => $alert_value) {
                        $one_less = 0;
                        foreach ($alert_value as $record => $value) {
                            //we don't add the deleted alert and rename the old ones.
                            if ($value == $record_id) {
                                $one_less = 1;
                            }else if($record >= 0){
                                //if the record is -1 do not add it. When copying a project sometimes it has a weird config.
                                $jsonArray[$form][$alert][$record - $one_less] = $value;
                            }
                        }
                    }
                }
                $this->setProjectSetting('email-repetitive-sent', json_encode($jsonArray));
            }
        }
    }

    /**
     *To call externally to see if the email has been requested to send or not.
     * It is used in other Plugins
     *
     */
    function getEmailTriggerRequested(){
        return $this->email_requested;
    }

    function setEmailTriggerRequested($email_requested){
       $this->email_requested =  $email_requested;
    }

    function sendEmailAlert($project_id, $id, $data, $record,$email_sent,$email_timestamp_sent,$email_repetitive_sent,$event_id,$instrument,$repeat_instance,$isRepeatInstrument){
        //memory increase
        ini_set('memory_limit', '512M');

        $email_repetitive = $this->getProjectSetting("email-repetitive",$project_id)[$id];
        $email_deactivate = $this->getProjectSetting("email-deactivate",$project_id)[$id];
        $email_deleted = $this->getProjectSetting("email-deleted",$project_id)[$id];
        $email_repetitive_sent = json_decode($email_repetitive_sent);
        $email_condition = $this->getProjectSetting("email-condition", $project_id)[$id];
        if((($email_repetitive == "1") || ($email_repetitive == '0' && !$this->isEmailAlreadySentForThisSurvery($email_repetitive_sent, $record, $instrument,$id,$isRepeatInstrument,$repeat_instance))) && ($email_deactivate == "0" || $email_deactivate == "") && ($email_deleted == "0" || $email_deleted == "")) {
            //If the condition is met or if we don't have any, we send the email
            $evaluateLogic = \REDCap::evaluateLogic($email_condition, $project_id, $record,$event_id);
            if($isRepeatInstrument){
                $evaluateLogic = \REDCap::evaluateLogic($email_condition, $project_id, $record,$event_id, $repeat_instance, $instrument);
            }
            if ((!empty($email_condition) && \LogicTester::isValid($email_condition) && $evaluateLogic) || empty($email_condition)) {
                $email_from = $this->getProjectSetting("email-from", $project_id)[$id];
                $email_to = $this->getProjectSetting("email-to", $project_id)[$id];
                $email_cc = $this->getProjectSetting("email-cc", $project_id)[$id];
                $email_bcc = $this->getProjectSetting("email-bcc", $project_id)[$id];
                $email_subject = $this->getProjectSetting("email-subject", $project_id)[$id];
                $email_text = $this->getProjectSetting("email-text", $project_id)[$id];
                $email_attachment_variable = $this->getProjectSetting("email-attachment-variable", $project_id)[$id];
                $datapipe_var = $this->getProjectSetting("datapipe_var", $project_id);
                $datapipeEmail_var = $this->getProjectSetting("datapipeEmail_var", $project_id);
                $surveyLink_var = $this->getProjectSetting("surveyLink_var", $project_id);

                //To ensure it's the last module called
                $delayedSuccessful =  $this->delayModuleExecution();
                if($delayedSuccessful){
                    return;
                }
                //Data piping
                if (!empty($datapipe_var)) {
                    $datapipe = explode("\n", $datapipe_var);
                    foreach ($datapipe as $emailvar) {
                        $var = preg_split("/[;,]+/", $emailvar)[0];
                        if (\LogicTester::isValid($var)) {
                            //Repeatable instruments
                            $logic = $this->isRepeatingInstrument($project_id, $data, $record, $event_id, $instrument, $repeat_instance, $var,0);
                            $label = $this->getLogicLabel($var,$logic,$project_id,$data,$event_id,$record,$repeat_instance);
//                            $label = $this->getChoiceLabel(array('field_name'=>$var, 'value'=>$logic, 'project_id'=>$project_id, 'record_id'=>$record,'event_id'=>$event_id,'survey_form'=>$instrument,'instance'=>$repeat_instance));
                            if(!empty($label)){
                                $logic = $label;
                            }
                            $email_text = str_replace($var, $logic, $email_text);
                            $email_subject = str_replace($var, $logic, $email_subject);

                        }
                    }
                }
                //Survey Link
                if(!empty($surveyLink_var)) {
                    $datasurvey = explode("\n", $surveyLink_var);
                    foreach ($datasurvey as $surveylink) {
                        $var = preg_split("/[;,]+/", $surveylink)[0];

                        $form_event_id = $event_id;
                        if(\REDCap::isLongitudinal()) {
                            preg_match_all("/\[[^\]]*\]/", $var, $matches);
                            if (sizeof($matches[0]) > 1) {
                                $var = $matches[0][1];
                                $form_name = str_replace('[', '', $matches[0][0]);
                                $form_name = str_replace(']', '', $form_name);
                                $form_event_id = \REDCap::getEventIdFromUniqueEvent($form_name);
                            }
                        }

                        //only if the variable is in the text we reset the survey link status
                        if (strpos($email_text, $var) !== false) {
                            $instrument_form = str_replace('[__SURVEYLINK_', '', $var);
                            $instrument_form = str_replace(']', '', $instrument_form);
                            $passthruData = $this->resetSurveyAndGetCodes($project_id, $record, $instrument_form, $form_event_id);

                            $returnCode = $passthruData['return_code'];
                            $hash = $passthruData['hash'];

                            $url = $this->getUrl('surveyPassthru.php') . "&instrument=" . $instrument_form . "&record=" . $record . "&returnCode=" . $returnCode."&NOAUTH";
                            $link = "<a href='" . $url . "' target='_blank'>" . $url . "</a>";
                            $email_text = str_replace( preg_split("/[;,]+/", $surveylink)[0], $link, $email_text);
                        }
                    }
                }
                $mail = new \PHPMailer;

                //Email Addresses
                if (!empty($datapipeEmail_var)) {
                    $email_form_var = explode("\n", $datapipeEmail_var);

                    $emailsTo = preg_split("/[;,]+/", $email_to);
                    $emailsCC = preg_split("/[;,]+/", $email_cc);
                    $emailsBCC = preg_split("/[;,]+/", $email_bcc);
                    $mail = $this->fill_emails($mail,$emailsTo, $email_form_var, $data, 'to',$project_id,$record, $event_id, $instrument, $repeat_instance);
                    $mail = $this->fill_emails($mail,$emailsCC, $email_form_var, $data, 'cc',$project_id,$record, $event_id, $instrument, $repeat_instance);
                    $mail = $this->fill_emails($mail,$emailsBCC, $email_form_var, $data, 'bcc',$project_id,$record, $event_id, $instrument, $repeat_instance);
                }else{
                    $email_to_ok = $this->check_email ($email_to,$project_id);
                    $email_cc_ok = $this->check_email ($email_cc,$project_id);
                    $email_bcc_ok = $this->check_email ($email_bcc,$project_id);

                    if(!empty($email_to_ok)) {
                        foreach ($email_to_ok as $email) {
                            $mail = $this->check_single_email($mail,$email, 'to', $project_id);
                        }
                    }

                    if(!empty($email_cc_ok)){
                        foreach ($email_cc_ok as $email) {
                            $mail = $this->check_single_email($mail,$email, 'cc', $project_id);
                        }
                    }

                    if(!empty($email_bcc_ok)){
                        foreach ($email_bcc_ok as $email) {
                            $mail = $this->check_single_email($mail,$email, 'bcc', $project_id);
                        }
                    }
                }

                //Email From
                if(!empty($email_from)){
                    $from_data = preg_split("/[;,]+/", $email_from);
                    if(filter_var(trim($from_data[0]), FILTER_VALIDATE_EMAIL)) {
                        if($from_data[1] == '""' || empty($from_data[1])){
                            $mail->SetFrom($from_data[0]);
                        }else{
                            $mail->SetFrom($from_data[0], $from_data[1]);
                        }

                    }else{
                        $this->sendFailedEmailRecipient($this->getProjectSetting("emailFailed_var", $project_id),"Wrong recipient" ,"The email ".$from_data[0]." in Project: ".$project_id.", Record: ".$record." Alert #".$id.", does not exist");
                    }
                }else{
                    $this->sendFailedEmailRecipient($this->getProjectSetting("emailFailed_var", $project_id),"Sender is empty" ,"The sender in Project: ".$project_id.", Record: ".$record." Alert #".$id.", is empty.");
                }

                //Embedded images
                preg_match_all('/src=[\"\'](.+?)[\"\'].*?/i',$email_text, $result);
                $result = array_unique($result[1]);
                foreach ($result as $img_src){
                    preg_match_all('/(?<=file=)\\s*([0-9]+)\\s*/',$img_src, $result_img);
                    $edoc = array_unique($result_img[1])[0];
                    if(is_numeric($edoc)){
                        $this->addNewAttachment($mail,$edoc,$project_id,'images');

                        if(!empty($edoc)) {
                            $src = "cid:" . $edoc;
                            $email_text = str_replace($img_src, $src, $email_text);
                        }
                    }
                }

                $mail->CharSet = 'UTF-8';
                $mail->Subject = $email_subject;
                $mail->IsHTML(true);
                $mail->Body = $email_text;

                //Attachments
                for($i=1; $i<6 ; $i++){
                    $edoc = $this->getProjectSetting("email-attachment".$i,$project_id)[$id];
                    if(is_numeric($edoc)){
                        $this->addNewAttachment($mail,$edoc,$project_id,'files');
                    }
                }
                //Attchment from RedCap variable
                if(!empty($email_attachment_variable)){
                    $var = preg_split("/[;,]+/", $email_attachment_variable);
                    foreach ($var as $attachment) {
                        if(\LogicTester::isValid(trim($attachment))) {
                            $edoc = $this->isRepeatingInstrument($project_id,$data, $record, $event_id, $instrument, $repeat_instance, $attachment,0);
                            if(is_numeric($edoc)) {
                                $this->addNewAttachment($mail, $edoc, $project_id, 'files');
                            }
                        }
                    }
                }

                //DKIM to make sure the email does not go into spam folder
                $privatekeyfile = 'dkim_private.key';
                //Make a new key pair
                //(2048 bits is the recommended minimum key length -
                //gmail won't accept less than 1024 bits)
                $pk = openssl_pkey_new(
                    array(
                        'private_key_bits' => 2048,
                        'private_key_type' => OPENSSL_KEYTYPE_RSA
                    )
                );
                openssl_pkey_export_to_file($pk, $privatekeyfile);
                $mail->DKIM_private = $privatekeyfile;
                $mail->DKIM_selector = 'PHPMailer';
                $mail->DKIM_passphrase = ''; //key is not encrypted
                if (!$mail->send()) {
                   $this->sendFailedEmailRecipient($this->getProjectSetting("emailFailed_var", $project_id),"Mailer Error" ,"Mailer Error:".$mail->ErrorInfo." in Project: ".$project_id.", Record: ".$record." Alert #".$id);

                } else {
                    $email_sent[$id] = "1";
                    $email_timestamp_sent[$id] = date('Y-m-d H:i:s');
                    $this->setProjectSetting('email-timestamp-sent', $email_timestamp_sent, $project_id) ;

                    $this->setProjectSetting('email-sent', $email_sent, $project_id) ;

                    $email_repetitive_sent = $this->addJSONRecord($email_repetitive_sent,$record,$instrument,$id,$isRepeatInstrument,$repeat_instance);
                    $this->setProjectSetting('email-repetitive-sent', $email_repetitive_sent, $project_id) ;

                    //Add some logs
                    $action_description = "Email Sent - Alert ".$id;
                    $changes_made = "[Subject]: ".$email_subject.", [Message]: ".$email_text;
                    \REDCap::logEvent($action_description,$changes_made,NULL,$record,$event_id,$project_id);

                    $action_description = "Email Sent To - Alert ".$id;
                    $email_list = '';
                    foreach ($mail->getAllRecipientAddresses() as $email=>$value){
                        $email_list .= $email.";";
                    }
                    \REDCap::logEvent($action_description,$email_list,NULL,$record,$event_id,$project_id);

                }
                unlink($privatekeyfile);
                // Clear all addresses and attachments for next loop
                $mail->clearAddresses();
                $mail->clearAttachments();
            }
        }
    }

    /**
     * Function that checks if an instruments os repeatables AND complete
     * @param $data
     * @param $record
     * @param $event_id
     * @param $instrument
     * @param $instance
     * @return bool
     */
    function isRepeatInstrumentComplete($data, $record, $event_id, $instrument, $instance){
        if((array_key_exists('repeat_instances',$data[$record]) && ($data[$record]['repeat_instances'][$event_id][$instrument][$instance][$instrument.'_complete'] == '2' || $data[$record]['repeat_instances'][$event_id][''][$instance][$instrument.'_complete'] == '2'))){
            return true;
        }
        return false;
    }

    /**
     * Function that checks in the JSON if an email has already been sent by [survey][alert][record]
     * @param $email_repetitive_sent, the JSON
     * @param $new_record, the new record
     * @param $instrument, the survey
     * @param $alertid, the email alert
     * @return bool
     */
    function isEmailAlreadySentForThisSurvery($email_repetitive_sent, $record, $instrument, $alertid,$isRepeatInstrument,$repeat_instance){
        if(!empty($email_repetitive_sent)){
            foreach ($email_repetitive_sent as $sv_name => $survey_records){
                if($sv_name == $instrument) {
                    foreach ($survey_records as $alert => $alert_value) {
                        if($alertid == $alert) {
                            foreach ($alert_value as $sv_number => $survey_record) {
                                if($isRepeatInstrument){
                                    if($sv_number == 'repeat_instances'){
                                        foreach ($survey_record as $record_repeat => $record_value) {
                                            if ($record == $record_repeat) {
                                                foreach ($record_value as $instance => $instance_value) {
                                                    if ($repeat_instance == $instance_value) {
                                                        return true;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }else if ($record == $survey_record) {
                                    return true;
                                }
                            }
                        }
                    }
                }
            }
        }
        return false;
    }

    /**
     * Function that checks that returns the logic depending on if it's a repeating instrument
     * @param $data
     * @param $record
     * @param $event_id
     * @param $instrument
     * @param $repeat_instance
     * @param $var
     * @return mixed
     */
    function isRepeatingInstrument($project_id,$data, $record, $event_id, $instrument, $repeat_instance, $var, $option=null){
        $var_name = str_replace('[', '', $var);
        $var_name = str_replace(']', '', $var_name);
        if(array_key_exists('repeat_instances',$data[$record]) && $data[$record]['repeat_instances'][$event_id][$instrument][$repeat_instance][$var_name] != "") {
            //Repeating instruments by form
            $logic = $data[$record]['repeat_instances'][$event_id][$instrument][$repeat_instance][$var_name];
        }else if(array_key_exists('repeat_instances',$data[$record]) && $data[$record]['repeat_instances'][$event_id][''][$repeat_instance][$var_name] != "") {
            //Repeating instruments by event
            $logic = $data[$record]['repeat_instances'][$event_id][''][$repeat_instance][$var_name];
        }else{
            if($option == '1'){
                if(\REDCap::isLongitudinal() && \LogicTester::apply($var, $data[$record], null, true) == ""){
                    $logic = $data[$record][$event_id][$var_name];
                }else{
                    $logic = \LogicTester::apply($var, $data[$record], null, true);
                }
            }else{
                if(\REDCap::isLongitudinal() && \LogicTester::apply($var, $data[$record], null, true) == ""){
                    $logic = $data[$record][$event_id][$var_name];
                }else{
                    preg_match_all("/\[[^\]]*\]/", $var, $matches);
                    //Special case for radio buttons
                    if(sizeof($matches[0]) == 1 && \REDCap::getDataDictionary($project_id,'array',false,$var_name)[$var_name]['field_type'] == "radio"){
                        $logic = $data[$record][$event_id][$var_name];
                    }else{
                        $logic = \LogicTester::apply($var, $data[$record], null, true);
                    }
                }
            }
        }
        return $logic;
    }

    /**
     * Function that returns the label of the certain fields instead of their values
     * @param $var, the field name we want to look for
     * @param $value, the value of the field
     * @param $project_id
     * @param $data, the project data
     * @return string, the label
     */
    function getLogicLabel ($var, $value, $project_id, $data,$event_id,$record,$repeat_instance){
        $data_event = $data[$record][$event_id];
        if(empty($data_event)){
            $data_event = $data[$record]['repeat_instances'][$event_id][''][$repeat_instance];
        }
        $field_name = str_replace('[', '', $var);
        $field_name = str_replace(']', '', $field_name);

		$dateFormats = [
				"date_dmy" => "d-m-Y",
				"date_mdy" => "m-d-Y",
				"date_ymd" => "Y-m-d",
				"datetime_dmy" => "d-m-Y h:i",
				"datetime_mdy" => "m-d-Y h:i",
				"datetime_ymd" => "Y-m-d h:i",
				"datetime_seconds_dmy" => "d-m-Y h:i:s",
				"datetime_seconds_mdy" => "m-d-Y h:i:s",
				"datetime_seconds_ymd" => "Y-m-d  h:i:s"
		];

        $metadata = \REDCap::getDataDictionary($project_id,'array',false,$field_name);
        //event arm is defined
        if(empty($metadata)){
            preg_match_all("/\[[^\]]*\]/", $var, $matches);
            $event_name = str_replace('[', '', $matches[0][0]);
            $event_name = str_replace(']', '', $event_name);

            $field_name = str_replace('[', '', $matches[0][1]);
            $field_name = str_replace(']', '', $field_name);
            $metadata = \REDCap::getDataDictionary($project_id,'array',false,$field_name);
        }
        $label = "";
        if($metadata[$field_name]['field_type'] == 'checkbox' || $metadata[$field_name]['field_type'] == 'dropdown' || $metadata[$field_name]['field_type'] == 'radio'){
            $other_event_id = \REDCap::getEventIdFromUniqueEvent($event_name);
            $choices = preg_split("/\s*\|\s*/", $metadata[$field_name]['select_choices_or_calculations']);
            foreach ($choices as $choice){
                $option_value = preg_split("/,/", $choice)[0];
                if($value != ""){
                    if(is_array($data_event[$field_name])){
                        foreach ($data_event[$field_name] as $choiceValue=>$multipleChoice){
                            if($multipleChoice === "1" && $choiceValue == $option_value) {
                                $label .= trim(preg_split("/^(.+?),/", $choice)[1]).", ";
                            }
                        }
                    }else if($value === $option_value){
                        $label = trim(preg_split("/^(.+?),/", $choice)[1]);
                    }
                }else if($value === $option_value){
                    $label = trim(preg_split("/^(.+?),/", $choice)[1]);
                    break;
                }else if($value == "" && $metadata[$field_name]['field_type'] == 'checkbox'){
                    //Checkboxes for event_arms
                    if( $other_event_id == ""){
                        $other_event_id = $event_id;
                    }
                    if($data[$record][$other_event_id][$field_name][$option_value] == "1"){
                        $label .= trim(preg_split("/^(.+?),/", $choice)[1]).", ";
                    }
                }
            }
            //we delete the last comma and space
            $label = rtrim($label,", ");
        }else if($metadata[$field_name]['field_type'] == 'truefalse'){
            if($value == '1'){
                $label = "True";
            }else if($value == '0'){
                $label = "False";
            }
        }else if($metadata[$field_name]['field_type'] == 'yesno'){
            if($value == '1'){
                $label = "Yes";
            }else if($value == '0'){
                $label = "No";
            }
        }else if($metadata[$field_name]['field_type'] == 'sql'){
            if(!empty($value)) {
                $q = $this->query($metadata[$field_name]['select_choices_or_calculations']);

                if ($error = db_error()) {
                    throw new \Exception($metadata[$field_name]['select_choices_or_calculations'] . ': ' . $error);
                }

                while ($row = db_fetch_assoc($q)) {
                    if($row['record'] == $value ) {
                        $label = $row['value'];
                        break;
                    }
                }
            }
        } else if(in_array($metadata[$field_name]['text_validation_type_or_show_slider_number'],array_keys($dateFormats)) && $value != "") {
			$label = date($dateFormats[$metadata[$field_name]['text_validation_type_or_show_slider_number']],strtotime($value));
		}
        return $label;
    }

    /**
     * Function that replaces the logic variables for email values and checks if they are valid
     * @param $mail
     * @param $emailsTo, liest of emaisl to send as CC or To
     * @param $email_form_var, list of redcap email variables
     * @param $data, redcap data
     * @param $option, if they are To or CC emails
     * @param $project_id
     * @return mixed
     */
    function fill_emails($mail, $emailsTo, $email_form_var, $data, $option, $project_id, $record, $event_id, $instrument, $repeat_instance){
        foreach ($emailsTo as $email){
            foreach ($email_form_var as $email_var) {
                $var = preg_split("/[;,]+/", $email_var);
                if(!empty($email)) {
                    if (\LogicTester::isValid($var[0])) {
                       $email_redcap = $this->isRepeatingInstrument($project_id,$data, $record, $event_id, $instrument, $repeat_instance, $var[0],1);
                       if (!empty($email_redcap) && (strpos($email, $var[0]) !== false || $email_redcap == $email)) {
                            $mail = $this->check_single_email($mail,$email_redcap,$option,$project_id);
                       } else if(filter_var(trim($email), FILTER_VALIDATE_EMAIL) && (empty($email_redcap) || $email != $email_redcap)){
                            $mail = $this->check_single_email($mail,$email,$option,$project_id);
                       }
                    } else {
                        $mail = $this->check_single_email($mail,$email,$option,$project_id);
                    }
                }
            }
        }
        return $mail;
    }

    /**
     * Function that if valid adds an email address to the mail
     * @param $mail
     * @param $email
     * @param $option, if they are To or CC emails
     * @param $project_id
     * @return mixed
     */
    function check_single_email($mail,$email, $option, $project_id){
        if(filter_var(trim($email), FILTER_VALIDATE_EMAIL)) {
            if($option == "to"){
                $mail->addAddress($email);
            }else if($option == "cc"){
                $mail->addCC($email);
            }else if($option == "bcc"){
                $mail->addBCC($email);
            }
        }else{
           $this->sendFailedEmailRecipient($this->getProjectSetting("emailFailed_var", $project_id),"Wrong recipient" ,"The email ".$email." in the project ".$project_id.", do not exist");
        }
        return $mail;
    }

    /**
     * Function to send an extra error email if there is a value in the configuration
     * @param $emailFailed_var
     * @param $subject
     * @param $message
     */
    function sendFailedEmailRecipient($emailFailed_var, $subject, $message){
        if(!empty($emailFailed_var)){
            $emailsFailed = preg_split("/[;,]+/", $emailFailed_var);
            foreach ($emailsFailed as $failed){
                \REDCap::email(trim($failed), 'noreply@vanderbilt.edu',$subject, $message);
            }
        }
    }

    /**
     * Function that checks if the emails are valid and sends an error email in case there's an error
     * @param $emails
     * @param $project_id
     * @return array|string
     */
    function check_email($emails, $project_id){
        $email_list = array();
        $email_list_error = array();
        $emails = preg_split("/[;,]+/", $emails);
        foreach ($emails as $email){
            if(!empty($email)){
                if(filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    //VALID
                    array_push($email_list,$email);
                }else{
                    array_push($email_list_error,$email);

                }
            }
        }
        if(!empty($email_list_error)){
           $this->sendFailedEmailRecipient($this->getProjectSetting("emailFailed_var", $project_id),"Wrong recipient" ,"The email ".$email." in the project ".$project_id.", do not exist");
        }
        return $email_list;
    }

    /**
     * Function that adds a ne attachment (file or image type) to the mail if the file exists in the DB and if it's no bigger than 3MB to send. Otherwise it sends an error email
     * @param $mail
     * @param $edoc
     * @param $project_id
     * @return mixed
     */
    function addNewAttachment($mail,$edoc,$project_id, $type){
        if(!empty($edoc)) {
            $sql = "SELECT stored_name,doc_name,doc_size FROM redcap_edocs_metadata WHERE doc_id=" . $edoc." AND project_id=".$project_id;
            $q = $this->query($sql);

            if ($error = db_error()) {
                throw new \Exception($sql . ': ' . $error);
            }

            while ($row = db_fetch_assoc($q)) {
                if($row['doc_size'] > 3145728 ){
                   $this->sendFailedEmailRecipient($this->getProjectSetting("emailFailed_var", $project_id),"File Size too big" ,"One or more ".$type." in the project ".$project_id.", are too big to be sent.");
                }else{
                    if($type == 'files'){
                        //attach file with a different name
                        $mail->AddAttachment(EDOC_PATH . $row['stored_name'], $row['doc_name']);
                    }else if($type == 'images'){
                        $mail->AddEmbeddedImage(EDOC_PATH . $row['stored_name'],$edoc);
                    }
                }
            }
        }
        return $mail;
    }

    /**
     * Function that creates and returns the JSON of the emails sent by [survey][alert][record]
     * @param $email_repetitive_sent, the JSON
     * @param $new_record, the new record
     * @param $instrument, the survey
     * @param $alertid, the email alert
     * @return string
     */
    function addJSONRecord($email_repetitive_sent, $new_record, $instrument, $alertid,$isRepeatInstrument,$repeat_instance){
        //print_array($email_repetitive_sent);
        $found_new_instrument = true;
        if(!empty($email_repetitive_sent)){
            foreach ($email_repetitive_sent as $sv_name => $survey_records){
                $found_alert = false;
                foreach ($survey_records as $alert => $alert_value){
                    $jsonArray[$sv_name][$alert] = array();
                    $jsonVarArray = array();

                    if($alert == $alertid){
                        $found_alert = true;
                    }

                    $found_record = false;
                    foreach ($alert_value as $sv_number => $survey_record){
                        array_push($jsonVarArray,$survey_record);

                        if($survey_record == $new_record){
                            $found_record = true;
                        }
                    }

                    //If it's the same survey,alert and a new record, we add it
                    if($sv_name == $instrument && $alert == $alertid && !$found_record) {
                        //add new record for specific instrument
                        array_push($jsonVarArray, $new_record);
                    }
                    $jsonArray[$sv_name][$alert] = $jsonVarArray;
                }

                if($sv_name == $instrument){
                    $found_new_instrument = false;
                }

                //NEW Alert same instrument
                if(!$found_alert && $sv_name == $instrument){
                    $jsonArray = $this->addNewJSONRecord($jsonArray,$sv_name,$alertid,$new_record,$isRepeatInstrument,$repeat_instance);
                }
            }
        }else{
            $jsonArray = $this->addNewJSONRecord([],$instrument,$alertid,$new_record,$isRepeatInstrument,$repeat_instance);
        }

        //add new record for new survey
        if($found_new_instrument){
            $jsonArray = $this->addNewJSONRecord($jsonArray,$instrument,$alertid,$new_record,$isRepeatInstrument,$repeat_instance);
        }

        //print_array($jsonArray);
        //die;
        return json_encode($jsonArray,JSON_FORCE_OBJECT);
    }

    /**
     * Function that adds a new record in the JSON
     * @param $jsonArray
     * @param $instrument
     * @param $alertid
     * @param $new_record
     * @return mixed
     */
    function addNewJSONRecord($jsonArray, $instrument, $alertid, $new_record,$isRepeatInstrument,$repeat_instance){
        if($isRepeatInstrument){
            $jsonArray[$instrument][$alertid]['repeat_instances'][$new_record] = array();
            $jsonVarArray = array();
            array_push($jsonVarArray,$repeat_instance);
            $jsonArray[$instrument][$alertid]['repeat_instances'][$new_record] = $jsonVarArray;
        }else{
            $jsonArray[$instrument][$alertid] = array();
            $jsonVarArray = array();
            array_push($jsonVarArray,$new_record);
            $jsonArray[$instrument][$alertid] = $jsonVarArray;
        }

        return $jsonArray;
    }


}



