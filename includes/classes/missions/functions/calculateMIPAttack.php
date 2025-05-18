<?php

/**
 * Oblicza straty w obronie w wyniku ataku rakietami międzyplanetarnymi (MIP).
 *
 * @param int   $TargetDefTech    Poziom technologii obronnych atakowanej planety.
 * @param int   $OwnerAttTech     Poziom technologii ofensywnych atakującego gracza.
 * @param int   $missiles         Liczba wysłanych rakiet międzyplanetarnych.
 * @param array $targetDefensive  Tablica asocjacyjna obrony na celu (element_id => ilość).
 * @param int   $firstTarget      ID pierwszego (priorytetowego) celu do zniszczenia.
 * @param int   $defenseMissles   Liczba rakiet przeciwrakietowych przechwyconych.
 *
 * @return array Tablica asocjacyjna zniszczonej obrony (element_id => ilość zniszczonych).
 * Zwraca pustą tablicę, jeśli żadna obrona nie została zniszczona.
 *
 * @throws Exception Wyrzuca wyjątek w przypadku nieznanego błędu (element ID 0).
 */
function calculateMIPAttack($TargetDefTech, $OwnerAttTech, $missiles, $targetDefensive, $firstTarget, $defenseMissles): array
{
    global $pricelist, $CombatCaps; // Dostęp do globalnych tablic cennika i charakterystyk bojowych.

    $destroyShips = []; // Inicjalizuj tablicę zniszczonych jednostek obronnych.
    $countMissles = $missiles - $defenseMissles; // Oblicz liczbę rakiet, które dotarły do celu.

    // Jeśli żadna rakieta nie dotarła, zwróć pustą tablicę.
    if ($countMissles == 0) {
        return $destroyShips;
    }

    // Oblicz całkowitą siłę ataku rakiet międzyplanetarnych.
    $totalAttack = $countMissles * $CombatCaps[503]['attack'] * (1 + 0.1 * $OwnerAttTech);

    // Wybierz cel priorytetowy, jeśli istnieje.
    if (isset($targetDefensive[$firstTarget])) {
        $firstTargetData = [$firstTarget => $targetDefensive[$firstTarget]];
        unset($targetDefensive[$firstTarget]); // Usuń cel priorytetowy z głównej tablicy obrony.
        $targetDefensive = $firstTargetData + $targetDefensive; // Umieść cel priorytetowy na początku tablicy.
    }

    // Iteruj po jednostkach obronnych na celu.
    foreach ($targetDefensive as $element => $count) {
        // Wykryj nieznany błąd (element ID 0) i wyrzuć wyjątek.
        if ($element == 0) {
            throw new Exception("Nieznany błąd. Proszę zgłosić ten błąd na tracker.2moons.cc. Informacje debugowania:<br><br>" . serialize([$TargetDefTech, $OwnerAttTech, $missiles, $targetDefensive, $firstTarget, $defenseMissles]));
        }
        // Oblicz punkty struktury jednostki obronnej.
        $elementStructurePoints = ($pricelist[$element]['cost'][901] + $pricelist[$element]['cost'][902]) * (1 + 0.1 * $TargetDefTech) / 10;
        // Oblicz liczbę jednostek obronnych, które można zniszczyć.
        $destroyCount = floor($totalAttack / $elementStructurePoints);
        // Nie zniszcz więcej jednostek niż jest dostępne.
        $destroyCount = min($destroyCount, $count);
        // Zmniejsz całkowitą siłę ataku o zniszczone jednostki.
        $totalAttack -= $destroyCount * $elementStructurePoints;

        // Zapisz liczbę zniszczonych jednostek w tablicy.
        $destroyShips[$element] = $destroyCount;
        // Jeśli całkowita siła ataku spadła do zera lub poniżej, nie ma potrzeby dalszego obliczania.
        if ($totalAttack <= 0) {
            return $destroyShips;
        }
    }

    return $destroyShips; // Zwróć tablicę zniszczonych jednostek obronnych.
}