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
4. Uruchom `php includes/ConvertSmartyToTwig.php` aby przekonwertować pozostałe szablony
5. Następuj instrukcjami instalacji w przeglądarce

## Automatyczna konwersja szablonów
Do projektu dodano skrypt `includes/ConvertSmartyToTwig.php`, który automatycznie konwertuje szablony Smarty (.tpl) na Twig (.twig). 
Aby go użyć, wykonaj w konsoli:

```bash
php includes/ConvertSmartyToTwig.php
# 2Moons Game Engine

## Informacje o aktualizacji do PHP 8.4 i Twig

Ten projekt został zaktualizowany z użyciem technologii:
- PHP 8.4
- Twig 3.7 jako system szablonów (migracja ze Smarty)

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

### Przykłady
1. Instrukcje warunkowe:
   ```twig
   {% if user.isAdmin %}
       <span class="badge bg-danger">Admin</span>
   {% elseif user.isModerator %}
       <span class="badge bg-warning">Moderator</span>
   {% else %}
       <span class="badge bg-primary">Użytkownik</span>
   {% endif %}
   ```

2. Pętle:
   ```twig
   {% for planet in planets %}
       <div class="planet-item">
           <h3>{{ planet.name }}</h3>
           <p>Pozycja: [{{ planet.galaxy }}:{{ planet.system }}:{{ planet.planet }}]</p>
       </div>
   {% else %}
       <p>Brak planet.</p>
   {% endfor %}
   ```

3. Filtry:
   ```twig
   {{ user.points|number_format }} punktów
   {{ message|raw }} <!-- dla niesformatowanego HTML -->
   {{ date|date("d.m.Y H:i") }}
   ```

## Konfiguracja Twig

Ustawienia Twig znajdują się w pliku `includes/constants.php`: