<?php
/**
 * Pobiera różne współczynniki wpływające na rozgrywkę dla użytkownika.
 *
 * @param array       $USER Dane użytkownika.
 * @param string      $Type Typ współczynników do pobrania (domyślnie 'basic').
 * @param int|null    $TIME Znacznik czasu, dla którego mają być obliczone współczynniki (domyślnie aktualny czas).
 *
 * @return array Tablica asocjacyjna współczynników.
 */
function getFactors($USER, $Type = 'basic', $TIME = null): array
{
    global $resource, $pricelist, $reslist; // Dostęp do globalnych tablic zasobów, cennika i listy zasobów.
    if (empty($TIME)) {
        $TIME = TIMESTAMP; // Użyj aktualnego czasu, jeśli nie podano innego.
    }

    $bonusList = BuildFunctions::getBonusList(); // Pobierz listę dostępnych bonusów z funkcji statycznych klasy BuildFunctions.
    $factor = ArrayUtil::combineArrayWithSingleElement($bonusList, 0); // Stwórz tablicę współczynników, inicjalizując wszystkie wartości na 0 na podstawie listy bonusów.

    foreach ($reslist['bonus'] as $elementID) {
        $bonus = $pricelist[$elementID]['bonus']; // Pobierz bonusy dla danego elementu (budynku/technologii).

        // Określ poziom elementu (budynku na planecie lub technologii użytkownika).
        if (isset($PLANET[$resource[$elementID]])) {
            $elementLevel = $PLANET[$resource[$elementID]];
        } elseif (isset($USER[$resource[$elementID]])) {
            $elementLevel = $USER[$resource[$elementID]];
        } else {
            continue; // Przejdź do następnego elementu, jeśli nie znaleziono poziomu.
        }

        // Obsługa specjalnych funkcji Ciemnej Materii (DMExtra).
        if (in_array($elementID, $reslist['dmfunc'], true)) {
            if (DMExtra($elementLevel, $TIME, false, true)) {
                continue; // Jeśli funkcja DM jest aktywna, pomiń dodawanie zwykłego bonusu.
            }

            foreach ($bonusList as $bonusKey) {
                $factor[$bonusKey] += $bonus[$bonusKey][0]; // Dodaj wartość bonusu z cennika do odpowiedniego współczynnika.
            }
        } else {
            // Dodaj bonusy wynikające z poziomu budynku/technologii.
            foreach ($bonusList as $bonusKey) {
                $factor[$bonusKey] += $elementLevel * $bonus[$bonusKey][0]; // Pomnóż bonus przez poziom i dodaj do współczynnika.
            }
        }
    }

    return $factor; // Zwróć tablicę obliczonych współczynników.
}

/**
 * Pobiera listę planet należących do użytkownika.
 *
 * @param array $USER Dane użytkownika.
 *
 * @return array Tablica asocjacyjna z danymi planet, gdzie kluczem jest ID planety.
 */
function getPlanets($USER): array
{
    if (isset($USER['PLANETS'])) {
        return $USER['PLANETS']; // Zwróć listę planet, jeśli jest już dostępna w danych użytkownika.
    }

    $order = $USER['planet_sort_order'] == 1 ? "DESC" : "ASC"; // Określ porządek sortowania (rosnąco lub malejąco).

    $sql = "SELECT id, name, galaxy, system, planet, planet_type, image, b_building, b_building_id
            FROM %%PLANETS%% WHERE id_owner = :userId AND destruyed = :destruyed ORDER BY ";

    // Dodaj klauzulę ORDER BY w zależności od preferencji sortowania użytkownika.
    switch ($USER['planet_sort']) {
        case 0:
            $sql .= 'id ' . $order; // Sortuj według ID planety.
            break;
        case 1:
            $sql .= 'galaxy, system, planet, planet_type ' . $order; // Sortuj według współrzędnych.
            break;
        case 2:
            $sql .= 'name ' . $order; // Sortuj według nazwy planety.
            break;
    }

    $planetsResult = Database::get()->select($sql, [
        ':userId'    => $USER['id'],      // ID zalogowanego użytkownika.
        ':destruyed' => 0,                 // Tylko niezniszczone planety.
    ]);

    $planetsList = []; // Inicjalizuj pustą tablicę na listę planet.

    foreach ($planetsResult as $planetRow) {
        $planetsList[$planetRow['id']] = $planetRow; // Dodaj dane planety do tablicy, używając ID planety jako klucza.
    }

    return $planetsList; // Zwróć listę planet użytkownika.
}

/**
 * Generuje tablicę asocjacyjną stref czasowych do użycia w selektorze HTML.
 * Grupuje strefy czasowe według kontynentu.
 *
 * @return array Tablica asocjacyjna stref czasowych.
 */
function get_timezone_selector(): array
{
    $timezones = []; // Inicjalizuj pustą tablicę na strefy czasowe.
    $timezone_identifiers = DateTimeZone::listIdentifiers(); // Pobierz listę wszystkich dostępnych identyfikatorów stref czasowych.

    foreach ($timezone_identifiers as $value) {
        // Filtruj tylko strefy czasowe, które zaczynają się od nazwy kontynentu.
        if (preg_match('/^(America|Antartica|Arctic|Asia|Atlantic|Europe|Indian|Pacific)\//', $value)) {
            $ex = explode('/', $value); // Rozdziel identyfikator strefy czasowej na kontynent i miasto.
            $city = isset($ex[2]) ? $ex[1] . ' - ' . $ex[2] : $ex[1]; // Jeśli jest więcej niż jeden segment, połącz miasto z regionem.
            $timezones[$ex[0]][$value] = str_replace('_', ' ', $city); // Dodaj strefę czasową do tablicy, zastępując podkreślenia spacjami.
        }
    }
    return $timezones; // Zwróć tablicę stref czasowych pogrupowanych według kontynentu.
}

/**
 * Formatuje łańcuch formatu daty, zastępując skróty dni tygodnia i miesięcy ich zlokalizowanymi nazwami.
 *
 * @param string      $format Łańcuch formatu daty (np. 'D, d. M Y H:i:s').
 * @param int         $time   Znacznik czasu (timestamp) do formatowania.
 * @param Language|null $LNG    Obiekt języka. Jeśli null, użyje globalnego $LNG.
 *
 * @return string Sformatowany łańcuch formatu daty z zlokalizowanymi nazwami dni i miesięcy.
 */
