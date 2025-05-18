<?php
declare(strict_types=1);

/**
 * Plik wczytywany przez wszystkie skrypty - zawiera podstawową konfigurację
 * Odpowiada za inicjalizację środowiska, wczytanie wymaganych klas i ustawienie
 * globalnych zmiennych
 */

// Wyświetlanie błędów (wyłączone w produkcji)
if (defined('DEBUG_MODE') && DEBUG_MODE === true) {
    error_reporting(E_ALL); // Raportuj wszystkie błędy, ostrzeżenia i notice.
    ini_set('display_errors', '1'); // Wyświetlaj błędy na ekranie.
} else {
    error_reporting(E_ERROR | E_WARNING | E_PARSE); // Raportuj tylko błędy krytyczne, ostrzeżenia i błędy składniowe.
    ini_set('display_errors', '0'); // Nie wyświetlaj błędów na ekranie w środowisku produkcyjnym.
}

// Definicja stałych dla różnych trybów misji flot
define('FLEET_OUTWARD', 0);   // Flota wylatuje.
define('FLEET_RETURN', 1);    // Flota wraca.
define('FLEET_HOLD', 2);      // Flota stacjonuje.
define('FLEET_END', 3);       // Flota zakończyła misję.
define('FLEET_DESTROYED', 4); // Flota została zniszczona.

// Ustawienie strefy czasowej na UTC (uniwersalny czas koordynowany).
date_default_timezone_set('UTC');

// Ustawienie czasu serwera (timestamp) jako stałej zawierającej aktualny czas uniksowy.
define('TIMESTAMP', time());

// Automatyczne ładowanie klas za pomocą funkcji anonimowej zarejestrowanej w spl_autoload_register.
spl_autoload_register(function ($class) {
    // Lista ścieżek do wyszukiwania plików klas.
    $paths = [
        'includes/classes/',
        'includes/classes/missions/',
        'includes/pages/',
        'includes/pages/game/',
        'includes/pages/adm/',
        'includes/pages/login/',
    ];

    // Dodanie rozszerzenia '.class.php' do nazwy klasy.
    $classFile = $class . '.class.php';

    // Wyszukiwanie pliku klasy w zdefiniowanych ścieżkach.
    foreach ($paths as $path) {
        if (file_exists($path . $classFile)) {
            require_once($path . $classFile); // Dołącz plik klasy, jeśli istnieje.
            return; // Zakończ działanie autoloadera po znalezieniu klasy.
        }
    }

    // Sprawdzenie, czy plik klasy istnieje z rozszerzeniem '.php' (dla klas, które nie mają '.class.php').
    foreach ($paths as $path) {
        if (file_exists($path . $class . '.php')) {
            require_once($path . $class . '.php'); // Dołącz plik klasy, jeśli istnieje.
            return; // Zakończ działanie autoloadera po znalezieniu klasy.
        }
    }
});

// Ścieżka do katalogu cache (jeśli nie została wcześniej zdefiniowana).
if (!defined('CACHE_PATH')) {
    define('CACHE_PATH', 'cache/');
}

// Zdefiniowanie stałych dla absolutnych ścieżek do katalogów projektu.
define('ROOT_PATH', dirname(__DIR__) . '/'); // Ścieżka do głównego katalogu projektu.
define('INCLUDES_PATH', ROOT_PATH . 'includes/'); // Ścieżka do katalogu 'includes'.
define('VENDOR_PATH', ROOT_PATH . 'vendor/'); // Ścieżka do katalogu 'vendor' (dla zależności Composer).

// Ładowanie konfiguracji z pliku config.php, jeśli ten plik istnieje w głównym katalogu projektu.
if (file_exists(ROOT_PATH . 'config.php')) {
    require ROOT_PATH . 'config.php';
}

// Określenie trybu działania aplikacji na podstawie parametru GET 'mode'.
$modeMapping = [
    'adm'     => 'ADMIN',   // Tryb administracyjny.
    'install' => 'INSTALL', // Tryb instalacji.
    'login'   => 'LOGIN',   // Tryb logowania.
    // Domyślnie tryb gry.
];

// Określenie trybu na podstawie parametru GET 'mode' (konwertowany do małych liter).
$mode = isset($_GET['mode']) ? strtolower($_GET['mode']) : '';
define('MODE', isset($modeMapping[$mode]) ? $modeMapping[$mode] : 'GAME'); // Jeśli 'mode' pasuje do mapowania, użyj zdefiniowanej stałej, w przeciwnym razie ustaw domyślny tryb 'GAME'.

