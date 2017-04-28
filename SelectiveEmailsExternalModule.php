<?php
namespace ExternalModules;
require_once dirname(__FILE__) . '/../../external_modules/classes/ExternalModules.php';
require_once APP_PATH_DOCROOT. '/Classes/LogicTester.php';

class SelectiveEmailsExternalModule extends AbstractExternalModule
{
	function hook_save_record ($project_id,$record = NULL,$instrument,$event_id)
	{
		if(isset($project_id)){
            $sql = "SELECT d.field_name, d.event_id, d.value
                    FROM redcap_data d
                    WHERE d.project_id = $project_id
                    AND d.record = '$record'";
            $q = db_query($sql);
            $data = array($record => array());
            while ($row = db_fetch_assoc($q)) {
                $myEventId = $row['event_id'];
                if (!isset($data[$record][$myEventId])) {
                    $data[$record][$myEventId] = array();
                }
                $data[$record][$myEventId][$row['field_name']] = $row['value'];
            }

            $events = array();
            foreach ($data[$record] as $evID => $evData) {
                $events[] = $evID;
            }

            $sql = "SELECT m.field_name, f.event_id
                    FROM redcap_metadata m, redcap_events_forms f
                    WHERE m.project_id = $project_id
                    AND m.form_name = f.form_name
                    AND f.event_id IN (".implode(", ", $events).");";
            $q = db_query($sql);
            while ($row = db_fetch_assoc($q)) {
                $myEventId = $row['event_id'];
                if (!$data[$record][$myEventId]) {
                    $data[$record][$myEventId] = array();
                }
                $myValue = "";
                if (!isset($data[$record][$myEventId][$row['field_name']])) {
                    $data[$record][$myEventId][$row['field_name']] = $myValue;
                }
            }

			#Form Complete
			$forms_name = $this->getProjectSetting("form-name",$project_id) ;
			if(!empty($forms_name)){
                $dag = array();
                $user = array();
                $userRole = array();

                $numDAGs = 25;
                $numUsers = 10;
                $numRoles = 15;
                for ($i=0; $i < $numDAGs; $i++) {
				    $dag[] = $this->getProjectSetting("dag-".($i+1),$project_id);
                }
                for ($i=0; $i < $numUsers; $i++) {
				    $user[] = $this->getProjectSetting("user-".($i+1),$project_id);
                }
                for ($i=0; $i < $numRoles; $i++) {
				    $userRole[] = $this->getProjectSetting("user-role-".($i+1),$project_id);
                }

				$logic = $this->getProjectSetting("logic",$project_id);
                $isAll = $this->getProjectSetting("any-or-all",$project_id);
				$subject = $this->getProjectSetting("email-subject",$project_id);
				$email_text = $this->getProjectSetting("email-text",$project_id);
				$num_forms = count($forms_name);

                $dag = changeFormat($dag);
                $user = changeFormat($user);
                $userRole = changeFormat($userRole);

				if(!is_array($forms_name)) {
					$logic = array($logic);
					$num_forms = 1;
				}
				for ($i = 0; $i<$num_forms;$i++) {
					if ($instrument == $forms_name[$i]) {
					    $dagEmails = getDAGEmails($dag[$i], $project_id);
					    $userEmails = getUserEmails($user[$i], $project_id);
					    $roleEmails = getRoleEmails($userRole[$i], $project_id);

                        if ($isAll[$i] == "1") {
                            # all - in both DAG and role
					        $emailsOrig = array_unique($userEmails);
                            foreach ($dagEmails as $e) {
                                if (in_array($e, $roleEmails) && !in_array($e, $emailsOrig)) {
                                    $emailsOrig[] = $e;
                                }
                            }
                        } else {
                            #any
					        $emailsOrig = array_unique(array_merge($dagEmails, $userEmails, $roleEmails));
                        }
                        $emails = array();
                        foreach ($emailsOrig as $email) {
                            if ($email) {
                                $emails[] = $email;
                            }
                        }

				    	if (count($emails) > 0) {
							//we check the emails
							$email_list = check_email(implode(",", $emails), $project_id);
							if ($logic[$i] !== "") {
								if (\LogicTester::isValid($logic[$i])) {
                                    $evID = findEventIDForForm($forms_name[$i]);
                                    $evData = array($evID => $data[$record][$evID]);

                                    \REDCap::allowProjects(array($project_id));
                                    $projectTitle = \REDCap::getProjectTitle();
									$conditionValue = \LogicTester::apply($logic[$i], $evData, null, false);
                                    $specificText = "\n\nProject $projectTitle\nRecord $record\n".APP_PATH_WEBROOT_FULL.substr(APP_PATH_WEBROOT, 1)."/DataEntry/index.php?pid=$project_id&page=$instrument&id=$record";
									if ($conditionValue) {
										\REDCap::email($email_list, 'noreply@vanderbilt.edu', $subject[$i], $email_text[$i].$specificText);
                                    }
                                }
							} else {
								\REDCap::email($email_list, 'noreply@vanderbilt.edu', $subject[$i], $email_text[$i]);
							}
                        }
					}
				}
			}
		}
	}
}

