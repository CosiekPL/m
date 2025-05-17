<?php

declare(strict_types=1);

/**
 * Plik wejściowy dla części logowania/rejestracji systemu 2moon2
 * Zarządza ładowaniem odpowiednich stron i kontrolerów
 */

// Definiowanie podstawowych stałych
define('MODE', 'LOGIN'); // Tryb działania: LOGIN dla części niezalogowanej
define('ROOT_PATH', str_replace('\\', '/', dirname(__FILE__)) . '/'); // Ścieżka główna projektu
set_include_path(ROOT_PATH); // Ustawienie ścieżki wyszukiwania plików

// Ładowanie podstawowych klas
require 'includes/pages/login/AbstractLoginPage.class.php';
require 'includes/pages/login/ShowErrorPage.class.php';
require 'includes/common.php';

/** @var Language $LNG */

// Pobieranie parametrów z żądania
$page = HTTP::_GP('page', 'index'); // Nazwa strony, domyślnie 'index'
$mode = HTTP::_GP('mode', 'show'); // Tryb działania, domyślnie 'show'

// Zabezpieczenie przed injekcją ścieżki
$page = str_replace(['_', '\\', '/', '.', "\0"], '', $page);

// Tworzenie nazwy klasy dla żądanej strony
$pageClass = 'Show' . ucfirst($page) . 'Page';

// Określenie ścieżki do pliku z klasą strony
$path = 'includes/pages/login/' . $pageClass . '.class.php';

// Sprawdzenie, czy plik istnieje
if (!file_exists($path)) {
    ShowErrorPage::printError($LNG['page_doesnt_exist']);
}

// Ładowanie pliku z klasą strony
// Docelowo należy zaimplementować automatyczne ładowanie klas
require($path);

// Tworzenie instancji obiektu strony
$pageObj = new $pageClass();

// Pobranie właściwości klasy używając funkcji PHP 8.4
$pageProps = get_class_vars($pageClass);

// Sprawdzenie, czy wymagany moduł jest dostępny
if (isset($pageProps['requireModule']) && $pageProps['requireModule'] !== 0 && !isModuleAvailable($pageProps['requireModule'])) {
    ShowErrorPage::printError($LNG['sys_module_inactive']);
}

// Sprawdzenie, czy żądana metoda (tryb) istnieje
if (!is_callable([$pageObj, $mode])) {
    // Jeśli nie, sprawdź domyślny kontroler
    if (!isset($pageProps['defaultController']) || !is_callable([$pageObj, $pageProps['defaultController']])) {
        ShowErrorPage::printError($LNG['page_doesnt_exist']);
    }
    // Użyj domyślnego kontrolera
    $mode = $pageProps['defaultController'];
}

// Wywołanie metody kontrolera
$pageObj->{$mode}();
