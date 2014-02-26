<?php
namespace Magery {
    /**
     * Mage trait
     * @package Magery
     */
    trait Mage {
        use Object, Traits\Read, Traits\Write, Traits\Exists, Traits\Remove, Traits\Call;
    }
}
