<?php

require_once 'includes/classes/cronjob/CronjobTask.interface.php'; // Załaduj interfejs dla zadań cron.

/**
 * Klasa StatisticCronjob implementująca interfejs CronjobTask.
 * Zadanie crona odpowiedzialne za generowanie statystyk gry.
 */
class StatisticCronjob implements CronjobTask
{
    /**
     * Metoda uruchamiana przez system cron. Tworzy i aktualizuje statystyki gry.
     *
     * @return void
     */
    function run(): void
    {
        require 'includes/classes/class.statbuilder.php'; // Załaduj klasę odpowiedzialną za budowanie statystyk.
        $stat = new Statbuilder(); // Utwórz nową instancję klasy Statbuilder.
        $stat->MakeStats(); // Wywołaj metodę klasy Statbuilder, która generuje i zapisuje statystyki.
    }
}

// Sugestie ulepszeń:

// 1. Logowanie: Dodanie logowania rozpoczęcia i zakończenia generowania statystyk oraz ewentualnych błędów.

// 2. Konfiguracja: Możliwość konfigurowania, jakie statystyki mają być generowane i jak często.

// 3. Optymalizacja: Monitorowanie czasu wykonania i zasobów zużywanych przez generowanie statystyk. W przypadku dużych ilości danych, optymalizacja zapytań do bazy danych może być kluczowa.

// 4. Blokowanie: Zaimplementowanie mechanizmu blokowania, aby zapobiec uruchomieniu wielu instancji zadania statystyk w tym samym czasie (szczególnie jeśli generowanie trwa dłużej).

// 5. Dzielenie na mniejsze zadania: W przypadku bardzo rozbudowanych statystyk, rozważenie podzielenia procesu na mniejsze, niezależne zadania cron.

// 6. Wykorzystanie pamięci podręcznej: Jeśli niektóre części statystyk nie zmieniają się często, rozważenie ich cachowania.

// 7. Powiadomienia: Opcjonalne powiadomienia (np. e-mail do administratora) o zakończeniu generowania statystyk lub wystąpieniu błędów.