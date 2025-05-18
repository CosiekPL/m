<?php

require_once 'includes/classes/cronjob/CronjobTask.interface.php'; // Załaduj interfejs dla zadań cron.

/**
 * Klasa CleanerCronjob implementująca interfejs CronjobTask.
 * Zadanie crona odpowiedzialne za czyszczenie starych danych z bazy danych.
 */
class CleanerCronjob implements CronjobTask
{
    /**
     * Metoda uruchamiana przez system cron. Usuwa stare wiadomości, sojusze bez członków,
     * zniszczone planety, nieaktywne sesje, niepowiązane zdarzenia flot,
     * usuwa przestarzałe potwierdzenia e-mail, usuwa nieaktywnych użytkowników,
     * czyści top listy bitew i stare raporty bitewne.
     *
     * @return void
     */
    function run(): void
    {
        $config = Config::get(ROOT_UNI); // Pobierz konfigurację głównego uniwersum.

        $unis = Universe::availableUniverses(); // Pobierz listę dostępnych uniwersów.

        // Definicje czasów przed usunięciem danych (w sekundach).
        $del_before = TIMESTAMP - ($config->del_oldstuff * 86400);       // Czas przed usunięciem starych danych (dni * sekundy/dzień).
        $del_inactive = TIMESTAMP - ($config->del_user_automatic * 86400); // Czas nieaktywności użytkownika przed automatycznym usunięciem.
        $del_deleted = TIMESTAMP - ($config->del_user_manually * 86400);   // Czas po ręcznym usunięciu użytkownika przed trwałym usunięciem.
        $del_messages = TIMESTAMP - ($config->message_delete_days * 86400); // Czas przed usunięciem usuniętych wiadomości.

        // Zabezpieczenie przed przypadkowym usunięciem wszystkich nieaktywnych użytkowników, jeśli konfiguracja jest błędna.
        if ($del_inactive === TIMESTAMP) {
            $del_inactive = 2147483647; // Ustaw na maksymalną wartość timestamp, aby nic nie usunąć.
        }

        // Usuń stare wiadomości.
        $sql = 'DELETE FROM %%MESSAGES%% WHERE `message_time` < :time;';
        Database::get()->delete($sql, [
            ':time' => $del_before
        ]);

        // Usuń sojusze bez członków.
        $sql = 'DELETE FROM %%ALLIANCE%% WHERE `ally_members` = 0;';
        Database::get()->delete($sql);

        // Usuń zniszczone planety, które są starsze niż aktualny czas (prawdopodobnie błąd logiczny, powinno być starsze niż jakiś okres).
        $sql = 'DELETE FROM %%PLANETS%% WHERE `destruyed` < :time AND `destruyed` != 0;';
        Database::get()->delete($sql, [
            ':time' => TIMESTAMP
        ]);

        // Usuń stare sesje użytkowników.
        $sql = 'DELETE FROM %%SESSION%% WHERE `lastonline` < :time;';
        Database::get()->delete($sql, [
            ':time' => TIMESTAMP - SESSION_LIFETIME
        ]);

        // Usuń zdarzenia flot, które nie są powiązane z istniejącymi flotami.
        $sql = 'DELETE FROM %%FLEETS_EVENT%% WHERE fleetID NOT IN (SELECT fleet_id FROM %%FLEETS%%);';
        Database::get()->delete($sql);

        // Usuń przestarzałe potwierdzenia e-mail (ustaw `email_2` na `email` po pewnym czasie).
        $sql = 'UPDATE %%USERS%% SET `email_2` = `email` WHERE `setmail` < :time;';
        Database::get()->update($sql, [
            ':time' => TIMESTAMP
        ]);

        // Znajdź ID użytkowników do usunięcia (nieaktywnych lub ręcznie usuniętych).
        $sql = 'SELECT `id` FROM %%USERS%% WHERE `authlevel` = :authlevel
        AND ((`db_deaktjava` != 0 AND `db_deaktjava` < :timeDeleted) OR `onlinetime` < :timeInactive);';

        $deleteUserIds = Database::get()->select($sql, [
            ':authlevel' => AUTH_USR,
            ':timeDeleted' => $del_deleted,
            ':timeInactive' => $del_inactive
        ]);

        // Usuń znalezionych użytkowników.
        if (!empty($deleteUserIds)) {
            foreach ($deleteUserIds as $dataRow) {
                PlayerUtil::deletePlayer($dataRow['id']);
            }
        }

        // Czyść top listy bitew dla każdego uniwersum.
        foreach ($unis as $uni) {
            $sql = 'SELECT units FROM %%TOPKB%% WHERE `universe` = :universe ORDER BY units DESC LIMIT 99,1;';

            $battleHallLowest = Database::get()->selectSingle($sql, [
                ':universe' => $uni
            ], 'units');

            // Jeśli istnieje więcej niż 99 rekordów, usuń te z najniższą liczbą jednostek.
            if (!is_null($battleHallLowest)) {
                $sql = 'DELETE %%TOPKB%%, %%TOPKB_USERS%%
                FROM %%TOPKB%%
                INNER JOIN %%TOPKB_USERS%% USING (rid)
                WHERE `universe` = :universe AND `units` < :battleHallLowest;';

                Database::get()->delete($sql, [
                    ':universe' => $uni,
                    ':battleHallLowest' => $battleHallLowest
                ]);
            }
        }

        // Usuń stare raporty bitewne, które nie znajdują się na top liście.
        $sql = 'DELETE FROM %%RW%% WHERE `time` < :time AND `rid` NOT IN (SELECT `rid` FROM %%TOPKB%%);';
        Database::get()->delete($sql, [
            ':time' => $del_before
        ]);

        // Usuń wiadomości, które zostały oznaczone jako usunięte i są starsze niż określony czas.
        $sql = 'DELETE FROM %%MESSAGES%% WHERE `message_deleted` < :time;';
        Database::get()->delete($sql, [
            ':time' => $del_messages
        ]);
    }
}

// Sugestie ulepszeń:

// 1. Logowanie: Dodanie logowania każdej operacji czyszczenia (np. "Usunięto X starych wiadomości").
// 2. Konfiguracja: Umożliwienie konfiguracji czasów usuwania poszczególnych typów danych w panelu administracyjnym.
// 3. Optymalizacja zapytań: Sprawdzenie indeksów w tabelach, z których usuwane są dane, aby zapewnić wydajność.
// 4. Bezpieczeństwo: Upewnienie się, że operacje usuwania są bezpieczne i nie usuwają przypadkowo ważnych danych.
// 5. Monitorowanie: Monitorowanie czasu wykonania zadania cron, aby wykryć potencjalne problemy z wydajnością.
// 6. Transakcje: Rozważenie użycia transakcji bazy danych dla bardziej złożonych operacji (np. usuwanie użytkowników wraz z ich danymi).
// 7. Podział na mniejsze zadania: W przypadku bardzo dużej bazy danych, rozważenie podzielenia czyszczenia na mniejsze, bardziej wyspecjalizowane zadania cron.