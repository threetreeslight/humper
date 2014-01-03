<?php
/**
 * Base class for Haml parser.
 *
 * This does the real work of parsing HAML.
 * Handling includes, context/variables, filters etc.
 * happen in HamlParser.
 */
class HamlLine {
	/**
	 * Haml source
	 *
	 * @var string
	 */
	public $sSource = '';

	/**
	 * Files path
	 *
	 * @var string
	 */
	protected $sPath = '';

	/**
	 * Compile templates??
	 *
	 * @var boolean
	 */
	protected $bCompile = true;

	/**
	 * Filename
	 *
	 * @var string
	 */
	protected $sFile = '';

	/**
	 * Parent parser
	 *
	 * @var object
	 */
	protected $oParent = null;

	/**
	 * Children parsers
	 *
	 * @var array
	 */
	protected $aChildren = array();

	/**
	 * Indent level
	 *
	 * @var integer
	 */
	public $iIndent = -1;

	/**
	 * Translation function name.
	 *
	 * @var string
	 */
	public $sTranslate = 'fake_translate';

	/**
	 * Translation function
	 *
	 * You can use it in templates.
	 * <code>
	 * %strong= $foo.$this->translate('to translate')
	 * </code>
	 */
	protected function translate()
	{
		$a = func_get_args();
		return call_user_func_array($this->sTranslate, $a);
	}

	/**
	 * Temporary directory
	 *
	 * @var string
	 */
	protected $sTmp = '';

	/**
	 * Block of PHP code
	 *
	 * @var boolean
	 */
	protected $bBlock = false;

	/**
	 * Current tag name
	 *
	 * @var string
	 */
	public $sTag = 'div';

   /**
    * The constructor.
    *
    * Create Haml parser. Second argument can be path to
    * temporary directory or boolean if true then templates
    * are compiled to templates path else if false then
    * templates are compiled on every run
    * <code>
    * <?php
    * require_once './includes/haml/HamlParser.class.php';
    *
    * $parser = new HamlParser('./tpl', './tmp');
    * $foo = 'bar';
    * $parser->assign('foo', $foo);
    * $parser->display('mainpage.haml');
    * ?>
    * </code>
    *
    * @param bool $sPath
    * @param bool $bCompile
    * @param null $oParent
    * @param null $aDebug
    * @param bool $bInside
    *
    * @internal param \Path $string to files
    * @internal param $boolean /string Compile templates (can be path)
    * @internal param \Parent $object parser
    * @internal param Array $array with debug informations
    * @internal param \Is $boolean used dynamic including
    */
	public function __construct($sPath = false, $bCompile = true, $oParent = null, $aDebug = null, $bInside = false)
	{
		if ($sPath)
			$this->setPath($sPath);
		$this->bCompile = $bCompile;
		if (is_string($bCompile))
         $this->setTmp($bCompile); 
      elseif ($sPath)
			$this->setTmp($sPath);
		else
			$this->setTmp(ini_get('session.save_path'));
		if ($oParent)
		{
			$this->setParent($oParent);
			if ($aDebug)
				$this->aDebug = $aDebug;
		}
		$this->bInside = $bInside;
		if (!self::$otc)
			self::__otc();
	}

	/**
	 * Is used dynamic including
	 *
	 * @var boolean
	 */
	protected $bInside = false;

	/**
	 * Debugging informations
	 *
	 * @var array
	 */
	public $aDebug = null;

	/**
	 * One time constructor, is executed??
	 *
	 * @var boolean
	 */
	protected static $otc = false;

	/**
	 * One time constructor. Register Textile block
	 * if exists Textile class and Markdown block if
	 * exists Markdown functions
	 */
	protected static function __otc()
	{
		self::$otc = true;
		if (class_exists('Textile', false))
			self::registerBlock(array(new Textile, 'TextileThis'), 'textile');
		if (function_exists('Markdown'))
			self::registerBlock('Markdown', 'markdown');
		self::tryRegisterSass();
	}

	/**
	 * Try register Sass engine as text block
	 *
	 * @return boolean
	 */
	protected static function tryRegisterSass()
	{
		if (file_exists($f = dirname(__FILE__) . '/../sass/SassParser.class.php'))
			require_once $f;
		else
			return false;
		self::registerBlock(array('SassParser', 'sass'), 'sass');
		return true;
	}

	/**
	 * Return source of child
	 *
	 * @param integer Level
	 * @return string
	 */
	public function getAsSource($iLevel)
	{
		$x = ($this->iIndent - $iLevel - 1) * HamlParser::$INDENT;
		$sSource = '';
		if ($x >= 0)
			$sSource = preg_replace('|^'.str_repeat(HamlParser::$TOKEN_INDENT, ($iLevel + 1) * HamlParser::$INDENT).'|', '', $this->sRealSource);
		foreach ($this->aChildren as $oChild)
			$sSource .= self::TOKEN_LINE.$oChild->getAsSource($iLevel);
		return trim($sSource, self::TOKEN_LINE);
	}

