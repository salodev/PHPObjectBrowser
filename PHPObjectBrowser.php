<?php
/**
 * PHPObjectBrowser is a tool for browse complex objects structure.
 * The most usual case is the parent child relationship, where we have both
 * objects interrelated. Example:
 * $parent = new Parent();
 * $parent->child = new Child();
 * $parent->child->parent = $parent;
 * When we want to inspect $parent instance using print_r, php generates an
 * infinite string.
 *
 * To solve this problem, PHPObjectBrowser dont inspect recursivelly the object
 * tree. Instead, just do that the user request using something called 'path',
 * a simple like xPath string. With this path we tell to PHPObjectBrowser what
 * objects we want to see.
 *
 * @author salojc2006@gmail.com
 * @version 0.2
 * @todo - Fix url crations and make it better, more intelligent, shorting length of the querystring.
 *       - Output for linux console
 *       - Add callback function to render special object types. E. g. Collections object may rendered
 *         like Collection (x) where x is number of child objects such array renders.
 *       - Make better autodetection of web or console output mode.
 *       - Write english well. (sorry)
 * 
 * Example:
 * session_start();
 * PHPObjectBrowser::inspect($_SESSION);
 * die();
 * 
 */
class PHPObjectBrowser {

	static private $_printType = 'html';
	static private $_urlParameterName = '__path';

	static public function SetURLParameterName($name) {
		self::$_urlParameterName = $name;
	}
	
	static public function getShowValue($e) {
		if (is_object($e)) {
			return get_class($e);
		}
		if (is_array($e)) {
			return 'array';
		}
		return $e;
	}

	static public function colorContent($content, $color, $style = null, $tooltip = null) {
		if (self::$_printType=='html') {
			return self::colorContentHTML($content, $color, $style, $tooltip);
		}
	}

	static public function colorContentHTML($content, $color, $style = null, $tooltip = null) {
		$italic = $style == 'italic' ? 'font-style:italic;': '';
		$tooltip = $tooltip ? " title=\"{$tooltip}\"" : '';

		return "<span style=\"color:{$color};{$italic}\"{$tooltip}>{$content}</span>";
	}

	static public function getBreakLine() {
		if (self::$_printType=='html') {
			return '<br />';
		}
	}

	static public function getTab() {
		if (self::$_printType=='html') {
			return '&nbsp;&nbsp;';
		}
	}

	static public function getPathLink($path, $matchPath, $prevPath) {
		if (self::$_printType=='html') {
			return self::getPathLinkHTML($path, $matchPath, $prevPath);
		}
	}

	static private function _getLink($path) {
		
	}

	static public function getPathLinkHTML($path, $matchPath, $prevPath) {
		$urlParameterName = self::$_urlParameterName;
		$urlHandler = new URLHandler();
		if (self::_matchPath($matchPath, $path)) {
			$plusMinus = '[-]';
			$encodedPath = urlencode($path);
			$urlHandler->parameters["{$urlParameterName}[$encodedPath]"] = 0;
		} else {
			$plusMinus = '[+]';
			$encodedPath = urlencode($path);
			$urlHandler->parameters["{$urlParameterName}[$encodedPath]"] = 1;
		}
		//$urlHandler->parameters["{$urlParameterName}[$encodedPath]"] = 1;
		
		$link = $urlHandler->getURI();
		return self::colorContent("<a id=\"{$path}\" style=\"color:lightgray;text-decoration:none;\" href=\"{$link}#---{$path}\">{$plusMinus}</a>", 'lightgray');
	}

	static private function _openPrintArea() {
		if (self::$_printType=='html') {
			return self::_openPrintAreaHTML();
		}
	}

	static private function _closePrintArea() {
		if (self::$_printType=='html') {
			return self::_closePrintAreaHTML();
		}
	}

	static private function _openPrintAreaHTML() {
		return "<span class=\"PHPObjectBrowserPrintArea\" style=\"font-family:Monospace\">";
		return "
		<style type=\"text/css\">
			.PHPObjectBrowserPrintArea blockquote {margin:0; padding: 0 0 0 10px;}
		</style>
		<span class=\"PHPObjectBrowserPrintArea\" style=\"font-family:Monospace\">";
	}

	static private function _closePrintAreaHTML() {
		return '</span>';
	}

