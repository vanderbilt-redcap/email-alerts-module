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

    function hook_survey_complete ($projectId,$record,$instrument,$event_id, $group_id, $survey_hash,$response_id, $repeat_instance){
        if($record != "") {
            if(!$this->isProjectStatusCompleted($projectId)) {
                $data = \REDCap::getData($projectId, "array", $record);
                $this->setEmailTriggerRequested(false);
                if (isset($projectId)) {
                    #Form Complete
                    $forms_name = $this->getProjectSetting("form-name", $projectId);
                    if (!empty($forms_name) && $record != NULL) {
                        foreach ($forms_name as $id => $form) {
                            $form_name_event_id = $this->getProjectSetting("form-name-event", $projectId)[$id];
                            $isLongitudinalData = false;
                            if (\REDCap::isLongitudinal() && !empty($form_name_event_id)) {
                                $isLongitudinalData = true;
                            }

                            if (($event_id == $form_name_event_id && $isLongitudinalData) || !$isLongitudinalData) {
                                if ($_REQUEST['page'] == "" && $_REQUEST['s'] != "") {
                                    #Surveys are always complete
                                    $isRepeatInstrument = false;
                                    if ((array_key_exists('repeat_instances', $data[$record]) && ($data[$record]['repeat_instances'][$event_id][$form][$repeat_instance][$form . '_complete'] != '' || $data[$record]['repeat_instances'][$event_id][''][$repeat_instance][$form . '_complete'] != ''))) {
                                        $isRepeatInstrument = true;
                                    }
                                    $this->sendEmailFromSurveyCode($_REQUEST['s'], $projectId, $id, $data, $record, $event_id, $instrument, $repeat_instance, $isRepeatInstrument, $form);
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    function hook_save_record ($projectId,$record,$instrument,$event_id, $group_id, $survey_hash,$response_id, $repeat_instance){
        if($record != "") {
            if(!$this->isProjectStatusCompleted($projectId)) {
                $data = \REDCap::getData($projectId, "array", $record);
                $this->setEmailTriggerRequested(false);
                if (isset($projectId)) {
                    #Form Complete
                    $forms_name = $this->getProjectSetting("form-name", $projectId);
                    if (!empty($forms_name) && $record != null) {
                        foreach ($forms_name as $id => $form) {
                            $form_name_event_id = $this->getProjectSetting("form-name-event", $projectId)[$id];
                            $isLongitudinalData = false;
                            if (\REDCap::isLongitudinal() && !empty($form_name_event_id)) {
                                $isLongitudinalData = true;
                            }

                            $isRepeatInstrumentComplete = $this->isRepeatInstrumentComplete($data, $record, $event_id, $form, $repeat_instance);
                            $isRepeatInstrument = false;
                            if (
                                array_key_exists('repeat_instances', $data[$record])
                                && (
                                    (
                                        isset($data[$record]['repeat_instances'][$event_id][$form])
                                        && $data[$record]['repeat_instances'][$event_id][$form][$repeat_instance][$form . '_complete'] != ''
                                    )
                                    || (
                                        isset($data[$record]['repeat_instances'][$event_id][""])
                                        && $data[$record]['repeat_instances'][$event_id][''][$repeat_instance][$form . '_complete'] != ''
                                    )
                                )
                            ) {
                                $isRepeatInstrument = true;
                            }
                            $incompleteAry = $this->getProjectSetting("email-incomplete", $projectId);
                            $email_incomplete = isset($incompleteAry[$id]) ? $incompleteAry[$id] : "";
                            if (
                                (
                                    $email_incomplete == "1"
                                    && (
                                        ($isRepeatInstrument && !$isRepeatInstrumentComplete)
                                        || (!$isRepeatInstrument && $data[$record][$event_id][$form . '_complete'] != '2')
                                    )
                                )
                                || (
                                    !$this->isSurveyPage()
                                    && isset($data[$record][$event_id])
                                    && ($data[$record][$event_id][$form . '_complete'] == '2'
                                        || $isRepeatInstrumentComplete)
                                )
                            ) {
                                if (($event_id == $form_name_event_id && $isLongitudinalData) || !$isLongitudinalData) {
                                    if ($_REQUEST['page'] == $form) {
                                        $this->setEmailTriggerRequested(true);
                                        $this->sendEmailAlert($projectId, $id, $data, $record, $event_id, $instrument, $repeat_instance, $isRepeatInstrument);
                                    } else if ($_REQUEST['page'] == "" && $_REQUEST['s'] != "") {
                                        $this->sendEmailFromSurveyCode($_REQUEST['s'], $projectId, $id, $data, $record, $event_id, $instrument, $repeat_instance, $isRepeatInstrumentComplete, $form);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    function sendEmailFromSurveyCode($surveyCode, $projectId, $id, $data, $record, $event_id, $instrument, $repeat_instance, $isRepeatInstrumentComplete, $form){
        $q = $this->query("SELECT s.form_name FROM redcap_surveys_participants as sp LEFT JOIN redcap_surveys s ON (sp.survey_id = s.survey_id ) where s.project_id =? AND sp.hash=?", [$projectId,$surveyCode]);

        if($row = $q->fetch_assoc()){
            if ($row['form_name'] == $form) {
                $this->setEmailTriggerRequested(true);
                $this->sendEmailAlert($projectId, $id, $data, $record,$event_id,$instrument,$repeat_instance,$isRepeatInstrumentComplete);
            }
        }
    }

    /**
     * Function that deletes information when we click on the REDCap buttons: Delete the project, Erase all data, Delete record
     * @param null $projectId
     */
    function hook_every_page_before_render($projectId = null){
        if(strpos($_SERVER['REQUEST_URI'],'delete_project.php') !== false && $_POST['action'] == 'delete') {
            #Button: Delete the project
            $this->setProjectSetting('email-queue', '');
        }else if(strpos($_SERVER['REQUEST_URI'],'erase_project_data.php') !== false && $_POST['action'] == 'erase_data'){
            #Button: Erase all data

            $this->setProjectSetting('email-repetitive-sent', '');
            $this->setProjectSetting('email-records-sent', '');
            $this->setProjectSetting('email-queue', '');
            $this->removeLogs("project_id = $projectId and message = 'email-repetitive-sent'");
            $this->removeLogs("project_id = $projectId and message = 'email-records-sent'");
            $this->removeLogs("project_id = $projectId and message = 'email-sent'");
            $this->removeLogs("project_id = $projectId and message = 'email-timestamp-sent'");
        }else if (
            isset($_REQUEST['route'])
            && isset($_REQUEST['record'])
            && ($_REQUEST['route'] == 'DataEntryController:deleteRecord')
            && ($_REQUEST['record'] != "")
        ){
            #Button: Delete record

            $record_id = urldecode($_REQUEST['record']);

            #Delete email repetitive sent and the list of records before deleting all data
            $email_repetitive_sent =  $this->getProjectSetting('email-repetitive-sent');
            $email_repetitive_sent = json_decode($email_repetitive_sent,true);
            $email_records_sent =  $this->getProjectSetting('email-records-sent');

            if($email_repetitive_sent) {
                foreach ($email_repetitive_sent as $form => $form_value) {
                    foreach ($form_value as $alert => $alert_value) {
                        foreach ($alert_value as $record => $value) {
                            #we delete the found record
                            if ($record == $record_id) {
                                unset($email_repetitive_sent[$form][$alert][$record]);
                            }
                        }
                    }
                }
                $this->setProjectSetting('email-repetitive-sent', json_encode($email_repetitive_sent));
            }
            if($email_records_sent){
                foreach ($email_records_sent as $index=>$sent){
                    $records = array_map('trim', explode(',', $sent));
                    foreach ($records as $record){
                        if($record == $record_id){
                            #Delete list of records sent
                            $aux = str_replace(", ".$record_id,"",$email_records_sent[$index], $count);
                            if($count == 0){
                                $email_records_sent[$index] = str_replace($record_id,"",$email_records_sent[$index]);
                            }else{
                                $email_records_sent[$index] = $aux;
                            }
                        }else if($record == ""){
                            #If there are empty values in the string we delete the commas
                            $email_records_sent[$index] = str_replace(", ,",",",$email_records_sent[$index], $count);

                        }
                    }
                }
                $this->setProjectSetting('email-records-sent', $email_records_sent);
            }

            $this->removeLogs("project_id = $projectId and message = 'email-repetitive-sent' and record_id='$record_id'");
            $this->removeLogs("project_id = $projectId and message = 'email-records-sent' and value='$record_id'");
            $this->removeLogs("project_id = $projectId and message = 'email-sent' and value='$record_id'");
            $this->removeLogs("project_id = $projectId and message = 'email-timestamp-sent' and value='$record_id'");

            #Delete the queued emails for that record
            $email_queue = $this->getProjectSetting('email-queue');
            $email_queue_aux = $email_queue;
            if($email_queue){
                foreach ($email_queue as $id=>$email){
                    if($email['project_id'] == $projectId && $email['record'] == $record_id){
                        unset($email_queue_aux[$id]);
                    }
                }
                $this->setProjectSetting('email-queue', $email_queue_aux);
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

    /**
     * Function that sends the email alert or schedules it in a queue to send it later
     * @param $projectId
     * @param $id
     * @param $data
     * @param $record
     * @param $event_id
     * @param $instrument
     * @param $repeat_instance
     * @param $isRepeatInstrument
     * @throws \Exception
     */
    function sendEmailAlert($projectId, $id, $data, $record,$event_id,$instrument,$repeat_instance,$isRepeatInstrument){
        #To ensure it's the last module called
        $delayedSuccessful = $this->delayModuleExecution();
        if ($delayedSuccessful) {
            return;
        }
        $email_repetitive = $this->getProjectSetting("email-repetitive",$projectId)[$id];
        $email_deactivate = $this->getProjectSetting("email-deactivate",$projectId)[$id];
        $email_deleted = $this->getProjectSetting("email-deleted",$projectId)[$id];
        $email_repetitive_sent = $this->getProjectSettingLog($projectId,"email-repetitive-sent",$isRepeatInstrument);
        $email_records_sent = $this->getProjectSettingLog($projectId,"email-records-sent");
        $email_condition = htmlspecialchars_decode($this->getProjectSetting("email-condition", $projectId)[$id]);
        if(($email_deactivate == "0" || $email_deactivate == "") && ($email_deleted == "0" || $email_deleted == "")) {
            $recordEmailsSent = isset($email_records_sent[$id]) ? $email_records_sent[$id] : [];
            $isEmailAlreadySentForThisSurvery = $this->isEmailAlreadySentForThisSurvery($projectId,$email_repetitive_sent,$recordEmailsSent,$event_id, $record, $instrument,$id,$isRepeatInstrument,$repeat_instance);
            if((($email_repetitive == "1") || ($email_repetitive == '0' && !$isEmailAlreadySentForThisSurvery))) {

                #If the condition is met or if we don't have any, we send the email
                $evaluateLogic = \REDCap::evaluateLogic($email_condition, $projectId, $record, $event_id);
                if (($isRepeatInstrument || \REDCap::isLongitudinal()) && !$evaluateLogic) {
                    $evaluateLogic = \REDCap::evaluateLogic($email_condition, $projectId, $record, $event_id, $repeat_instance, $instrument);
                }
                if ((!empty($email_condition) && \LogicTester::isValid($email_condition) && $evaluateLogic) || empty($email_condition)) {
                    $cron_repeat_for = $this->getProjectSetting("cron-repeat-for", $projectId)[$id];
                    $cron_send_email_on = $this->getProjectSetting("cron-send-email-on", $projectId)[$id];
                    $cron_send_email_on_field = htmlspecialchars_decode($this->getProjectSetting("cron-send-email-on-field", $projectId)[$id]);

                    if ($email_repetitive == '0' && (($cron_send_email_on != 'now' && $cron_send_email_on != '' && $cron_send_email_on_field != '') || ($cron_send_email_on == 'now' && $cron_repeat_for >= 1))) {
                        #SCHEDULED EMAIL
                        if (!$this->isQueueExpired($projectId, $record, $event_id, $repeat_instance, $instrument, $isRepeatInstrument, $id) && !$this->isAlreadyInQueue($id, $projectId, $record,$repeat_instance)) {
                            $this->addQueuedEmail($id, $projectId, $record, $event_id, $instrument, $repeat_instance, $isRepeatInstrument);
                        }

                    } else {
                        #REGULAR EMAIL
                        $this->createAndSendEmail($data, $projectId, $record, $id, $instrument, $repeat_instance, $isRepeatInstrument, $event_id, false,$isEmailAlreadySentForThisSurvery);
                    }
                }
            }
        }
    }

    /**
     * Function to add queued emails from the user interface
     * @param $projectId
     * @param $alert
     * @param $record
     * @param $times_sent
     */
    function addQueueEmailFromInterface($projectId, $alert, $record, $times_sent, $event_id, $last_sent,$instance){
        if($record != "") {
            $data = \REDCap::getData($projectId, "array", $record);

            $instrument = $this->getProjectSetting("form-name", $projectId)[$alert];

            $isRepeatInstrument = false;
            if ((array_key_exists('repeat_instances', $data[$record]) && ($data[$record]['repeat_instances'][$event_id][$instrument][$instance][$instrument . '_complete'] != '' || $data[$record]['repeat_instances'][$event_id][''][$repeat_instance][$instrument . '_complete'] != ''))) {
                $isRepeatInstrument = true;
            }

            if (!$this->isQueueExpired($projectId, $record, $event_id, $instance, $instrument, $isRepeatInstrument, $alert) && !$this->isAlreadyInQueue($alert, $projectId, $record, $instance)) {
                $this->addQueuedEmail($alert, $projectId, $record, $event_id, $instrument, $instance, $isRepeatInstrument, $times_sent, $last_sent);
            } else {
                return $record;
            }
        }
        return "";
    }

    /**
     * Function that checks if the repeatable option has met the condition if yes, then we don't add the email to the queue
     * @param $projectId
     * @param $record
     * @param $event_id
     * @param $instance
     * @param $instrument
     * @param $isRepeatInstrument
     * @param $id
     * @return bool
     */
    function isQueueExpired($projectId, $record, $event_id, $instance, $instrument, $isRepeatInstrument, $id){
        $cron_queue_expiration_date =  $this->getProjectSetting('cron-queue-expiration-date',$projectId)[$id];
        $cron_queue_expiration_date_field =  htmlspecialchars_decode($this->getProjectSetting('cron-queue-expiration-date-field',$projectId)[$id]);

        $today = date('Y-m-d');
        $evaluateLogic = \REDCap::evaluateLogic($cron_queue_expiration_date_field, $projectId, $record, $event_id);
        if($isRepeatInstrument){
            $evaluateLogic = \REDCap::evaluateLogic($cron_queue_expiration_date_field,  $projectId, $record, $event_id, $instance, $instrument);
        }

        if ($cron_queue_expiration_date != 'never' && $cron_queue_expiration_date != "" && $cron_queue_expiration_date_field != '') {
            if ($cron_queue_expiration_date == 'date') {
                if (strtotime($cron_queue_expiration_date_field) >= strtotime($today)) {
                    return false;
                } else {
                    return true;
                }
            } else if ($cron_queue_expiration_date == 'cond' && $cron_queue_expiration_date_field != "") {
                if ($evaluateLogic) {
                    return true;
                } else {
                    return false;
                }
            }
        } else if ($cron_queue_expiration_date == 'never') {
            return false;
        }

        return true;

    }

    function isAlreadyInQueue($alert, $projectId, $record, $instance){
        $email_queue = $this->getProjectSetting('email-queue');
        $found = false;
        foreach ($email_queue as $index=>$queue){
            if($alert == $queue['alert'] && $projectId == $queue['project_id'] && $record == $queue['record'] && $queue['instance'] == $instance){
                $found = true;
                break;
            }
        }

        return $found;
    }

    /**
     * Function called by the CRON to send the scheduled email alerts
     * @throws \Exception
     */
    function scheduledemails(){
        $q = $this->query("SELECT s.project_id FROM redcap_external_modules m, redcap_external_module_settings s WHERE m.external_module_id = s.external_module_id AND s.value = ? AND (m.directory_prefix = ? OR m.directory_prefix = ?) AND s.`key` = ?", ['true','vanderbilt_emailTrigger','email_alerts','enabled']);

        while($row = $q->fetch_assoc()){
            $projectId = (int)$row['project_id'];
            if($projectId != "") {
                $this->deleteOldLogs($projectId);
                if(!$this->isProjectStatusCompleted($projectId)) {
                    $this->log("scheduledemails PID: " . $projectId . " - start", ['scheduledemails' => 1]);
                    $email_queue = $this->getProjectSetting('email-queue', $projectId);
                    if ($email_queue != '') {
                        $email_sent_total = 0;
                        foreach ($email_queue as $index => $queue) {
                            if ($queue['record'] != '' && $email_sent_total < 100 && !$this->hasQueueExpired($queue, $index, $projectId) && $queue['deactivated'] != 1) {
                                if ($this->getProjectSetting('email-deactivate', $projectId)[$queue['alert']] != "1" && $this->sendToday($queue, $projectId)) {
                                    $this->log("scheduledemails PID: " . $projectId . " - Has queued emails to send today " . date("Y-m-d H:i:s"), ['scheduledemails' => 1]);
                                    #SEND EMAIL
                                    $email_sent = $this->sendQueuedEmail($index, $projectId, $queue['record'], $queue['alert'], $queue['instrument'], $queue['instance'], $queue['isRepeatInstrument'], $queue['event_id']);
                                    #If email sent save date and number of times sent and delete queue if needed
                                    if ($email_sent || $email_sent == "1") {
                                        $email_sent_total++;
                                    }
                                    #Check if we need to delete the queue
                                    $this->stopRepeat($queue, $index, $projectId);
                                }
                            } else if ($email_sent_total >= 100) {
                                $this->log("scheduledemails PID: " . $projectId . " - Batch ended at " . date("Y-m-d H:i:s"), ['scheduledemails' => 1]);
                                break;
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Function that checks if the email alert has to be sent today or not
     * @param $queue, the email alert queue info
     * @param $index, the queue index
     * @return bool
     */
    function sendToday($queue,$projectId)
    {
        $cron_send_email_on_field = htmlspecialchars_decode($this->getProjectSetting('cron-send-email-on-field',$projectId)[$queue['alert']]);
        $cron_repeat_for = $this->getProjectSetting('cron-repeat-for',$projectId)[$queue['alert']];

        $repeat_days = $cron_repeat_for;
        if($queue['times_sent'] != 0){
            $repeat_days = $cron_repeat_for * $queue['times_sent'];
        }

        $today = date('Y-m-d');
        $extra_days = ' + ' . $repeat_days . " days";
        $repeat_date = date('Y-m-d', strtotime($cron_send_email_on_field . $extra_days));
        $repeat_date_now = date('Y-m-d', strtotime($queue['last_sent'] . '+'.$cron_repeat_for.' days'));

        $evaluateLogic_on = \REDCap::evaluateLogic($cron_send_email_on_field, $projectId, $queue['record'], $queue['event_id']);
        if($queue['isRepeatInstrument']){
            $evaluateLogic_on = \REDCap::evaluateLogic($cron_send_email_on_field,  $projectId, $queue['record'], $queue['event_id'], $queue['instance'], $queue['instrument']);
        }

		if($this->getProjectSetting('email-deactivate', $projectId)[$queue['alert']] != "1" && (strtotime($queue['last_sent']) != strtotime($today) || $queue['last_sent'] == "")){
            if (($queue['option'] == 'date' && ($cron_send_email_on_field == $today || $repeat_date == $today || ($queue['last_sent'] == "" && strtotime($cron_send_email_on_field) <= strtotime($today)))) || ($queue['option'] == 'calc' && $evaluateLogic_on && ($repeat_date_now == $today || $queue['last_sent'] == '')) || ($queue['option'] == 'now' && ($repeat_date_now == $today || $queue['last_sent'] == ''))) {
                return true;
            }
        }
        return false;
    }

    /**
     * Function that checks if it has to stop sending the email alerts and delete them from the queue
     * @param $delete_queue, array to fill up with the queue indexes to delete
     * @param $queue, current queue
     * @param $index, queue index
     * @return mixed
     */
    function stopRepeat($queue,$index,$projectId){
        $cron_repeat_for = $this->getProjectSetting('cron-repeat-for',$queue['project_id'])[$queue['alert']];
        if($cron_repeat_for == "" || $cron_repeat_for == "0" && $queue['last_sent'] != ""){
            $this->deleteQueuedEmail($index, $projectId);
            $this->log("scheduledemails PID: " . $queue['project_id'] . " - Alert # ".$queue['alert']." Queue #".$index." stop repeat. Delete.",['scheduledemails' => 1]);
        }
    }

    /**
     * Function that if the queue never expires, it checks the other options and delete the expired queues
     * @param $queue
     * @param $index
     * @param $projectId
     * @return bool
     */
    function hasQueueExpired($queue,$index,$projectId){
        $cron_queue_expiration_date =  $this->getProjectSetting('cron-queue-expiration-date',$projectId)[$queue['alert']];
        $cron_queue_expiration_date_field =  htmlspecialchars_decode($this->getProjectSetting('cron-queue-expiration-date-field',$projectId)[$queue['alert']]);
        $cron_repeat_for = $this->getProjectSetting('cron-repeat-for',$projectId)[$queue['alert']];

        #If the repeat is 0 we delete regardless of the expiration option
        if(($cron_repeat_for == "" || $cron_repeat_for == "0") && $queue['last_sent'] != ""){
            $this->log("scheduledemails PID: " . $projectId . " - Alert # ".$queue['alert']." Queue #".$index." expired. Delete.",['scheduledemails' => 1]);
            $this->deleteQueuedEmail($index, $projectId);
            return true;
        }

        if($cron_queue_expiration_date_field != "" && $cron_queue_expiration_date != '' && $cron_queue_expiration_date != 'never') {
            $evaluateLogic = \REDCap::evaluateLogic($cron_queue_expiration_date_field, $projectId, $queue['record'], $queue['event_id']);
            if ($queue['isRepeatInstrument']) {
                $evaluateLogic = \REDCap::evaluateLogic($cron_queue_expiration_date_field, $projectId, $queue['record'], $queue['event_id'], $queue['instance'], $queue['instrument']);
            }

            if ($cron_queue_expiration_date == 'date' && $cron_queue_expiration_date_field != "") {
                if (strtotime($cron_queue_expiration_date_field) <= strtotime(date('Y-m-d'))) {
                    $this->log("scheduledemails PID: " . $projectId . " - Alert # ".$queue['alert']." Queue #".$index." expired date. Delete.",['scheduledemails' => 1]);
                    $this->deleteQueuedEmail($index, $projectId);
                    return true;
                }
            }else if ($cron_queue_expiration_date == 'cond' && $cron_queue_expiration_date_field != "") {
                if ($evaluateLogic) {
                    $this->log("scheduledemails PID: " . $projectId . " - Alert # ".$queue['alert']." Queue #".$index." expired condition. Delete.",['scheduledemails' => 1]);
                    $this->deleteQueuedEmail($index, $projectId);
                    return true;
                }
            }
        }else if($cron_queue_expiration_date == 'never'){
            return false;
        }
        return false;
    }

    /**
     * Function that adds an email alert to the queue
     * @param $alert, alert number
     * @param $projectId
     * @param $record
     * @param $event_id
     * @param $instrument
     * @param $instance
     * @param $isRepeatInstrument
     */
    function addQueuedEmail($alert, $projectId, $record, $event_id, $instrument, $instance, $isRepeatInstrument,$times_sent=0,$last_sent=''){
        $queue = array();
        $queue['alert'] = $alert;
        $queue['record'] = $record;
        $queue['project_id'] = $projectId;
        $queue['event_id'] = $event_id;
        $queue['instrument'] = $instrument;
        $queue['instance'] = $instance;
        $queue['isRepeatInstrument'] = $isRepeatInstrument;
        $queue['creation_date'] = date('Y-m-d');

        $cron_send_email_on = $this->getProjectSetting("cron-send-email-on", $projectId)[$alert];
        $queue['option'] = $cron_send_email_on;
        $queue['deactivated'] = 0;
        $queue['times_sent'] = $times_sent;
        $queue['last_sent'] = $last_sent;

        $email_queue = empty($this->getProjectSetting('email-queue', $projectId))?array():$this->getProjectSetting('email-queue', $projectId);
        array_push($email_queue,$queue);
        $this->setProjectSetting('email-queue', $email_queue, $projectId);
    }

    /**
     * Function that deletes a specific queue
     * @param $index
     * @param $projectId
     */
    function deleteQueuedEmail($index, $projectId){
        $email_queue =  empty($this->getProjectSetting('email-queue',$projectId))?array():$this->getProjectSetting('email-queue',$projectId);
        if(is_array($index)){
            foreach ($index as $queue_index){
                unset($email_queue[$queue_index]);
            }
        }else if(is_numeric($index)){
            unset($email_queue[$index]);
        }

        $this->setProjectSetting('email-queue', $email_queue,$projectId);
    }

    /**
     * Function that sends a specific scheduled email from the queue
     * @param $projectId
     * @param $record
     * @param $id
     * @param $instrument
     * @param $instance
     * @param $isRepeatInstrument
     * @param $event_id
     * @return bool
     */
    function sendQueuedEmail($index,$projectId, $record, $id, $instrument, $instance, $isRepeatInstrument, $event_id){
        $data = \REDCap::getData($projectId,"array",$record);
        $email_repetitive_sent = $this->getProjectSettingLog($projectId,"email-repetitive-sent",$isRepeatInstrument);
        $email_records_sent = $this->getProjectSettingLog($projectId,"email-records-sent");
        $isEmailAlreadySentForThisSurvery = $this->isEmailAlreadySentForThisSurvery($projectId,$email_repetitive_sent,$email_records_sent[$id],$event_id, $record, $instrument,$id,$isRepeatInstrument,$instance);
        $email_sent = $this->createAndSendEmail($data, $projectId, $record, $id, $instrument, $instance, $isRepeatInstrument, $event_id,true,$isEmailAlreadySentForThisSurvery);

        if ($email_sent || $email_sent == "1") {
            $email_queue = $this->getProjectSetting('email-queue', $projectId);

            $email_queue[$index]['last_sent'] = date('Y-m-d');
            $email_queue[$index]['times_sent'] = $email_queue[$index]['times_sent'] + 1;

            $this->setProjectSetting('email-queue', $email_queue, $projectId);
        }

        return $email_sent;
    }

    /**
     * Function that adds schduled info in the log
     * @param $pid
     * @param $action_description
     * @param $cron_send_email_on
     * @param $cron_send_email_on_field
     * @param $cron_repeat_email
     * @param $cron_repeat_for
     * @param $cron_repeat_until
     * @param $cron_repeat_until_field
     */
    function addQueueLog($pid,$action_description,$cron_send_email_on,$cron_send_email_on_field,$cron_repeat_for,$cron_queue_expiration_date,$cron_queue_expiration_date_field){
        #Add logs
        $scheduled_email = "";
        if($cron_send_email_on == "now"){
            $scheduled_email = "Send ".$cron_send_email_on."";
        }else if($cron_send_email_on == "date"){
            $scheduled_email = "Send on ".$cron_send_email_on;
        }else if($cron_send_email_on == "calc"){
            $scheduled_email = "Send on condition";
        }
        if($cron_send_email_on_field != ""){
            $scheduled_email .= ": ".$cron_send_email_on_field."";
        }
        $never = false;
        if ($cron_repeat_for != "" && $cron_repeat_for != "0") {
            if($cron_repeat_for == 1){
                $scheduled_email .= "<br><br>Repeat every day";
            }else{
                $scheduled_email .= "<br><br>Repeat every " . $cron_repeat_for . " days";
            }
        }
        if ($cron_queue_expiration_date != "" && $cron_queue_expiration_date != null) {
            $scheduled_email .= " ";
            if ($cron_queue_expiration_date == "cond") {
                $scheduled_email .= "<br><br>Expires on condition: ";
            }else if ($cron_queue_expiration_date == "date") {
                $scheduled_email .= "<br><br> Expires on: ";
            } else {
                $scheduled_email .= "<br><br><b>Never</b> Expires";
                $never = true;
            }
        }
        if ($cron_queue_expiration_date_field != "" && !$never) {
            $scheduled_email .= $cron_queue_expiration_date_field . "";
        }

        $changes_made = $scheduled_email;
        \REDCap::logEvent($action_description,$changes_made,null,null,null,$pid);
    }

    function getProjectSettingLog($projectId,$settingName,$isRepeatInstrument=""){
        $data = $this->getProjectSetting($settingName, $projectId);
        if ($data === NULL) {
            $data = [];
        }
        if($settingName == "email-repetitive-sent"){
            $logs = $this->queryLogs("select instrument, alert, record_id, event, instance where project_id = $projectId and message = '$settingName'");
            $data = $data ? json_decode($data,true) : [];
            foreach($logs as $log){
                $instrument = $log['instrument'];
                $alert = $log['alert'];
                $record = $log['record_id'];
                $event = $log['event'];
                $instance = $log['instance'];
                $data = $this->addRecordSent($data, $record, $instrument, $alert, $isRepeatInstrument, $instance, $event);
            }
        }else {
            $logs = $this->queryLogs("select value,id where project_id = $projectId and message = '$settingName'");
            if ($logs === NULL) {
                $logs = [];
            }
            if($settingName == "email-records-sent"){
                if(!empty($data)){
                    $aux = $data;
                    foreach($logs as $log){
                        $log_found = false;
                        foreach ($data as $id=>$value){
                            if($id == $log['id'] && strpos($aux[$log['id']],$log['value']) === false){
                                $aux[$id] = $aux[$id].", ".$log['value'];
                                $log_found = true;
                            }
                        }
                        if(!$log_found) {
                            if($aux[$log['id']] == ""){
                                $aux[$log['id']] = $aux[$log['id']].$log['value'];
                            }else{
                                $aux[$log['id']] = $aux[$log['id']].", ".$log['value'];
                            }
                        }
                    }
                    $data = $aux;
                }else{
                    foreach($logs as $log){
                        if(
                            isset($data[$log['id']])
                            && strpos($data[$log['id']],$log['value']) === false
                        ){
                            $data[$log['id']] = $data[$log['id']].$log['value'] . ", ";
                        }
                    }
                    foreach($data as $id=>$dat){
                        $data[$id] = rtrim($dat,", ");
                    }
                }
            }else if($settingName == "email-timestamp-sent"){
                foreach($logs as $log) {
                    if(
                        !isset($data[$log['id']])
                        || $data[$log['id']] < $log['value']
                        || $data[$log['id']] == ''
                    ){
                        $data[$log['id']] = $log['value'];
                    }
                }
            }else{
                foreach($logs as $log) {
                    $data[$log['id']] = $log['value'];
                }
            }
        }

        return $data;
    }

    /**
     * Function that creates and sends an email
     * @param $data
     * @param $projectId
     * @param $record
     * @param $id
     * @param $instrument
     * @param $instance
     * @param $isRepeatInstrument
     * @param $event_id
     * @param $isCron
     * @return bool
     */
    function createAndSendEmail($data, $projectId, $record, $id, $instrument, $instance, $isRepeatInstrument, $event_id,$isCron,$isEmailAlreadySentForThisSurvery=false){
        //memory increase
        ini_set('memory_limit', '4096M');

        $email_subject = htmlspecialchars_decode($this->getProjectSetting("email-subject", $projectId)[$id]);
        $email_text = $this->getProjectSetting("email-text", $projectId)[$id];
        $datapipe_var = $this->getProjectSetting("datapipe_var", $projectId);
        $alert_id = $this->getProjectSetting("alert-id", $projectId);

        $isLongitudinal = false;
        if($isCron){
            $q = $this->query("SELECT count(e.event_id) as number_events FROM redcap_projects p, redcap_events_metadata e, redcap_events_arms a WHERE p.project_id=? AND p.repeatforms=? AND a.arm_id = e.arm_id AND p.project_id=a.project_id", [$projectId,'1']);
            if($row = $q->fetch_assoc()) {
                if($row['number_events'] >= "2") {
                    $isLongitudinal = true;
                }
            }else{
                $isLongitudinal = false;
            }
        }else{
            $isLongitudinal = \REDCap::isLongitudinal();
        }
        #Data piping
        $email_text = $this->setDataPiping($datapipe_var, $email_text, $projectId, $data, $record, $event_id, $instrument, $instance, $isLongitudinal);
        $email_subject = $this->setDataPiping($datapipe_var, $email_subject, $projectId, $data, $record, $event_id, $instrument, $instance, $isLongitudinal);

        #Survey and Data-Form Links
        $email_text = $this->setREDCapSurveyLink($email_text, $projectId, $record, $event_id, $isLongitudinal);
        $email_text = $this->setPassthroughSurveyLink($email_text, $projectId, $record, $event_id, $isLongitudinal);
        $email_text = $this->setFormLink($email_text, $projectId, $record, $event_id, $isLongitudinal);

        #Email Data structure
        $array_emails = [];

        #Email Addresses
        $array_emails = $this->setEmailAddresses($array_emails, $projectId, $record, $event_id, $instrument, $instance, $data, $id, $isLongitudinal);

        #Email From
        $array_emails = $this->setFrom($array_emails, $projectId, $record, $id);

        #Embedded images
        $array_emails = $this->setEmbeddedImages($array_emails, $projectId, $email_text);

        #Attachments
        $array_emails = $this->setAttachments($array_emails, $projectId, $id);

        #Attchment from RedCap variable
        $array_emails = $this->setAttachmentsREDCapVar($array_emails, $projectId, $data, $record, $event_id, $instrument, $instance, $id, $isLongitudinal);

        if($alert_id != ""){
            $alert_number = $id;
        }else{
            $alert_number = $alert_id[$id];
        }

        $email_sent_ok = false;

        #We use the message class so the emails get recorded in the Email Logging section in REDCap
        $email = new \Message($projectId, $record, $event_id, $instrument, $instance);
        $email->setTo($array_emails['to']);
        if ($array_emails['cc'] != '') $email->setCc($array_emails['cc']);
        if ($array_emails['bcc'] != '') $email->setBcc($array_emails['bcc']);
        $email->setFrom($array_emails['from']);
        $email->setFromName($array_emails['fromName']);
        $email->setSubject($email_subject);
        $email->setBody($email_text);
        if (isset($array_emails['attachments']) && is_array($array_emails['attachments']) && !empty($array_emails['attachments'])) {
            foreach ($array_emails['attachments'] as $name=>$fullPath) {
                $email->setAttachment($fullPath, $name);
            }
        }
        $send = $email->send();

        if (!$send) {
            $this->log("scheduledemails PID: ".$projectId."Mailer Error: The email could not be sent",['scheduledemails' => 1]);
            $this->sendFailedEmailRecipient($this->getProjectSetting("emailFailed_var", $projectId),"Mailer Error" ,"Mailer Error: The email could not be sent in project ".$projectId." record #".$record.
                "<br><br>To: ".$array_emails['to'] ."<br>CC: ".$array_emails['cc'] ."<br>From (".$array_emails['fromName']."): ".$array_emails['from']."<br>Subject: ".$email_subject.
                "<br>Message: <br>".$email_text);

        }else{
            try {
                $this->log("scheduledemails PID: " . $projectId . " - Email was sent!",['scheduledemails' => 1]);
                $this->log("scheduledemails PID: " . $projectId . " - Alert #".$alert_number.", Record ".$record.", Event ".$event_id,['scheduledemails' => 1]);

                $email_records_sent = $this->getProjectSettingLog($projectId,"email-records-sent");
                $email_sent_ok = true;

                $this->log('email-timestamp-sent', [
                    'project_id' => $projectId,
                    'id' => $id,
                    'value' => date('Y-m-d H:i:s')
                ]);

                $this->log('email-sent', [
                    'project_id' => $projectId,
                    'id' => $id,
                    'value' => "1"
                ]);


                if (!$isEmailAlreadySentForThisSurvery) {
                    $this->log('email-repetitive-sent', [
                        'project_id' => $projectId,
                        'instrument' => $instrument,
                        'alert' => $id,
                        'record_id' => $record,
                        'event' => $event_id,
                        'instance' => $instance
                    ]);
                }

                $records = isset($email_records_sent[$id]) ? array_map('trim', explode(',', $email_records_sent[$id])) : [];
                $record_found = false;
                foreach ($records as $record_id) {
                    if ($record_id == $record) {
                        $record_found = true;
                        break;
                    }
                }

                if (!$record_found) {
                    $this->log('email-records-sent', [
                        'project_id' => $projectId,
                        'id' => $id,
                        'value' => $record
                    ]);
                }

                #Add some logs
                $action_description = "Email Sent - Alert " . $alert_number;
                $changes_made = "[Subject]: " . $email_subject . ", [Message]: " . $email_text;
                \REDCap::logEvent($action_description, $changes_made, null, $record, $event_id, $projectId);

                $action_description = "Email Sent To - Alert " . $alert_number;
                $email_list = $array_emails['to'];
                if($array_emails['cc'] != ""){
                    $email_list .= ";".$array_emails['cc'];
                }
                if($array_emails['bcc'] != ""){
                    $email_list .= ";".$array_emails['bcc'];
                }
                \REDCap::logEvent($action_description, $email_list, null, $record, $event_id, $projectId);

            }catch(Exception $e){

            }
        }
        return $email_sent_ok;
    }

    /**
     * Function that adds the email addresses into the mail.
     * @param $mail
     * @param $projectId
     * @param $record
     * @param $event_id
     * @param $instrument
     * @param $instance
     * @param $data
     * @param $id
     * @param bool $isLongitudinal
     * @return mixed
     */
    function setEmailAddresses($array_emails, $projectId, $record, $event_id, $instrument, $instance, $data, $id, $isLongitudinal=false){
        $datapipeEmail_var = $this->getProjectSetting("datapipeEmail_var", $projectId);
        $email_to = $this->getProjectSetting("email-to", $projectId)[$id];
        $email_cc = $this->getProjectSetting("email-cc", $projectId)[$id];
        $email_bcc = $this->getProjectSetting("email-bcc", $projectId)[$id];

        if (!empty($datapipeEmail_var)) {
            $email_form_var = explode("\n", $datapipeEmail_var);

            $emailsTo = ($email_to !== "") ? preg_split("/[;,]+/", $email_to) : [];
            $emailsCC = ($email_cc !== "") ? preg_split("/[;,]+/", $email_cc) : [];
            $emailsBCC = ($email_bcc !== "") ? preg_split("/[;,]+/", $email_bcc) : [];

            $array_emails = $this->fill_emails($array_emails,$emailsTo, $email_form_var, $data, 'to',$projectId,$record, $event_id, $instrument, $instance, $isLongitudinal);
            $array_emails = $this->fill_emails($array_emails,$emailsCC, $email_form_var, $data, 'cc',$projectId,$record, $event_id, $instrument, $instance, $isLongitudinal);
            $array_emails = $this->fill_emails($array_emails,$emailsBCC, $email_form_var, $data, 'bcc',$projectId,$record, $event_id, $instrument, $instance, $isLongitudinal);
        }else{

            $array_emails['to'] = $email_to;
            $array_emails['cc'] = $email_cc;
            $array_emails['bcc'] = $email_bcc;
        }
        return $array_emails;
    }

    /**
     * Function that adds the from email into the mail
     * @param $mail
     * @param $projectId
     * @param $record
     * @param $id
     * @return mixed
     */
    function setFrom($array_emails, $projectId, $record, $id){
    	global $from_email;
		// Using the Universal From Email Address?
		$usingUniversalFrom = ($from_email != '');
		// Get the defined FROM address
        $email_from = $this->getProjectSetting("email-from", $projectId)[$id];
        if(!empty($email_from)){
            $from_data = preg_split("/[;,]+/", $email_from);
            if(filter_var(trim($from_data[0]), FILTER_VALIDATE_EMAIL)) {
				// Set the From email for this message
				$this_from_email = (!$usingUniversalFrom ? $from_data[0] : $from_email);
				// From, Reply-To, and Return-Path. Also, set Display Name if possible.
				if (count($from_data) > 1 && ($from_data[1] == '""' || empty($from_data[1]))) {
					// If no Display Name, then use the Sender address as the Display Name if using Universal FROM address
					$fromDisplayName = $usingUniversalFrom ? $from_data[0] : "";
					$replyToDisplayName = '';
				} else if (count($from_data) > 1) {
					// Clean the defined display name
					$from_data[1] = str_replace('"', '', trim($from_data[1]));
					// If has a Display Name, then use the Sender address+real Display Name if using Universal FROM address
					$fromDisplayName = $usingUniversalFrom ? $from_data[1]." <".$from_data[0].">" : $from_data[1];
					$replyToDisplayName = $from_data[1];
				} else {
                    $fromDisplayName = "";
                    $replyToDisplayName = "";
                }
                $array_emails['from'] = $this_from_email;
                $array_emails['fromName'] = $fromDisplayName;
            }else{
                $this->sendFailedEmailRecipient($this->getProjectSetting("emailFailed_var", $projectId),"Wrong recipient" ,"The email ".$from_data[0]." in Project: ".$projectId.", Record: ".$record." Alert #".$id.", does not exist");
            }
        }else{
            $this->sendFailedEmailRecipient($this->getProjectSetting("emailFailed_var", $projectId),"Sender is empty" ,"The sender in Project: ".$projectId.", Record: ".$record." Alert #".$id.", is empty.");
        }
        return $array_emails;
    }

    /**
     * function that checks for the piped data, replaces it and returns the content
     * @param $datapipe_var
     * @param $email_content
     * @param $projectId
     * @param $data
     * @param $record
     * @param $event_id
     * @param $instrument
     * @param $instance
     * @param $isLongitudinal
     * @return mixed
     */
    function setDataPiping($datapipe_var, $email_content, $projectId, $data, $record, $event_id, $instrument, $instance, $isLongitudinal){
        if (!empty($datapipe_var)) {
            $datapipe = explode("\n", $datapipe_var);
            foreach ($datapipe as $emailvar) {
                $var = preg_split("/[;,]+/", $emailvar)[0];
                if (\LogicTester::isValid($var)) {
                    $var_replace = $var;
                    $var = \Piping::pipeSpecialTags($var, $projectId, $record, $event_id, $instance, null, false, null, $instrument, false, false, false, false, false, true);
                    preg_match_all("/\\[(.*?)\\]/", $var, $matches);

                    //For arms and different events
                    if(count($matches[1]) > 1){
                        $project = new \Project($projectId);
                        $event_id_repeating = $project->getEventIdUsingUniqueEventName($matches[1][0]);
                        $var = "[".$matches[1][1]."]";
                        if($event_id_repeating == "") {
                            #Repeating instances
                            $var = "[".$matches[1][0]."]";
                            $instance = $matches[1][1];
                        }else{
                            $event_id = $event_id_repeating;
                            if (count($matches[1]) == 3) {
                                $instance = $matches[1][2];
                            }
                        }
                    }

                    if (!preg_match("/\[.+\]/", $var)) {
                        # smart variable
                        $logic = $var;
                    } else {
                        //Repeatable instruments
                        $logic = $this->isRepeatingInstrument($projectId, $data, $record, $event_id, $instrument, $instance, $var,0, $isLongitudinal);
                        if (is_array($logic)) {
                            $correctLabels = [];
                            foreach ($logic as $index => $value) {
                                if ($value) {
                                    array_push($correctLabels, $this->getChoiceLabel(array('field_name'=>$var, 'value'=>$index, 'project_id'=>$projectId, 'record_id'=>$record,'event_id'=>$event_id,'survey_form'=>$instrument,'instance'=>$instance)));
                                }
                            }
                            $logic = implode(", ", $correctLabels);
                        } else {
                            $label = $this->getChoiceLabel(array('field_name'=>$var, 'value'=>$logic, 'project_id'=>$projectId, 'record_id'=>$record,'event_id'=>$event_id,'survey_form'=>$instrument,'instance'=>$instance));
                            if(!empty($label)){
                                $logic = $label;
                            }
                        }
                    }

                    $email_content = str_replace($var_replace, $logic, $email_content);
                }
            }
        }
        return $email_content;
    }

    /**
     * looks at list of variables and returns only the lines with smart variables
     * @param $dataform - list of data
     * @return array
     */
    static function getSmartVariablesFromVariableList($dataform) {
        $newList = [];
        foreach ($dataform as $formlink) {
            $formlink = trim($formlink);
            if ($formlink) {
                $var = preg_split("/[;,]+/", $formlink)[0];
                foreach (self::SMART_VARIABLES as $smartVariable) {
                    if (strpos($var, $smartVariable) !== FALSE) {
                        $newList[] = $formlink;
                        break;
                    }
                }
            }
        }
        return $newList;
    }

    /**
     * Function that adds the data form link into the mail content
     * @param $email_text
     * @param $projectId
     * @param $record
     * @param $event_id
     * @param $isLongitudinal
     * @return mixed
     */
    function setFormLink($email_text, $projectId, $record, $event_id, $isLongitudinal){
        $formLink_var = $this->getProjectSetting("formLink_var", $projectId);
        if(!empty($formLink_var)) {
            $allDataforms = explode("\n", $formLink_var);
            $smartDataForms = self::getSmartVariablesFromVariableList($allDataforms);
            $normalDataForms = array_diff($allDataforms, $smartDataForms);
            foreach ([$smartDataForms, $normalDataForms] AS $dataform) {
                foreach ($dataform as $formlink) {
                    $var = preg_split("/[;,]+/", $formlink)[0];

                    if($isLongitudinal) {
                        preg_match_all("/\[[^\]]*\]/", $var, $matches);
                        $var = "";
                        if (sizeof($matches[0]) > 2) {
                            $var = $matches[0][1];
                        }
                        if (sizeof($matches[0]) == 2) {
                            $smarts = self::SMART_VARIABLES;
                            foreach ($smarts as $i => $var) {
                                $smarts[$i] = "[".$var."]";
                            }
                            if (in_array($matches[0][1], $smarts)) {
                                $var = $matches[0][0];
                            } else {
                                $var = $matches[0][1];
                            }
                        }
                        if (sizeof($matches[0]) == 1) {
                            $var = $matches[0][0];
                        }
                    }

                    if (strpos($email_text, $var) !== false) {
                        list($form_event_id, $instrument_form, $instance) = $this->getEventIdInstrumentAndInstance($var, "__FORMLINK_", $projectId, $record, $event_id, $isLongitudinal);
                        # get rid of extra /'s
                        $dir = preg_replace("/\/$/", "", APP_PATH_WEBROOT_FULL.preg_replace("/^\//", "", APP_PATH_WEBROOT));
                        if ( preg_match("/redcap_v[\d\.]+/", APP_PATH_WEBROOT, $matches)) {
                            $dir = APP_PATH_WEBROOT_FULL.$matches[0];
                        }
                        $url = $dir . "/DataEntry/index.php?pid=".$projectId."&event_id=".$form_event_id."&page=".$instrument_form."&id=".$record."&instance=".$instance;
                        $link = "<a href='" . $url . "' target='_blank'>" . $url . "</a>";
                        $email_text = str_replace( preg_split("/[;,]+/", $formlink)[0], $link, $email_text);
                    }
                }
            }
        }
        return $email_text;
    }

    private function getEventIdInstrumentAndInstance($var, $codeToReplace, $projectId, $record, $event_id, $isLongitudinal) {
        $redcapLogic = str_replace($codeToReplace, '', $var);
        $redcapLogic = \Piping::pipeSpecialTags($redcapLogic, $projectId, $record, $event_id);
        preg_match_all("/\\[(.*?)\\]/", $redcapLogic, $matches);
        if (count($matches[1]) >= 1) {
            if ((count($matches[1]) == 2) && in_array($matches[1][1], self::SMART_VARIABLES)) {
                $formEventId = $event_id;
                $instrumentForm = $matches[1][0];
                $instance = $this->getNumericalInstanceForForm(
                    $projectId,
                    $record,
                    $event_id,
                    $instrumentForm,
                    $matches[1][1],
                    $isLongitudinal
                );
            } else if ((count($matches[1]) == 2) && $isLongitudinal) {
                $project = new \Project($projectId);
                $formEventId = $project->getEventIdUsingUniqueEventName($matches[1][0]);
                $instrumentForm = $matches[1][1];
                $instance = 1;
            } else if ((count($matches[1]) == 2) && is_numeric($matches[1][1])) {
                $formEventId = $event_id;
                $instrumentForm = $matches[1][0];
                $instance = $matches[1][1];
            } else if (count($matches[1]) == 2) {
                # all others - classical with non-numerical second term - should not happen
                throw new \Exception("Improper term! $redcapLogic");
            } else if (count($matches[1]) == 3) {
                $project = new \Project($projectId);
                $formEventId = $project->getEventIdUsingUniqueEventName($matches[1][0]);
                $instrumentForm = $matches[1][1];
                if (is_numeric($matches[1][2])) {
                    $instance = $matches[1][2];
                } else {
                    $instance = $this->getNumericalInstanceForForm(
                        $projectId,
                        $record,
                        $event_id,
                        $instrumentForm,
                        $matches[1][2],
                        $isLongitudinal
                    );
                }
            } else {
                $instrumentForm = $matches[1][0];
                $formEventId = $event_id;
                $instance = 1;
            }
        } else {
            $formEventId = $event_id;
            $instrumentForm = str_replace(['[', ']'], "", $redcapLogic);
            $instance = 1;
        }
        return [$formEventId, $instrumentForm, $instance];
    }

    /**
     * Function that tells if an instrument is repeating. If the event is repeating (not the instrument),
     * it returns FALSE.
     * @param $event_id
     * @param $instrument
     * @param $smartVariable (one of SMART_VARIABLES)
     * @return bool
     */
    static function isRepeatingInstrumentInEvent($event_id, $instrument) {
        $q = ExternalModules::query("SELECT COUNT(form_name) AS cnt FROM redcap_events_repeat WHERE event_id=? AND form_name=?", [$event_id,$instrument]);
        if($row = $q->fetch_assoc()) {
            return ($row['cnt'] > 0);
        }
        return FALSE;
    }

    /**
     * Function that transforms a smart variable into a numerical instance
     * @param $projectId
     * @param $record
     * @param $form_event_id
     * @param $instrument_form
     * @param $smartVariable (one of SMART_VARIABLES)
     * @return int
     */
     function getNumericalInstanceForForm($projectId, $record, $form_event_id, $instrument_form, $smartVariable, $isLongitudinal) {
         if (is_integer($smartVariable) || ctype_digit($smartVariable)) {
             return $smartVariable;
         }
         $smartVariable = preg_replace("/\]$/", "", preg_replace("/^\[/", "", $smartVariable));

         $instanceMin = 1;
         if ($isLongitudinal && !self::isRepeatingInstrumentInEvent($form_event_id, $instrument_form)) {
             # get max instance for event
             $q = $this->query("SELECT DISTINCT(instance) AS instance FROM redcap_data WHERE project_id = ? AND event_id = ? AND record = ? ORDER BY instance DESC", [$projectId,$form_event_id,$record]);
         } else {
             # get max instance for instrument
             $q = $this->query("SELECT DISTINCT(d.instance) AS instance FROM redcap_data AS d INNER JOIN redcap_metadata AS m ON ((d.project_id = m.project_id) AND (d.field_name = m.field_name)) WHERE m.form_name = ? AND d.project_id = ? AND d.event_id = ? AND d.record = ? ORDER BY d.instance DESC", [$instrument_form,$projectId,$form_event_id,$record]);
         }
         $instanceMax = 1;
         $instanceNew = 1;
         if ($row = $q->fetch_assoc()) {
             $instanceMax = $row['instance'] ?: 1;
             $instanceNew = $instanceMax + 1;
         }

         if ($smartVariable == 'new-instance') {
             return $instanceNew;
         }
         else if ($smartVariable == 'last-instance') {
             return $instanceMax;
         }
         else if ($smartVariable == 'first-instance') {
             return $instanceMin;
         } else {
             throw new \Exception("Invalid smart variable $smartVariable");
         }
     }

    /**
     * Function that transforms the results of preg_match_all into a more parseable format
     * @param $matches
     */
    private static function transformMatches(&$matches) {
        $newMatches = [];
        foreach ($matches as $i => $itemMatches) {
            foreach ($itemMatches as $j => $match) {
                if (!isset($newMatches[$j])) {
                    $newMatches[$j] = [];
                }
                $newMatches[$j][$i] = $match;
            }
        }
        $matches = $newMatches;
    }
    /**
     * Function that adds the survey link into the mail content
     * @param $email_text
     * @param $projectId
     * @param $record
     * @param $event_id
     * @param $isLongitudinal
     * @return mixed
     */
    private function setREDCapSurveyLink($email_text, $projectId, $record, $event_id, $isLongitudinal){
        if (preg_match_all("/\[survey-link:(\w+):\s*([^\]]+)\]\[([^\]]+)\]/", $email_text, $matches)) {
            self::transformMatches($matches);
            foreach (array_values($matches) as $match) {
                $fullTextMatch = $match[0];
                $instrument = $match[1];
                $textForLink = $match[2];
                $smartVariable = $match[3];
                $instance = $this->getNumericalInstanceForForm(
                    $projectId,
                    $record,
                    $event_id,
                    $instrument,
                    $smartVariable,
                    $isLongitudinal
                );
                if ($instance) {
                    $url = \REDCap::getSurveyLink($record, $instrument, $event_id, $instance, $projectId);
                    $text = "<a href='$url'>$textForLink</a>";
                    $email_text = str_replace($fullTextMatch, $text, $email_text);
                }
            }
        } elseif (preg_match_all("/\[survey-link:(\w+)\]\[([^\]]+)\]/", $email_text, $matches)) {
            self::transformMatches($matches);
            foreach (array_values($matches) as $match) {
                $fullTextMatch = $match[0];
                $instrument = $match[1];
                $smartVariable = $match[2];
                $instance = $this->getNumericalInstanceForForm(
                    $projectId,
                    $record,
                    $event_id,
                    $instrument,
                    $smartVariable,
                    $isLongitudinal
                );
                if ($instance) {
                    $url = \REDCap::getSurveyLink($record, $instrument, $event_id, $instance, $projectId);
                    $email_text = str_replace($fullTextMatch, $url, $email_text);
                }
            }
        }
        if (preg_match_all("/\[survey-link:(\w+):\s*([^\]]+)\]/", $email_text, $matches)) {
            self::transformMatches($matches);
            foreach (array_values($matches) as $match) {
                $fullTextMatch = $match[0];
                $instrument = $match[1];
                $textForLink = $match[2];
                $url = \REDCap::getSurveyLink($record, $instrument, $event_id, 1, $projectId);
                $text = "<a href='$url'>$textForLink</a>";
                $email_text = str_replace($fullTextMatch, $text, $email_text);
            }
        } elseif (preg_match_all("/\[survey-link:(\w+)\]/", $email_text, $matches)) {
            self::transformMatches($matches);
            foreach (array_values($matches) as $match) {
                $fullTextMatch = $match[0];
                $instrument = $match[1];
                $url = \REDCap::getSurveyLink($record, $instrument, $event_id, 1, $projectId);
                $email_text = str_replace($fullTextMatch, $url, $email_text);
            }
        }
        if (preg_match_all("/\[survey-url:(\w+)\]\[([^\]]+)\]/", $email_text, $matches)) {
            self::transformMatches($matches);
            foreach (array_values($matches) as $match) {
                $fullTextMatch = $match[0];
                $instrument = $match[1];
                $smartVariable = $match[2];
                $instance = $this->getNumericalInstanceForForm(
                    $projectId,
                    $record,
                    $event_id,
                    $instrument,
                    $smartVariable,
                    $isLongitudinal
                );
                if ($instance) {
                    $url = \REDCap::getSurveyLink($record, $instrument, $event_id, $instance, $projectId);
                    $email_text = str_replace($fullTextMatch, $url, $email_text);
                }
            }
        }
        if (preg_match_all("/\[survey-url:(\w+)\]/", $email_text, $matches)) {
            self::transformMatches($matches);
            foreach (array_values($matches) as $match) {
                $fullTextMatch = $match[0];
                $instrument = $match[1];
                $url = \REDCap::getSurveyLink($record, $instrument, $event_id, 1, $projectId);
                $email_text = str_replace($fullTextMatch, $url, $email_text);
            }
        }
        if (preg_match_all("/\[survey-queue-link:\s*([^\]]+)\]/", $email_text, $matches)) {
            self::transformMatches($matches);
            foreach (array_values($matches) as $match) {
                $fullTextMatch = $match[0];
                $textForLink = $match[1];
                $url = \REDCap::getSurveyQueueLink($record, $projectId);
                if ($url) {
                    $text = "<a href='$url'>$textForLink</a>";
                    $email_text = str_replace($fullTextMatch, $text, $email_text);
                }
            }
        }
        if (preg_match("/\[survey-queue-url\]/", $email_text)) {
            $fullTextMatch = "[survey-queue-url]";
            $url = \REDCap::getSurveyQueueLink($record, $projectId);
            if ($url) {
                $email_text = str_replace($fullTextMatch, $url, $email_text);
            }
        }
        if (preg_match_all("/\[survey-return-code:(\w+)\]\[([^\]]+)\]/", $email_text, $matches)) {
            self::transformMatches($matches);
            foreach (array_values($matches) as $match) {
                $fullTextMatch = $match[0];
                $instrument = $match[1];
                $smartVariable = $match[2];
                $instance = $this->getNumericalInstanceForForm(
                    $projectId,
                    $record,
                    $event_id,
                    $instrument,
                    $smartVariable,
                    $isLongitudinal
                );
                if ($instance) {
                    $returnCode = $this->getReturnCode($record, $instrument, $event_id, $instance, $projectId);
                    if ($returnCode) {
                        $email_text = str_replace($fullTextMatch, $returnCode, $email_text);
                    }
                }
            }
        }
        if (preg_match_all("/\[survey-return-code:(\w+)\]/", $email_text, $matches)) {
            self::transformMatches($matches);
            foreach (array_values($matches) as $match) {
                $fullTextMatch = $match[0];
                $instrument = $match[1];
                $returnCode = $this->getReturnCode($record, $instrument, $event_id, 1, $projectId);
                if ($returnCode) {
                    $email_text = str_replace($fullTextMatch, $returnCode, $email_text);
                }
            }
        }
        return $email_text;
    }

    private function getReturnCode($record, $instrument, $eventId, $instance, $projectId)
    {
        if (method_exists("\REDCap", "getSurveyReturnCode")) {
            return \REDCap::getSurveyReturnCode($record, $instrument, $eventId, $instance, $projectId);
        }
        $sql = "SELECT r.return_code
        FROM redcap_surveys_response AS r
        INNER JOIN redcap_surveys_participants AS p
        ON (p.participant_id = r.participant_id)
        WHERE
            p.event_id = ?
            AND r.record=?
            AND r.instance=?
        LIMIT 1";
        $q = $this->query($sql, [$eventId,$record,$instance]);
        if ($row = $q->fetch_assoc()) {
            return $row['return_code'];
        }
        return "";
    }

    /**
     * Function that adds the survey link into the mail content
     * @param $email_text
     * @param $projectId
     * @param $record
     * @param $event_id
     * @param $isLongitudinal
     * @return mixed
     */
    private function setPassthroughSurveyLink($email_text, $projectId, $record, $event_id, $isLongitudinal){
        $surveyLink_var = $this->getProjectSetting("surveyLink_var", $projectId);
        if(!empty($surveyLink_var)) {
			## Sort survey links by reverse string lengths to prevent shorter links from
			## overwriting longer links containing the shorter links
            $datasurvey = explode("\n", $surveyLink_var);
			usort($datasurvey,function($a,$b) {
				$aLen = strlen($a);
				$bLen = strlen($b);
				if($aLen > $bLen) {
					return -1;
				}
				else if($aLen == $bLen) {
					return 0;
				}
				return 1;
			});
            foreach ($datasurvey as $surveylink) {
                $var = preg_split("/[;,]+/", $surveylink)[0];
                $var_replace = $var;

                preg_match_all("/\\[(.*?)\\]/", $var, $matches);
                //For arms and different events
                if(count($matches[1]) > 1){
                    $project = new \Project($projectId);
                    $form_event_id = $project->getEventIdUsingUniqueEventName($matches[1][0]);
                    if ($form_event_id) {
                        $var = $matches[1][1];
                    }
                }

                #only if the variable is in the text we reset the survey link status
                if (strpos($email_text, $var) !== false) {
                    list($form_event_id, $instrument_form, $instance) = $this->getEventIdInstrumentAndInstance($var_replace, "__SURVEYLINK_", $projectId, $record, $event_id, $isLongitudinal);
                    if ($instance == 1) {
                        $passthruData = $this->resetSurveyAndGetCodes($projectId, $record, $instrument_form, $form_event_id);

                        $returnCode = $passthruData['return_code'];
                        $hash = $passthruData['hash'];

                    } else {
                        # repeating instances - assume no need to reset survey since they're looking at a specific instance
                        # must generate link in DB, but this will use a return-code because save-and-return is enabled
                        $linkURL = \REDCap::getSurveyLink(
                            $record,
                            $instrument_form,
                            $form_event_id,
                            $instance,
                            $projectId
                        );
                        $returnCode = $this->getReturnCode(
                            $record,
                            $instrument_form,
                            $form_event_id,
                            $instance,
                            $projectId
                        );
                    }
                    ## getUrl doesn't append a pid when accessed through the cron, add pid if it's not there already
                    $baseUrl = $this->getUrl('surveyPassthru.php');
                    if(!preg_match("/[\&\?]pid=/", $baseUrl)) {
                        $baseUrl .= "&pid=".$projectId;
                    }

                    $url = $baseUrl . "&instrument=" . $instrument_form . "&record=" . $record . "&returnCode=" . $returnCode."&event=".$form_event_id."&instance=".$instance."&NOAUTH";
                    $link = "<a href='" . $url . "' target='_blank'>" . $url . "</a>";
                    $email_text = str_replace( $var_replace, $link, $email_text);
                }
            }
        }
        return $email_text;
    }

    /**
     * Function that adds attachments into the mail
     * @param $mail
     * @param $projectId
     * @param $id
     * @return mixed
     * @throws \Exception
     */
    function setAttachments($array_emails, $projectId, $id){
        for($i=1; $i<6 ; $i++){
            $attachmentAry = $this->getProjectSetting("email-attachment".$i,$projectId);
            $edoc = isset($attachmentAry[$id]) ? $attachmentAry[$id] : FALSE;
            if(is_numeric($edoc)){
                $array_emails = $this->addNewAttachment($array_emails,$edoc,$projectId,'files');
            }
        }
        return $array_emails;
    }

    /**
     * Function that adds piped attachments into the mail
     * @param $mail
     * @param $projectId
     * @param $data
     * @param $record
     * @param $event_id
     * @param $instrument
     * @param $repeat_instance
     * @param $id
     * @param bool $isLongitudinal
     * @return mixed
     * @throws \Exception
     */
    function setAttachmentsREDCapVar($array_emails,$projectId,$data, $record, $event_id, $instrument, $repeat_instance, $id, $isLongitudinal=false){
        $email_attachment_variable = htmlspecialchars_decode($this->getProjectSetting("email-attachment-variable", $projectId)[$id]);
        if(!empty($email_attachment_variable)){
            $var = preg_split("/[;,]+/", $email_attachment_variable);
            foreach ($var as $attachment) {
                $attachment = trim($attachment);
                if(\LogicTester::isValid($attachment)) {
                    $edoc = $this->isRepeatingInstrument($projectId,$data, $record, $event_id, $instrument, $repeat_instance, $attachment,0, $isLongitudinal);
                    if(is_numeric($edoc)) {
                        $array_emails = $this->addNewAttachment($array_emails,$edoc,$projectId,'files');
                    }
                }
            }
        }
        return $array_emails;
    }

    /**
     * Function that attaches images into the mail
     * @param $mail
     * @param $projectId
     * @param $email_text
     * @return mixed
     * @throws \Exception
     */
    function setEmbeddedImages($mail,$projectId,$email_text){
        preg_match_all('/src=[\"\'](.+?)[\"\'].*?/i',$email_text, $result);
        $result = array_unique($result[1]);
        foreach ($result as $img_src){
            preg_match_all('/(?<=file=)\\s*([0-9]+)\\s*/',$img_src, $result_img);
            $edoc = array_unique($result_img[1])[0];
            if(is_numeric($edoc)){
                $mail = $this->addNewAttachment($mail,$edoc,$projectId,'images');

                if(!empty($edoc)) {
                    $src = "cid:" . $edoc;
                    $email_text = str_replace($img_src, $src, $email_text);
                }
            }
        }
        return $mail;
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
        if (
            array_key_exists('repeat_instances',$data[$record])
            && (
                (
                    isset($data[$record]['repeat_instances'][$event_id][$instrument])
                    && $data[$record]['repeat_instances'][$event_id][$instrument][$instance][$instrument.'_complete'] == '2'
                )
                || (
                    isset($data[$record]['repeat_instances'][$event_id][""])
                    && $data[$record]['repeat_instances'][$event_id][''][$instance][$instrument.'_complete'] == '2'
                )
            )
        ){
            return true;
        }
        return false;
    }

    /**
     * Function that checks in the JSON if an email has already been sent by [survey][alert][record][event_id]
     * @param $email_repetitive_sent, the JSON
     * @param $new_record, the new record
     * @param $instrument, the survey
     * @param $alertid, the email alert
     * @return bool
     */
    function isEmailAlreadySentForThisSurvery($projectId,$email_repetitive_sent, $email_records_sent,$event_id, $record, $instrument, $alertid,$isRepeatInstrument,$repeat_instance){
        if(!empty($email_repetitive_sent)){
            if(array_key_exists($instrument,$email_repetitive_sent)){
                if(array_key_exists($alertid,$email_repetitive_sent[$instrument])){
                    if(array_key_exists('repeat_instances', $email_repetitive_sent[$instrument][$alertid])){
                        #In case they have changed the project to non repeatable
                        if(array_key_exists($record, $email_repetitive_sent[$instrument][$alertid]['repeat_instances'])){
                            if(array_key_exists($event_id, $email_repetitive_sent[$instrument][$alertid]['repeat_instances'][$record])){
                                if(in_array($repeat_instance, $email_repetitive_sent[$instrument][$alertid]['repeat_instances'][$record][$event_id])){
                                    return true;
                                }
                            }else{
                                //Old structure
                                foreach ($email_repetitive_sent[$instrument][$alertid]['repeat_instances'][$record] as $index=>$instance){
                                    if($instance == $repeat_instance){
                                        #delete the old instance and add a the new structure
                                        unset($email_repetitive_sent[$instrument][$alertid]['repeat_instances'][$record][$index]);
                                        $email_repetitive_sent = $this->addRecordSent($email_repetitive_sent, $record, $instrument, $alertid,$isRepeatInstrument,$repeat_instance,$event_id);
                                        $this->setProjectSetting('email-repetitive-sent', json_encode($email_repetitive_sent), $projectId);
                                        return true;
                                    }
                                }
                            }
                        }
                    }
                    if(array_key_exists($record, $email_repetitive_sent[$instrument][$alertid])){
                        if(array_key_exists($event_id, $email_repetitive_sent[$instrument][$alertid][$record])){
                            return true;
                        }else{
                            #Old structure
                            if($email_repetitive_sent[$instrument][$alertid][$record] == "1" && !$isRepeatInstrument){
                                #Add the event in the new structure
//                                $email_repetitive_sent = $this->addRecordSent($email_repetitive_sent, $record, $instrument, $alertid,$isRepeatInstrument,$repeat_instance,$event_id);
//                                $this->setProjectSetting('email-repetitive-sent', $email_repetitive_sent, $projectId);
                                $this->log('email-repetitive-sent', [
                                    'project_id' => $projectId,
                                    'instrument' => $instrument,
                                    'alert' => $alertid,
                                    'record_id' => $record,
                                    'event' => $event_id,
                                    'instance' => $instance
                                ]);
                                return true;
                            }
                        }
                    }
                    #If the record is registered as sent but it's not in the old repetitive sent structure
                    if($this->recordExistsInRegisteredRecords($email_records_sent,$record) && (!array_key_exists($record, $email_repetitive_sent[$instrument][$alertid]['repeat_instances']) && !array_key_exists($record, $email_repetitive_sent[$instrument][$alertid]))){
//                        $email_repetitive_sent = $this->addRecordSent($email_repetitive_sent, $record, $instrument, $alertid,$isRepeatInstrument,$repeat_instance,$event_id);
//                        $this->setProjectSetting('email-repetitive-sent', $email_repetitive_sent, $projectId);
                        $this->log('email-repetitive-sent', [
                            'project_id' => $projectId,
                            'instrument' => $instrument,
                            'alert' => $alertid,
                            'record_id' => $record,
                            'event' => $event_id,
                            'instance' => $instance
                        ]);
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Function that checks if a record email has been sent already but is not in the structure
     * @param $email_records_sent
     * @param $record
     * @return bool
     */
    function recordExistsInRegisteredRecords($email_records_sent,$record){
        if (is_array($email_records_sent)) {
            $records_registered = array_map('trim', $email_records_sent);
        } else if (is_string($email_records_sent)) {
            $records_registered = array_map('trim', explode(',', $email_records_sent));
        } else if ($email_records_sent === NULL) {
            $records_registered = [];
        } else {
            throw new \Exception("Invalid format");
        }
        foreach ($records_registered as $record_registered){
            if($record == $record_registered){
                return true;
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
    function isRepeatingInstrument($projectId,$data, $record, $event_id, $instrument, $repeat_instance, $var, $option=null, $isLongitudinal=false){
        $var_name = str_replace('[', '', $var);
        $var_name = str_replace(']', '', $var_name);
        $logic = "";
        if(
            array_key_exists('repeat_instances',$data[$record])
            && isset($data[$record]['repeat_instances'][$event_id][$instrument][$repeat_instance][$var_name])
            && $data[$record]['repeat_instances'][$event_id][$instrument][$repeat_instance][$var_name] != ""
        ) {
            #Repeating instruments by form
            $logic = $data[$record]['repeat_instances'][$event_id][$instrument][$repeat_instance][$var_name];
		}else if(
            array_key_exists('repeat_instances',$data[$record])
            && isset($data[$record]['repeat_instances'][$event_id][''][$repeat_instance][$var_name])
            && $data[$record]['repeat_instances'][$event_id][''][$repeat_instance][$var_name] != ""
        ) {
			#Repeating instruments by event
			$logic = $data[$record]['repeat_instances'][$event_id][''][$repeat_instance][$var_name];
		}else{
			$project = new \Project($projectId);
			if($option == '1'){
				if($isLongitudinal && \LogicTester::apply($var, $data[$record], $project, true, true) == ""){
					$logic = $data[$record][$event_id][$var_name];
				}else{
					$dumbVar = \Piping::pipeSpecialTags($var, $projectId, $record, $event_id, $repeat_instance);
					$logic = \LogicTester::apply($dumbVar, $data[$record], $project, true, true);
				}
			}else{
				if($isLongitudinal && \LogicTester::apply($var, $data[$record], $project, true, true) == ""){
					$logic = $data[$record][$event_id][$var_name];
				}else {
					preg_match_all("/\[[^\]]*\]/", $var, $matches);
					if (preg_match("/\(([^\)]+)\)/", $var_name, $checkboxMatches)) {
						#Special case for checkboxes
						$index = $checkboxMatches[1];
						$checkboxVarName = str_replace("($index)", "", $var_name);
						$logic = $data[$record][$event_id][$checkboxVarName][$index];
					} else if(sizeof($matches[0]) == 1 && \REDCap::getDataDictionary($projectId,'array',false,$var_name)[$var_name]['field_type'] == "radio"){
						#Special case for radio buttons
						$logic = $data[$record][$event_id][$var_name];
					}else{
						$dumbVar = \Piping::pipeSpecialTags($var, $projectId, $record, $event_id, $repeat_instance);
						$logic = \LogicTester::apply($dumbVar, $data[$record], $project, true, true);
					}
				}
				
				if($logic == "" && isset($data[$record]['repeat_instances'])){
					#it's a repeating instance from a different form
					foreach ($data[$record]['repeat_instances'][$event_id] ?: [] as $instrumentFound =>$instances){
						foreach ($instances as $instanceFound=>$p){
							if($instanceFound == $repeat_instance){
								$logic = $data[$record]['repeat_instances'][$event_id][$instrumentFound][$repeat_instance][$var_name];
							}
						}
					}
				}
			}
		}
        if (is_array($logic)) {
            $logic = "";
        }
		return htmlentities($logic ?? '', ENT_QUOTES);
	}

    /**
     * Function that replaces the logic variables for email values and checks if they are valid
     * @param $mail
     * @param $emailsTo, liest of emaisl to send as CC or To
     * @param $email_form_var, list of redcap email variables
     * @param $data, redcap data
     * @param $option, if they are To or CC emails
     * @param $projectId
     * @return mixed
     */
    function fill_emails($array_emails, $emailsTo, $email_form_var, $data, $option, $projectId, $record, $event_id, $instrument, $repeat_instance, $isLongitudinal=false){
        $array_emails_aux = array();
        foreach ($emailsTo as $email){
            foreach ($email_form_var as $email_var) {
                $var = preg_split("/[;,]+/", $email_var);
                if(!empty($email)) {
                    if (\LogicTester::isValid($var[0])) {
                       $email_redcap = $this->isRepeatingInstrument($projectId,$data, $record, $event_id, $instrument, $repeat_instance, $var[0],1,$isLongitudinal);

                       $isLabel = false;
                       if(is_numeric($email_redcap) || empty($email_redcap) || (is_array($email_redcap) && $isLongitudinal)){
                           $isLabel = true;
                           $email_redcap = $this->getChoiceLabel(array('field_name'=>$email, 'value'=>$email_redcap, 'project_id'=>$projectId, 'record_id'=>$record,'event_id'=>$event_id,'survey_form'=>$instrument,'instance'=>$repeat_instance));
                       }

                       if (
                           !empty($email_redcap)
                           && (
                               strpos($email, $var[0]) !== false
                               || $email_redcap == $email
                           )
                           && !$isLabel
                       ) {
                           $array_emails_aux[] = $email_redcap;
                       } else if(
                           filter_var(trim($email), FILTER_VALIDATE_EMAIL)
                           && (
                               empty($email_redcap)
                               || $email != $email_redcap
                           )
                       ) {
                           $array_emails_aux[] = $email;
                       }else if(
                           filter_var(trim($email_redcap), FILTER_VALIDATE_EMAIL)
                           && $email == $var[0]
                           && $isLabel
                       ) {
                           $array_emails_aux[] = $email_redcap;
                       } else if(
                           $email == $var[0]
                           && $isLabel
                       ) {
                           $email_redcap_checkboxes = preg_split("/[;,]+/", $email_redcap);
                           foreach ($email_redcap_checkboxes as $email_ck){
                               if(filter_var(trim($email_ck), FILTER_VALIDATE_EMAIL)){
                                   $array_emails_aux[] = $email_ck;
                               }
                           }
                       } else {
                           $ary = preg_split('/\s*<([^>]*)>/', $email_redcap, -1, PREG_SPLIT_NO_EMPTY|PREG_SPLIT_DELIM_CAPTURE);
                           if (count($ary) >= 2) {
                               $parsed_email = trim($ary[1]);
                               if(filter_var($parsed_email)){
                                   $array_emails_aux[] = $parsed_email;
                               }
                           }
                       }
                    } else {
                        $array_emails_aux[] = $email;
                    }
                }
            }
        }
        $array_emails[$option] = implode(";",$array_emails_aux);
        return $array_emails;
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
                \REDCap::email(trim($failed), 'noreply@vumc.org',$subject, $message);
            }
        }
    }

    /**
     * Function that checks if the emails are valid and sends an error email in case there's an error
     * @param $emails
     * @param $projectId
     * @return array|string
     */
    function check_email($emails, $projectId){
        $email_list = array();
        $email_list_error = array();
        $emails = preg_split("/[;,]+/", $emails);
        foreach ($emails as $email){
            if(!empty(trim($email))){
                if(filter_var(trim($email), FILTER_VALIDATE_EMAIL)) {
                    //VALID
                    array_push($email_list,trim($email));
                }else{
                    array_push($email_list_error,$email);
                }
            }
        }
        if(!empty($email_list_error)){
           $this->sendFailedEmailRecipient($this->getProjectSetting("emailFailed_var", $projectId),"Error: Email Address Validation" ,"The email ".print_r($email_list_error, true)." in the project ".$projectId.", may be invalid format");
        }
        return $email_list;
    }

    /**
     * Function that adds a ne attachment (file or image type) to the mail if the file exists in the DB and if it's no bigger than 3MB to send. Otherwise it sends an error email
     * @param $mail
     * @param $edoc
     * @param $projectId
     * @return mixed
     */
    function addNewAttachment($array_emails,$edoc,$projectId){
        if(!empty($edoc)) {
            $q = $this->query("SELECT stored_name,doc_name,doc_size FROM redcap_edocs_metadata WHERE doc_id=? AND project_id=?", [$edoc,$projectId]);
            while ($row = $q->fetch_assoc()) {
                if($row['doc_size'] > 3145728 ){
                   $this->sendFailedEmailRecipient($this->getProjectSetting("emailFailed_var", $projectId),"File Size too big" ,"One or more ".$type." in the project ".$projectId.", are too big to be sent.");
                }else{
                    //attach file with name as index
                    $array_emails['attachments'][$row['doc_name']] = EDOC_PATH . $row['stored_name'];
                }
            }
        }
        return $array_emails;
    }

    /**
     * Function that creates and returns the JSON of the emails sent by [survey][alert][record][event_id]
     * @param $email_repetitive_sent, the JSON
     * @param $new_record, the new record
     * @param $instrument, the survey
     * @param $alertid, the email alert
     * @return string
     */
    function addRecordSent($email_repetitive_sent, $new_record, $instrument, $alertid,$isRepeatInstrument,$repeat_instance,$event_id){
        $email_repetitive_sent_aux = $email_repetitive_sent;
        if(!empty($email_repetitive_sent)) {
            $found_new_instrument = true;
            foreach ($email_repetitive_sent as $sv_name => $survey_records) {
                if($sv_name == $instrument) {
                    $found_new_instrument = false;
                    $found_new_alert = true;
                    foreach ($survey_records as $alert => $alert_value) {
                        if ($alert == $alertid) {
                            $found_new_alert = false;
                            $found_new_record = true;
                            $found_is_repeat = false;
                            if(!empty($alert_value)){
                                foreach ($alert_value as $sv_number => $survey_record) {
                                    if ($sv_number === "repeat_instances") {
                                        $found_is_repeat = true;
                                        if($isRepeatInstrument){
                                            foreach ($alert_value['repeat_instances'] as $survey_record_repeat =>$survey_instances){
                                                return  $this->addArrayInfo(true,$email_repetitive_sent_aux,$instrument,$alertid,$new_record, $repeat_instance,$event_id);
                                            }
                                        }
                                    }else if($sv_number == $new_record){
                                        if($isRepeatInstrument){
                                            return  $this->addArrayInfo(true,$email_repetitive_sent_aux,$instrument,$alertid,$new_record, $repeat_instance,$event_id);
                                        }else{
                                            if(is_array($survey_record)){
                                                return  $this->addArrayInfo(false,$email_repetitive_sent_aux,$instrument,$alertid,$new_record, $repeat_instance,$event_id);
                                            }else{
                                                $event_array = array($event_id => $repeat_instance);
                                                $email_repetitive_sent_aux[$instrument][$alertid][$new_record] = $event_array;
                                                return $email_repetitive_sent_aux;
                                            }
                                        }

                                    }

                                }
                            }

                        }
                    }
                }
            }
            if($found_new_instrument){
                return $this->addJSONInfo($isRepeatInstrument,$email_repetitive_sent_aux,$instrument,$alertid,$new_record, $repeat_instance,$event_id,false);
            }else if($found_new_alert){
                return $this->addJSONInfo($isRepeatInstrument,$email_repetitive_sent_aux,$instrument,$alertid,$new_record, $repeat_instance,$event_id,false);
            }else if($found_new_record){
                return $this->addJSONInfo($isRepeatInstrument,$email_repetitive_sent_aux,$instrument,$alertid,$new_record, $repeat_instance,$event_id,$found_is_repeat);
            }else if(!$found_new_record && !$found_is_repeat){
                return $this->addJSONInfo($isRepeatInstrument,$email_repetitive_sent_aux,$instrument,$alertid,$new_record, $repeat_instance,$event_id,true);
            }
            return $email_repetitive_sent_aux;
        }else{
            return $this->addJSONInfo($isRepeatInstrument,$email_repetitive_sent_aux,$instrument,$alertid,$new_record, $repeat_instance,$event_id,false);
        }

    }

    /**
     * Function that adds the new information to the structure
     * @param $isRepeatInstrument
     * @param $email_repetitive_sent_aux
     * @param $instrument
     * @param $alertid
     * @param $new_record
     * @param $repeat_instance
     * @param $event_id
     * @param $found_is_repeat
     * @return string
     */
    function addJSONInfo($isRepeatInstrument,$email_repetitive_sent_aux,$instrument,$alertid,$new_record, $repeat_instance,$event_id, $found_is_repeat){
        if($instrument != "" && $new_record != "" & $alertid != "" && $event_id != "") {
            if ($isRepeatInstrument) {
                #NEW REPEAT INSTANCE
                if (!$found_is_repeat) {
                    $email_repetitive_sent_aux = $this->addArrayInfo(true, $email_repetitive_sent_aux, $instrument, $alertid, $new_record, $repeat_instance, $event_id);
                } else {
                    $email_repetitive_sent_aux = $this->addArrayInfo(false, $email_repetitive_sent_aux, $instrument, $alertid, $new_record, $repeat_instance, $event_id);
                }
            } else {
                $email_repetitive_sent_aux = $this->addArrayInfo(false, $email_repetitive_sent_aux, $instrument, $alertid, $new_record, $repeat_instance, $event_id);
            }
        }
        return $email_repetitive_sent_aux;
    }

    /**
     * Function that saves the infomation in the structure in the right format
     * @param $addRepeat
     * @param $email_repetitive_sent_aux
     * @param $instrument
     * @param $alertid
     * @param $new_record
     * @param $repeat_instance
     * @param $event_id
     * @return string
     */
    function addArrayInfo($addRepeat,$email_repetitive_sent_aux,$instrument,$alertid,$new_record, $repeat_instance,$event_id){
        if($addRepeat){
            if(
                !isset($email_repetitive_sent_aux[$instrument][$alertid]['repeat_instances'][$new_record][$event_id])
                || !is_array($email_repetitive_sent_aux[$instrument][$alertid]['repeat_instances'][$new_record][$event_id])
            ){
                $email_repetitive_sent_aux[$instrument][$alertid]['repeat_instances'][$new_record][$event_id] = array();
            }
            array_push($email_repetitive_sent_aux[$instrument][$alertid]['repeat_instances'][$new_record][$event_id],$repeat_instance);
        }else{
            if(
                !isset($email_repetitive_sent_aux[$instrument][$alertid][$new_record][$event_id])
                || !is_array($email_repetitive_sent_aux[$instrument][$alertid][$new_record][$event_id])
            ){
                $email_repetitive_sent_aux[$instrument][$alertid][$new_record][$event_id] = array();
            }
            array_push($email_repetitive_sent_aux[$instrument][$alertid][$new_record][$event_id],$repeat_instance);
        }
        return $email_repetitive_sent_aux;
    }

    function getAdditionalFieldChoices($configRow,$pid) {
        if ($configRow['type'] == 'user-role-list') {
            $choices = [];

            $sql = "SELECT CAST(role_id as CHAR) as role_id,role_name
						FROM redcap_user_roles
						WHERE project_id = ?
						ORDER BY role_id";
            $result = $this->query($sql, [$pid]);

            while ($row = $result->fetch_assoc()) {
                $choices[] = ['value' => htmlentities($row['role_id'],ENT_QUOTES), 'name' => strip_tags(nl2br(htmlentities($row['role_name'],ENT_QUOTES)))];
            }

            $configRow['choices'] = $choices;
        }
        else if ($configRow['type'] == 'user-list') {
            $choices = [];

            $sql = "SELECT ur.username,ui.user_firstname,ui.user_lastname
						FROM redcap_user_rights ur, redcap_user_information ui
						WHERE ur.project_id = ?
								AND ui.username = ur.username
						ORDER BY ui.ui_id";
            $result = $this->query($sql, [$pid]);

            while ($row = $result->fetch_assoc()) {
                $choices[] = ['value' => strtolower(htmlentities($row['username'],ENT_QUOTES)), 'name' => htmlentities($row['user_firstname'],ENT_QUOTES) . ' ' . htmlentities($row['user_lastname'],ENT_QUOTES)];
            }

            $configRow['choices'] = $choices;
        }
        else if ($configRow['type'] == 'dag-list') {
            $choices = [];

            $sql = "SELECT CAST(group_id as CHAR) as group_id,group_name
						FROM redcap_data_access_groups
						WHERE project_id = ?
						ORDER BY group_id";
            $result = $this->query($sql, [$pid]);

            while ($row = $result->fetch_assoc()) {
                $choices[] = ['value' => htmlentities($row['group_id'],ENT_QUOTES), 'name' => strip_tags(nl2br(htmlentities($row['group_name'],ENT_QUOTES)))];
            }

            $configRow['choices'] = $choices;
        }
        else if ($configRow['type'] == 'field-list') {
            $choices = [];

            $sql = "SELECT field_name,element_label
					FROM redcap_metadata
					WHERE project_id = ?
					ORDER BY field_order";
            $result = $this->query($sql, [$pid]);

            while ($row = $result->fetch_assoc()) {
                $row['element_label'] = htmlentities(strip_tags(nl2br($row['element_label'])),ENT_QUOTES);
                if (strlen($row['element_label']) > 30) {
                    $row['element_label'] = substr($row['element_label'], 0, 20) . "... " . substr($row['element_label'], -8);
                }
                $choices[] = ['value' => htmlentities($row['field_name'],ENT_QUOTES), 'name' => htmlentities($row['field_name'],ENT_QUOTES) . " - " . htmlentities($row['element_label'],ENT_QUOTES)];
            }

            $configRow['choices'] = $choices;
        }
        else if ($configRow['type'] == 'form-list') {
            $choices = [];

            $sql = "SELECT DISTINCT form_name
					FROM redcap_metadata
					WHERE project_id = ?
					ORDER BY field_order";
            $result = $this->query($sql, [$pid]);

            while ($row = $result->fetch_assoc()) {
                $choices[] = ['value' => htmlentities($row['form_name'],ENT_QUOTES), 'name' => strip_tags(nl2br(htmlentities($row['form_name'],ENT_QUOTES)))];
            }

            $configRow['choices'] = $choices;
        }
        else if ($configRow['type'] == 'arm-list') {
            $choices = [];

            $sql = "SELECT CAST(a.arm_id as CHAR) as arm_id, a.arm_name
					FROM redcap_events_arms a
					WHERE a.project_id = ?
					ORDER BY a.arm_id";
            $result = $this->query($sql, [$pid]);

            while ($row = $result->fetch_assoc()) {
                $choices[] = ['value' => htmlentities($row['arm_id'],ENT_QUOTES), 'name' => htmlentities($row['arm_name'],ENT_QUOTES)];
            }

            $configRow['choices'] = $choices;
        }
        else if ($configRow['type'] == 'event-list') {
            $choices = [];

            $sql = "SELECT CAST(e.event_id as CHAR) as event_id, e.descrip, CAST(a.arm_id as CHAR) as arm_id, a.arm_name
					FROM redcap_events_metadata e, redcap_events_arms a
					WHERE a.project_id = ?
						AND e.arm_id = a.arm_id
					ORDER BY e.event_id";
            $result = $this->query($sql, [$pid]);

            while ($row = $result->fetch_assoc()) {
                $choices[] = ['value' => htmlentities($row['event_id'],ENT_QUOTES), 'name' => "Arm: ".strip_tags(nl2br(htmlentities($row['arm_name'],ENT_QUOTES)))." - Event: ".strip_tags(nl2br(htmlentities($row['descrip'],ENT_QUOTES)))];
            }

            $configRow['choices'] = $choices;
        }
        else if($configRow['type'] == 'sub_settings') {
            foreach ($configRow['sub_settings'] as $subConfigKey => $subConfigRow) {
                $configRow['sub_settings'][$subConfigKey] = $this->getAdditionalFieldChoices($subConfigRow,$pid);
                if($configRow['super-users-only']) {
                    $configRow['sub_settings'][$subConfigKey]['super-users-only'] = $configRow['super-users-only'];
                }
                if(!isset($configRow['source']) && $configRow['sub_settings'][$subConfigKey]['source']) {
                    $configRow['source'] = "";
                }
                $configRow["source"] .= ($configRow["source"] == "" ? "" : ",").$configRow['sub_settings'][$subConfigKey]['source'];
            }
        }
        else if($configRow['type'] == 'project-id') {
            // We only show projects to which the current user has design rights
            // since modules could make all kinds of changes to projects.
            $sql ="SELECT CAST(p.project_id as char) as project_id, p.app_title
					FROM redcap_projects p, redcap_user_rights u
					WHERE p.project_id = u.project_id
						AND u.username = ?
						AND u.design = 1";

            $result = ExternalModules::query($sql, USERID);

            $matchingProjects = [
                [
                    "value" => "",
                    "name" => ""
                ]
            ];

            while($row = $result->fetch_assoc()) {
                $projectName = utf8_encode($row["app_title"]);

                // Required to display things like single quotes correctly
                $projectName = htmlspecialchars_decode($projectName, ENT_QUOTES);

                $matchingProjects[] = [
                    "value" => htmlentities($row["project_id"],ENT_QUOTES),
                    "name" => "(" . htmlentities($row["project_id"],ENT_QUOTES) . ") " . $projectName,
                ];
            }
            $configRow['choices'] = $matchingProjects;
        }

        return $configRow;
    }

    // This can be removed once the REDCap min version can be updated to a version that includes this function in the framework.
    public function getSafePath($path, $root){
		if(!file_exists($root)){
			//= The specified root ({0}) does not exist as either an absolute path or a relative path to the module directory.
			throw new \Exception(ExternalModules::tt("em_errors_103", $root));
		}

		$root = realpath($root);

		if(strpos($path, $root) === 0){
			// The root is already included inthe path.
			$fullPath = $path;
		}
		else{
			$fullPath = "$root/$path";
		}

		if(file_exists($fullPath)){
			$fullPath = realpath($fullPath);
		}
		else{
			// Also support the case where this is a path to a new file that doesn't exist yet and check it's parents.
			$dirname = dirname($fullPath);
				
			if(!file_exists($dirname)){
				//= The parent directory ({0}) does not exist.  Please create it before calling getSafePath() since the realpath() function only works on directories that exist.
				throw new \Exception(ExternalModules::tt("em_errors_104", $dirname));
			}

			$fullPath = realpath($dirname) . DIRECTORY_SEPARATOR . basename($fullPath);
		}

		if(strpos($fullPath, $root) !== 0){
			//= You referenced a path ({0}) that is outside of your allowed parent directory ({1}).
			throw new \Exception(ExternalModules::tt("em_errors_105", $fullPath, $root));
		}

		return $fullPath;
	}

	function deleteOldLogs($projectId){
        $remove_logs_date = strtotime($this->getProjectSetting("remove-logs-date",$projectId));
        $today = strtotime(date('Y-m-d'));

        #Make sure we only remove logs once a day
        if($remove_logs_date == "" || $remove_logs_date < $today) {
            #If logs are older than a month, delete them. Only delete the scheduledemails log data
            $this->removeLogs('
                project_id = ?
                and scheduledemails = 1
                and timestamp < date_sub(now(), interval 1 month)
                ', [$projectId]);
            $this->setProjectSetting('remove-logs-date', date('Y-m-d'), $projectId);
        }
    }

    function isProjectStatusCompleted ($projectId){
        #project is completed, deactivate all alerts
        $email_deactivate = $this->getProjectSetting("email-deactivate",$projectId);
        $project = new \Project($projectId);
        #If project completed and there are active alerts
        if($project->project['completed_time'] != "" && in_array('0', $email_deactivate, 0)){
            foreach ($email_deactivate as $index=>$deamil){
                $email_deactivate[$index] = "1";
                #Deactivate queued alerts
                $email_queue =  $this->getProjectSetting('email-queue',$projectId);
                if(!empty($email_queue)){
                    $scheduled_records_changed = "";
                    $queue = $email_queue;
                    foreach ($email_queue as $id=>$email){
                        if($email['project_id'] == $projectId && $email['alert']==$index){
                            $queue[$id]['deactivated'] = 1;
                            $scheduled_records_changed .= $email['record'].",";
                        }
                    }
                    $this->setProjectSetting('email-queue', $queue, $projectId);

                    #Add logs
                    $action_description = "Deactivated Scheduled Alert ".$index;
                    $changes_made = "Record IDs deactivated: ".rtrim($scheduled_records_changed,",");
                    \REDCap::logEvent($action_description,$changes_made,null,null,null,$projectId);
                }
            }
            $this->setProjectSetting('email-deactivate', $email_deactivate,$projectId);
            $this->log("scheduledemails PID: " . $projectId . " - Project Completed. All alerts have been deactivated");

            return true;
        }
        return false;
    }

    const SMART_VARIABLES = ["new-instance", "last-instance", "first-instance"];
}



