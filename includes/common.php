<?php

declare(strict_types=1);

/**
 * Plik wczytywany przez wszystkie skrypty - zawiera podstawową konfigurację
 * Odpowiada za inicjalizację środowiska, wczytanie wymaganych klas i ustawienie
 * globalnych zmiennych
 */

// Wyświetlanie błędów (wyłączone w produkcji)
if (defined('DEBUG_MODE') && DEBUG_MODE === true) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ERROR | E_WARNING | E_PARSE);
    ini_set('display_errors', '0');
}

// Definicja stałych dla różnych trybów misji flot
define('FLEET_OUTWARD', 0);
define('FLEET_RETURN', 1);
define('FLEET_HOLD', 2);
define('FLEET_END', 3);
define('FLEET_DESTROYED', 4);

// Ustawienie strefy czasowej
date_default_timezone_set('UTC');

// Ustawienie czasu serwera (timestamp)
define('TIMESTAMP', time());

// Automatyczne ładowanie klas
spl_autoload_register(function ($class) {
    // Lista ścieżek do wyszukiwania klas
    $paths = [
        'includes/classes/',
        'includes/classes/missions/',
        'includes/pages/',
        'includes/pages/game/',
        'includes/pages/adm/',
        'includes/pages/login/',
    ];
    
    // Dodanie rozszerzenia do pliku
    $classFile = $class . '.class.php';
    
    // Wyszukiwanie klasy w zdefiniowanych ścieżkach
    foreach ($paths as $path) {
        if (file_exists($path . $classFile)) {
            require_once($path . $classFile);
            return;
        }
    }
    
    // Sprawdzenie dla klas, które nie mają rozszerzenia .class.php
    foreach ($paths as $path) {
        if (file_exists($path . $class . '.php')) {
            require_once($path . $class . '.php');
            return;
        }
    }
});

// Ścieżka do katalogu cache
if (!defined('CACHE_PATH')) {
    define('CACHE_PATH', 'cache/');
}

// Zdefiniowanie stałych dla ścieżek
define('ROOT_PATH', dirname(__DIR__) . '/');
define('INCLUDES_PATH', ROOT_PATH . 'includes/');
define('VENDOR_PATH', ROOT_PATH . 'vendor/');

// Ładowanie konfiguracji z pliku config.php
if (file_exists(ROOT_PATH . 'config.php')) {
    require ROOT_PATH . 'config.php';
}

// Określenie trybu działania
$modeMapping = [
    'adm' => 'ADMIN',
    'install' => 'INSTALL',
    'login' => 'LOGIN',
    // Domyślnie tryb gry
];

// Określenie trybu na podstawie parametru GET 'mode'
$mode = isset($_GET['mode']) ? strtolower($_GET['mode']) : '';
define('MODE', isset($modeMapping[$mode]) ? $modeMapping[$mode] : 'GAME');

// Sprawdzenie czy żądanie jest typu AJAX
define('AJAX_REQUEST', isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

// Inicjalizacja globalnych obiektów
$LNG = null;      // Obiekt języka
$THEME = null;    // Obiekt motywu
$USER = null;     // Dane zalogowanego użytkownika
$PLANET = null;   // Aktualnie wybrana planeta

// Inicjalizacja sesji
session_start();

// Funkcje pomocnicze

/**
 * Formatuje liczbę w przyjazny dla użytkownika sposób
 */
function pretty_number(float|int $number, bool $color = true): string
{
    if (!is_numeric($number)) {
        return $number;
    }
    
    if ($color) {
        // Jeśli $color=true, kolorujemy liczbę (zielona dla dodatnich, czerwona dla ujemnych)
        if ($number > 0) {
            return '<span style="color:green">+' . number_format($number, 0, ',', '.') . '</span>';
        } elseif ($number < 0) {
            return '<span style="color:red">' . number_format($number, 0, ',', '.') . '</span>';
        }
    }
    
    // Bez kolorowania
    return number_format($number, 0, ',', '.');
}

/**
 * Sprawdza czy moduł jest dostępny
 */
function isModuleAvailable(int $moduleID): bool
{
    global $USER;
    
    if (isset($USER['rights']) && ($USER['rights'] & 1) == 1) {
        // Administrator ma dostęp do wszystkich modułów
        return true;
    }
    
    // Pobieranie dostępnych modułów z konfiguracji
    $config = Config::get();
    $modules = explode(';', $config->moduls);
    
    return in_array($moduleID, $modules);
}

/**
 * Generuje unikalny identyfikator
 */
function getUniqueIdentifier(): string
{
    return md5(uniqid((string)mt_rand(), true));
}

/**
 * Ładuje klasę języka w zależności od ustawień użytkownika lub przeglądarki
 */
function getLanguage(?string $language = null): Language
{
    global $USER;
    
    if ($language !== null) {
        return new Language($language);
    }
    
    if (isset($USER['lang']) && !empty($USER['lang'])) {
        return new Language($USER['lang']);
    }
    
    if (isset($_REQUEST['lang']) && !empty($_REQUEST['lang'])) {
        return new Language($_REQUEST['lang']);
    }
    
    // Pobieranie języka z przeglądarki
    if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        $browserLang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
        if (Language::exists($browserLang)) {
            return new Language($browserLang);
        }
    }
    
    // Domyślny język
    return new Language();
}

