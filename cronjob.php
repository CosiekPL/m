<?php

declare(strict_types=1);

/**
 * System zadań cyklicznych (cronjob)
 * Uruchamiany za pomocą obrazka GIF osadzonego na stronie
 * Pozwala na wykonywanie cyklicznych zadań bez konieczności konfigurowania zadań cron na serwerze
 */

// Definiowanie podstawowych stałych
define('MODE', 'CRON'); // Tryb działania: CRON dla zadań cyklicznych
define('ROOT_PATH', str_replace('\\', '/', dirname(__FILE__)) . '/'); // Ścieżka główna projektu
set_include_path(ROOT_PATH); // Ustawienie ścieżki wyszukiwania plików

// Ładowanie wspólnych zasobów i klas
require 'includes/common.php';

// Ładowanie sesji użytkownika
$session = Session::load();

// Wysyłanie nagłówków dla przezroczystego obrazka GIF
// Ten obrazek jest używany do niewidocznego uruchamiania zadań cron w tle strony
HTTP::sendHeader('Cache-Control', 'no-cache');
HTTP::sendHeader('Content-Type', 'image/gif');
HTTP::sendHeader('Expires', '0');

// Dane binarnego obrazka 1x1 px przezroczystego GIF
echo("\x47\x49\x46\x38\x39\x61\x01\x00\x01\x00\x80\x00\x00\x00\x00\x00\x00\x00\x00\x21\xF9\x04\x01\x00\x00\x00\x00\x2C\x00\x00\x00\x00\x01\x00\x01\x00\x00\x02\x02\x44\x01\x00\x3B");

// Sprawdzenie, czy sesja jest ważna - bezpieczeństwo wykonywania zadań
if (!$session->isValidSession()) {
    exit;
}

// Pobranie ID zadania cron do wykonania
$cronjobID = HTTP::_GP('cronjobID', 0);

// Jeśli nie podano ID zadania, zakończ
if (empty($cronjobID)) {
    exit;
}

// Załadowanie klasy obsługującej zadania cron
require 'includes/classes/Cronjob.class.php';

// Pobranie listy zadań, które powinny zostać wykonane
$cronjobsTodo = Cronjob::getNeedTodoExecutedJobs();

// Sprawdzenie, czy zadanie o podanym ID powinno zostać wykonane
if (!in_array($cronjobID, $cronjobsTodo)) {
    exit;
}

// Wykonanie zadania
Cronjob::execute($cronjobID);
