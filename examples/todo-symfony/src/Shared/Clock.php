<?php
namespace App\Shared;

/** The current time, as a contract. Features receive a Clock and pass
 *  the time into entities; entities never read a clock themselves,
 *  so tests can say exactly what "now" means. */
interface Clock
{
    public function now(): \DateTimeImmutable;
}
