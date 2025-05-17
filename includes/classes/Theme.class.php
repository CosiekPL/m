<?php

declare(strict_types=1);

/**
 * Klasa obsługująca system motywów
 * Zarządza motywami graficznymi i układem strony
 */
class Theme
{
    /**
     * Aktualnie wybrany motyw
     */
    private string $themeName;
    
    /**
     * Czy motyw jest już załadowany
     */
    private bool $isLoaded = false;
    
    /**
     * Lista dostępnych motywów
     */
    private static ?array $availableThemes = null;
    
    /**
     * Konfiguracja aktualnego motywu
     */
    private array $config = [];
    
    /**
     * Domyślny motyw
     */
    private const DEFAULT_THEME = 'galaxy';
    
    /**
     * Konstruktor - inicjalizuje motyw
     */
    public function __construct(?string $themeName = null)
    {
        $this->setTheme($themeName ?? $this->getDefaultTheme());
    }
    
    /**
     * Ustawia wybrany motyw
     */
    public function setTheme(string $themeName): bool
    {
        if (!self::exists($themeName)) {
            $themeName = $this->getDefaultTheme();
        }
        
        $this->themeName = $themeName;
        $this->loadThemeConfig();
        
        return true;
    }
    
    /**
     * Pobiera aktualnie wybrany motyw
     */
    public function getTheme(): string
    {
        return $this->themeName;
    }
    
    /**
     * Pobiera domyślny motyw z konfiguracji lub stałą
     */
    public function getDefaultTheme(): string
    {
        if (defined('MODE') && MODE === 'INSTALL') {
            return self::DEFAULT_THEME;
        }
        
        // Próba pobrania z konfiguracji
        if (class_exists('Config')) {
            try {
                $config = Config::get();
                if (isset($config->skin)) {
                    return $config->skin;
                }
            } catch (Exception $e) {
                // Ignoruj błędy konfiguracji w trybie instalacji
            }
        }
        
        return self::DEFAULT_THEME;
    }
    
    /**
     * Sprawdza czy dany motyw istnieje
     */
    public static function exists(string $themeName): bool
    {
        return in_array($themeName, self::getAllowedThemes(true), true);
    }
    
    /**
     * Pobiera listę dostępnych motywów
     * 
     * @param bool $keyOnly Czy zwrócić tylko nazwy motywów (bez opisów)
     */
    public static function getAllowedThemes(bool $keyOnly = false): array
    {
        if (self::$availableThemes === null) {
            $themes = [];
            $themeDir = ROOT_PATH . 'styles/themes/';
            
            if (is_dir($themeDir)) {
                $directoryIterator = new DirectoryIterator($themeDir);
                
                foreach ($directoryIterator as $fileInfo) {
                    if ($fileInfo->isDir() && !$fileInfo->isDot()) {
                        $themeName = $fileInfo->getFilename();
                        
                        // Wczytanie opisu motywu
                        $configFile = $themeDir . $themeName . '/theme.config.php';
                        if (file_exists($configFile)) {
                            include $configFile;
                            if (isset($themeName, $themeDesc)) {
                                $themes[$themeName] = $themeDesc;
                                continue;
                            }
                        }
                        
                        // Domyślny opis jeśli nie znaleziono pliku konfiguracyjnego
                        $themes[$themeName] = ucfirst($themeName) . ' Theme';
                    }
                }
            }
            
            self::$availableThemes = $themes;
        }
        
        if ($keyOnly) {
            return array_keys(self::$availableThemes);
        }
        
        return self::$availableThemes;
    }
    
    /**
     * Ładuje konfigurację motywu
     */
    private function loadThemeConfig(): void
    {
        $configFile = ROOT_PATH . 'styles/themes/' . $this->themeName . '/theme.config.php';
        
        // Domyślna konfiguracja
        $this->config = [
            'name' => $this->themeName,
            'description' => ucfirst($this->themeName) . ' Theme',
            'author' => 'Unknown',
            'version' => '1.0',
            'css' => [
                'main.css'
            ],
            'js' => [
                'main.js'
            ]
        ];
        
        // Ładowanie konfiguracji z pliku
        if (file_exists($configFile)) {
            include $configFile;
            
            if (isset($themeName)) {
                $this->config['name'] = $themeName;
            }
            
            if (isset($themeDesc)) {
                $this->config['description'] = $themeDesc;
            }
            
            if (isset($themeAuthor)) {
                $this->config['author'] = $themeAuthor;
            }
            
            if (isset($themeVersion)) {
                $this->config['version'] = $themeVersion;
            }
            
            if (isset($cssFiles) && is_array($cssFiles)) {
                $this->config['css'] = $cssFiles;
            }
            
            if (isset($jsFiles) && is_array($jsFiles)) {
                $this->config['js'] = $jsFiles;
            }
        }
        
        $this->isLoaded = true;
    }
    
    /**
     * Pobiera konfigurację motywu
     */
    public function getConfig(): array
    {
        if (!$this->isLoaded) {
            $this->loadThemeConfig();
        }
        
        return $this->config;
    }
    
    /**
     * Pobiera ścieżkę do plików motywu
     */
    public function getThemePath(bool $absolutePath = false): string
    {
        $path = 'styles/themes/' . $this->themeName . '/';
        
        if ($absolutePath) {
            return ROOT_PATH . $path;
        }
        
        return $path;
    }
    
    /**
     * Generuje tagi CSS dla motywu
     */
    public function getCSSLinks(): string
    {
        $config = $this->getConfig();
        $path = $this->getThemePath();
        
        $cssLinks = '';
        foreach ($config['css'] as $cssFile) {
            $cssLinks .= '<link rel="stylesheet" href="' . $path . 'css/' . $cssFile . '">' . PHP_EOL;
        }
        
        return $cssLinks;
    }
    
    /**
     * Generuje tagi JS dla motywu
     */
    public function getJSLinks(): string
    {
        $config = $this->getConfig();
        $path = $this->getThemePath();
        
        $jsLinks = '';
        foreach ($config['js'] as $jsFile) {
            $jsLinks .= '<script src="' . $path . 'js/' . $jsFile . '"></script>' . PHP_EOL;
        }
        
        return $jsLinks;
    }
    
    /**
     * Sprawdza czy plik szablonu istnieje w motywie
     */
    public function templateExists(string $templateName): bool
    {
        $path = $this->getThemePath(true) . 'templates/' . $templateName;
        
        if (file_exists($path . '.twig')) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Pobiera ścieżkę do pliku szablonu (najpierw sprawdza w motywie, potem w katalogu domyślnym)
     */
    public function getTemplatePath(string $templateName): string
    {
        // Sprawdź czy szablon istnieje w motywie
        if ($this->templateExists($templateName)) {
            return '@theme/' . $templateName . '.twig';
        }
        
        // Szablonu nie znaleziono w motywie, użyj domyślnego
        return $templateName . '.twig';
    }
}
