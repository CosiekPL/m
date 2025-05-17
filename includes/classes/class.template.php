<?php

declare(strict_types=1);

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\Cache\FilesystemCache;
use Twig\Extension\DebugExtension;

/**
 * Klasa szablonu obsługująca silnik Twig
 * Zastępuje starszy silnik Smarty
 */
class template
{
    protected string $window = 'full';
    public array $jsscript = [];
    public array $script = [];
    protected Environment $twig;
    protected array $templateDirs = ['styles/templates/'];
    protected ?string $compileDir = null;
    protected ?string $cacheDir = null;
    protected ?string $compileId = null;
    
    /**
     * Inicjalizacja szablonu
     */
    public function __construct()
    {
        $this->twigSettings();
    }

    /**
     * Konfiguracja silnika Twig
     */
    private function twigSettings(): void
    {
        $loader = new FilesystemLoader($this->templateDirs);
        
        $this->compileDir = is_writable(CACHE_PATH) ? CACHE_PATH : $this->getTempPath();
        $this->cacheDir = $this->compileDir . 'templates';
        
        // Upewnij się, że katalog cache istnieje
        if (!is_dir($this->cacheDir) && !mkdir($this->cacheDir, 0755, true) && !is_dir($this->cacheDir)) {
            throw new RuntimeException("Nie można utworzyć katalogu cache: {$this->cacheDir}");
        }
        
        $isDebug = defined('DEBUG_MODE') && DEBUG_MODE === true;
        
        $this->twig = new Environment($loader, [
            'cache' => new FilesystemCache($this->cacheDir),
            'debug' => $isDebug,
            'auto_reload' => $isDebug,
            'strict_variables' => $isDebug,
            'optimizations' => -1, // Włącz wszystkie optymalizacje
        ]);
        
        // Dodaj rozszerzenie debugowania, jeśli tryb debug jest włączony
        if ($isDebug) {
            $this->twig->addExtension(new DebugExtension());
        }
        
        $this->registerTwigFilters();
        $this->registerTwigFunctions();
    }

    /**
     * Rejestruje filtry Twig
     */
    private function registerTwigFilters(): void
    {
        // Podstawowe filtry
        $this->twig->addFilter(new TwigFilter('number', fn($number, $decimals = 0) => 
            number_format($number, $decimals)));
        
        $this->twig->addFilter(new TwigFilter('json', fn($value) => 
            json_encode($value, JSON_THROW_ON_ERROR)));
        
        $this->twig->addFilter(new TwigFilter('time', fn($timestamp) => 
            date('Y-m-d H:i:s', is_numeric($timestamp) ? (int)$timestamp : strtotime($timestamp))));
            
        // Filtry formatowania
        $this->twig->addFilter(new TwigFilter('colorNumber', fn($number) => 
            $number > 0 ? "<span style=\"color:lime\">+{$number}</span>" : 
            ($number < 0 ? "<span style=\"color:red\">{$number}</span>" : $number)));
            
        $this->twig->addFilter(new TwigFilter('prettyNumber', fn($number) => 
            pretty_number($number)));
            
        $this->twig->addFilter(new TwigFilter('shortNumber', function($number) {
            $units = ['', 'K', 'M', 'B', 'T', 'Q'];
            $index = 0;
            while ($number >= 1000 && $index < count($units) - 1) {
                $number /= 1000;
                $index++;
            }
            return round($number, 1) . $units[$index];
        }));
        
        // Filtry do obsługi tekstów
        $this->twig->addFilter(new TwigFilter('truncate', fn($text, $length = 100, $suffix = '...') => 
            mb_strlen($text) > $length ? mb_substr($text, 0, $length) . $suffix : $text));
            
        $this->twig->addFilter(new TwigFilter('nl2br', fn($text) => 
            nl2br($text)));
            
        $this->twig->addFilter(new TwigFilter('bbcode', function($text) {
            // Podstawowa implementacja BBCode
            $patterns = [
                '/\[b\](.*?)\[\/b\]/is',
                '/\[i\](.*?)\[\/i\]/is',
                '/\[u\](.*?)\[\/u\]/is',
                '/\[url=(.*?)\](.*?)\[\/url\]/is',
                '/\[url\](.*?)\[\/url\]/is',
                '/\[img\](.*?)\[\/img\]/is'
            ];
            
            $replacements = [
                '<strong>$1</strong>',
                '<em>$1</em>',
                '<u>$1</u>',
                '<a href="$1">$2</a>',
                '<a href="$1">$1</a>',
                '<img src="$1" alt="" />'
            ];
            
            return preg_replace($patterns, $replacements, $text);
        }));
    }
    