function locale_date_format($format, $time, $LNG = null): string
{
    // Użyj globalnego obiektu języka, jeśli nie został przekazany.
    if (!isset($LNG)) {
        global $LNG;
    }

    // Pobierz dzień tygodnia (0 - niedziela, 6 - sobota) i miesiąc (0 - styczeń, 11 - grudzień) z znacznika czasu.
    $weekDay = date('w', (int)$time);
    $months = date('n', (int)$time) - 1;

    // Zastąp skróty dni tygodnia ('D') i miesięcy ('M') tymczasowymi znacznikami.
    $format = str_replace(['D', 'M'], ['$D$', '$M$'], $format);

    // Wstaw zlokalizowane nazwy dni tygodnia i miesięcy, używając addcslashes do uniknięcia problemów ze znakami specjalnymi.
    $format = str_replace('$D$', addcslashes($LNG['week_day'][$weekDay], 'A..z'), $format);
    $format = str_replace('$M$', addcslashes($LNG['months'][$months], 'A..z'), $format);

    return $format; // Zwróć zmodyfikowany łańcuch formatu daty.
}

/**
 * Formatuje znacznik czasu (timestamp) do podanego formatu daty i czasu, z opcjonalną konwersją strefy czasowej.
 *
 * @param string      $format     Łańcuch formatu daty (zgodny z funkcją date()).
 * @param int|null    $time       Znacznik czasu (timestamp) do formatowania. Jeśli null, użyje aktualnego czasu serwera.
 * @param string|null $toTimeZone Nazwa strefy czasowej, na którą ma zostać przekonwertowany czas (np. 'Europe/Warsaw').
 * @param Language|null $LNG        Obiekt języka. Jeśli null, użyje globalnego $LNG.
 *
 * @return string Sformatowany ciąg znaków daty i czasu.
 */
function _date($format, $time = null, $toTimeZone = null, $LNG = null): string
{
    // Użyj aktualnego czasu serwera, jeśli nie podano znacznika czasu.
    if (!isset($time)) {
        $time = TIMESTAMP;
    }

    // Jeśli podano strefę czasową docelową, przekonwertuj czas.
    if (isset($toTimeZone)) {
        $date = new DateTime(); // Utwórz nowy obiekt DateTime.
        if (method_exists($date, 'setTimestamp')) {
            // Dla PHP w wersji 5.3 i nowszych, ustaw znacznik czasu bezpośrednio.
            $date->setTimestamp((int)$time);
        } else {
            // Dla PHP w wersji starszej niż 5.3, ustaw datę i czas ręcznie na podstawie znacznika czasu.
            $tempDate = getdate((int)$time);
            $date->setDate($tempDate['year'], $tempDate['mon'], $tempDate['mday']);
            $date->setTime($tempDate['hours'], $tempDate['minutes'], $tempDate['seconds']);
        }

        // Skoryguj znacznik czasu o offset bieżącej strefy czasowej.
        $time -= $date->getOffset();
        try {
            // Ustaw docelową strefę czasową.
            $date->setTimezone(new DateTimeZone($toTimeZone));
        } catch (Exception $e) {
            // Ignoruj błędy ustawienia strefy czasowej (np. nieprawidłowa nazwa strefy).
        }
        // Skoryguj znacznik czasu o offset docelowej strefy czasowej.
        $time += $date->getOffset();
    }

    // Sformatuj datę i czas, używając zlokalizowanych nazw dni tygodnia i miesięcy.
    $format = locale_date_format($format, $time, $LNG);
    return date($format, (int)$time); // Zwróć sformatowany ciąg znaków daty i czasu.
}

/**
 * Waliduje podany adres e-mail.
 *
 * Sprawdza poprawność adresu e-mail za pomocą wbudowanej funkcji `filter_var`,
 * jeśli jest dostępna. W przeciwnym razie używa złożonego wyrażenia regularnego
 * zaczerpniętego z biblioteki Swift Mailer w celu przeprowadzenia walidacji
 * zgodnej z RFC 2822.
 *
 * @param string $address Adres e-mail do walidacji.
 *
 * @return bool True, jeśli adres e-mail jest poprawny, false w przeciwnym razie.
 */
