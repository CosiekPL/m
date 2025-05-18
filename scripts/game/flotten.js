var acstime = 0; // Zmienna globalna, prawdopodobnie związana z czasem ACS (atak skoordynowany).

function updateVars() {
    // Funkcja aktualizująca różne zmienne i pola formularza związane z lotem floty.
    document.getElementsByName("fleet_group")[0].value = 0; // Resetuje wartość grupy floty do 0.
    dataFlyDistance = GetDistance(); // Oblicza i przypisuje odległość lotu.
    dataFlyTime = GetDuration(); // Oblicza i przypisuje czas trwania lotu.
    dataFlyConsumption = GetConsumption(); // Oblicza i przypisuje zużycie paliwa.
    dataFlyCargoSpace = storage(); // Oblicza i przypisuje dostępną pojemność ładowni.
    refreshFormData(); // Aktualizuje wizualne elementy formularza na podstawie obliczonych wartości.
}

function GetDistance() {
    // Funkcja obliczająca odległość między planetami na podstawie wprowadzonych współrzędnych.
    const thisGalaxy = data.planet.galaxy; // Galaktyka startowa.
    const thisSystem = data.planet.system; // System startowy.
    const thisPlanet = data.planet.planet; // Planeta startowa.
    const targetGalaxy = document.getElementsByName("galaxy")[0].value; // Docelowa galaktyka.
    const targetSystem = document.getElementsByName("system")[0].value; // Docelowy system.
    const targetPlanet = document.getElementsByName("planet")[0].value; // Docelowa planeta.

    if (targetGalaxy - thisGalaxy != 0) {
        return Math.abs(targetGalaxy - thisGalaxy) * 20000; // Odległość międzygalaktyczna.
    } else if (targetSystem - thisSystem != 0) {
        return Math.abs(targetSystem - thisSystem) * 5 * 19 + 2700; // Odległość międzygwiezdna.
    } else if (targetPlanet - thisPlanet != 0) {
        return Math.abs(targetPlanet - thisPlanet) * 5 + 1000; // Odległość wewnątrzsystemowa.
    } else {
        return 5; // Minimalna odległość (prawdopodobnie dla tej samej planety).
    }
}

function GetDuration() {
    // Funkcja obliczająca czas trwania lotu floty.
    const sp = document.getElementsByName("speed")[0].value; // Prędkość floty (procent).
    // Oblicza czas trwania lotu na podstawie prędkości, odległości i prędkości gry/floty.
    return Math.max(Math.round((3500 / (sp * 0.1) * Math.pow(dataFlyDistance * 10 / data.maxspeed, 0.5) + 10) / data.gamespeed) * data.fleetspeedfactor, data.fleetMinDuration);
}

function GetConsumption() {
    // Funkcja obliczająca zużycie deuteru przez flotę podczas lotu.
    let dataFlyConsumption = 0;
    let dataFlyConsumption2 = 0;
    let basicConsumption = 0;
    let i;
    $.each(data.ships, function(shipid, ship) {
        // Oblicza efektywną prędkość statku.
        const spd = 35000 / (dataFlyTime * data.gamespeed - 10) * Math.sqrt(dataFlyDistance * 10 / ship.speed);
        basicConsumption = ship.consumption * ship.amount; // Podstawowe zużycie paliwa dla danego typu statku.
        // Dodaje do całkowitego zużycia paliwa wartość uwzględniającą odległość i prędkość.
        dataFlyConsumption2 += basicConsumption * dataFlyDistance / 35000 * (spd / 10 + 1) * (spd / 10 + 1);
    });
    return Math.round(dataFlyConsumption + dataFlyConsumption2) + 1; // Zaokrągla i dodaje 1.
}

function storage() {
    // Funkcja obliczająca dostępną pojemność ładowni floty po uwzględnieniu zużytego paliwa.
    return data.fleetroom - dataFlyConsumption;
}

