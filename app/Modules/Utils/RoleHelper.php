<?php

namespace App\Modules\Utils;

class RoleHelper
{
    /**
     * Get all roles
     *
     * @return array
     */
    public static function getRoles()
    {
        return [
            "sadm",
            "adm",
            "ret",
            "dd",
            "di",
            "md",
            "asm",
            "zsm",
        ];
    }
}
