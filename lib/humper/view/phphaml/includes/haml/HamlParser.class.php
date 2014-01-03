<?php
/**
 * Haml parser.
 *
 * @link http://haml.hamptoncatlin.com/ Original Haml parser (for Ruby)
 * @link http://phphaml.sourceforge.net/ Online documentation
 * @link http://sourceforge.net/projects/phphaml/ SourceForge project page
 * @license http://www.opensource.org/licenses/mit-license.php MIT (X11) License
 * @author Amadeusz Jasak <amadeusz.jasak@gmail.com>
 * @package phpHaml
 * @subpackage Haml
 */

require_once dirname(__FILE__) . '/../common/CommonCache.class.php';
require_once dirname(__FILE__) . '/HamlException.class.php';
require_once dirname(__FILE__) . '/HamlFilters.php';
require_once dirname(__FILE__) . '/HamlLine.class.php';

/**
 * Haml parser.
 *
 * Haml is templating language. It is very simple and clean.
 * Example Haml code
 * <code>
 * !!! 1.1
 * %html
 *   %head
 *     %title= $title ? $title : 'none'
 *     %link{ :rel => 'stylesheet', :type => 'text/css', :href => "$uri/tpl/$theme.css" }
 *   %body
 *     #header
 *       %h1.sitename example.com
 *     #content
 *       / Table with models
 *       %table.config.list
 *         %tr
 *           %th ID
 *           %th Name
 *           %th Value
 *         - foreach ($models as $model)
 *           %tr[$model]
 *             %td= $model->ID
 *             %td= $model->name
 *             %td= $model->value
 *     #footer
 *       %address.author Random Hacker
 * </code>
 * Comparing to original Haml language I added:
 * <ul>
 *   <li>
 *     Support to translations - use '$'
 * <code>
 * %strong$ Log in
 * </code>
 *   </li>
 *   <li>
 *     Including support ('!!') and level changing ('?')
 * <code>
 * !! html
 * !! page.header?2
 * %p?3
 *   Foo bar
 * !! page.footer?2
 * </code>
 *   </li>
 * </ul>
 *
 * @link http://haml.hamptoncatlin.com/ Original Haml parser (for Ruby)
 * @link http://phphaml.sourceforge.net/ Online documentation
 * @link http://sourceforge.net/projects/phphaml/ SourceForge project page
 * @license http://www.opensource.org/licenses/mit-license.php MIT (X11) License
 * @author Amadeusz Jasak <amadeusz.jasak@gmail.com>
 * @package phpHaml
 * @subpackage Haml
 */
class HamlParser extends HamlLine {

	/**
	 * Indention token
	 * gets set the first time the indentation token is encountered
	 */
	 	
	public static $TOKEN_INDENT = null;
	 
	/**
	 * Number of TOKEN_INDENT to indent
	 */
	    
	public static $INDENT = null;

	/**
	 * Render Haml. Append globals variables
	 *
	 * Simple way to use Haml
	 * <code>
	 * echo HamlParser::haml('%strong Hello, World!'); // <strong>Hello, World!</strong>
	 * $foo = 'bar'; // This is global variable
	 * echo Haml::haml('%strong= "Foo is $foo"'); // <strong>Foo is bar</strong>
	 * </code>
	 *
	 * @param string Haml source
	 * @return string xHTML
	 */
	public static function haml($sSource)
	{
		static $__haml_parser;
		if (!$__haml_parser)
			$__haml_parser = new HamlParser();
		$__haml_parser->setSource($sSource);
		$__haml_parser->append($GLOBALS);
		return $__haml_parser->fetch();
	}
   
	protected $aCustomHelpers = array();

	/**
	 * Register helper function that could be later called inside template via  $this->registered_alias
	 * <code>
	 * -# escape title
	 * %title= $this->e($meta_title)
	 *	</code>
	 *
	 * @param string alias
	 * @param callable callback
	 */
	public function registerFunc($alias, $callback) {
		$call_name = null;
		if(is_callable($callback, false, $call_name))
			$this->aCustomHelpers[$alias] = $callback;
	}
	/**
	 * call registered function
	 * @param string name 
	 * @param mixed arguments
	 * @return mixed executed function result
	 */
	public function __call($name, $arguments) {
		if(isset($this->aCustomHelpers[$name]))
			return call_user_func_array($this->aCustomHelpers[$name], $arguments);
	}
	