   /**
    * Set parent parser. Used internally.
    *
    * @param HamlLine $oParent
    *
    * @internal param \Parent $object parser
    * @return object
    */
	public function setParent(HamlLine $oParent)
	{
		$this->oParent = $oParent;
		$this->bRemoveBlank = $oParent->bRemoveBlank;
		return $this;
	}

	/**
	 * Set files path.
	 *
	 * @param string File path
	 * @return object
	 */
	public function setPath($sPath)
	{
		$this->sPath = realpath($sPath);
		return $this;
	}

	/**
	 * Set filename.
	 *
	 * Filename can be full path to file or
	 * filename (then file is searched in path)
	 * <code>
	 * // We have file ./foo.haml and ./tpl/bar.haml
	 * // ...
	 * $parser->setPath('./tpl');
	 * $parser->setFile('foo.haml'); // Setted to ./foo.haml
	 * $parser->setFile('bar.haml'); // Setted to ./tpl/bar.haml
	 * </code>
	 *
	 * @param string Filename
	 * @return object
	 */
	public function setFile($sPath)
	{
		if (file_exists($sPath))
			$this->sFile = $sPath;
		else
			$this->sFile = "{$this->sPath}/$sPath";
		$this->setSource(file_get_contents($this->sFile));
		return $this;
	}

	/**
	 * Return filename to include
	 *
	 * You can override this function.
	 *
	 * @param string Name
	 * @return string
	 */
	public function getFilename($sName)
	{
		return "{$this->sPath}/".trim($sName).'.haml';
	}

	/**
	 * Real source
	 *
	 * @var string
	 */
	public $sRealSource = '';

	/**
	 * Set source code
	 *
	 * <code>
	 * // ...
	 * $parser->setFile('foo.haml');
	 * echo $parser->setSource('%strong Foo')->fetch(); // <strong>Foo</strong>
	 * </code>
	 *
	 * @param string Source
	 * @return object
	 */
	public function setSource($sHaml)
	{
		$this->sSource = trim($sHaml, HamlParser::$TOKEN_INDENT);
		$this->sRealSource = $sHaml;
		$this->sTag = null;
		$this->aChildren = array();
		return $this;
	}


	/**
	 * Set temporary directory
	 *
	 * @param string Directory
	 * @return object
	 */
	public function setTmp($sTmp)
	{
		$this->sTmp = realpath($sTmp);
		return $this;
	}

	/**
	 * Debug mode
	 *
	 * @see HamlParser::isDebug()
	 * @var boolean
	 */
	protected static $bDebug = false;

	/**
	 * Set and check debug mode. If is set
	 * debugging mode to generated source are
	 * added comments with debugging mode and
	 * Haml source is not cached.
	 *
	 * @param boolean Debugging mode (if null, then only return current state)
	 * @return boolean
	 */
	public function isDebug($bDebug = null)
	{
		if (!is_null($bDebug))
			self::$bDebug = $bDebug;
		if (self::$bDebug)
			$this->bCompile = false;
		return self::$bDebug;
	}

	/**
	 * List of text processing blocks
	 *
	 * @var array
	 */
	protected static $aBlocks = array(
		'javascript' => '_js',
		'css' => '_css',
		'escaped' => '_escaped',
		'plain' => '_plain',
      'php' => '_php',
      'cdata' => '_cdata'
	);

   /**
    * Register block
    *
    * Text processing blocks are very usefull stuff ;)
    * <code>
    * // ...
    * %code.checksum
    * $tpl = <<<__TPL__
    *   :md5
    *     Count MD5 checksum of me
    * __TPL__;
    * HamlParser::registerBlock('md5', 'md5');
    * $parser->display($tpl); // <code class="checksum">iejmgioemvijeejvijioj323</code>
    * </code>
    *
    * @param      string Name
    * @param bool $sName
    *
    * @internal param Callable $mixed
    */
	public static function registerBlock($mCallable, $sName = false)
	{
		if (!$sName)
			$sName = serialize($mCallable);
		self::$aBlocks[$sName] = $mCallable;
	}

	/**
	 * Unregister block
	 *
	 * @param string Name
	 */
	public static function unregisterBlock($sName)
	{
		unset(self::$aBlocks[$sName]);
	}

	/**
	 * Parse text block
	 *
	 * @param string Block name
	 * @param string Data
	 * @return string
	 */
	protected function parseTextBlock($sName, $sText)
	{
		return call_user_func(self::$aBlocks[$sName], $sText);
	}