// Sprawdzenie, czy bieżące żądanie jest typu AJAX (za pomocą nagłówka HTTP 'X-Requested-With').
define('AJAX_REQUEST', isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

// Inicjalizacja globalnych obiektów, które będą używane w różnych częściach aplikacji.
$LNG = null;     // Obiekt odpowiedzialny za obsługę języka.
$THEME = null;    // Obiekt odpowiedzialny za obsługę motywu graficznego.
$USER = null;     // Obiekt przechowujący dane zalogowanego użytkownika.
$PLANET = null;   // Obiekt przechowujący dane aktualnie wybranej planety.

// Inicjalizacja sesji PHP, która umożliwia przechowywanie danych między żądaniami użytkownika.
session_start();

// Funkcje pomocnicze

/**
 * Formatuje liczbę w przyjazny dla użytkownika sposób, z opcjonalnym kolorowaniem.
 *
 * @param float|int $number Liczba do sformatowania.
 * @param bool      $color  Czy kolorować liczbę (zielony dla dodatnich, czerwony dla ujemnych). Domyślnie true.
 *
 * @return string Sformatowana liczba.
 */
function pretty_number(float|int $number, bool $color = true): string
{
    if (!is_numeric($number)) {
        return $number; // Zwróć wartość bez zmian, jeśli nie jest liczbą.
    }

    if ($color) {
        // Jeśli $color jest true, koloruj liczbę.
        if ($number > 0) {
            return '<span style="color:green">+' . number_format($number, 0, ',', '.') . '</span>'; // Dodatnia - zielona z plusem.
        } elseif ($number < 0) {
            return '<span style="color:red">' . number_format($number, 0, ',', '.') . '</span>';   // Ujemna - czerwona.
        }
    }

    // Bez kolorowania - formatuj liczbę z separatorem tysięcy i dziesiętnym.
    return number_format($number, 0, ',', '.');
}

/**
 * Sprawdza, czy moduł o podanym ID jest dostępny dla aktualnego użytkownika.
 *
 * @param int $moduleID ID modułu do sprawdzenia.
 *
 * @return bool True, jeśli moduł jest dostępny, false w przeciwnym razie.
 */
function isModuleAvailable(int $moduleID): bool
{
    global $USER; // Dostęp do globalnej zmiennej zawierającej dane użytkownika.

    if (isset($USER['rights']) && ($USER['rights'] & 1) === 1) {
        // Jeśli użytkownik ma ustawiony bit uprawnień administratora (prawdopodobnie bit 0).
        return true; // Administrator ma dostęp do wszystkich modułów.
    }

    // Pobieranie dostępnych modułów z konfiguracji.
    $config = Config::get(); // Pobierz obiekt konfiguracji.
    $modules = explode(';', $config->moduls); // Rozdziel listę ID modułów po średniku.

    return in_array($moduleID, $modules, true); // Sprawdź, czy podany ID modułu znajduje się na liście dostępnych modułów (strict comparison).
}

/**
 * Generuje unikalny identyfikator w formacie MD5.
 *
 * @return string Unikalny identyfikator.
 */
function getUniqueIdentifier(): string
{
    return md5(uniqid((string)mt_rand(), true)); // Generuj unikalny ID na podstawie czasu, losowej liczby i unikalności, a następnie zakoduj go w MD5.
}

/**
 * Ładuje klasę języka Language na podstawie preferencji użytkownika, parametrów żądania lub ustawień przeglądarki.
 *
 * @param string|null $language Opcjonalny kod języka do załadowania bezpośrednio.
 *
 * @return Language Obiekt klasy Language.
 */
function getLanguage(?string $language = null): Language
{
    global $USER; // Dostęp do globalnej zmiennej zawierającej dane użytkownika.

    if ($language !== null) {
        return new Language($language); // Załaduj język na podstawie podanego kodu.
    }

    if (isset($USER['lang']) && !empty($USER['lang'])) {
        return new Language($USER['lang']); // Załaduj język preferowany przez zalogowanego użytkownika.
    }

    if (isset($_REQUEST['lang']) && !empty($_REQUEST['lang'])) {
        return new Language($_REQUEST['lang']); // Załaduj język przekazany w parametrze GET lub POST.
    }

    // Pobieranie języka z nagłówka Accept-Language przeglądarki.
    if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        $browserLang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2); // Pobierz pierwsze dwa znaki (np. 'pl', 'en').
        if (Language::exists($browserLang)) {
            return new Language($browserLang); // Załaduj język przeglądarki, jeśli jest dostępny.
        }
    }

    // Domyślny język (jeśli nie znaleziono preferencji).
    return new Language();
}

