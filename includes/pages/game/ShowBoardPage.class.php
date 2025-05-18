<?php
class ShowBoardPage extends AbstractGamePage
{
	public static $requireModule = 0;

	function __construct() 
	{
		parent::__construct();
	}
	
	function show() 
	{
		global $LNG;
		$boardUrl	= Config::get()->forum_url;
		if(filter_var($boardUrl, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED))
		{
			HTTP::sendHeader('Location', $boardUrl);
		}
		else
		{
			$this->printMessage($LNG['bad_forum_url']);
		}
	}
}
