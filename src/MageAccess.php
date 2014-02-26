<?php
namespace Magery {
    /**
     * MageAccess trait
     * @package Magery
     */
    trait MageAccess {
        use Object, Traits\Read, Traits\Write, Traits\Exists, Traits\Remove {
            Traits\Read::get as offsetGet;
            Traits\Write::write as offsetSet;
            Traits\Exists::exists as offsetExists;
            Traits\Remove::remove as offsetUnset;
        }
    }
}
