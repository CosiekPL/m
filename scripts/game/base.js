function number_format (number, decimals) {
    // Formatuje liczbę z zadaną liczbą miejsc po przecinku i separatorem tysięcy.
    number = (number + '').replace(/[^0-9+\-Ee.]/g, ''); // Usuwa wszystkie znaki inne niż cyfry, +, -, E, e, .
    var n = !isFinite(+number) ? 0 : +number, // Parsuje liczbę, jeśli nie jest skończona, ustawia na 0.
        prec = !isFinite(+decimals) ? 0 : Math.abs(decimals), // Parsuje liczbę miejsc po przecinku, jeśli nie jest skończona, ustawia na 0 i bierze wartość bezwzględną.
        sep = '.', // Separator tysięcy.
        dec = ',', // Separator dziesiętny.
        s = '',
        toFixedFix = function (n, prec) {
            // Pomocnicza funkcja do zaokrąglania liczb zmiennoprzecinkowych.
            var k = Math.pow(10, prec);
            return '' + Math.round(n * k) / k;
        };
    // Fix for IE parseFloat(0.55).toFixed(0) = 0;
    s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.'); // Zaokrągla liczbę i dzieli na część całkowitą i dziesiętną.
    if (s[0].length > 3) {
        s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep); // Dodaje separator tysięcy do części całkowitej.
    }
    if ((s[1] || '').length < prec) {
        s[1] = s[1] || '';
        s[1] += new Array(prec - s[1].length + 1).join('0'); // Dopełnia zerami część dziesiętną do żądanej długości.
    }
    return s.join(dec); // Łączy część całkowitą i dziesiętną separatorem dziesiętnym.
}

function NumberGetHumanReadable(value, dec) {
    // Formatuje liczbę do czytelnej formy z opcjonalną liczbą miejsc po przecinku.
    if (typeof dec === "undefined") {
        dec = 0; // Domyślna liczba miejsc po przecinku to 0.
    }
    if (dec == 0) {
        value = removeE(Math.floor(value)); // Usuwa notację wykładniczą i zaokrągla w dół, jeśli brak miejsc po przecinku.
    }
    return number_format(value, dec); // Wywołuje number_format do właściwego formatowania.
}

function shortly_number(number) {
    // Skraca duże liczby, dodając odpowiedni sufiks (K, M, B, T, Q, Q+, S, S+, O, N).
    var unit = ["", "K", "M", "B", "T", "Q", "Q+", "S", "S+", "O", "N"]; // Tablica z sufiksami jednostek.
    var negate = number < 0 ? -1 : 1; // Zapamiętuje znak liczby.
    var key = 0; // Indeks aktualnej jednostki.
    number = Math.abs(number); // Bierze wartość bezwzględną liczby.

    if (number >= 1000000) {
        ++key;
        while (number >= 1000000) {
            ++key;
            number = number / 1000000; // Dzieli liczbę przez milion, zwiększając jednostkę.
        }
    } else if (number >= 1000) {
        ++key;
        number = number / 1000; // Dzieli liczbę przez tysiąc, zwiększając jednostkę.
    }

    decial = key != 0 && number != 0 && number < 100; // Określa, czy używać miejsc po przecinku dla liczb mniejszych niż 100 z sufiksem.
    return NumberGetHumanReadable(negate * number, decial) + (key !== 0 ? '&nbsp;' + unit[key] : ''); // Formatuje liczbę i dodaje sufiks jednostki (jeśli istnieje).
}

function removeE(Number) {
    // Usuwa notację wykładniczą z liczby.
    Number = String(Number); // Konwertuje liczbę na ciąg znaków.
    if (Number.search(/e\+/) == -1)
        return Number; // Jeśli nie zawiera notacji wykładniczej, zwraca bez zmian.
    var e = parseInt(Number.replace(/\S+.?e\+/g, '')); // Wyodrębnia wykładnik.
    if (isNaN(e) || e == 0)
        return Number; // Jeśli wykładnik nie jest liczbą lub wynosi 0, zwraca bez zmian.
    else if ($.browser.webkit || $.browser.msie)
        return parseFloat(Number).toPrecision(Math.min(e + 1, 21)); // Dla WebKit i IE używa toPrecision z ograniczeniem do 21 cyfr.
    else
        return parseFloat(Number).toPrecision(e + 1); // Dla innych przeglądarek używa toPrecision z wykładnikiem + 1.
}