function ValidateAddress($address): bool
{
    // Sprawdź, czy funkcja `filter_var` jest dostępna (PHP >= 5.2.0).
    if (function_exists('filter_var')) {
        // Użyj wbudowanej funkcji do walidacji adresu e-mail.
        return filter_var($address, FILTER_VALIDATE_EMAIL) !== false;
    } else {
		return preg_match('/^(?:(?:(?:(?:(?:(?:(?:[ \t]*(?:\r\n))?[ \t])?(\((?:(?:(?:[ \t]*(?:\r\n))?[ \t])|(?:(?:[\x01-\x08\x0B\x0C\x0E-\x19\x7F]|[\x21-\x27\x2A-\x5B\x5D-\x7E])|(?:\\[\x00-\x08\x0B\x0C\x0E-\x7F])|(?1)))*(?:(?:[ \t]*(?:\r\n))?[ \t])?\)))*(?:(?:(?:(?:[ \t]*(?:\r\n))?[ \t])?(\((?:(?:(?:[ \t]*(?:\r\n))?[ \t])|(?:(?:[\x01-\x08\x0B\x0C\x0E-\x19\x7F]|[\x21-\x27\x2A-\x5B\x5D-\x7E])|(?:\\[\x00-\x08\x0B\x0C\x0E-\x7F])|(?1)))*(?:(?:[ \t]*(?:\r\n))?[ \t])?\)))|(?:(?:[ \t]*(?:\r\n))?[ \t])))?(?:[a-zA-Z0-9!#\$%&\'\*\+\-\/=\?\^_\{\}\|~]+(\.[a-zA-Z0-9!#\$%&\'\*\+\-\/=\?\^_\{\}\|~]+)*)+(?:(?:(?:(?:[ \t]*(?:\r\n))?[ \t])?(\((?:(?:(?:[ \t]*(?:\r\n))?[ \t])|(?:(?:[\x01-\x08\x0B\x0C\x0E-\x19\x7F]|[\x21-\x27\x2A-\x5B\x5D-\x7E])|(?:\\[\x00-\x08\x0B\x0C\x0E-\x7F])|(?1)))*(?:(?:[ \t]*(?:\r\n))?[ \t])?\)))*(?:(?:(?:(?:[ \t]*(?:\r\n))?[ \t])?(\((?:(?:(?:[ \t]*(?:\r\n))?[ \t])|(?:(?:[\x01-\x08\x0B\x0C\x0E-\x19\x7F]|[\x21-\x27\x2A-\x5B\x5D-\x7E])|(?:\\[\x00-\x08\x0B\x0C\x0E-\x7F])|(?1)))*(?:(?:[ \t]*(?:\r\n))?[ \t])?\)))|(?:(?:[ \t]*(?:\r\n))?[ \t])))?)|(?:(?:(?:(?:(?:[ \t]*(?:\r\n))?[ \t])?(\((?:(?:(?:[ \t]*(?:\r\n))?[ \t])|(?:(?:[\x01-\x08\x0B\x0C\x0E-\x19\x7F]|[\x21-\x27\x2A-\x5B\x5D-\x7E])|(?:\\[\x00-\x08\x0B\x0C\x0E-\x7F])|(?1)))*(?:(?:[ \t]*(?:\r\n))?[ \t])?\)))*(?:(?:(?:(?:[ \t]*(?:\r\n))?[ \t])?(\((?:(?:(?:[ \t]*(?:\r\n))?[ \t])|(?:(?:[\x01-\x08\x0B\x0C\x0E-\x19\x7F]|[\x21-\x27\x2A-\x5B\x5D-\x7E])|(?:\\[\x00-\x08\x0B\x0C\x0E-\x7F])|(?1)))*(?:(?:[ \t]*(?:\r\n))?[ \t])?\)))|(?:(?:[ \t]*(?:\r\n))?[ \t])))?"((?:(?:[ \t]*(?:\r\n))?[ \t])?(?:(?:[\x01-\x08\x0B\x0C\x0E-\x19\x7F]|[\x21\x23-\x5B\x5D-\x7E])|(?:\\[\x00-\x08\x0B\x0C\x0E-\x7F])))*(?:(?:[ \t]*(?:\r\n))?[ \t])?"(?:(?:(?:(?:[ \t]*(?:\r\n))?[ \t])?(\((?:(?:(?:[ \t]*(?:\r\n))?[ \t])|(?:(?:[\x01-\x08\x0B\x0C\x0E-\x19\x7F]|[\x21-\x27\x2A-\x5B\x5D-\x7E])|(?:\\[\x00-\x08\x0B\x0C\x0E-\x7F])|(?1)))*(?:(?:[ \t]*(?:\r\n))?[ \t])?\)))*(?:(?:(?:(?:[ \t]*(?:\r\n))?[ \t])?(\((?:(?:(?:[ \t]*(?:\r\n))?[ \t])|(?:(?:[\x01-\x08\x0B\x0C\x0E-\x19\x7F]|[\x21-\x27\x2A-\x5B\x5D-\x7E])|(?:\\[\x00-\x08\x0B\x0C\x0E-\x7F])|(?1)))*(?:(?:[ \t]*(?:\r\n))?[ \t])?\)))|(?:(?:[ \t]*(?:\r\n))?[ \t])))?))@(?:(?:(?:(?:(?:(?:[ \t]*(?:\r\n))?[ \t])?(\((?:(?:(?:[ \t]*(?:\r\n))?[ \t])|(?:(?:[\x01-\x08\x0B\x0C\x0E-\x19\x7F]|[\x21-\x27\x2A-\x5B\x5D-\x7E])|(?:\\[\x00-\x08\x0B\x0C\x0E-\x7F])|(?1)))*(?:(?:[ \t]*(?:\r\n))?[ \t])?\)))*(?:(?:(?:(?:[ \t]*(?:\r\n))?[ \t])?(\((?:(?:(?:[ \t]*(?:\r\n))?[ \t])|(?:(?:[\x01-\x08\x0B\x0C\x0E-\x19\x7F]|[\x21-\x27\x2A-\x5B\x5D-\x7E])|(?:\\[\x00-\x08\x0B\x0C\x0E-\x7F])|(?1)))*(?:(?:[ \t]*(?:\r\n))?[ \t])?\)))|(?:(?:[ \t]*(?:\r\n))?[ \t])))?(?:[a-zA-Z0-9!#\$%&\'\*\+\-\/=\?\^_\{\}\|~]+(\.[a-zA-Z0-9!#\$%&\'\*\+\-\/=\?\^_\{\}\|~]+)*)+(?:(?:(?:(?:[ \t]*(?:\r\n))?[ \t])?(\((?:(?:(?:[ \t]*(?:\r\n))?[ \t])|(?:(?:[\x01-\x08\x0B\x0C\x0E-\x19\x7F]|[\x21-\x27\x2A-\x5B\x5D-\x7E])|(?:\\[\x00-\x08\x0B\x0C\x0E-\x7F])|(?1)))*(?:(?:[ \t]*(?:\r\n))?[ \t])?\)))*(?:(?:(?:(?:[ \t]*(?:\r\n))?[ \t])?(\((?:(?:(?:[ \t]*(?:\r\n))?[ \t])|(?:(?:[\x01-\x08\x0B\x0C\x0E-\x19\x7F]|[\x21-\x27\x2A-\x5B\x5D-\x7E])|(?:\\[\x00-\x08\x0B\x0C\x0E-\x7F])|(?1)))*(?:(?:[ \t]*(?:\r\n))?[ \t])?\)))|(?:(?:[ \t]*(?:\r\n))?[ \t])))?)|(?:(?:(?:(?:(?:[ \t]*(?:\r\n))?[ \t])?(\((?:(?:(?:[ \t]*(?:\r\n))?[ \t])|(?:(?:[\x01-\x08\x0B\x0C\x0E-\x19\x7F]|[\x21-\x27\x2A-\x5B\x5D-\x7E])|(?:\\[\x00-\x08\x0B\x0C\x0E-\x7F])|(?1)))*(?:(?:[ \t]*(?:\r\n))?[ \t])?\)))*(?:(?:(?:(?:[ \t]*(?:\r\n))?[ \t])?(\((?:(?:(?:[ \t]*(?:\r\n))?[ \t])|(?:(?:[\x01-\x08\x0B\x0C\x0E-\x19\x7F]|[\x21-\x27\x2A-\x5B\x5D-\x7E])|(?:\\[\x00-\x08\x0B\x0C\x0E-\x7F])|(?1)))*(?:(?:[ \t]*(?:\r\n))?[ \t])?\)))|(?:(?:[ \t]*(?:\r\n))?[ \t])))?\[((?:(?:[ \t]*(?:\r\n))?[ \t])?(?:(?:[\x01-\x08\x0B\x0C\x0E-\x19\x7F]|[\x21-\x5A\x5E-\x7E])|(?:\\[\x00-\x08\x0B\x0C\x0E-\x7F])))*?(?:(?:[ \t]*(?:\r\n))?[ \t])?\](?:(?:(?:(?:[ \t]*(?:\r\n))?[ \t])?(\((?:(?:(?:[ \t]*(?:\r\n))?[ \t])|(?:(?:[\x01-\x08\x0B\x0C\x0E-\x19\x7F]|[\x21-\x27\x2A-\x5B\x5D-\x7E])|(?:\\[\x00-\x08\x0B\x0C\x0E-\x7F])|(?1)))*(?:(?:[ \t]*(?:\r\n))?[ \t])?\)))*(?:(?:(?:(?:[ \t]*(?:\r\n))?[ \t])?(\((?:(?:(?:[ \t]*(?:\r\n))?[ \t])|(?:(?:[\x01-\x08\x0B\x0C\x0E-\x19\x7F]|[\x21-\x27\x2A-\x5B\x5D-\x7E])|(?:\\[\x00-\x08\x0B\x0C\x0E-\x7F])|(?1)))*(?:(?:[ \t]*(?:\r\n))?[ \t])?\)))|(?:(?:[ \t]*(?:\r\n))?[ \t])))?)))$/D', $address);
	}
}

