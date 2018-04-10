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

class Modpack
{
    public $name;
    public $display_name;
    public $url;
    public $icon;
    public $icon_md5;
    public $logo;
    public $logo_md5;
    public $background;
    public $background_md5;
    public $recommended;
    public $latest;
    public $builds = [];

    public function __construct($properties)
    {
        foreach ($properties as $key => $val) {
            $this->{$key} = $val;
        }
    }
}
