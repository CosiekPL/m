<?php



if ($USER['authlevel'] == AUTH_USR)
{
	throw new PagePermissionException("Permission error!");
}

function ShowLoginPage()
{
	global $USER;
	
	$session	= Session::create();
	if($session->adminAccess == 1)
	{
		HTTP::redirectTo('admin.php');
	}
	
	if(isset($_REQUEST['admin_pw']))
	{
		$password	= PlayerUtil::cryptPassword($_REQUEST['admin_pw']);

		if ($password == $USER['password']) {
			$session->adminAccess	= 1;
			HTTP::redirectTo('admin.php');
		}
	}

	$template	= new template();

	$template->assign_vars(array(	
		'bodyclass'	=> 'standalone',
		'username'	=> $USER['username']
	));
	$template->show('LoginPage.twig');
}