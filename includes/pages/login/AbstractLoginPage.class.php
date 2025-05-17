<?php

declare(strict_types=1);

/**
 * Abstrakcyjna klasa bazowa dla wszystkich stron logowania
 * Zapewnia podstawowe funkcje i narzędzia używane przez wszystkie podstrony logowania
 */
abstract class AbstractLoginPage
{
    /**
     * Referencja do obiektu szablonu
     * @var template|null
     */
    protected ?template $tplObj = null;
    
    /**
     * Aktualne okno wyświetlania (normal, light, popup, ajax)
     * @var string
     */
    protected string $window;
    
    /**
     * Domyślne okno wyświetlania dla tej strony
     * @var string
     */
    public string $defaultWindow = 'normal';
    
    /**
     * Konstruktor - inicjalizuje szablon i ustawia typ okna
     */
    protected function __construct() 
    {
        if (!AJAX_REQUEST) {
            $this->setWindow($this->defaultWindow);
            $this->initTemplate();
        } else {
            $this->setWindow('ajax');
        }
    }

    /**
     * Pobiera selektor wszechświatów jako tablicę asocjacyjną
     * Używany do wyświetlania listy dostępnych wszechświatów w formularzu logowania
     */
    protected function getUniverseSelector(): array
    {
        $universeSelect = [];
        foreach (Universe::availableUniverses() as $uniId) {
            $universeSelect[$uniId] = Config::get($uniId)->uni_name;
        }

        return $universeSelect;
    }

    /**
     * Inicjalizuje obiekt szablonu 
     * Ustawia odpowiedni katalog szablonów dla stron logowania
     */
    protected function initTemplate(): bool
    {
        if (isset($this->tplObj)) {
            return true;
        }
            
        $this->tplObj = new template;
        [$tplDir] = $this->tplObj->getTemplateDir();
        $this->tplObj->setTemplateDir($tplDir . 'login/');
        return true;
    }
    
    /**
     * Ustawia typ okna wyświetlania (normal, popup, ajax, light)
     */
    protected function setWindow(string $window): void 
    {
        $this->window = $window;
    }
        
    /**
     * Zwraca aktualny typ okna wyświetlania
     */
    protected function getWindow(): string 
    {
        return $this->window;
    }
    
    /**
     * Tworzy ciąg zapytania na podstawie aktualnych parametrów page i mode
     * Używany do tworzenia linków zachowujących aktualne parametry
     */
    protected function getQueryString(): string 
    {
        $queryString = [];
        $page = HTTP::_GP('page', '');
        
        if (!empty($page)) {
            $queryString['page'] = $page;
        }
        
        $mode = HTTP::_GP('mode', '');
        if (!empty($mode)) {
            $queryString['mode'] = $mode;
        }
        
        return http_build_query($queryString);
    }
    
    /**
     * Ładuje i przypisuje dane globalne do szablonu
     * Ustawia podstawowe zmienne potrzebne na wszystkich stronach
     */
    protected function getPageData(): void
    {        
        global $LNG;

        $config = Config::get();

        $this->tplObj->assign_vars([
            'recaptchaEnable'     => $config->capaktiv,
            'recaptchaPublicKey'  => $config->cappublic,
            'gameName'            => $config->game_name,
            'facebookEnable'      => $config->fb_on,
            'fb_key'              => $config->fb_apikey,
            'mailEnable'          => $config->mail_active,
            'reg_close'           => $config->reg_closed,
            'referralEnable'      => $config->ref_active,
            'analyticsEnable'     => $config->ga_active,
            'analyticsUID'        => $config->ga_key,
            'lang'                => $LNG->getLanguage(),
            'UNI'                 => Universe::current(),
            'VERSION'             => $config->VERSION,
            'REV'                 => substr($config->VERSION, -4),
            'languages'           => Language::getAllowedLangs(false),
            'currentYear'         => date('Y'),
            'serverName'          => $config->game_name,
            'serverinfo'          => $config->uni_name ?? '',
            'TIMESTAMP'           => TIMESTAMP,
        ]);
    }
    
    /**
     * Wyświetla komunikat z przyciskami przekierowania
     * Używany do wyświetlania błędów, powiadomień itp.
     */
    protected function printMessage(string $message, ?array $redirectButtons = null, ?array $redirect = null, bool $fullSide = true): never
    {
        $this->assign([
            'message'          => $message,
            'redirectButtons'  => $redirectButtons,
        ]);
        
        if (isset($redirect)) {
            $this->tplObj->gotoside($redirect[0], $redirect[1]);
        }
        
        if (!$fullSide) {
            $this->setWindow('popup');
        }
        
        $this->display('error.default.twig');
    }
    
    /**
     * Metoda zapisywania - może być nadpisana przez klasy pochodne
     * Wykonywana przed wyświetleniem strony
     */
    protected function save(): void 
    {
        // Może być nadpisana w klasach pochodnych
    }

    /**
     * Przypisuje zmienne do szablonu
     */
    protected function assign(array $array, bool $nocache = true): void 
    {
        $this->tplObj->assign_vars($array, $nocache);
    }
    
    /**
     * Wyświetla szablon i kończy działanie skryptu
     * Automatycznie dodaje układ strony na podstawie typu okna
     */
    protected function display(string $file): never 
    {
        global $LNG;
        
        $this->save();
        
        if ($this->getWindow() !== 'ajax') {
            $this->getPageData();
        }

        // Obsługa wild-card dla wielu wszechświatów
        if (defined('UNIS_WILDCAST') && UNIS_WILDCAST) {
            $hostParts = explode('.', HTTP_HOST);
            if (preg_match('/uni[0-9]+/', $hostParts[0])) {
                array_shift($hostParts);
            }
            $host = implode('.', $hostParts);
            $basePath = PROTOCOL . $host . HTTP_BASE;
        } else {
            $basePath = PROTOCOL . HTTP_HOST . HTTP_BASE;
        }
        
        $this->assign([
            'lang'            => $LNG->getLanguage(),
            'bodyclass'       => $this->getWindow(),
            'basepath'        => $basePath,
            'isMultiUniverse' => count(Universe::availableUniverses()) > 1,
            'unisWildcast'    => defined('UNIS_WILDCAST') ? UNIS_WILDCAST : false,
        ]);

        $this->assign([
            'LNG' => $LNG,
        ], false);
        
        // Zmiana rozszerzenia pliku z .tpl na .twig
        $twigFile = str_replace('.tpl', '.twig', $file);
        $layoutFile = str_replace('.tpl', '.twig', 'extends:layout.' . $this->getWindow() . '.tpl|' . $file);
        
        $this->tplObj->display($layoutFile);
        exit;
    }
    
    /**
     * Wysyła odpowiedź JSON i kończy działanie skryptu
     */
    protected function sendJSON(mixed $data): never 
    {
        $this->save();
        echo json_encode($data, JSON_THROW_ON_ERROR);
        exit;
    }
    
    /**
     * Przekierowuje użytkownika do podanego URL i kończy działanie skryptu
     */
    protected function redirectTo(string $url): never 
    {
        $this->save();
        HTTP::redirectTo($url);
        exit;
    }
    
    /**
     * Przekierowuje użytkownika metodą POST do podanego URL z dodatkowymi polami
     * Używane do bezpiecznego przesyłania danych do zewnętrznych systemów
     */
    protected function redirectPost(string $url, array $postFields): never 
    {
        $this->save();
        $this->assign([
            'url'        => $url,
            'postFields' => $postFields,
        ]);
        
        $this->display('info.redirectPost.twig');
    }
}