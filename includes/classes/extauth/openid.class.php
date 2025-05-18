<?php

require 'includes/libs/OpenID/openid.php'; // Załaduj bibliotekę OpenID.

/**
 * Klasa OpenIDAuth implementująca interfejs externalAuth.
 * Obsługuje logowanie i rejestrację za pomocą protokołu OpenID.
 */
class OpenIDAuth implements externalAuth
{
    /**
     * @var LightOpenID|null Obiekt biblioteki LightOpenID.
     */
    private $oidObj = null;

    /**
     * Konstruktor klasy. Inicjalizuje obiekt LightOpenID i obsługuje przekierowania
     * w przypadku braku trybu OpenID w żądaniu.
     */
    public function __construct()
    {
        $this->oidObj = new LightOpenID(PROTOCOL . HTTP_HOST); // Inicjalizuj LightOpenID z adresem URL serwera.
        if (!$this->oidObj->mode) {
            // Jeśli nie ma trybu OpenID w żądaniu (użytkownik nie wraca z serwera OpenID).
            if (isset($_REQUEST['openid_identifier'])) {
                // Jeśli podano identyfikator OpenID w żądaniu.
                $this->oidObj->identity = $_REQUEST['openid_identifier']; // Ustaw tożsamość OpenID.
                $this->oidObj->required = ['namePerson/friendly', 'contact/email', 'pref/language']; // Wymagane atrybuty.
                $this->oidObj->optional = ['namePerson']; // Opcjonalne atrybuty.

                HTTP::sendHeader('Location', $this->oidObj->authUrl()); // Przekieruj użytkownika na serwer OpenID w celu autoryzacji.
                exit;
            } else {
                HTTP::redirectTo('index.php?code=4'); // Przekieruj na stronę błędu, jeśli brak identyfikatora OpenID.
            }
        }
    }

    /**
     * Sprawdza, czy aktualnie trwa proces autoryzacji OpenID.
     *
     * @return bool Zawsze zwraca false, ponieważ logika przekierowania jest w konstruktorze.
     */
    public function isActiveMode(): bool
    {
        return false;
    }

    /**
     * Sprawdza, czy odpowiedź z serwera OpenID jest ważna (nie jest anulowana).
     *
     * @return bool True, jeśli odpowiedź jest ważna, false w przeciwnym razie.
     */
    public function isValid(): bool
    {
        return $this->oidObj->mode && $this->oidObj->mode != 'cancel';
    }

    /**
     * Pobiera identyfikator konta użytkownika z atrybutów OpenID (e-mail, nazwa).
     *
     * @return string|false Identyfikator konta (e-mail lub nazwa) lub false w przypadku błędu.
     */
    public function getAccount()
    {
        $user = $this->oidObj->getAttributes(); // Pobierz atrybuty użytkownika z serwera OpenID.

        if (!empty($user['contact/email'])) {
            return $user['contact/email']; // Preferuj e-mail jako identyfikator.
        }

        if (!empty($user['namePerson/friendly'])) {
            return $user['namePerson/friendly']; // Następnie użyj przyjaznej nazwy.
        }

        if (!empty($user['namePerson'])) {
            return $user['namePerson']; // Ostatecznie użyj pełnej nazwy.
        }

        HTTP::redirectTo('index.php?code=4'); // Przekieruj na stronę błędu, jeśli nie można uzyskać identyfikatora.

        return false;
    }

