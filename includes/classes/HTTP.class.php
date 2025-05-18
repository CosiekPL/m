<?php

class HTTP
{
    /**
     * Przekierowuje przeglądarkę na podany adres URL.
     *
     * @param string $URL      Adres URL do przekierowania.
     * @param bool   $external Czy adres URL jest zewnętrzny (poza domeną gry). Domyślnie false.
     *
     * @return void
     */
    static public function redirectTo($URL, $external = false): void
    {
        if ($external) {
            self::sendHeader('Location', $URL); // Przekierowanie na zewnętrzny URL.
        } else {
            self::sendHeader('Location', HTTP_PATH . $URL); // Przekierowanie w obrębie domeny gry.
        }
        exit; // Zakończ wykonywanie skryptu po przekierowaniu.
    }

    /**
     * Wysyła nagłówek HTTP.
     *
     * @param string      $name  Nazwa nagłówka.
     * @param string|null $value Wartość nagłówka (jeśli null, wysyłany jest tylko sam nagłówek). Domyślnie null.
     *
     * @return void
     */
    static public function sendHeader($name, $value = null): void
    {
        header($name . (!is_null($value) ? ': ' . $value : ''));
    }

    /**
     * Przekierowuje przeglądarkę do określonego uniwersum.
     *
     * @param int $universe ID uniwersum docelowego.
     *
     * @return void
     */
    static public function redirectToUniverse($universe): void
    {
        HTTP::redirectTo(PROTOCOL . HTTP_HOST . HTTP_BASE . "uni" . $universe . "/" . HTTP_FILE, true);
    }

    /**
     * Ustawia ciasteczko (cookie).
     *
     * @param string      $name   Nazwa ciasteczka.
     * @param string      $value  Wartość ciasteczka (domyślnie pusty ciąg znaków).
     * @param int|null    $toTime Czas wygaśnięcia ciasteczka jako timestamp Unix (jeśli null, wygaśnie po zamknięciu przeglądarki). Domyślnie null.
     *
     * @return void
     */
    static public function sendCookie($name, $value = "", $toTime = null): void
    {
        setcookie($name, $value, $toTime);
    }

    /**
     * Pobiera wartość z tablic $_REQUEST ($_GET lub $_POST) z opcjonalnym filtrowaniem i typowaniem.
     *
     * @param string       $name       Nazwa parametru do pobrania.
     * @param mixed        $default    Wartość domyślna, jeśli parametr nie istnieje. Określa również typ oczekiwanej wartości.
     * @param bool         $multibyte  Czy oczekiwać i obsługiwać znaki wielobajtowe (UTF-8). Domyślnie false.
     * @param bool         $highnum    Czy traktować wartość domyślną jako float (dla bardzo dużych liczb). Domyślnie false.
     *
     * @return mixed Wartość parametru, sfiltrowana i przekonwertowana do odpowiedniego typu, lub wartość domyślna, jeśli parametr nie istnieje.
     */
    static public function _GP($name, $default, $multibyte = false, $highnum = false)
    {
        if (!isset($_REQUEST[$name])) {
            return $default; // Zwróć wartość domyślną, jeśli parametr nie istnieje.
        }

        if (is_float($default) || $highnum) {
            return (float)$_REQUEST[$name]; // Przekonwertuj na float.
        }

        if (is_int($default)) {
            return (int)$_REQUEST[$name]; // Przekonwertuj na int.
        }

        if (is_string($default)) {
            return self::_quote($_REQUEST[$name], $multibyte); // Przetwórz jako string.
        }

        if (is_array($default) && is_array($_REQUEST[$name])) {
            return self::_quoteArray($_REQUEST[$name], $multibyte, !empty($default) && $default[0] === 0); // Przetwórz jako tablicę.
        }

        return $default; // Zwróć wartość domyślną w innych przypadkach.
    }

    /**
     * Rekurencyjnie przetwarza tablicę wartości, stosując _quote na każdym elemencie.
     *
     * @param array $var       Tablica do przetworzenia.
     * @param bool  $multibyte Czy obsługiwać znaki wielobajtowe.
     * @param bool  $onlyNumbers Czy konwertować tylko na liczby całkowite.
     *
     * @return array Przetworzona tablica.
     */
    private static function _quoteArray($var, $multibyte, $onlyNumbers = false): array
    {
        $data = [];
        foreach ($var as $key => $value) {
            if (is_array($value)) {
                $data[$key] = self::_quoteArray($value, $multibyte); // Rekurencyjnie przetwórz podtablicę.
            } elseif ($onlyNumbers) {
                $data[$key] = (int)$value; // Przekonwertuj na int.
            } else {
                $data[$key] = self::_quote($value, $multibyte); // Przetwórz pojedynczą wartość.
            }
        }

        return $data;
    }

    /**
     * Filtruje i eskejpuje pojedynczą wartość (string).
     * Usuwa znaki nowej linii, konwertuje specjalne znaki HTML i opcjonalnie filtruje znaki wielobajtowe.
     *
     * @param string $var       Wartość do przetworzenia.
     * @param bool   $multibyte Czy obsługiwać znaki wielobajtowe.
     *
     * @return string Przetworzony string.
     */
    private static function _quote($var, $multibyte): string
    {
        $var = str_replace(["\r\n", "\r", "\0"], ["\n", "\n", ''], $var); // Usuń różne formy nowych linii i null byte.
        $var = htmlspecialchars($var, ENT_QUOTES, 'UTF-8'); // Konwertuj specjalne znaki HTML na encje.
        $var = trim($var); // Usuń białe znaki z początku i końca stringa.

        if ($multibyte) {
            if (!preg_match('/^./u', $var)) {
                $var = ''; // Jeśli string zawiera tylko nieprawidłowe znaki UTF-8, ustaw na pusty.
            }
        } else {
            $var = preg_replace('/[\x80-\xFF]/', '?', $var); // Jeśli nie obsługujemy multibyte, zamień znaki spoza ASCII na '?'.
        }

        return $var;
    }
}