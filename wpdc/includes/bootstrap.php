<?php

if($_ENV["WPDC_ENV"] == "test") {
    $path = '/usr/lib/pear';
    set_include_path(get_include_path() . PATH_SEPARATOR . $path);
}