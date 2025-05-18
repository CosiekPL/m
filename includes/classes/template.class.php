<?php


declare(strict_types=1);

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\Extension\DebugExtension;

/**
 * Klasa obsługująca system szablonów Twig
 * Zastępuje starszy system szablonów Smarty
 */
class Template
{
    /**
     * Instancja obiektu Twig
     */
    private Environment $twig;
    
    /**
     * Instancja obiektu ładującego szablony Twig
     */
    private FilesystemLoader $loader;
    
    /**
     * Zmienne przypisane do szablonu
     */
    private array $variables = [];
    
    /**
     * Konstruktor - inicjalizuje system szablonów Twig
     * 
     * @param string $templateDir Katalog z szablonami
     * @param bool $enableCache Czy włączyć cache szablonów
     */
    public function __construct(string $templateDir = 'templates/', bool $enableCache = true)
    {
        $this->initTwig($templateDir, $enableCache);
        $this->registerDefaultFunctions();
        $this->registerDefaultFilters();
    }
    
    /**
     * Inicjalizuje środowisko Twig
     */
    private function initTwig(string $templateDir, bool $enableCache): void
    {
        // Utworzenie ładowacza plików szablonów
        $this->loader = new FilesystemLoader(ROOT_PATH . $templateDir);
        
        // Konfiguracja środowiska Twig
        $options = [
            'debug' => defined('DEBUG_MODE') && DEBUG_MODE === true,
            'auto_reload' => true
        ];
        
        // Włączenie cache jeśli wymagane
        if ($enableCache) {
            $options['cache'] = ROOT_PATH . CACHE_PATH . 'twig/';
        }
        
        // Utworzenie środowiska Twig
        $this->twig = new Environment($this->loader, $options);
        
        // Jeśli włączony jest tryb debug, dodaj rozszerzenie Debug
        if ($options['debug']) {
            $this->twig->addExtension(new DebugExtension());
        }
    }
    
    /**
     * Rejestruje domyślne funkcje dostępne w szablonach
     */
    private function registerDefaultFunctions(): void
    {
        // Funkcja do wyświetlania adresu
        $this->addFunction('showAddress', function(array $info, bool $displayLink = true): string {
            return showAddress($info, $displayLink);
        });
        
        // Funkcja do pobierania adresu statycznego pliku (css, js, images)
        $this->addFunction('asset', function(string $path): string {
            return 'assets/' . ltrim($path, '/');
        });
        
        // Funkcja do generowania adresu URL dla podanej ścieżki
        $this->addFunction('url', function(string $path = '', array $params = []): string {
            $query = '';
            if (!empty($params)) {
                $query = '?' . http_build_query($params);
            }
            
            return $path . $query;
        });
        
        // Funkcja do generowania tokenu CSRF
        $this->addFunction('csrf_token', function(): string {
            if (!isset($_SESSION['csrf_token'])) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            }
            
            return $_SESSION['csrf_token'];
        });
        
        // Funkcja do wyświetlania formatu liczby (pretty_number)
        $this->addFunction('pretty_number', function($number, bool $color = true): string {
            return pretty_number($number, $color);
        });
        
        // Funkcja do sprawdzania dostępu do modułu
        $this->addFunction('isModuleAvailable', function(int $moduleID): bool {
            return isModuleAvailable($moduleID);
        });
        