// Ładowanie języka - tworzy globalny obiekt $LNG klasy Language.
$LNG = getLanguage();

// Ładowanie motywu - tworzy globalny obiekt $THEME klasy Theme.
$THEME = new Theme();

// Inicjalizacja dodatkowych komponentów w zależności od trybu (gry lub administracji).
if (MODE === 'GAME' || MODE === 'ADMIN') {
    // Inicjalizacja danych użytkownika i planety dla zalogowanych użytkowników.
    $session = Session::load(); // Załaduj dane sesji użytkownika.

    if ($session->isValidSession()) {
        // Jeśli sesja jest ważna.
        // Załadowanie danych użytkownika z bazy danych na podstawie ID użytkownika z sesji.
        $sql = "SELECT * FROM %%USERS%% WHERE id = :userId;";
        $USER = Database::get()->selectSingle($sql, [
            ':userId' => $session->userId
        ]);

        if (empty($USER)) {
            // Jeśli nie znaleziono użytkownika o podanym ID (sesja jest nieprawidłowa).
            $session->delete(); // Usuń nieprawidłową sesję.
            HTTP::redirectTo('index.php'); // Przekieruj na stronę logowania.
            exit; // Zakończ wykonywanie skryptu.
        }

        // Załadowanie aktywnej planety użytkownika z bazy danych na podstawie ID planety z sesji.
        $sql = "SELECT * FROM %%PLANETS%% WHERE id = :planetId;";
        $PLANET = Database::get()->selectSingle($sql, [
            ':planetId' => $session->planetId
        ]);

        if (empty($PLANET)) {
            // Jeśli nie znaleziono planety o podanym ID (planeta nie istnieje).
            // Wybierz pierwszą planetę użytkownika typu 'planeta' (planet_type = 1) z bazy danych.
            $sql = "SELECT * FROM %%PLANETS%% WHERE id_owner = :userId AND planet_type = 1 ORDER BY id ASC LIMIT 1;";
            $PLANET = Database::get()->selectSingle($sql, [
                ':userId' => $USER['id']
            ]);

            if (empty($PLANET)) {
                // Jeśli użytkownik nie ma żadnych planet.
                $session->delete(); // Usuń sesję.
                HTTP::redirectTo('index.php'); // Przekieruj na stronę logowania.
                exit; // Zakończ wykonywanie skryptu.
            }

            // Aktualizacja ID aktywnej planety w sesji na nowo wybraną planetę.
            $session->planetId = $PLANET['id'];
        }

        // Aktualizacja informacji o ostatniej aktywności użytkownika w bazie danych.
        $sql = "UPDATE %%USERS%% SET
                    onlinetime = :timestamp,
                    user_lastip = :userIp
                    WHERE id = :userId;";

        Database::get()->update($sql, [
            ':timestamp' => TIMESTAMP, // Aktualny czas serwera.
            ':userIp'    => $_SERVER['REMOTE_ADDR'], // Adres IP użytkownika.
            ':userId'    => $USER['id'] // ID zalogowanego użytkownika.
        ]);

        // Ustawienie globalnego obiektu języka ($LNG) na język preferowany przez użytkownika.
        $LNG = getLanguage($USER['lang']);
    } else {
        // Jeśli brak ważnej sesji.
        HTTP::redirectTo('index.php'); // Przekieruj na stronę logowania.
        exit; // Zakończ wykonywanie skryptu.
    }
}

// Inicjalizacja Composer Autoload (jeśli plik autoload.php istnieje w katalogu vendor).
if (file_exists(VENDOR_PATH . 'autoload.php')) {
    require VENDOR_PATH . 'autoload.php';
}

// Zabezpieczenie przed potencjalną manipulacją tablicą GLOBALS poprzez POST lub GET.
if (isset($_POST['GLOBALS']) || isset($_GET['GLOBALS'])) {
    exit('Nie można ustawić tablicy GLOBALS z zewnątrz skryptu.');
}

// Ładowanie Composer Autoloader (alternatywna ścieżka), jeśli istnieje.
$composerAutoloader = __DIR__ . '/../vendor/autoload.php';
if (file_exists($composerAutoloader)) {
    require $composerAutoloader;
}

// Ustawienie kodowania znaków UTF-8 dla funkcji wielobajtowych, jeśli są dostępne.
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding("UTF-8");
}

