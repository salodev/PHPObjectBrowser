<?php
/**
 * Example, how inspect the session status..
 */
session_start();
PHPObjectBrowser::inspect($_SESSION);
die();
