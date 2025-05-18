<?php

require 'includes/libs/facebook/facebook.php'; // Załaduj bibliotekę Facebook PHP SDK.

/**
 * Klasa FacebookAuth implementująca interfejs externalAuth.
 * Obsługuje logowanie i rejestrację za pomocą API Facebooka.
 */
class FacebookAuth implements externalAuth
{
    /**
     * @var Facebook|null Obiekt biblioteki Facebook PHP SDK.
     */
    private $fbObj = null;

    /**
     * Konstruktor klasy. Inicjalizuje obiekt Facebooka, jeśli tryb Facebooka jest aktywny.
     */
    public function __construct()
    {
        if ($this->isActiveMode()) {
            $this->fbObj = new Facebook([
                'appId'  => Config::get()->fb_apikey,  // Pobierz klucz API aplikacji Facebook z konfiguracji.
                'secret' => Config::get()->fb_skey,  // Pobierz sekret aplikacji Facebook z konfiguracji.
                'cookie' => true, // Włącz obsługę ciasteczek Facebooka.
            ]);
        }
    }

    /**
     * Sprawdza, czy logowanie przez Facebooka jest aktywne w konfiguracji.
     *
     * @return bool True, jeśli logowanie przez Facebooka jest włączone, false w przeciwnym razie.
     */
    public function isActiveMode(): bool
    {
        return Config::get()->fb_on == 1;
    }

    /**
     * Sprawdza, czy użytkownik jest zalogowany przez Facebooka lub przekierowuje do logowania.
     *
     * @return string|void Zwraca ID użytkownika Facebooka, jeśli jest zalogowany, w przeciwnym razie przekierowuje do logowania na Facebooku i kończy skrypt.
     */
    public function isValid()
    {
        $userId = $this->getAccount(); // Pobierz ID użytkownika Facebooka.
        if ($userId != 0) {
            return $userId; // Użytkownik jest zalogowany przez Facebooka.
        }

        // Jeśli wystąpił błąd podczas logowania na Facebooku (np. użytkownik anulował).
        if (!empty($_GET['error_reason'])) {
            HTTP::redirectTo('index.php'); // Przekieruj na stronę główną.
        }

        // Przekieruj użytkownika na stronę logowania Facebooka.
        HTTP::sendHeader('Location', $this->fbObj->getLoginUrl([
            'scope'        => 'public_profile,email', // Wymagane uprawnienia (profil publiczny i e-mail).
            'redirect_uri' => HTTP_PATH . 'index.php?page=externalAuth&method=facebook', // Adres URL przekierowania po zalogowaniu.
        ]));
        exit; // Zakończ wykonywanie skryptu po przekierowaniu.
    }

    /**
     * Pobiera ID zalogowanego użytkownika Facebooka.
     *
     * @return int ID użytkownika Facebooka lub 0, jeśli nie jest zalogowany.
     */
    public function getAccount(): int
    {
        return (int)$this->fbObj->getUser();
    }

    /**
     * Rejestruje powiązanie konta Facebooka z lokalnym kontem użytkownika (jeśli istnieje).
     * Jeśli nie istnieje, przekierowuje do standardowej rejestracji.
     *
     * @return void
     */
    public function register(): void
    {
        $uid = $this->getAccount(); // Pobierz ID użytkownika Facebooka.
        $me = $this->fbObj->api('/me'); // Pobierz dane profilu użytkownika z Facebooka.

        // Sprawdź, czy istnieje już niezweryfikowane konto z tym e-mailem.
        $sql = 'SELECT validationID, validationKey FROM %%USERS_VALID%%
        WHERE universe = :universe AND email = :email;';

        $registerData = Database::get()->selectSingle($sql, [
            ':universe' => Universe::current(),
            ':email'    => $me['email'],
        ]);

        if (!empty($registerData)) {
            // Jeśli istnieje, przekieruj do linku weryfikacyjnego.
            $url = sprintf('index.php?uni=%s&page=reg&action=valid&i=%s&validationKey=%s',
                Universe::current(), $registerData['validationID'], $registerData['validationKey']);

            HTTP::redirectTo($url);
        }

        // Wstaw powiązanie konta Facebooka z istniejącym lokalnym kontem (jeśli istnieje po e-mailu).
        $sql = 'INSERT INTO %%USERS_AUTH%% SET
        id = (SELECT id FROM %%USERS%% WHERE email = :email OR email_2 = :email),
        account = :accountId,
        mode = :mode;';

        Database::get()->insert($sql, [
            ':email'     => $me['email'],
            ':accountId' => $uid,
            ':mode'      => 'facebook',
        ]);
    }

    /**
     * Pobiera dane logowania użytkownika na podstawie ID Facebooka.
     *
     * @return array|null Tablica z danymi użytkownika (id, id_planet) lub null, jeśli nie znaleziono.
     */
    public function getLoginData(): ?array
    {
        $uid = $this->getAccount(); // Pobierz ID użytkownika Facebooka.

        $sql = 'SELECT user.id, id_planet
        FROM %%USERS_AUTH%% auth
        INNER JOIN %%USERS%% user ON auth.id = user.id AND user.universe = :universe
        WHERE auth.account = :accountId AND mode = :mode;';

        return Database::get()->selectSingle($sql, [
            ':mode'      => 'facebook',
            ':accountId' => $uid,
            ':universe'  => Universe::current(),
        ]);
    }

    /**
     * Pobiera dane konta użytkownika z API Facebooka.
     *
     * @return array Tablica z ID, nazwą i lokalizacją użytkownika z Facebooka.
     */
    public function getAccountData(): array
    {
        $data = $this->fbObj->api('/me', ['access_token' => $this->fbObj->getAccessToken()]); // Pobierz dane profilu z Facebooka z tokenem dostępu.

        return [
            'id'     => $data['id'],     // ID użytkownika Facebooka.
            'name'   => $data['name'],   // Nazwa użytkownika Facebooka.
            'locale' => $data['locale'], // Lokalizacja użytkownika Facebooka.
        ];
    }
}

// Sugestie ulepszeń:

// 1. Obsługa błędów: Dodanie bardziej szczegółowej obsługi błędów API Facebooka (np. brak połączenia, nieprawidłowe uprawnienia).
// 2. Konfiguracja: Przeniesienie klucza API i sekretu aplikacji Facebook do konfiguracji.
// 3. Bezpieczeństwo: Zabezpieczenie przed atakami typu cross-site request forgery (CSRF) podczas procesu logowania Facebookiem. Biblioteka Facebook PHP SDK zazwyczaj to obsługuje, ale warto to zweryfikować.
// 4. Mapowanie danych: Możliwość mapowania dodatkowych danych z profilu Facebooka na lokalne pola użytkownika (np. data urodzenia, płeć).
// 5. Odłączanie konta: Implementacja możliwości odłączenia konta Facebooka od lokalnego konta użytkownika.
// 6. Logowanie: Dodanie logowania procesów logowania i rejestracji przez Facebooka.
// 7. Aktualizacja Facebook SDK: Upewnienie się, że używana wersja Facebook PHP SDK jest aktualna i bezpieczna.
// 8. Kompatybilność z PHP 8.4: Upewnienie się, że używana biblioteka Facebook PHP SDK jest w pełni kompatybilna z PHP 8.4.