	/**
	 * Eval embedded PHP code
	 *
	 * @see HamlParser::embedCode()
	 * @var boolean
	 */
	protected static $bEmbed = true;

	/**
	 * Eval embedded PHP code
	 *
	 * @param boolean
	 * @return boolean
	 */
	public function embedCode($bEmbed = null)
	{
		if (is_null($bEmbed))
			return self::$bEmbed;
		else
			return self::$bEmbed = $bEmbed;
	}

	/**
	 * Remove white spaces??
	 *
	 * @var boolean
	 * @access private
	 */
	public $bRemoveBlank = null;

	/**
	 * Remove white spaces
	 *
	 * @param boolean
	 * @return HamlParser
	 */
	public function removeBlank($bRemoveBlank)
	{
		$this->bRemoveBlank = $bRemoveBlank;
		return $this;
	}

	/**
	 * Whitespace eaters (< and >).
	 */
	public $bWhitespaceOutside = false;
	public $bWhitespaceInside = false;

  public function addCustomBlock($begin, $end)
  {
    self::$aCustomBlocks[$begin] = $end;
  }
	/**
	 * Parse { brackets in line
	 */
	private function parseOpts(&$in) {
		$depth = 0;
		$begin = $end = 0;
		for($i = 0, $len = strlen($in); $i < $len; $i++) {
			if($in[$i] == '{') {
				if($depth == 0) $begin = $i;
				$depth++;
			}
			
			if($in[$i] == '}') {
				$depth--;
				if($depth == 0) $end = $i;
			}

		}
		$result = preg_replace('/'.self::TOKEN_OPTION.'/', '"$1" =>', substr($in, $begin+1, $end-$begin-1));
		$in = str_replace(substr($in, $begin, $end-$begin+1), '', $in);
		return $result;
	}

   private function parseInterpolation($sContent) {
      $_sContent = $sContent;
            # check for interpolation
      if(strpos($sContent, self::TOKEN_INTERPOLATION_START) !== false) {
         $depth = 0;

         $double_opened = false;
         $single_opened = false;

         $original = '';
         for($i = 0, $l = strlen($sContent); $i < $l; $i++) {
            # string detection
            if($sContent[$i] == '"' && !$double_opened && !$single_opened) {
               $double_opened = true;
               $i++;
            }

            if($sContent[$i] == "'" && !$single_opened && !$double_opened) {
               $single_opened = true;
               $i++;

            }

            # attempt interpolation only inside strings and ignore string nesting
            if($single_opened || $double_opened) {
               $chunk = substr($sContent,$i,2);

               # TODO ignore escaped interpolation \#{
               if($chunk == self::TOKEN_INTERPOLATION_START) {
                  $depth++;
                  $i += 2;
               }

               if($sContent[$i] == '{') $depth++;
               if($sContent[$i] == '}') $depth--;

               if($depth) #grab contents of an #{ }
                  $original .= $sContent[$i];

               if(!$depth && $original) {
                  # detect string type
                  $quote = '';
                  if($single_opened)
                     $quote = "'";
                  elseif($double_opened)
                     $quote = '"';

                  $_sContent = str_replace('#{'.$original.'}', "$quote.(".$original.").$quote", $_sContent);
                  $original = '';
               }
            }

            #string end detection
            #
            if($single_opened && !$double_opened && $sContent[$i] == "'")
               $single_opened = false;

            if($double_opened && !$single_opened && $sContent[$i] == '"')
               $double_opened = false;

         }
      }

      return $_sContent;
   }

	/**
	 * Parse line
	 *
	 * @param string Line
	 * @return string
	 */

