<?php




class ShowRulesPage extends AbstractLoginPage
{
	public static $requireModule = 0;

	function __construct() 
	{
		parent::__construct();
        $this->setWindow('light');
	}
	
	function show() 
	{
		global $LNG;
		$this->assign(array(
			'rules'		=> $LNG->getTemplate('rules'),
		));
		
		$this->display('page.rules.default.twig');
	}
}