function refreshFormData() {
    // Funkcja aktualizująca wizualne elementy formularza wyświetlające czas lotu, odległość, prędkość i zużycie paliwa.
    let seconds = dataFlyTime;
    const hours = Math.floor(seconds / 3600);
    seconds -= hours * 3600;
    const minutes = Math.floor(seconds / 60);
    seconds -= minutes * 60;
    $("#duration").text(hours + (":" + dezInt(minutes, 2) + ":" + dezInt(seconds, 2) + " h")); // Wyświetla sformatowany czas trwania.
    $("#distance").text(NumberGetHumanReadable(dataFlyDistance)); // Wyświetla sformatowaną odległość.
    $("#maxspeed").text(NumberGetHumanReadable(data.maxspeed)); // Wyświetla sformatowaną maksymalną prędkość.
    // Aktualizuje wyświetlane zużycie paliwa i pojemność ładowni, kolorując tekst na zielono, jeśli jest wystarczająco miejsca, a na czerwono, jeśli nie.
    const consumptionColor = dataFlyCargoSpace >= 0 ? "lime" : "red";
    $("#consumption").html("<font color=\"" + consumptionColor + "\">" + NumberGetHumanReadable(dataFlyConsumption) + "</font>");
    $("#storage").html("<font color=\"" + consumptionColor + "\">" + NumberGetHumanReadable(dataFlyCargoSpace) + "</font>");
}

function setACSTarget(galaxy, solarsystem, planet, type, tacs) {
    // Funkcja ustawiająca cel ataku skoordynowanego (ACS) i aktualizująca formularz.
    setTarget(galaxy, solarsystem, planet, type); // Ustawia docelowe współrzędne.
    updateVars(); // Aktualizuje zmienne i formularz.
    document.getElementsByName("fleet_group")[0].value = tacs; // Ustawia ID grupy ACS.
}

function setTarget(galaxy, solarsystem, planet, type) {
    // Funkcja ustawiająca docelowe współrzędne w odpowiednich polach formularza.
    document.getElementsByName("galaxy")[0].value = galaxy;
    document.getElementsByName("system")[0].value = solarsystem;
    document.getElementsByName("planet")[0].value = planet;
    document.getElementsByName("type")[0].value = type; // Typ planety docelowej.
}

function FleetTime(){
    // Funkcja aktualizująca wyświetlany czas przybycia i powrotu floty.
    const sekunden = serverTime.getSeconds(); // Aktualna sekunda czasu serwera.
    const starttime = dataFlyTime; // Czas trwania lotu.
    const endtime	= starttime + dataFlyTime; // Czas powrotu (zakładając natychmiastowy powrót).
    $("#arrival").html(getFormatedDate(serverTime.getTime()+1000*starttime, tdformat)); // Wyświetla sformatowany czas przybycia.
    $("#return").html(getFormatedDate(serverTime.getTime()+1000*endtime, tdformat)); // Wyświetla sformatowany czas powrotu.
}

function setResource(id, val) {
    // Funkcja ustawiająca ilość surowca do transportu w odpowiednim polu formularza.
    const element = document.getElementsByName(id)[0];
    if (element) {
        document.getElementsByName("resource" + id)[0].value = val;
    }
}

function maxResource(id) {
    // Funkcja ustawiająca maksymalną możliwą ilość surowca do transportu.
    let thisresource = getRessource(id); // Dostępna ilość surowca na planecie.
    let thisresourcechosen = parseInt(document.getElementsByName(id)[0].value) || 0; // Aktualnie wybrana ilość surowca do transportu.

    let storCap = data.fleetroom - data.consumption; // Całkowita pojemność ładowni minus zużyte paliwo.

    if (id === 'deuterium') {
        thisresource -= data.consumption; // Dostępne deuterium minus zużycie na lot.
    }
    const metalToTransport = parseInt(document.getElementsByName("metal")[0].value) || 0;
    const crystalToTransport = parseInt(document.getElementsByName("crystal")[0].value) || 0;
    const deuteriumToTransport = parseInt(document.getElementsByName("deuterium")[0].value) || 0;
    const freeCapacity = Math.max(storCap - metalToTransport - crystalToTransport - deuteriumToTransport, 0); // Oblicza wolną pojemność ładowni.
    document.getElementsByName(id)[0].value = Math.min(freeCapacity + thisresourcechosen, thisresource); // Ustawia ilość surowca do transportu, nie przekraczając dostępnej ilości i wolnej pojemności.
    calculateTransportCapacity(); // Aktualizuje wyświetlaną informację o pozostałej pojemności.
}