// Konfiguracja środowiska PHP

ignore_user_abort(true); // Kontynuuj wykonywanie skryptu nawet jeśli użytkownik przerwał połączenie.
error_reporting(E_ALL & ~E_STRICT); // Raportuj wszystkie błędy oprócz ścisłej zgodności (zalecane w starszych wersjach PHP).

// Ustawienie strefy czasowej - zabezpieczenie na wypadek nieprawidłowej konfiguracji.
date_default_timezone_set(@date_default_timezone_get()); // Ustaw strefę czasową na podstawie ustawień serwera, ignorując potencjalne błędy.

// Konfiguracja wyświetlania błędów i logowania.
ini_set('display_errors', '1'); // Wyświetlaj błędy na ekranie (zazwyczaj w celach debugowania).
ini_set('log_errors', 'On');    // Włącz logowanie błędów.
ini_set('error_log', 'includes/error.log'); // Ścieżka do pliku, w którym będą zapisywane błędy.

// Ustawienie nagłówka Content-Type, informującego przeglądarkę o typie zawartości i kodowaniu znaków.
header('Content-Type: text/html; charset=UTF-8');

// Definicja stałej z aktualnym znacznikiem czasu (liczba sekund od Epoki Unix).
define('TIMESTAMP', time());

// Załadowanie stałych systemowych z pliku includes/constants.php.
require 'includes/constants.php';

// Załadowanie funkcji ogólnych i konfiguracja obsługi błędów.
require 'includes/GeneralFunctions.php'; // Zawiera różne pomocnicze funkcje.
set_exception_handler('exceptionHandler'); // Ustawia niestandardową funkcję do obsługi nie przechwyconych wyjątków.
set_error_handler('errorHandler');       // Ustawia niestandardową funkcję do obsługi błędów PHP.

// Załadowanie klas podstawowych.
require 'includes/classes/ArrayUtil.class.php';      // Klasa do pracy z tablicami.
require 'includes/classes/Cache.class.php';         // Klasa do obsługi pamięci podręcznej.
require 'includes/classes/Database.class.php';      // Klasa do obsługi bazy danych.
require 'includes/classes/Config.class.php';        // Klasa do zarządzania konfiguracją.
require 'includes/classes/class.FleetFunctions.php'; // Klasa z funkcjami związanymi z flotami.
require 'includes/classes/HTTP.class.php';          // Klasa do obsługi żądań i odpowiedzi HTTP.
require 'includes/classes/Language.class.php';      // Klasa do obsługi języków.
require 'includes/classes/PlayerUtil.class.php';    // Klasa z narzędziami dla graczy.
require 'includes/classes/Session.class.php';       // Klasa do obsługi sesji użytkowników.
require 'includes/classes/Universe.class.php';      // Klasa do zarządzania uniwersum gry.

// Załadowanie klas interfejsu użytkownika.
require 'includes/classes/Theme.class.php';        // Klasa do obsługi motywów graficznych.
require 'includes/classes/Template.class.php';     // Klasa do obsługi szablonów.

// Załadowanie klasy do obsługi komunikatów typu flash (jednorazowe powiadomienia).
require 'includes/classes/class.Flash.php';

// Ustawienie nagłówka P3P dla obsługi ciasteczek stron trzecich (może być istotne dla osadzonych iframe).
HTTP::sendHeader('P3P', 'CP="IDC DSP COR ADM DEVi TAIi PSA PSD IVAi IVDi CONi HIS OUR IND CNT"');

// Definicja stałej do sprawdzania, czy bieżące żądanie jest AJAX-owe.
define('AJAX_REQUEST', HTTP::_GP('ajax', 0)); // Sprawdza, czy parametr 'ajax' jest ustawiony w GET lub POST.

// Inicjalizacja obiektu motywu.
$THEME = new Theme();

// Jeśli to tryb instalacji, zakończ tutaj, aby nie wykonywać dalszej logiki gry.
if (MODE === 'INSTALL') {
    return;
}

// Sprawdź, czy plik konfiguracyjny (includes/config.php) istnieje i nie jest pusty.
if (!file_exists('includes/config.php') || filesize('includes/config.php') === 0) {
    HTTP::redirectTo('install/index.php'); // Jeśli brak lub pusty, przekieruj do instalatora.
}

