inside /libraries is the main file to be imported by the framework.

inside /static is a folder named mail. There's a javascript file and 2 images there. Those are for ajax calls.

If you want to use the javascript file, the html has to be like this:

<form id="mailing" action="/your/url" method="post">
	<p>
		<label for="name">Your Name:</label>
		<br />
		<input name="name" type="text" size="40" id="name" value="" />
	</p>
	<p>
		<label for="email">Your Email:</label>
		<br />
		<input name="email" type="text" id="email" size="40" value="" />
	</p>
	<p>
		<label for="subject">Your Subject:</label>
		<br />
		<input name="subject" type="text" id="subject" size="40" value="" />
	</p>
	<p>
		<label for="msg">Your Message:</label>
		<br />
		<textarea name="msg" id="msg" cols="42" rows="8"></textarea>
	</p>
	<p>
		<label for="captcha">Enter code (click to refresh):<br />
		<img src="/contact/captcha" id="captchaimg" /></label>
		<input name="captcha" type="text" id="captcha" size="8" />
	</p>
	<p>&nbsp;</p>
	<p>
		<input type="submit" name="button" id="button" value="Submit" />
		<input type="reset" name="button2" id="button2" value="Reset" />
	</p>
</form>

Attach the javascript file for the mail.js. Everything will be done automatically.
Inside the mail.js file there are a couple of settings you will need to change. The instructions are in the comment.

PHP controller code. May have to modify:

	function contact($args=array()){
		$vars = array('title'=>'Contact Us', 'name'=>'', 'email'=>'', 'subject'=>'', 'msg'=>'', 'error'=>'');
		if (count($args) > 0){
			switch ($args[0]){
				case 'captcha':
					EmailForm::generateCaptcha();
					return;
				break;
				
				
				case 'send': case 'ajaxsend':
					$data = array();
					foreach (array('name', 'email', 'subject', 'msg', 'captcha') as $key){
						$data[$key] = (isset($_POST[$key])) ? strip_tags($_POST[$key]) : '';
					}
					$status = EmailForm::sendMail($data['name'], $data['email'], $data['subject'], $data['msg'], $data['captcha']);
					$vars = array_merge($vars, $data);
					$vars['captcha'] = '';
					switch ($status){
						case EmailForm::FAILURE:
							$vars['error'] = 'Something has gone wrong. Please contact us directly.';
						break;
						
						case EmailForm::SUCCESS:
							$vars['error'] = 'Your message has been sent!';
						break;
						
						case EmailForm::INVALID_NAME:
							$vars['error'] = 'Name cannot be empty.';
						break;
						
						case EmailForm::INVALID_EMAIL:
							$vars['error'] = 'Invalid email.';
						break;
						
						case EmailForm::INVALID_SUBJECT:
							$vars['error'] = 'Subject cannot be empty.';
						break;
						
						case EmailForm::INVALID_MESSAGE:
							$vars['error'] = 'Message cannot be empty.';
						break;
						
						case EmailForm::INVALID_CAPTCHA:
							$vars['error'] = 'Invalid captcha.';
						break;
					}
					if ($args[0] == 'ajaxsend'){
						echo $vars['error'];
						return;
					}
					$this->render('contact', $vars);
				break;
				
				case 'checkcaptcha':
					$captchaStandings = '0';
					if (isset($args[1])){
						$captchaStandings = (EmailForm::checkCaptcha($args[1])) ? '1' : '0';
					}
					echo $captchaStandings;
					return;
				break;
			}
		}
		$this->render('contact', $vars);
	}