/**
 * Wyświetla wiadomość dla użytkownika i opcjonalnie przekierowuje.
 *
 * @param string      $mes   Tekst wiadomości do wyświetlenia.
 * @param string      $dest  Adres URL do przekierowania (jeśli pusty, pozostanie na stronie).
 * @param string      $time  Czas w sekundach przed przekierowaniem (domyślnie 3).
 * @param bool        $topnav Czy wyświetlić wiadomość w górnym panelu nawigacyjnym (domyślnie false).
 *
 * @return void
 */
function message($mes, $dest = "", $time = "3", $topnav = false): void
{
    require_once('includes/classes/class.template.php'); // Załaduj klasę szablonów.
    $template = new template(); // Utwórz instancję klasy szablonów.
    $template->message($mes, $dest, $time, !$topnav); // Wywołaj metodę wyświetlającą wiadomość.
    exit; // Zakończ wykonywanie skryptu po wyświetleniu wiadomości.
}

/**
 * Oblicza maksymalną liczbę pól na planecie, uwzględniając poziom terraformera i bazy księżycowej.
 *
 * @param array $planet Dane planety (zawierające 'field_max' oraz poziomy terraformera i bazy księżycowej).
 *
 * @return int Maksymalna liczba pól.
 */
function CalculateMaxPlanetFields($planet): int
{
    global $resource; // Dostęp do globalnej tablicy zasobów.
    return $planet['field_max'] + ($planet[$resource[33]] * FIELDS_BY_TERRAFORMER) + ($planet[$resource[41]] * FIELDS_BY_MOONBASIS_LEVEL);
}

/**
 * Formatuje czas w sekundach do przyjaznego dla użytkownika formatu (Dni GG:MM:SS).
 *
 * @param int $seconds Czas w sekundach.
 *
 * @return string Sformatowany czas.
 */
function pretty_time($seconds): string
{
    global $LNG; // Dostęp do globalnego obiektu języka.

    $day    = floor($seconds / 86400);       // Oblicz dni.
    $hour   = floor((int)($seconds / 3600) % 24); // Oblicz godziny.
    $minute = floor((int)($seconds / 60) % 60);   // Oblicz minuty.
    $second = floor((int)$seconds % 60);        // Oblicz sekundy.

    $time  = '';

    if ($day > 0) {
        $time .= sprintf('%d%s ', $day, $LNG['short_day']); // Dodaj dni do sformatowanego czasu.
    }

    return $time . sprintf('%02d%s %02d%s %02d%s', // Formatuj pozostały czas (GG:MM:SS).
        $hour, $LNG['short_hour'],
        $minute, $LNG['short_minute'],
        $second, $LNG['short_second']
    );
}

/**
 * Formatuje czas lotu w sekundach do formatu GG:MM:SS.
 *
 * @param int $seconds Czas w sekundach.
 *
 * @return string Sformatowany czas lotu.
 */
function pretty_fly_time($seconds): string
{
    $hour   = floor($seconds / 3600);    // Oblicz godziny.
    $minute = floor($seconds / 60) % 60; // Oblicz minuty.
    $second = (int)$seconds % 60;         // Oblicz sekundy.

    return sprintf('%02d:%02d:%02d', $hour, $minute, $second); // Zwróć sformatowany czas lotu.
}

/**
 * Generuje link do strony galaktyki z podświetleniem planety startowej floty.
 *
 * @param array  $FleetRow Dane wiersza floty z bazy danych.
 * @param string $FleetType Dodatkowa klasa CSS dla linku (opcjonalna).
 *
 * @return string Link HTML do galaktyki.
 */
function GetStartAddressLink($FleetRow, $FleetType = ''): string
{
    return '<a href="game.php?page=galaxy&amp;galaxy=' . $FleetRow['fleet_start_galaxy'] . '&amp;system=' . $FleetRow['fleet_start_system'] . '" class="' . $FleetType . '">[' . $FleetRow['fleet_start_galaxy'] . ':' . $FleetRow['fleet_start_system'] . ':' . $FleetRow['fleet_start_planet'] . ']</a>';
}

/**
 * Generuje link do strony galaktyki z podświetleniem planety docelowej floty.
 *
 * @param array  $FleetRow Dane wiersza floty z bazy danych.
 * @param string $FleetType Dodatkowa klasa CSS dla linku (opcjonalna).
 *
 * @return string Link HTML do galaktyki.
 */
