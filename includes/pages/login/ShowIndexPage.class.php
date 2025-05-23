<?php



class ShowIndexPage extends AbstractLoginPage
{
	function __construct() 
	{
		parent::__construct();
		$this->setWindow('light');
	}
	
	function show() 
	{
		global $LNG;
		
		$referralID		= HTTP::_GP('ref', 0);
		if(!empty($referralID))
		{
			$this->redirectTo('index.php?page=register&referralID='.$referralID);
		}
	
		$universeSelect	= array();
		
		foreach(Universe::availableUniverses() as $uniId)
		{
			$config = Config::get($uniId);
			$universeSelect[$uniId]	= $config->uni_name.($config->game_disable == 0 ? $LNG['uni_closed'] : '');
		}
		
		$Code	= HTTP::_GP('code', 0);
		$loginCode	= false;
		if(isset($LNG['login_error_'.$Code]))
		{
			$loginCode	= $LNG['login_error_'.$Code];
		}

        $sql = "SELECT date, title, text, user FROM %%NEWS%% ORDER BY id DESC LIMIT 2;";
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

		$config				= Config::get();
		$this->assign(array(
			'universeSelect'		=> $universeSelect,
			'code'					=> $loginCode,
			'descHeader'			=> sprintf($LNG['loginWelcome'], $config->game_name),
			'descText'				=> sprintf($LNG['loginServerDesc'], $config->game_name),
            'gameInformations'      => explode("\n", $LNG['gameInformations']),
			'loginInfo'				=> sprintf($LNG['loginInfo'], '<a href="index.php?page=rules">'.$LNG['menu_rules'].'</a>')
		));
		
		$this->display('page.index.default.twig');
	}
}