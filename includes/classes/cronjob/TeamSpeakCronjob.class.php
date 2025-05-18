<?php

require_once 'includes/classes/cronjob/CronjobTask.interface.php'; // Załaduj interfejs dla zadań cron.

/**
 * Klasa TeamSpeakCronjob implementująca interfejs CronjobTask.
 * Zadanie crona odpowiedzialne za odświeżanie danych TeamSpeak w pamięci podręcznej.
 */
class TeamSpeakCronjob implements CronjobTask
{
    /**
     * Metoda uruchamiana przez system cron. Czyści i ponownie buduje cache danych TeamSpeak.
     *
     * @return void
     */
    function run(): void
    {
        // Dodaj klucz 'teamspeak' do kolejki odświeżania pamięci podręcznej,
        // używając funkcji 'TeamspeakBuildCache' do jego aktualizacji w razie potrzeby.
        Cache::get()->add('teamspeak', 'TeamspeakBuildCache');

        // Natychmiastowo opróżnij (wyczyść) dane z pamięci podręcznej pod kluczem 'teamspeak'.
        // Spowoduje to, że przy następnym żądaniu danych TeamSpeak, funkcja 'TeamspeakBuildCache'
        // zostanie wykonana w celu pobrania aktualnych informacji.
        Cache::get()->flush('teamspeak');
    }
}

// Sugestie ulepszeń:

// 1. Obsługa błędów: Dodanie mechanizmu obsługi błędów w przypadku problemów z odświeżaniem cache (np. logowanie).

// 2. Warunkowe odświeżanie: Możliwość odświeżania cache tylko wtedy, gdy wykryto zmiany w danych TeamSpeak (jeśli istnieje taka możliwość).

// 3. Częstotliwość odświeżania: Upewnienie się, że częstotliwość uruchamiania tego zadania cron jest odpowiednia do dynamiki zmian danych TeamSpeak. Zbyt częste odświeżanie może obciążać serwer, a zbyt rzadkie może powodować wyświetlanie nieaktualnych informacji.

// 4. Zależności: Jeśli odświeżanie danych TeamSpeak zależy od innych zadań cron lub procesów, warto zarządzać kolejnością ich wykonywania.

// 5. Konfiguracja: Przeniesienie klucza cache ('teamspeak') i nazwy funkcji budującej cache ('TeamspeakBuildCache') do konfiguracji, aby ułatwić ich zmianę.