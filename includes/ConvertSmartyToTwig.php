<?php

/**
 * Skrypt do konwersji szablonów Smarty na Twig
 * 
 * Uruchomienie: php includes/ConvertSmartyToTwig.php
 */

declare(strict_types=1);

/**
 * Funkcja rekurencyjnie przeszukująca katalogi i konwertująca pliki
 */
function convertTemplates(string $sourceDir, string $targetDir): void 
{
    $files = scandir($sourceDir);
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        
        $sourcePath = $sourceDir . '/' . $file;
        $targetPath = $targetDir . '/' . $file;
        
        if (is_dir($sourcePath)) {
            if (!file_exists($targetPath)) {
                mkdir($targetPath, 0755, true);
            }
            convertTemplates($sourcePath, $targetPath);
        } elseif (pathinfo($file, PATHINFO_EXTENSION) === 'tpl') {
            $targetPath = str_replace('.tpl', '.twig', $targetPath);
            convertSmartyToTwig($sourcePath, $targetPath);
            echo "Przekonwertowano: $sourcePath -> $targetPath\n";
        }
    }
}

/**
 * Konwertuje składnię Smarty na składnię Twig
 */
function convertSmartyToTwig(string $sourcePath, string $targetPath): void 
{
    $content = file_get_contents($sourcePath);
    
    // Zmiana zmiennych
    $content = preg_replace('/{\$(.*?)}/', '{{ $1 }}', $content);
    
    // Zmiana bloków if/elseif/else/endif
    $content = preg_replace('/{if\s+(.*?)}/', '{% if $1 %}', $content);
    $content = preg_replace('/{\/if}/', '{% endif %}', $content);
    $content = preg_replace('/{else}/', '{% else %}', $content);
    $content = preg_replace('/{elseif\s+(.*?)}/', '{% elseif $1 %}', $content);
    
    // Zmiana bloków foreach
    $content = preg_replace('/{foreach\s+from=\$(.*?)\s+item=\$(.*?)}/', '{% for $2 in $1 %}', $content);
    $content = preg_replace('/{foreach\s+from=\$(.*?)\s+item=\$(.*?)\s+key=\$(.*?)}/', '{% for $3, $2 in $1 %}', $content);
    $content = preg_replace('/{\/foreach}/', '{% endfor %}', $content);
    
    // Zmiana include
    $content = preg_replace('/{include\s+file="(.*?)"}/', '{% include "$1" %}', $content);
    $content = preg_replace('/{include\s+file="(.*?).tpl"}/', '{% include "$1.twig" %}', $content);
    
    // Zmiana section
    $content = preg_replace('/{section\s+name=(.*?)\s+loop=(.*?)}/', '{% for $1 in $2 %}', $content);
    $content = preg_replace('/{\/section}/', '{% endfor %}', $content);
    
    // Zmiana assign
    $content = preg_replace('/{assign\s+var=(.*?)\s+value=(.*?)}/', '{% set $1 = $2 %}', $content);
    
    // Zmiana komentarzy
    $content = preg_replace('/{\*(.*?)\*}/s', '{# $1 #}', $content);
    
    // Zmiana literali 
    $content = preg_replace('/{literal}(.*?){\/literal}/s', '{% verbatim %}$1{% endverbatim %}', $content);
    
    // Zmiana filtrów
    $content = preg_replace('/{\$(.*?)\|(.*?)}/', '{{ $1|$2 }}', $content);
    
    // Zmiana bloków extends
    $content = preg_replace('/{extends file="(.*?)"}/', '{% extends "$1" %}', $content);
    $content = preg_replace('/{extends file="(.*?).tpl"}/', '{% extends "$1.twig" %}', $content);
    
    // Zmiana bloków block
    $content = preg_replace('/{block\s+name=(.*?)}/', '{% block $1 %}', $content);
    $content = preg_replace('/{\/block}/', '{% endblock %}', $content);
    
    // Zmiana rozszerzeń w linkach do szablonów z .tpl na .twig
    $content = preg_replace('/(["\'])([^"\']*?)\.tpl(["\'])/', '$1$2.twig$3', $content);
    
    file_put_contents($targetPath, $content);
}

// Ścieżki do plików szablonów
$templateDir = dirname(__DIR__) . '/styles/templates/';
$convertedDir = dirname(__DIR__) . '/styles/templates_converted/';
<?php

declare(strict_types=1);

/**
 * Skrypt konwertujący szablony Smarty (.tpl) na szablony Twig (.twig)
 * Automatycznie przekształca podstawowe konstrukcje i składnię
 */

// Podstawowa konfiguracja
if (!file_exists('includes/common.php')) {
    die('Nie znaleziono common.php. Uruchom skrypt z głównego katalogu projektu.');
}

