<?php



class ShowChatPage extends AbstractGamePage
{
	public static $requireModule = MODULE_CHAT;

	function __construct() 
	{
		parent::__construct();
	}
	
	function show() 
	{
		$action	= HTTP::_GP('action', '');
		if($action == 'alliance') {
			$this->setWindow('popup');
			$this->initTemplate();
		}
		
		$this->display('page.chat.default.tpl');
	}
}
