<?php
defined('_SECURE_') or die('Forbidden');

if (_OP_ == 'token') {
	
	$token = trim($_REQUEST['username']);
	
	if ($token) {
		$username = '';
		$validated = FALSE;
		
		if (preg_match('/^(.+)@(.+)\.(.+)$/', $token)) {
			if (auth_validate_email($token, $password)) {
				$username = user_email2username($token);
				$validated = TRUE;
			}
		} else {
			if ($uid = auth_validate_token($token)) {
				$username = $token;
				$validated = TRUE;
			}
		}
		
		if ($validated) {
			auth_session_setup($uid);
			if (auth_isvalid()) {
				logger_print("u:" . $_SESSION['username'] . " uid:" . $uid . " status:" . $_SESSION['status'] . " sid:" . $_SESSION['sid'] . " ip:" . $_SERVER['REMOTE_ADDR'], 2, "login");
			} else {
				logger_print("unable to setup session u:" . $_SESSION['username'] . " status:" . $_SESSION['status'] . " sid:" . $_SESSION['sid'] . " ip:" . $_SERVER['REMOTE_ADDR'], 2, "login");
				$_SESSION['error_string'] = _('Unable to login');
			}
		} else {
			$_SESSION['error_string'] = _('Invalid login');
		}
	}
	
	header("Location: " . _u($core_config['http_path']['base']));
	exit();
} else {
	
	// error string
	if ($_SESSION['error_string']) {
		$error_content = '<div class="error_string">' . $_SESSION['error_string'] . '</div>';
	}
	
	$enable_logo = FALSE;
	$show_web_title = TRUE;
	
	if ($core_config['main']['enable_logo'] && $core_config['main']['logo_url']) {
		$enable_logo = TRUE;
		if ($core_config['main']['logo_replace_title']) {
			$show_web_title = FALSE;
		}
	}
	
	unset($tpl);
	$tpl = array(
		'name' => 'auth_token',
		'vars' => array(
			'HTTP_PATH_BASE' => $core_config['http_path']['base'],
			'WEB_TITLE' => $core_config['main']['web_title'],
			'URL_ACTION' => _u('index.php?app=main&inc=core_auth&route=token&op=token') ,
			'URL_REGISTER' => _u('index.php?app=main&inc=core_auth&route=register') ,
			'URL_FORGOT' => _u('index.php?app=main&inc=core_auth&route=forgot') ,
			'ERROR' => $error_content,
			'Username' => _('Token') ,
			'Login' => _('Login') ,
			'Register an account' => _('Register an account') ,
			'Recover password' => _('Recover password') ,
			'logo_url' => $core_config['main']['logo_url']
		) ,
		'ifs' => array(
			'enable_register' => $core_config['main']['enable_register'],
			'enable_forgot' => $core_config['main']['enable_forgot'],
			'enable_logo' => $enable_logo,
			'show_web_title' => $show_web_title,
		)
	);
	
	_p(tpl_apply($tpl));
}