    /**
     * Rejestruje powiązanie konta OpenID z lokalnym kontem użytkownika (jeśli istnieje).
     * Jeśli nie istnieje, przekierowuje do standardowej rejestracji.
     *
     * @return void
     */
    public function register(): void
    {
        $uid = $this->getAccount(); // Pobierz identyfikator konta OpenID.
        $user = $this->oidObj->getAttributes(); // Pobierz atrybuty OpenID.

        if (empty($user['contact/email'])) {
            HTTP::redirectTo('index.php?code=4'); // Przekieruj na stronę błędu, jeśli brak e-maila.
        }

        // Sprawdź, czy istnieje już niezweryfikowane konto z tym e-mailem.
        $sql = 'SELECT validationID, validationKey FROM %%USERS_VALID%%
        WHERE universe = :universe AND email = :email;';

        $registerData = Database::get()->selectSingle($sql, [
            ':universe' => Universe::current(),
            ':email'    => $user['contact/email'],
        ]);

        if (!empty($registerData)) {
            // Jeśli istnieje, przekieruj do linku weryfikacyjnego.
            $url = sprintf('index.php?uni=%s&page=reg&action=valid&i=%s&validationKey=%s',
                Universe::current(), $registerData['validationID'], $registerData['validationKey']);

            HTTP::redirectTo($url);
        }

        // Wstaw powiązanie konta OpenID z istniejącym lokalnym kontem (jeśli istnieje po e-mailu).
        $sql = 'INSERT INTO %%USERS_AUTH%% SET
        id = (SELECT id FROM %%USERS%% WHERE email = :email OR email_2 = :email),
        account = :accountId,
        mode = :mode;';

        Database::get()->insert($sql, [
            ':email'     => $user['contact/email'],
            ':accountId' => $uid,
            ':mode'      => $this->oidObj->identity,
        ]);
    }

    /**
     * Pobiera dane logowania użytkownika na podstawie konta OpenID.
     *
     * @return array|null Tablica z danymi użytkownika lub null, jeśli nie znaleziono.
     */
    public function getLoginData(): ?array
    {
        $user = $this->oidObj->getAttributes(); // Pobierz atrybuty OpenID.

        $sql = 'SELECT
        user.id, user.username, user.dpath, user.authlevel, user.id_planet
        FROM %%USERS_AUTH%% auth
        INNER JOIN %%USERS%% user ON auth.id = user.id AND user.universe = :universe
        WHERE auth.account = :email AND mode = :mode;';

        return Database::get()->select($sql, [
            ':universe' => Universe::current(),
            ':email'    => $user['contact/email'],
            ':mode'     => $this->oidObj->identity,
        ]);
    }

    /**
     * Pobiera dane konta użytkownika z atrybutów OpenID.
     *
     * @return array Tablica z identyfikatorem, nazwą i lokalizacją użytkownika.
     */
    public function getAccountData(): array
    {
        $user = $this->oidObj->getAttributes(); // Pobierz atrybuty OpenID.

        return [
            'id'     => $user['contact/email'], // Użyj e-maila jako unikalnego ID.
            'name'   => $this->getAccount(),    // Pobierz przyjazną nazwę.
            'locale' => $user['pref/language'], // Pobierz preferowany język.
        ];
    }
}

// Sugestie ulepszeń:

// 1. Obsługa błędów: Dodanie bardziej szczegółowej obsługi błędów, np. w przypadku problemów z komunikacją z serwerem OpenID.
// 2. Konfiguracja: Przeniesienie listy wymaganych i opcjonalnych atrybutów OpenID do konfiguracji.
// 3. Bezpieczeństwo: Zabezpieczenie przed atakami typu phishing (sprawdzanie tożsamości serwera OpenID).
// 4. Mapowanie atrybutów: Możliwość mapowania atrybutów OpenID na lokalne pola użytkownika (np. imię, nazwisko).
// 5. Obsługa różnych dostawców OpenID: Ułatwienie integracji z różnymi dostawcami OpenID.
// 6. Logowanie: Dodanie logowania procesów autoryzacji i rejestracji OpenID.
// 7. Kompatybilność z PHP 8.4: Upewnienie się, że używana biblioteka OpenID (`LightOpenID`) jest w pełni kompatybilna z PHP 8.4. W nowszych projektach rozważenie użycia nowszych standardów uwierzytelniania, takich jak OAuth 2.0.