<?php




class ShowChangelogPage extends AbstractGamePage
{
	public static $requireModule = 0;

	function __construct() 
	{
		parent::__construct();
	}
	
	function show() 
	{
        include ROOT_PATH.'includes/libs/Parsedown/Parsedown.php';

        $parsedown = new Parsedown();

		$this->assign(array(
			'ChangelogList'	=> $parsedown->text(file_get_contents(ROOT_PATH.'CHANGES.md')),
		));
		
		$this->display('page.changelog.default.twig');
	}
}