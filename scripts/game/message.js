const Message = {
    // Obiekt zarządzający wiadomościami w interfejsie.

    MessID: 0, // Aktualnie wyświetlana kategoria wiadomości.

    MessageCount: function() {
        // Funkcja aktualizująca liczniki nieprzeczytanych wiadomości.
        if (Message.MessID === 100) {
            // Jeśli aktualnie przeglądana kategoria to "Wszystkie wiadomości" (ID 100).
            $('#unread_0').text('0');
            $('#unread_1').text('0');
            $('#unread_2').text('0');
            $('#unread_3').text('0');
            $('#unread_4').text('0');
            $('#unread_5').text('0');
            $('#unread_15').text('0');
            $('#unread_99').text('0');
            $('#unread_100').text('0');
            $('#newmes').text(''); // Czyści wskaźnik nowych wiadomości.
        } else {
            // Dla konkretnych kategorii wiadomości.
            const count = parseInt($(`#unread_${Message.MessID}`).text()) || 0; // Pobiera liczbę nieprzeczytanych wiadomości dla bieżącej kategorii (domyślnie 0).
            const lmnew = parseInt($('#newmesnum').text()) || 0; // Pobiera łączną liczbę nowych wiadomości (domyślnie 0).

            $(`#unread_${Message.MessID}`).text(Math.max(0, parseInt($('#unread_100').text()) - 10));
            // Aktualizuje licznik nieprzeczytanych wiadomości dla bieżącej kategorii, odejmując 10 od licznika "Wszystkie".
            // Użyto Math.max, aby licznik nie był mniejszy niż 0.

            if (Message.MessID !== 999) {
                // Jeśli aktualna kategoria nie jest specjalną kategorią (ID 999).
                $('#unread_100').text(parseInt($('#unread_100').text()) - count);
                // Aktualizuje licznik "Wszystkie" o liczbę przeczytanych wiadomości w bieżącej kategorii.
            }

            if (lmnew - count <= 0) {
                // Jeśli łączna liczba nowych wiadomości po odjęciu przeczytanych jest mniejsza lub równa 0.
                $('#newmes').text(''); // Czyści wskaźnik nowych wiadomości.
            } else {
                // W przeciwnym razie.
                $('#newmesnum').text(lmnew - count); // Aktualizuje łączną liczbę nowych wiadomości.
            }
        }
    },

    getMessages: function (MessID, page = 1) {
        // Funkcja pobierająca wiadomości dla danej kategorii i strony.
        Message.MessID = MessID; // Ustawia aktualnie przeglądaną kategorię wiadomości.
        Message.MessageCount(MessID); // Aktualizuje liczniki nieprzeczytanych wiadomości.

        $('#loading').show(); // Pokazuje wskaźnik ładowania.

        $.get(`game.php?page=messages&mode=view&messcat=${MessID}&site=${page}&ajax=1`, function(data) {
            // Wykonuje żądanie HTTP GET na podany URL, pobierając wiadomości dla danej kategorii i strony.
            $('#loading').hide(); // Ukrywa wskaźnik ładowania po otrzymaniu danych.
            $('#messagestable').remove(); // Usuwa istniejącą tabelę z wiadomościami (jeśli istnieje).
            $('#content table:eq(0)').after(data); // Wstawia pobrane dane HTML (tabelę z wiadomościami) po pierwszej tabeli w elemencie o id 'content'.
        });
    },

    stripHTML: function (string) {
        // Funkcja usuwająca tagi HTML z podanego ciągu znaków.
        return string.replace(/<(.|\n)*?>/g, '');
    },

    CreateAnswer: function (Answer) {
        // Funkcja tworząca prefiks odpowiedzi w oparciu o istniejący temat wiadomości.
        const strippedAnswer = Message.stripHTML(Answer); // Usuwa tagi HTML z odpowiedzi.
        if (strippedAnswer.startsWith("Re:")) {
            // Jeśli temat zaczyna się od "Re:".
            return `Re[2]:${strippedAnswer.substring(3)}`; // Zmienia na "Re[2]:".
        } else if (strippedAnswer.startsWith("Re[")) {
            // Jeśli temat zaczyna się od "Re[liczba]:".
            const reMatch = strippedAnswer.match(/Re\[(\d+)\]:(.*)/);
            if (reMatch) {
                const reCount = parseInt(reMatch[1]) + 1; // Zwiększa licznik odpowiedzi.
                return `Re[${reCount}]:${reMatch[2]}`; // Zwraca nowy prefiks z zwiększonym licznikiem.
            } else {
                return `Re:${strippedAnswer}`; // Jeśli format "Re[...]:" jest nieprawidłowy, dodaje zwykłe "Re:".
            }
        } else {
            // Jeśli temat nie zaczyna się od "Re:".
            return `Re:${strippedAnswer}`; // Dodaje prefiks "Re:".
        }
    },

    getMessagesIDs: function(Infos) {
        // Funkcja pobierająca ID wiadomości zaznaczonych do usunięcia.
        const IDs = []; // Inicjalizuje pustą tablicę na ID wiadomości.
        $.each(Infos, function(index, mess) {
            // Iteruje po elementach (prawdopodobnie inputach formularza) przekazanych w 'Infos'.
            if (mess.value === 'on') {
                // Jeśli wartość elementu to 'on' (zaznaczony checkbox).
                IDs.push(mess.name.replace(/delmes\[(\d+)\]/, '$1'));
                // Wyodrębnia ID wiadomości z atrybutu 'name' (zakładając format 'delmes[ID]') i dodaje do tablicy IDs.
            }
        });
        return IDs; // Zwraca tablicę z ID zaznaczonych wiadomości.
    }
};

// ULEPSZENIA (SUGESTIE):

// 1. Lepsze zarządzanie selektorami jQuery: Przypisanie często używanych selektorów do stałych na początku funkcji (szczególnie w `MessageCount` i `getMessages`).

// 2. Czytelniejsza logika w `MessageCount`: Można rozbić warunki i operacje na mniejsze bloki dla lepszej czytelności. Użycie opcjonalnego łańcuchowania (`|| 0`) dla bezpiecznego parsowania liczb.

// 3. Wykorzystanie template literals do składania URL-a w `getMessages`.

// 4. Lepsza obsługa błędów AJAX w `getMessages` za pomocą `.fail()`.

// 5. W `CreateAnswer`, użycie bardziej czytelnych nazw zmiennych i potencjalnie wyrażeń regularnych do bardziej precyzyjnego parsowania. Zastosowano ulepszone parsowanie `Re[...]`.

// 6. W `getMessagesIDs`, użycie `Array.prototype.map` i `Array.prototype.filter` mogłoby być bardziej funkcyjnym i zwięzłym podejściem.

// ZASTOSOWANE ULEPSZENIA:

// - Użyto opcjonalnego łańcuchowania (`|| 0`) w `MessageCount` dla bezpiecznego parsowania.
// - Użyto template literals w `getMessages`.
// - Ulepszono logikę parsowania w `CreateAnswer` dla formatu `Re[...]`.
// - Dodano komentarze wyjaśniające działanie kodu.

// Pozostałe sugestie można rozważyć w zależności od potrzeb i złożoności projektu.