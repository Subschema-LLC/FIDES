<?php
namespace App\Shared;

/** Where each setting's value came from, for the `todo:config`
 *  command: key => [effective value, layer]. A contract no entity
 *  owns; the config loader fulfills it, bound in config/wiring.yaml. */
interface SettingsSources
{
    /** @return array<string, array{0: mixed, 1: string}> */
    public function sources(string $owner): array;
}
