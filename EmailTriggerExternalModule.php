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
				$forms = $this->getProjectSetting("email-form",$project_id) ;
				if(!empty($forms)){
					$email = $this->getProjectSetting("email",$project_id) ;
					$subject = $this->getProjectSetting("email-subject",$project_id) ;
					$email_text = $this->getProjectSetting("email-text",$project_id) ;
					if(is_array($forms)) {
						foreach ($forms as $form) {
							if ($_REQUEST['page'] == $form) {
								\REDCap::email($email, 'noreply@vanderbilt.edu', $subject, $email_text);
								break;
							}
						}
					}else if ($_REQUEST['page'] == $forms) {
						\REDCap::email($email, 'noreply@vanderbilt.edu', $subject, $email_text);
					}
				}
			}
		}
	}
}
