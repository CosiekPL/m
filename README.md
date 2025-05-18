# 2Moons - Zaktualizowana wersja

## Informacje o aktualizacji
Projekt został zaktualizowany do:
- PHP 8.4
- Twig (zamiast Smarty) jako system szablonów

## Ważne zmiany:
1. Wszystkie pliki szablonów zostały przekonwertowane z formatu .tpl (Smarty) na .twig (Twig)
2. Klasa template została całkowicie przepisana do obsługi Twiga
3. Wprowadzono strict_types=1 dla kompatybilności z PHP 8.4
4. Zaktualizowano składnię PHP do wersji 8.4 (tablice, typy argumentów i zwracanych wartości)

## Wymagania systemowe
- PHP 8.4 lub wyższy
- Composer do zarządzania zależnościami
- Serwer WWW (Apache, Nginx)
- Baza danych MySQL/MariaDB

## Instalacja
1. Sklonuj repozytorium
2. Uruchom `composer install` aby zainstalować zależności
3. Skonfiguruj swój serwer WWW
4. Następuj instrukcjami instalacji w przeglądarce


# 2Moons Game Engine

## Informacje o aktualizacji do PHP 8.4 i Twig

Ten projekt został zaktualizowany z użyciem technologii:
- PHP 8.4
- Twig 3.21.2 jako system szablonów (migracja ze Smarty)

## Instalacja

### Wymagania
- PHP 8.4 lub nowszy
- MySQL 5.7 lub nowszy (zalecany MariaDB 10.5+)
- Rozszerzenia PHP: PDO, mbstring, json, bcmath

### Kroki instalacji
1. Pobierz kod źródłowy
2. Zainstaluj zależności za pomocą Composer:
   ```
   composer install
   ```
3. Skonfiguruj serwer WWW (Apache/Nginx) do wskazywania na katalog główny projektu
4. Uruchom instalator pod adresem: http://twoja-domena/install/

## Migracja szablonów ze Smarty na Twig

### Główne zmiany w składni szablonów:
- Zmiana rozszerzeń plików z `.tpl` na `.twig`
- Zmiana zmiennych: `{$variable}` → `{{ variable }}`
- Zmiana instrukcji warunkowych: `{if}...{/if}` → `{% if %}...{% endif %}`
- Zmiana pętli: `{foreach}...{/foreach}` → `{% for item in items %}...{% endfor %}`
- Dołączanie szablonów: `{include}` → `{% include %}`
- Dziedziczenie szablonów: `{% extends "base.twig" %}` i `{% block content %}...{% endblock %}`
