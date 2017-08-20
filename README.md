# PHPObjectBrowser
Inspect complex and reciprocal referred structure objects of a easy and visual way.

It was moved into my [salodev](https://github.com/salojc2006/salodev) repository
See source code [here](https://github.com/salojc2006/salodev/blob/master/src/Debug/ObjectInspector.php)

Example code of simple use.
```
<?php
use salodev\Debug\ObjectInspector;
session_start();
ObjectInspector::inspect($_SESSION);
die();
```
