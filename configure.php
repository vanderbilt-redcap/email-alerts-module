<?php
	namespace ExternalModules;
	require_once dirname(__FILE__) . '/../../external_modules/classes/ExternalModules.php';
	require_once APP_PATH_DOCROOT.'Classes/LogicTester.php';

	$prefix = "vanderbilt_selectiveEmails";
	$saved = array();
	$unsaved = array();
    $checkAlerts = array();
	if (isset($_POST['configure'])) {
		function validateLogic($logic) {
			return \LogicTester::isValid($logic);
		}

        $checks = array();
        $others = array();
		foreach ($_POST as $key => $value) {
            if ($value && preg_match("/^CHK~~~/", $key)) {
                $nodes = preg_split("/~~~/", $key);
                if (count($nodes) >= 4) {
                    $type = $nodes[1];
                    $checkValue = $nodes[2];
                    $instance = intval($nodes[3]);
                    if (!isset($checks[$type])) {
                        $checks[$type] = array(
                                                "checks" => array(),
                                                "keys" => array()
                                                );
                    }
                    if (!isset($checks[$type]["checks"][$instance])) {
                        $checks[$type]["checks"][$instance] = array();
                    }
                    $checks[$type]["checks"][$instance][] = $checkValue;
                }
            } else if ($value && preg_match("/^KEY~~~/", $key)) {
                $nodes = preg_split("/~~~/", $key);
                if (count($nodes) >= 3) {
                    $type = $nodes[1];
                    $associatedKey= $nodes[2];
                    $instance = intval($nodes[3]);
                    if (!isset($checks[$type])) {
                        $checks[$type] = array(
                                                "checks" => array(),
                                                "keys" => array()
                                                );
                    }
                    if (!isset($checks[$type]["keys"][$instance])) {
                        $checks[$type]["keys"][$instance] = array();
                    }
                    $checks[$type]["keys"][$instance][] = $associatedKey;
                }
            } else {
                $others[$key] = $value;
            }
		}
		foreach ($others as $key => $value) {
			if (!preg_match("/____/", $key) && ($key != "configure") && ((preg_match("/^logic/", $key) && validateLogic($value)) || !preg_match("/^logic/", $key))) { 
                if ($value === null) {
                    $value = "";
                }
				ExternalModules::setProjectSetting($prefix, $_GET['pid'], $key, $value."");
				$saved[$key] = $value;
			}
        }
		foreach ($others as $key => $value) {
			if (preg_match("/____/", $key) && ($key != "configure")) {
				if (preg_match("/^logic/", $key) && ($value !== "")) {
					$splitKey = preg_split("/____/", $key);
					ExternalModules::setInstance($prefix, $_GET['pid'], $splitKey[0], (int) $splitKey[1], $value);
					$saved[$splitKey[0]." ".$splitKey[1]] = $value;
				} else if (preg_match("/^logic/", $key) && validateLogic($value)) {
					$splitKey = preg_split("/____/", $key);
					ExternalModules::setInstance($prefix, $_GET['pid'], $splitKey[0], (int) $splitKey[1], $value);
					$saved[$splitKey[0]." ".$splitKey[1]] = $value;
				} else if (!preg_match("/^logic/", $key)) { 
					$splitKey = preg_split("/____/", $key);
					ExternalModules::setInstance($prefix, $_GET['pid'], $splitKey[0], (int) $splitKey[1], $value);
					$saved[$splitKey[0]." ".$splitKey[1]] = $value;
				} else {
                    $unsaved[$key] = "A ".$value;
                }
			} else {
                $unsaved[$key] = "B ".$value;
            }
		}
        $initialValue = "";
        foreach ($checks as $type => $info) {
            $checkboxes = $info['checks'];
            ksort($checkboxes);
            $keys = $info['keys'];
            foreach ($keys as $instance => $keyList) {
                $instance = (int) $instance;
                if ($instance === 0) {
                    foreach ($keyList as $key) {
                        $splitKey = preg_replace("/____\d+$/", "", $key);
		                ExternalModules::setProjectSetting($prefix, $_GET['pid'], $splitKey, $initialValue);
                    }
                } else {
                    foreach ($keyList as $key) {
                        $splitKey = preg_split("/____/", $key);
		                ExternalModules::setInstance($prefix, $_GET['pid'], $splitKey[0], $instance, $initialValue);
                    }
                }
            }
            foreach ($checkboxes as $instance => $values) {
                $instance = (int) $instance;
                foreach ($values as $value) {
                    if (isset($keys[$instance]) && (count($keys[$instance]) > 0)) {
                        $key = array_shift($keys[$instance]);
                        if (preg_match("/____/", $key)) {
                            $splitKey = preg_split("/____/", $key);
                        } else {
                            $splitKey = array($key, 0);
                        }
                        if (!isset($checks[$type]['checks']['saved'])) {
                            $checks[$type]['checks']['saved'] = array();
                        }
                        if (!isset($checks[$type]['checks']['saved'][$instance])) {
                            $checks[$type]['checks']['saved'][$instance] = array();
                        }
                        $checks[$type]['checks']['saved'][$instance][$splitKey[0]] = $value;
					    ExternalModules::setInstance($prefix, $_GET['pid'], $splitKey[0], $instance, $value);
                    } else {
                        if (!isset($checkAlerts[$type])) {
                            $checkAlerts[$type] = array();
                        }
                        $checkAlerts[$type][$instance] = $value;
                    }
                }
            }
        }
	}
	if (isset($_POST['___logicValidator___'])) {
		if (\LogicTester::isValid($_POST['___logicValidator___'])) {
			echo "true";
		} else {
			echo "false";
		}
		return;
	}
