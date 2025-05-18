<?php
declare(strict_types=1);

//SET TIMEZONE (if Server Timezone are not correct)
//date_default_timezone_set('Europe/Warsaw');

//TEMPLATES DEFAULT SETTINGS
define('DEFAULT_THEME'            , 'star');
define('HTTPS'                    , false); // true = aktywny https | false = nieaktywny https
define('PROTOCOL'                 , HTTPS ? 'https://' : 'http://'); // Protokół strony
define('HTTP_HOST'                , 'localhost');
define('HTTP_BASE'                , '/');
define('HTTP_ROOT'                , PROTOCOL.HTTP_HOST.HTTP_BASE); // Adres URL gry
define('COOKIE_LIFETIME'          , 31536000);

// KONFIGURACJA
define('COMBAT_REPORT_HISTORY_SIZE'    , 100);
define('COMBAT_REPORT_HISTORY_DAYS'    , 14);

// DANE UNIWERSUM, nazwa realmu, prędkości, galaktyki, systemy i planety
define('UNIVERSE_FACTOR'               , 1);
define('MAX_GALAXY_IN_WORLD'           , 5);
define('MAX_SYSTEM_IN_GALAXY'          , 400);
define('MAX_PLANET_IN_SYSTEM'          , 15);

// DODANE DLA TWIG
define('TWIG_CACHE_ENABLED'            , true); // Ustaw na true w środowisku produkcyjnym
define('TWIG_DEBUG'                    , false);  // Ustaw na false w środowisku produkcyjnym
