<?php



if ($USER['authlevel'] == AUTH_USR)
{
    throw new PagePermissionException("Permission error!");
}

function ShowLogoutPage()
{
	$session	= Session::create();
	$session->adminAccess	= 0;
}

