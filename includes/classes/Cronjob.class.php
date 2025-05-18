<?php

require_once 'includes/classes/cronjob/CronjobTask.interface.php'; // Załaduj interfejs dla zadań cron.

/**
 * Klasa Cronjob.
 * Zarządza wykonywaniem zadań crona (automatycznie uruchamianych skryptów).
 */
class Cronjob
{
    /**
     * Konstruktor klasy (pusty, ponieważ klasa zawiera tylko statyczne metody).
     */
    function __construct()
    {
    }

    /**
     * Wykonuje zadanie crona o podanym ID, jeśli jest aktywne i nie jest zablokowane.
     * Blokuje zadanie na czas wykonania, uruchamia jego metodę run(),
     * ponownie oblicza czas następnego uruchomienia i zwalnia blokadę.
     * Zapisuje również log wykonania zadania.
     *
     * @param int $cronjobID ID zadania crona do wykonania.
     *
     * @return void
     *
     * @throws Exception Wyrzuca wyjątek, jeśli zadanie crona o podanym ID nie istnieje lub jest nieaktywne.
     */
    static function execute($cronjobID): void
    {
        $lockToken = md5(TIMESTAMP); // Generuj unikalny token blokady dla tego wykonania.

        $db = Database::get(); // Pobierz instancję bazy danych.

        // Pobierz nazwę klasy zadania crona na podstawie ID, jeśli jest aktywne i nie jest zablokowane.
        $sql = 'SELECT class FROM %%CRONJOBS%% WHERE isActive = :isActive AND cronjobID = :cronjobId AND `lock` IS NULL;';
        $cronjobClassName = $db->selectSingle($sql, [
            ':isActive'  => 1,
            ':cronjobId' => $cronjobID
        ], 'class');

        // Jeśli nie znaleziono aktywnego i niezablokowanego zadania o podanym ID, wyrzuć wyjątek.
        if (empty($cronjobClassName)) {
            throw new Exception(sprintf("Nieznane zadanie crona %s lub zadanie jest nieaktywne!", $cronjobID));
        }

        // Zablokuj zadanie crona, ustawiając token blokady.
        $sql = 'UPDATE %%CRONJOBS%% SET `lock` = :lock WHERE cronjobID = :cronjobId;';
        $db->update($sql, [
            ':lock'      => $lockToken,
            ':cronjobId' => $cronjobID
        ]);

        // Skonstruuj ścieżkę do pliku klasy zadania crona.
        $cronjobPath = 'includes/classes/cronjob/' . $cronjobClassName . '.class.php';

        // Wymagaj (zatrzymaj skrypt w przypadku błędu) pliku klasy zadania crona.
        require_once($cronjobPath);

        /** @var CronjobTask $cronjobObj Utwórz instancję klasy zadania crona. */
        $cronjobObj = new $cronjobClassName;
        $cronjobObj->run(); // Wykonaj metodę run() zadania crona.

        // Ponownie oblicz czas następnego uruchomienia zadania crona.
        self::reCalculateCronjobs($cronjobID);

        // Zwolnij blokadę zadania crona.
        $sql = 'UPDATE %%CRONJOBS%% SET `lock` = NULL WHERE cronjobID = :cronjobId;';
        $db->update($sql, [
            ':cronjobId' => $cronjobID
        ]);

        // Zapisz log wykonania zadania crona.
        $sql = 'INSERT INTO %%CRONJOBS_LOG%% SET `cronjobId` = :cronjobId,
        `executionTime` = :executionTime, `lockToken` = :lockToken';
        $db->insert($sql, [
            ':cronjobId'     => $cronjobID,
            ':executionTime' => Database::formatDate(TIMESTAMP),
            ':lockToken'     => $lockToken
        ]);
    }

    /**
     * Pobiera listę ID zadań crona, które powinny zostać wykonane (aktywne, czas następnego uruchomienia minął i nie są zablokowane).
     *
     * @return array Tablica ID zadań crona do wykonania.
     */
    static function getNeedTodoExecutedJobs(): array
    {
        $sql = 'SELECT cronjobID
        FROM %%CRONJOBS%%
        WHERE isActive = :isActive AND nextTime < :time AND `lock` IS NULL;';

        $cronjobResult = Database::get()->select($sql, [
            ':isActive' => 1,
            ':time'     => TIMESTAMP
        ]);

        $cronjobList = [];

        foreach ($cronjobResult as $cronjobRow) {
            $cronjobList[] = $cronjobRow['cronjobID'];
        }

        return $cronjobList;
    }

    /**
     * Pobiera czas ostatniego wykonania zadania crona o podanej nazwie.
     *
     * @param string $cronjobName Nazwa zadania crona.
     *
     * @return int|false Znacznik czasu (timestamp) ostatniego wykonania lub false, jeśli zadanie nigdy nie było wykonane.
     */
    static function getLastExecutionTime($cronjobName)
    {
        require_once 'includes/libs/tdcron/class.tdcron.php'; // Załaduj bibliotekę tdCron.
        require_once 'includes/libs/tdcron/class.tdcron.entry.php'; // Załaduj klasę wpisu tdCron.

        $sql = 'SELECT MAX(executionTime) as executionTime FROM %%CRONJOBS_LOG%% INNER JOIN %%CRONJOBS%% USING(cronjobId) WHERE name = :cronjobName;';
        $lastTime = Database::get()->selectSingle($sql, [
            ':cronjobName' => $cronjobName
        ], 'executionTime');

        if (empty($lastTime)) {
            return false;
        }

        return strtotime($lastTime);
    }

    /**
     * Ponownie oblicza czas następnego uruchomienia dla określonego zadania crona lub wszystkich zadań.
     *
     * @param int|null $cronjobID Opcjonalny ID zadania crona do ponownego obliczenia. Jeśli null, oblicz dla wszystkich.
     *
     * @return void
     */
    static function reCalculateCronjobs($cronjobID = null): void
    {
        require_once 'includes/libs/tdcron/class.tdcron.php'; // Załaduj bibliotekę tdCron.
        require_once 'includes/libs/tdcron/class.tdcron.entry.php'; // Załaduj klasę wpisu tdCron.

        $db = Database::get(); // Pobierz instancję bazy danych.

        if (!empty($cronjobID)) {
            $sql = 'SELECT cronjobID, min, hours, dom, month, dow FROM %%CRONJOBS%% WHERE cronjobID = :cronjobId;';
            $cronjobResult = $db->select($sql, [
                ':cronjobId' => $cronjobID
            ]);
        } else {
            $sql = 'SELECT cronjobID, min, hours, dom, month, dow FROM %%CRONJOBS%%;';
            $cronjobResult = $db->select($sql);
        }

        $sql = 'UPDATE %%CRONJOBS%% SET nextTime = :nextTime WHERE cronjobID = :cronjobId;';

        foreach ($cronjobResult as $cronjobRow) {
            $cronTabString = implode(' ', [$cronjobRow['min'], $cronjobRow['hours'], $cronjobRow['dom'], $cronjobRow['month'], $cronjobRow['dow']]);
            $nextTime = tdCron::getNextOccurrence($cronTabString, TIMESTAMP + 60); // Oblicz czas następnego uruchomienia (dodaj 60 sekund, aby uniknąć natychmiastowego ponownego uruchomienia).

            $db->update($sql, [
                ':nextTime'  => $nextTime,
                ':cronjobId' => $cronjobRow['cronjobID'],
            ]);
        }
    }
}