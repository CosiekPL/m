<?php

declare(strict_types=1);

/**
 * Interfejs dla wszystkich klas misji flot
 * Każda misja musi implementować te trzy metody
 */
interface Mission 
{
    /**
     * Wykonuje akcję gdy flota dociera do celu
     * Ta metoda jest wywoływana, gdy flota osiąga swój cel
     */
    public function TargetEvent(): void;

    /**
     * Wykonuje akcję gdy flota kończy swój pobyt w miejscu docelowym
     * Ta metoda jest wywoływana po zakończeniu czasu postoju floty
     */
    public function EndStayEvent(): void;

    /**
     * Wykonuje akcję gdy flota wraca do miejsca początkowego
     * Ta metoda jest wywoływana, gdy flota wraca do punktu startowego
     */
    public function ReturnEvent(): void;
}