	public function parseLine($sSource) {
		$sParsed = '';
		$sRealBegin = '';
		$sRealEnd = '';
		$sParsedBegin = '';
		$sParsedEnd = '';
		$bParse = true;
		// Dynamic including
		if (preg_match('/^'.self::TOKEN_INCLUDE.self::TOKEN_PARSE_PHP.' (.*)/', $sSource, $aMatches) && $this->embedCode()) {
         return ($this->isDebug() ?
             "{$this->aDebug['line']}:\t{$aMatches[1]} == <?php var_export({$aMatches[1]}) ?>\n\n" : '') 
              . "<?php echo \$this->indent(\$this->fetch(\$this->getFilename({$aMatches[1]})), $this->iIndent, true, false); ?>";
		} else
        // Doctype parsing
        if (preg_match('/^'.self::TOKEN_DOCTYPE.'(.*)/', $sSource, $aMatches))
        {
           $aMatches[1] = trim($aMatches[1]);
           if ($aMatches[1] == '')
             $aMatches[1] = '1.1';
           $sParsed = self::$aDoctypes[$aMatches[1]]."\n";
        } else
          // Internal comment
          if (preg_match('/^'.self::TOKEN_HAML_COMMENT.'/', $sSource))
             return '';
          else
          // PHP instruction
            if (preg_match('/^'.self::TOKEN_INSTRUCTION_PHP.' (.*)/', $sSource, $aMatches))
            {
               if (!$this->embedCode())
                  return '';
               $bBlock = false;
               $blockName = array();
               // Check for custom block
               if (preg_match('/^('.implode('|', array_keys(self::$aCustomBlocks)).')/', $aMatches[1], $blockName) && !empty(self::$aCustomBlocks))
               {
                  $sParsedBegin = '<?php ' . $this->indent($aMatches[1] . ';', -2, false) . '?>';
                  $sParsedEnd = '<?php ' . self::$aCustomBlocks[$blockName[1]] . '();?>';
               } else {
                 if(preg_match('/^(case|default)\s*/', $aMatches[1])) {
                   $sParsedBegin = '<?php ' . $this->indent($aMatches[1].':', -2, false) . "?>\n";
                   $sParsedEnd = "<?php break; ?>\n"; 
                 } else {
                  // Check for block
                  if (preg_match('/^('.implode('|', self::$aPhpBlocks).')/', $aMatches[1]))
                    $this->bBlock = $bBlock = true;
                  // FIXME: indenting here is probably for aesthetics, since it's trying to be careful with generating the right spacing.
                  $sParsedBegin = '<?php ' . $this->indent($aMatches[1] . ($bBlock ? ' { ' : ';'), -2, false)  . "?>\n";
                  if ($bBlock)
                    $sParsedEnd = "<?php } ?>\n";
                 }
               }
            } else
		// Text block
		if (preg_match('/^'.self::TOKEN_TEXT_BLOCKS.'(.+)/', $sSource, $aMatches))
		{
			$sParsed = $this->indent($this->parseTextBlock($aMatches[1], $this->getAsSource($this->iIndent)));
			$this->aChildren = array();
		} else
        // Check for PHP
      if (preg_match('/^'.self::TOKEN_ESCAPE_HTML.' (.*)/', $sSource, $aMatches))
			if ($this->embedCode())
				$sParsed = $this->indent("<?php echo htmlspecialchars({$aMatches[1]}, ENT_QUOTES, 'UTF-8'); ?>")."\n";
			else
				$sParsed = "\n";
      elseif (preg_match('/^'.self::TOKEN_PARSE_PHP.' (.*)/', $sSource, $aMatches))
			if ($this->embedCode())
				$sParsed = $this->indent("<?php echo {$this->parseInterpolation($aMatches[1])}; ?>")."\n";
			else
				$sParsed = "\n";
		else
		{
			$aAttributes = array(
			  '_inline' => array()
			);
			$sAttributes = '';
			$sTag = 'div';
			$sToParse = '';
			$sContent = '';
			$sAutoVar = '';
			
			// parse options
			if(preg_match('/(#|%|\.)\w+\\'.self::TOKEN_OPTIONS_LEFT.'/i', $sSource)) 
				$aAttributes['_inline'][] = $this->parseOpts($sSource);
			
			/**
			 * html-style attributes parsing
			 */

			if(preg_match('/(%|#|\.)\w+\\'.self::TOKEN_TERSER_LEFT.'/i', $sSource)) {
				$n = strlen($sSource);
				$depth = $begin = $end = 0;
				//find valid ending ')'
				for($i = 0; $i < $n; $i++) {
					if($sSource[$i] == '(') {
						++$depth;
						if($depth == 1) $begin = $i;
					}
					elseif($sSource[$i] == ')') {
						--$depth;
						if($depth == 0)  {
							$end = $i+1; break;
						}
					}
				}
				//get content
				$match = substr($sSource, $begin, $end-$begin);
				//remove it
				$sSource = str_replace($match, '', $sSource);
				//remove unnecessary ()
				$match = trim(substr($match, 1, strlen($match)-2));
				$ret = array();

				$o = preg_replace("/(^|\s+)([\w-]+)\s*=\s*([\"'($])/i", ' $2 => $3',$match);
				$values = preg_split("/(\w+)\s*=>\s*/i", trim($o), -1, PREG_SPLIT_NO_EMPTY);

				preg_match_all("/(\w+)\s*=>\s*/i", $o, $keys);

				for($i = 0; $i < count($keys[0]); $i++) 
					$ret[] = "\"{$keys[1][$i]}\" => ".trim($values[$i]);

				$sOptions = implode(', ', $ret);
				$aAttributes['_inline'][] = $sOptions;
			}

			$sFirst = '['.self::TOKEN_TAG.self::TOKEN_ID.self::TOKEN_CLASS.self::TOKEN_PARSE_PHP.']';

			if (preg_match("/^($sFirst.*?) (.*)/", $sSource, $aMatches))
			{
				$sToParse = $aMatches[1];
				$sContent = $aMatches[2];
			} else
			if (preg_match("/^($sFirst.*)/", $sSource, $aMatches))
				$sToParse = $aMatches[1];
			else
			{
				if (strlen($sSource) == 0)
				{
					$bParse = false;
				} else
				// Check for comment
				if (!preg_match('/^\\'.self::TOKEN_COMMENT.'(.*)/', $sSource, $aMatches))
				{
					if ($this->canIndent() && $this->bRemoveBlank)
						if ($this->isFirst())
							$sParsed = $this->indent($sSource, 0, false) . ' '; else
						if ($this->isLast())
							$sParsed = "$sSource\n";
						else
							$sParsed = "$sSource ";
					else
						$sParsed = $this->indent($sSource);
				}
				else
				{
					$aMatches[1] = trim($aMatches[1]);
					if ($aMatches[1] && !preg_match('/\[.*\]/', $aMatches[1]))
						$sParsed = $this->indent(wordwrap($aMatches[1], 60, "\n"), 1)."\n";
				}
				$bParse = false;
			}

			if ($bParse)
			{
				$bPhp = false;
				$bClosed = false;
				// Match tag
				if (preg_match_all('/'.self::TOKEN_TAG.'([a-zA-Z0-9:\-_]*)/', $sToParse, $aMatches))
					$this->sTag = $sTag = end($aMatches[1]); // it's stack
				// Match ID
				if (preg_match_all('/'.self::TOKEN_ID.'([a-zA-Z0-9\-_]*)/', $sToParse, $aMatches))
					$aAttributes['id'] = '\''.end($aMatches[1]).'\''; // it's stack
				// Match classes
				if (preg_match_all('/\\'.self::TOKEN_CLASS.'([a-zA-Z0-9\-_]*)/', $sToParse, $aMatches))
					$aAttributes['class'] = '\''.implode(' ', $aMatches[1]).'\'';
				if (preg_match_all('/'.self::TOKEN_WHITESPACE_OUTSIDE.'/', $sToParse, $aMatches))
					$this->bWhitespaceOutside = true;
				if (preg_match_all('/'.self::TOKEN_WHITESPACE_INSIDE.'/', $sToParse, $aMatches))
					$this->bWhitespaceInside = true;
				// Check for PHP
            if(preg_match('/'.self::TOKEN_ESCAPE_HTML.'/', $sToParse)) {
              if($this->embedCode()) {
                $sContentOld = $sContent;
                $sContent = "<?php echo htmlspecialchars($sContent, ENT_QUOTES, 'UTF-8'); ?>\n";
                $bPhp = true;
              } else $sContent = '';
              
            } elseif (preg_match('/'.self::TOKEN_PARSE_PHP.'/', $sToParse)) {
					if ($this->embedCode())
					{
						$sContentOld = $sContent;

                  $sContent = $this->parseInterpolation($sContent);

						$sContent = "<?php echo $sContent; ?>\n";
						$bPhp = true;
					}
					else
						$sContent = '';
				}
				// Match translating
				if (preg_match('/\\'.self::TOKEN_TRANSLATE.'$/', $sToParse, $aMatches))
				{
					if (!$bPhp)
						$sContent = "'$sContent'";
					else
						$sContent = $sContentOld;
					$sContent = "<?php echo {$this->sTranslate}($sContent); ?>\n";
				}
				// Match single tag
				if (preg_match('/\\'.self::TOKEN_SINGLE.'$/', $sToParse))
					$bClosed = true;
				// Match brackets
				if (preg_match('/\\'.self::TOKEN_AUTO_LEFT.'(.*?)\\'.self::TOKEN_AUTO_RIGHT.'/', $sToParse, $aMatches) && $this->embedCode())
					$sAutoVar = $aMatches[1];

				$inline = $aAttributes['_inline'];
				unset($aAttributes['_inline']);
				if (!empty($aAttributes) || !empty($sAutoVar) || !empty($inline))
					$sAttributes = '<?php HamlParser::writeAttributes('.$this->arrayExport($aAttributes).(!empty($sAutoVar) ? ", \HamlParser::parseSquareBrackets($sAutoVar)" : '' ).(!empty($inline)? ', array(' . implode($inline, ', ').')' : '') . '); ?>';
				$this->bBlock = $this->oParent->bBlock;
				$iLevelM = $this->oParent->bBlock || $this->bBlock ? -1 : 0;
				// FIXME: this whole block is a mess!!!
				// Needs to be reorganized to handle each orthogonal situation.
				// Check for closed tag
				if ($this->isClosed($sTag) || $bClosed)
					$sParsedBegin = $this->indent("<$sTag$sAttributes />", $iLevelM); else
				// Check for no indent tag
				if (in_array($sTag, self::$aNoIndentTags))
				{
					$this->bRemoveBlank = false;
					$sParsedBegin = $this->indent("<$sTag$sAttributes>", $iLevelM, false);
					if (!empty($sContent))
						$sParsed = $this->indent($sContent);
					$sParsedEnd = $this->indent("</$sTag>\n", $iLevelM);
				} else
				// Check for block tag
				if (!$this->isInline($sTag))
				{
					$sParsedBegin = $this->indent("<$sTag$sAttributes>", $iLevelM);
					if (!empty($sContent))
						if (strlen($sContent) > 60)
							$sParsed = $this->indent(wordwrap($sContent, 60, "\n"), 1+$iLevelM);
						else
							$sParsed = $this->indent($sContent, 1+$iLevelM);
					$sParsedEnd = $this->indent("</$sTag>", $iLevelM);
				} else
				// Check for inline tag
				if ($this->isInline($sTag))
				{
					if ($this->canIndent() && $this->bRemoveBlank)
						if ($this->isFirst())
							$sParsedBegin = $this->indent("<$sTag$sAttributes>", 0, false); else
						if ($this->isLast())
							$sParsedBegin = "<$sTag$sAttributes>\n";
						else
							$sParsedBegin = "<$sTag$sAttributes>";
					else
						if (!$this->canIndent())
							$sParsedBegin = "\n" . $this->indent("<$sTag$sAttributes>", $iLevelM, false);
						else
							$sParsedBegin = $this->indent("<$sTag$sAttributes>", $iLevelM, false);
					$sParsed = $sContent;
					if ($this->canIndent() && $this->bRemoveBlank)
						if ($this->isLast())
							$sParsedEnd = "</$sTag>\n";
						else
							$sParsedEnd = "</$sTag> ";
					else
						$sParsedEnd = "</$sTag>\n";
				}
			}
		}
		// Children appending
		$lastWhitespaceOutside = null;
		foreach ($this->aChildren as $oChild){
			$sChild = $oChild->parseLine($oChild->sSource);
			if($oChild->bWhitespaceOutside){
				$sParsed = rtrim($sParsed);
				$sChild = trim($sChild);
			}
			if($lastWhitespaceOutside){
				$sChild = ltrim($sChild);
			}
			$lastWhitespaceOutside = $oChild->bWhitespaceOutside;
			$sParsed .= $sChild;
		}
		// Check for IE comment
		if (preg_match('/^\\'.self::TOKEN_COMMENT.'\[(.*?)\](.*)/', $sSource, $aMatches))
		{
			$aMatches[2] = trim($aMatches[2]);
			if (count($this->aChildren) == 0)
			{
				$sParsedBegin = $this->indent("<!--[{$aMatches[1]}]> $sParsedBegin", 0, false);
				$sParsed = $aMatches[2];
				$sParsedEnd = "$sParsedEnd <![endif]-->\n";
			}
			else
			{
				$sParsed = $sParsedBegin.$sParsed.$sParsedEnd;
				$sParsedBegin = $this->indent("<!--[{$aMatches[1]}]>");
				$sParsedEnd = $this->indent("<![endif]-->");
			}
		} else
		// Check for comment
		if (preg_match('/^\\'.self::TOKEN_COMMENT.'(.*)/', $sSource, $aMatches))
		{
			$aMatches[1] = trim($aMatches[1]);
			if (count($this->aChildren) == 0)
			{
				$sParsedBegin = $this->indent("<!-- $sParsedBegin", 0, false);
				$sParsed = $aMatches[1];
				$sParsedEnd = "$sParsedEnd -->\n";
			}
			else
			{
				$sParsed = $sParsedBegin.$sParsed.$sParsedEnd;
				$sParsedBegin = $this->indent("<!--");
				$sParsedEnd = $this->indent("-->");
			}
		}
		if ($this->isDebug() && (count($this->aChildren) > 0))
			$sParsedEnd = "{$this->aDebug['line']}:\t$sParsedEnd";
		$sCompiled = $sRealBegin.$sParsedBegin.$sParsed.$sParsedEnd.$sRealEnd;
		if ($this->isDebug())
			$sCompiled = "{$this->aDebug['line']}:\t$sCompiled";
		return $sCompiled;
	}
	
