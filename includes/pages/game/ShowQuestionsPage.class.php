<?php


 
class ShowQuestionsPage extends AbstractGamePage
{
	public static $requireModule = 0;

	function __construct() 
	{
		parent::__construct();
	}
	
	function show()
	{
		global $LNG;
		
		$LNG->includeData(array('FAQ'));
		
		$this->display('page.questions.default.twig');
	}
	
	function single()
	{
		global $LNG;
		
		$LNG->includeData(array('FAQ'));
		
		$categoryID	= HTTP::_GP('categoryID', 0);
		$questionID	= HTTP::_GP('questionID', 0);
		
		if(!isset($LNG['questions'][$categoryID][$questionID])) {
			HTTP::redirectTo('game.php?page=questions');
		}
		
		$this->assign(array(
			'questionRow'	=> $LNG['questions'][$categoryID][$questionID],
		));
		$this->display('page.questions.single.twig');
	}
}