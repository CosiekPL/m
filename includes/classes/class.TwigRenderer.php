<?php

/**
 *  2Moons 
 *   by Jan-Otto Kröpke 2009-2016
 *
 * For the full copyright and license information, please view the LICENSE
 *
 * @package 2Moons
 * @author Jan-Otto Kröpke <slaver7@gmail.com>
 * @copyright 2009 Lucky
 * @copyright 2016 Jan-Otto Kröpke <slaver7@gmail.com>
 * @licence MIT
 * @version 1.8.0
 * @link https://github.com/jkroepke/2Moons
 */

declare(strict_types=1);

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\Extension\DebugExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class TwigRenderer
{
    /**
     * Twig environment
     * @var Environment
     */
    protected Environment $twig;
    
    /**
     * Template directories
     * @var array
     */
    protected array $templateDirs = [];
    
    /**
     * Compile directory
     * @var string
     */
    protected string $compileDir;
    
    /**
     * Template variables
     * @var array
     */
    protected array $templateVars = [];
    
    /**
     * JavaScript files to include
     * @var array
     */
    public array $jsscript = [];
    
    /**
     * Inline JavaScript code
     * @var array
     */
    public array $script = [];
    
    /**
     * Display mode (full, ajax, popup)
     * @var string
     */
    protected string $window = 'full';
    
    /**
     * Language compile ID
     * @var string|null
     */
    public ?string $compile_id = null;

    /**
     * Initialize Twig renderer
     */
    public function __construct()
    {
        $this->templateDirs = [ROOT_PATH . 'styles/templates/'];
        $this->compileDir = ROOT_PATH . 'cache/templates_c';
        
        $this->initTwig();
    }

    /**
     * Initialize Twig environment
     */
    protected function initTwig(): void
    {
        // Ensure compile directory exists
        if (!is_dir($this->compileDir)) {
            mkdir($this->compileDir, 0755, true);
        }
        
        $loader = new FilesystemLoader($this->templateDirs);
        
        $options = [
            'cache' => $this->compileDir,
            'debug' => true,
            'auto_reload' => true
        ];
        
        $this->twig = new Environment($loader, $options);
        
        // Add debug extension
        $this->twig->addExtension(new DebugExtension());
        
        // Register custom filters
        $this->registerTwigFilters();
        
        // Register custom functions
        $this->registerTwigFunctions();
    }
    
    /**
     * Register custom Twig filters
     */
    protected function registerTwigFilters(): void
    {
        // Number formatting filter
        $this->twig->addFilter(new TwigFilter('number_format', function ($number, $decimals = 0) {
            return number_format($number, $decimals, ',', '.');
        }));
        
        // Pretty number filter
        $this->twig->addFilter(new TwigFilter('pretty_number', function ($number) {
            return pretty_number($number);
        }));
        
        // Raw HTML output filter
        $this->twig->addFilter(new TwigFilter('raw', function ($string) {
            return $string;
        }, ['is_safe' => ['html']]));
        
        // Date formatting filter
        $this->twig->addFilter(new TwigFilter('date', function ($timestamp, $format = null) {
            if ($format === null) {
                return date('Y-m-d H:i:s', $timestamp);
            }
            return date($format, $timestamp);
        }));
        
        // JSON encoding filter
        $this->twig->addFilter(new TwigFilter('json_encode', function ($data) {
            return json_encode($data);
        }));
    }
    
    /**
     * Register custom Twig functions
     */
    protected function registerTwigFunctions(): void
    {
        // Pretty number function
        $this->twig->addFunction(new TwigFunction('pretty_number', function ($number) {
            return pretty_number($number);
        }));
        
        // Custom URI function
        $this->twig->addFunction(new TwigFunction('url', function ($page, $params = []) {
            $url = 'game.php?page=' . $page;
            
            if (!empty($params)) {
                foreach ($params as $key => $value) {
                    $url .= '&' . $key . '=' . $value;
                }
            }
            
            return $url;
        }));
        
        // Current date/time function
        $this->twig->addFunction(new TwigFunction('now', function ($format = 'Y-m-d H:i:s') {
            return date($format);
        }));
    }

    /**
     * Set template directory
     */
    public function setTemplateDir(string $dir): void
    {
        $this->templateDirs = [$dir];
        $loader = new FilesystemLoader($this->templateDirs);
        $this->twig->setLoader($loader);
    }
    
    /**
     * Get template directories
     */
    public function getTemplateDir(): array
    {
        return $this->templateDirs;
    }
    
    /**
     * Set compile directory
     */
    public function setCompileDir(string $dir): void
    {
        $this->compileDir = $dir;
        $this->initTwig();
    }
    
    /**
     * Get compile directory
     */
    public function getCompileDir(): string
    {
        return $this->compileDir;
    }
    
    /**
     * Assign variables to template
     */
    public function assign_vars(array $variables, bool $nocache = true): void
    {
        foreach ($variables as $key => $value) {
            $this->templateVars[$key] = $value;
        }
    }
    
    /**
     * Add JavaScript file to include
     */
    public function loadscript(string $script): void
    {
        $scriptName = substr($script, 0, -3);
        if (!in_array($scriptName, $this->jsscript)) {
            $this->jsscript[] = $scriptName;
        }
    }
    
    /**
     * Add inline JavaScript code
     */
    public function execscript(string $script): void
    {
        $this->script[] = $script;
    }
    
    /**
     * Set page redirect
     */
    public function gotoside(string|bool $dest, int $time = 3): void
    {
        $this->assign_vars([
            'gotoinsec' => $time,
            'goto' => $dest,
        ]);
    }
    
    /**
     * Display error message
     */
    public function message(string $message, string|bool $dest = false, int $time = 3, bool $fatal = false): void
    {
        global $LNG, $THEME;
        
        $this->assign_vars([
            'message' => $message,
            'fcm_info' => $LNG['fcm_info'] ?? '',
            'Fatal' => $fatal,
            'dpath' => $THEME->getTheme(),
        ]);
        
        $this->gotoside($dest, $time);
        $this->display('error_message_body.twig');
    }
    
    /**
     * Display template
     */
    public function display(string $file): void
    {
        // Convert old .tpl filenames to .twig
        $twigFile = str_replace('.tpl', '.twig', $file);
        
        // Prepare data for template
        $this->assign_vars([
            'scripts' => $this->jsscript,
            'execscript' => implode("\n", $this->script),
        ]);
        
        // Render template
        echo $this->twig->render($twigFile, $this->templateVars);
    }
    
    /**
     * Show template (alias for display)
     */
    public function show(string $file): void
    {
        $this->display($file);
    }
    
    /**
     * Static method to display error message
     */
    public static function printMessage(string $message, bool $fullSide = true, ?array $redirect = null): never 
    {
        $renderer = new self();
        
        if ($redirect === null) {
            $redirect = [false, 0];
        }
        
        $renderer->message($message, $redirect[0], $redirect[1], !$fullSide);
        exit;
    }
    
    /**
     * Set display window mode
     */
    public function setWindow(string $mode): void
    {
        $this->window = $mode;
    }
    
    /**
     * Get current window mode
     */
    public function getWindow(): string
    {
        return $this->window;
    }
}
