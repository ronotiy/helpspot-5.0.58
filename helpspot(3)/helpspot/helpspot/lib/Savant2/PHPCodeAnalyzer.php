<?php
/**
* A class for performing code analysis for php scripts
* It is designed to be the heart of a code limiting script
* to use with Savant {@link http://phpsavant.com}.
*
* This code should be php4 compatiable but i've only run it in php5 and some of the Tokenizer constants have changed
*
* @author	Joshua Eichorn <josh@bluga.net>
* @copyright	Joshua Eichorn 2004
*
* @license http://www.gnu.org/copyleft/lesser.html LGPL
*/

/**#@+
* compat tokeniezer defines
*/
if (! defined('T_OLD_FUNCTION')) {
    define('T_OLD_FUNCTION', T_FUNCTION);
}
if (! defined('T_ML_COMMENT')) {
    define('T_ML_COMMENT', T_COMMENT);
} else {
    define('T_DOC_COMMENT', T_ML_COMMENT);
}
/**#@-*/

/**
 * Code Analysis class.
 *
 * Example Usage:
 * <code>
 * $analyzer = new PHPCodeAnalyzer();
 * $analyzer->source = file_get_contents(__FILE__);
 * $analyzer->analyze();
 * print_r($analyzer->calledMethods);
 * </code>
 *
 * @todo is it important to grab the details from creating new functions defines classes?
 * @todo support php5 only stuff like interface
 *
 * @version	0.4
 * @license http://www.gnu.org/copyleft/lesser.html LGPL
 * @copyright	Joshua Eichorn 2004
 * @author	Joshua Eichorn <josh@bluga.net>
 */
class PHPCodeAnalyzer
{
    /**
     * Source code to analyze.
     */
    public $source = '';

    /**
     * functions called.
     */
    public $calledFunctions = [];

    /**
     * Called constructs.
     */
    public $calledConstructs = [];

    /**
     * methods called.
     */
    public $calledMethods = [];

    /**
     * static methods called.
     */
    public $calledStaticMethods = [];

    /**
     * new classes instantiated.
     */
    public $classesInstantiated = [];

    /**
     * variables used.
     */
    public $usedVariables = [];

    /**
     * member variables used.
     */
    public $usedMemberVariables = [];

    /**
     * classes created.
     */
    public $createdClasses = [];

    /**
     * functions created.
     */
    public $createdFunctions = [];

    /**
     * Files includes or requried.
     */
    public $filesIncluded = [];

    // private variables
    /**#@+
    * @access private
    */
    public $currentString = null;

    public $currentStrings = null;

    public $currentVar = false;

    public $staticClass = false;

    public $inNew = false;

    public $inInclude = false;

    public $lineNumber = 1;

    /**#@-*/

    /**
     * parse source filling informational arrays.
     */
    public function analyze()
    {
        $tokens = token_get_all($this->source);

        // mapping of token to method to call
        $handleMap = [
            T_STRING => 'handleString',
            T_CONSTANT_ENCAPSED_STRING => 'handleString',
            T_ENCAPSED_AND_WHITESPACE => 'handleString',
            T_CHARACTER => 'handleString',
            T_NUM_STRING => 'handleString',
            T_DNUMBER => 'handleString',
            T_FUNC_C => 'handleString',
            T_CLASS_C => 'handleString',
            T_FILE => 'handleString',
            T_LINE => 'handleString',
            T_DOUBLE_ARROW => 'handleString',

            T_DOUBLE_COLON => 'handleDoubleColon',
            T_NEW => 'handleNew',
            T_OBJECT_OPERATOR => 'handleObjectOperator',
            T_VARIABLE => 'handleVariable',
            T_FUNCTION => 'handleFunction',
            T_OLD_FUNCTION => 'handleFunction',
            T_CLASS => 'handleClass',
            T_WHITESPACE => 'handleWhitespace',
            T_INLINE_HTML => 'handleWhitespace',
            T_OPEN_TAG => 'handleWhitespace',
            T_CLOSE_TAG => 'handleWhitespace',

            T_AS	=> 'handleAs',

            T_ECHO => 'handleConstruct',
            T_EVAL => 'handleConstruct',
            T_UNSET => 'handleConstruct',
            T_ISSET => 'handleConstruct',
            T_PRINT => 'handleConstruct',
            T_FOR	=> 'handleConstruct',
            T_FOREACH=> 'handleConstruct',
            T_EMPTY	=> 'handleConstruct',
            T_EXIT	=> 'handleConstruct',
            T_CASE	=> 'handleConstruct',
            T_GLOBAL=> 'handleConstruct',
            T_UNSET	=> 'handleConstruct',
            T_WHILE	=> 'handleConstruct',
            T_DO	=> 'handleConstruct',
            T_IF	=> 'handleConstruct',
            T_LIST	=> 'handleConstruct',
            T_RETURN=> 'handleConstruct',
            T_STATIC=> 'handleConstruct',
            T_ENDFOR=> 'handleConstruct',
            T_ENDFOREACH=> 'handleConstruct',
            T_ENDIF=> 'handleConstruct',
            T_ENDSWITCH=> 'handleConstruct',
            T_ENDWHILE=> 'handleConstruct',

            T_INCLUDE => 'handleInclude',
            T_INCLUDE_ONCE => 'handleInclude',
            T_REQUIRE => 'handleInclude',
            T_REQUIRE_ONCE => 'handleInclude',
        ];

        foreach ($tokens as $token) {
            if (is_string($token)) {
                // we have a simple 1-character token
                $this->handleSimpleToken($token);
            } else {
                list($id, $text) = $token;
                if (isset($handleMap[$id])) {
                    $call = $handleMap[$id];
                    $this->$call($id, $text);
                }
                /*else
                {
                    echo token_name($id).": $text<br>\n";
                }*/
            }
        }
    }