	protected function getIndentToken(){
		return str_repeat(HamlParser::$TOKEN_INDENT, HamlParser::$INDENT);
	}
	
	/**
	 * Indent line
	 *
	 * @param string Line
	 * @param integer Additional indention level
	 * @param boolean Add new line
	 * @param boolean Count level from parent
	 * @return string
	 */
	protected function indent($sLine, $iAdd = 0, $bNew = true, $bCount = true)
	{
		if (!is_null($this->oParent) && $bCount)
			if (!$this->canIndent())
				if ($sLine{0} == '<')
					return $sLine;
				else
					return "$sLine\n";
		$aLine = explode("\n", $sLine);
		$sIndented = '';
		$iLevel = ($bCount ? $this->iIndent : 0) + $iAdd;
		foreach ($aLine as $sLine)
			$sIndented .= str_repeat($this->getIndentToken(), $iLevel >= 0 ? $iLevel : 0).($bNew ? "$sLine\n" : $sLine);
		return $sIndented;
	}

	/**
	 * Is first child of parent
	 *
	 * @return boolean
	 */
	protected function isFirst()
	{
		if (!$this->oParent instanceof HamlParser)
			return false;
		foreach ($this->oParent->aChildren as $key => $value)
			if ($value === $this)
				return $key == 0;
	}

