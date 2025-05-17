<?php



if (!allowedTo(str_replace(array(dirname(__FILE__), '\\', '/', '.php'), '', __FILE__))) throw new Exception("Permission error!");

function ShowClearCachePage()
{
	global $LNG;
	ClearCache();
	$template = new template();
	$template->message($LNG['cc_cache_clear']);
}