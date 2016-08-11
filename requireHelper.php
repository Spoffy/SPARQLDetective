<?php

define("__ROOT__", __dir__);

spl_autoload_register(function($className) {
    if($className === "Config") {
        require_once(__ROOT__ . "/config.php");
    }
});