?>
<html>
<head>
	<style>
		h1 {
			font-family: Arial;
		}
		h2 {
			font-family: Arial;
		}
		p {
			font-family: Arial;
		}
		td {
			font-family: Arial;
			vertical-align: top;
			padding-right: 10px;
		}
		input[type='text'], select, textarea {
			width: 300px;
			font-size: 14px;
		}
		button {
			font-size: 14px;
		}
		textarea {
			height: 80px;
		}
        .header {
            font-size: 16px;
            font-weight: bold;
        }
	</style>
	<script src="https://code.jquery.com/jquery-3.1.1.js"></script>
	<script>
		function isLogicValid(ob) {
			if (ob.val() !== "") {
				$.post('?id=<?=$_GET['id']?>&page=<?=$_GET['page']?>&pid=<?=$_GET['pid']?>', { '___logicValidator___': ob.val() }, function(data) {
					if (data == "true") {
						ob.css({ "background-color": "white"});
					} else {
						ob.css({ "background-color": "#ff5d5d"});
					}
				});
			} else {
				ob.css({ "background-color": "white"});
			}
		}
	</script>
</head>
<body>
<?php
	$config = ExternalModules::getConfig($prefix, null, $_GET['pid']);
	$settings = ExternalModules::getProjectSettingsAsArray($prefix, $_GET['pid']);

	$forms = array();
	foreach ($config["project-settings"] as $row) {
		if (isset($row['sub_settings'])) {
			foreach ($row['sub_settings'] as $subrow) {
				if ($subrow['key'] == "form-name") {
					foreach ($subrow['choices'] as $choice) {
						if ($choice['value'] !== "") {
							$forms[] = $choice;
						}
					}
				}
			}
		}
	}
    $formNames = \REDCap::getInstrumentNames();

	echo "<h1>ARC REDCap Database</h1>";
    echo "<p><a href='".APP_PATH_WEBROOT."/ProjectSetup/index.php?pid=".$_GET['pid']."'>Back to Project</a></p>";
	echo "<form id='main' action='?id=".$_GET['id']."&page=configure&pid=".$_GET['pid']."' method='POST'>";
	echo "<input type='hidden' name='configure' value='1'>";
	echo "<p><button type='submit' form='main' value='Submit'>Change Configuration</button></p>";
	$currFormNumber = 0;
    $keysForTypes = array();
	foreach ($forms as $choice) {
		$value = $choice['value'];
		$name = $choice['name'];
		foreach ($config["project-settings"] as $row) {
			if (isset($row['sub_settings'])) {
				foreach ($row['sub_settings'] as $subrow) {
					$key = $subrow['key'];
					if ($currFormNumber > 0) {
						$key = $key . "____" . $currFormNumber;
					}
					if (($subrow['type'] == 'dag-list') || ($subrow['type'] == 'user-role-list')) {
                        if (!isset($keysForTypes[$subrow['type']])) {
                            $keysForTypes[$subrow['type']] = array();
                        }
                        if (!isset($keysForTypes[$subrow['type']][$currFormNumber])) {
                            $keysForTypes[$subrow['type']][$currFormNumber] = array();
                        }
                        $keysForTypes[$subrow['type']][$currFormNumber][] = $key;
                    }
                }
            }
        }
        $currFormNumber++;
    }
    $currFormNumber = 0;     # reset
	foreach ($forms as $choice) {
		$value = $choice['value'];
		$name = $choice['name'];
        $choices = array();

		echo "<h2>".$formNames[$name]."</h2>";
		echo "<table>";
		foreach ($config["project-settings"] as $row) {
			if (isset($row['sub_settings'])) {
				foreach ($row['sub_settings'] as $subrow) {
					$key = $subrow['key'];
					if ($currFormNumber > 0) {
						$key = $key . "____" . $currFormNumber;
					}
					if (isset($settings[$subrow['key']]['value']) && !is_array($settings[$subrow['key']]['value'])) {
						$settings[$subrow['key']]['value'] = array($settings[$subrow['key']]['value']);
					}
					if ($subrow['key'] == "form-name") {
						echo "<input type='hidden' name='".$key."' value=\"".htmlspecialchars($value)."\">";
					} else if (($subrow['type'] == 'dag-list') || ($subrow['type'] == 'user-role-list')) {
                        $currValues = array();
                        foreach ($keysForTypes[$subrow['type']][$currFormNumber] as $prospectiveKey) {
                            $splitProspectiveKey = preg_split("/____/", $prospectiveKey);
						    if (isset($settings[$splitProspectiveKey[0]]['value'][$currFormNumber]) && $settings[$splitProspectiveKey[0]]['value'][$currFormNumber]) {
							    $currValues[] = $settings[$splitProspectiveKey[0]]['value'][$currFormNumber];
						    }
                        }
						echo "<input type='hidden' name='KEY~~~".$subrow['type']."~~~".$key."~~~".$currFormNumber."' value='1'>";
                        if (!isset($choices[$subrow['type']])) {
                            $choices[$subrow['type']] = array(  "checkboxes"    =>  array(),
                                                                "keys"  => array(),
                                                                "checkboxValues"  => array()
                                                            );
                        }
                        foreach ($subrow['choices'] as $choice) {
                            if (!in_array($choice['value'], $choices[$subrow['type']]['checkboxValues'])) {
                                $choice['checked'] = false;
                                $choices[$subrow['type']]['checkboxes'][] = $choice;
                                $choices[$subrow['type']]['checkboxValues'][] = $choice['value'];
                            }
                        }
                        $i = 0;
                        foreach($choices[$subrow['type']]['checkboxes'] as $choice) {
                            if (in_array($choice['value'], $currValues)) {
                                $choices[$subrow['type']]['checkboxes'][$i]['checked'] = true;
                            }
                            $i++;
                        }
                        $choices[$subrow['type']]['keys'][] = $key;
					} else {
						echo "<tr>";
						echo "<td><b>".$subrow['name']."</b></td>";
						echo "<td>";
						$currValue = "";
						if (isset($settings[$subrow['key']]['value'][$currFormNumber])) {
							$currValue = $settings[$subrow['key']]['value'][$currFormNumber];
						}
						if ($subrow['type'] == 'textarea') {
							echo "<textarea name='".$key."'>".$currValue."</textarea>";
						} else if ($subrow['type'] == 'text') {
							if ($subrow['key'] == 'logic') {
								echo "<input type='text' name='".$key."' onblur='isLogicValid($(this));' value=\"".htmlspecialchars($currValue)."\">";
							} else {
								echo "<input type='text' name='".$key."' value=\"".htmlspecialchars($currValue)."\">";
							}
						} else if ($subrow['type'] == 'user-list') {
                            echo "<select name='".$key."'><option value=''></option>";
							foreach($subrow['choices'] as $choice) {
								$selected = "";
								if ($currValue === $choice['value']) {
									$selected = " SELECTED";
								}
								echo "<option value='".$choice['value']."'".$selected.">".$choice['name']."</option>";
							}
							echo "</select>";
                        }
						echo "</td>";
						echo "</tr>";
                    }
				}
			}
            $typeNames = array (
                                    "dag-list"  =>  "Data Access Groups",
                                    "user-role-list" =>  "User Roles",
                                    "user-list" =>  "Users"
                                );
            foreach ($choices as $type => $info) {
                echo "<tr>";
                echo "<td colspan='2'><span class='header'>".$typeNames[$type]."</span><br>";
                foreach ($info['checkboxes'] as $checkbox) {
                    $attr = "";
                    if ($checkbox['checked']) {
                        $attr = " checked";
                    }
                    echo "<input type='checkbox' name='CHK~~~$type"."~~~{$checkbox['value']}~~~$currFormNumber' $attr> {$checkbox['name']}<br>";  
                }
                echo "</td>";
                echo "</tr>";
            }
		}
		echo "</table>";
		$currFormNumber++;
	}

    foreach ($checkAlerts as $type => $info) {
        foreach ($info as $instance => $choiceValue) {
            $form = $formNames[$forms[$instance]['name']];
            foreach ($choices[$type]['checkboxes'] as $checkbox) {
                if ($checkbox['value'] == $choiceValue) {
                    echo "<script>alert('On form $form, the checkbox with {$checkbox['name']} was not saved, presumably because there are too many items checked on that form. Please try again.');</script>";
                }
            }
        }
    }

	echo "<p><button type='submit' form='main' value='Submit'>Change Configuration</button></p>";
	echo "</form>";
?>
</body>
</html>