function GetTargetAddressLink($FleetRow, $FleetType = ''): string
{
    return '<a href="game.php?page=galaxy&amp;galaxy=' . $FleetRow['fleet_end_galaxy'] . '&amp;system=' . $FleetRow['fleet_end_system'] . '" class="' . $FleetType . '">[' . $FleetRow['fleet_end_galaxy'] . ':' . $FleetRow['fleet_end_system'] . ':' . $FleetRow['fleet_end_planet'] . ']</a>';
}

/**
 * Generuje link do strony galaktyki z podświetleniem aktualnie wyświetlanej planety.
 *
 * @param array $CurrentPlanet Dane aktualnie wyświetlanej planety z bazy danych.
 *
 * @return string Link HTML do galaktyki.
 */
function BuildPlanetAddressLink($CurrentPlanet): string
{
    return '<a href="game.php?page=galaxy&amp;galaxy=' . $CurrentPlanet['galaxy'] . '&amp;system=' . $CurrentPlanet['system'] . '">[' . $CurrentPlanet['galaxy'] . ':' . $CurrentPlanet['system'] . ':' . $CurrentPlanet['planet'] . ']</a>';
}

/**
 * Formatuje liczbę zmiennoprzecinkową lub całkowitą do formatu z separatorem tysięcy i dziesiętnym.
 * Wykorzystuje `floatToString` do poprawnej konwersji liczb zmiennoprzecinkowych.
 *
 * @param float|int $n   Liczba do sformatowania.
 * @param int       $dec Liczba miejsc po przecinku (domyślnie 0).
 *
 * @return string Sformatowana liczba.
 */
function pretty_number($n, $dec = 0): string
{
    return number_format(floatToString($n, $dec), $dec, ',', '.');
}

/**
 * Pobiera informacje o użytkowniku na podstawie jego ID.
 *
 * @param int          $userId  ID użytkownika do wyszukania.
 * @param string|array $GetInfo Kolumny do pobrania z tabeli użytkowników. Może być ciągiem '*' lub tablicą nazw kolumn.
 *
 * @return array|null Tablica asocjacyjna z danymi użytkownika lub null, jeśli użytkownik nie istnieje.
 */
function GetUserByID($userId, $GetInfo = "*"): ?array
{
    if (is_array($GetInfo)) {
        $GetOnSelect = implode(', ', $GetInfo); // Łączy nazwy kolumn w ciąg znaków oddzielony przecinkami.
    } else {
        $GetOnSelect = $GetInfo; // Użyj podanego ciągu znaków jako listy kolumn.
    }

    $sql = 'SELECT ' . $GetOnSelect . ' FROM %%USERS%% WHERE id = :userId';

    $User = Database::get()->selectSingle($sql, [
        ':userId' => $userId
    ]);

    return $User;
}

/**
 * Zamienia znaki nowej linii w tekście na tagi `<br>`.
 * Obsługuje różne formaty nowych linii (`\r\n`, `\r`, `\n`) i uwzględnia wersję PHP
 * w celu zapewnienia kompatybilności (używa `nl2br` od PHP 8.0.0).
 *
 * @param string $text Tekst do zamiany.
 *
 * @return string Tekst z zamienionymi znakami nowej linii na tagi `<br>`.
 */
function makebr($text): string
{
    $BR = "<br>\n";
    return (version_compare(PHP_VERSION, "8.0.0", ">=")) ? nl2br($text, false) : strtr($text, ["\r\n" => $BR, "\r" => $BR, "\n" => $BR]);
}

/**
 * Sprawdza, czy atakujący i atakowany gracz podlegają ochronie przed noobami.
 *
 * @param array $OwnerPlayer  Dane atakującego gracza.
 * @param array $TargetPlayer Dane atakowanego gracza.
 * @param array $Player       Dane aktualnie zalogowanego gracza (prawdopodobnie atakującego).
 *
 * @return array Tablica asocjacyjna z informacjami o ochronie przed noobami dla obu graczy.
 * Klucze: 'NoobPlayer' (czy atakowany jest noobem), 'StrongPlayer' (czy atakujący jest silniejszy od nooba).
 */
function CheckNoobProtec($OwnerPlayer, $TargetPlayer, $Player): array
{
    $config = Config::get(); // Pobierz konfigurację gry.
    if (
        $config->noobprotection == 0 ||
        $config->noobprotectiontime == 0 ||
        $config->noobprotectionmulti == 0 ||
        $Player['banaday'] > TIMESTAMP ||
        $Player['onlinetime'] < TIMESTAMP - INACTIVE
    ) {
        return ['NoobPlayer' => false, 'StrongPlayer' => false]; // Ochrona przed noobami wyłączona lub gracz zbanowany/nieaktywny.
    }

    return [
        'NoobPlayer'   => (
            /* WAHR:
                Jeśli gracz ma mniej lub równo punktów niż limit czasu ochrony noobów ORAZ
                Jeśli atakujący ma więcej punktów niż wielokrotność punktów atakowanego.
            */
            ($TargetPlayer['total_points'] <= $config->noobprotectiontime) && // Default: 25.000
            ($OwnerPlayer['total_points'] > $TargetPlayer['total_points'] * $config->noobprotectionmulti)
        ),
        'StrongPlayer' => (
            /* WAHR:
                Jeśli gracz ma mniej punktów niż limit czasu ochrony noobów ORAZ
                Jeśli punkty atakującego pomnożone przez współczynnik ochrony noobów są mniejsze niż punkty atakowanego.
            */
            ($OwnerPlayer['total_points'] < $config->noobprotectiontime) && // Default: 5.000
            ($OwnerPlayer['total_points'] * $config->noobprotectionmulti < $TargetPlayer['total_points'])
        ),
    ];
}

/**
 * Skraca duże liczby, dodając odpowiedni sufiks (K, M, B, T, Q, Q+, S, S+, O, N).
 *
 * @param float|int $number Liczba do skrócenia.
 * @param int|null  $decial Liczba miejsc po przecinku (null - automatyczne).
 *
 * @return string Skrócona i sformatowana liczba z sufiksem.
 */
