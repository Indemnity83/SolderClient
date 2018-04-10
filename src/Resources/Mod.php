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

class Mod
{
    public $name;
    public $version;
    public $md5;
    public $pretty_name;
    public $author;
    public $description;
    public $link;

    public function __construct($properties)
    {
        foreach ($properties as $key => $val) {
            $this->{$key} = $val;
        }
    }
}