// Sprawdź, czy wymagana jest aktualizacja bazy danych.
try {
    $sql = "SELECT dbVersion FROM %%SYSTEM%%;"; // Zapytanie SQL do pobrania wersji bazy danych.
    $dbVersion = Database::get()->selectSingle($sql, [], 'dbVersion'); // Wykonaj zapytanie i pobierz wartość 'dbVersion'.
    $dbNeedsUpgrade = $dbVersion < DB_VERSION_REQUIRED; // Porównaj aktualną wersję z wymaganą.
} catch (Exception $e) {
    // W przypadku błędu (np. tabela %%SYSTEM%% nie istnieje), załóż, że wymagana jest aktualizacja.
    $dbNeedsUpgrade = true;
}

if ($dbNeedsUpgrade) {
    HTTP::redirectTo('install/index.php?mode=upgrade'); // Jeśli baza danych wymaga aktualizacji, przekieruj do skryptu aktualizującego.
}

// Obsługa kompatybilności wstecznej dla starego panelu administracyjnego (jeśli zdefiniowano stałą DATABASE_VERSION jako 'OLD').
if (defined('DATABASE_VERSION') && DATABASE_VERSION === 'OLD') {
    require 'includes/classes/Database_BC.class.php'; // Załaduj klasę bazy danych dla kompatybilności wstecznej.
    $DATABASE = new Database_BC(); // Utwórz instancję starej klasy bazy danych.

    $dbTableNames = Database::get()->getDbTableNames(); // Pobierz nazwy tabel z nowej klasy bazy danych.
    $dbTableNames = array_combine($dbTableNames['keys'], $dbTableNames['names']); // Połącz aliasy tabel z ich rzeczywistymi nazwami.

    foreach ($dbTableNames as $dbAlias => $dbName) {
        define(substr($dbAlias, 2, -2), $dbName); // Zdefiniuj stałe dla nazw tabel (prawdopodobnie w starym formacie).
    }
}

// Pobierz konfigurację systemu.
$config = Config::get();

// Ustaw strefę czasową PHP na podstawie konfiguracji systemu.
date_default_timezone_set($config->timezone);

