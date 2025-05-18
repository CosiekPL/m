<?php



function ShowTopnavPage()
{
	global $LNG, $USER;
	$template	= new template();

	$universeSelect	= array();
	foreach(Universe::availableUniverses() as $uniId)
	{
		$config = Config::get($uniId);
		$universeSelect[$uniId]	= sprintf('%s (ID: %d)', $config->uni_name, $uniId);
	}

	ksort($universeSelect);
	$template->assign_vars(array(	
		'ad_authlevel_title'	=> $LNG['ad_authlevel_title'],
		're_reset_universe'		=> $LNG['re_reset_universe'],
		'mu_universe'			=> $LNG['mu_universe'],
		'mu_moderation_page'	=> $LNG['mu_moderation_page'],
		'adm_cp_title'			=> $LNG['adm_cp_title'],
		'adm_cp_index'			=> $LNG['adm_cp_index'],
		'adm_cp_logout'			=> $LNG['adm_cp_logout'],
		'sid'					=> session_id(),
		'id'					=> $USER['id'],
		'authlevel'				=> $USER['authlevel'],
		'AvailableUnis'			=> $universeSelect,
		'UNI'					=> Universe::getEmulated(),
	));
	
	$template->show('ShowTopnavPage.twig');
}