	static public function getPrintableValueType($v) {
		$type = gettype($v);
		$color = 'blue';
		$style = null;
		$ret = '';
		switch ($type) {
			case 'string':
				$color = 'red';
				if (strlen($v)>64) {
					$v = substr($v, 0, 64) . ' ... ';
				}
				$ret = '"' . str_replace(array("\r\n", "\r", "\n","\t"," "), array('<br/>', '<br/>','<br/>', "&nbsp;&nbsp;", "&nbsp;"), $v) . '"';
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
				$style = 'italic';
				$color = 'violet';
				$ret = $v ? 'TRUE' : 'FALSE';
				break;
			case 'object':
				$ret = get_class($v); // . ' { ... }';
				break;
			case 'NULL':
				$style = 'italic';
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

		return self::colorContent($ret, $color, $style);
	}

	static public function getPrintableValueClosure($value, $type) {
		if (self::$_printType=='html') {
			return self::getPrintableValueClosureHTML($value, $type);
		}
	}

	static public function getPrintableValueClosureHTML($value, $openClose) {
		$open = '{';
		$close = '}';
		$type = gettype($value);
		$color = 'blue';
		switch ($type) {
			case 'array':
				$color = 'black';
				$open = '[';
				$close = ']';
				break;
			case 'object':
				break;
			case 'string':
			case 'integer':
			case 'boolean':
			case 'NULL':
			case 'double':
			case 'resource':
			default:
				return '';
				break;
		}
		
		return self::colorContent(($openClose=='open')?$open:$close, $color);
	}

	static public function autodetectPrintType() {
		self::$_printType = (!empty($_SERVER)) ? 'html' : 'linux';
	}

	static public function inspect($element, $matchPath = null, $returnString = false) {
		self::autodetectPrintType();
		if ($matchPath === null) {
			if (self::$_printType=='html') {
				$matchPath = &$_GET[self::$_urlParameterName];
			}
		}
		if ($returnString) {
			ob_start();
		}
		echo self::_openPrintArea();
		self::_dump($element, null, $matchPath);
		echo self::_closePrintArea();
		if ($returnString) {
			return ob_get_clean();
		}
	}

	static private function _matchPath($matchPath, $path) {
		if (empty($matchPath)) {
			return false;
		}
		foreach($matchPath as $match => $yesOrNot) {
			// echo "**{$match}**";
			if (strpos($match, $path)===0) {
				return (bool)$yesOrNot;
			}
		}
		return false;
		$matchPaths = explode(',', $matchPath);
		foreach($matchPaths as $testPath) {
			if (strpos($testPath, $path)===0) {
				return true;
			}
		}
		return false;
	}

	static private function _dump($element, $prevPath = null, $matchPath = null, $level = 0, $showElementType = true) {
		$br = self::getBreakLine();
		$tab = self::getTab();
		$tabs = str_repeat($tab, $level);
		$path = $prevPath;
		$addLevel = 2;
		// echo "\n{$tabs}ELEMENT PATH ($path): ";
		$pKey1 = self::colorContent("{", 'blue');
		$pKey2 = self::colorContent("}", 'blue');
		if (is_array($element)) {
			if ($showElementType) {
				echo "{$br}{$tabs}" . self::colorContent("array (", 'blue');
			}
			foreach($element as $key => $value) {
				$newPath = $path . "/$key/";
				$showValue = self::getShowValue($value);
				$pKey = self::getPrintableValueType($key);
				$pPath = self::getPathLink($newPath, $matchPath, $prevPath);
				$pValue = self::getPrintableValueType($value);
				$pKey1 = self::getPrintableValueClosure($value, 'open');
				$pKey2 = self::getPrintableValueClosure($value, 'close');
				
				echo "{$br}{$tabs}{$tab}[{$pKey}] {$pValue} {$pKey1} {$pPath}";
				if ($matchPath && self::_matchPath($matchPath, $newPath)) {
					self::_dump($value, $newPath, $matchPath, $level+$addLevel, false);
					echo "{$br}{$tabs}{$tab}{$pKey2}";
				} else {
					echo self::colorContent("{$pKey2}", 'blue');
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
				$r = new ReflectionObject($element);
				$constants = $r->getConstants();
				$pAccess = self::colorContent('const', 'dimgray', 'italic');
				foreach($constants as $name => $value) {
					echo "{$br}{$tabs}{$tab}{$pAccess} " . self::colorContent($name, 'limegreen') . ' ' . self::getPrintableValueType($value);
				}
				$properties = $r->getProperties(ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PROTECTED | ReflectionProperty::IS_PRIVATE);
				foreach($properties as $property) {
					$access = $property->isPrivate() ? 'private' : ($property->isProtected() ? 'protected' : 'public');
					$static = $property->isStatic() ? 'static ' : '';
					if (!$property->isPublic()) {
						$property->setAccessible(true);
					}
					$value = $property->getValue($element);
					$pAccess = self::colorContent($access, 'dimgray', 'italic');
					$pValue = self::getPrintableValueType($value);
					$pName  = self::colorContent('$' . $property->name, 'limegreen');
					$newPath = $path . '/' . $property->name . '/';
					$pPath = self::getPathLink($newPath, $matchPath, $prevPath);
					$pKey1 = self::getPrintableValueClosure($value, 'open');
					$pKey2 = self::getPrintableValueClosure($value, 'close');
					echo "{$br}{$tabs}{$tab}{$static}{$pAccess} {$pName} {$pValue} {$pKey1} {$pPath}";
					if ($matchPath && self::_matchPath($matchPath, $newPath)) {
						self::_dump($value, $newPath, $matchPath, $level+$addLevel, false);
						echo "{$br}{$tabs}{$tab}{$pKey2}";
					} else {
						echo self::colorContent(" {$pKey2}", 'blue');
					}
				}
				$methods = $r->getMethods();
				foreach($methods as $method) {
					$access = $method->isPublic() ? 'public' : ($method->isPrivate() ? 'private' : 'protected');
					$pAccess = self::colorContent($access, 'dimgray', 'italic');
					echo "{$br}{$tabs}{$tab}{$pAccess} " . self::colorContent("{$method->name}(", 'blue',null, $method->getDocComment());
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

class URLHandler {
	public $parameters = array();
	public $uri = null;

	public function __construct() {
		$this->parse();
	}
	public function parse($url = null) {
		if ($url === null) {
			$url = $_SERVER['REQUEST_URI'];
		}
		@list($uri,$qs) = explode('?', $url);
		$this->uri = $uri;
		$this->parameters = array();
		if (empty($qs)) return;
		
		$qp = explode('&', $qs);
		foreach($qp as $q){
			list($n,$v) = explode('=', $q);
			$this->parameters[$n] = $v;
		}
	}
	
	public function encodeQueryString() {
		$tmp = array();
		foreach($this->parameters as $n => $v) {
			$tmp[] ="{$n}=$v";
		}
		return implode('&', $tmp);
	}

	public function getURI() {
		$ret = $this->uri;
		if (count($this->parameters)) {
			$ret .= '?' . $this->encodeQueryString();
		}

		return $ret;
	}
}