	/**
	 * function transforms multiline option lists into single line in order to parse them normally
	 *
	 * depth(A|B) are level controls, which prevent from bracket missunderstanding
	 */
    private function preParse(&$sSource) {
       preg_match_all('#(\#|\.|%)\w+\{((?>[^\{\}]+)|(?R))*\}#x', $sSource, $matches);
       preg_match_all('#(\#|\.|%)\w+\(((?>[^\(\)]+)|(?R))*\)#x', $sSource, $matches2);
         
       $_matches = $_replaces = array();
       foreach($matches[2] as $m) {
          if(strpos($m, "\xA") !== FALSE) {
             $_replaces[] = str_replace(array("\n", "\r"), '', $m);
             $_matches[] = $m;
          }
       } 
       foreach($matches2[2] as $m) {
          if(strpos($m, "\xA") !== FALSE) {
             $_replaces[] = str_replace(array("\n", "\r"), '', $m);
             $_matches[] = $m;
          }
       }
       $sSource = str_replace($_matches, $_replaces, $sSource);
	/*	$depthA = $depthB = 0;
		for($i = 0, $len = strlen($sSource); $i < $len; $i++) {
			if($sSource[$i] == '{' && !$depthB)
				$depthA++;
			
			if($sSource[$i] == '}' && !$depthB) 
				$depthA--;

			if($sSource[$i] == '(' && !$depthA)
				$depthB++;

			if($sSource[$i] == ')' && !$depthA) 
				$depthB--;

			if($sSource[$i] == "\n" && ($depthA || $depthB)) 
				$sSource[$i] = ' ';
      }*/
  }

	/**
	 * Render the source or file
	 *
	 * @see HamlParser::fetch()
	 * @return string
	 */
	public function render(array $aContext = array())
	{
		$this->preParse($this->sSource);
		$__aSource = explode(self::TOKEN_LINE, $this->sRealSource = $this->sSource = $this->parseBreak($this->sSource));
		$__sCompiled = '';
		$__oCache = new CommonCache($this->sTmp, 'hphp', $this->sSource);
		$this->aChildren = array();
		if ($__oCache->isCached() && $this->bCompile && !$this->isDebug())
			$__sCompiled = $__oCache->getFilename();
		else
		{
			$__sGenSource = $this->parseIncludes($this->sSource);
			$this->sSource = $this->sRealSource = $__sGenSource;
			$__aSource = explode(self::TOKEN_LINE, $__sGenSource);
			$__sCompiled = $__oCache->setCached($this->parseFile($__aSource))->cacheIt()->getFilename();
		}
		$__c = $this->execute($__sCompiled, $aContext);
		return $__c;
	}

	// Template engine
	public function parseIncludes($source){
		do
		{
			$__aSource = explode(self::TOKEN_LINE, $source = $__sGenSource = $this->parseBreak($source));
			$__iIndent = 0;
			$__iIndentLevel = 0;
			foreach ($__aSource as $__iKey => $__sLine)
			{
				$__iLevel = $this->countLevel($__sLine);
				if ($__iLevel <= $__iIndentLevel)
					$__iIndent = $__iIndentLevel = 0;
				if (preg_match('/\\'.self::TOKEN_LEVEL.'([0-9]+)$/', $__sLine, $__aMatches))
				{
					$__iIndent = (int)$__aMatches[1];
					$__iIndentLevel = $__iLevel;
					$__sLine = preg_replace('/\\'.self::TOKEN_LEVEL."$__iIndent$/", '', $__sLine);
				}
				$__sLine = str_repeat(HamlParser::$TOKEN_INDENT, $__iIndent * HamlParser::$INDENT) . $__sLine;
				$__aSource[$__iKey] = $__sLine;
				if (preg_match('/^(\s*)'.self::TOKEN_INCLUDE.' (.+)/', $__sLine, $aMatches))
				{
					$__sISource = file_get_contents($__sIFile = $this->getFilename($aMatches[2]));
					if ($this->isDebug())
						$__sISource = "// Begin file $__sIFile\n$__sISource\n// End file $__sIFile";
					$__sIncludeSource = $this->sourceIndent($__sISource, $__iIndent ? $__iIndent : $__iLevel);
					$__sLine = str_replace($aMatches[1] . self::TOKEN_INCLUDE . " {$aMatches[2]}", $__sIncludeSource, $__sLine);
					$__aSource[$__iKey] = $__sLine;
				}
				$source = implode(self::TOKEN_LINE, $__aSource);
			}
		} while (preg_match('/(\\'.self::TOKEN_LEVEL.'[0-9]+)|(\s*[^!]'.self::TOKEN_INCLUDE.' .+)/', $source));
		return $source;
	}