// Obsługa trybów wymagających zalogowania użytkownika (gra, panel administracyjny, zadania cron).
if (MODE === 'INGAME' || MODE === 'ADMIN' || MODE === 'CRON') {
    // Załaduj dane sesji użytkownika.
    $session = Session::load();

    // Sprawdź, czy sesja jest ważna.
    if (!$session->isValidSession()) {
        $session->delete(); // Usuń nieprawidłową sesję.
        HTTP::redirectTo('index.php?code=3'); // Przekieruj na stronę logowania z kodem błędu sesji.
    }

    // Załaduj dodatkowe pliki wymagane w trybie gry.
    require 'includes/vars.php';             // Zawiera różne zmienne globalne związane z grą.
    require 'includes/classes/class.BuildFunctions.php'; // Klasa z funkcjami budowania.
    require 'includes/classes/class.PlanetRessUpdate.php'; // Klasa do aktualizacji zasobów na planetach.

    // Obsługa zdarzeń floty (tylko w trybie gry i jeśli moduł zdarzeń floty jest dostępny oraz nie jest to żądanie AJAX).
    if (!AJAX_REQUEST && MODE === 'INGAME' && isModuleAvailable(MODULE_FLEET_EVENTS)) {
        require('includes/FleetHandler.php'); // Plik obsługujący ruch i akcje flot.
    }

    // Pobierz dane zalogowanego użytkownika wraz z liczbą nieprzeczytanych wiadomości.
    $db = Database::get(); // Pobierz instancję bazy danych.
    $sql = "SELECT
        user.*,
        COUNT(message.message_id) as messages
        FROM %%USERS%% as user
        LEFT JOIN %%MESSAGES%% as message ON message.message_owner = user.id AND message.message_unread = :unread
        WHERE user.id = :userId
        GROUP BY message.message_owner;";

    $USER = $db->selectSingle($sql, [
        ':unread' => 1,             // Tylko nieprzeczytane wiadomości.
        ':userId' => $session->userId // ID zalogowanego użytkownika.
    ]);

    // Sprawdź, czy użytkownik o podanym ID istnieje w bazie danych.
    if (empty($USER)) {
        HTTP::redirectTo('index.php?code=3'); // Przekieruj na stronę logowania, jeśli użytkownik nie istnieje.
    }

    // Inicjalizacja globalnego obiektu języka ($LNG) na podstawie preferencji użytkownika.
    $LNG = new Language($USER['lang']);
    $LNG->includeData(['L18N', 'INGAME', 'TECH', 'CUSTOM']); // Załaduj dane językowe z różnych plików.

    // Ustaw motyw użytkownika na podstawie jego ustawień w bazie danych.
    $THEME->setUserTheme($USER['dpath']);

    // Sprawdź, czy gra jest wyłączona dla zwykłych użytkowników.
    if ($config->game_disable == 0 && $USER['authlevel'] == AUTH_USR) {
        ShowErrorPage::printError($LNG['sys_closed_game'] . '<br><br>' . $config->close_reason, false); // Wyświetl stronę błędu o wyłączonej grze.
    }

    // Sprawdź, czy użytkownik jest zbanowany.
    if ($USER['bana'] == 1) {
        ShowErrorPage::printError(
            "<font size=\"6px\">" . $LNG['css_account_banned_message'] . "</font><br><br>" .
            sprintf($LNG['css_account_banned_expire'], _date($LNG['php_tdformat'], $USER['banaday'], $USER['timezone'])) .
            "<br><br>" . $LNG['css_goto_homeside'],
            false
        ); // Wyświetl stronę błędu o zbanowanym koncie.
    }
}
// Dodatkowe operacje w trybie gry.
if (MODE === 'INGAME') {
    // Sprawdzenie, czy zalogowany użytkownik znajduje się we właściwym wszechświecie.
    $universeAmount = count(Universe::availableUniverses()); // Pobierz liczbę dostępnych wszechświatów.
    if (Universe::current() !== $USER['universe'] && $universeAmount > 1) {
        HTTP::redirectToUniverse($USER['universe']); // Przekieruj użytkownika do jego wszechświata, jeśli jest ich więcej niż jeden i aktualny się nie zgadza.
    }

    // Wybierz aktywną planetę użytkownika na podstawie ID zapisanego w sesji.
    $session->selectActivePlanet();

    // Pobierz dane aktywnej planety z bazy danych.
    $db = Database::get(); // Pobierz instancję bazy danych.
    $sql = "SELECT * FROM %%PLANETS%% WHERE id = :planetId;";
    $PLANET = $db->selectSingle($sql, [
        ':planetId' => $session->planetId,
    ]);

    // Jeśli wybrana planeta nie istnieje (np. została usunięta), spróbuj pobrać planetę główną użytkownika.
    if (empty($PLANET)) {
        $sql = "SELECT * FROM %%PLANETS%% WHERE id = :planetId;";
        $PLANET = $db->selectSingle($sql, [
            ':planetId' => $USER['id_planet'],
        ]);

        // Jeśli główna planeta również nie istnieje - wyrzuć wyjątek krytyczny.
        if (empty($PLANET)) {
            throw new Exception("Główna planeta nie istnieje!");
        } else {
            // Jeśli główna planeta istnieje, ustaw ją jako aktywną w sesji.
            $session->planetId = $USER['id_planet'];
        }
    }

    // Pobierz różne współczynniki wpływające na rozgrywkę dla użytkownika.
    $USER['factor'] = getFactors($USER);

    // Pobierz listę planet należących do użytkownika.
    $USER['PLANETS'] = getPlanets($USER);
}
// Dodatkowe operacje w trybie administracyjnym.
elseif (MODE === 'ADMIN') {
    // Włącz raportowanie błędów na poziomie E_ERROR, E_WARNING i E_PARSE dla administratorów.
    error_reporting(E_ERROR | E_WARNING | E_PARSE);

    // Deserializuj ciąg znaków zawierający prawa administratora do tablicy.
    $USER['rights'] = unserialize($USER['rights']);

    // Załaduj dodatkowe pliki językowe specyficzne dla panelu administracyjnego.
    $LNG->includeData(['ADMIN', 'CUSTOM']);
}
// Obsługa trybu logowania.
elseif (MODE === 'LOGIN') {
    // Inicjalizacja obiektu języka na podstawie preferowanego języka przeglądarki.
    $LNG = new Language();
    $LNG->getUserAgentLanguage(); // Próbuje wykryć język przeglądarki.
    $LNG->includeData(['L18N', 'INGAME', 'PUBLIC', 'CUSTOM']); // Załaduj podstawowe i publiczne dane językowe.
}
// Obsługa trybu czatu.
elseif (MODE === 'CHAT') {
    // Załaduj dane sesji użytkownika.
    $session = Session::load();

    // Sprawdź, czy sesja jest ważna.
    if (!$session->isValidSession()) {
        HTTP::redirectTo('index.php?code=3'); // Przekieruj na stronę logowania z kodem błędu sesji.
    }
}
