<?php
function FormGroupCheck($title, $names)
{

    $HTML = "<div class='form-group'>";

    $HTML .= "<h5>{$title}</h5>";

    foreach ($names as $name) {
        $HTML .= "<div class='form-check'>
								<input class='form-check-input' type='checkbox' value=' id='defaultCheck1' />
								<label class='form-check-label' for='defaultCheck1'>
									{$name}
								</label>
                            </div>
                            ";
    }

    $HTML .= "</div>";

    echo $HTML;
}
