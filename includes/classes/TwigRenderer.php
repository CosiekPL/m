<?php
require_once __DIR__ . '/includes/libs/Twig/Loader/FilesystemLoader.php'; // Załaduj klasę FilesystemLoader z biblioteki Twig.
require_once __DIR__ . '/includes/libs/Twig/Environment.php';        // Załaduj klasę Environment z biblioteki Twig.

/**
 * Klasa TwigRenderer.
 * Uproszcza renderowanie szablonów Twig.
 */
class TwigRenderer
{
    /**
     * Renderuje podany szablon Twig z opcjonalnym kontekstem danych.
     *
     * @param string $template Nazwa pliku szablonu Twig (bez ścieżki, np. 'index.twig').
     * @param array  $context  Tablica asocjacyjna danych do przekazania do szablonu (domyślnie pusta).
     *
     * @return string Wyrenderowana zawartość szablonu.
     */
    public static function render($template, $context = []): string
    {
        // Utwórz loader systemu plików Twig, wskazujący na katalog z szablonami.
        // Ścieżka jest relatywna do bieżącego pliku klasy.
        $loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/styles/templates');

        // Utwórz środowisko Twig z loaderem i opcjonalnymi ustawieniami.
        $twig = new \Twig\Environment($loader, [
            'cache' => false, // Wyłącz cache szablonów na czas developmentu. W produkcji warto włączyć.
            'debug' => true,  // Włącz tryb debugowania Twig, przydatny podczas tworzenia szablonów.
        ]);

        // Renderuj szablon z podanym kontekstem danych i zwróć wyrenderowaną zawartość.
        return $twig->render($template, $context);
    }
}

// Sugestie ulepszeń:

// 1. Konfiguracja ścieżki szablonów: Przeniesienie ścieżki do katalogu 'templates' do konfiguracji (np. w pliku config.php), aby ułatwić zmianę.
// 2. Konfiguracja cache: Włączenie cache w środowisku produkcyjnym poprzez ustawienie opcji 'cache' na ścieżkę do katalogu cache.
// 3. Obsługa wyjątków: Dodanie try-catch wokół renderowania szablonu w celu obsługi potencjalnych błędów (np. brak pliku szablonu).
// 4. Rozszerzenia Twig: Możliwość dodawania niestandardowych rozszerzeń Twig (np. filtry, funkcje, testy).
// 5. Globalne zmienne Twig: Możliwość definiowania globalnych zmiennych Twig dostępnych we wszystkich szablonach.
// 6. Debugowanie: Usunięcie 'debug' => true w środowisku produkcyjnym lub udostępnienie opcji debugowania.
// 7. Kompatybilność z PHP 8.4: Upewnienie się, że używana wersja biblioteki Twig jest w pełni kompatybilna z PHP 8.4.
