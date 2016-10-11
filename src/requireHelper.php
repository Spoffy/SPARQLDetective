<?php

spl_autoload_register(function($className) {
    if($className === "Config") {
        require_once(__ROOT__ . "/etc/config.php");
    }
});