// Ładowanie języka
$LNG = getLanguage();

// Ładowanie motywu
$THEME = new Theme();

// Inicjalizacja dodatkowych komponentów w zależności od trybu
if (MODE === 'GAME' || MODE === 'ADMIN') {
    // Inicjalizacja danych użytkownika i planety dla zalogowanych użytkowników
    $session = Session::load();
    
    if ($session->isValidSession()) {
        // Załadowanie danych użytkownika
        $sql = "SELECT * FROM %%USERS%% WHERE id = :userId;";
        $USER = Database::get()->selectSingle($sql, [
            ':userId' => $session->userId
        ]);
        
        if (empty($USER)) {
            // Sesja jest nieprawidłowa - użytkownik nie istnieje
            $session->delete();
            HTTP::redirectTo('index.php');
            exit;
        }
        
        // Załadowanie aktywnej planety użytkownika
        $sql = "SELECT * FROM %%PLANETS%% WHERE id = :planetId;";
        $PLANET = Database::get()->selectSingle($sql, [
            ':planetId' => $session->planetId
        ]);
        
        if (empty($PLANET)) {
            // Planeta nie istnieje - wybierz pierwszą dostępną
            $sql = "SELECT * FROM %%PLANETS%% WHERE id_owner = :userId AND planet_type = 1 ORDER BY id ASC LIMIT 1;";
            $PLANET = Database::get()->selectSingle($sql, [
                ':userId' => $USER['id']
            ]);
            
            if (empty($PLANET)) {
                // Użytkownik nie ma planet - przekieruj do strony logowania
                $session->delete();
                HTTP::redirectTo('index.php');
                exit;
            }
            
            // Aktualizacja aktywnej planety w sesji
            $session->planetId = $PLANET['id'];
        }
        
        // Aktualizacja ostatniej aktywności użytkownika
        $sql = "UPDATE %%USERS%% SET 
                onlinetime = :timestamp,
                user_lastip = :userIp
                WHERE id = :userId;";
        
        Database::get()->update($sql, [
            ':timestamp' => TIMESTAMP,
            ':userIp' => $_SERVER['REMOTE_ADDR'],
            ':userId' => $USER['id']
        ]);
        
        // Ustawienie języka użytkownika
        $LNG = getLanguage($USER['lang']);
    } else {
        // Brak ważnej sesji - przekieruj do strony logowania
        HTTP::redirectTo('index.php');
        exit;
    }
}

// Inicjalizacja Composer Autoload (jeśli istnieje)
if (file_exists(VENDOR_PATH . 'autoload.php')) {
    require VENDOR_PATH . 'autoload.php';
}

// Zabezpieczenie przed manipulacją tablicą GLOBALS
if (isset($_POST['GLOBALS']) || isset($_GET['GLOBALS'])) {
    exit('Nie można ustawić tablicy GLOBALS z zewnątrz skryptu.');
}

// Ładowanie Composer Autoloader, jeśli istnieje
$composerAutoloader = __DIR__ . '/../vendor/autoload.php';
if (file_exists($composerAutoloader)) {
    require $composerAutoloader;
}

// Ustawienie kodowania UTF-8 dla funkcji wielobajtowych
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding("UTF-8");
}

// Konfiguracja środowiska PHP
ignore_user_abort(true); // Kontynuuj wykonywanie skryptu nawet jeśli użytkownik przerwał połączenie
error_reporting(E_ALL & ~E_STRICT); // Raportuj wszystkie błędy oprócz ścisłej zgodności

