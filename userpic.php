<?php

declare(strict_types=1);

/**
 * Generator banerów statystyk użytkownika
 * Tworzy graficzny baner z informacjami o graczu na podstawie ID
 * Obsługuje buforowanie po stronie przeglądarki za pomocą ETag
 */

// Definiowanie podstawowych stałych
define('MODE', 'BANNER'); // Tryb działania: BANNER dla generowania obrazków
define('ROOT_PATH', str_replace('\\', '/', dirname(__FILE__)) . '/'); // Ścieżka główna projektu
set_include_path(ROOT_PATH); // Ustawienie ścieżki wyszukiwania plików

// Sprawdzenie, czy rozszerzenie GD jest dostępne (wymagane do generowania obrazów)
if (!extension_loaded('gd')) {
    clearGIF(); // Jeśli nie, zwróć przezroczysty GIF
}

// Ładowanie wspólnych zasobów i klas
require 'includes/common.php';

// Pobranie ID użytkownika, dla którego ma zostać wygenerowany baner
$id = HTTP::_GP('id', 0);

// Sprawdzenie, czy moduł banerów jest dostępny i czy podano poprawne ID
if (!isModuleAvailable(MODULE_BANNER) || $id === 0) {
    clearGIF(); // Jeśli nie, zwróć przezroczysty GIF
}

// Inicjalizacja obiektu tłumaczeń
$LNG = new Language;
$LNG->getUserAgentLanguage(); // Wykrycie języka przeglądarki
$LNG->includeData(['L18N', 'BANNER', 'CUSTOM']); // Załadowanie potrzebnych tłumaczeń

// Załadowanie klasy generującej banery
require 'includes/classes/class.StatBanner.php';

// Utworzenie obiektu banera i pobranie danych gracza
$banner = new StatBanner();
$Data = $banner->GetData($id);

// Sprawdzenie, czy dane istnieją
if (!isset($Data) || !is_array($Data)) {
    clearGIF(); // Jeśli nie, zwróć przezroczysty GIF
}
    
// Generowanie znacznika ETag dla buforowania po stronie przeglądarki
$ETag = md5(implode('', $Data));
header('ETag: ' . $ETag);

// Sprawdzenie, czy baner jest w cache przeglądarki - jeśli tak, zwróć 304 Not Modified
if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] === $ETag) {
    HTTP::sendHeader('HTTP/1.0 304 Not Modified');
    exit;
}

// Generowanie i wyświetlanie banera
$banner->CreateUTF8Banner($Data);