    /**
     * Rejestruje funkcje Twig
     */
    private function registerTwigFunctions(): void
    {
        // Funkcja do sprawdzania uprawnień
        $this->twig->addFunction(new TwigFunction('hasPermission', function($permission) {
            global $USER;
            return isset($USER['rights']) && ($USER['rights'] & $permission) != 0;
        }));
        
        // Funkcja pomocnicza do adresów URL
        $this->twig->addFunction(new TwigFunction('url', function($page, $params = []) {
            $url = '?page=' . $page;
            foreach ($params as $key => $value) {
                $url .= '&' . $key . '=' . urlencode($value);
            }
            return $url;
        }));
        
        // Funkcja do wstawiania zasobów (CSS, JS)
        $this->twig->addFunction(new TwigFunction('asset', function($path, $version = null) {
            $version = $version ?? TIMESTAMP;
            return $path . '?v=' . $version;
        }));
        
        // Funkcja do sprawdzania, czy moduł jest dostępny
        $this->twig->addFunction(new TwigFunction('isModuleAvailable', function($moduleId) {
            return isModuleAvailable($moduleId);
        }));
    }

    /**
     * Pobiera tymczasowy katalog
     */
    private function getTempPath(): string
    {
        require_once 'includes/libs/wcf/BasicFileUtil.class.php';
        return BasicFileUtil::getTempFolder();
    }
    
    /**
     * Ustawia katalog szablonów
     */
    public function setTemplateDir(string $dir): void
    {
        $this->templateDirs = [$dir];
        // Aktualizacja loader'a Twig
        $loader = new FilesystemLoader($this->templateDirs);
        $this->twig->setLoader($loader);
    }
    
    /**
     * Zwraca katalog szablonów
     */
    public function getTemplateDir(): array
    {
        return $this->templateDirs;
    }
    
    /**
     * Ustawia katalog kompilacji
     */
    public function setCompileDir(string $dir): void
    {
        $this->compileDir = $dir;
        
        // Upewnij się, że katalog istnieje
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException("Nie można utworzyć katalogu kompilacji: {$dir}");
        }
        
        $isDebug = defined('DEBUG_MODE') && DEBUG_MODE === true;
        
        // Aktualizacja konfiguracji Twig
        $this->twig = new Environment(
            $this->twig->getLoader(), 
            [
                'cache' => new FilesystemCache($dir),
                'debug' => $isDebug,
                'auto_reload' => $isDebug,
                'strict_variables' => $isDebug,
                'optimizations' => -1
            ]
        );
        
