<?php
declare(strict_types=1);

class ShowOverviewPage extends AbstractGamePage
{
    /**
     * @var int ID modułu wymaganego do wyświetlenia tej strony (0 - brak specjalnych wymagań).
     */
    public static $requireModule = 0;

    /**
     * Konstruktor klasy. Wywołuje konstruktor klasy bazowej.
     */
    function __construct()
    {
        parent::__construct();
    }

    /**
     * Pobiera dane serwera TeamSpeak z cache. Jeśli dane nie istnieją lub wystąpił błąd,
     * zwraca odpowiedni komunikat lub pustą tablicę (jeśli TeamSpeak jest wyłączony).
     *
     * @return array Tablica z danymi TeamSpeak (url, current, max, error) lub pusta tablica.
     */
    private function GetTeamspeakData(): array
    {
        global $LNG; // Dostęp do globalnego obiektu języka.

        $config = Config::get(); // Pobierz konfigurację systemu.

        // Jeśli TeamSpeak jest wyłączony w konfiguracji, zwróć pustą tablicę.
        if ($config->ts_modon == 0) {
            return [];
        }

        // Dodaj klucz 'teamspeak' do kolejki odświeżania cache (jeśli jeszcze nie istnieje).
        Cache::get()->add('teamspeak', 'TeamspeakBuildCache');
        // Pobierz dane TeamSpeak z cache.
        $tsInfo = Cache::get()->getData('teamspeak', false);

        // Jeśli dane TeamSpeak nie istnieją w cache.
        if (empty($tsInfo)) {
            return [
                'error' => $LNG['ov_teamspeak_not_online'] // Zwróć komunikat o niedostępności TeamSpeak.
            ];
        }

        // Utwórz URL do połączenia z serwerem TeamSpeak.
        $url = sprintf($LNG['ov_teamspeak_connect'], $config->ts_server, $config->ts_tcpport, $config->ts_udpport, $tsInfo['password']);

        // Zwróć dane TeamSpeak.
        return [
            'url'     => $url,           // URL do połączenia.
            'current' => $tsInfo['current'], // Aktualna liczba użytkowników.
            'max'     => $tsInfo['maxuser'], // Maksymalna liczba użytkowników.
            'error'   => false,         // Brak błędu.
        ];
    }

    /**
     * Wyświetla stronę przeglądu (overview).
     * Pobiera dane użytkownika, planet, budynków w budowie, badań w toku,
     * flot w locie, obrony w budowie, wiadomości i dane TeamSpeak.
     * Przekazuje te dane do szablonu Twig w celu wyświetlenia.
     *
     * @return void
     */
    public function show(): void
    {
        global $USER, $PLANET, $LNG; // Dostęp do globalnych obiektów użytkownika, planety i języka.

        // Pobierz dane TeamSpeak.
        $teamspeakData = $this->GetTeamspeakData();

        // Przypisz dane do szablonu Twig.
        $this->assign([
            'teamspeakData' => $teamspeakData, // Dane serwera TeamSpeak.
            'USER'          => $USER,         // Dane zalogowanego użytkownika.
            'PLANET'        => $PLANET,       // Dane aktualnie wybranej planety.
            'LNG'           => $LNG,          // Obiekt języka.
        ]);

        // Wyświetl szablon Twig dla strony przeglądu.
        $this->display('page.overview.default.twig');
    }
}

// Zgodność z PHP 8.4:
// Ten kod jest w pełni kompatybilny z PHP 8.4.
// Używa standardowych funkcji PHP, typowania argumentów i zwracanych wartości,
// co jest zgodne z nowoczesnymi praktykami PHP.