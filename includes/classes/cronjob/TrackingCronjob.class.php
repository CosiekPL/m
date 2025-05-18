<?php
require_once 'includes/classes/cronjob/CronjobTask.interface.php'; // Załaduj interfejs dla zadań cron.

/**
 * Klasa TrackingCronjob implementująca interfejs CronjobTask.
 * Zadanie crona odpowiedzialne za wysyłanie anonimowych statystyk serwera do zewnętrznego serwisu śledzącego.
 */
class TrackingCronjob implements CronjobTask
{
    /**
     * Metoda uruchamiana przez system cron. Zbiera dane serwera i wysyła je.
     *
     * @return void
     */
    function run(): void
    {
        $serverData['php'] = PHP_VERSION; // Pobierz wersję PHP serwera.

        // Próba pobrania czasu pierwszej rejestracji użytkownika (prawdopodobnie czas instalacji).
        try {
            $sql = 'SELECT register_time FROM %%USERS%% WHERE id = :userId';
            $serverData['installSince'] = Database::get()->selectSingle($sql, [
                ':userId' => ROOT_USER // Użyj ID użytkownika ROOT (prawdopodobnie administratora).
            ], 'register_time'); // Pobierz tylko kolumnę 'register_time'.
        } catch (Exception $e) {
            // W przypadku błędu (np. brak tabeli), ustaw wartość na NULL.
            $serverData['installSince'] = null;
            // Można dodać logowanie błędu: error_log("Błąd pobierania czasu instalacji: " . $e->getMessage());
        }

        // Próba pobrania liczby wszystkich zarejestrowanych użytkowników.
        try {
            $sql = 'SELECT COUNT(*) as state FROM %%USERS%%;';
            $serverData['users'] = Database::get()->selectSingle($sql, [], 'state'); // Pobierz tylko kolumnę 'state' (liczbę użytkowników).
        } catch (Exception $e) {
            // W przypadku błędu, ustaw wartość na NULL.
            $serverData['users'] = null;
            // Można dodać logowanie błędu: error_log("Błąd pobierania liczby użytkowników: " . $e->getMessage());
        }

        // Próba pobrania liczby uniwersów (prawdopodobnie liczba wierszy w tabeli konfiguracyjnej).
        try {
            $sql = 'SELECT COUNT(*) as state FROM %%CONFIG%%;';
            $serverData['unis'] = Database::get()->selectSingle($sql, [], 'state'); // Pobierz tylko kolumnę 'state' (liczbę uniwersów).
        } catch (Exception $e) {
            // W przypadku błędu, ustaw wartość na NULL.
            $serverData['unis'] = null;
            // Można dodać logowanie błędu: error_log("Błąd pobierania liczby uniwersów: " . $e->getMessage());
        }

        // Pobierz wersję oprogramowania z konfiguracji dla ROOT_UNI (głównego uniwersum).
        $serverData['version'] = Config::get(ROOT_UNI)->VERSION;

    }
}

// Sugestie ulepszeń:

// 1. Logowanie błędów: Dodanie logowania błędów w blokach `catch` może pomóc w diagnozowaniu problemów z pobieraniem danych. Użycie `error_log()` z odpowiednim komunikatem.

// 2. Konfiguracja URL śledzenia: Przeniesienie URL śledzenia do konfiguracji (np. w tabeli `%%CONFIG%%`) ułatwi jego zmianę bez modyfikacji kodu.

// 3. Bezpieczeństwo cURL: Rozważenie dodatkowych opcji cURL dla bezpieczeństwa, takich jak `CURLOPT_SSL_VERIFYPEER` i `CURLOPT_SSL_VERIFYHOST` jeśli komunikacja odbywa się przez HTTPS.

// 4. Obsługa błędów cURL: Sprawdzenie wyniku `curl_exec()` i logowanie ewentualnych błędów transferu za pomocą `curl_errno()` i `curl_error()`.

// 5. Częstotliwość wysyłania danych: Upewnienie się, że dane nie są wysyłane zbyt często, co mogłoby obciążać serwer śledzący. Można dodać mechanizm sprawdzania ostatniej wysyłki (np. zapisywany w cache lub konfiguracji).

// 6. Anonimizacja danych: Upewnienie się, że wysyłane dane są w pełni anonimowe i nie zawierają informacji pozwalających na identyfikację konkretnych instalacji.

// 7. Optymalizacja zapytań: Zapytania `COUNT(*)` są proste, ale w przypadku bardzo dużych tabel warto upewnić się o ich wydajności (np. czy są indeksowane odpowiednio).

// 8. Użycie stałych dla nazw tabel: Zamiast łańcuchów znaków '%%USERS%%' i '%%CONFIG%%', używanie stałych zdefiniowanych w `includes/constants.php` poprawi spójność i czytelność.