function getFormatedDate(timestamp, format) {
    // Formatuje znacznik czasu (timestamp) do podanego formatu daty i czasu.
    var currTime = new Date(); // Tworzy nowy obiekt Date.
    currTime.setTime(timestamp + (ServerTimezoneOffset * 1000)); // Ustawia czas na podstawie znacznika czasu i przesunięcia strefy czasowej serwera.
    str = format; // Kopiuje format.
    str = str.replace('[d]', dezInt(currTime.getDate(), 2)); // Dzień miesiąca (z wiodącym zerem).
    str = str.replace('[D]', days[currTime.getDay()]); // Skrócona nazwa dnia tygodnia.
    str = str.replace('[m]', dezInt(currTime.getMonth() + 1, 2)); // Miesiąc (z wiodącym zerem).
    str = str.replace('[M]', months[currTime.getMonth()]); // Skrócona nazwa miesiąca.
    str = str.replace('[j]', parseInt(currTime.getDate())); // Dzień miesiąca (bez wiodącego zera).
    str = str.replace('[Y]', currTime.getFullYear()); // Pełny rok.
    str = str.replace('[y]', currTime.getFullYear().toString().substr(2, 4)); // Dwie ostatnie cyfry roku.
    str = str.replace('[G]', currTime.getHours()); // Godzina (24-godzinna, bez wiodącego zera).
    str = str.replace('[H]', dezInt(currTime.getHours(), 2)); // Godzina (24-godzinna, z wiodącym zerem).
    str = str.replace('[i]', dezInt(currTime.getMinutes(), 2)); // Minuty (z wiodącym zerem).
    str = str.replace('[s]', dezInt(currTime.getSeconds(), 2)); // Sekundy (z wiodącym zerem).
    return str; // Zwraca sformatowany ciąg znaków daty i czasu.
}
function dezInt(num, size, prefix) {
    // Dodaje wiodące zera lub inny prefiks do liczby.
    prefix = (prefix) ? prefix : "0"; // Domyślny prefiks to "0".
    var minus = (num < 0) ? "-" : "",
        result = (prefix == "0") ? minus : ""; // Dodaje znak minus na początku, jeśli prefiks to "0".
    num = Math.abs(parseInt(num, 10)); // Bierze wartość bezwzględną liczby i parsruje jako liczbę dziesiętną.
    size -= ("" + num).length; // Oblicza liczbę potrzebnych prefiksów.
    for (var i = 1; i <= size; i++) {
        result += "" + prefix; // Dodaje prefiksy.
    }
    result += ((prefix != "0") ? minus : "") + num; // Dodaje liczbę, uwzględniając znak minus, jeśli prefiks nie jest "0".
    return result; // Zwraca ciąg znaków z wiodącymi zerami lub prefiksem.
}

function getFormatedTime(time) {
    // Formatuje czas w sekundach do formatu HH:MM:SS.
    const hours = Math.floor(time / 3600); // Oblicza liczbę godzin.
    let timeleft = time % 3600; // Pozostałe sekundy po odjęciu godzin.
    const minutes = Math.floor(timeleft / 60); // Oblicza liczbę minut.
    timeleft = timeleft % 60; // Pozostałe sekundy po odjęciu minut.
    const seconds = timeleft; // Pozostałe sekundy.
    return dezInt(hours, 2) + ":" + dezInt(minutes, 2) + ":" + dezInt(seconds, 2); // Zwraca sformatowany czas z wiodącymi zerami.
}

function GetRestTimeFormat(Secs) {
    // Formatuje czas w sekundach do formatu HH:MM:SS.
    let s = Secs; // Kopia sekund.
    let m = 0; // Inicjalizacja minut.
    let h = 0; // Inicjalizacja godzin.
    if (s > 59) {
        m = Math.floor(s / 60); // Oblicza minuty.
        s = s - m * 60; // Aktualizuje sekundy.
    }
    if (m > 59) {
        h = Math.floor(m / 60); // Oblicza godziny.
        m = m - h * 60; // Aktualizuje minuty.
    }
    return dezInt(h, 2) + ':' + dezInt(m, 2) + ":" + dezInt(s, 2); // Zwraca sformatowany czas z wiodącymi zerami.
}

