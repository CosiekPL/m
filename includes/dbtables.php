<?php
// Definicje stałych związanych z bazą danych.

// Wymagana wersja bazy danych (prawdopodobnie wewnętrzna numeracja systemu).
define('DB_VERSION_REQUIRED', 4);

// Nazwa bazy danych pobrana z konfiguracji ($database['databasename']).
define('DB_NAME', $database['databasename']);

// Prefiks tabel bazy danych pobrany z konfiguracji ($database['tableprefix']).
define('DB_PREFIX', $database['tableprefix']);

// Tablica asocjacyjna zawierająca nazwy tabel bazy danych.
// Klucze tablicy to logiczne nazwy tabel używane w kodzie PHP,
// a wartości to rzeczywiste nazwy tabel w bazie danych, składające się z prefiksu i nazwy tabeli.
$dbTableNames = array(
    'AKS'               => DB_PREFIX . 'aks',             // Tabela ataków skoordynowanych (AKS).
    'ALLIANCE'          => DB_PREFIX . 'alliance',        // Tabela sojuszy.
    'ALLIANCE_RANK'     => DB_PREFIX . 'alliance_ranks',  // Tabela rang sojuszu.
    'ALLIANCE_REQUEST'  => DB_PREFIX . 'alliance_request', // Tabela próśb o dołączenie do sojuszu.
    'BANNED'            => DB_PREFIX . 'banned',          // Tabela zbanowanych graczy.
    'BUDDY'             => DB_PREFIX . 'buddy',           // Tabela listy znajomych.
    'BUDDY_REQUEST'     => DB_PREFIX . 'buddy_request',   // Tabela próśb o dodanie do znajomych.
    'CHAT_BAN'          => DB_PREFIX . 'chat_bans',       // Tabela zbanowanych użytkowników czatu.
    'CHAT_INV'          => DB_PREFIX . 'chat_invitations', // Tabela zaproszeń do czatu.
    'CHAT_MES'          => DB_PREFIX . 'chat_messages',    // Tabela wiadomości czatu.
    'CHAT_ON'           => DB_PREFIX . 'chat_online',      // Tabela zalogowanych użytkowników czatu.
    'CONFIG'            => DB_PREFIX . 'config',          // Tabela konfiguracji gry.
    'CRONJOBS'          => DB_PREFIX . 'cronjobs',        // Tabela zadań cron.
    'CRONJOBS_LOG'      => DB_PREFIX . 'cronjobs_log',    // Tabela logów zadań cron.
    'DIPLO'             => DB_PREFIX . 'diplo',           // Tabela dyplomacji między sojuszami.
    'FLEETS'            => DB_PREFIX . 'fleets',          // Tabela flot w locie.
    'FLEETS_EVENT'      => DB_PREFIX . 'fleet_event',     // Tabela zdarzeń flot.
    'LOG'               => DB_PREFIX . 'log',             // Główna tabela logów.
    'LOG_FLEETS'        => DB_PREFIX . 'log_fleets',      // Tabela logów flot.
    'LOSTPASSWORD'      => DB_PREFIX . 'lostpassword',    // Tabela resetowania haseł.
    'NEWS'              => DB_PREFIX . 'news',            // Tabela newsów.
    'NOTES'             => DB_PREFIX . 'notes',           // Tabela notatek graczy.
    'MESSAGES'          => DB_PREFIX . 'messages',        // Tabela prywatnych wiadomości.
    'MULTI'             => DB_PREFIX . 'multi',           // Tabela wykrywania multikont.
    'PLANETS'           => DB_PREFIX . 'planets',         // Tabela planet.
    'RW'                => DB_PREFIX . 'raports',         // Tabela raportów bitewnych.
    'RECORDS'           => DB_PREFIX . 'records',         // Tabela rekordów gry.
    'SESSION'           => DB_PREFIX . 'session',         // Tabela sesji użytkowników.
    'SHORTCUTS'         => DB_PREFIX . 'shortcuts',       // Tabela skrótów graczy.
    'STATPOINTS'        => DB_PREFIX . 'statpoints',      // Tabela punktów statystyk graczy.
    'SYSTEM'            => DB_PREFIX . 'system',          // Tabela systemów słonecznych.
    'TICKETS'           => DB_PREFIX . 'ticket',          // Tabela zgłoszeń do supportu (ticketów).
    'TICKETS_ANSWER'    => DB_PREFIX . 'ticket_answer',   // Tabela odpowiedzi na zgłoszenia.
    'TICKETS_CATEGORY'  => DB_PREFIX . 'ticket_category', // Tabela kategorii zgłoszeń.
    'TOPKB'             => DB_PREFIX . 'topkb',           // Tabela top listy bitew.
    'TOPKB_USERS'       => DB_PREFIX . 'users_to_topkb',  // Tabela powiązań użytkowników z top listą bitew.
    'USERS'             => DB_PREFIX . 'users',           // Główna tabela użytkowników.
    'USERS_ACS'         => DB_PREFIX . 'users_to_acs',    // Tabela powiązań użytkowników z atakami ACS.
    'USERS_AUTH'        => DB_PREFIX . 'users_to_extauth', // Tabela powiązań użytkowników z zewnętrznymi systemami autoryzacji.
    'USERS_VALID'       => DB_PREFIX . 'users_valid',     // Tabela weryfikacji użytkowników (np. e-mail).
    'VARS'              => DB_PREFIX . 'vars',            // Tabela różnych zmiennych globalnych.
    'VARS_RAPIDFIRE'    => DB_PREFIX . 'vars_rapidfire',   // Tabela zmiennych szybkostrzelności.
    'VARS_REQUIRE'      => DB_PREFIX . 'vars_requriements', // Tabela zmiennych wymagań (np. budynków do technologii).
);
// MOD-TABLES - miejsce na definicje tabel dodanych przez modyfikacje (nie ma w tym pliku).

// Zgodność z PHP 8.4:
// Ten kod jest w pełni kompatybilny z PHP 8.4.
// Definiuje stałe i tablicę asocjacyjną, co jest standardową i bezpieczną praktyką w PHP.
// Należy jedynie upewnić się, że zmienna `$database` jest zdefiniowana i zawiera klucze 'databasename' i 'tableprefix'.