function maxResources() {
    // Funkcja ustawiająca maksymalną ilość wszystkich surowców do transportu.
    maxResource('metal');
    maxResource('crystal');
    maxResource('deuterium');
}

function calculateTransportCapacity() {
    // Funkcja obliczająca i wyświetlająca pozostałą pojemność ładowni.
    const metal = Math.abs(document.getElementsByName("metal")[0].value) || 0;
    const crystal = Math.abs(document.getElementsByName("crystal")[0].value) || 0;
    const deuterium = Math.abs(document.getElementsByName("deuterium")[0].value) || 0;
    const transportCapacity = data.fleetroom - data.consumption - metal - crystal - deuterium; // Oblicza pozostałą pojemność.
    const remainingResourcesElement = document.getElementById("remainingresources");
    if (transportCapacity < 0) {
        remainingResourcesElement.innerHTML = "<font color=red>" + NumberGetHumanReadable(transportCapacity) + "</font>"; // Wyświetla na czerwono, jeśli pojemność jest niewystarczająca.
    } else {
        remainingResourcesElement.innerHTML = "<font color=lime>" + NumberGetHumanReadable(transportCapacity) + "</font>"; // Wyświetla na zielono, jeśli jest wystarczająco miejsca.
    }
    return transportCapacity; // Zwraca obliczoną pozostałą pojemność.
}

function maxShip(id) {
    // Funkcja ustawiająca maksymalną dostępną liczbę danego typu statku do wysłania.
    const element = document.getElementsByName(id)[0];
    if (element) {
        const amountElement = document.getElementById(id + "_value");
        if (amountElement) {
            const amount = amountElement.innerHTML.replace(/\./g, ""); // Pobiera dostępną ilość i usuwa separatory tysięcy.
            document.getElementsByName(id)[0].value = amount; // Ustawia maksymalną dostępną ilość w polu input.
        }
    }
}

function maxShips() {
    // Funkcja ustawiająca maksymalną liczbę wszystkich dostępnych statków do wysłania.
    $('input[name^="ship"]').each(function() {
        maxShip($(this).attr('name')); // Wywołuje maxShip dla każdego inputa, którego nazwa zaczyna się od "ship".
    });
}


function noShip(id) {
    // Funkcja ustawiająca liczbę danego typu statku do wysłania na zero.
    const element = document.getElementsByName(id)[0];
    if (element) {
        document.getElementsByName(id)[0].value = 0;
    }
}


function noShips() {
    // Funkcja ustawiająca liczbę wszystkich statków do wysłania na zero.
    $('input[name^="ship"]').each(function() {
        noShip($(this).attr('name')); // Wywołuje noShip dla każdego inputa, którego nazwa zaczyna się od "ship".
    });
}

function setNumber(name, number) {
    // Funkcja ustawiająca określoną liczbę statków do wysłania.
    const element = document.getElementsByName("ship" + name)[0];
    if (typeof element != "undefined") {
        document.getElementsByName("ship" + name)[0].value = number;
    }
}

function CheckTarget()
{
    // Funkcja sprawdzająca, czy wprowadzone współrzędne celu są poprawne.
    const kolo = (typeof data.ships[208] === "object") ? 1 : 0; // Sprawdza, czy gracz posiada statek o ID 208 (prawdopodobnie kolonizator).

    $.getJSON('game.php?page=fleetStep1&mode=checkTarget&galaxy='+document.getElementsByName("galaxy")[0].value+'&system='+document.getElementsByName("system")[0].value+'&planet='+document.getElementsByName("planet")[0].value+'&planet_type='+document.getElementsByName("type")[0].value+'&lang='+Lang+'&kolo='+kolo, function(data) {
        // Wysyła żądanie AJAX GET w formacie JSON do serwera w celu sprawdzenia celu.
        if(data === "OK") {
            document.getElementById('form').submit(); // Jeśli odpowiedź to "OK", wysyła formularz.
        } else {
            NotifyBox(data); // Jeśli odpowiedź jest inna, wyświetla powiadomienie (zakłada się, że funkcja NotifyBox jest zdefiniowana).
        }
    });
    return false; // Zapobiega domyślnej akcji formularza.
}