        // Funkcja dump dla debugowania w trybie DEBUG_MODE
        $this->addFunction('dump', function($variable): string {
            if (defined('DEBUG_MODE') && DEBUG_MODE === true) {
                ob_start();
                var_dump($variable);
                return ob_get_clean() ?: '';
            }
            
            return '';
        });
    }
    
    /**
     * Rejestruje domyślne filtry dostępne w szablonach
     */
    private function registerDefaultFilters(): void
    {
        // Filtr do formatowania liczby (pretty_number)
        $this->addFilter('number_format', function($number, bool $color = false): string {
            return pretty_number($number, $color);
        });
        
        // Filtr do formatowania daty i czasu
        $this->addFilter('date', function($timestamp, string $format = 'd.m.Y H:i:s'): string {
            if (!is_numeric($timestamp)) {
                return (string)$timestamp;
            }
            
            return date($format, (int)$timestamp);
        });
        
        // Filtr do skracania tekstu
        $this->addFilter('truncate', function(string $text, int $length = 100, string $suffix = '...'): string {
            if (mb_strlen($text) <= $length) {
                return $text;
            }
            
            return mb_substr($text, 0, $length) . $suffix;
        });
        
        // Filtr do konwersji tekstu na małe litery
        $this->addFilter('lower', function(string $text): string {
            return mb_strtolower($text);
        });
        
        // Filtr do konwersji tekstu na wielkie litery
        $this->addFilter('upper', function(string $text): string {
            return mb_strtoupper($text);
        });
        
        // Filtr do konwersji tekstu do pierwszej wielkiej litery
        $this->addFilter('capitalize', function(string $text): string {
            return mb_convert_case($text, MB_CASE_TITLE);
        });
    }
    
    /**
     * Dodaje funkcję do szablonu
     */
    public function addFunction(string $name, callable $callback): void
    {
        $this->twig->addFunction(new TwigFunction($name, $callback));
    }
    
    /**
     * Dodaje filtr do szablonu
     */
    public function addFilter(string $name, callable $callback): void
    {
        $this->twig->addFilter(new TwigFilter($name, $callback));
    }
    
    /**
     * Dodaje globalną zmienną dostępną we wszystkich szablonach
     */
    public function addGlobal(string $name, mixed $value): void
    {
        $this->twig->addGlobal($name, $value);
    }
    
    /**
     * Przypisuje zmienną do szablonu
     */
    public function assign(string $name, mixed $value): void
    {
        $this->variables[$name] = $value;
    }
    
    /**
     * Przypisuje tablicę zmiennych do szablonu
     */
    public function assignArray(array $variables): void
    {
        $this->variables = array_merge($this->variables, $variables);
    }
    
    /**
     * Pobiera wszystkie przypisane zmienne
     */
    public function getTemplateVars(): array
    {
        return $this->variables;
    }
    
    /**
     * Renderuje szablon i zwraca wynik jako string
     */
    public function render(string $templateName): string
    {
        if (!str_ends_with($templateName, '.twig')) {
            $templateName .= '.twig';
        }
        
        try {
            return $this->twig->render($templateName, $this->variables);
        } catch (\Exception $e) {
            if (defined('DEBUG_MODE') && DEBUG_MODE === true) {
                return 'Błąd szablonu: ' . $e->getMessage();
            }
            
            return 'Błąd podczas renderowania szablonu.';
        }
    }
    
    /**
     * Renderuje szablon i wyświetla wynik
     */
    public function display(string $templateName): void
    {
        echo $this->render($templateName);
    }
    
    /**
     * Sprawdza czy szablon istnieje
     */
    public function templateExists(string $templateName): bool
    {
        if (!str_ends_with($templateName, '.twig')) {
            $templateName .= '.twig';
        }
        
        return $this->loader->exists($templateName);
    }
    
    /**
     * Czyści cache szablonów
     */
    public function clearCache(): void
    {
        if (is_dir(ROOT_PATH . CACHE_PATH . 'twig/')) {
            $this->cleanDirectory(ROOT_PATH . CACHE_PATH . 'twig/');
        }
    }
    
    /**
     * Pomocnicza funkcja do usuwania zawartości katalogu
     */
    private function cleanDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        
        $iterator = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::CHILD_FIRST);
        
        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
    }
}
use Twig\TwigFilter;
use Twig\TwigFunction;

class template
{
    /**
     * Twig engine instance
     * @var Environment
     */
    private Environment $twigEngine;
    
    /**
     * Array of JavaScript files to include
     * @var array
     */
    public array $jsscript = [];
    
    /**
     * Array of JavaScript commands to execute
     * @var array
     */
    private array $script = [];
    
    /**
     * Template constructor
     */
    public function __construct()
    {    
        $templateDirectories = [ROOT_PATH.'styles/templates'];
        
        if (MODE === 'adm') {
            $templateDirectories = [ROOT_PATH.'styles/templates/adm'];
        }
        
        $loader = new FilesystemLoader($templateDirectories);
        
        $twigOptions = [
            'cache' => TWIG_CACHE_ENABLED ? CACHE_PATH.'templates' : false,
            'debug' => TWIG_DEBUG,
            'auto_reload' => true,
        ];
        
        $this->twigEngine = new Environment($loader, $twigOptions);
        
        if (TWIG_DEBUG) {
            $this->twigEngine->addExtension(new DebugExtension());
        }
        
        // Add custom filters
        $this->twigEngine->addFilter(new TwigFilter('time', [$this, 'timeFilter']));
        $this->twigEngine->addFilter(new TwigFilter('number', [$this, 'numberFilter']));
        $this->twigEngine->addFilter(new TwigFilter('raw', function ($string) {
            return $string;
        }, ['is_safe' => ['html']]));
        
        // Add custom functions
        $this->twigEngine->addFunction(new TwigFunction('printr', [self::class, 'printr']));
        
        // Set default global variables
        $isAdmin = (MODE === 'adm');
        $this->assign_vars(['isAdmin' => $isAdmin], false);
    }
    
    /**
     * Filter to format time
     * 
     * @param int $seconds Time in seconds
     * @return string Formatted time
     */
    public function timeFilter(int $seconds): string
    {
        return pretty_time($seconds);
    }
    
    /**
     * Filter to format numbers
     * 
     * @param int|float $number The number to format
     * @return string Formatted number
     */
    public function numberFilter($number): string
    {
        return pretty_number($number);
    }
    
