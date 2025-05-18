<?php



function ShowMenuPage()
{
	global $USER;
	$template	= new template();
	
	$template->assign_vars(array(	
		'supportticks'	=> $GLOBALS['DATABASE']->getFirstCell("SELECT COUNT(*) FROM ".TICKETS." WHERE universe = ".Universe::getEmulated()." AND status = 0;"),
	));
	
	$template->show('ShowMenuPage.twig');
}