function EditShortcuts(autoadd) {
    // Funkcja przełączająca widoczność linków i pól edycji skrótów.
    $(".shortcut-link").hide(); // Ukrywa elementy wyświetlające linki skrótów.
    $(".shortcut-edit:not(.shortcut-new)").show(); // Pokazuje pola edycji skrótów, które nie są nowymi skrótami.
    if ($('.shortcut-isset').length === 0) // Sprawdza, czy nie ma już ustawionych skrótów.
        AddShortcuts(); // Jeśli nie ma, wywołuje funkcję dodającą nowy skrót.
}

function AddShortcuts() {
    // Funkcja dodająca nowe pole do edycji skrótu.
    const HTML = $('.shortcut-new td:first').clone().children(); // Klonuje zawartość pierwszej komórki w wierszu nowego skrótu.
    HTML.find('input, select').attr('name', function(i, old) {
        return old.replace("shortcut[]", "shortcut[" + ($('.shortcut-link').length) + "-new]"); // Aktualizuje atrybuty 'name' inputów i selectów dla nowego skrótu.
    });

    let nextFreeColum = $('.shortcut-row:last td:not(.shortcut-isset):first'); // Znajduje pierwszą wolną komórkę w ostatnim wierszu skrótów.

    if (nextFreeColum.length === 0) { // Jeśli nie ma wolnej komórki w ostatnim wierszu.
        if ($('.shortcut-row:last').length) { // Sprawdza, czy istnieje już jakiś wiersz skrótów.
            const newRow = $('<tr />').addClass('shortcut-row').insertAfter('.shortcut-row:last'); // Tworzy nowy wiersz i dodaje go po ostatnim wierszu skrótów.
            for (let i = 1; i <= shortCutRows; i++) {
                newRow.append('<td class="shortcut-colum" style="width:' + (100 / shortCutRows) + '%">&nbsp</td>'); // Dodaje puste komórki do nowego wiersza.
            }
            nextFreeColum = $('.shortcut-row:last td:first'); // Ponownie znajduje pierwszą komórkę w nowym wierszu.
        } else { // Jeśli nie ma jeszcze wierszy skrótów.
            const newRow = $('<tr />').addClass('shortcut-row').insertAfter('.shortcut-none'); // Tworzy nowy wiersz i dodaje go po wierszu "brak skrótów".
            for (let i = 1; i <= shortCutRows; i++) {
                newRow.append('<td class="shortcut-colum" style="width:' + (100 / shortCutRows) + '%">&nbsp;</td>'); // Dodaje puste komórki do nowego wiersza.
            }
            nextFreeColum = $('.shortcut-row:last td:first'); // Znajduje pierwszą komórkę w nowym wierszu.
            $('.shortcut-none').remove(); // Usuwa wiersz "brak skrótów".
        }
    }

    nextFreeColum.html(HTML).addClass("shortcut-isset"); // Wstawia sklonowaną zawartość do wolnej komórki i dodaje klasę oznaczającą, że komórka jest zajęta.
}