	/**
	 * Is last child of parent
	 *
	 * @return boolean
	 */
	protected function isLast()
	{
		if (!$this->oParent instanceof HamlParser)
			return false;
		$count = count($this->oParent->aChildren);
		foreach ($this->oParent->aChildren as $key => $value)
			if ($value === $this)
				return $key == $count - 1;
	}

	/**
	 * Can indent (check for parent is NoIndentTag)
	 *
	 * @return boolean
	 */
	public function canIndent()
	{
		if (in_array($this->sTag, self::$aNoIndentTags))
			return false;
		else
			if ($this->oParent instanceof HamlLine)
				return $this->oParent->canIndent();
			else
				return true;
	}

	/**
	 * Indent Haml source
	 *
	 * @param string Source
	 * @param integer Level
	 * @return string
	 */
	protected function sourceIndent($sSource, $iLevel)
	{
		$aSource = explode(self::TOKEN_LINE, $sSource);
		foreach ($aSource as $sKey => $sValue)
			$aSource[$sKey] = str_repeat(HamlParser::$TOKEN_INDENT, $iLevel * HamlParser::$INDENT) . $sValue;
		$sSource = implode(self::TOKEN_LINE, $aSource);
		return $sSource;
	}

	/**
	 * Count level of line
	 *
	 * @param string Line
	 * @return integer
	 */
	protected function countLevel($sLine)
	{

		if ((strlen($sLine) > strlen(ltrim($sLine))) && HamlParser::$TOKEN_INDENT === null){
			//first indented line
			//save the indent char into $TOKEN_INDENT
			HamlParser::$TOKEN_INDENT = $sLine[0];

			//sanity check: should be space or tab
			if (HamlParser::$TOKEN_INDENT != self::TOKEN_TAB && HamlParser::$TOKEN_INDENT != self::TOKEN_SPACE)
				throw new HamlException("Invalid indent on line '$sLine': Needs to be either Tab or space!");

			//check how many of TOKEN_INDENTS there are and set into INDENT
			HamlParser::$INDENT = (strlen($sLine) - strlen(ltrim($sLine, HamlParser::$TOKEN_INDENT)));	

			//first indented line, should be level 1
			return 1;

		}else{
			$spaces = (strlen($sLine) - strlen(ltrim($sLine, HamlParser::$TOKEN_INDENT)));
		}
		//now we have everything set, we can just continue normally and check the indents

		if ($spaces == 0) return 0;

		if($spaces % HamlParser::$INDENT != 0){
			throw new HamlException("Invalid indent on line '$sLine': $spaces space(s) (needed multiple of " . HamlParser::$INDENT . ")");
		}
		return $spaces / HamlParser::$INDENT;
	}
	/**
	 * Check for inline tag
	 *
	 * @param string Tag
	 * @return boolean
	 */
	protected function isInline($sTag)
	{
		return (empty($this->aChildren) && in_array($sTag, self::$aInlineTags)) || empty($this->aChildren);
	}

