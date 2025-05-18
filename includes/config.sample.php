<?php

// ### Konfiguracja dostępu do bazy danych ### //

// Tablica asocjacyjna przechowująca dane połączenia z bazą danych.
$database = array();

// Adres hosta serwera bazy danych. Zostanie zastąpiony konkretną wartością (%s).
$database['host'] = '%s';

// Port, na którym nasłuchuje serwer bazy danych. Zostanie zastąpiony konkretną wartością (%s).
$database['port'] = '%s';

// Nazwa użytkownika bazy danych. Zostanie zastąpiona konkretną wartością (%s).
$database['user'] = '%s';

// Hasło użytkownika bazy danych. Zostanie zastąpione konkretną wartością (%s).
$database['userpw'] = '%s';

// Nazwa bazy danych, z której korzysta aplikacja. Zostanie zastąpiona konkretną wartością (%s).
$database['databasename'] = '%s';

// Prefiks dodawany do nazw tabel bazy danych. Umożliwia instalację wielu instancji aplikacji w jednej bazie danych. Zostanie zastąpiony konkretną wartością (%s).
$database['tableprefix'] = '%s';

// Sól używana do haszowania haseł użytkowników. Powinna być unikalnym, losowym ciągiem 22 znaków z alfabetu "./0-9A-Za-z". Zostanie zastąpiona konkretną wartością (%s).
$salt = '%s'; // 22 digits from the alphabet "./0-9A-Za-z"

// ### Nie zmieniaj niczego powyżej tej linii ### //

// Ta sekcja kodu oznacza koniec konfiguracji bazy danych.
// Wszystkie ustawienia specyficzne dla połączenia z bazą danych
// znajdują się powyżej. Zmiana czegokolwiek poniżej może spowodować
// nieprawidłowe działanie aplikacji.

?>