function SaveShortcuts(reedit) {
    // Funkcja zapisująca skróty i aktualizująca ich wyświetlanie.
    $.getJSON('game.php?page=fleetStep1&mode=saveShortcuts&ajax=1&' + $('.shortcut-row').find("input, select").serialize(), function(res) {
        // Wysyła żądanie AJAX GET w formacie JSON z serializowanymi danymi formularza skrótów.
        $(".shortcut-link").show(); // Pokazuje linki skrótów.
        $(".shortcut-edit").hide(); // Ukrywa pola edycji skrótów.

        const deadElements = $(".shortcut-isset").filter(function() {
            // Filtruje zajęte komórki, które mają puste wymagane pola.
            return $('input[name*=name]', this).val() === "" ||
                $('input[name*=galaxy]', this).val() === "" || $('input[name*=galaxy]', this).val() === "0" ||
                $('input[name*=system]', this).val() === "" || $('input[name*=system]', this).val() === "0" ||
                $('input[name*=planet]', this).val() === "" || $('input[name*=planet]', this).val() === "0";
        });

        if (deadElements.length % 2 === 1) { // Jeśli liczba pustych skrótów jest nieparzysta.
            deadElements.remove(); // Usuwa puste skróty.
            $(".shortcut-colum:last").after('<td class="shortcut-colum" style="width:' + (100 / shortCutRows) + '%">&nbsp;</td>'); // Dodaje pustą komórkę, aby wyrównać wiersz.
        }

        $(".shortcut-isset").unwrap(); // Usuwa otoczkę (prawdopodobnie div) z zajętych komórek.

        const activeElements = Math.ceil($(".shortcut-isset").length / shortCutRows); // Oblicza liczbę aktywnych wierszy skrótów.

        if (activeElements === 0) { // Jeśli nie ma aktywnych skrótów.
            $('<tr style="height:20px;" class="shortcut-none"><td colspan="' + shortCutRows + '">' + fl_no_shortcuts + '</td></tr>').insertAfter('.shortcut tr:first'); // Dodaje wiersz "brak skrótów".
        } else { // Jeśli są aktywne skróty.
            for (let i = 1; i <= activeElements; i++) {
                $('<tr />').addClass('shortcut-row').insertAfter('.shortcut tr:first'); // Tworzy wiersze dla skrótów.
            }

            $(".shortcut-colum").each(function(i) {
                $(this).appendTo('tr.shortcut-row:eq(' + Math.floor(i / 3) + ')'); // Przenosi komórki skrótów do odpowiednich wierszy.
            });

            $('.shortcut-colum').filter(function() {
                return $(this).parent().is(':not(tr)'); // Filtruje komórki, które nie znajdują się w wierszach.
            }).remove(); // Usuwa te komórki.

            $('.shortcut-row').filter(function() {
                return !$(this).children('.shortcut-isset').length; // Filtruje puste wiersze skrótów.
            }).remove(); // Usuwa puste wiersze.

            $(".shortcut-isset > .shortcut-link").html(function() {
                // Aktualizuje HTML linków skrótów.
                if ($(this).nextAll().find('input[name*=name]').val() === "") { // Jeśli nazwa skrótu jest pusta.
                    $(this).parent().html("&nbsp;"); // Wstawia spację.
                    return false;
                }
                const Data = $(this).nextAll(); // Pobiera dane powiązane ze skrótem.
                return '<a href="javascript:setTarget(' + Data.find('input[name*=galaxy]').val() + ',' + Data.find('input[name*=system]').val() + ',' + Data.find('input[name*=planet]').val() + ',' + Data.find('select[name*=type]').val() + ');updateVars();">' + Data.find('input[name*=name]').val() + '(' + Data.nextAll().find('select[name*=type] option:selected').text()[0] + ') [' + Data.find('input[name*=galaxy]').val() + ':' + Data.find('input[name*=system]').val() + ':' + Data.find('input[name*=planet]').val() + ']</a>';
            });
        }

        $('.shortcut-row:has(td:not(.shortcut-isset) + td)').remove(); // Usuwa wiersze z niepełną liczbą skrótów na końcu.

        if (typeof reedit === "undefinded" || reedit !== true) { // Jeśli 'reedit' nie jest true.
            NotifyBox(res); // Wyświetla powiadomienie o wyniku zapisywania.
        } else { // Jeśli 'reedit' jest true.
            if ($(".shortcut-isset").length) { // Jeśli są jakieś ustawione skróty.
                EditShortcuts(); // Ponownie włącza tryb edycji skrótów.
            }
        }
    });
}

$(function() {
	$('.shortcut-delete').live('click', function() {
		$(this).prev().val('');
		$(this).parent().find('input');
		SaveShortcuts(true);
	});
});