function shortly_number($number, $decial = null): string
{
    $negate = $number < 0 ? -1 : 1; // Zapamiętaj znak liczby.
    $number = abs($number);          // Weź wartość bezwzględną.
    $unit = ["", "K", "M", "B", "T", "Q", "Q+", "S", "S+", "O", "N"]; // Jednostki.
    $key = 0;

    if ($number >= 1000000) {
        ++$key;
        while ($number >= 1000000) {
            ++$key;
            $number = $number / 1000000;
        }
    } elseif ($number >= 1000) {
        ++$key;
        $number = $number / 1000;
    }

    $decial = !is_numeric($decial) ? ((int)(((int)$number != $number) && $key != 0 && $number != 0 && $number < 100)) : $decial;
    return pretty_number($negate * $number, $decial) . '&nbsp;' . $unit[$key];
}

/**
 * Konwertuje liczbę zmiennoprzecinkową na ciąg znaków, opcjonalnie formatując z podaną precyzją
 * i zamieniając przecinek na kropkę (przydatne do formatowania liczb dla JavaScript).
 *
 * @param float|int $number Liczba do konwersji.
 * @param int       $Pro    Liczba miejsc po przecinku (domyślnie 0).
 * @param bool      $output Czy zamienić przecinek na kropkę (true) czy zwrócić bez zmian (false).
 *
 * @return string Liczba w postaci ciągu znaków.
 */
function floatToString($number, $Pro = 0, $output = false): string
{
    return $output ? str_replace(",", ".", sprintf("%." . $Pro . "f", $number)) : sprintf("%." . $Pro . "f", $number);
}

/**
 * Sprawdza, czy moduł o podanym ID jest dostępny dla aktualnego użytkownika.
 * Administratorzy mają dostęp do wszystkich modułów.
 *
 * @param int $ID ID modułu do sprawdzenia.
 *
 * @return bool True, jeśli moduł jest dostępny, false w przeciwnym razie.
 */
function isModuleAvailable($ID): bool
{
    global $USER; // Dostęp do globalnej zmiennej zawierającej dane użytkownika.
    $modules = explode(';', Config::get()->moduls); // Pobierz listę dostępnych modułów z konfiguracji.

    // Domyślnie zakładaj, że moduł jest niedostępny, jeśli jego ID nie istnieje w konfiguracji.
    if (!isset($modules[$ID])) {
        $modules[$ID] = 0; // Ustaw na 0 (niedostępny).
    }

    // Moduł jest dostępny, jeśli jego wartość w konfiguracji to 1 LUB użytkownik jest administratorem.
    return $modules[$ID] == 1 || (isset($USER['authlevel']) && $USER['authlevel'] > AUTH_USR);
}

/**
 * Czyści katalogi cache (główny i z szablonami), ponownie oblicza zadania cron i czyści cache statystyk.
 * Aktualizuje również wersję w konfiguracji na 'git'.
 *
 * @return void
 */
function ClearCache(): void
{
    $DIRS = ['cache/', 'cache/templates/']; // Tablica katalogów cache do wyczyszczenia.
    foreach ($DIRS as $DIR) {
        $FILES = array_diff(scandir($DIR), ['..', '.', '.htaccess']); // Pobierz listę plików i katalogów, pomijając '.' i '..'.
        foreach ($FILES as $FILE) {
            $fullPath = ROOT_PATH . $DIR . $FILE;
            if (is_dir($fullPath)) {
                continue; // Pomiń katalogi.
            }
            unlink($fullPath); // Usuń plik.
        }
    }

    $template = new template(); // Utwórz instancję klasy szablonów.
    $template->clearAllCache(); // Wyczyść cache szablonów.

    require_once 'includes/classes/Cronjob.class.php'; // Załaduj klasę Cronjob.
    Cronjob::reCalculateCronjobs(); // Ponownie oblicz następne uruchomienia zadań cron.

    $sql = 'UPDATE %%PLANETS%% SET eco_hash = :ecoHash;'; // Zapytanie SQL do zresetowania hashów ekonomii planet.
    Database::get()->update($sql, [
        ':ecoHash' => ''
    ]);
    clearstatcache(); // Czyści cache statystyk PHP dla plików i katalogów.

    // Komentowany kod dotyczący pobierania rewizji z Git (prawdopodobnie nie działa w każdym środowisku).
    $config = Config::get(); // Pobierz konfigurację.
    $version = explode('.', $config->VERSION); // Rozdziel obecną wersję.
    $config->VERSION = $version[0] . '.' . $version[1] . '.' . 'git'; // Ustaw wersję na 'x.y.git'.
    $config->save(); // Zapisz zaktualizowaną konfigurację.
}

/**
 * Sprawdza, czy użytkownik ma uprawnienia do dostępu do danej strony/funkcji (na podstawie praw administratora lub praw specyficznych).
 *
 * @param string $side Nazwa strony/funkcji do sprawdzenia uprawnień.
 *
 * @return bool True, jeśli użytkownik ma dostęp, false w przeciwnym razie.
 */
function allowedTo($side): bool
{
    global $USER; // Dostęp do globalnej zmiennej zawierającej dane użytkownika.
    return ($USER['authlevel'] == AUTH_ADM || (isset($USER['rights']) && isset($USER['rights'][$side]) && $USER['rights'][$side] == 1));
}

/**
 * Sprawdza, czy dodatek Ciemnej Materii jest aktywny na podstawie czasu wygaśnięcia.
 *
 * @param int $Extra Czas wygaśnięcia dodatku (timestamp).
 * @param int $Time  Aktualny czas (timestamp).
 *
 * @return bool True, jeśli dodatek jest aktywny, false w przeciwnym razie.
 */
function isactiveDMExtra($Extra, $Time): bool
{
    return $Time - $Extra <= 0; // Dodatek jest aktywny, jeśli czas wygaśnięcia jest w przyszłości lub teraźniejszości.
}

/**
 * Zwraca true lub false w zależności od tego, czy dodatek Ciemnej Materii jest aktywny.
 *
 * @param int   $Extra Czas wygaśnięcia dodatku (timestamp).
 * @param int   $Time  Aktualny czas (timestamp).
 * @param mixed $true  Wartość do zwrócenia, jeśli dodatek jest aktywny.
 * @param mixed $false Wartość do zwrócenia, jeśli dodatek nie jest aktywny.
 *
 * @return mixed $true lub $false w zależności od statusu dodatku.
 */
