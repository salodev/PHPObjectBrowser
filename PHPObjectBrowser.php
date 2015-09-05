<?php

class PHPObjectBrowser {

	static private $printType = 'html';
	
	static public function getShowValue($e) {
		if (is_object($e)) {
			return get_class($e);
		}
		if (is_array($e)) {
			return 'array';
		}
		return $e;
	}

	static public function colorContent($content, $color) {
		if (self::$printType=='html') {
			return self::colorContentHTML($content, $color);
		}
	}

	static public function colorContentHTML($content, $color, $fontStyle = false) {
		return "<span style=\"color:{$color}\">{$content}</span>";
	}

	static public function getBreakLine() {
		if (self::$printType=='html') {
			return '<br />';
		}
	}

	static public function getTab() {
		if (self::$printType=='html') {
			return '<span>&nbsp;&nbsp;</span>';
		}
	}

	static public function getPathLink($path, $matchPath, $prevPath) {
		if (self::$printType=='html') {
			return self::getPathLinkHTML($path, $matchPath, $prevPath);
		}
	}

	static public function getPathLinkHTML($path, $matchPath, $prevPath) {
		if (strpos($matchPath, $path)===0) {
			$encodedPath = urlencode($prevPath);
			return self::colorContent("<a id=\"{$path}\" style=\"color:lightgray;text-decoration:none;\" href=\"?path={$encodedPath}#---{$path}\">[-]</a>", 'lightgray');
		} else {
			$encodedPath = urlencode($path);
			return self::colorContent("<a id=\"{$path}\" style=\"color:lightgray;text-decoration:none;\" href=\"?path={$encodedPath}#---{$path}\">[+]</a>", 'lightgray');
		}
	}

	static private function _openPrintArea() {
		if (self::$printType=='html') {
			return self::_openPrintAreaHTML();
		}
	}

	static private function _closePrintArea() {
		if (self::$printType=='html') {
			return self::_closePrintAreaHTML();
		}
	}

	static private function _openPrintAreaHTML() {
		return '<span style="font-family:Monospace">';
	}

	static private function _closePrintAreaHTML() {
		return '</span>';
	}

	static public function getPrintableValueType($v) {
		$type = gettype($v);
		$color = 'blue';
		$ret = '';
		switch ($type) {
			case 'string':
				$color = 'red';
				$ret = "\"$v\"";
				break;
			case 'array':
				$count = count($v);
				$ret = "array({$count})";
				break;
			case 'integer':
				$color = 'violet';
				$ret = "{$v}";
				break;
			case 'boolean':
				$color = 'violet';
				$ret = $v ? 'TRUE' : 'FALSE';
				break;
			case 'object':
				$ret = get_class($v) . ' { ... }';
				break;
			case 'NULL':
				$color = 'violet';
				$ret = 'NULL';
				break;
			case 'double':
				$ret = "{$v}";
				break;
			case 'resource':
				$ret = "#{$v}";
				break;
			default:
				$ret = "Unknown {$v}";
				break;
		}

		return self::colorContent($ret, $color);
	}

	static public function dumpVar($element, $matchPath = null) {
		echo self::_openPrintArea();
		self::_dump($element, null, $matchPath);
		echo self::_closePrintArea();
	}

	static private function _dump($element, $prevPath = null, $matchPath = null, $level = 0, $showElementType = true) {
		$br = self::getBreakLine();
		$tab = self::getTab();
		$tabs = str_repeat($tab, $level);
		$path = $prevPath;
		$addLevel = 2;
		// echo "\n{$tabs}ELEMENT PATH ($path): ";
		if (is_array($element)) {
			if ($showElementType) {
				echo "{$br}{$tabs}" . self::colorContent("array (", 'blue');
			}
			foreach($element as $key => $value) {
				$newPath = $path . "/$key";
				$showValue = self::getShowValue($value);
				$pKey = self::getPrintableValueType($key);
				$pPath = self::getPathLink($newPath, $matchPath, $prevPath);
				$pValue = self::getPrintableValueType($value);
				echo "{$br}{$tabs}{$tab}[{$pKey}] {$pValue} {$pPath}";
				if ($matchPath && strpos($matchPath, $newPath)===0) {
					self::_dump($value, $newPath, $matchPath, $level+$addLevel, false);
				}
			}
			if ($showElementType) {
				echo "{$br}{$tabs}".self::colorContent(')', 'blue');
			}
		} else {
			if (is_object($element)) {
				if ($showElementType) {
					echo "{$br}{$tabs}".self::colorContent(get_class($element) . " {", 'blue');
				}
				$path = $prevPath; //$prevPath . '/' . get_class($element);
				$r = new ReflectionClass($element);
				$constants = $r->getConstants();
				foreach($constants as $name => $value) {
					echo "{$br}{$tabs}{$tab}const " . self::colorContent($name, 'limegreen') . ' ' . self::getPrintableValueType($value);
				}
				$properties = $r->getProperties(ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PROTECTED | ReflectionProperty::IS_PRIVATE);
				foreach($properties as $property) {
					$visibility = $property->isPrivate() ? 'private' : ($property->isProtected() ? 'protected' : 'public');
					$static = $property->isStatic() ? 'static ' : '';
					if (!$property->isPublic()) {
						$property->setAccessible(true);
					}
					$value = $property->getValue($element);
					$pValue = self::getPrintableValueType($value);
					$pName  = self::colorContent('$' . $property->name, 'limegreen');
					$newPath = $path . '/' . $property->name;
					$pPath = self::getPathLink($newPath, $matchPath, $prevPath);
					echo "{$br}{$tabs}{$tab}{$static}{$visibility} {$pName} {$pValue} {$pPath}";
					if ($matchPath && strpos($matchPath, $newPath)===0) {
						self::_dump($value, $newPath, $matchPath, $level+$addLevel, false);
					}
				}
				$methods = $r->getMethods();
				foreach($methods as $method) {
					$visibility = $method->isPublic() ? 'public' : ($method->isPrivate() ? 'private' : 'protected');
					echo "{$br}{$tabs}{$tab}{$visibility} " . self::colorContent("{$method->name}(", 'blue');
					$tmpParameters = array();
					foreach($method->getParameters() as $parameter) {
						$tmpParameters[] = self::colorContent("\${$parameter->name}", 'limegreen');
					}
					echo implode(', ', $tmpParameters);
					echo self::colorContent(")", 'blue');
				}
				if ($showElementType) {
					echo "{$br}{$tabs}".self::colorContent('}', 'blue');
				}
			} else {
				echo "{$br}{$tabs}{$element}";
			}
		}
	}
}
