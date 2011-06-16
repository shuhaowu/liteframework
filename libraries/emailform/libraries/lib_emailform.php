<?php

/**
 * EmailForm class. This class has a collection of functiosn and variables that sends an email given captchas.
 * Using class instead of namespace to be backward compatible
 * @author Shuhao Wu <shuhao@shuhaowu.com>
 * @copyright Copyright (c) 2011, Shuhao Wu
 * @package emailform
 */
class EmailForm{	
	
	const FAILURE = -1;
	const SUCCESS = 0;
	const INVALID_NAME = 1;
	const INVALID_EMAIL = 2;
	const INVALID_SUBJECT = 3;
	const INVALID_MESSAGE = 4;
	const INVALID_CAPTCHA = 5;
	
	
	public static $RECIPIENT = 'recipient@yourdomain.com'; // Change this to whatever you want.
	
	/**
	 * Starts a session safely without generating a notice.
	 * @return boolean Whatever session_start() returns. If there's already a session, it also returns false.
	 */
	public static function safe_session_start(){
		if (session_id() == '') return session_start();
		return false;
	}
	
	/**
	 * Generates a captcha image. Outputs itself to the browser.
	 */
	public static function generateCaptcha(){
		// Starts a session if it's not started.
		self::safe_session_start();
		
		$location = dirname(__FILE__) . '/emailform/';
		
		// Decide what characters are allowed in our string
		// Our captcha will be case-insensitive, and we avoid some
		// characters like 'O' and 'l' that could confuse users
		$charlist = '23456789ABCDEFGHJKMNPQRSTVWXYZ'; 
		
		$string = '';
		
		for ($i=0; $i<5; $i++) $string .= substr($charlist, mt_rand(0, strlen($charlist)-1), 1);
		
		// Create a GD image from our background image file
		$captcha = imagecreatefrompng($location . 'captcha.png');
		
		// Set the colour for our text string
		// This is chosen to be hard for machines to read against the background, but
		// OK for humans
		$col = imagecolorallocate($captcha, 240, 200, 240);
		
		// Write the string on to the image using TTF fonts
		imagettftext($captcha, 17, 0, 13, 22, $col, $location . 'dorisbr.ttf', $string);
		
		// Store the random string in a session variable
		$_SESSION['secret_string'] = $string;
		
		// Put out the image to the page
		header("Content-type: image/png");
		imagepng($captcha);
	}
	
	/**
	 * Checks if the captcha is correct.
	 * @param string $code The code user entered
	 * @return boolean true if the code is correct, false otherwise.
	 */
	public static function checkCaptcha($code){
		self::safe_session_start();
		return (strtoupper($code) == $_SESSION['secret_string']);
	}
	
	/**
	 * Using regex to validate email.
	 * @param string $email The email address.
	 * @return boolean
	 */
	public static function checkEmail($email){
		return ((bool) preg_match("~^[a-zA-Z0-9._-]+@[a-zA-Z0-9-]+\.[a-z]{2,4}$~", $email));
	}
	
	/**
	 * Sends the email.
	 * @param string $name Name of the user
	 * @param string $email Email address of the user.
	 * @param string $subject Subject user entered.
	 * @param string $message Message user entered.
	 * @param string $captcha Captcha code user has entered.
	 * @param mixed $toEmail To override the default EmailForm::$RECIPIENT value.
	 * @return int The codes that's defined in this class.
	 */ 
	public static function sendMail($name, $email, $subject, $message, $captcha, $toEmail=false){
		self::safe_session_start();
		
		if (strlen($name) <= 0){
			return self::INVALID_NAME;
		} else if (strlen($email) <= 0 || !self::checkEmail($email)){
			return self::INVALID_EMAIL;
		} else if (strlen($subject) <= 0){
			return self::INVALID_SUBJECT;
		} else if (strlen($message)<=0){
			return self::INVALID_MESSAGE;
		} else if (!self::checkCaptcha($captcha)){
			return self::INVALID_CAPTCHA;
		}
		
		$mailcontent = "Name: $name \nEmail: $email\nMessage:\n$message";
		$from = "From: $email";
		if (!$toEmail) $toEmail = self::$RECIPIENT;
		// return self::$SUCCESS; Debugging purposes
		return ((@ mail($toEmail, $subject, $mailcontent, $from)) ? self::SUCCESS : self::FAILURE);
	}
}

?>