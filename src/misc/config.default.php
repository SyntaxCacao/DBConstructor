<?php

$cfg["baseurl"] = "http://127.0.0.1/dbc/";

$cfg["cookies"]["path"] = "/dbc";
$cfg["cookies"]["prefix"] = "dbc-";

// set this to true to show error messages
$cfg["development"] = false;

// use this key to perform a database scheme migration: /migrate?key=TISN942
$cfg["migratekey"] = "TISN942";

$cfg["mysql"]["database"] = "dbconstructor";
// "127.0.0.1" can be significantly faster than "localhost"
$cfg["mysql"]["hostname"] = "127.0.0.1";
// empty string indicates no password
$cfg["mysql"]["password"] = "";
$cfg["mysql"]["username"] = "root";
