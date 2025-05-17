<?php




class ShowDisclamerPage extends AbstractLoginPage
{
	public static $requireModule = 0;

	function __construct() 
	{
		parent::__construct();
        $this->setWindow('light');
	}
	
	function show() 
	{
		$config	= Config::get();
		$this->assign(array(
			'disclamerAddress'	=> makebr($config->disclamerAddress),
			'disclamerPhone'	=> $config->disclamerPhone,
			'disclamerMail'		=> $config->disclamerMail,
			'disclamerNotice'	=> $config->disclamerNotice,
		));
		
		$this->display('page.disclamer.default.tpl');
	}
}
