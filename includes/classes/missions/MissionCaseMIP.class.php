<?php

require_once 'includes/classes/missions/MissionFunctions.php'; // Załaduj klasę bazową dla misji.
require_once 'includes/classes/missions/Mission.interface.php'; // Załaduj interfejs misji.

/**
 * Klasa MissionCaseMIP implementująca interfejs Mission.
 * Obsługuje misję ataku rakietami międzyplanetarnymi (MIP).
 */
class MissionCaseMIP extends MissionFunctions implements Mission
{
    /**
     * @var array Dane floty wykonującej misję.
     */
    protected $_fleet;

    /**
     * Konstruktor klasy. Inicjalizuje dane floty.
     *
     * @param array $Fleet Dane floty.
     */
    function __construct($Fleet)
    {
        $this->_fleet = $Fleet;
    }

    /**
     * Wykonuje akcje po dotarciu floty do celu (uderzenie rakietami).
     * Aktualizuje liczbę rakiet przeciwrakietowych i niszczy obronę planety.
     * Wysyła raporty do atakującego i broniącego.
     * Na końcu niszczy flotę MIP.
     *
     * @return void
     */
    function TargetEvent(): void
    {
        global $resource, $reslist; // Dostęp do globalnych tablic zasobów i listy elementów.

        $db = Database::get(); // Pobierz instancję bazy danych.

        $sqlFields = []; // Tablica pól SQL do pobrania (obrona i rakiety).
        $elementIDs = array_merge($reslist['defense'], $reslist['missile']); // Połącz listy obrony i rakiet.

        // Buduj listę pól do pobrania z tabeli planet.
        foreach ($elementIDs as $elementID) {
            $sqlFields[] = '%%PLANETS%%.`' . $resource[$elementID] . '`';
        }

        // Zapytanie SQL pobierające dane celu (planety) wraz z poziomem technologii tarcz i ilością obrony/rakiet.
        $sql = 'SELECT lang, shield_tech,
        %%PLANETS%%.id, name, id_owner, ' . implode(', ', $sqlFields) . '
        FROM %%PLANETS%%
        INNER JOIN %%USERS%% ON id_owner = %%USERS%%.id
        WHERE %%PLANETS%%.id = :planetId;';

        $targetData = $db->selectSingle($sql, [
            ':planetId' => $this->_fleet['fleet_end_id'], // ID planety docelowej.
        ]);

        // Jeśli celem jest księżyc, pobierz ilość rakiet przeciwrakietowych z jego pola 'id_luna'.
        if ($this->_fleet['fleet_end_type'] == 3) {
            $sql = 'SELECT ' . $resource[502] . ' FROM %%PLANETS%% WHERE id_luna = :moonId;';
            $targetData[$resource[502]] = $db->selectSingle($sql, [
                ':moonId' => $this->_fleet['fleet_end_id']
            ], $resource[502]);
        }

        // Zapytanie SQL pobierające poziom technologii militarnej atakującego.
        $sql = 'SELECT lang, military_tech FROM %%USERS%% WHERE id = :userId;';
        $senderData = $db->selectSingle($sql, [
            ':userId' => $this->_fleet['fleet_owner'] // ID atakującego.
        ]);

        // Określ cel priorytetowy ataku MIP (domyślnie pierwszy lepszy element obrony).
        if (!in_array($this->_fleet['fleet_target_obj'], array_merge($reslist['defense'], $reslist['missile']))
            || $this->_fleet['fleet_target_obj'] == 502
            || $this->_fleet['fleet_target_obj'] == 0) {
            $primaryTarget = 401; // Domyślny cel: wyrzutnia rakietowa.
        } else {
            $primaryTarget = $this->_fleet['fleet_target_obj']; // Cel wybrany przez gracza.
        }

        $targetDefensive = []; // Tablica ilości poszczególnych jednostek obronnych na celu.

        // Wypełnij tablicę obrony celu.
        foreach ($elementIDs as $elementID) {
            $targetDefensive[$elementID] = $targetData[$resource[$elementID]];
        }

        // Usuń rakiety przeciwrakietowe z listy celów dla MIP.
        unset($targetDefensive[502]);

        $LNG = $this->getLanguage(Config::get($this->_fleet['fleet_universe'])->lang, ['L18N', 'FLEET', 'TECH']); // Załaduj język.

        // Obsługa przechwycenia rakiet przeciwrakietowych.
        if ($targetData[$resource[502]] >= $this->_fleet['fleet_amount']) {
            $message = $LNG['sys_irak_no_att']; // Komunikat o przechwyceniu wszystkich rakiet.
            $where = $this->_fleet['fleet_end_type'] == 3 ? 'id_luna' : 'id'; // Określ pole ID dla planety lub księżyca.

            $sql = 'UPDATE %%PLANETS%% SET ' . $resource[502] . ' = ' . $resource[502] . ' - :amount WHERE ' . $where . ' = :planetId;';

            $db->update($sql, [
                ':amount' => $this->_fleet['fleet_amount'], // Zmniejsz liczbę rakiet przeciwrakietowych.
                ':planetId' => $targetData['id']
            ]);
        } else {
            // Częściowe lub brak przechwycenia.
            if ($targetData[$resource[502]] > 0) {
                $where = $this->_fleet['fleet_end_type'] == 3 ? 'id_luna' : 'id';
                $sql = 'UPDATE %%PLANETS%% SET ' . $resource[502] . ' = :amount WHERE ' . $where . ' = :planetId;';

                $db->update($sql, [
                    ':amount' => 0, // Zniszcz wszystkie rakiety przeciwrakietowe.
                    ':planetId' => $targetData['id']
                ]);
            }

            // Usuń jednostki obronne z zerową ilością.
            $targetDefensive = array_filter($targetDefensive);

            // Jeśli na planecie jest jakaś obrona.
            if (!empty($targetDefensive)) {
                require_once 'includes/classes/missions/functions/calculateMIPAttack.php'; // Załaduj funkcję obliczającą zniszczenia MIP.
                $result = calculateMIPAttack($targetData["shield_tech"], $senderData["military_tech"],
                    $this->_fleet['fleet_amount'], $targetDefensive, $primaryTarget, $targetData[$resource[502]]);

                $result = array_filter($result); // Usuń z wyniku zniszczone jednostki z zerową ilością.

                $message = sprintf($LNG['sys_irak_def'], $targetData[$resource[502]]) . '<br><br>'; // Komunikat o zniszczonych rakietach przeciwrakietowych.

                ksort($result, SORT_NUMERIC); // Sortuj zniszczone jednostki według ID.

                // Aktualizuj liczbę zniszczonych jednostek obronnych na planecie.
                foreach ($result as $Element => $destroy) {
                    $message .= sprintf('%s (- %d)<br>', $LNG['tech'][$Element], $destroy);

                    $sql = 'UPDATE %%PLANETS%% SET ' . $resource[$Element] . ' = ' . $resource[$Element] . ' - :amount WHERE id = :planetId;';
                    $db->update($sql, [
                        ':planetId' => $targetData['id'],
                        ':amount' => $destroy
                    ]);
                }
            } else {
                $message = $LNG['sys_irak_no_def']; // Komunikat o braku obrony.
            }
        }

        // Pobierz nazwę planety startowej.
        $sql = 'SELECT name FROM %%PLANETS%% WHERE id = :planetId;';
        $planetName = Database::get()->selectSingle($sql, [
            ':planetId' => $this->_fleet['fleet_start_id'],
        ], 'name');

        // Wygeneruj linki do planet startowej i docelowej.
		$ownerLink			= $planetName." ".GetStartAddressLink($this->_fleet);
		$targetLink 		= $targetData['name']." ".GetTargetAddressLink($this->_fleet);
		$message			= sprintf($LNG['sys_irak_mess'], $this->_fleet['fleet_amount'], $ownerLink, $targetLink).$message;

		PlayerUtil::sendMessage($this->_fleet['fleet_owner'], 0, $LNG['sys_mess_tower'], 3,
			$LNG['sys_irak_subject'], $message, $this->_fleet['fleet_start_time'], NULL, 1, $this->_fleet['fleet_universe']);

		PlayerUtil::sendMessage($this->_fleet['fleet_target_owner'], 0, $LNG['sys_mess_tower'], 3,
			$LNG['sys_irak_subject'], $message, $this->_fleet['fleet_start_time'], NULL, 1, $this->_fleet['fleet_universe']);

		$this->KillFleet();
	}
	
	function EndStayEvent()
	{
		return;
	}
	
	function ReturnEvent()
	{
		return;
	}
}