# parameter is an instrument name
# returns the first event name that that form exists in
# returns -1 if nothing found
function findEventIDForForm($instrument) {
    $uniqueEventNames = \REDCap::getEventNames(true);
    $evIds = array();
    foreach ($uniqueEventNames as $uniqueEventName) {
        $evIds[] = \REDCap::getEventIdFromUniqueEvent($uniqueEventName);
    }

    $sql = "SELECT event_id
                FROM redcap_events_forms 
                WHERE (form_name = '".db_real_escape_string($instrument)."')
                AND (event_id IN (".implode(", ", $evIds)."));";
    $result = db_query($sql);

    $events = array();
	while ($row = db_fetch_assoc($result)) {
		$events[] = $row['event_id'];
	}

    if (empty($events)) {
        return $sql." ".db_error();
    }
    return $events[0];
}

function changeFormat($sourceArray) {
    $i = 0;
    $destArray= array();
    if (count($sourceArray) > 0) {
        foreach ($sourceArray[0] as $role) {
            $destArray[] = array();
            if ($sourceArray[0][$i] !== "") {
                $destArray[$i][] = $sourceArray[0][$i];
            }
            if ($userRole2[$i] !== "") {
                $destArray[$i][] = $sourceArray[1][$i];
            }
            if ($userRole3[$i] !== "") {
                $destArray[$i][] = $sourceArray[2][$i];
            }
            if ($userRole4[$i] !== "") {
                $destArray[$i][] = $sourceArray[3][$i];
            }
            if ($userRole5[$i] !== "") {
                $destArray[$i][] = $sourceArray[4][$i];
            }
            if ($userRole6[$i] !== "") {
                $destArray[$i][] = $sourceArray[5][$i];
            }
            $i++;
        }
    }
    return $destArray;
}

/* Function that gets the emails in a DAG and returns them as an array */
function getDAGEmails($dagIds, $project_id) {
	if (!is_array($dagIds)) {
		$dagIds = array($dagIds);
	}
	$filteredDAGIds = array();
	foreach ($dagIds as $dag) {
		if ($dag != "") {
			$filteredDAGIds[] = $dag;
		}
	}
    if (empty($filteredDAGIds)) {
        return array();
    }
	$sql = "SELECT ui.user_email
		FROM redcap_user_rights ur, redcap_user_information ui
		WHERE ur.username = ui.username
			AND ur.project_id = ".db_real_escape_string($project_id)."
			AND ur.group_id IN (".db_real_escape_string(implode(",", $filteredDAGIds)).")";
	$result = db_query($sql);
	$emails = array();
	while ($row = db_fetch_assoc($result)) {
		$emails[] = $row['user_email'];
	}
	return $emails;
}

/* Function that gets the email of an array of usernames */
function getUserEmails($usernames, $project_id) {
	if (!is_array($usernames)) {
		$usernames = array($usernames);
	}
	$filteredUsernames = array();
	foreach ($usernames as $name) {
		if ($name != "") {
			$filteredUsernames[] = db_real_escape_string($name);
		}
	}
    if (empty($filteredUsernames)) {
        return array();
    }
	$sql = "SELECT ui.user_email
		FROM redcap_user_information ui
		WHERE ui.username IN ('".implode("','", $filteredUsernames)."')";
	$result = db_query($sql);
	$emails = array();
	while ($row = db_fetch_assoc($result)) {
		$emails[] = $row['user_email'];
	}
	return $emails;
}

/* Function that gets the emails in a given role_id and returns them as an array */
function getRoleEmails($roleIds, $project_id) {
	if (!is_array($roleIds)) {
		$roleIds = array($roleIds);
	}
	$filteredRoles = array();
	foreach ($roleIds as $role) {
		if ($role != "") {
			$filteredRoles[] = $role;
		}
	}
    if (empty($filteredRoles)) {
        return array();
    }
	$sql = "SELECT ui.user_email
		FROM redcap_user_information ui, redcap_user_rights ur
		WHERE ui.username = ur.username
			AND ur.project_id = ".db_real_escape_string($project_id)."
			AND ur.role_id IN (".db_real_escape_string(implode(",", $filteredRoles)).")";
	$result = db_query($sql);
	$emails = array();
	while ($row = db_fetch_assoc($result)) {
		$emails[] = $row['user_email'];
	}
	return $emails;
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
