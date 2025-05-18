<?php

class Universe
{
    /**
     * @var int|null ID aktualnego uniwersum.
     */
    private static $currentUniverse = null;

    /**
     * @var int|null ID emulowanego uniwersum (używane np. w panelu administracyjnym).
     */
    private static $emulatedUniverse = null;

    /**
     * @var array Tablica przechowująca ID dostępnych uniwersów.
     */
    private static $availableUniverses = [];

    /**
     * Zwraca ID aktualnego uniwersum. Jeśli nie jest ustawione, próbuje je zdefiniować.
     *
     * @return int ID aktualnego uniwersum.
     */
    static public function current(): int
    {
        if (is_null(self::$currentUniverse)) {
            self::$currentUniverse = self::defineCurrentUniverse();
        }

        return self::$currentUniverse;
    }

    /**
     * Dodaje ID uniwersum do listy dostępnych uniwersów.
     *
     * @param int $universe ID uniwersum do dodania.
     *
     * @return void
     */
    static public function add($universe): void
    {
        self::$availableUniverses[] = $universe;
    }

    /**
     * Zwraca ID aktualnie emulowanego uniwersum. Jeśli nie jest ustawione, próbuje je pobrać z sesji lub ustawia na aktualne.
     *
     * @return int ID emulowanego uniwersum.
     */
    static public function getEmulated(): int
    {
        if (is_null(self::$emulatedUniverse)) {
            $session = Session::load();
            if (isset($session->emulatedUniverse)) {
                self::setEmulated($session->emulatedUniverse);
            } else {
                self::setEmulated(self::current());
            }
        }

        return self::$emulatedUniverse;
    }

    /**
     * Ustawia ID emulowanego uniwersum i zapisuje je w sesji.
     *
     * @param int $universeId ID uniwersum do emulacji.
     *
     * @return bool True po ustawieniu emulowanego uniwersum.
     *
     * @throws Exception Wyrzuca wyjątek, jeśli podano nieznane ID uniwersum.
     */
    static public function setEmulated($universeId): bool
    {
        if (!self::exists($universeId)) {
            throw new Exception('Nieznane ID uniwersum: ' . $universeId);
        }

        $session = Session::load();
        $session->emulatedUniverse = $universeId;
        $session->save();

        self::$emulatedUniverse = $universeId;

        return true;
    }

    /**
     * Określa ID aktualnego uniwersum na podstawie ciasteczek, parametrów GET lub kluczy sesji.
     *
     * @return int ID aktualnego uniwersum.
     */
    static private function defineCurrentUniverse(): int
    {
        $universe = null;
        if (MODE === 'INSTALL') {
            // Instalator zawsze działa w pierwszym uniwersum.
            return ROOT_UNI;
        }

        // Jeśli jest więcej niż jeden dostępny uniwersum.
        if (count(self::availableUniverses()) != 1) {
            if (MODE == 'LOGIN') {
                // Próbuj pobrać ID uniwersum z ciasteczka lub parametru GET podczas logowania.
                if (isset($_COOKIE['uni'])) {
                    $universe = (int)$_COOKIE['uni'];
                }

                if (isset($_REQUEST['uni'])) {
                    $universe = (int)$_REQUEST['uni'];
                }
            } elseif (MODE == 'ADMIN' && isset($_SESSION['admin_uni'])) {
                // Próbuj pobrać ID uniwersum z sesji administratora.
                $universe = (int)$_SESSION['admin_uni'];
            }

            // Jeśli ID uniwersum nadal nie jest ustawione.
            if (is_null($universe)) {
                // Próbuj określić ID uniwersum na podstawie nazwy hosta (dla wildcard domen).
                if (UNIS_WILDCAST) {
                    $temp = explode('.', $_SERVER['HTTP_HOST']);
                    $temp = substr($temp[0], 3);
                    if (is_numeric($temp)) {
                        $universe = (int)$temp;
                    } else {
                        $universe = ROOT_UNI;
                    }
                } else {
                    // Próbuj określić ID uniwersum na podstawie ścieżki URL (dla standardowych konfiguracji).
                    if (isset($_SERVER['REDIRECT_UNI'])) {
                        // Apache - szybsze niż preg_match.
                        $universe = (int)$_SERVER["REDIRECT_UNI"];
                    } elseif (isset($_SERVER['REDIRECT_REDIRECT_UNI'])) {
                        // Patch dla www.top-hoster.de - Hoster.
                        $universe = (int)$_SERVER["REDIRECT_REDIRECT_UNI"];
                    } elseif (preg_match('!/uni([0-9]+)/!', HTTP_PATH, $match)) {
                        if (isset($match[1])) {
                            $universe = (int)$match[1];
                        }
                    } else {
                        $universe = ROOT_UNI;
                    }
                }

                // Jeśli ID uniwersum nadal nie jest ustawione lub nie istnieje, przekieruj do głównego uniwersum.
                if (!isset($universe) || !self::exists($universe)) {
                    HTTP::redirectToUniverse(ROOT_UNI);
                }
            }
        } else {
            // Jeśli jest tylko jeden dostępny uniwersum, użyj ROOT_UNI i przekieruj, jeśli ścieżka jest nieprawidłowa.
            if (HTTP_ROOT != HTTP_BASE) {
                HTTP::redirectTo(PROTOCOL . HTTP_HOST . HTTP_BASE . HTTP_FILE, true);
            }
            $universe = ROOT_UNI;
        }

        return $universe; // Zwróć określony ID uniwersum.
    }

    /**
     * Zwraca tablicę zawierającą ID wszystkich dostępnych uniwersów.
     *
     * @return array Tablica ID dostępnych uniwersów.
     */
    static public function availableUniverses(): array
    {
        return self::$availableUniverses;
    }

    /**
     * Sprawdza, czy uniwersum o podanym ID istnieje na liście dostępnych uniwersów.
     *
     * @param int $universeId ID uniwersum do sprawdzenia.
     *
     * @return bool True, jeśli uniwersum istnieje, false w przeciwnym razie.
     */
    static public function exists($universeId): bool
    {
        return in_array($universeId, self::availableUniverses());
    }
}