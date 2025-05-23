<?php



class ShowBanListPage extends AbstractLoginPage
{
	public static $requireModule = MODULE_BANLIST;

	function __construct() 
	{
		parent::__construct();
        $this->setWindow('light');
	}

	function show()
	{		
		global $LNG;

		$db = Database::get();

		$page  		= HTTP::_GP('side', 1);

		$sql = "SELECT COUNT(*) as count FROM %%BANNED%% WHERE universe = :universe ORDER BY time DESC;";
		$banCount = $db->selectSingle($sql, array(
			':universe'	=> Universe::current(),
		), 'count');

		$maxPage	= ceil($banCount / BANNED_USERS_PER_PAGE);
		$page		= max(1, min($page, $maxPage));
		
		$sql = "SELECT * FROM %%BANNED%% WHERE universe = :universe ORDER BY time DESC LIMIT :offset, :limit;";
		$banResult = $db->select($sql, array(
			':universe'	=> Universe::current(),
			':offset'	=> (($page - 1) * BANNED_USERS_PER_PAGE),
			':limit'	=> BANNED_USERS_PER_PAGE
		));

		$banList	= array();
		
		foreach($banResult as $banRow)
		{
			$banList[]	= array(
				'player'	=> $banRow['who'],
				'theme'		=> $banRow['theme'],
				'from'		=> _date($LNG['php_tdformat'], $banRow['time'], Config::get()->timezone),
				'to'		=> _date($LNG['php_tdformat'], $banRow['longer'], Config::get()->timezone),
				'admin'		=> $banRow['author'],
				'mail'		=> $banRow['email'],
				'info'		=> sprintf($LNG['bn_writemail'], $banRow['author']),
			);
		}

		$universeSelect	= $this->getUniverseSelector();
		
		$this->assign(array(
			'universeSelect'	=> $universeSelect,
			'banList'			=> $banList,
			'banCount'			=> $banCount,
			'page'				=> $page,
			'maxPage'			=> $maxPage,
		));
		
		$this->display('page.banList.default.twig');
	}
}