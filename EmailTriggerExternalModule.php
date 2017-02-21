<?php
namespace ExternalModules;
require_once dirname(__FILE__) . '/../../external_modules/classes/ExternalModules.php';

class EmailTriggerExternalModule extends AbstractExternalModule
{



	function hook_save_record ($project_id,$record = NULL,$instrument,$event_id)
	{
		$data = \REDCap::getData($project_id);
		if(isset($project_id)){
			#Form Complete
			if($data[$record][$event_id]['my_first_instrument_complete'] == '2'){
				$forms_name = $this->getProjectSetting("form-name",$project_id) ;
				if(!empty($forms_name)){
					$email = $this->getProjectSetting("email",$project_id) ;
					$subject = $this->getProjectSetting("email-subject",$project_id) ;
					$email_text = $this->getProjectSetting("email-text",$project_id) ;
                    $num_forms = count($forms_name);
					if(is_array($forms_name)) {
						for ($i = 0; $i<$num_forms;$i++) {
						    //we check the emails
						    $email_list = check_email ($email[$i],$project_id);
							if ($_REQUEST['page'] == $forms_name[$i]) {
								\REDCap::email($email_list, 'noreply@vanderbilt.edu', $subject[$i], $email_text[$i]);
							}
						}
					}else if ($_REQUEST['page'] == $forms_name) {
                        //we check the emails
                        $email_list = check_email ($email,$project_id);
						\REDCap::email($email, 'noreply@vanderbilt.edu', $subject, $email_text);
					}
				}
			}
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
        if(filter_var($email, FILTER_VALIDATE_EMAIL)) {
            //VALID
            array_push($email_list,$email);
        }else{
            array_push($email_list_error,$email);

        }
    }
    if(!empty($email_list_error)){
        //if error send email to datacore@vanderbilt.edu
        \REDCap::email('datacore@vanderbilt.edu', 'noreply@vanderbilt.edu', "Wrong recipient", "The email/s "+implode(",",$email_list_error)+" in the project "+$project_id+", do not exist");
    }
    $email_list = implode(",",$email_list);
    return $email_list;
}
