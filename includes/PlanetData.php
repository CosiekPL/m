<?php
// Stała określająca mnożnik dla liczby pól na planetach.
define('PLANET_FIELDS_MULTIPLIER', 3);

// Tablica zawierająca dane charakterystyczne dla różnych typów planet.
// Klucze tablicy (1 do 15) reprezentują ID typu planety.
$planetData = array(
    1  => array(
        'temp'   => mt_rand(220, 260),    // Losowa temperatura planety (Kelwiny).
        'fields' => mt_rand(95, 108) * PLANET_FIELDS_MULTIPLIER, // Liczba pól pomnożona przez stałą
        'image'  => array(                 // Możliwe obrazy planety.
            'trocken' => mt_rand(1, 10),  // Losowy ID obrazu dla planety suchej.
            'wuesten' => mt_rand(1, 4),   // Losowy ID obrazu dla planety pustynnej.
        ),
    ),
    2  => array(
        'temp'   => mt_rand(170, 210),
        'fields' => mt_rand(97, 110) * PLANET_FIELDS_MULTIPLIER,
        'image'  => array(
            'trocken' => mt_rand(1, 10),
            'wuesten' => mt_rand(1, 4),
        ),
    ),
    3  => array(
        'temp'   => mt_rand(120, 160),
        'fields' => mt_rand(98, 137) * PLANET_FIELDS_MULTIPLIER,
        'image'  => array(
            'trocken' => mt_rand(1, 10),
            'wuesten' => mt_rand(1, 4),
        ),
    ),
    4  => array(
        'temp'   => mt_rand(70, 110),
        'fields' => mt_rand(123, 203) * PLANET_FIELDS_MULTIPLIER,
        'image'  => array(
            'dschjungel' => mt_rand(1, 10), // Losowy ID obrazu dla planety dżungli.
        ),
    ),
    5  => array(
        'temp'   => mt_rand(60, 100),
        'fields' => mt_rand(148, 210) * PLANET_FIELDS_MULTIPLIER,
        'image'  => array(
            'dschjungel' => mt_rand(1, 10),
        ),
    ),
    6  => array(
        'temp'   => mt_rand(50, 90),
        'fields' => mt_rand(148, 226) * PLANET_FIELDS_MULTIPLIER, 
        'image'  => array(
            'dschjungel' => mt_rand(1, 10),
        ),
    ),
    7  => array(
        'temp'   => mt_rand(40, 80),
        'fields' => mt_rand(141, 273) * PLANET_FIELDS_MULTIPLIER,
        'image'  => array(
            'normaltemp' => mt_rand(1, 7),   // Losowy ID obrazu dla planety o normalnej temperaturze.
        ),
    ),
    8  => array(
        'temp'   => mt_rand(30, 70),
        'fields' => mt_rand(169, 246) * PLANET_FIELDS_MULTIPLIER,
        'image'  => array(
            'normaltemp' => mt_rand(1, 7),
        ),
    ),
    9  => array(
        'temp'   => mt_rand(20, 60),
        'fields' => mt_rand(161, 238) * PLANET_FIELDS_MULTIPLIER,
        'image'  => array(
            'normaltemp' => mt_rand(1, 7),
            'wasser'     => mt_rand(1, 9),   // Losowy ID obrazu dla planety wodnej.
        ),
    ),
    10 => array(
        'temp'   => mt_rand(10, 50),
        'fields' => mt_rand(154, 224) * PLANET_FIELDS_MULTIPLIER,
        'image'  => array(
            'normaltemp' => mt_rand(1, 7),
            'wasser'     => mt_rand(1, 9),
        ),
    ),
    11 => array(
        'temp'   => mt_rand(0, 40),
        'fields' => mt_rand(148, 204) * PLANET_FIELDS_MULTIPLIER,
        'image'  => array(
            'normaltemp' => mt_rand(1, 7),
            'wasser'     => mt_rand(1, 9),
        ),
    ),
    12 => array(
        'temp'   => mt_rand(-10, 30),
        'fields' => mt_rand(136, 171) * PLANET_FIELDS_MULTIPLIER,
        'image'  => array(
            'normaltemp' => mt_rand(1, 7),
            'wasser'     => mt_rand(1, 9),
        ),
    ),
    13 => array(
        'temp'   => mt_rand(-50, -10),
        'fields' => mt_rand(109, 121) * PLANET_FIELDS_MULTIPLIER,
        'image'  => array(
            'eis' => mt_rand(1, 10),      // Losowy ID obrazu dla planety lodowej.
        ),
    ),
    14 => array(
        'temp'   => mt_rand(-90, -50),
        'fields' => mt_rand(81, 93) * PLANET_FIELDS_MULTIPLIER,
        'image'  => array(
            'eis' => mt_rand(1, 10),
        ),
    ),
    15 => array(
        'temp'   => mt_rand(-130, -90),
        'fields' => mt_rand(65, 74) * PLANET_FIELDS_MULTIPLIER,
        'image'  => array(
            'eis' => mt_rand(1, 10),
        ),
    ),
);

// Zgodność z PHP 8.4:
// Ten kod jest w pełni kompatybilny z PHP 8.4.
// Używa standardowych funkcji PHP (array, mt_rand, define) i nie zawiera żadnych konstrukcji,
// które mogłyby być problematyczne w nowszych wersjach PHP.