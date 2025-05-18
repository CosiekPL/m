<?php

class Config
{
    /**
     * @var array Tablica przechowująca dane konfiguracyjne dla danego uniwersum.
     */
    protected $configData = [];

    /**
     * @var array Tablica przechowująca nazwy kolumn, które zostały zaktualizowane.
     */
    protected $updateRecords = [];

    /**
     * @var array Statyczna tablica przechowująca instancje klasy Config dla każdego uniwersum.
     */
    protected static $instances = [];

    /**
     * @var array Statyczna tablica przechowująca klucze konfiguracji globalnych (dotyczących wszystkich uniwersów).
     */
    protected static $globalConfigKeys = ['VERSION', 'game_name', 'stat', 'stat_level', 'stat_last_update',
        'stat_settings', 'stat_update_time', 'stat_last_db_update', 'stats_fly_lock',
        'cron_lock', 'ts_modon', 'ts_server', 'ts_tcpport', 'ts_udpport', 'ts_timeout',
        'ts_version', 'ts_cron_last', 'ts_cron_interval', 'ts_login', 'ts_password',
        'capaktiv', 'cappublic', 'capprivate', 'mail_active', 'mail_use', 'smtp_host',
        'smtp_port', 'smtp_user', 'smtp_pass', 'smtp_ssl', 'smtp_sendmail',
        'smail_path', 'fb_on', 'fb_apikey', 'fb_skey', 'ga_active', 'ga_key',
        'chat_closed', 'chat_allowchan', 'chat_allowmes', 'chat_allowdelmes',
        'chat_logmessage', 'chat_nickchange', 'chat_botname', 'chat_channelname',
        'chat_socket_active', 'chat_socket_host', 'chat_socket_ip', 'chat_socket_port',
        'chat_socket_chatid', 'ttf_file', 'sendmail_inactive', 'del_user_sendmail',
        'del_user_automatic', 'del_oldstuff', 'del_user_manually', 'ref_max_referals',
        'disclamerAddress', 'disclamerPhone', 'disclamerMail', 'disclamerNotice'];

    /**
     * Zwraca tablicę globalnych kluczy konfiguracyjnych.
     *
     * @return array Tablica globalnych kluczy konfiguracyjnych.
     */
    public static function getGlobalConfigKeys(): array
    {
        return self::$globalConfigKeys;
    }

    /**
     * Zwraca obiekt Config dla żądanego uniwersum (singleton).
     * Jeśli instancja dla danego uniwersum nie istnieje, tworzy ją.
     *
     * @param int $universe ID uniwersum. Domyślnie 0 (aktualne uniwersum).
     *
     * @return Config Obiekt Config dla danego uniwersum.
     *
     * @throws Exception Wyrzuca wyjątek, jeśli podano nieznane ID uniwersum.
     */
    static public function get($universe = 0): Config
    {
        if (empty(self::$instances)) {
            self::generateInstances(); // Generuj instancje dla wszystkich uniwersów przy pierwszym żądaniu.
        }

        if ($universe === 0) {
            $universe = Universe::current(); // Pobierz ID aktualnego uniwersum.
        }

        if (!isset(self::$instances[$universe])) {
            throw new Exception("Nieznane ID uniwersum: " . $universe);
        }

        return self::$instances[$universe];
    }

    /**
     * Ponownie generuje instancje Config dla wszystkich uniwersów (np. po zmianie konfiguracji).
     *
     * @return void
     */
    static public function reload(): void
    {
        self::generateInstances();
    }

    /**
     * Generuje instancje Config dla wszystkich uniwersów na podstawie danych z bazy danych.
     *
     * @return void
     */
    static private function generateInstances(): void
    {
        $db = Database::get(); // Pobierz instancję bazy danych.
        $configResult = $db->nativeQuery("SELECT * FROM %%CONFIG%%;"); // Pobierz wszystkie wiersze z tabeli konfiguracji.
        foreach ($configResult as $configRow) {
            self::$instances[$configRow['uni']] = new self($configRow); // Utwórz nową instancję Config dla każdego uniwersum.
            Universe::add($configRow['uni']); // Dodaj ID uniwersum do listy dostępnych uniwersów.
        }
    }