// Ustawienie strefy czasowej - zabezpieczenie na wypadek nieprawidłowej konfiguracji
date_default_timezone_set(@date_default_timezone_get());

// Konfiguracja wyświetlania błędów i logowania
ini_set('display_errors', '1');
ini_set('log_errors', 'On');
ini_set('error_log', 'includes/error.log');

// Ustawienie nagłówka Content-Type
header('Content-Type: text/html; charset=UTF-8');

// Definicja stałej z aktualnym znacznikiem czasu
define('TIMESTAMP', time());

// Załadowanie stałych systemowych
require 'includes/constants.php';

// Załadowanie funkcji ogólnych i konfiguracja obsługi błędów
require 'includes/GeneralFunctions.php';
set_exception_handler('exceptionHandler');
set_error_handler('errorHandler');

// Załadowanie klas podstawowych
require 'includes/classes/ArrayUtil.class.php';
require 'includes/classes/Cache.class.php';
require 'includes/classes/Database.class.php';
require 'includes/classes/Config.class.php';
require 'includes/classes/class.FleetFunctions.php';
require 'includes/classes/HTTP.class.php';
require 'includes/classes/Language.class.php';
require 'includes/classes/PlayerUtil.class.php';
require 'includes/classes/Session.class.php';
require 'includes/classes/Universe.class.php';

// Załadowanie klas interfejsu użytkownika
require 'includes/classes/class.theme.php';
require 'includes/classes/class.template.php';

// Załadowanie klasy do obsługi komunikatów
require 'includes/classes/class.Flash.php';

// Ustawienie nagłówka P3P dla obsługi ciasteczek stron trzecich
HTTP::sendHeader('P3P', 'CP="IDC DSP COR ADM DEVi TAIi PSA PSD IVAi IVDi CONi HIS OUR IND CNT"');

// Definicja stałej do sprawdzania, czy żądanie jest AJAX-owe
define('AJAX_REQUEST', HTTP::_GP('ajax', 0));

// Inicjalizacja obiektu motywu
$THEME = new Theme();

// Jeśli to tryb instalacji, zakończ tutaj
if (MODE === 'INSTALL') {
    return;
}

// Sprawdź, czy plik konfiguracyjny istnieje
if (!file_exists('includes/config.php') || filesize('includes/config.php') === 0) {
    HTTP::redirectTo('install/index.php');
}

// Sprawdź, czy wymagana jest aktualizacja bazy danych
try {
    $sql = "SELECT dbVersion FROM %%SYSTEM%%;";
    $dbVersion = Database::get()->selectSingle($sql, [], 'dbVersion');
    $dbNeedsUpgrade = $dbVersion < DB_VERSION_REQUIRED;
} catch (Exception $e) {
    $dbNeedsUpgrade = true;
}

if ($dbNeedsUpgrade) {
    HTTP::redirectTo('install/index.php?mode=upgrade');
}

// Obsługa kompatybilności wstecznej dla starego panelu administracyjnego
if (defined('DATABASE_VERSION') && DATABASE_VERSION === 'OLD') {
    require 'includes/classes/Database_BC.class.php';
    $DATABASE = new Database_BC();
    
    $dbTableNames = Database::get()->getDbTableNames();
    $dbTableNames = array_combine($dbTableNames['keys'], $dbTableNames['names']);
    
    foreach ($dbTableNames as $dbAlias => $dbName) {
        define(substr($dbAlias, 2, -2), $dbName);
    }    
}

// Pobierz konfigurację i ustaw strefę czasową
$config = Config::get();
date_default_timezone_set($config->timezone);