        // Ponowna rejestracja filtrów i funkcji po zmianie środowiska
        $this->registerTwigFilters();
        $this->registerTwigFunctions();
    }
    
    /**
     * Zwraca katalog kompilacji
     */
    public function getCompileDir(): string
    {
        return $this->compileDir;
    }
    
    /**
     * Ustawia katalog cache
     */
    public function setCacheDir(string $dir): void
    {
        $this->cacheDir = $dir;
        
        // Upewnij się, że katalog istnieje
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException("Nie można utworzyć katalogu cache: {$dir}");
        }
    }
    
    /**
     * Zwraca katalog cache
     */
    public function getCacheDir(): string
    {
        return $this->cacheDir;
    }
    
    /**
     * Przypisuje zmienne do szablonu
     */
    public function assign_vars(array $var, bool $nocache = true): void
    {
        foreach ($var as $key => $value) {
            $this->twig->addGlobal($key, $value);
        }
    }

    /**
     * Ładuje skrypt JavaScript
     */
    public function loadscript(string $script): void
    {
        $this->jsscript[] = substr($script, 0, -3);
    }

    /**
     * Wykonuje skrypt inline
     */
    public function execscript(string $script): void
    {
        $this->script[] = $script;
    }
    
    /**
     * Konfiguracja dla panelu administratora
     */
    private function adm_main(): void
    {
        global $LNG, $USER;
        
        $dateTimeServer = new DateTime("now");
        if (isset($USER['timezone'])) {
            try {
                $dateTimeUser = new DateTime("now", new DateTimeZone($USER['timezone']));
            } catch (Exception $e) {
                $dateTimeUser = $dateTimeServer;
            }
        } else {
            $dateTimeUser = $dateTimeServer;
        }

        $config = Config::get();

        $this->assign_vars([
            'scripts' => $this->script,
            'title' => $config->game_name . ' - ' . $LNG['adm_cp_title'],
            'fcm_info' => $LNG['fcm_info'],
            'lang' => $LNG->getLanguage(),
            'REV' => substr($config->VERSION, -4),
            'date' => explode("|", date('Y\|n\|j\|G\|i\|s\|Z', TIMESTAMP)),
            'Offset' => $dateTimeUser->getOffset() - $dateTimeServer->getOffset(),
            'VERSION' => $config->VERSION,
            'dpath' => 'styles/theme/gow/',
            'bodyclass' => 'full',
            'USER' => $USER ?? [],
            'TIMESTAMP' => TIMESTAMP,
            'debug' => defined('DEBUG_MODE') && DEBUG_MODE === true
        ]);
    }
    
    /**
     * Wyświetla szablon
     */
    public function show(string $file): void
    {
        global $LNG, $THEME;

        if ($THEME->isCustomTPL($file)) {
            $this->setTemplateDir($THEME->getTemplatePath());
        }

        $tplDir = $this->getTemplateDir();
            
        if (MODE === 'INSTALL') {
            $this->setTemplateDir($tplDir[0] . 'install/');
        } elseif (MODE === 'ADMIN') {
            $this->setTemplateDir($tplDir[0] . 'adm/');
            $this->adm_main();
        }

        $this->assign_vars([
            'scripts' => $this->jsscript,
            'execscript' => implode("\n", $this->script),
        ]);

        $this->assign_vars([
            'LNG' => $LNG,
            'MODE' => MODE,
        ]);
        
        $this->compileId = $LNG->getLanguage();
        
        // Zmiana rozszerzenia z .tpl na .twig
        $twigFile = str_replace('.tpl', '.twig', $file);
        
        try {
            echo $this->twig->render($twigFile);
        } catch (Exception $e) {
            if (defined('DEBUG_MODE') && DEBUG_MODE === true) {
                echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
                echo '<p>File: ' . htmlspecialchars($e->getFile()) . ' on line ' . $e->getLine() . '</p>';
                echo '<p>Trace: ' . htmlspecialchars($e->getTraceAsString()) . '</p>';
            } else {
                echo 'Wystąpił błąd podczas renderowania szablonu. Skontaktuj się z administratorem.';
            }
        }
    }
    
    /**
     * Wyświetla szablon (alternatywna metoda)
     */
    public function display(?string $file = null): void
    {
        if ($file === null) {
            throw new InvalidArgumentException('Nazwa pliku szablonu nie może być pusta');
        }
        
        global $LNG;
        $this->compileId = $LNG->getLanguage();
        
        // Zmiana rozszerzenia z .tpl na .twig
        $twigFile = str_replace('.tpl', '.twig', $file);
        
        try {
            echo $this->twig->render($twigFile);
        } catch (Exception $e) {
            if (defined('DEBUG_MODE') && DEBUG_MODE === true) {
                echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
            } else {
                echo 'Wystąpił błąd podczas renderowania szablonu. Skontaktuj się z administratorem.';
            }
        }
    }
    
    /**
     * Ustawia przekierowanie
     */
    public function gotoside(string|bool $dest, int $time = 3): void
    {
        $this->assign_vars([
            'gotoinsec' => $time,
            'goto' => $dest,
        ]);
    }
    
    /**
     * Wyświetla komunikat
     */
    public function message(string $mes, string|bool $dest = false, int $time = 3, bool $Fatal = false): void
    {
        global $LNG, $THEME;
    
        $this->assign_vars([
            'mes' => $mes,
            'fcm_info' => $LNG['fcm_info'] ?? '',
            'Fatal' => $Fatal,
            'dpath' => $THEME->getTheme(),
            'is_ajax' => isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
        ]);
        
        $this->gotoside($dest, $time);
        
        // Jeśli to żądanie AJAX, zwróć JSON zamiast pełnej strony
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            HTTP::sendHeader('Content-Type', 'application/json');
            echo json_encode([
                'message' => $mes,
                'redirect' => $dest,
                'redirect_time' => $time,
                'fatal' => $Fatal
            ]);
            return;
        }
        
        $this->show('error_message_body.twig');
    }
    
    /**
     * Statyczna metoda do wyświetlania komunikatów
     */
    public static function printMessage(string $Message, bool $fullSide = true, ?array $redirect = null): never {
        $template = new self();
        if ($redirect === null) {
            $redirect = [false, 0];
        }
        
        $template->message($Message, $redirect[0], $redirect[1], !$fullSide);
        exit;
    }
    
    /**
     * Czyści cache szablonów
     */
    public function clearCache(): bool
    {
        if (!is_dir($this->cacheDir)) {
            return true;
        }
        
        $dir = new RecursiveDirectoryIterator($this->cacheDir, FilesystemIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($dir, RecursiveIteratorIterator::CHILD_FIRST);
        
        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }
        
        return true;
    }
}
