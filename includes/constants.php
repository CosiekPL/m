<?php
// KONFIGURACJA DOMYŚLNYCH USTAWIEŃ SZABLONÓW

// Domyślny motyw strony ('star' w tym przypadku).
define('DEFAULT_THEME', 'star');

// Określa, czy używane jest połączenie HTTPS. Sprawdza, czy zmienna serwera HTTPS jest ustawiona i ma wartość 'on'.
define('HTTPS', isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == 'on');

// Definiuje protokół HTTP (http:// lub https://) na podstawie stałej HTTPS.
define('PROTOCOL', HTTPS ? 'https://' : 'http://');

// Sprawdza, czy skrypt jest uruchamiany z linii poleceń (CLI).
if (PHP_SAPI === 'cli') {
    // Jeśli skrypt jest uruchamiany z CLI, generuje URL na potrzeby debugowania.
    $requestUrl = str_replace(array(dirname(dirname(__FILE__)), '\\'), array('', '/'), $_SERVER["PHP_SELF"]);

    // Tryb debugowania - definicje ścieżek.
    define('HTTP_BASE', str_replace(array('\\', '//'), '/', dirname($_SERVER['SCRIPT_NAME']) . '/'));
    define('HTTP_ROOT', str_replace(basename($_SERVER['SCRIPT_FILENAME']), '', parse_url($requestUrl, PHP_URL_PATH)));

    define('HTTP_FILE', basename($_SERVER['SCRIPT_NAME']));
    define('HTTP_HOST', '127.0.0.1'); // Domyślny host dla CLI.
    define('HTTP_PATH', PROTOCOL . HTTP_HOST . HTTP_ROOT);
} else {
    // Jeśli skrypt jest uruchamiany przez serwer WWW.
    define('HTTP_BASE', str_replace(array('\\', '//'), '/', dirname($_SERVER['SCRIPT_NAME']) . '/'));
    define('HTTP_ROOT', str_replace(basename($_SERVER['SCRIPT_FILENAME']), '', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)));

    define('HTTP_FILE', basename($_SERVER['SCRIPT_NAME']));
    define('HTTP_HOST', $_SERVER['HTTP_HOST']); // Pobiera host z nagłówka HTTP.
    define('HTTP_PATH', PROTOCOL . HTTP_HOST . HTTP_ROOT);
}

// Definiuje ścieżkę do czatu AJAX, jeśli nie została jeszcze zdefiniowana.
if (!defined('AJAX_CHAT_PATH')) {
    define('AJAX_CHAT_PATH', ROOT_PATH . 'chat/');
}

// Definiuje ścieżkę do katalogu cache, jeśli nie została jeszcze zdefiniowana.
if (!defined('CACHE_PATH')) {
    define('CACHE_PATH', ROOT_PATH . 'cache/');
}

// Definiuje używany silnik walki ('FreeStar' w tym przypadku).
define('COMBAT_ENGINE', 'FreeStar');

// Ustawienie domyślnego języka na potrzeby obsługi błędów krytycznych.
define('DEFAULT_LANG', 'pl');

// Włączenie obsługi domen z wildcard (poddomen) dla uniwersów.
define('UNIS_WILDCAST', false);

// Liczba pól dodawanych na każdy poziom bazy księżycowej.
define('FIELDS_BY_MOONBASIS_LEVEL', 4);

// Liczba pól dodawanych na każdy poziom terraformera.
define('FIELDS_BY_TERRAFORMER', 8);

// Czas w sekundach, po którym nieaktywny gracz pojawia się na galaktyce jako (i).
define('INACTIVE', 604800); // 7 dni

// Czas w sekundach, po którym nieaktywny gracz pojawia się na galaktyce jako (i I).
define('INACTIVE_LONG', 2419200); // 28 dni

// Współczynnik opłaty za anulowanie budowy w stoczni (0.6 = 60% zwrotu surowców).
define('FACTOR_CANCEL_SHIPYARD', 0.7);

// Minimalny czas trwania lotu floty w sekundach.
define('MIN_FLEET_TIME', 5);

// Koszt deuteru za użycie falangi czujników.
define('PHALANX_DEUTERIUM', 5000);

// Czas w sekundach, po zmianie nicku, po którym można go ponownie zmienić.
define('USERNAME_CHANGETIME', 604800); // 7 dni

// Maksymalna liczba wyników na stronie wyszukiwania (-1 oznacza wyłączenie limitu).
define('SEARCH_LIMIT', 25);

// Liczba wiadomości wyświetlanych na jednej stronie listy wiadomości.
define('MESSAGES_PER_PAGE', 10);

// Liczba zbanowanych użytkowników wyświetlanych na jednej stronie listy banów.
define('BANNED_USERS_PER_PAGE', 25);