    /**
     * Renders a template and returns the result
     * 
     * @param string $file Template file path
     * @return string Rendered template
     */
    public function render(string $file): string
    {
        global $LNG;
        
        $this->assign([
            'scripts' => $this->script,
            'execscript' => implode("\n", $this->jsscript),
        ]);
        
        $this->assign_vars([
            'lang' => $LNG->getLanguage(),
        ], false);
        
        // Convert .tpl to .twig if needed
        $filePath = $this->convertTemplateExtension($file);
        
        return $this->twigEngine->render($filePath, $this->twigEngine->getGlobals());
    }
    
    /**
     * Sets multiple template variables
     * 
     * @param array $var Variables to assign
     * @param bool $nocache Whether to use nocache (ignored in Twig, kept for compatibility)
     */
    public function assign_vars(array $var, bool $nocache = true): void
    {
        foreach ($var as $key => $value) {
            $this->twigEngine->addGlobal($key, $value);
        }
    }
    
    /**
     * Alias for assign_vars
     * 
     * @param array $var Variables to assign
     * @param bool $nocache Whether to use nocache (ignored in Twig, kept for compatibility)
     */
    public function assign(array $var, bool $nocache = true): void
    {
        $this->assign_vars($var, $nocache);
    }
    
    /**
     * Adds JavaScript file to load
     * 
     * @param string $script JavaScript file name
     */
    public function loadscript(string $script): void
    {
        $this->script[] = $script;
    }
    
    /**
     * Adds JavaScript code to execute
     * 
     * @param string $script JavaScript code
     */
    public function execscript(string $script): void
    {
        $this->jsscript[] = $script;
    }
    
    /**
     * Adds redirect functionality to template
     * 
     * @param string $dest Destination URL
     * @param int $time Time before redirect in seconds
     */
    public function gotoside(string $dest, int $time = 3): void
    {
        $this->assign([
            'gotoinsec' => $time,
            'goto' => $dest,
        ]);
    }
    
    /**
     * Displays a template
     * 
     * @param string $file Template file path
     */
    public function show(string $file): void
    {
        global $LNG, $CONFIG;
        
        $this->assign([
            'scripts' => $this->script,
            'execscript' => implode("\n", $this->jsscript),
        ]);
                
        $this->assign_vars([
            'lang' => $LNG->getLanguage(),
            'REV' => substr($CONFIG->VERSION, -4),
            'VERSION' => $CONFIG->VERSION,
        ], false);
        
        // Convert .tpl to .twig if needed
        $filePath = $this->convertTemplateExtension($file);
        
        echo $this->twigEngine->render($filePath, $this->twigEngine->getGlobals());
    }
    
    /**
     * Converts .tpl extensions to .twig for backward compatibility
     * 
     * @param string $templatePath Original template path
     * @return string Converted template path
     */
    private function convertTemplateExtension(string $templatePath): string
    {
        if (substr($templatePath, -4) === '.tpl') {
            return substr($templatePath, 0, -4) . '.twig';
        }
        
        return $templatePath;
    }
    
    /**
     * Debug function to print variable content
     * 
     * @param mixed $content Content to print
     * @return string Formatted output
     */
    public static function printr($content): string
    {
        if (PHP_SAPI === 'cli') {
            return var_export($content, true);
        }
        
        return "<pre>" . htmlspecialchars(print_r($content, true)) . "</pre>";
    }
    
    /**
     * Gets template file content
     * 
     * @param string $templateName Template name
     * @return string Template content
     */
    public static function gettemplate(string $templateName): string
    {
        // Handle both .tpl and .twig extensions
        $templateName = substr($templateName, -4) === '.tpl' 
            ? substr($templateName, 0, -4) . '.twig' 
            : $templateName;
        
        $templatePath = ROOT_PATH . 'styles/templates/' . TEMPLATE_DIR . $templateName;
        
        if (!file_exists($templatePath)) {
            // Try with original extension
            $templatePath = ROOT_PATH . 'styles/templates/' . TEMPLATE_DIR . $templateName . '.twig';
        }
        
        return file_exists($templatePath) ? file_get_contents($templatePath) : '';
    }
    
    /**
     * Gets JavaScript file content
     * 
     * @param string $templateName JavaScript file name
     * @return string JavaScript content
     */
    public static function getjavascript(string $templateName): string
    {
        return file_get_contents(ROOT_PATH . 'scripts/' . $templateName);
    }
    
    /**
     * Creates JSON response for AJAX requests
     * 
     * @param bool $status Status of the request
     * @param string $text Status message
     * @return string JSON encoded response
     */
    public static function getAjaxJSON(bool $status, string $text = "OK"): string
    {
        return json_encode(['status' => $status, 'statusText' => $text]);
    }
}
