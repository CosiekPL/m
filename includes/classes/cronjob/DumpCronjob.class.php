<?php

require_once 'includes/classes/cronjob/CronjobTask.interface.php'; // Załaduj interfejs dla zadań cron.

/**
 * Klasa DumpCronjob implementująca interfejs CronjobTask.
 * Zadanie crona odpowiedzialne za tworzenie zrzutu (backupu) bazy danych.
 */
class DumpCronjob implements CronjobTask
{
    /**
     * Metoda uruchamiana przez system cron. Tworzy zrzut tabel bazy danych z określonym prefiksem
     * i zapisuje go do pliku w katalogu 'includes/backups/'.
     *
     * @return void
     *
     * @throws Exception Wyrzuca wyjątek, jeśli nie znaleziono tabel do zrzutu.
     */
    function run(): void
    {
        $prefixCounts = strlen(DB_PREFIX); // Długość prefiksu tabel bazy danych.
        $dbTables = []; // Inicjalizuj pustą tablicę na nazwy tabel do zrzutu.
        $tableNames = Database::get()->nativeQuery('SHOW TABLE STATUS FROM ' . DB_NAME . ';'); // Pobierz status wszystkich tabel w bazie danych.

        // Filtruj tabele, które mają zdefiniowany prefiks.
        foreach ($tableNames as $table) {
            if (DB_PREFIX == substr($table['Name'], 0, $prefixCounts)) {
                $dbTables[] = $table['Name']; // Dodaj nazwę tabeli do tablicy.
            }
        }

        // Jeśli nie znaleziono żadnych tabel z prefiksem, wyrzuć wyjątek.
        if (empty($dbTables)) {
            throw new Exception('Nie znaleziono tabel do zrzutu.');
        }

        // Utwórz nazwę pliku z datą i godziną utworzenia zrzutu.
        $fileName = '2MoonsBackup_' . date('d_m_Y_H_i_s', TIMESTAMP) . '.sql';
        $filePath = 'includes/backups/' . $fileName; // Ścieżka do pliku zrzutu.

        require 'includes/classes/SQLDumper.class.php'; // Załaduj klasę odpowiedzialną za tworzenie zrzutu SQL.

        $dump = new SQLDumper; // Utwórz nową instancję klasy SQLDumper.
        $dump->dumpTablesToFile($dbTables, $filePath); // Wywołaj metodę tworzącą zrzut podanych tabel do pliku.
    }
}

// Sugestie ulepszeń:

// 1. Logowanie: Dodanie logowania rozpoczęcia i zakończenia tworzenia zrzutu oraz ewentualnych błędów.
// 2. Konfiguracja: Umożliwienie konfiguracji ścieżki zapisu zrzutów, formatu nazwy pliku, a także wyboru tabel do zrzutu.
// 3. Kompresja: Dodanie opcji kompresji zrzutu (np. gzip) w celu zmniejszenia rozmiaru pliku.
// 4. Rotacja zrzutów: Zaimplementowanie mechanizmu automatycznego usuwania starych zrzutów, aby nie zajmowały zbyt dużo miejsca na dysku.
// 5. Zdalny zapis: Możliwość zapisywania zrzutów na zdalnym serwerze (np. FTP, chmura).
// 6. Powiadomienia: Opcjonalne powiadomienia (np. e-mail do administratora) o utworzeniu zrzutu lub wystąpieniu błędów.
// 7. Obsługa błędów: Dodanie bardziej szczegółowej obsługi błędów podczas tworzenia zrzutu (np. brak uprawnień do zapisu).