// Wczytanie podstawowych funkcji
require_once 'includes/common.php';

// Definicja kolorów do konsoli
define('COLOR_GREEN', "\033[32m");
define('COLOR_RED', "\033[31m");
define('COLOR_YELLOW', "\033[33m");
define('COLOR_BLUE', "\033[34m");
define('COLOR_RESET', "\033[0m");

// Katalogi z szablonami
$templateDirectories = [
    'templates/login',
    'templates/game',
    'templates/install',
    'templates/adm'
];

// Liczniki statystyk
$stats = [
    'converted' => 0,
    'skipped' => 0,
    'errors' => 0,
    'already_exists' => 0
];

// Powitanie
echo COLOR_BLUE . "====================================" . COLOR_RESET . PHP_EOL;
echo COLOR_BLUE . "2Moons - Konwerter szablonów Smarty na Twig" . COLOR_RESET . PHP_EOL;
echo COLOR_BLUE . "====================================" . COLOR_RESET . PHP_EOL . PHP_EOL;

// Funkcja do konwersji zawartości pliku
function convertSmartyToTwig(string $content): string {
    // Konwersja komentarzy
    $content = preg_replace('/{\\*(.+?)\\*}/s', '{#$1#}', $content);
    
    // Konwersja zmiennych
    $content = preg_replace('/{\\$([a-zA-Z0-9_\[\]\.\'"]+)}/m', '{{ $1 }}', $content);
    
    // Konwersja stałych
    $content = preg_replace('/{([A-Z_]+)}/m', '{{ constant(\'$1\') }}', $content);
    
    // Konwersja wyrażeń językowych
    $content = preg_replace('/{lang\\s+([a-zA-Z0-9_\.]+)}/m', '{{ LNG.$1 }}', $content);
    
    // Konwersja include
    $content = preg_replace('/{include\\s+file="(.+?)"\s*}/m', '{% include "$1" %}', $content);
    $content = preg_replace('/{include\\s+file="(.+?)"\s+([a-zA-Z0-9_]+)=(.+?)}/m', '{% include "$1" with {\'$2\': $3} %}', $content);
    
    // Konwersja iteracji (foreach)
    $content = preg_replace('/{foreach\\s+([a-zA-Z0-9_\$\[\]\.]+)\\s+([a-zA-Z0-9_]+)}/m', '{% for $2 in $1 %}', $content);
    $content = preg_replace('/{foreach\\s+([a-zA-Z0-9_\$\[\]\.]+)\\s+([a-zA-Z0-9_]+)\\s+([a-zA-Z0-9_]+)}/m', '{% for $3, $2 in $1 %}', $content);
    $content = str_replace('{/foreach}', '{% endfor %}', $content);
    
    // Konwersja instrukcji warunkowych
    $content = preg_replace('/{if\\s+(.+?)}/m', '{% if $1 %}', $content);
    $content = preg_replace('/{elseif\\s+(.+?)}/m', '{% elseif $1 %}', $content);
    $content = str_replace('{else}', '{% else %}', $content);
    $content = str_replace('{/if}', '{% endif %}', $content);
    
    // Konwersja sekcji
    $content = preg_replace('/{section\\s+name=([a-zA-Z0-9_]+)\\s+loop=([a-zA-Z0-9_\$\[\]\.]+)\\s+start=([a-zA-Z0-9_\$\[\]\.]+)}/m', 
        '{% for $1 in range($3, ($2)|length - 1) %}', $content);
    $content = str_replace('{/section}', '{% endfor %}', $content);
    
    // Konwersja operatorów
    $content = str_replace('===', '==', $content);
    $content = str_replace('!==', '!=', $content);
    
    // Operatory logiczne
    $content = preg_replace('/({%[^}]*?)\\s+AND\\s+([^}]*?%})/i', '$1 and $2', $content);
    $content = preg_replace('/({%[^}]*?)\\s+OR\\s+([^}]*?%})/i', '$1 or $2', $content);
    $content = preg_replace('/({%[^}]*?)\\s+!\\s+([^}]*?%})/i', '$1 not $2', $content);
    
    // Wywołania funkcji (zamiana składni)
    $content = preg_replace('/{([a-zA-Z0-9_]+)\\s+([^}]+)}/m', '{{ $1($2) }}', $content);
    
    // Podmiana operatorów modulo
    $content = preg_replace('/([a-zA-Z0-9_\$\[\]\.]+)\\s+%\\s+([0-9]+)/m', '$1 mod $2', $content);
    
    // Specjalne przypadki
    // Konwersja {$foo|escape:'html'} na {{ foo|e('html') }}
    $content = preg_replace('/{\\$([a-zA-Z0-9_\[\]\.]+)\\|escape:\'([a-zA-Z0-9_]+)\'}/m', '{{ $1|e(\'$2\') }}', $content);
    
    // Konwersja {$foo|truncate:50} na {{ foo|truncate(50) }}
    $content = preg_replace('/{\\$([a-zA-Z0-9_\[\]\.]+)\\|truncate:([0-9]+)}/m', '{{ $1|truncate($2) }}', $content);
    
    // Konwersja {$foo|default:'-'} na {{ foo|default('-') }}
    $content = preg_replace('/{\\$([a-zA-Z0-9_\[\]\.]+)\\|default:\'?([^\'}" ]+)\'?}/m', '{{ $1|default(\'$2\') }}', $content);
    
    return $content;
}

