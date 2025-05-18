<?php

require_once 'includes/classes/cronjob/CronjobTask.interface.php'; // Załaduj interfejs dla zadań cron.

/**
 * Klasa InactiveMailCronjob implementująca interfejs CronjobTask.
 * Zadanie crona odpowiedzialne za wysyłanie przypomnień e-mail do nieaktywnych użytkowników.
 */
class InactiveMailCronjob implements CronjobTask
{
    /**
     * Metoda uruchamiana przez system cron. Wysyła e-maile do użytkowników, którzy nie logowali się przez określony czas.
     *
     * @return void
     */
    function run(): void
    {
        global $LNG; // Dostęp do globalnego obiektu języka.

        $config = Config::get(ROOT_UNI); // Pobierz konfigurację głównego uniwersum.

        // Sprawdź, czy wysyłanie e-maili jest aktywne w konfiguracji.
        if ($config->mail_active == 1) {
            /** @var Language[] $langObjects Tablica przechowująca obiekty języka, aby uniknąć wielokrotnego ładowania tego samego języka. */
            $langObjects = [];

            require 'includes/classes/Mail.class.php'; // Załaduj klasę do obsługi wysyłki e-maili.

            // Zapytanie SQL pobierające ID, nazwę użytkownika, język, e-mail, czas ostatniej aktywności, strefę czasową i uniwersum nieaktywnych użytkowników,
            // którzy jeszcze nie otrzymali przypomnienia (`inactive_mail` = 0).
            $sql = 'SELECT `id`, `username`, `lang`, `email`, `onlinetime`, `timezone`, `universe`
            FROM %%USERS%% WHERE `inactive_mail` = 0 AND `onlinetime` < :time;';

            $inactiveUsers = Database::get()->select($sql, [
                ':time' => TIMESTAMP - ($config->del_user_sendmail * 24 * 60 * 60), // Czas nieaktywności przed wysłaniem e-maila (dni * sekundy/dzień).
            ]);

            // Iteruj po znalezionych nieaktywnych użytkownikach.
            foreach ($inactiveUsers as $user) {
                // Załaduj obiekt języka dla danego użytkownika, jeśli jeszcze nie został załadowany.
                if (!isset($langObjects[$user['lang']])) {
                    $langObjects[$user['lang']] = new Language($user['lang']);
                    $langObjects[$user['lang']]->includeData(['L18N', 'INGAME', 'PUBLIC', 'CUSTOM']); // Załaduj dane językowe.
                }

                $userConfig = Config::get($user['universe']); // Pobierz konfigurację uniwersum użytkownika.
                $LNG = $langObjects[$user['lang']]; // Pobierz obiekt języka dla użytkownika.

                // Utwórz temat e-maila.
                $MailSubject = sprintf($LNG['spec_mail_inactive_title'], $userConfig->game_name . ' - ' . $userConfig->uni_name);
                // Pobierz treść e-maila z szablonu.
                $MailRAW = $LNG->getTemplate('email_inactive');

                // Zastąp placeholdery w treści e-maila danymi użytkownika i konfiguracji.
                $MailContent = str_replace([
                    '{USERNAME}',
                    '{GAMENAME}',
                    '{LASTDATE}',
                    '{HTTPPATH}',
                ], [
                    $user['username'],
                    $userConfig->game_name . ' - ' . $userConfig->uni_name,
                    _date($LNG['php_tdformat'], $user['onlinetime'], $user['timezone']), // Sformatuj datę ostatniej aktywności.
                    HTTP_PATH, // Ścieżka do strony głównej gry.
                ], $MailRAW);

                // Wyślij e-mail do nieaktywnego użytkownika.
                Mail::send($user['email'], $user['username'], $MailSubject, $MailContent);

                // Oznacz użytkownika jako tego, któremu wysłano już przypomnienie.
                $sql = 'UPDATE %%USERS%% SET `inactive_mail` = 1 WHERE `id` = :userId;';
                Database::get()->update($sql, [
                    ':userId' => $user['id'],
                ]);
            }
        }
    }
}

// Sugestie ulepszeń:

// 1. Logowanie: Dodanie logowania informacji o wysłanych e-mailach (komu, kiedy) oraz ewentualnych błędów wysyłki.
// 2. Konfiguracja: Umożliwienie konfiguracji treści e-maila, czasu nieaktywności przed wysłaniem i statusu wysyłania e-maili w panelu administracyjnym.
// 3. Ograniczenie wysyłki: Dodanie mechanizmu ograniczającego liczbę wysyłanych e-maili w jednym uruchomieniu crona, aby uniknąć przeciążenia serwera poczty.
// 4. Testowanie: Możliwość testowania wysyłki e-maili do określonych użytkowników.
// 5. Statystyki: Zbieranie statystyk dotyczących skuteczności wysyłanych przypomnień (np. ilu użytkowników powróciło po otrzymaniu e-maila).
// 6. Obsługa błędów: Dodanie obsługi błędów podczas pobierania danych użytkowników i wysyłki e-maili.
// 7. Kolejkowanie e-maili: W przypadku dużej liczby nieaktywnych użytkowników, rozważenie kolejkowania e-maili, aby uniknąć blokowania wykonania crona.