<?php
function FormGroupCheck($title, $names,  $values = 0, $handleChangeFunction, $customClass = [])
{
    if (empty($customClass)) {
        $customClass = array_fill(0, count($names), "");
    }
    debug_to_console($customClass);
    debug_to_console($names);
    $HTML = "<div class='form-group'>";
    $HTML .= "<h5>{$title}</h5>";
    foreach ($names as $i => $name) {
        $HTML .= "<div class='form-check'>
								<input class='form-check-input {$customClass[$i]}' type='checkbox'  id='{$name}' name='{$title}[]' value='{$values[$i]}' onchange=\"{$handleChangeFunction}(this)\" checked/>
								<label class='form-check-label {$customClass[$i]}' for='{$name}'>
									{$name}
								</label>
                            </div>
                            ";
    }

    $HTML .= "</div>";

    echo $HTML;
}
