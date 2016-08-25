<?php
/**
 * Example, how to inspect a session status..
 */
session_start();
PHPObjectBrowser::inspect($_SESSION);
die();
