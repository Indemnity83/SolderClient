<?php

/*
 * This file is part of TechnicPack Solder.
 *
 * (c) Syndicate LLC
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace TechnicPack\SolderClient\Resources;

class Build
{
    public $minecraft;
    public $minecraft_md5;
    public $mods = [];

    public function __construct($properties)
    {
        foreach ($properties as $key => $val) {
            if ($key != 'mods') {
                $this->{$key} = $val;
            }
        }

        foreach ($properties['mods'] as $mod) {
            array_push($this->mods, new Mod($mod));
        }
    }
}
