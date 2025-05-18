<?php




class ShowLogoutPage extends AbstractGamePage
{
	public static $requireModule = 0;

	function __construct() 
	{
		parent::__construct();
		$this->setWindow('popup');
	}
	
	function show() 
	{
		Session::load()->delete();
		$this->display('page.logout.default.twig');
	}
}