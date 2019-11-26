<?php
function FormGroupCheck($title, $names, $values = 0)
{
    $HTML = "<div class='form-group'>";
    $HTML .= "<h5>{$title}</h5>";
    foreach ($names as $i => $name) {
        $HTML .= "<div class='form-check'>
								<input class='form-check-input' type='checkbox'  id='{$name}' name='{$title}[]' value='{$values[$i]}' checked/>
								<label class='form-check-label' for='{$name}'>
									{$name}
								</label>
                            </div>
                            ";
    }

    $HTML .= "</div>";

    echo $HTML;
}
