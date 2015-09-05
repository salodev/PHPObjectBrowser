# PHPObjectBrowser
Inspect complex and reciprocal referred structure objects of a easy and visual way.

Example code of simple use.
<?php
/**
 * Example, how inspect the session status..
 */
session_start();
$path = &$_REQUEST['path'];
PHPObjectBrowser::dumpVar($_SESSION, $path); die();
die();
