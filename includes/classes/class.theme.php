<?php


<?php



declare(strict_types=1);

class Theme
{
	private string $skin;
	private string $skinPath;
	private string $templatePath;
	private array $defaultSkinPath;
	
	public function __construct()
	{	
		$this->skin			= ConfigFile::load()->skin;
		$this->skinPath		= 'styles/theme/' . $this->skin . '/';
		$this->templatePath	= ROOT_PATH . $this->skinPath . 'templates/';
		$this->defaultSkinPath	= array(ROOT_PATH . 'styles/templates/');
	}
	
	public function isCustomTPL(string $tplName): bool
	{
		return file_exists($this->templatePath . $tplName . '.twig');
	}
	
	public function getTemplatePath(): string
	{
		return $this->templatePath;
	}
	
	public function getTheme(): string
	{
		return $this->skin;
	}

	/**
	 * Add a custom template directory to array (for addons etc.)
	 */
	public function addCustomTPLDir(string $dir): bool
	{
		if(isset($this->defaultSkinPath[$dir])) {
			return false;
		}
		
		$this->defaultSkinPath[$dir] = ROOT_PATH . $dir;
		return true;
	}
	
	public static function getAvalibleSkins(): array
	{
		$skins = array_diff(scandir(ROOT_PATH.'styles/theme/'), array('.', '..', '.svn', '.git', '.idea'));
		$validSkins = array();
		
		foreach($skins as $skin) {
			if(!file_exists(ROOT_PATH.'styles/theme/'.$skin.'/style.css')) {
				continue;
			}
			
			$validSkins[] = $skin;
		}
		
		return $validSkins;
	}
}
class Theme
{
	static public $Themes;
	private $THEMESETTINGS;
	private $skininfo;
	private $skin;
	private $customtpls;
	
	function __construct($install = false)
	{	
		$this->skininfo = array();
		$this->skin		= isset($_SESSION['dpath']) ? $_SESSION['dpath'] : DEFAULT_THEME;
		$this->setUserTheme($this->skin);
	}
	
	function isHome() {
		$this->template		= ROOT_PATH.'styles/home/';
		$this->customtpls	= array();
	}
	
	function setUserTheme($Theme) {
		if(!file_exists(ROOT_PATH.'styles/theme/'.$Theme.'/style.cfg'))
			return false;
			
		$this->skin		= $Theme;
		$this->parseStyleCFG();
		$this->setStyleSettings();
	}
		
	function getTheme() {
		return './styles/theme/'.$this->skin.'/';
	}
	
	function getThemeName() {
		return $this->skin;
	}
	
	function getTemplatePath() {
		return ROOT_PATH.'/styles/templates/'.$this->skin.'/';
	}
		
	function isCustomTPL($tpl) {
		if(!isset($this->customtpls))
			return false;
			
		return in_array($tpl, $this->customtpls);
	}
	
	function parseStyleCFG() {
		require(ROOT_PATH.'styles/theme/'.$this->skin.'/style.cfg');
		$this->skininfo		= $Skin;
		$this->customtpls	= (array) $Skin['templates'];	
	}
	
	function setStyleSettings() {
		if(file_exists(ROOT_PATH.'styles/theme/'.$this->skin.'/settings.cfg')) {
			require(ROOT_PATH.'styles/theme/'.$this->skin.'/settings.cfg');
		}
		
		$this->THEMESETTINGS	= array_merge(array(
			'PLANET_ROWS_ON_OVERVIEW' => 2,
			'SHORTCUT_ROWS_ON_FLEET1' => 2,
			'COLONY_ROWS_ON_FLEET1' => 2,
			'ACS_ROWS_ON_FLEET1' => 1,
			'TOPNAV_SHORTLY_NUMBER' => 0,
		), $THEMESETTINGS);
	}
	
	function getStyleSettings() {
		return $this->THEMESETTINGS;
	}
	
	static function getAvalibleSkins() {
		if(!isset(self::$Themes))
		{
			if(file_exists(ROOT_PATH.'cache/cache.themes.php'))
			{
				self::$Themes	= unserialize(file_get_contents(ROOT_PATH.'cache/cache.themes.php'));
			} else {
				$Skins	= array_diff(scandir(ROOT_PATH.'styles/theme/'), array('..', '.', '.svn', '.htaccess', 'index.htm'));
				$Themes	= array();
				foreach($Skins as $Theme) {
					if(!file_exists(ROOT_PATH.'styles/theme/'.$Theme.'/style.cfg'))
						continue;
						
					require(ROOT_PATH.'styles/theme/'.$Theme.'/style.cfg');
					$Themes[$Theme]	= $Skin['name'];
				}
				file_put_contents(ROOT_PATH.'cache/cache.themes.php', serialize($Themes));
				self::$Themes	= $Themes;
			}
		}
		return self::$Themes;
	}
}