    /**
     * Handle a 1 char token.
     */
    public function handleSimpleToken($token)
    {
        if ($token !== ';') {
            $this->currentStrings .= $token;
        }
        switch ($token) {
            case '(':
                // method is called
                if ($this->staticClass !== false) {
                    if (! isset($this->calledStaticMethods[$this->staticClass][$this->currentString])) {
                        $this->calledStaticMethods[$this->staticClass][$this->currentString]
                            = [];
                    }
                    $this->calledStaticMethods[$this->staticClass][$this->currentString][]
                        = $this->lineNumber;
                    $this->staticClass = false;
                } elseif ($this->currentVar !== false) {
                    if (! isset($this->calledMethods[$this->currentVar][$this->currentString])) {
                        $this->calledMethods[$this->currentVar][$this->currentString] = [];
                    }
                    $this->calledMethods[$this->currentVar][$this->currentString][] = $this->lineNumber;
                    $this->currentVar = false;
                } elseif ($this->inNew !== false) {
                    $this->classInstantiated();
                } elseif ($this->currentString !== null) {
                    $this->functionCalled();
                }
                //$this->currentString = null;
            break;
            case '=':
            case ';':
                if ($this->inNew !== false) {
                    $this->classInstantiated();
                } elseif ($this->inInclude !== false) {
                    $this->fileIncluded();
                } elseif ($this->currentVar !== false) {
                    $this->useMemberVar();
                }
                $this->currentString = null;
                $this->currentStrings = null;

            break;
        }
    }

    /**
     * handle includes and requires.
     */
    public function handleInclude($id, $text)
    {
        $this->inInclude = true;
        $this->handleConstruct($id, $text);
    }

    /**
     * handle String tokens.
     */
    public function handleString($id, $text)
    {
        $this->currentString = $text;
        $this->currentStrings .= $text;
    }

    /**
     * handle variables.
     */
    public function handleVariable($id, $text)
    {
        $this->currentString = $text;
        $this->currentStrings .= $text;
        $this->useVariable();
    }

    /**
     * handle Double Colon tokens.
     */
    public function handleDoubleColon($id, $text)
    {
        $this->staticClass = $this->currentString;
        $this->currentString = null;
    }

    /**
     * handle new keyword.
     */
    public function handleNew($id, $text)
    {
        $this->inNew = true;
    }

    /**
     * handle function.
     */
    public function handleFunction($id, $text)
    {
        $this->createdFunctions[] = $this->lineNumber;
    }

    /**
     * handle class.
     */
    public function handleClass($id, $text)
    {
        $this->createdClasses[] = $this->lineNumber;
    }

    /**
     * Handle ->.
     */
    public function handleObjectOperator($id, $text)
    {
        $this->currentVar = $this->currentString;
        $this->currentString = null;
        $this->currentStrings .= $text;
    }

    /**
     * handle whitespace to figure out line counts.
     */
    public function handleWhitespace($id, $text)
    {
        $this->lineNumber += substr_count($text, "\n");
        if ($id == T_CLOSE_TAG) {
            $this->handleSimpleToken(';');
        }
    }

    /**
     * as has been used we must have a var before it.
     */
    public function handleAs($id, $text)
    {
        $this->handleSimpleToken(';');
    }

    /**
     * a language construct has been called record it.
     */
    public function handleConstruct($id, $construct)
    {
        if (! isset($this->calledConstructs[$construct])) {
            $this->calledConstructs[$construct] = [];
        }
        $this->calledConstructs[$construct][] = $this->lineNumber;
        $this->currentString = null;
    }

    /**
     * a class was Instantiated record it.
     */
    public function classInstantiated()
    {
        if (! isset($this->classesInstantiated[$this->currentString])) {
            $this->classesInstantiated[$this->currentString] = [];
        }
        $this->classesInstantiated[$this->currentString][] = $this->lineNumber;
        $this->inNew = false;
    }

    /**
     * a file was included record it.
     */
    public function fileIncluded()
    {
        if (! isset($this->filesIncluded[$this->currentStrings])) {
            $this->filesIncluded[$this->currentStrings] = [];
        }
        $this->filesIncluded[$this->currentStrings][] = $this->lineNumber;
        $this->inInclude = false;
        $this->currentString = null;
        $this->currentStrings = '';
    }

    /**
     * a function was called record it.
     */
    public function functionCalled($id = false)
    {
        if (! isset($this->calledFunctions[$this->currentString])) {
            $this->calledFunctions[$this->currentString] = [];
        }
        $this->calledFunctions[$this->currentString][] = $this->lineNumber;
        $this->currentString = null;
    }

    /**
     * we used a member variable record it.
     */
    public function useMemberVar()
    {
        if (! isset($this->usedMemberVariables[$this->currentVar][$this->currentString])) {
            $this->usedMemberVariables[$this->currentVar][$this->currentString] = [];
        }
        $this->usedMemberVariables[$this->currentVar][$this->currentString][] = $this->lineNumber;
        $this->currentVar = false;
        $this->currentString = null;
    }

    /**
     * we used a variable record it.
     */
    public function useVariable()
    {
        if (! isset($this->usedVariables[$this->currentString])) {
            $this->usedVariables[$this->currentString] = [];
        }
        $this->usedVariables[$this->currentString][] = $this->lineNumber;
    }
}
?> 