// Obsługa trybów wymagających zalogowania (INGAME, ADMIN, CRON)
if (MODE === 'INGAME' || MODE === 'ADMIN' || MODE === 'CRON') {
    // Ładowanie sesji użytkownika
    $session = Session::load();

    // Sprawdź, czy sesja jest ważna
    if (!$session->isValidSession()) {
        $session->delete();
        HTTP::redirectTo('index.php?code=3'); // Kod 3 - błąd sesji
    }

    // Ładowanie dodatkowych plików potrzebnych w trybie gry
    require 'includes/vars.php';
    require 'includes/classes/class.BuildFunctions.php';
    require 'includes/classes/class.PlanetRessUpdate.php';
    
    // Obsługa zdarzeń floty (jeśli moduł jest dostępny)
    if (!AJAX_REQUEST && MODE === 'INGAME' && isModuleAvailable(MODULE_FLEET_EVENTS)) {
        require('includes/FleetHandler.php');
    }
    
    // Pobieranie danych użytkownika wraz z licznikiem nieprzeczytanych wiadomości
    $db = Database::get();
    $sql = "SELECT 
    user.*,
    COUNT(message.message_id) as messages
    FROM %%USERS%% as user
    LEFT JOIN %%MESSAGES%% as message ON message.message_owner = user.id AND message.message_unread = :unread
    WHERE user.id = :userId
    GROUP BY message.message_owner;";
    
    $USER = $db->selectSingle($sql, [
        ':unread'  => 1,
        ':userId'  => $session->userId
    ]);
    
    // Sprawdzenie, czy użytkownik istnieje
    if (empty($USER)) {
        HTTP::redirectTo('index.php?code=3');
    }
    
    // Inicjalizacja obiektu języka
    $LNG = new Language($USER['lang']);
    $LNG->includeData(['L18N', 'INGAME', 'TECH', 'CUSTOM']);
    
    // Ustawienie motywu użytkownika
    $THEME->setUserTheme($USER['dpath']);
    
    // Sprawdzenie, czy gra jest wyłączona (dla zwykłych użytkowników)
    if ($config->game_disable == 0 && $USER['authlevel'] == AUTH_USR) {
        ShowErrorPage::printError($LNG['sys_closed_game'] . '<br><br>' . $config->close_reason, false);
    }

    // Sprawdzenie, czy użytkownik jest zbanowany
    if ($USER['bana'] == 1) {
        ShowErrorPage::printError(
            "<font size=\"6px\">" . $LNG['css_account_banned_message'] . "</font><br><br>" . 
            sprintf($LNG['css_account_banned_expire'], _date($LNG['php_tdformat'], $USER['banaday'], $USER['timezone'])) . 
            "<br><br>" . $LNG['css_goto_homeside'], 
            false
        );
    }
    
    // Dodatkowe operacje w trybie gry
    if (MODE === 'INGAME') {
        // Sprawdzenie, czy użytkownik jest we właściwym wszechświecie
        $universeAmount = count(Universe::availableUniverses());
        if (Universe::current() != $USER['universe'] && $universeAmount > 1) {
            HTTP::redirectToUniverse($USER['universe']);
        }

        // Wybierz aktywną planetę użytkownika
        $session->selectActivePlanet();

        // Pobierz dane planety
        $sql = "SELECT * FROM %%PLANETS%% WHERE id = :planetId;";
        $PLANET = $db->selectSingle($sql, [
            ':planetId' => $session->planetId,
        ]);

        // Jeśli wybrana planeta nie istnieje, spróbuj pobrać główną planetę
        if (empty($PLANET)) {
            $sql = "SELECT * FROM %%PLANETS%% WHERE id = :planetId;";
            $PLANET = $db->selectSingle($sql, [
                ':planetId' => $USER['id_planet'],
            ]);
            
            // Jeśli główna planeta również nie istnieje - błąd krytyczny
            if (empty($PLANET)) {
                throw new Exception("Główna planeta nie istnieje!");
            } else {
                $session->planetId = $USER['id_planet'];
            }
        }
        
        // Pobierz współczynniki i listę planet użytkownika
        $USER['factor'] = getFactors($USER);
        $USER['PLANETS'] = getPlanets($USER);
    }
    // Dodatkowe operacje w trybie administracyjnym
    elseif (MODE === 'ADMIN') {
        // Raportowanie błędów w trybie administratora
        error_reporting(E_ERROR | E_WARNING | E_PARSE);
        
        // Deserializacja praw użytkownika
        $USER['rights'] = unserialize($USER['rights']);
        
        // Ładowanie dodatkowych plików językowych
        $LNG->includeData(['ADMIN', 'CUSTOM']);
    }
}
// Obsługa trybu logowania
elseif (MODE === 'LOGIN') {
    // Inicjalizacja obiektu języka na podstawie języka przeglądarki
    $LNG = new Language();
    $LNG->getUserAgentLanguage();
    $LNG->includeData(['L18N', 'INGAME', 'PUBLIC', 'CUSTOM']);
}
// Obsługa trybu czatu
elseif (MODE === 'CHAT') {
    // Ładowanie sesji użytkownika
    $session = Session::load();

    // Sprawdzenie, czy sesja jest ważna
    if (!$session->isValidSession()) {
        HTTP::redirectTo('index.php?code=3');
    }
}
