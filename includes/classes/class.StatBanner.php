<?php

class StatBanner
{
    /**
     * @var string Ścieżka do domyślnego obrazu tła banera.
     */
    private $source = "styles/resource/images/banner.jpg";

    /**
     * Pobiera dane użytkownika i serwera potrzebne do wygenerowania banera statystyk.
     *
     * @param int $id ID użytkownika, dla którego ma zostać wygenerowany baner.
     *
     * @return array|null Tablica asocjacyjna z danymi użytkownika, statystykami, planetą główną i konfiguracją serwera, lub null w przypadku braku danych.
     */
    public function GetData($id): ?array
    {
        $sql = 'SELECT user.username, user.wons, user.loos, user.draws,
		stat.total_points, stat.total_rank,
		planet.name, planet.galaxy, planet.system, planet.planet, config.game_name,
		config.users_amount, config.ttf_file
		FROM %%USERS%% as user, %%STATPOINTS%% as stat, %%PLANETS%% as planet, %%CONFIG%% as config
		WHERE user.id = :userId AND stat.stat_type = :statType AND stat.id_owner = :userId
		AND planet.id = user.id_planet AND config.uni = user.universe;';

        return Database::get()->selectSingle($sql, [
            ':userId'   => $id,
            ':statType' => 1 // Typ statystyk (prawdopodobnie ogólne punkty).
        ]);
    }

    /**
     * Tworzy baner statystyk w formacie JPEG z tekstem UTF-8.
     *
     * @param array $data Tablica z danymi użytkownika i serwera pobranymi przez GetData().
     *
     * @return void Wysyła nagłówki i dane obrazu JPEG do przeglądarki.
     */
    public function CreateUTF8Banner($data): void
    {
        global $LNG; // Dostęp do globalnego obiektu języka.
        $image = imagecreatefromjpeg($this->source); // Utwórz obraz z pliku JPEG.

        $Font = $data['ttf_file']; // Ścieżka do pliku czcionki TrueType.
        if (!file_exists($Font))
            $this->BannerError('TTF Font missing!'); // Wyświetl komunikat o błędzie, jeśli czcionka nie istnieje.

        // Kolory tekstu i cienia.
        $color = imagecolorallocate($image, 255, 255, 225); // Jasny kolor tekstu (prawie biały).
        $shadow = imagecolorallocate($image, 33, 33, 33);   // Ciemny kolor cienia (prawie czarny).

        $total = $data['wons'] + $data['loos'] + $data['draws']; // Suma wszystkich walk.
        $quote = $total != 0 ? $data['wons'] / $total * 100 : 0; // Procent wygranych walk.

        // Wyświetlaj informacje na banerze z cieniem.
        imagettftext($image, 20, 0, 20, 31, $shadow, $Font, $data['username']); // Nick gracza (cień).
        imagettftext($image, 20, 0, 20, 30, $color, $Font, $data['username']);  // Nick gracza.

        imagettftext($image, 16, 0, 250, 31, $shadow, $Font, $data['game_name']); // Nazwa gry (cień).
        imagettftext($image, 16, 0, 250, 30, $color, $Font, $data['game_name']);  // Nazwa gry.

        imagettftext($image, 11, 0, 20, 60, $shadow, $Font, $LNG['ub_rank'] . ': ' . $data['total_rank']); // Ranga (cień).
        imagettftext($image, 11, 0, 20, 59, $color, $Font, $LNG['ub_rank'] . ': ' . $data['total_rank']);  // Ranga.

        imagettftext($image, 11, 0, 20, 81, $shadow, $Font, $LNG['ub_points'] . ': ' . html_entity_decode(shortly_number($data['total_points']))); // Punkty (cień).
        imagettftext($image, 11, 0, 20, 80, $color, $Font, $LNG['ub_points'] . ': ' . html_entity_decode(shortly_number($data['total_points'])));  // Punkty.

        imagettftext($image, 11, 0, 250, 60, $shadow, $Font, $LNG['ub_fights'] . ': ' . html_entity_decode(shortly_number($total, 0))); // Walki (cień).
        imagettftext($image, 11, 0, 250, 59, $color, $Font, $LNG['ub_fights'] . ': ' . html_entity_decode(shortly_number($total, 0)));  // Walki.

        imagettftext($image, 11, 0, 250, 81, $shadow, $Font, $LNG['ub_quote'] . ': ' . html_entity_decode(shortly_number($quote, 2)) . '%'); // Procent wygranych (cień).
        imagettftext($image, 11, 0, 250, 80, $color, $Font, $LNG['ub_quote'] . ': ' . html_entity_decode(shortly_number($quote, 2)) . '%');  // Procent wygranych.

        // Wyślij nagłówek Content-type tylko jeśli nie jest to tryb debugowania.
        if (!isset($_GET['debug'])) {
            HTTP::sendHeader('Content-type', 'image/jpg');
        }

        imagejpeg($image); // Wyślij obraz JPEG do przeglądarki.
        imagedestroy($image); // Zwolnij pamięć zajmowaną przez obraz.
    }

    /**
     * Wyświetla obraz błędu z podanym komunikatem.
     *
     * @param string $Message Komunikat błędu do wyświetlenia na banerze.
     *
     * @return void Wysyła nagłówki i dane obrazu JPEG z komunikatem błędu.
     */
    function BannerError($Message): void
    {
        HTTP::sendHeader('Content-type', 'image/jpg'); // Ustaw typ zawartości na obraz JPEG.
        $im = imagecreate(450, 80); // Utwórz nowy obraz o wymiarach 450x80 pikseli.
        $text_color = imagecolorallocate($im, 233, 14, 91); // Ustal kolor tekstu błędu (czerwony).
        imagestring($im, 3, 5, 5, $Message, $text_color); // Narysuj tekst błędu na obrazie.
        imagejpeg($im); // Wyślij obraz JPEG do przeglądarki.
        imagedestroy($im); // Zwolnij pamięć zajmowaną przez obraz.
        exit; // Zakończ wykonywanie skryptu.
    }
}

// Sugestie ulepszeń:

// 1. Konfiguracja: Przeniesienie ścieżki do obrazu tła banera i czcionki do konfiguracji.
// 2. Personalizacja: Umożliwienie personalizacji banera (np. wybór kolorów, czcionek).
// 3. Optymalizacja: Cachowanie wygenerowanych banerów, aby zmniejszyć obciążenie serwera.
// 4. Obsługa błędów: Lepsze logowanie błędów związanych z tworzeniem obrazu.
// 5. Bezpieczeństwo: Walidacja danych wejściowych, aby zapobiec potencjalnym problemom z generowaniem obrazu.
// 6. Dynamiczne rozmiary: Umożliwienie generowania banerów o różnych rozmiarach.
// 7. Format obrazu: Możliwość wyboru formatu obrazu (np. PNG).