	/**
	 * Check for closed tag
	 *
	 * @param string Tag
	 * @return boolean
	 */
	protected function isClosed($sTag)
	{
		return in_array($sTag, self::$aClosedTags);
	}

	/**
	 * End of line character
	 */
	const TOKEN_LINE = "\n";

	/**
	 * Indention token
	 */
	//const TOKEN_INDENT = ' ';
	const TOKEN_SPACE = ' ';
	const TOKEN_TAB = "\t";
	/**
	 * Create tag (%strong, %div)
	 */
	const TOKEN_TAG = '%';

	/**
	 * Set element ID (#foo, %strong#bar)
	 */
	const TOKEN_ID = '#';

	/**
	 * Set element class (.foo, %strong.lorem.ipsum)
	 */
	const TOKEN_CLASS = '.';

	/**
	 * Start the options (attributes) list
	 */
	const TOKEN_OPTIONS_LEFT = '{';

   /**
    * Start of interpolation
    */
   const TOKEN_INTERPOLATION_START = '#{';

   /**
	 * End the options list
	 */
	const TOKEN_OPTIONS_RIGHT = '}';

	/**
	 * Options separator
	 */
	const TOKEN_OPTIONS_SEPARATOR = ',\s*(:| )';

	/**
	 * Start option name
	 */
	const TOKEN_OPTION = ":([\\w-:_]+)\s*=>";