    /**
     * Konstruktor klasy. Inicjalizuje obiekt Config z danymi konfiguracyjnymi dla danego uniwersum.
     *
     * @param array $configData Tablica asocjacyjna z danymi konfiguracyjnymi.
     */
    public function __construct($configData)
    {
        $this->configData = $configData;
    }

    /**
     * Magiczna metoda do pobierania wartości klucza konfiguracji.
     *
     * @param string $key Nazwa klucza konfiguracji.
     *
     * @return mixed Wartość klucza konfiguracji.
     *
     * @throws UnexpectedValueException Wyrzuca wyjątek, jeśli żądany klucz nie istnieje.
     */
    public function __get($key)
    {
        if (!isset($this->configData[$key])) {
            throw new UnexpectedValueException(sprintf("Nieznany klucz konfiguracji %s!", $key));
        }

        return $this->configData[$key];
    }

    /**
     * Magiczna metoda do ustawiania wartości klucza konfiguracji.
     * Zaznacza rekord jako wymagający aktualizacji.
     *
     * @param string $key   Nazwa klucza konfiguracji.
     * @param mixed  $value Nowa wartość klucza.
     *
     * @return void
     *
     * @throws UnexpectedValueException Wyrzuca wyjątek, jeśli próbuje ustawić nieznany klucz.
     */
    public function __set($key, $value): void
    {
        if (!isset($this->configData[$key])) {
            throw new UnexpectedValueException(sprintf("Nieznany klucz konfiguracji %s!", $key));
        }
        $this->updateRecords[] = $key; // Dodaj klucz do listy zaktualizowanych rekordów.
        $this->configData[$key] = $value; // Ustaw nową wartość.
    }

    /**
     * Magiczna metoda do sprawdzania, czy klucz konfiguracji istnieje.
     *
     * @param string $key Nazwa klucza konfiguracji.
     *
     * @return bool True, jeśli klucz istnieje, false w przeciwnym razie.
     */
    public function __isset($key): bool
    {
        return isset($this->configData[$key]);
    }

    /**
     * Zapisuje zmienione wartości konfiguracji do bazy danych.
     * Opcjonalnie synchronizuje globalne klucze z innymi uniwersami.
     *
     * @param array|null $options Opcjonalna tablica opcji.
     * 'noGlobalSave' => bool - Czy pominąć synchronizację globalnych kluczy z innymi uniwersami (domyślnie false).
     *
     * @return bool True, jeśli zapis zakończył się pomyślnie.
     */

	public function save($options = NULL)
	{
		if (empty($this->updateRecords)) {
			return true;
		}
		
		if(is_null($options))
		{
			$options	= array();
		}
		
		$options	+= array(
			'noGlobalSave' => false
		);
		
		$updateData = array();
		$params     = array();
		foreach ($this->updateRecords as $columnName) {
			$updateData[]             = '`' . $columnName . '` = :' . $columnName;
			$params[':' . $columnName] = $this->configData[$columnName];

			//TODO: find a better way ...
			if(!$options['noGlobalSave'] && in_array($columnName, self::$globalConfigKeys))
			{
				foreach(Universe::availableUniverses() as $universeId)
				{
					if($universeId != $this->configData['uni'])
					{
						$config = Config::get();
						$config->$columnName = $this->configData[$columnName];
						$config->save(array('noGlobalSave' => true));
					}
				}
			}
		}

		$sql = 'UPDATE %%CONFIG%% SET '.implode(', ', $updateData).' WHERE `UNI` = :universe';
		$params[':universe'] = $this->configData['uni'];
		$db     = Database::get();
		$db->update($sql, $params);
		
		$this->updateRecords = array();
		return true;
	}

	static function getAll()
	{
		throw new Exception("Config::getAll is deprecated!");
	}
}
