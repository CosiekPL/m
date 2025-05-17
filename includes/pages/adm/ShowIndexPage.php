<?php



function ShowIndexPage()
{
	global $LNG;
	$template	= new template();

	$template->assign_vars(array(	
		'game_name'		=> Config::get()->game_name,
		'adm_cp_title'	=> $LNG['adm_cp_title'],
	));
	
	$template->display('adm/ShowIndexPage.tpl');
}