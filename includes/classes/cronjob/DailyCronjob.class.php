<?php

require_once 'includes/classes/cronjob/CronjobTask.interface.php'; // Załaduj interfejs dla zadań cron.

/**
 * Klasa DailyCronJob implementująca interfejs CronjobTask.
 * Zadanie crona odpowiedzialne za codzienne rutynowe czynności konserwacyjne bazy danych i systemu.
 */
class DailyCronJob implements CronjobTask
{
    /**
     * Metoda uruchamiana przez system cron. Wykonuje optymalizację tabel, czyszczenie cache,
     * ponowne obliczanie zadań cron i czyszczenie cache ekonomii planet.
     *
     * @return void
     */
    function run(): void
    {
        $this->optimizeTables();       // Optymalizuj tabele bazy danych.
        $this->clearCache();           // Wyczyść cache systemu i szablonów.
        $this->reCalculateCronjobs();  // Ponownie oblicz następne uruchomienia zadań cron.
        $this->clearEcoCache();        // Wyczyść cache ekonomii planet.
    }

    /**
     * Optymalizuje wszystkie tabele bazy danych zdefiniowanym prefiksem.
     *
     * @return void
     */
    function optimizeTables(): void
    {
        $sql = "SHOW TABLE STATUS FROM `" . DB_NAME . "`;"; // Zapytanie SQL do pobrania statusu wszystkich tabel w bazie danych.
        $sqlTableRaw = Database::get()->nativeQuery($sql); // Wykonaj natywne zapytanie SQL.

        $prefixCounts = strlen(DB_PREFIX); // Długość prefiksu tabel.
        $dbTables = []; // Inicjalizuj tablicę na nazwy tabel do optymalizacji.

        foreach ($sqlTableRaw as $table) {
            // Sprawdź, czy nazwa tabeli zaczyna się od zdefiniowanego prefiksu.
            if (DB_PREFIX == substr($table['Name'], 0, $prefixCounts)) {
                $dbTables[] = $table['Name']; // Dodaj nazwę tabeli do tablicy.
            }
        }

        // Jeśli znaleziono jakieś tabele z prefiksem, wykonaj optymalizację.
        if (!empty($dbTables)) {
            Database::get()->nativeQuery("OPTIMIZE TABLE " . implode(', ', $dbTables) . ";");
        }
    }

    /**
     * Czyści cache systemu i szablonów, wywołując globalną funkcję ClearCache.
     *
     * @return void
     */
    function clearCache(): void
    {
        ClearCache();
    }

    /**
     * Ponownie oblicza następne uruchomienia zadań cron, wywołując statyczną metodę klasy Cronjob.
     *
     * @return void
     */
    function reCalculateCronjobs(): void
    {
        Cronjob::reCalculateCronjobs();
    }

    /**
     * Czyści cache ekonomii planet, resetując pole eco_hash w tabeli planet.
     *
     * @return void
     */
    function clearEcoCache(): void
    {
        $sql = "UPDATE %%PLANETS%% SET eco_hash = '';"; // Zapytanie SQL do zresetowania hasha ekonomii planet.
        Database::get()->update($sql);
    }
}

// Sugestie ulepszeń:

// 1. Logowanie: Dodanie logowania rozpoczęcia i zakończenia każdej operacji (optymalizacja, czyszczenie cache itp.).
// 2. Konfiguracja: Możliwość wyłączenia poszczególnych operacji (np. optymalizacji) w konfiguracji.
// 3. Monitorowanie: Monitorowanie czasu wykonania każdej operacji, aby wykryć potencjalne problemy z wydajnością.
// 4. Bezpieczeństwo: Upewnienie się, że operacje są bezpieczne i nie powodują utraty danych.
// 5. Częstotliwość: Sprawdzenie, czy codzienna częstotliwość jest optymalna dla wszystkich operacji. Niektóre mogą wymagać rzadszego wykonywania.
// 6. Obsługa błędów: Dodanie mechanizmów obsługi błędów w przypadku niepowodzenia którejkolwiek z operacji.