function DMExtra($Extra, $Time, $true, $false)
{
    return isactiveDMExtra($Extra, $Time) ? $true : $false;
}

/**
 * Generuje losowy ciąg znaków w formacie MD5 (32 znaki heksadecymalne).
 *
 * @return string Losowy ciąg znaków.
 */
function getRandomString(): string
{
    return md5(uniqid());
}

/**
 * Sprawdza, czy użytkownik jest w trybie urlopu.
 *
 * @param array $USER Dane użytkownika.
 *
 * @return bool True, jeśli użytkownik jest w trybie urlopu, false w przeciwnym razie.
 */
function isVacationMode($USER): bool
{
    return ($USER['urlaubs_modus'] == 1) ? true : false;
}

/**
 * Generuje i wysyła pusty obraz GIF z odpowiednimi nagłówkami cache.
 * Używane prawdopodobnie do zastępowania obrazków lub śledzenia bez wyświetlania treści.
 *
 * @return void
 */
function clearGIF(): void
{
    header('Cache-Control: no-cache');   // Wyłącz buforowanie po stronie klienta.
    header('Content-type: image/gif');  // Ustaw typ zawartości na obraz GIF.
    header('Content-length: 43');       // Ustaw długość zawartości na 43 bajty (rozmiar minimalnego przezroczystego GIF-a).
    header('Expires: 0');              // Ustaw datę wygaśnięcia na przeszłość, aby wymusić ponowne pobranie.
    echo("\x47\x49\x46\x38\x39\x61\x01\x00\x01\x00\x80\x00\x00\x00\x00\x00\x00\x00\x00\x21\xF9\x04\x01\x00\x00\x00\x00\x2C\x00\x00\x00\x00\x01\x00\x01\x00\x00\x02\x02\x44\x01\x00\x3B");
    exit; // Zakończ wykonywanie skryptu po wysłaniu obrazu.
}

/*
 * Handler dla nie przechwyconych wyjątków.
 * Wyświetla komunikat o błędzie i loguje go.
 *
 * @param object $exception Obiekt wyjątku.
 *
 * @return void
 */
function exceptionHandler($exception)
{
	/** @var $exception ErrorException|Exception */

	if(!headers_sent()) {
		if (!class_exists('HTTP', false)) {
			require_once('includes/classes/HTTP.class.php');
		}
		
		HTTP::sendHeader('HTTP/1.1 503 Service Unavailable');
	}

	if(method_exists($exception, 'getSeverity')) {
		$errno	= $exception->getSeverity();
	} else {
		$errno	= E_USER_ERROR;
	}
	
	$errorType = array(
		E_ERROR				=> 'ERROR',
		E_WARNING			=> 'WARNING',
		E_PARSE				=> 'PARSING ERROR',
		E_NOTICE			=> 'NOTICE',
		E_CORE_ERROR		=> 'CORE ERROR',
		E_CORE_WARNING	   => 'CORE WARNING',
		E_COMPILE_ERROR		=> 'COMPILE ERROR',
		E_COMPILE_WARNING	=> 'COMPILE WARNING',
		E_USER_ERROR		=> 'USER ERROR',
		E_USER_WARNING		=> 'USER WARNING',
		E_USER_NOTICE		=> 'USER NOTICE',
		E_STRICT			=> 'STRICT NOTICE',
		E_RECOVERABLE_ERROR	=> 'RECOVERABLE ERROR',
		E_DEPRECATED		 => 'DEPRECATED',
		E_USER_DEPRECATED	 => 'USER-TRIGGERED DEPRECATED',
	);
	
	if(file_exists(ROOT_PATH.'install/VERSION'))
	{
		$VERSION	= file_get_contents(ROOT_PATH.'install/VERSION').' (FILE)';
	}
	else
	{
		$VERSION	= 'UNKNOWN';
	}
	$gameName	= '-';
	
	if(MODE !== 'INSTALL')
	{
		try
		{
			$config		= Config::get();
			$gameName	= $config->game_name;
			$VERSION	= $config->VERSION;
		} catch(ErrorException $e) {
		}
	}
	
	
	$DIR		= MODE == 'INSTALL' ? '..' : '.';
	ob_start();
	echo '<!DOCTYPE html>
<!--[if lt IE 7 ]> <html lang="pl" class="no-js ie6"> <![endif]-->
<!--[if IE 7 ]>    <html lang="pl" class="no-js ie7"> <![endif]-->
<!--[if IE 8 ]>    <html lang="pl" class="no-js ie8"> <![endif]-->
<!--[if IE 9 ]>    <html lang="pl" class="no-js ie9"> <![endif]-->
<!--[if (gt IE 9)|!(IE)]><!--> <html lang="pl" class="no-js"> <!--<![endif]-->
<head>
	<title>'.$gameName.' - '.$errorType[$errno].'</title>
	<meta name="generator" content="FreeStar '.$VERSION.'">
	<!-- 
		This website is powered by FreeStar '.$VERSION.'
		FreeStar is copyright 2009-2013
	-->
	<meta http-equiv="content-type" content="text/html; charset=UTF-8">
	<link rel="stylesheet" type="text/css" href="'.$DIR.'/styles/resource/css/base/boilerplate.css?v='.$VERSION.'">
	<link rel="stylesheet" type="text/css" href="'.$DIR.'/styles/resource/css/ingame/main.css?v='.$VERSION.'">
	<link rel="stylesheet" type="text/css" href="'.$DIR.'/styles/resource/css/base/jquery.css?v='.$VERSION.'">
	<link rel="stylesheet" type="text/css" href="'.$DIR.'/styles/theme/star/formate.css?v='.$VERSION.'">
	<link rel="shortcut icon" href="./favicon.ico" type="image/x-icon">
	<script type="text/javascript">
	var ServerTimezoneOffset = -3600;
	var serverTime 	= new Date(2012, 2, 12, 14, 43, 36);
	var startTime	= serverTime.getTime();
	var localTime 	= serverTime;
	var localTS 	= startTime;
	var Gamename	= document.title;
	var Ready		= "Fertig";
	var Skin		= "'.$DIR.'/styles/theme/star/";
	var Lang		= "de";
	var head_info	= "Information";
	var auth		= 3;
	var days 		= ["So","Mo","Di","Mi","Do","Fr","Sa"] 
	var months 		= ["Jan","Feb","Mar","Apr","Mai","Jun","Jul","Aug","Sep","Okt","Nov","Dez"] ;
	var tdformat	= "[M] [D] [d] [H]:[i]:[s]";
	var queryString	= "";

	setInterval(function() {
		serverTime.setSeconds(serverTime.getSeconds()+1);
	}, 1000);
	</script>
	<script type="text/javascript" src="'.$DIR.'/scripts/base/jquery.js?v=2123"></script>
	<script type="text/javascript" src="'.$DIR.'/scripts/base/jquery.ui.js?v=2123"></script>
	<script type="text/javascript" src="'.$DIR.'/scripts/base/jquery.cookie.js?v=2123"></script>
	<script type="text/javascript" src="'.$DIR.'/scripts/base/jquery.fancybox.js?v=2123"></script>
	<script type="text/javascript" src="'.$DIR.'/scripts/base/jquery.validationEngine.js?v=2123"></script>
	<script type="text/javascript" src="'.$DIR.'/scripts/base/tooltip.js?v=2123"></script>
	<script type="text/javascript" src="'.$DIR.'/scripts/game/base.js?v=2123"></script>
</head>
<body id="overview" class="full">
<table width="960">
	<tr>
		<th>'.$errorType[$errno].'</th>
	</tr>
	<tr>
		<td class="left">
			<b>Message: </b>'.$exception->getMessage().'<br>
			<b>File: </b>'.$exception->getFile().'<br>
			<b>Line: </b>'.$exception->getLine().'<br>
			<b>URL: </b>'.PROTOCOL.HTTP_HOST.$_SERVER['REQUEST_URI'].'<br>
			<b>PHP-Version: </b>'.PHP_VERSION.'<br>
			<b>PHP-API: </b>'.php_sapi_name().'<br>
			<b>FreeStar Version: </b>'.$VERSION.'<br>
			<b>Debug Backtrace:</b><br>'.makebr(htmlspecialchars($exception->getTraceAsString())).'
		</td>
	</tr>
</table>
</body>
</html>';

	echo str_replace(array('\\', ROOT_PATH, substr(ROOT_PATH, 0, 15)), array('/', '/', 'FILEPATH '), ob_get_clean());
	
	$errorText	= date("[d-M-Y H:i:s]", TIMESTAMP).' '.$errorType[$errno].': "'.strip_tags($exception->getMessage())."\"\r\n";
	$errorText	.= 'File: '.$exception->getFile().' | Line: '.$exception->getLine()."\r\n";
	$errorText	.= 'URL: '.PROTOCOL.HTTP_HOST.$_SERVER['REQUEST_URI'].' | Version: '.$VERSION."\r\n";
	$errorText	.= "Stack trace:\r\n";
	$errorText	.= str_replace(ROOT_PATH, '/', htmlspecialchars(str_replace('\\', '/',$exception->getTraceAsString())))."\r\n";
	
	if(is_writable('includes/error.log'))
	{
		file_put_contents('includes/error.log', $errorText, FILE_APPEND);
	}
}
/*
 * Handler dla błędów PHP.
 *
 * @param int    $errno   Poziom błędu (np. E_WARNING, E_NOTICE).
 * @param string $errstr  Komunikat błędu.
 * @param string $errfile Nazwa pliku, w którym wystąpił błąd.
 * @param int    $errline Numer linii, w której wystąpił błąd.
 *
 * @throws ErrorException Wyrzuca wyjątek ErrorException dla każdego błędu,
 * umożliwiając jednolite traktowanie błędów i wyjątków.
 *
 * @return bool Jeśli błąd jest ukryty (nie jest raportowany zgodnie z error_reporting()), zwraca true, w przeciwnym razie wyrzuca wyjątek.
 */
