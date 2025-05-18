<?php

require_once 'includes/classes/cronjob/CronjobTask.interface.php'; // Załaduj interfejs dla zadań cron.

/**
 * Klasa ReferralCronJob implementująca interfejs CronjobTask.
 * Zadanie crona odpowiedzialne za przyznawanie bonusów za polecenia nowych graczy.
 */
class ReferralCronJob implements CronjobTask
{
    /**
     * Metoda uruchamiana przez system cron. Sprawdza poleconych graczy i przyznaje bonusy.
     *
     * @return bool|null True po wykonaniu (nawet jeśli nie przyznano bonusów), null jeśli system poleceń jest wyłączony.
     */
    function run(): ?bool
    {
        $uniConfig = Config::get(ROOT_UNI); // Pobierz konfigurację głównego uniwersum.
        if ($uniConfig->ref_active != 1) {
            return null; // Jeśli system poleceń jest wyłączony, zakończ zadanie.
        }

        /** @var Language[] $langObjects Tablica przechowująca obiekty języka, aby uniknąć wielokrotnego ładowania tego samego języka. */
        $langObjects = [];

        $db = Database::get(); // Pobierz instancję bazy danych.

        // Zapytanie SQL pobierające użytkowników, którzy polecili innych i spełniają warunki do otrzymania bonusu,
        // ale jeszcze go nie otrzymali (`ref_bonus` = 1).
        $sql = 'SELECT `username`, `ref_id`, `id`, `lang`, user.`universe`
        FROM %%USERS%% user
        INNER JOIN %%STATPOINTS%% as stats
        ON stats.`id_owner` = user.`id` AND stats.`stat_type` = :type AND stats.`total_points` >= :points
        WHERE user.`ref_bonus` = 1;';

        $userArray = $db->select($sql, [
            ':type'   => 1, // Typ statystyk (prawdopodobnie ogólne punkty).
            ':points' => $uniConfig->ref_minpoints, // Minimalna liczba punktów wymagana do przyznania bonusu.
        ]);

        // Iteruj po użytkownikach spełniających warunki do przyznania bonusu za polecenie.
        foreach ($userArray as $user) {
            // Załaduj obiekt języka dla danego użytkownika, jeśli jeszcze nie został załadowany.
            if (!isset($langObjects[$user['lang']])) {
                $langObjects[$user['lang']] = new Language($user['lang']);
                $langObjects[$user['lang']]->includeData(['L18N', 'INGAME', 'TECH', 'CUSTOM']); // Załaduj dane językowe.
            }

            $userConfig = Config::get($user['universe']); // Pobierz konfigurację uniwersum użytkownika.
            $LNG = $langObjects[$user['lang']]; // Pobierz obiekt języka dla użytkownika.

            // Zapytanie SQL aktualizujące konto polecającego, dodając mu ciemną materię (bonus).
            $sql = 'UPDATE %%USERS%% SET `darkmatter` = `darkmatter` + :bonus WHERE `id` = :userId;';

            $db->update($sql, [
                ':bonus'  => $userConfig->ref_bonus, // Wysokość bonusu (w ciemnej materii).
                ':userId' => $user['ref_id'], // ID użytkownika, który polecił.
            ]);

            // Zapytanie SQL aktualizujące status poleconego użytkownika, oznaczając, że bonus został przyznany.
            $sql = 'UPDATE %%USERS%% SET `ref_bonus` = 0 WHERE `id` = :userId;';

            $db->update($sql, [
                ':userId' => $user['id'], // ID poleconego użytkownika.
            ]);

            // Wygeneruj wiadomość do polecającego użytkownika.
            $Message = sprintf($LNG['sys_refferal_text'], $user['username'], pretty_number($userConfig->ref_minpoints), pretty_number($userConfig->ref_bonus), $LNG['tech'][921]);
            // Wyślij wiadomość do polecającego użytkownika.
            PlayerUtil::sendMessage($user['ref_id'], '', $LNG['sys_refferal_from'], 4, sprintf($LNG['sys_refferal_title'], $user['username']), $Message, TIMESTAMP);
        }

        return true; // Zwróć true, oznaczając zakończenie zadania.
    }
}

// Sugestie ulepszeń:

// 1. Logowanie: Dodanie logowania informacji o przyznanych bonusach (komu, ile) oraz ewentualnych błędów.

// 2. Konfiguracja: Możliwość konfigurowania wysokości bonusu, minimalnej liczby punktów i statusu systemu poleceń w panelu administracyjnym.

// 3. Zapobieganie duplikatom: Upewnienie się, że bonus za polecenie nie zostanie przyznany wielokrotnie temu samemu polecającemu za tego samego poleconego. Obecny kod bazuje na fladze `ref_bonus`, co powinno temu zapobiegać, ale warto to monitorować.

// 4. Wydajność: W przypadku bardzo dużej liczby użytkowników, optymalizacja zapytań SQL może być konieczna (np. dodanie indeksów).

// 5. Transakcje bazy danych: Rozważenie użycia transakcji bazy danych, aby zapewnić, że aktualizacja konta polecającego i statusu poleconego są wykonane atomowo. W przypadku błędu w jednej operacji, druga również zostanie wycofana.

// 6. Obiektowość: Można rozważyć stworzenie dedykowanej klasy do obsługi poleceń, która zawierałaby logikę przyznawania bonusów i wysyłania wiadomości.

// 7. Testy jednostkowe: Dodanie testów jednostkowych, aby upewnić się, że logika przyznawania bonusów działa poprawnie w różnych scenariuszach.