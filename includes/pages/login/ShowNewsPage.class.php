<?php



class ShowNewsPage extends AbstractLoginPage
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

		$sql = "SELECT date, title, text, user FROM %%NEWS%% ORDER BY id DESC;";
		$newsResult = Database::get()->select($sql);

		$newsList	= array();
		
		foreach ($newsResult as $newsRow)
		{
			$newsList[]	= array(
				'title' => $newsRow['title'],
				'from' 	=> sprintf($LNG['news_from'], _date($LNG['php_tdformat'], $newsRow['date']), $newsRow['user']),
				'text' 	=> makebr($newsRow['text']),
			);
		}
		
		$this->assign(array(
			'newsList'	=> $newsList,
		));
		
		$this->display('page.news.default.tpl');
	}
}