	/**
	 * Start option value
	 */
	const TOKEN_OPTION_VALUE = '=>';

	/**
	 * Begin PHP instruction (without displaying)
	 */
	const TOKEN_INSTRUCTION_PHP = '-';

	/**
	 * Parse PHP (and display)
	 */
	const TOKEN_PARSE_PHP = '=';

	/**
	 * Whitespace eater: eat outside
	 */
	const TOKEN_WHITESPACE_OUTSIDE = '>';

	/**
	 * Whitespace eater: eat inside
	 */
	const TOKEN_WHITESPACE_INSIDE = '<';

	/**
	 * Set DOCTYPE or XML header (!!! 1.1, !!!, !!! XML)
	 */
	const TOKEN_DOCTYPE = '!!!';

	/**
	 * Include file (!! tpl2)
	 */
	const TOKEN_INCLUDE = '!!';

	/**
	 * Comment code (block and inline)
	 */
	const TOKEN_COMMENT = '/';

	/**
	 * Translate content (%strong$ Translate)
	 */
	const TOKEN_TRANSLATE = '$';

	/**
	 * Mark level (%strong?3, !! foo?3)
	 */
	const TOKEN_LEVEL = '?';

	/**
	 * Create single, closed tag (%meta{ :foo => 'bar'}/)
	 */
	const TOKEN_SINGLE = '/';

	/**
	 * Break line
	 */
	const TOKEN_BREAK = '|';

	/**
	 * Begin automatic id and classes naming (%tr[$model])
	 */
	const TOKEN_AUTO_LEFT = '[';

	/**
	 * End automatic id and classes naming
	 */
	const TOKEN_AUTO_RIGHT = ']';

	/**
	 * Insert text block (:textile)
	 */
	const TOKEN_TEXT_BLOCKS = ':';

	/**
	 * Haml silent comment
	 */
	const TOKEN_HAML_COMMENT = '-#';

	/**
	 * Begin html-style attirbutes
	 */

	const TOKEN_TERSER_LEFT  = '(';
	const TOKEN_TERSER_CONTENT = '/([\w-]+)\s*=\s*(("|\')[\w\d\s\/=;:$_@{}.,#-]+("|\'))/i';
   const TOKEN_TERSER_RIGHT = ')';

   const TOKEN_ESCAPE_HTML = '&=';

	/**
	 * Number of TOKEN_INDENT to indent
	 */
   
	//const INDENT = 2;

	/**
	 * Doctype definitions
	 *
	 * @var string
	 */
	protected static $aDoctypes = array
	(
		'1.1' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">',
		'Strict' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">',
		'Transitional' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">',
		'Frameset' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">',
		'5' => '<!DOCTYPE html>',
		'XML' => "<?php echo '<?xml version=\"1.0\" encoding=\"utf-8\"?>'; ?>\n"
	);

	/**
	 * List of inline tags
	 *
	 * @var array
	 */
	public static $aInlineTags = array
	(
		'a', 'strong', 'b', 'em', 'i', 'h1', 'h2', 'h3', 'h4',
		'h5', 'h6', 'span', 'title', 'li', 'dt', 'dd', 'code',
		'cite', 'td', 'th', 'abbr', 'acronym', 'legend', 'label'
	);

	/**
	 * List of closed tags (like br, link...)
	 *
	 * @var array
	 */
	public static $aClosedTags = array('br', 'hr', 'link', 'meta', 'img', 'input');

	/**
	 * List of tags which can't be indented
	 *
	 * @var array
	 */
	public static $aNoIndentTags = array('pre', 'textarea');

	/**
	 * List of PHP blocks
	 *
	 * @var array
	 *
	 */
	protected static $aPhpBlocks = array('if', 'else', 'elseif', 'while', 'switch', 'for', 'do', 'function');

	/**
	 * List of custom blocks
	 *
	 * @var array
	 *
	 */
  protected static $aCustomBlocks = array();

	/**
	 * Export array
	 *
	 * @return string
	 */
	protected function arrayExport()
	{
		$sArray = 'array (';
		$aArray = $aNArray = array();
		foreach (func_get_args() as $aArg)
			$aArray = array_merge($aArray, $aArg);
		foreach ($aArray as $sKey => $sValue) {
			if (!preg_match('/[\'$"()]/', $sValue))
				$sValue = "'$sValue'";
			$aNArray[] = "'$sKey' => $sValue";
		}
		$sArray .= implode(', ', $aNArray).')';
		return $sArray;
	}
}