function errorHandler($errno, $errstr, $errfile, $errline): bool
{
    // Sprawdź, czy dany poziom błędu jest uwzględniony w aktualnych ustawieniach raportowania błędów.
    if (!($errno & error_reporting())) {
        return false; // Jeśli błąd nie jest raportowany, zwróć false (oznacza, że został "ukryty").
    }

    // Wyrzuć wyjątek ErrorException dla danego błędu.
    // Dzięki temu błędy PHP mogą być obsługiwane w bloku try-catch, podobnie jak standardowe wyjątki.
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}

// "Workaround" dla funkcji array_replace_recursive, która była dostępna dopiero od PHP w wersji 5.3.0.
if (!function_exists('array_replace_recursive')) {
    /**
     * Rekurencyjnie zastępuje elementy w pierwszym array elementami z kolejnych array.
     * Zastępuje elementy z array1 w array. Jeśli klucz z array1 istnieje w array i obie wartości są array,
     * funkcja wywołuje się rekurencyjnie. W przeciwnym razie wartość z array1 zastępuje wartość z array.
     *
     * @param array $array  Podstawowa tablica, do której będą dodawane/zastępowane elementy.
     * @param array $array1 Tablica, której elementy będą dodawane/zastępowane w $array.
     * @param array ...$args Kolejne tablice do scalenia.
     *
     * @return array Scalona tablica.
     */
    function array_replace_recursive()
    {
        // Definicja funkcji rekurencyjnej do scalania tablic.
        if (!function_exists('recurse')) {
            function recurse($array, $array1)
            {
                foreach ($array1 as $key => $value) {
                    // Stwórz nowy klucz w $array, jeśli nie istnieje lub nie jest tablicą.
                    if (!isset($array[$key]) || (isset($array[$key]) && !is_array($array[$key]))) {
                        $array[$key] = [];
                    }

                    // Nadpisz wartość w tablicy bazowej.
                    if (is_array($value)) {
                        // Jeśli wartość jest tablicą, wywołaj rekurencyjnie funkcję recurse.
                        $value = recurse($array[$key], $value);
                    }
                    $array[$key] = $value;
                }
                return $array;
            }
        }

        // Obsłuż argumenty, scalaj jeden po drugim.
        $args = func_get_args();
        $array = $args[0];
        if (!is_array($array)) {
            return $array;
        }
        $count = count($args);
        for ($i = 1; $i < $count; ++$i) {
            if (is_array($args[$i])) {
                $array = recurse($array, $args[$i]);
            }
        }
        return $array;
    }
}
