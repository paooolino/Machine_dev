<?php
/**
 *	Plugin for centralize error management
 *
 *	Errors are rendered through an error.php template which render an
 *		an $errorMessages array variable
 */
namespace Plugin;

class Error {
	
	private $machine;
	private $errors;
	private $raisedErrors;
	
	const ENCRYPT_METHOD = "AES-128-ECB";
	const ENCRYPT_KEY = 'If$;KVkTDu+ 29)UQy<Tzh[5$aTw41xaT$*i]g?1SJ30`}Ieat97|z5SU+d/3g=5';
	
	function __construct($machine) {
		$this->machine = $machine;
		$this->errors = [];
		$this->raisedErrors = [];
		
		$this->machine->addPage("/error/{errorslist}/", function($machine, $enc_codes) {
			$errorcodes = openssl_decrypt($enc_codes, self::ENCRYPT_METHOD, self::ENCRYPT_KEY);
			$errorcodes_arr = explode(",", $errorcodes);
			$errorMessages = [];
			foreach ($errorcodes_arr as $errorcode) {
				$errorMessages[] = $this->errors[$errorcode];
			}
			return [
				"template" => "error.php",
				"data" => [
					"errorMessages" => $errorMessages
				]
			];
		});
	}
	
	/**
	 *	Add an error to the repository.
	 *
	 *	@param $code String An all-caps no-spaces error code
	 *	@param $message String A textual message to be shown to the user
	 */
	public function addError($code, $message) {
		$this->errors[$code] = $message;
	}

	/**
	 *	Add an error to the raisedErrors list
	 *
	 *	@param $code String The error code to raise
	 */
	public function raiseError($code) {
		// to do... check if code exists in repository $this->errors
		$this->raisedErrors[$code] = $this->errors[$code];
	}
	
	/**
	 *	Get a list of currently raised errors
	 *
	 *	@return String A list of error codes with a separator
	 */
	public function getRaisedErrorCodesList($sep) {
		return implode($sep, array_keys($this->raisedErrors));
	}
	
	/**
	 *	Redirect to the error page, if any error has been raised
	 */
	public function showError() {
		if (count($this->raisedErrors) == 0) {
			return;
		}
		$enc_codes = openssl_encrypt($this->getRaisedErrorCodesList(","), self::ENCRYPT_METHOD, self::ENCRYPT_KEY);
		$this->machine->redirect("/error/" . $enc_codes . "/");
	}
}
