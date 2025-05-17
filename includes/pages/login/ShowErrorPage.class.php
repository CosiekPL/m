<?php




class ShowErrorPage extends AbstractLoginPage
{
	public static $requireModule = 0;
	
	protected $disableEcoSystem = true;

	function __construct() 
	{
		parent::__construct();
		$this->initTemplate();
	}
	
	static function printError($Message, $fullSide = true, $redirect = NULL)
	{
		$pageObj	= new self;
		$pageObj->printMessage($Message, $fullSide, $redirect);
	}
	
	function show() 
	{
		
	}
}
