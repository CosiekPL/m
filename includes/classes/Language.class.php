<?php
declare(strict_types=1);

/**
 * Klasa obsługująca system tłumaczeń
 * Zarządza wielojęzycznością i ładowaniem plików tłumaczeń
 */
class Language implements ArrayAccess
{
    /**
     * Domyślny język systemu
     */
    private const DEFAULT_LANGUAGE = 'pl';
    
    /**
     * Aktualnie wybrany język
     */
    private string $language;
    
    /**
     * Załadowane frazy językowe
     */
    private array $container = [];
    
    /**
     * Czy języka są już załadowane
     */
    private bool $isLoaded = false;
    
    /**
     * Lista dostępnych języków
     */
    private static ?array $availableLanguages = null;
    
    /**
     * Konstruktor - inicjalizuje język
     */
    public function __construct(?string $language = null)
    {
        $this->setLanguage($language ?? self::DEFAULT_LANGUAGE);
        $this->loadLangFiles();
    }
    
    /**
     * Ustawia wybrany język
     */
    public function setLanguage(string $language): bool
    {
        if (!self::exists($language)) {
            $language = self::DEFAULT_LANGUAGE;
        }
        
        $this->language = $language;
        return true;
    }
    
    /**
     * Pobiera aktualnie wybrany język
     */
    public function getLanguage(): string
    {
        return $this->language;
    }
    
    /**
     * Sprawdza czy dany język istnieje
     */
    public static function exists(string $language): bool
    {
        return in_array($language, self::getAllowedLangs(true), true);
    }
    
    /**
     * Pobiera listę dostępnych języków
     * 
     * @param bool $keyOnly Czy zwrócić tylko kody języków (bez nazw)
     */
    public static function getAllowedLangs(bool $keyOnly = false): array
    {
        if (self::$availableLanguages === null) {
            $langs = [];
            $directoryIterator = new DirectoryIterator(ROOT_PATH . 'language/');
            
            foreach ($directoryIterator as $fileInfo) {
                if ($fileInfo->isDir() && !$fileInfo->isDot()) {
                    $langKey = $fileInfo->getFilename();
                    
                    // Wczytanie nazwy języka
                    $langFile = ROOT_PATH . "language/{$langKey}/LANG.cfg";
                    if (file_exists($langFile)) {
                        $langData = parse_ini_file($langFile);
                        if (isset($langData['name'])) {
                            $langs[$langKey] = $langData['name'];
                            continue;
                        }
                    }
                    
                    // Jeśli nie znaleziono pliku konfiguracyjnego, użyj nazwy katalogu
                    $langs[$langKey] = ucfirst($langKey);
                }
            }
            
            self::$availableLanguages = $langs;
        }
        
        if ($keyOnly) {
            return array_keys(self::$availableLanguages);
        }
        
        return self::$availableLanguages;
    }
    
    /**
     * Ładuje pliki językowe odpowiednie dla aktualnego kontekstu
     */
    public function loadLangFiles(string $directory = null): bool
    {
        if ($this->isLoaded && $directory === null) {
            return true;
        }
        
        $langPath = ROOT_PATH . 'language/' . $this->language . '/';
        
        if ($directory !== null) {
            // Ładowanie konkretnego katalogu
            $path = $langPath . $directory;
            $this->loadFromDirectory($path);
        } else {
            // Ładowanie plików językowych dla aktualnego trybu
            switch (MODE) {
                case 'INSTALL':
                    $this->loadFromDirectory($langPath . 'install');
                    break;
                case 'ADMIN':
                    $this->loadFromDirectory($langPath . 'admin');
                    // Ładowanie również ogólnych fraz
                    $this->loadFromDirectory($langPath . 'system');
                    $this->loadFromDirectory($langPath . 'custom');
                    break;
                case 'GAME':
                    $this->loadFromDirectory($langPath . 'system');
                    $this->loadFromDirectory($langPath . 'custom');
                    $this->loadFromDirectory($langPath . 'ingame');
                    break;
                case 'LOGIN':
                default:
                    $this->loadFromDirectory($langPath . 'system');
                    $this->loadFromDirectory($langPath . 'login');
                    break;
            }
            
            $this->isLoaded = true;
        }
        
        return true;
    }
    
    /**
     * Ładuje wszystkie pliki językowe z danego katalogu
     */
    private function loadFromDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }
        
        $directoryIterator = new DirectoryIterator($directory);
        
        foreach ($directoryIterator as $fileInfo) {
            if ($fileInfo->isFile() && $fileInfo->getExtension() === 'php') {
                $filePath = $fileInfo->getPathname();
                $langData = include($filePath);
                
                if (is_array($langData)) {
                    $this->container = array_merge($this->container, $langData);
                }
            }
        }
    }
    
    /**
     * Implementacja ArrayAccess
     */
    public function offsetSet($offset, $value): void
    {
        if (is_null($offset)) {
            $this->container[] = $value;
        } else {
            $this->container[$offset] = $value;
        }
    }
    
    /**
     * Implementacja ArrayAccess
     */
    public function offsetExists($offset): bool
    {
        return isset($this->container[$offset]);
    }
    
    /**
     * Implementacja ArrayAccess
     */
    public function offsetUnset($offset): void
    {
        unset($this->container[$offset]);
    }
    
    /**
     * Implementacja ArrayAccess
     * 
     * @return mixed Wartość frazy językowej lub klucz jeśli fraza nie istnieje
     */
    public function offsetGet($offset): mixed
    {
        return isset($this->container[$offset]) ? $this->container[$offset] : $offset;
    }
    
    /**
     * Zwraca wszystkie załadowane frazy językowe
     */
    public function getAll(): array
    {
        return $this->container;
    }
    
    /**
     * Sprawdza czy fraza językowa istnieje
     */
    public function exists(string $key): bool
    {
        return isset($this->container[$key]);
    }
    
    /**
     * Dodaje nową frazę językową
     */
    public function addData(array $data): void
    {
        $this->container = array_merge($this->container, $data);
    }
    
    /**
     * Formatuje frazę językową z parametrami
     * 
     * @param string $key Klucz frazy językowej
     * @param array $args Argumenty do formatowania
     */
    public function formatString(string $key, array $args = []): string
    {
        if (!isset($this->container[$key])) {
            return $key;
        }
        
        if (empty($args)) {
            return $this->container[$key];
        }
        
        return vsprintf($this->container[$key], $args);
    }
    
    /**
     * Zwraca frazę językową dla danego klucza lub klucz jeśli fraza nie istnieje
     */
    public function __get(string $name): string
    {
        return $this->offsetGet($name);
    }
    
    /**
     * Ustawia frazę językową
     */
    public function __set(string $name, string $value): void
    {
        $this->offsetSet($name, $value);
    }
    
    /**
     * Sprawdza czy fraza językowa istnieje
     */
    public function __isset(string $name): bool
    {
        return $this->offsetExists($name);
    }
}


