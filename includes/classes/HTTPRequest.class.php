<?php

class HTTPRequest
{
    /**
     * @var string|null Adres URL żądania.
     */
    private $url = null;

    /**
     * @var string|null Zawartość odpowiedzi HTTP.
     */
    private $content = null;

    /**
     * @var resource|null Uchwyt cURL.
     */
    private $ch = null;

    /**
     * Konstruktor klasy. Inicjalizuje adres URL żądania.
     *
     * @param string|null $url Adres URL do wysłania żądania HTTP.
     */
    public function __construct($url = null)
    {
        $this->url = $url;
    }

    /**
     * Wysyła żądanie HTTP GET do zdefiniowanego URL-a przy użyciu cURL (jeśli jest dostępne).
     * Ustawia opcje takie jak User-Agent i nagłówki Accept.
     * Zapisuje odpowiedź w `$this->content`.
     *
     * @return void
     */
    public function send(): void
    {
        // Sprawdź, czy funkcja cURL jest dostępna na serwerze.
        if (function_exists("curl_init")) {
            $this->ch = curl_init($this->url); // Inicjalizuj sesję cURL z podanym URL-em.
            curl_setopt($this->ch, CURLOPT_HTTPGET, true);        // Ustaw metodę żądania na GET.
            curl_setopt($this->ch, CURLOPT_AUTOREFERER, true);    // Automatycznie ustawiaj nagłówek Referer.
            curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true); // Zwróć wynik transferu jako ciąg znaków.
            curl_setopt($this->ch, CURLOPT_USERAGENT, "Mozilla/5.0 (compatible; 2Moons/" . Config::get()->VERSION . "; +http://2moons.cc)"); // Ustaw nagłówek User-Agent.
            curl_setopt($this->ch, CURLOPT_HTTPHEADER, [
                "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
                "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.3",
                "Accept-Language: de-DE,de;q=0.8,en-US;q=0.6,en;q=0.4",
            ]); // Ustaw nagłówki Accept, Accept-Charset i Accept-Language.

            $this->content = curl_exec($this->ch); // Wykonaj żądanie cURL i zapisz odpowiedź.
            curl_close($this->ch); // Zamknij sesję cURL.
        }
        // Można dodać alternatywną implementację (np. file_get_contents z kontekstem)
        // dla serwerów bez cURL.
    }

    /**
     * Wysyła żądanie HTTP i zwraca treść odpowiedzi.
     *
     * @return string|null Treść odpowiedzi HTTP lub null, jeśli wystąpił błąd lub cURL nie jest dostępne.
     */
    public function getResponse()
    {
        $this->send(); // Wyślij żądanie.
        return $this->content; // Zwróć treść odpowiedzi.
    }
}

// Sugestie ulepszeń:

// 1. Obsługa błędów cURL: Dodanie sprawdzania błędów cURL (`curl_errno()`, `curl_error()`) i logowanie ich.
// 2. Konfiguracja opcji cURL: Umożliwienie konfigurowania różnych opcji cURL (np. timeout, SSL verification, proxy).
// 3. Obsługa innych metod HTTP: Dodanie obsługi innych metod HTTP (POST, PUT, DELETE) oraz możliwości wysyłania danych w ciele żądania.
// 4. Nagłówki: Umożliwienie ustawiania niestandardowych nagłówków HTTP.
// 5. Alternatywne implementacje: Dodanie alternatywnych metod wysyłania żądań HTTP (np. `file_get_contents` z kontekstem) w przypadku braku cURL.
// 6. Bezpieczeństwo: Rozważenie opcji cURL związanych z bezpieczeństwem (np. weryfikacja SSL).
// 7. Asynchroniczność: W bardziej zaawansowanych scenariuszach rozważenie użycia bibliotek asynchronicznych do obsługi żądań HTTP.
// 8. Typowanie: Dodanie deklaracji typów argumentów i zwracanych wartości dla metod.