// Poziom sprawdzania bloków IP (1 = tylko pierwsza część, 2 = pierwsze dwie, 3 = pierwsze trzy).
define('COMPARE_IP_BLOCKS', 2);

// Maksymalna liczba rund w walce.
define('MAX_ATTACK_ROUNDS', 6);

// Włączenie linku do symulatora walki w raportach szpiegowskich.
define('ENABLE_SIMULATOR_LINK', true);

// Maksymalny czas trwania sesji użytkownika w sekundach (12 godzin).
define('SESSION_LIFETIME', 43200);

// Włączenie wielu alertów przy wysyłaniu flot.
define('ENABLE_MULTIALERT', true);

// Włączenie obsługi UTF-8 dla nazw (wymagane dla znaków innych niż angielskie).
define('UTF8_SUPPORT', true);

/*
    Definicja, jak trudniej jest szpiegować wszystkie informacje.
    Jeśli [Poziom technologii szpiegowskiej nadawcy] > [Poziom technologii szpiegowskiej celu]
        minimalna liczba szpiegów = -1 * (abs([Poziom technologii szpiegowskiej nadawcy] - [Poziom technologii szpiegowskiej celu]) * SPY_DIFFENCE_FACTOR) ^ 2;
    w przeciwnym razie
        minimalna liczba szpiegów = -1 * (abs([Poziom technologii szpiegowskiej nadawcy] - [Poziom technologii szpiegowskiej celu]) * SPY_DIFFENCE_FACTOR) ^ 2;
*/
define('SPY_DIFFENCE_FACTOR', 1);

/*
    Definicja, jak trudniej jest szpiegować wszystkie informacje.
    minimalna liczba szpiegów = patrz MissionCaseSpy.php#78

    Aby zobaczyć Flotę		= {minimalna liczba szpiegów}
    Aby zobaczyć Obronę		= {minimalna liczba szpiegów} + 1 * SPY_VIEW_FACTOR
    Aby zobaczyć Budynki	= {minimalna liczba szpiegów} + 3 * SPY_VIEW_FACTOR
    Aby zobaczyć Technologię	= {minimalna liczba szpiegów} + 5 * SPY_VIEW_FACTOR
*/
define('SPY_VIEW_FACTOR', 1);

// Ustawienia dotyczące "Bashowania" (prawdopodobnie intensywne ataki na słabszych graczy).
define('BASH_ON', false);      // Włączenie/wyłączenie reguł bashowania.
define('BASH_COUNT', 12);       // Maksymalna liczba ataków na jednego gracza w określonym czasie.
define('BASH_TIME', 86400);     // Okres czasu w sekundach (24 godziny) dla reguły bashowania.

// Reguła bashowania podczas wojen sojuszy:
// 0 = NORMAL - reguły bashowania obowiązują.
// 1 = ON WAR, BASH RULE IS DEACTIVE - reguły bashowania są wyłączone podczas wojny.
define('BASH_WAR', 0);

// Minimalny czas lotu floty musi być wyższy niż czas trwania reguły bashowania (komentarz sugeruje, ale nie jest to wymuszone kodem).
define('FLEETLOG_AGE', 86400); // Czas w sekundach (24 godziny) przechowywania logów flot.

// ID kont ROOT (administratorów).
define('ROOT_UNI', 1);    // ID uniwersum ROOT.
define('ROOT_USER', 1);   // ID użytkownika ROOT.

// Poziomy uprawnień (AUTHLEVEL).
define('AUTH_ADM', 3);    // Administrator.
define('AUTH_OPS', 2);    // Operator.
define('AUTH_MOD', 1);    // Moderator.
define('AUTH_USR', 0);    // Użytkownik.