function OpenPopup(target_url, win_name, width, height) {
    // Otwiera nowe okno popup o podanych parametrach.
    const new_win = window.open(target_url + '&ajax=1', win_name, 'scrollbars=yes,statusbar=no,toolbar=no,location=no,directories=no,resizable=no,menubar=no,width=' + width + ',height=' + height + ',screenX=' + ((screen.width - width) / 2) + ",screenY=" + ((screen.height - height) / 2) + ",top=" + ((screen.height - height) / 2) + ",left=" + ((screen.width - width) / 2));
    new_win.focus(); // Przenosi fokus na nowo otwarte okno.
}

function DestroyMissiles() {
    // Wysyła żądanie AJAX GET w formacie JSON w celu zniszczenia rakiet.
    $.getJSON('?page=information&mode=destroyMissiles&' + $('.missile').serialize(), function(data) {
        // Aktualizuje wyświetlaną liczbę rakiet przechwytujących i międzyplanetarnych.
        $('#missile_502').text(NumberGetHumanReadable(data[0]));
        $('#missile_503').text(NumberGetHumanReadable(data[1]));
        $('.missile').val(''); // Czyści wartości pól input dla rakiet.
    });
}

function handleErr(errMessage, url, line)
{
    // Funkcja obsługi błędów JavaScript, wyświetla alert z informacjami o błędzie.
    const error = `There is an error at this page.\nError: ${errMessage}\nURL: ${url}\nLine: ${line}\n\nClick OK to continue viewing this page,\n`;
    alert(error);
    if (typeof console === "object")
        console.log(error);

    return true;
}

const Dialog	= {
    // Obiekt zawierający funkcje do otwierania różnych okien dialogowych (prawdopodobnie za pomocą Fancybox).
    info: function(ID){
        // Otwiera okno informacji o danym ID.
        return Dialog.open('game.php?page=information&id='+ID, 590, (ID > 600 && ID < 800) ? 210 : ((ID > 100 && ID < 200) ? 300 : 620));
    },

    alert: function(msg, callback){
        // Wyświetla prosty alert i opcjonalnie wywołuje funkcję callback.
        alert(msg);
        if(typeof callback === "function") {
            callback();
        }
    },

    PM: function(ID, Subject, Message) {
        // Otwiera okno pisania prywatnej wiadomości do gracza o danym ID.
        if(typeof Subject !== 'string')
            Subject	= '';

        return Dialog.open('game.php?page=messages&mode=write&id='+ID+'&subject='+encodeURIComponent(Subject)+'&message='+encodeURIComponent(Subject), 650, 350);
    },

    Playercard: function(ID) {
        // Otwiera okno karty gracza o danym ID, jeśli jest aktywne.
        return isPlayerCardActive && Dialog.open('game.php?page=playerCard&id='+ID, 650, 600);
    },

    Buddy: function(ID) {
        // Otwiera okno wysyłania zaproszenia do listy znajomych do gracza o danym ID.
        return Dialog.open('game.php?page=buddyList&mode=request&id='+ID, 650, 300);
    },

    PlanetAction: function() {
        // Otwiera okno akcji planetarnych.
        return Dialog.open('game.php?page=overview&mode=actions', 400, 180);
    },

    AllianceChat: function() {
        // Otwiera okno czatu sojuszu w popupie.
        return OpenPopup('game.php?page=chat&action=alliance', "alliance_chat", 960, 900);
    },

    open: function(url, width, height) {
        // Otwiera okno dialogowe za pomocą Fancybox.
        $.fancybox({
            width: width,
            padding: 0,
            height: height,
            type: 'iframe',
            href: url
        });

        return false;
    }
}

function NotifyBox(text) {
    // Wyświetla krótkie powiadomienie tekstowe na środku ekranu.
    const tip = $('#tooltip'); // Pobiera element o id 'tooltip' (prawdopodobnie div do wyświetlania powiadomień).
    tip.html(text).addClass('notify').css({ // Ustawia tekst powiadomienia, dodaje klasę 'notify' i ustawia jego pozycję.
        left: (($(window).width() - $('#leftmenu').width()) / 2 - tip.outerWidth() / 2) + $('#leftmenu').width(), // Wyśrodkowuje powiadomienie po prawej stronie menu.
    }).show(); // Wyświetla powiadomienie.
    window.setTimeout(function() { // Ustawia opóźnione ukrycie powiadomienia.
        tip.fadeOut(1000, function() { // Animuje wygaszanie powiadomienia w ciągu 1 sekundy.
            tip.removeClass('notify'); // Po zakończeniu animacji usuwa klasę 'notify'.
        });
    }, 500); // Opóźnienie przed rozpoczęciem wygaszania (0.5 sekundy).
}


