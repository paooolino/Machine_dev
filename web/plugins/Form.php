<?php
namespace Plugin;

class Form {
	
	private $machine;
	private $forms;
	private $formrow_template = '
		<div class="formRow">
			<div class="formLabel">
				{{LABEL}}
			</div>
			<div class="formField">
				{{FIELD}}
			</div>
		</div>
	';
	private $form_template = '
		<div class="formContainer">
			<form action="">
				{{FORMROWS}}
				<button type="submit"></button>
			</form>
		</div>
	';
	
	function __construct($machine) {
		$this->machine = $machine;
	}
	
	public function addForm($name, $fields) {
		$this->forms[$name] = $fields;
	}
	
	public function Render($params) {
		$formName = $params[0];
		
		$html_rows = "";
		foreach ($this->forms[$formName] as $formField) {
			$html_rows .= $this->machine->populate_template($this->formrow_template, [
				"LABEL" => $this->getFormLabel($formField),
				"FIELD" => $this->getFormField($formField)
			]);
		}
		return $html_rows;
	}
	
	private function getFormLabel($formField) {
		if (gettype($formField) == "string") {
			return $formField;
		}
	}
	
	private function getFormField($formField) {
		if (gettype($formField) == "string") {
			return '<input type="text" name="' . $formField . '" />';
		}
	}
}