	public function execute($__sCompiled, $__sContext){
		// Expand compiled template
		// set up variables for context
		foreach ($this->aVariables as $__sName => $__mValue)
			$$__sName = $__mValue;
		foreach ($__sContext as $__sName => $__mValue)
			$$__sName = $__mValue;
		ob_start();		// start a new output buffer
		require $__sCompiled;
		if ($this->isDebug())
			@unlink($__sCompiled);
		$__c = rtrim(ob_get_clean()); // capture the result, and discard ob
		// Call filters
		foreach ($this->aFilters as $mFilter)
			$__c = call_user_func($mFilter, $__c);
		if ($this->isDebug())
		{
			header('Content-Type: text/plain');
			$__a = "\nFile $this->sFile:\n";
			foreach (explode("\n", $__sGenSource) as $iKey => $sLine)
				$__a .= 'F' . ($iKey + 1) . ":\t$sLine\n";
			$__c .= rtrim($__a);
		}
		return $__c;
	}

	/**
	 * Render the source or file
	 *
	 * @see HamlParser::fetch()
	 * @return string
	 */
	public function __toString()
	{
		return $this->render();
	}

	/**
	 * Parse multiline
	 *
	 * @param string File content
	 * @return string
	 */
	protected function parseBreak($sFile)
	{
		$sFile = preg_replace('/(\S+) +\\'.self::TOKEN_BREAK.'[ \t]*\n[ \t]*/', '\\1 ', $sFile);
		return $sFile;
	}

	/**
	 * Create and append line to parent
	 *
	 * @param string Line
	 * @param object Parent parser
	 * @param integer Line number
	 * @return object
	 */
	public function createLine($sLine, $parent, $iLine = null)
	{
		$oHaml = new HamlLine($this->sPath, $this->bCompile, $parent, array('line'=>$iLine, 'file'=>$this->sFile));
		$oHaml->setSource(rtrim($sLine, "\r"));
		$oHaml->iIndent = $parent->iIndent + 1;
		$parent->aChildren[] = $oHaml;
		return $oHaml;
	}

	/**
	 * Parse file
	 *
	 * @param array Array of source lines
	 * @return string
	 */
	protected function parseFile($aSource)
	{
		// Currently "active" HamlParsers at each level.
		$aLevels = array(-1 => $this);
		$sCompiled = '';
		foreach ($aSource as $iKey => $sSource)
		{
			// Skip blank lines
			if(trim($sSource) == "") continue;

			$iLevel = $this->countLevel($sSource);
			$aLevels[$iLevel] = $this->createLine($sSource, $aLevels[$iLevel - 1], $iKey + 1);
		}
		$sCompiled = $this->parseLine('');  // just invokes children recursively
		// For some reason, spaces keep accumulating before the else
		$sCompiled = preg_replace('|<\?php \} \?>\s*<\?php\s+else(\s*if)?|ius', '<?php } else\1 ', $sCompiled);
		return $sCompiled;
	}

	/**
	 * Template variables
	 *
	 * @var array
	 */
	protected $aVariables = array();

	/**
	 * Assign variable
	 *
	 * <code>
	 * // ...
	 * $parser->assign('foo', 'bar');
	 * $lorem = 'ipsum';
	 * $parser->assign('example', $lorem);
	 * </code>
	 *
	 * @param string Name
	 * @param mixed Value
	 * @return object
	 */
	public function assign($sName, $sValue)
	{
		$this->aVariables[$sName] = $sValue;
		return $this;
	}

	/**
	 * Assign associative array of variables
	 *
	 * <code>
	 * // ...
	 * $parser->append(array('foo' => 'bar', 'lorem' => 'ipsum');
	 * $data = array
	 * (
	 *   'x' => 10,
	 *   'y' => 5
	 * );
	 * $parser->append($data);
	 * </code>
	 *
	 * @param array Data
	 * @return object
	 */
	public function append($aData)
	{
		$this->aVariables = array_merge($this->aVariables, $aData);
		return $this;
	}

	/**
	 * Removes variables
	 *
	 * @return object
	 */
	public function clearVariables()
	{
		$this->aVariables = array();
		return $this;
	}

	/**
	 * Remove all compiled templates (*.hphp files)
	 *
	 * @return object
	 */
	public function clearCompiled()
	{
		$oDirs = new DirectoryIterator($this->sTmp);
		foreach ($oDirs as $oDir)
			if (!$oDir->isDot())
				if (preg_match('/\.hphp/', $oDir->getPathname()))
				unlink($oDir->getPathname());
				return $this;
	}

	/**
	 * Return compiled template
	 *
	 * <code>
	 * // ...
	 * echo $parser->setSource('%strong Foo')->fetch(); // <strong>Foo</strong>
	 * $parser->setSource('%strong Bar')->display(); // <strong>Bar</strong>
	 * echo $parser->setSource('%em Linux'); // <strong>Linux</strong>
	 *
	 * echo $parser->fetch('bar.haml'); // Compile and display bar.haml
	 * </code>
	 *
	 * @param string Filename
	 * @return string
	 */
	public function fetch($sFilename = false)
	{
		if ($sFilename)
			$this->setFile($sFilename);
		return $this->render();
	}