// Funkcja do rekurencyjnej konwersji plików w katalogu
function convertDirectory(string $directory): void {
    global $stats;
    
    if (!is_dir($directory)) {
        echo COLOR_RED . "Katalog {$directory} nie istnieje." . COLOR_RESET . PHP_EOL;
        return;
    }
    
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($files as $file) {
        // Sprawdź czy to plik TPL
        if ($file->getExtension() !== 'tpl') {
            continue;
        }
        
        $smartyFile = $file->getPathname();
        $twigFile = str_replace('.tpl', '.twig', $smartyFile);
        
        // Sprawdź czy plik Twig już istnieje i jest nowszy
        if (file_exists($twigFile) && filemtime($twigFile) > filemtime($smartyFile)) {
            echo COLOR_YELLOW . "Pominięto: " . $smartyFile . " (już istnieje nowszy plik Twig)" . COLOR_RESET . PHP_EOL;
            $stats['already_exists']++;
            continue;
        }
        
        try {
            // Wczytaj zawartość pliku Smarty
            $content = file_get_contents($smartyFile);
            
            // Konwersja składni
            $convertedContent = convertSmartyToTwig($content);
            
            // Zapisz plik Twig
            file_put_contents($twigFile, $convertedContent);
            
            echo COLOR_GREEN . "Przekonwertowano: " . $smartyFile . " -> " . $twigFile . COLOR_RESET . PHP_EOL;
            $stats['converted']++;
        } catch (Exception $e) {
            echo COLOR_RED . "Błąd konwersji: " . $smartyFile . " - " . $e->getMessage() . COLOR_RESET . PHP_EOL;
            $stats['errors']++;
        }
    }
}

// Główna pętla konwersji
echo "Rozpoczęcie konwersji szablonów..." . PHP_EOL;

foreach ($templateDirectories as $directory) {
    echo PHP_EOL . COLOR_BLUE . "Przetwarzanie katalogu: " . $directory . COLOR_RESET . PHP_EOL;
    convertDirectory($directory);
}

// Wyświetlenie statystyk
echo PHP_EOL . COLOR_BLUE . "====================================" . COLOR_RESET . PHP_EOL;
echo COLOR_BLUE . "Statystyka konwersji:" . COLOR_RESET . PHP_EOL;
echo COLOR_GREEN . "Przekonwertowano: " . $stats['converted'] . " plików" . COLOR_RESET . PHP_EOL;
echo COLOR_YELLOW . "Pominięto (istnieją nowsze): " . $stats['already_exists'] . " plików" . COLOR_RESET . PHP_EOL;
echo COLOR_RED . "Błędy: " . $stats['errors'] . " plików" . COLOR_RESET . PHP_EOL;
echo COLOR_BLUE . "====================================" . COLOR_RESET . PHP_EOL;

echo PHP_EOL . "Konwersja zakończona." . PHP_EOL;
echo "Uwaga: Po konwersji mogą być wymagane ręczne poprawki w bardziej złożonych szablonach." . PHP_EOL;

// Informacja o potencjalnych problemach
echo PHP_EOL . COLOR_YELLOW . "Potencjalne problemy do ręcznej poprawy:" . COLOR_RESET . PHP_EOL;
echo "1. Złożone wyrażenia w instrukcjach warunkowych" . PHP_EOL;
echo "2. Niestandardowe filtry i modyfikatory Smarty" . PHP_EOL;
echo "3. Skomplikowane wywołania funkcji z wieloma parametrami" . PHP_EOL;
echo "4. Bloki zdefiniowane przez użytkownika" . PHP_EOL;
// Tworzenie katalogu docelowego, jeśli nie istnieje
if (!file_exists($convertedDir)) {
    mkdir($convertedDir, 0755, true);
}

echo "Rozpoczynam konwersję szablonów Smarty na Twig...\n";
convertTemplates($templateDir, $convertedDir);
echo "Konwersja zakończona! Przekonwertowane szablony znajdują się w katalogu: $convertedDir\n";
echo "Pamiętaj, aby ręcznie zweryfikować przekonwertowane pliki i dostosować strukturę projektu.\n";