function UhrzeitAnzeigen() {
    // Aktualizuje wyświetlany czas serwera na stronie.
    $(".servertime").text(getFormatedDate(serverTime.getTime(), tdformat)); // Pobiera sformatowany czas serwera i ustawia go jako tekst elementu z klasą 'servertime'.
}


$.widget("custom.catcomplete", $.ui.autocomplete, {
    // Rozszerza widget autocomplete jQuery UI, aby grupować sugestie według kategorii.
    _renderMenu: function(ul, items) {
        const self = this; // Zachowuje kontekst 'this'.
        let currentCategory = ""; // Zmienna do śledzenia aktualnej kategorii.
        $.each(items, function(index, item) { // Iteruje po elementach sugestii.
            if (item.category != currentCategory) { // Jeśli kategoria elementu różni się od aktualnej kategorii.
                ul.append("<li class='ui-autocomplete-category'>" + item.category + "</li>"); // Dodaje nagłówek kategorii do listy sugestii.
                currentCategory = item.category; // Aktualizuje aktualną kategorię.
            }
            self._renderItem(ul, item); // Wywołuje domyślną metodę renderowania elementu sugestii.
        });
    }
});

$(function() {
    // Funkcja wykonywana po załadowaniu całego dokumentu HTML (DOM).
    $('#drop-admin').on('click', function() { // Obsługuje kliknięcie elementu z id 'drop-admin' (prawdopodobnie przycisk wylogowania admina).
        $.get('admin.php?page=logout', function() { // Wysyła żądanie AJAX GET w celu wylogowania admina.
            $('.globalWarning').animate({ // Animuje ukrycie elementu z klasą 'globalWarning' (prawdopodobnie pasek ostrzeżeń).
                'height': 0,
                'padding': 0,
                'opacity': 0
            }, function() {
                $(this).hide(); // Po zakończeniu animacji ukrywa element.
            });
        });
    });


    window.setInterval(function() { // Uruchamia interwał czasowy do cyklicznej aktualizacji odliczań.
        $('.countdown').each(function() { // Dla każdego elementu z klasą 'countdown'.
            const s = $(this).data('time') - (serverTime.getTime() - startTime) / 1000; // Oblicza czas pozostały.
            if (s <= 0) { // Jeśli czas dobiegł końca.
                $(this).text('-'); // Ustawia tekst na '-'.
            } else { // Jeśli czas jeszcze nie dobiegł końca.
                $(this).text(GetRestTimeFormat(s)); // Wyświetla sformatowany czas pozostały.
            }
        });
    }, 1000); // Interwał wykonywany co 1 sekundę.

    $('#planetSelector').on('change', function() { // Obsługuje zmianę wyboru w elemencie z id 'planetSelector' (prawdopodobnie lista rozwijana planet).
        document.location = '?' + queryString + '&cp=' + $(this).val(); // Przekierowuje przeglądarkę na nową planetę, zachowując inne parametry URL.
    });

    UhrzeitAnzeigen(); // Wywołuje funkcję do wyświetlenia czasu serwera przy załadowaniu strony.
    setInterval(UhrzeitAnzeigen, 1000); // Uruchamia interwał do cyklicznej aktualizacji czasu serwera co 1 sekundę.

    $("button#create_new_alliance_rank").click(function() { // Obsługuje kliknięcie przycisku z id 'create_new_alliance_rank' (prawdopodobnie tworzenie nowej rangi sojuszu).
        $("div#new_alliance_rank").dialog({ // Otwiera okno dialogowe jQuery UI dla tworzenia nowej rangi.
            draggable: false, // Wyłącza możliwość przeciągania okna.
            resizable: false, // Wyłącza możliwość zmiany rozmiaru okna.
            modal: true, // Ustawia okno jako modalne (blokujące interakcję z tłem).
            width: 760 // Ustawia szerokość okna na 760 pikseli.
        });

        return false; // Zapobiega domyślnej akcji przycisku.
    });
});