	/**
	 * Display template
	 *
	 * @see HamlParser::fetch()
	 * @param string Filename
	 */
	public function display($sFilename = false)
	{
		echo $this->fetch($sFilename);
	}

	/**
	 * List of registered filters
	 *
	 * @var array
	 */
	protected $aFilters = array();

	/**
	 * Register output filter.
	 *
	 * Filters are next usefull stuff. For example if
	 * you want remove <em>all</em> whitespaces (blah) use this
	 * <code>
	 * // ...
	 * function fcw($data)
	 * {
	 *   return preg_replace('|\s*|', '', $data);
	 * }
	 * $parser->registerFilter('fcw');
	 * echo $parser->fetch('foo.haml');
	 * </code>
	 *
	 * @param callable Filter
	 * @param string Name
	 * @return object
	 */
	public function registerFilter($mCallable, $sName = false)
	{
		if (!$sName)
			$sName = serialize($mCallable);
		$this->aFilters[$sName] = $mCallable;
		return $this;
	}

	/**
	 * Unregister output filter
	 *
	 * @param string Name
	 * @return object
	 */
	public function unregisterFilter($sName)
	{
		unset($this->aFilters[$sName]);
		return $this;
	}

	/**
	 * Return array of template variables
	 *
	 * @return array
	 */
	public function getVariables()
	{
		return $this->aVariables;
	}

	/**
	 * Parse variable in square brackets
	 *
	 * @param mixed Variable
	 * @return array Attributes
	 */
	public static function parseSquareBrackets($mVariable)
	{
		$sType = gettype($mVariable);
		$aAttr = array();
		$sId = '';
		if ($sType == 'object')
		{
			static $__objectNamesCache;
			if (!is_array($__objectNamesCache))
				$__objectNamesCache = array();
			$sClass = get_class($mVariable);
			if (!array_key_existS($sClass, $__objectNamesCache))
				$__objectNamesCache[$sClass] = $sType = trim(preg_replace('/([A-Z][a-z]*)/', '$1_', $sClass), '_');
			else
				$sType = $__objectNamesCache[$sClass];
			if (method_exists($mVariable, 'getID'))
				$sId = $mVariable->getID(); else
			if (!empty($mVariable->ID))
				$sId = $mVariable->ID;
		}
		if ($sId == '')
			$sId = substr(md5(uniqid(serialize($mVariable).rand(), true)), 0, 8);
		$aAttr['class'] = strtolower($sType);
		$aAttr['id'] = "{$aAttr['class']}_$sId";
		return $aAttr;
	}
	/**
	 * Write attributes
	 */
	public static function writeAttributes() {
		$aAttr = array();
		// Left takes precedence because cultivated options were in
		// argument 0
		foreach (func_get_args() as $aArray)
			$aAttr = array_merge($aArray, $aAttr);
		ksort($aAttr);
		foreach ($aAttr as $sName => $sValue){
			if(is_integer($sName)){
				self::writeAttributes($sValue);
			}
			else if ($sValue !== null && $sValue !== false) {
				if($sName == 'id' && is_array($sValue))
					$sValue = implode('_', array_filter($sValue));
				if($sName == 'class' && is_array($sValue))
					$sValue = implode(' ', array_filter($sValue));
				echo " $sName=\"".htmlentities($sValue, null, 'utf-8').'"';
			}
		}
	}
}

if (!function_exists('fake_translate'))
{
	/**
	 * Fake translation function used
	 * as default translation function
	 * in HamlParser
	 *
	 * @param string
	 * @return string
	 */
	function fake_translate($s)
	{
		return $s;
	}
}

/**
 * This is the simpliest way to use Haml
 * templates. Global variables are
 * automatically assigned to template.
 *
 * <code>
 * $x = 10;
 * $y = 5;
 * display_haml('my.haml'); // Simple??
 * </code>
 *
 * @param string Haml parser filename
 * @param array Associative array of additional variables
 * @param string Temporary directory (default is directory of Haml templates)
 * @param boolean Register get, post, session, server and cookie variables
 */
function display_haml($sFilename, $aVariables = array(), $sTmp = true, $bGPSSC = false)
{
	global $__oHaml;
	$sPath = realpath($sFilename);
	if (!is_object($__oHaml))
		$__oHaml = new HamlParser(dirname($sPath), $sTmp);
	$__oHaml->append($GLOBALS);
	if ($bGPSSC)
	{
		$__oHaml->append($_GET);
		$__oHaml->append($_POST);
		$__oHaml->append($_SESSION);
		$__oHaml->append($_SERVER);
		$__oHaml->append($_COOKIE);
	}
	$__oHaml->append($aVariables);
	$__oHaml->display($sFilename);
}

?>
