<?php
/**
 * Example, how inspect the session status..
 */
session_start();
$path = &$_REQUEST['path'];
PHPObjectBrowser::dumpVar($_SESSION, $path); die();
die();
