<?php



if (!allowedTo(str_replace(array(dirname(__FILE__), '\\', '/', '.php'), '', __FILE__))) throw new Exception("Permission error!");

function ShowActivePage()
{
	global $LNG, $USER;
	$id = HTTP::_GP('id', 0);
	
	$actionType = HTTP::_GP('action','');

	if($actionType == 'delete' && !empty($id)){
		$GLOBALS['DATABASE']->query("DELETE FROM ".USERS_VALID." WHERE `validationID` = '".$id."' AND `universe` = '".Universe::getEmulated()."';");

	$query = $GLOBALS['DATABASE']->query("SELECT * FROM ".USERS_VALID." WHERE `universe` = '".Universe::getEmulated()."' ORDER BY validationID ASC");

	$Users	= array();
	while ($User = $GLOBALS['DATABASE']->fetch_array($query)) {
		$Users[]	= array(
			'id'			=> $User['validationID'],
			'name'			=> $User['userName'],
			'date'			=> _date($LNG['php_tdformat'], $User['date'], $USER['timezone']),
			'email'			=> $User['email'],
			'ip'			=> $User['ip'],
			'password'		=> $User['password'],
			'validationKey'	=> $User['validationKey'],
		);
	}

	$template	= new template();

	$template->assign_vars(array(	
		'Users'				=> $Users,
		'uni'				=> Universe::getEmulated(),
	));
	
	$template->show('ActivePage.twig');
}