// Moduły gry (identyfikatory modułów używane w systemie).
define('MODULE_AMOUNT', 43);            // Całkowita liczba modułów.
define('MODULE_ALLIANCE', 0);          // Sojusz.
define('MODULE_BANLIST', 21);           // Lista zbanowanych.
define('MODULE_BANNER', 37);            // Banery.
define('MODULE_BATTLEHALL', 12);       // Sala bitew.
define('MODULE_BUDDYLIST', 6);         // Lista znajomych.
define('MODULE_BUILDING', 2);          // Budynki.
define('MODULE_CHAT', 7);              // Czat.
define('MODULE_DMEXTRAS', 8);          // Dodatki za Ciemną Materię.
define('MODULE_FLEET_EVENTS', 10);     // Zdarzenia flot.
define('MODULE_FLEET_TABLE', 9);       // Tabela flot.
define('MODULE_FLEET_TRADER', 38);     // Handlarz flot.
define('MODULE_GALAXY', 11);           // Galaktyka.
define('MODULE_IMPERIUM', 15);         // Imperium.
define('MODULE_INFORMATION', 14);      // Informacje.
define('MODULE_MESSAGES', 16);         // Wiadomości.
define('MODULE_MISSILEATTACK', 40);   // Atak rakietowy.
define('MODULE_MISSION_ATTACK', 1);    // Misja: Atak.
define('MODULE_MISSION_ACS', 42);       // Misja: ACS (Atak Skoordynowany).
define('MODULE_MISSION_COLONY', 35);    // Misja: Kolonizacja.
define('MODULE_MISSION_DARKMATTER', 31); // Misja: Zdobądź Ciemną Materią.
define('MODULE_MISSION_DESTROY', 29);   // Misja: Zniszcz.
define('MODULE_MISSION_EXPEDITION', 30);// Misja: Ekspedycja.
define('MODULE_MISSION_HOLD', 33);      // Misja: Zatrzymaj.
define('MODULE_MISSION_RECYCLE', 32);   // Misja: Recykling.
define('MODULE_MISSION_SPY', 24);       // Misja: Szpieguj.
define('MODULE_MISSION_STATION', 36);   // Misja: Stacjonuj.
define('MODULE_MISSION_TRANSPORT', 34);// Misja: Transport.
define('MODULE_NOTICE', 17);           // Ogłoszenia.
define('MODULE_OFFICIER', 18);         // Oficerowie.
define('MODULE_PHALANX', 19);          // Falanga Czujników.
define('MODULE_PLAYERCARD', 20);       // Karta Gracza.
define('MODULE_RECORDS', 22);          // Rekordy.
define('MODULE_RESEARCH', 3);          // Badania.
define('MODULE_RESSOURCE_LIST', 23);    // Lista Surowców.
define('MODULE_SEARCH', 26);           // Wyszukiwanie.
define('MODULE_SHIPYARD_FLEET', 4);    // Stocznia: Flota.
define('MODULE_SHIPYARD_DEFENSIVE', 5);// Stocznia: Obrona.
define('MODULE_SHORTCUTS', 41);       // Skróty.
define('MODULE_SIMULATOR', 39);       // Symulator Walki.
define('MODULE_STATISTICS', 25);       // Statystyki.
define('MODULE_SUPPORT', 27);          // Support.
define('MODULE_TECHTREE', 28);         // Drzewko Technologii.
define('MODULE_TRADER', 13);           // Handlarz.

// Stan floty (FLEET STATE).
define('FLEET_OUTWARD', 0); // Flota wylatuje.
define('FLEET_RETURN', 1);  // Flota wraca.
define('FLEET_HOLD', 2);    // Flota stacjonuje.

// Flagi elementów (ELEMENT FLAGS) - identyfikują typ elementu (budynek, technologia, itp.).
define('ELEMENT_BUILD', 1);             // ID 0 - 99 (Budynki).
define('ELEMENT_TECH', 2);              // ID 101 - 199 (Technologie).
define('ELEMENT_FLEET', 4);             // ID 201 - 399 (Flota).
define('ELEMENT_DEFENSIVE', 8);         // ID 401 - 599 (Obrona).
define('ELEMENT_OFFICIER', 16);        // ID 601 - 699 (Oficerowie).
define('ELEMENT_BONUS', 32);           // ID 701 - 799 (Bonusy).
define('ELEMENT_RACE', 64);            // ID 801 - 899 (Rasy).
define('ELEMENT_PLANET_RESOURCE', 128); // ID 901 - 949 (Zasoby planet).
define('ELEMENT_USER_RESOURCE', 256);   // ID 951 - 999 (Zasoby użytkownika - Ciemna Materia).

// ... kolejne flagi bitowe (512, 1024, 2048, 4096, 8192, 16384, 32768) - brak opisów.

define('ELEMENT_PRODUCTION', 65536);      // Elementy produkcyjne (np. kopalnie).
define('ELEMENT_STORAGE', 131072);       // Elementy magazynujące (np. magazyny).
define('ELEMENT_ONEPERPLANET', 262144);   // Element, który może występować tylko raz na planecie.
define('ELEMENT_BOUNS', 524288);         // Błąd w pisowni - prawdopodobnie miało być 'BONUS'.
define('ELEMENT_BUILD_ON_PLANET', 1048576); // Element budowany na planetach.
define('ELEMENT_BUILD_ON_MOONS', 2097152);   // Element budowany na księżycach.
define('ELEMENT_RESOURCE_ON_TF', 4194304); // Zasób na Terraformatorze (prawdopodobnie błąd w nazwie).
define('ELEMENT_RESOURCE_ON_FLEET', 8388608);// Zasób transportowany przez flotę.
define('ELEMENT_RESOURCE_ON_STEAL', 16777216);// Zasób możliwy do kradzieży.

// Zgodność z PHP 8.4:
// Ten kod jest w pełni kompatybilny z PHP 8.4.
// Definiuje stałe, co jest standardową i bezpieczną praktyką w PHP.
// Należy jedynie upewnić się, że stałe takie jak 'shortCut
