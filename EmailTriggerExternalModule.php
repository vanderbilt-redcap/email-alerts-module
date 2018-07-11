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
        $data = \REDCap::getData($project_id,"array",$record);
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
                            #Surveys are always complete
                            $isRepeatInstrument = false;
                            if((array_key_exists('repeat_instances',$data[$record]) && ($data[$record]['repeat_instances'][$event_id][$form][$repeat_instance][$form.'_complete'] != '' || $data[$record]['repeat_instances'][$event_id][''][$repeat_instance][$form.'_complete'] != ''))){
                                $isRepeatInstrument = true;
                            }
                            $this->setEmailTriggerRequested(true);
                            $this->sendEmailAlert($project_id, $id, $data, $record,$event_id,$instrument,$repeat_instance,$isRepeatInstrument);
                        }
                    }
                }
            }
        }
    }

    function hook_save_record ($project_id,$record = NULL,$instrument,$event_id, $group_id, $survey_hash,$response_id, $repeat_instance){
        $data = \REDCap::getData($project_id,"array",$record);
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
                                $this->sendEmailAlert($project_id, $id, $data, $record,$event_id,$instrument,$repeat_instance,$isRepeatInstrument);
                            }
                        }
                    }
                }
//                die;
            }
        }
    }

    /**
     * Function that deletes information when we click on the REDCap buttons: Delete the project, Erase all data, Delete record
     * @param null $project_id
     */
    function hook_every_page_before_render($project_id = null){
        if(strpos($_SERVER['REQUEST_URI'],'delete_project.php') !== false && $_POST['action'] == 'delete') {
            #Button: Delete the project

            $this->setProjectSetting('email-queue', '');
        }else if(strpos($_SERVER['REQUEST_URI'],'erase_project_data.php') !== false && $_POST['action'] == 'erase_data'){
            #Button: Erase all data

            $this->setProjectSetting('email-repetitive-sent', '');
            $this->setProjectSetting('email-records-sent', '');
            $this->setProjectSetting('email-queue', '');
        }else if($_REQUEST['route'] == 'DataEntryController:deleteRecord'){
            #Button: Delete record

            $record_id = urldecode($_REQUEST['record']);

            #Delete email repetitive sent and the list of records before deleting all data
            $email_repetitive_sent =  empty($this->getProjectSetting('email-repetitive-sent'))?array():$this->getProjectSetting('email-repetitive-sent');
            $email_repetitive_sent = json_decode($email_repetitive_sent,true);
            $email_records_sent =  empty($this->getProjectSetting('email-records-sent'))?array():$this->getProjectSetting('email-records-sent');

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
            if(!empty($email_records_sent)){
                foreach ($email_records_sent as $index=>$sent){
                    $records = array_map('trim', explode(',', $sent));
                    foreach ($records as $record){
                        if($record == $record_id){
                            #Delete list of records sent
                            if(str_replace($record_id.", ","",$email_records_sent[$index], $count) == 0){
                                $email_records_sent[$index] = str_replace($record_id.", ","",$email_records_sent[$index]);
                            }else{
                                $email_records_sent[$index] = str_replace($record_id,"",$email_records_sent[$index]);
                            }
                        }
                    }
                }
                $this->setProjectSetting('email-records-sent', $email_records_sent);
            }

            #Delete the queued emails for that record
            $email_queue =  empty($this->getProjectSetting('email-queue'))?array():$this->getProjectSetting('email-queue');
            $email_queue_aux = $email_queue;
            if(!empty($email_queue)){
                foreach ($email_queue as $id=>$email){
                    if($email['project_id'] == $project_id && $email['record'] == $record){
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
     * @param $project_id
     * @param $id
     * @param $data
     * @param $record
     * @param $event_id
     * @param $instrument
     * @param $repeat_instance
     * @param $isRepeatInstrument
     * @throws \Exception
     */
    function sendEmailAlert($project_id, $id, $data, $record,$event_id,$instrument,$repeat_instance,$isRepeatInstrument){
//        $id=9;
        $email_repetitive = $this->getProjectSetting("email-repetitive",$project_id)[$id];
        $email_deactivate = $this->getProjectSetting("email-deactivate",$project_id)[$id];
        $email_deleted = $this->getProjectSetting("email-deleted",$project_id)[$id];
        $email_repetitive_sent = json_decode($this->getProjectSetting("email-repetitive-sent",$project_id),true);
        $email_records_sent = $this->getProjectSetting("email-records-sent",$project_id);
        $email_condition = $this->getProjectSetting("email-condition", $project_id)[$id];
        $isEmailAlreadySentForThisSurvery = $this->isEmailAlreadySentForThisSurvery($project_id,$email_repetitive_sent,$email_records_sent[$id],$event_id, $record, $instrument,$id,$isRepeatInstrument,$repeat_instance);

//        echo "Alert: ".$id."<br>";
//        echo "email_repetitive: ".$email_repetitive."<br>";
        echo "isEmailAlreadySentForThisSurvery: ".$isEmailAlreadySentForThisSurvery."<br><br><br>";
//        echo "email_deleted: ".$email_deleted."<br>";
//        echo "email_deactivate: ".$email_deactivate."<br>";
//        echo "email_repetitive: ".$email_repetitive."<br>";

        if((($email_repetitive == "1") || ($email_repetitive == '0' && !$isEmailAlreadySentForThisSurvery)) && ($email_deactivate == "0" || $email_deactivate == "") && ($email_deleted == "0" || $email_deleted == "")) {
            echo "SEND!<br><br><br><br><br>";
            #If the condition is met or if we don't have any, we send the email
            $evaluateLogic = \REDCap::evaluateLogic($email_condition, $project_id, $record,$event_id);
            if($isRepeatInstrument){
                $evaluateLogic = \REDCap::evaluateLogic($email_condition, $project_id, $record,$event_id, $repeat_instance, $instrument);
            }
            if ((!empty($email_condition) && \LogicTester::isValid($email_condition) && $evaluateLogic) || empty($email_condition)) {
                $cron_repeat_email = $this->getProjectSetting("cron-repeat-email", $project_id)[$id];
                $cron_send_email_on = $this->getProjectSetting("cron-send-email-on", $project_id)[$id];
                $cron_send_email_on_field = $this->getProjectSetting("cron-send-email-on-field", $project_id)[$id];

                #To ensure it's the last module called
                $delayedSuccessful =  $this->delayModuleExecution();
                if($delayedSuccessful){
                    return;
                }

                if($email_repetitive == '0' && ($cron_repeat_email == '1' || ($cron_send_email_on != 'now' && $cron_send_email_on != '' && $cron_send_email_on_field !=''))){
                    #SCHEDULED EMAIL
                    if($this->addEmailToQueue($project_id, $record, $event_id, $repeat_instance, $instrument, $isRepeatInstrument, $id)){
                        $this->addQueuedEmail($id,$project_id,$record,$event_id,$instrument,$repeat_instance,$isRepeatInstrument);
                    }

                }else{
                    #REGULAR EMAIL
                    $this->createAndSendEmail($data,$project_id,$record,$id,$instrument,$repeat_instance,$isRepeatInstrument,$event_id,false);
                }
            }
        }
    }

    /**
     * Function to add queued emails from the user interface
     * @param $project_id
     * @param $alert
     * @param $record
     * @param $times_sent
     */
    function addQueueEmailFromInterface($project_id, $alert, $record, $times_sent, $event_id, $last_sent,$instance){
        $data = \REDCap::getData($project_id,"array",$record);

        $instrument = $this->getProjectSetting("form-name",$project_id)[$alert];
        $repeat_instance = "1";

        $isRepeatInstrument = false;
        if((array_key_exists('repeat_instances',$data[$record]) && ($data[$record]['repeat_instances'][$event_id][$instrument][$repeat_instance][$instrument.'_complete'] != '' || $data[$record]['repeat_instances'][$event_id][''][$repeat_instance][$instrument.'_complete'] != ''))){
            $isRepeatInstrument = true;
        }

        if($this->addEmailToQueue($project_id, $record, $event_id, $repeat_instance, $instrument, $isRepeatInstrument, $alert) && !$this->isAlreadyInQueue($alert, $project_id, $record,$instance)){
            $this->addQueuedEmail($alert,$project_id,$record,$event_id,$instrument,$repeat_instance,$isRepeatInstrument,$times_sent,$last_sent);
        }else{
            return $record;
        }
        return "";
    }

    function isAlreadyInQueue($alert, $project_id, $record, $instance){
        $email_queue = empty($this->getProjectSetting('email-queue'))?array():$this->getProjectSetting('email-queue');
        $found = false;
        foreach ($email_queue as $index=>$queue){
            if($alert == $queue['alert'] && $project_id == $queue['project_id'] && $record == $queue['record'] && $queue['instance'] == $instance){
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
        $sql="SELECT s.project_id FROM redcap_external_modules m, redcap_external_module_settings s WHERE m.external_module_id = s.external_module_id AND s.value = 'true' AND m.directory_prefix = 'vanderbilt_emailTrigger' AND s.`key` = 'enabled'";
        $q = $this->query($sql);

        if($error = db_error()){
            throw new \Exception($sql.': '.$error);
        }

        while($row = db_fetch_assoc($q)){
            $project_id = $row['project_id'];
            $email_queue =  $this->getProjectSetting('email-queue',$project_id);
            $queue_aux = $email_queue;
            $delete_queue = array();
            if($email_queue != ''){
                $email_sent_total = 0;
                foreach ($email_queue as $index=>$queue){
                    if($email_sent_total < 100) {
                        if($queue['deactivated'] != 1 && $this->sendToday($queue, $index)){
                            error_log("scheduledemails PID: ".$project_id."/ ".$queue['project_id']." - Has queued emails to send today ".date("Y-m-d H:i:s"));
                            #SEND EMAIL
                            $email_sent = $this->sendQueuedEmail($queue['project_id'],$queue['record'],$queue['alert'],$queue['instrument'],$queue['instance'],$queue['isRepeatInstrument'],$queue['event_id']);
                            #If email sent save date and number of times sent and delete queue if needed
                            if($email_sent){
                                $queue_aux[$index]['last_sent'] = date('Y-m-d');
                                $queue_aux[$index]['times_sent'] = $queue['times_sent'] + 1;
                                $this->setProjectSetting('email-queue', $queue_aux,$queue['project_id']);
                                $email_sent_total++;

                                #If it's the last time we send, we delete the queue
                                $delete_queue = $this->stopRepeat($delete_queue,$queue,$index);
                            }
                        }
                    }else{
                        break;
                    }
                }
                #delete all queues that need to stop sending
                $this->deleteQueuedEmail($delete_queue,$project_id);
            }
        }
    }

    /**
     * Function that checks if the email alert has to be sent today or not
     * @param $queue, the email alert queue info
     * @param $index, the queue index
     * @return bool
     */
    function sendToday($queue, $index)
    {
        $cron_send_email_on_field = empty($this->getProjectSetting('cron-send-email-on-field',$queue['project_id'])) ? array() : $this->getProjectSetting('cron-send-email-on-field',$queue['project_id'])[$queue['alert']];
        $cron_repeat_email =  empty($this->getProjectSetting('cron-repeat-email',$queue['project_id']))?array():$this->getProjectSetting('cron-repeat-email',$queue['project_id'])[$queue['alert']];
        $cron_repeat_for =  empty($this->getProjectSetting('cron-repeat-for',$queue['project_id']))?array():$this->getProjectSetting('cron-repeat-for',$queue['project_id'])[$queue['alert']];
        $cron_repeat_until =  empty($this->getProjectSetting('cron-repeat-until',$queue['project_id']))?array():$this->getProjectSetting('cron-repeat-until',$queue['project_id'])[$queue['alert']];
        $cron_repeat_until_field =  empty($this->getProjectSetting('cron-repeat-until-field',$queue['project_id']))?array():$this->getProjectSetting('cron-repeat-until-field',$queue['project_id'])[$queue['alert']];

        $repeat_days = $cron_repeat_for;
        if($queue['times_sent'] != 0){
            $repeat_days = $cron_repeat_for * $queue['times_sent'];
        }

        $today = date('Y-m-d');
        $extra_days = ' + ' . $repeat_days . " days";
        $repeat_date = date('Y-m-d', strtotime($cron_send_email_on_field . $extra_days));
        $repeat_date_now = date('Y-m-d', strtotime($queue['last_sent'] . '+'.$cron_repeat_for.' days'));

        $evaluateLogic_on = \REDCap::evaluateLogic($cron_send_email_on_field, $queue['project_id'], $queue['record'], $queue['event_id']);
        $evaluateLogic = \REDCap::evaluateLogic($cron_repeat_until_field, $queue['project_id'], $queue['record'], $queue['event_id']);
        if($queue['isRepeatInstrument']){
            $evaluateLogic_on = \REDCap::evaluateLogic($cron_send_email_on_field,  $queue['project_id'], $queue['record'], $queue['event_id'], $queue['instance'], $queue['instrument']);
            $evaluateLogic = \REDCap::evaluateLogic($cron_repeat_until_field,  $queue['project_id'], $queue['record'], $queue['event_id'], $queue['instance'], $queue['instrument']);
        }

        if(strtotime($queue['last_sent']) != strtotime($today) || $queue['last_sent'] == ""){
            if (($queue['option'] == 'date' && ($cron_send_email_on_field == $today || $repeat_date == $today || ($queue['last_sent'] == "" && strtotime($cron_send_email_on_field) <= strtotime($today)))) || ($queue['option'] == 'calc' && $evaluateLogic_on) || ($queue['option'] == 'now' && ($repeat_date_now == $today || $queue['last_sent'] == ''))) {
                if($cron_repeat_email == "1"){
                    #check repeat until option to see if we need to stop
                    if ($cron_repeat_until != 'forever' && $cron_repeat_until != '') {
                        if ($cron_repeat_until == 'date') {
                            if (strtotime($cron_repeat_until_field) >= strtotime($today)) {
                                return true;
                            } else {
                                $this->deleteQueuedEmail($index,$queue['project_id']);
                                return false;
                            }
                        } else if ($cron_repeat_until == 'cond' && $cron_repeat_until_field != "") {
                            if ($evaluateLogic) {
                                $this->deleteQueuedEmail($index,$queue['project_id']);
                                return false;
                            } else {
                                return true;
                            }
                        }
                    } else if ($cron_repeat_until == 'forever') {
                        return true;
                    }
                }else{
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Function that checks if the repeatable option has met the condition if yes, then we don't add the email to the queue
     * @param $project_id
     * @param $record
     * @param $event_id
     * @param $instance
     * @param $instrument
     * @param $isRepeatInstrument
     * @param $id
     * @return bool
     */
    function addEmailToQueue($project_id, $record, $event_id, $instance, $instrument, $isRepeatInstrument, $id){
        $cron_repeat_email =  empty($this->getProjectSetting('cron-repeat-email',$project_id))?array():$this->getProjectSetting('cron-repeat-email',$project_id)[$id];
        $cron_repeat_until =  empty($this->getProjectSetting('cron-repeat-until',$project_id))?array():$this->getProjectSetting('cron-repeat-until',$project_id)[$id];
        $cron_repeat_until_field =  empty($this->getProjectSetting('cron-repeat-until-field',$project_id))?array():$this->getProjectSetting('cron-repeat-until-field',$project_id)[$id];

        $today = date('Y-m-d');
        $evaluateLogic = \REDCap::evaluateLogic($cron_repeat_until_field, $project_id, $record, $event_id);
        if($isRepeatInstrument){
            $evaluateLogic = \REDCap::evaluateLogic($cron_repeat_until_field,  $project_id, $record, $event_id, $instance, $instrument);
        }

        if($cron_repeat_email == "1"){
            if ($cron_repeat_until != 'forever' && $cron_repeat_until != '') {
                if ($cron_repeat_until == 'date') {
                    if (strtotime($cron_repeat_until_field) >= strtotime($today)) {
                        return true;
                    } else {
                        return false;
                    }
                } else if ($cron_repeat_until == 'cond' && $cron_repeat_until_field != "") {
                    if ($evaluateLogic) {
                        return false;
                    } else {
                        return true;
                    }
                }
            } else if ($cron_repeat_until == 'forever') {
                return true;
            }
        }
        return true;

    }

    /**
     * Function that checks if it has to stop sending the email alerts and delete them from the queue
     * @param $delete_queue, array to fill up with the queue indexes to delete
     * @param $queue, current queue
     * @param $index, queue index
     * @return mixed
     */
    function stopRepeat($delete_queue,$queue,$index){
        $cron_repeat_email =  empty($this->getProjectSetting('cron-repeat-email',$queue['project_id']))?array():$this->getProjectSetting('cron-repeat-email',$queue['project_id'])[$queue['alert']];
        $cron_repeat_until =  empty($this->getProjectSetting('cron-repeat-until',$queue['project_id']))?array():$this->getProjectSetting('cron-repeat-until',$queue['project_id'])[$queue['alert']];
        $cron_repeat_until_field =  empty($this->getProjectSetting('cron-repeat-until-field',$queue['project_id']))?array():$this->getProjectSetting('cron-repeat-until-field',$queue['project_id'])[$queue['alert']];

        $evaluateLogic = \REDCap::evaluateLogic($cron_repeat_until_field, $queue['project_id'], $queue['record'], $queue['event_id']);
        if($queue['isRepeatInstrument']){
            $evaluateLogic = \REDCap::evaluateLogic($cron_repeat_until_field,  $queue['project_id'], $queue['record'], $queue['event_id'], $queue['instance'], $queue['instrument']);
        }
        if($cron_repeat_email == '0'){
            array_push($delete_queue,$index);
        }else if($cron_repeat_until != 'forever' && $cron_repeat_until != '' && $cron_repeat_email == '1'){
            if($cron_repeat_until == 'date'){
                if(strtotime($cron_repeat_until_field) <= strtotime(date('Y-m-d'))){
                    array_push($delete_queue,$index);
                }
            }else if($cron_repeat_until == 'cond' && $cron_repeat_until_field != ""){
                if(!$evaluateLogic){
                    array_push($delete_queue,$index);
                }
            }
        }
        return $delete_queue;
    }

    /**
     * Function that adds an email alert to the queue
     * @param $alert, alert number
     * @param $project_id
     * @param $record
     * @param $event_id
     * @param $instrument
     * @param $instance
     * @param $isRepeatInstrument
     */
    function addQueuedEmail($alert, $project_id, $record, $event_id, $instrument, $instance, $isRepeatInstrument,$times_sent="",$last_sent=''){
        $queue = array();
        $queue['alert'] = $alert;
        $queue['record'] = $record;
        $queue['project_id'] = $project_id;
        $queue['event_id'] = $event_id;
        $queue['instrument'] = $instrument;
        $queue['instance'] = $instance;
        $queue['isRepeatInstrument'] = $isRepeatInstrument;

        $cron_send_email_on = $this->getProjectSetting("cron-send-email-on", $project_id)[$alert];
        $queue['option'] = $cron_send_email_on;
        $queue['deactivated'] = 0;
        $queue['times_sent'] = $times_sent;
        $queue['last_sent'] = $last_sent;

        $email_queue = empty($this->getProjectSetting('email-queue'))?array():$this->getProjectSetting('email-queue');
        array_push($email_queue,$queue);
        $this->setProjectSetting('email-queue', $email_queue);
    }

    /**
     * Function that deletes a specific queue
     * @param $index
     * @param $project_id
     */
    function deleteQueuedEmail($index, $project_id){
        $email_queue =  empty($this->getProjectSetting('email-queue',$project_id))?array():$this->getProjectSetting('email-queue',$project_id);
        if(is_array($index)){
            foreach ($index as $queue_index){
                unset($email_queue[$queue_index]);
            }
        }else if(is_numeric($index)){
            unset($email_queue[$index]);
        }

        $this->setProjectSetting('email-queue', $email_queue,$project_id);
    }

    /**
     * Function that sends a specific scheduled email from the queue
     * @param $project_id
     * @param $record
     * @param $id
     * @param $instrument
     * @param $instance
     * @param $isRepeatInstrument
     * @param $event_id
     * @return bool
     */
    function sendQueuedEmail($project_id, $record, $id, $instrument, $instance, $isRepeatInstrument, $event_id){
        $data = \REDCap::getData($project_id,"array",$record);
        $email_sent = $this->createAndSendEmail($data, $project_id, $record, $id, $instrument, $instance, $isRepeatInstrument, $event_id,true);
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
    function addQueueLog($pid,$action_description,$cron_send_email_on,$cron_send_email_on_field,$cron_repeat_email,$cron_repeat_for,$cron_repeat_until,$cron_repeat_until_field){
        #Add logs
        if($cron_send_email_on == "now"){
            $scheduled_email = "Send ".$cron_send_email_on."";
        }else if($cron_send_email_on == "date"){
            $scheduled_email = "Send on ".$cron_send_email_on;
        }else if($cron_send_email_on == "calc"){
            $scheduled_email = "Send on calculation";
        }
        if($cron_send_email_on_field != ""){
            $scheduled_email .= ": ".$cron_send_email_on_field."";
        }
        if($cron_repeat_email == "1"){
            $scheduled_email .= "\n Repeat";

            if($cron_repeat_for != ""){
                $scheduled_email .= " for ".$cron_repeat_for." days";
            }
            if($cron_repeat_until == "forever"){
                $scheduled_email .= " forever";
            }else if($cron_repeat_until == "cond"){
                $scheduled_email .= " until condition is met";
            }else if($cron_repeat_until == "date"){
                $scheduled_email .= " until ";
            }
            if($cron_repeat_until_field != ''){
                $scheduled_email .= $cron_repeat_until_field;
            }
        }

        $changes_made = $scheduled_email;
        \REDCap::logEvent($action_description,$changes_made,NULL,NULL,NULL,$pid);
    }

    /**
     * Function that creates and sends an email
     * @param $data
     * @param $project_id
     * @param $record
     * @param $id
     * @param $instrument
     * @param $instance
     * @param $isRepeatInstrument
     * @param $event_id
     * @param $isCron
     * @return bool
     */
    function createAndSendEmail($data, $project_id, $record, $id, $instrument, $instance, $isRepeatInstrument, $event_id,$isCron){
        //memory increase
        ini_set('memory_limit', '512M');

        $email_subject = $this->getProjectSetting("email-subject", $project_id)[$id];
        $email_text = $this->getProjectSetting("email-text", $project_id)[$id];
        $datapipe_var = $this->getProjectSetting("datapipe_var", $project_id);
        $alert_id = $this->getProjectSetting("alert-id", $project_id);

        $isLongitudinal = false;
        if($isCron){
            $sql = "SELECT count(e.event_id) as number_events FROM redcap_projects p, redcap_events_metadata e, redcap_events_arms a WHERE p.project_id='".$project_id."' AND p.repeatforms='1' AND a.arm_id = e.arm_id AND p.project_id=a.project_id";
            $result = $this->query($sql);
            if(!empty($row = db_fetch_assoc($result))) {
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
        $email_text = $this->setDataPiping($datapipe_var, $email_text, $project_id, $data, $record, $event_id, $instrument, $instance, $isLongitudinal);
        $email_subject = $this->setDataPiping($datapipe_var, $email_subject, $project_id, $data, $record, $event_id, $instrument, $instance, $isLongitudinal);

        #Survey and Data-Form Links
        $email_text = $this->setSurveyLink($email_text, $project_id, $record, $event_id, $isLongitudinal);
        $email_text = $this->setFormLink($email_text, $project_id, $record, $event_id, $isLongitudinal);

        $mail = new \PHPMailer;

        #Email Addresses
        $mail = $this->setEmailAddresses($mail, $project_id, $record, $event_id, $instrument, $instance, $data, $id, $isLongitudinal);

        #Email From
        $mail = $this->setFrom($mail, $project_id, $record, $id);

        #Embedded images
        $mail = $this->setEmbeddedImages($mail, $project_id, $email_text);

        $mail->CharSet = 'UTF-8';
        $mail->Subject = $email_subject;
        $mail->IsHTML(true);
        $mail->Body = $email_text;

        #Attachments
        $mail = $this->setAttachments($mail, $project_id, $id);

        #Attchment from RedCap variable
        $mail = $this->setAttachmentsREDCapVar($mail, $project_id, $data, $record, $event_id, $instrument, $instance, $id, $isLongitudinal);

        #DKIM to make sure the email does not go into spam folder
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

        if(empty($alert_id)){
            $alert_number = $id;
        }else{
            $alert_number = $alert_id[$id];
        }

        $email_sent_ok = false;
        if (!$mail->send()) {
            error_log("scheduledemails PID: ".$project_id." Mailer Error:".$mail->ErrorInfo);
            $this->sendFailedEmailRecipient($this->getProjectSetting("emailFailed_var", $project_id),"Mailer Error" ,"Mailer Error:".$mail->ErrorInfo." in Project: ".$project_id.", Record: ".$record." Alert #".$alert_number);
        }else{
            error_log("scheduledemails PID: ".$project_id." - Email was sent!");
            $email_sent = $this->getProjectSetting("email-sent",$project_id);
            $email_timestamp_sent = $this->getProjectSetting("email-timestamp-sent",$project_id);
            $email_repetitive_sent = json_decode($this->getProjectSetting("email-repetitive-sent",$project_id),true);
            $email_records_sent = $this->getProjectSetting("email-records-sent",$project_id);
            $email_sent_ok = true;

            $email_sent[$id] = "1";
            $email_timestamp_sent[$id] = date('Y-m-d H:i:s');

            $this->setProjectSetting('email-timestamp-sent', $email_timestamp_sent, $project_id);
            $this->setProjectSetting('email-sent', $email_sent, $project_id);

            $email_repetitive_sent = $this->addRecordSent($email_repetitive_sent,$record,$instrument,$id,$isRepeatInstrument,$instance,$event_id);
            $this->setProjectSetting('email-repetitive-sent', $email_repetitive_sent, $project_id);

            $email_repetitive_sent = json_decode($email_repetitive_sent,true);
            if($email_records_sent[$id] == ''){
                if(!empty($email_repetitive_sent[$instrument][$id])) {
                    foreach ($email_repetitive_sent[$instrument][$id] as $record_key => $record_id) {
                        if(is_array($record_id)){
                            foreach ($record_id as $survey_key => $survey){
                                $email_records_sent[$id] = $email_records_sent[$id].$survey_key.", ";
                                break;
                            }
                        }else{
                            $email_records_sent[$id] = $email_records_sent[$id].$record_id.", ";
                        }
                    }
                    $email_records_sent[$id] = rtrim($email_records_sent[$id],', ');
                    $this->setProjectSetting('email-records-sent', $email_records_sent, $project_id);
                }
            }

            $records = array_map('trim', explode(',', $email_records_sent[$id]));
            $record_found = false;
            foreach ($records as $record_id){
                if($record_id == $record){
                    $record_found = true;
                    break;
                }
            }

            if(!$record_found){
                if($email_records_sent[$id] == ''){
                    $email_records_sent[$id] = $record;
                }else{
                    $email_records_sent[$id] = $email_records_sent[$id].", ".$record;
                }
                $this->setProjectSetting('email-records-sent', $email_records_sent, $project_id);
            }

            #Add some logs
            $action_description = "Email Sent - Alert ".$alert_number;
            $changes_made = "[Subject]: ".$email_subject.", [Message]: ".$email_text;
            \REDCap::logEvent($action_description,$changes_made,NULL,$record,$event_id,$project_id);

            $action_description = "Email Sent To - Alert ".$alert_number;
            $email_list = '';
            foreach ($mail->getAllRecipientAddresses() as $email=>$value){
                $email_list .= $email.";";
            }
            \REDCap::logEvent($action_description,$email_list,NULL,$record,$event_id,$project_id);
        }
        unlink($privatekeyfile);

        #Clear all addresses and attachments for next loop
        $mail->clearAddresses();
        $mail->clearAttachments();
        return $email_sent_ok;
    }

    /**
     * Function that adds the email addresses into the mail.
     * @param $mail
     * @param $project_id
     * @param $record
     * @param $event_id
     * @param $instrument
     * @param $instance
     * @param $data
     * @param $id
     * @param bool $isLongitudinal
     * @return mixed
     */
    function setEmailAddresses($mail, $project_id, $record, $event_id, $instrument, $instance, $data, $id, $isLongitudinal=false){
        $datapipeEmail_var = $this->getProjectSetting("datapipeEmail_var", $project_id);
        $email_to = $this->getProjectSetting("email-to", $project_id)[$id];
        $email_cc = $this->getProjectSetting("email-cc", $project_id)[$id];
        $email_bcc = $this->getProjectSetting("email-bcc", $project_id)[$id];
        if (!empty($datapipeEmail_var)) {
            $email_form_var = explode("\n", $datapipeEmail_var);

            $emailsTo = preg_split("/[;,]+/", $email_to);
            $emailsCC = preg_split("/[;,]+/", $email_cc);
            $emailsBCC = preg_split("/[;,]+/", $email_bcc);
            $mail = $this->fill_emails($mail,$emailsTo, $email_form_var, $data, 'to',$project_id,$record, $event_id, $instrument, $instance, $isLongitudinal);
            $mail = $this->fill_emails($mail,$emailsCC, $email_form_var, $data, 'cc',$project_id,$record, $event_id, $instrument, $instance, $isLongitudinal);
            $mail = $this->fill_emails($mail,$emailsBCC, $email_form_var, $data, 'bcc',$project_id,$record, $event_id, $instrument, $instance, $isLongitudinal);
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
        return $mail;
    }

    /**
     * Function that adds the from email into the mail
     * @param $mail
     * @param $project_id
     * @param $record
     * @param $id
     * @return mixed
     */
    function setFrom($mail, $project_id, $record, $id){
        $email_from = $this->getProjectSetting("email-from", $project_id)[$id];
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
        return $mail;
    }

    /**
     * function that checks for the piped data, replaces it and returns the content
     * @param $datapipe_var
     * @param $email_content
     * @param $project_id
     * @param $data
     * @param $record
     * @param $event_id
     * @param $instrument
     * @param $instance
     * @param $isLongitudinal
     * @return mixed
     */
    function setDataPiping($datapipe_var, $email_content, $project_id, $data, $record, $event_id, $instrument, $instance, $isLongitudinal){
        if (!empty($datapipe_var)) {
            $datapipe = explode("\n", $datapipe_var);
            foreach ($datapipe as $emailvar) {
                $var = preg_split("/[;,]+/", $emailvar)[0];
                if (\LogicTester::isValid($var)) {
                    preg_match_all("/\\[(.*?)\\]/", $var, $matches);

                    $var_replace = $var;
                    //For arms and different events
                    if(count($matches[1]) > 1){
                        $project = new \Project($project_id);
                        $event_id = $project->getEventIdUsingUniqueEventName($matches[1][0]);
                        $var = $matches[1][1];
                    }

                    //Repeatable instruments
                    $logic = $this->isRepeatingInstrument($project_id, $data, $record, $event_id, $instrument, $instance, $var,0, $isLongitudinal);
                    $label = $this->getChoiceLabel(array('field_name'=>$var, 'value'=>$logic, 'project_id'=>$project_id, 'record_id'=>$record,'event_id'=>$event_id,'survey_form'=>$instrument,'instance'=>$instance));
                    if(!empty($label)){
                        $logic = $label;
                    }
                    $email_content = str_replace($var_replace, $logic, $email_content);
                }
            }
        }
        return $email_content;
    }

    /**
     * Function that adds the data form link into the mail content
     * @param $email_text
     * @param $project_id
     * @param $record
     * @param $event_id
     * @param $isLongitudinal
     * @return mixed
     */
    function setFormLink($email_text, $project_id, $record, $event_id, $isLongitudinal){
        $formLink_var = $this->getProjectSetting("formLink_var", $project_id);
        if(!empty($formLink_var)) {
            $dataform = explode("\n", $formLink_var);
            foreach ($dataform as $formlink) {
                $var = preg_split("/[;,]+/", $formlink)[0];

                $instance = 1;
                $form_event_id = $event_id;
                if($isLongitudinal) {
                    preg_match_all("/\[[^\]]*\]/", $var, $matches);
                    $var = "";
                    $ev = "";
                    $smartInstance = "";
                    if (sizeof($matches[0]) > 2) {
                        $var = $matches[0][1];
                        $ev = $matches[0][0];
                        $smartInstance = $matches[0][2];
                    }
                    if (sizeof($matches[0]) == 2) {
                        $smarts = array("[new-instance]", "[last-instance]", "[first-instance]");
                        if (in_array($matches[0][1], $smarts)) {
                             $var = $matches[0][0];
                             $smartInstance = $matches[0][1];
                        } else {
                             $var = $matches[0][1];
                             $ev = $matches[0][0];
                        }
                    }
                    if (sizeof($matches[0]) == 1) {
                        $var = $matches[0][0];
                    }
                    if ($ev) {
                        $form_name = str_replace('[', '', $matches[0][0]);
                        $form_name = str_replace(']', '', $form_name);
                        $project = new \Project($project_id);
                        $form_event_id = $project->getEventIdUsingUniqueEventName($form_name);
                    }
                    if (count($matches[0]) > 2) {
                        $instanceMin = 1;
                        $sql = "SELECT DISTINCT(instance) AS instance FROM redcap_data WHERE project_id = $project_id AND record = '".db_real_escape_string($record)."' ORDER BY instance DESC";
                        $q = db_query($sql);
                        $instanceMax = 1;
                        $instanceNew = 1;
                        if ($row = db_fetch_assoc($q)) {
                            $instanceMax = $row['instance'];
                            $instanceNew = $instanceMax + 1;
                        }

                        if ($smartInstance == '[new-instance]') {
                            $instance = $instanceNew;
                        }
                        else if ($smartInstance == '[last-instance]') {
                            $instance = $instanceMax;
                        }
                        else if ($smartInstance == '[first-instance]') {
                            $instance = $instanceMin;
                        }
                    }
                }

                if (strpos($email_text, $var) !== false) {
                    $instrument_form = str_replace('[__FORMLINK_', '', $var);
                    $instrument_form = str_replace(']', '', $instrument_form);

                    # get rid of extra /'s
                    $dir = preg_replace("/\/$/", "", APP_PATH_WEBROOT_FULL.preg_replace("/^\//", "", APP_PATH_WEBROOT));
                    if ( preg_match("/redcap_v[\d\.]+/", APP_PATH_WEBROOT, $matches)) {
                        $dir = APP_PATH_WEBROOT_FULL.$matches[0];
                    }
                    $url = $dir . "/DataEntry/index.php?pid=".$project_id."&event_id=".$form_event_id."&page=".$instrument_form."&id=".$record."&instance=".$instance;
                    $link = "<a href='" . $url . "' target='_blank'>" . $url . "</a>";
                    $email_text = str_replace( preg_split("/[;,]+/", $formlink)[0], $link, $email_text);
                }
            }
        }
        return $email_text;
    }

    /**
     * Function that adds the survey link into the mail content
     * @param $email_text
     * @param $project_id
     * @param $record
     * @param $event_id
     * @param $isLongitudinal
     * @return mixed
     */
    function setSurveyLink($email_text, $project_id, $record, $event_id, $isLongitudinal){
        $surveyLink_var = $this->getProjectSetting("surveyLink_var", $project_id);
        if(!empty($surveyLink_var)) {
            $datasurvey = explode("\n", $surveyLink_var);
            foreach ($datasurvey as $surveylink) {
                $var = preg_split("/[;,]+/", $surveylink)[0];
                $var_replace = $var;
                $form_event_id = $event_id;

                preg_match_all("/\\[(.*?)\\]/", $var, $matches);
                //For arms and different events
                if(count($matches[1]) > 1){
                    $project = new \Project($project_id);
                    $form_event_id = $project->getEventIdUsingUniqueEventName($matches[1][0]);
                    $var = $matches[1][1];
                }

                #only if the variable is in the text we reset the survey link status
                if (strpos($email_text, $var_replace) !== false) {
                    $instrument_form = str_replace('__SURVEYLINK_', '', $var);
					$instrument_form = str_replace(['[',']'], '', $instrument_form);
					$passthruData = $this->resetSurveyAndGetCodes($project_id, $record, $instrument_form, $form_event_id);

                    $returnCode = $passthruData['return_code'];
                    $hash = $passthruData['hash'];

					## getUrl doesn't append a pid when accessed through the cron, add pid if it's not there already
					$baseUrl = $this->getUrl('surveyPassthru.php');
					if(!preg_match("/[\&\?]pid=/", $baseUrl)) {
						$baseUrl .= "&pid=".$project_id;
					}

                    $url = $baseUrl . "&instrument=" . $instrument_form . "&record=" . $record . "&returnCode=" . $returnCode."&event=".$form_event_id."&NOAUTH";
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
     * @param $project_id
     * @param $id
     * @return mixed
     * @throws \Exception
     */
    function setAttachments($mail, $project_id, $id){
        for($i=1; $i<6 ; $i++){
            $edoc = $this->getProjectSetting("email-attachment".$i,$project_id)[$id];
            if(is_numeric($edoc)){
                $mail = $this->addNewAttachment($mail,$edoc,$project_id,'files');
            }
        }
        return $mail;
    }

    /**
     * Function that adds piped attachments into the mail
     * @param $mail
     * @param $project_id
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
    function setAttachmentsREDCapVar($mail,$project_id,$data, $record, $event_id, $instrument, $repeat_instance, $id, $isLongitudinal=false){
        $email_attachment_variable = $this->getProjectSetting("email-attachment-variable", $project_id)[$id];
        if(!empty($email_attachment_variable)){
            $var = preg_split("/[;,]+/", $email_attachment_variable);
            foreach ($var as $attachment) {
                if(\LogicTester::isValid(trim($attachment))) {
                    $edoc = $this->isRepeatingInstrument($project_id,$data, $record, $event_id, $instrument, $repeat_instance, $attachment,0, $isLongitudinal);
                    if(is_numeric($edoc)) {
                        $this->addNewAttachment($mail, $edoc, $project_id, 'files');
                    }
                }
            }
        }
        return $mail;
    }

    /**
     * Function that attaches images into the mail
     * @param $mail
     * @param $project_id
     * @param $email_text
     * @return mixed
     * @throws \Exception
     */
    function setEmbeddedImages($mail,$project_id,$email_text){
        preg_match_all('/src=[\"\'](.+?)[\"\'].*?/i',$email_text, $result);
        $result = array_unique($result[1]);
        foreach ($result as $img_src){
            preg_match_all('/(?<=file=)\\s*([0-9]+)\\s*/',$img_src, $result_img);
            $edoc = array_unique($result_img[1])[0];
            if(is_numeric($edoc)){
                $mail = $this->addNewAttachment($mail,$edoc,$project_id,'images');

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
    function isEmailAlreadySentForThisSurvery($project_id,$email_repetitive_sent, $email_records_sent,$event_id, $record, $instrument, $alertid,$isRepeatInstrument,$repeat_instance){
        $isLongitudinal = \REDCap::isLongitudinal();
//    $email_repetitive_sent = $this->addRecordSent($email_repetitive_sent,$record,$instrument,$alertid,$isRepeatInstrument,$repeat_instance,$event_id);
//    echo "AFTER PRINT<br>";
//    print_array(json_decode($email_repetitive_sent,true));
//        $this->setProjectSetting('email-repetitive-sent', $email_repetitive_sent, $project_id);


//        $alertid = 9;
//        $record = 10;
//        $event_id = 125;
//        $isLongitudinal = false;
//        $isRepeatInstrument = true;
//        $repeat_instance = 1;
//        $instrument = "my_first_instrument_2";

        echo "Alert: ".$alertid."<br>";
        echo "Record: ".$record."<br>";
        echo "Instrument: ".$instrument."<br>";
        echo "Instance: ".$repeat_instance."<br>";
        echo "Event_id: ".$event_id."<br>";
        echo "isRepeatInstrument: ".$isRepeatInstrument."<br>";
        echo "isLongitudinal: ".$isLongitudinal."<br>";

        print_array($email_repetitive_sent);
        if(!empty($email_repetitive_sent)){
            if(array_key_exists($instrument,$email_repetitive_sent)){
                if(array_key_exists($alertid,$email_repetitive_sent[$instrument])){
                    if($isRepeatInstrument){
                        echo "__isRepeatInstrument found!<br>";
                        if(array_key_exists('repeat_instances', $email_repetitive_sent[$instrument][$alertid])){
                            if(array_key_exists($record, $email_repetitive_sent[$instrument][$alertid]['repeat_instances']) && array_key_exists($event_id, $email_repetitive_sent[$instrument][$alertid]['repeat_instances'][$record])){
                                if(in_array($repeat_instance, $email_repetitive_sent[$instrument][$alertid]['repeat_instances'][$record][$event_id])){
                                    return true;
                                }
                            }
                        }
                        if(array_key_exists($record, $email_repetitive_sent[$instrument][$alertid])){
                            if(array_key_exists($event_id, $email_repetitive_sent[$instrument][$alertid][$record])){
                                echo "__Record found4!<br>";
                                if(in_array($repeat_instance,$email_repetitive_sent[$instrument][$alertid][$record][$event_id])){
                                    return true;
                                }
                            }
                        }

                    }else{
                        echo "__NOT RepeatInstrument found!<br>";
                        if(array_key_exists('repeat_instances', $email_repetitive_sent[$instrument][$alertid])){
                            echo "__repeat found!<br>";
                            #In case they have changed the project to non repeatable
                            if(array_key_exists($event_id, $email_repetitive_sent[$instrument][$alertid]['repeat_instances'][$record])){
                                echo "__event found!<br>";
                                if(in_array($repeat_instance,  $email_repetitive_sent[$instrument][$alertid]['repeat_instances'][$record][$event_id])){
                                    return true;
                                }
                            }
                        }
                        if(array_key_exists($record, $email_repetitive_sent[$instrument][$alertid])){
                            echo "__Record found!<br>";
                            if(array_key_exists($event_id, $email_repetitive_sent[$instrument][$alertid][$record])){
                                return true;
                            }
                        }

                    }
                }
            }
        }
        return false;
    }

    function recordExistsInRegisteredRecords($email_records_sent,$record){
        $records_registered = array_map('trim', explode(',', $email_records_sent));
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
    function isRepeatingInstrument($project_id,$data, $record, $event_id, $instrument, $repeat_instance, $var, $option=null, $isLongitudinal=false){
        $var_name = str_replace('[', '', $var);
        $var_name = str_replace(']', '', $var_name);
        if(array_key_exists('repeat_instances',$data[$record]) && $data[$record]['repeat_instances'][$event_id][$instrument][$repeat_instance][$var_name] != "") {
            #Repeating instruments by form
            $logic = $data[$record]['repeat_instances'][$event_id][$instrument][$repeat_instance][$var_name];
        }else if(array_key_exists('repeat_instances',$data[$record]) && $data[$record]['repeat_instances'][$event_id][''][$repeat_instance][$var_name] != "") {
            #Repeating instruments by event
            $logic = $data[$record]['repeat_instances'][$event_id][''][$repeat_instance][$var_name];
        }else{
            $project = new \Project($project_id);
            if($option == '1'){
                if($isLongitudinal && \LogicTester::apply($var, $data[$record], $project, true) == ""){
                    $logic = $data[$record][$event_id][$var_name];
                }else{
                    $logic = \LogicTester::apply($var, $data[$record], $project, true);
                }
            }else{
                if($isLongitudinal && \LogicTester::apply($var, $data[$record], $project, true) == ""){
                    $logic = $data[$record][$event_id][$var_name];
                }else{
                    preg_match_all("/\[[^\]]*\]/", $var, $matches);
                    #Special case for radio buttons
                    if(sizeof($matches[0]) == 1 && \REDCap::getDataDictionary($project_id,'array',false,$var_name)[$var_name]['field_type'] == "radio"){
                        $logic = $data[$record][$event_id][$var_name];
                    }else{
                        $logic = \LogicTester::apply($var, $data[$record], $project, true);
                    }
                }
            }
        }
        return $logic;
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
    function fill_emails($mail, $emailsTo, $email_form_var, $data, $option, $project_id, $record, $event_id, $instrument, $repeat_instance, $isLongitudinal=false){
        foreach ($emailsTo as $email){
            foreach ($email_form_var as $email_var) {
                $var = preg_split("/[;,]+/", $email_var);
                if(!empty($email)) {
                    if (\LogicTester::isValid($var[0])) {
                       $email_redcap = $this->isRepeatingInstrument($project_id,$data, $record, $event_id, $instrument, $repeat_instance, $var[0],1,$isLongitudinal);
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
    function addRecordSent($email_repetitive_sent, $new_record, $instrument, $alertid,$isRepeatInstrument,$repeat_instance,$event_id){

//        $alertid = 9;
//        $new_record = 10;
//        $event_id = 125;
//        $isLongitudinal = false;
//        $isRepeatInstrument = true;
//        $repeat_instance = 1;
//        $instrument = "my_first_instrument_2";

        echo "Alert: ".$alertid."<br>";
        echo "Record: ".$new_record."<br>";
        echo "Instrument: ".$instrument."<br>";
        echo "Instance: ".$repeat_instance."<br>";
        echo "Event_id: ".$event_id."<br>";
        echo "isRepeatInstrument: ".$isRepeatInstrument."<br>";
//        echo "isLongitudinal: ".$isLongitudinal."<br>";
        print_array($email_repetitive_sent);
//        $this->setProjectSetting('email-repetitive-sent', json_encode($email_repetitive_sent), 86);

        $email_repetitive_sent_aux = $email_repetitive_sent;
        if(!empty($email_repetitive_sent)) {
            $found_new_instrument = true;
            foreach ($email_repetitive_sent as $sv_name => $survey_records) {
                if($sv_name == $instrument) {
                    $found_new_instrument = false;
                    $found_new_alert = true;
                    echo "__Instrument found<br>";
                    foreach ($survey_records as $alert => $alert_value) {
                        if ($alert == $alertid) {
                            echo "__Alert found<br>";
                            $found_new_alert = false;
                            $found_new_record = true;
                            $found_is_repeat = false;
                            if(!empty($alert_value)){
                                foreach ($alert_value as $sv_number => $survey_record) {
                                    if ($sv_number === "repeat_instances") {
                                        echo "__Repeat_instances found<br>";
                                        $found_is_repeat = true;
                                        if($isRepeatInstrument){
                                            foreach ($alert_value['repeat_instances'] as $survey_record_repeat =>$survey_instances){
                                                return  $this->addArrayInfo(true,$email_repetitive_sent_aux,$instrument,$alertid,$new_record, $repeat_instance,$event_id);
                                            }
                                        }
                                    }else if($sv_number == $new_record){
                                        echo "__Record found<br>";
                                        $found_new_record = false;
                                        if($isRepeatInstrument){
                                            return  $this->addArrayInfo(true,$email_repetitive_sent_aux,$instrument,$alertid,$new_record, $repeat_instance,$event_id);
                                        }else{
                                            if(is_array($survey_record)){
                                                return  $this->addArrayInfo(false,$email_repetitive_sent_aux,$instrument,$alertid,$new_record, $repeat_instance,$event_id);
                                            }else{
                                                $event_array = array($event_id => $repeat_instance);
                                                $email_repetitive_sent_aux[$instrument][$alertid][$new_record] = $event_array;
                                                return json_encode($email_repetitive_sent_aux);
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
                echo "__NEW Instrument<br>";
                return $this->addJSONInfo($isRepeatInstrument,$email_repetitive_sent_aux,$instrument,$alertid,$new_record, $repeat_instance,$event_id,false);
            }else if($found_new_alert){
                echo "__NEW Alert<br>";
                return $this->addJSONInfo($isRepeatInstrument,$email_repetitive_sent_aux,$instrument,$alertid,$new_record, $repeat_instance,$event_id,false);
            }else if($found_new_record){
                echo "__NEW Record<br>";
                return $this->addJSONInfo($isRepeatInstrument,$email_repetitive_sent_aux,$instrument,$alertid,$new_record, $repeat_instance,$event_id,$found_is_repeat);
            }else if(!$found_new_record && !$found_is_repeat){
                return $this->addJSONInfo($isRepeatInstrument,$email_repetitive_sent_aux,$instrument,$alertid,$new_record, $repeat_instance,$event_id,true);
            }
            return json_encode($email_repetitive_sent_aux);
        }else{
            return $this->addJSONInfo($isRepeatInstrument,$email_repetitive_sent_aux,$instrument,$alertid,$new_record, $repeat_instance,$event_id,false);
        }

    }

    function addJSONInfo($isRepeatInstrument,$email_repetitive_sent_aux,$instrument,$alertid,$new_record, $repeat_instance,$event_id, $found_is_repeat){
        if($isRepeatInstrument){
            #NEW REPEAT INSTANCE
            if(!$found_is_repeat){
                $email_repetitive_sent_aux = $this->addArrayInfo(true,$email_repetitive_sent_aux,$instrument,$alertid,$new_record, $repeat_instance,$event_id);
            }else{
                $email_repetitive_sent_aux = $this->addArrayInfo(false,$email_repetitive_sent_aux,$instrument,$alertid,$new_record, $repeat_instance,$event_id);
            }
        }else{
            $email_repetitive_sent_aux = $this->addArrayInfo(false,$email_repetitive_sent_aux,$instrument,$alertid,$new_record, $repeat_instance,$event_id);
        }
        return $email_repetitive_sent_aux;
    }

    function addArrayInfo($addRepeat,$email_repetitive_sent_aux,$instrument,$alertid,$new_record, $repeat_instance,$event_id){
        if($addRepeat){
            if(!is_array($email_repetitive_sent_aux[$instrument][$alertid]['repeat_instances'][$new_record][$event_id])){
                $email_repetitive_sent_aux[$instrument][$alertid]['repeat_instances'][$new_record][$event_id] = array();
            }
            array_push($email_repetitive_sent_aux[$instrument][$alertid]['repeat_instances'][$new_record][$event_id],$repeat_instance);
        }else{
            if(!is_array($email_repetitive_sent_aux[$instrument][$alertid][$new_record][$event_id])){
                $email_repetitive_sent_aux[$instrument][$alertid][$new_record][$event_id] = array();
            }
            array_push($email_repetitive_sent_aux[$instrument][$alertid][$new_record][$event_id],$repeat_instance);
        }
        return json_encode($email_repetitive_sent_aux);
    }

    /*function addJSONRecord($email_repetitive_sent, $new_record, $instrument, $alertid,$isRepeatInstrument,$repeat_instance){
        $found_new_instrument = true;
        $email_repetitive_sent_aux = $email_repetitive_sent;
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
                    $exists_repeatinstance = false;
                    foreach ($alert_value as $sv_number => $survey_record){
                        if($isRepeatInstrument){
                            if ($sv_number === "repeat_instances") {
                                $exists_repeatinstance = true;
                                foreach ($alert_value['repeat_instances'] as $survey_record_repeat =>$survey_instances){
                                    $jsonVarArray[$instrument][$alertid]['repeat_instances'][$new_record] = array();
                                    if($survey_record_repeat == $new_record){
                                        $found_record = true;
                                    }

                                    $found_instance = false;
                                    foreach ($survey_instances as $index =>$instance){
                                        array_push($jsonVarArray[$instrument][$alertid]['repeat_instances'][$new_record], $instance);
                                        if($instance == $repeat_instance){
                                            $found_instance = true;
                                        }
                                    }
                                }
                            }else {
                                array_push($jsonVarArray[$instrument][$alertid],$survey_record);
                            }

                        }else{
                            if ($sv_number === "repeat_instances") {
                                echo "REPEAT<br>";
                                $jsonVarArray[$instrument][$alert]['repeat_instances'] = $email_repetitive_sent_aux[$instrument][$alert]['repeat_instances'];
                            }else{
                                array_push($jsonVarArray,$survey_record);
                            }

                            if($survey_record == $new_record){
                                $found_record = true;
                            }


//                            print_array($jsonVarArray);
                        }
                    }
                    print_array($jsonVarArray);

                    if($isRepeatInstrument){
                        if($sv_name == $instrument && $alert == $alertid) {
                            if($found_record){
                                #If it's the same survey, alert,record and a new instance, we add it
                                if(!$found_instance){
                                    array_push($jsonVarArray[$instrument][$alertid]['repeat_instances'][$new_record], $repeat_instance);
                                }
                                $jsonArray = $jsonVarArray;
                            }else if(!$exists_repeatinstance){
                                $jsonVarArray[$instrument] = $survey_records;
                                $jsonVarArray[$instrument][$alertid]['repeat_instances'][$new_record] = array();
                                array_push($jsonVarArray[$instrument][$alertid]['repeat_instances'][$new_record], $repeat_instance);
                                $jsonArray = $jsonVarArray;

                            }
                        }
                    }else{
                        #If it's the same survey, alert and a new record, we add it
                        if($sv_name == $instrument && $alert == $alertid && !$found_record){
                            #add new record for specific instrument
                            array_push($jsonVarArray, $new_record);
                            $jsonArray[$sv_name][$alert] = $jsonVarArray;
                        }
                    }
                    print_array($jsonVarArray);
                    echo "__________<br>";
                }

                if($sv_name == $instrument){
                    $found_new_instrument = false;
                }

                #NEW Alert same instrument
                if(!$found_alert && $sv_name == $instrument){
                    $jsonArray = $this->addNewJSONRecord($jsonArray,$sv_name,$alertid,$new_record,$isRepeatInstrument,$repeat_instance);
                }
            }
        }else{
            $jsonArray = $this->addNewJSONRecord([],$instrument,$alertid,$new_record,$isRepeatInstrument,$repeat_instance);
        }

        #add new record for new survey
        if($found_new_instrument){
            $jsonArray = $this->addNewJSONRecord($jsonArray,$instrument,$alertid,$new_record,$isRepeatInstrument,$repeat_instance);
        }
        return json_encode($jsonArray,JSON_FORCE_OBJECT);
    }*/

    /**
     * Function that adds a new record in the JSON
     * @param $jsonArray
     * @param $instrument
     * @param $alertid
     * @param $new_record
     * @return mixed
     */
    /*function addNewJSONRecord($jsonArray, $instrument, $alertid, $new_record,$isRepeatInstrument,$repeat_instance){
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
    }*/
}



