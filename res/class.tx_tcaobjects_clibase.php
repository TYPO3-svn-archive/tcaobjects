<?php
/**
 * Base class for cli scripts
 *
 * $Id: class.tx_ptclibase_clibase.php,v 1.1 2007/10/31 08:21:21 ry44 Exp $
 *
 * @author 	Fabrizio Branca <mail@fabrizio-branca.de>
 */
class tx_tcaobjects_clibase {

	protected $argv;
	protected $verbose = false;
	protected $TerminalStyles = array();
	protected $eol = "\n";


	/**
	 * TODO: automatische Ausgabe einer Hilfe bei "-h"
	 * Parameter könnten irgendwo definiert und beschrieben werden
	 *
	 * $this->commandlineoptions[] = array ( '-v' => 'Switch verbose mode on');
	 */

	/**
	 * Constructor
	 *
	 * @param 	array	command line arguments
	 * @param 	int		status code (reference)
	 * @param 	mixed	(optional) additional parameter
	 * @author 	Fabrizio Branca <mail@fabrizio-branca.de>
	 */
	public function __construct($argv, &$statuscode, $param = false){
		// taken from ezCLI
		$this->TerminalStyles = array(
			'warning' => "\033[1;35m", 		'warning-end' => "\033[0;39m",
			'error' => "\033[1;31m", 		'error-end' => "\033[0;39m",
			'failure' => "\033[1;31m", 		'failure-end' => "\033[0;39m",
			'notice' => "\033[0;32m", 		'notice-end' => "\033[0;39m",
			'debug' => "\033[0;30m", 		'debug-end' => "\033[0;39m",
			'timing' => "\033[1;34m", 		'timing-end' => "\033[0;39m",
			'success' => "\033[1;32m", 		'success-end' => "\033[0;39m",
			'file' => "\033[1;38m", 		'file-end' => "\033[0;39m",
			'dir' => "\033[1;34m", 			'dir-end' => "\033[0;39m",
			'link' => "\033[0;36m", 		'link-end' => "\033[0;39m",
			'exe' => "\033[1;32m", 			'exe-end' => "\033[0;39m",
			'archive' => "\033[1;31m",	 	'archive-end' => "\033[0;39m",
			'image' => "\033[1;35m", 		'image-end' => "\033[0;39m",
			
			// colors
			'red' => "\033[1;31m", 			'red-end' => "\033[0;39m",
			'green' => "\033[1;32m", 		'green-end' => "\033[0;39m",
			'yellow' => "\033[1;33m", 		'yellow-end' => "\033[0;39m",
			'blue' => "\033[1;34m", 		'blue-end' => "\033[0;39m",
			'magenta' => "\033[1;35m", 		'magenta-end' => "\033[0;39m",
			'cyan' => "\033[1;36m", 		'cyan-end' => "\033[0;39m",
			'white' => "\033[1;37m", 		'white-end' => "\033[0;39m",
			'gray' => "\033[1;30m", 		'gray-end' => "\033[0;39m",
			
			// dark colors
			'dark-red' => "\033[0;31m", 	'dark-red-end' => "\033[0;39m",
			'dark-green' => "\033[0;32m", 	'dark-green-end' => "\033[0;39m",
			'dark-yellow' => "\033[0;33m", 	'dark-yellow-end' => "\033[0;39m",
			'dark-blue' => "\033[0;34m", 	'dark-blue-end' => "\033[0;39m",
			'dark-magenta' => "\033[0;35m", 'dark-magenta-end' => "\033[0;39m",
			'dark-cyan' => "\033[0;36m", 	'dark-cyan-end' => "\033[0;39m",
			'dark-white' => "\033[0;37m", 	'dark-white-end' => "\033[0;39m",
			'dark-gray' => "\033[0;30m", 	'dark-gray-end' => "\033[0;39m",
			
			// backgrounds
			'red-bg' => "\033[1;41m", 		'red-bg-end' => "\033[0;39m",
			'green-bg' => "\033[1;42m", 	'green-bg-end' => "\033[0;39m",
			'yellow-bg' => "\033[1;43m", 	'yellow-bg-end' => "\033[0;39m",
			'blue-bg' => "\033[1;44m", 		'blue-bg-end' => "\033[0;39m",
			'magenta-bg' => "\033[1;45m", 	'magenta-bg-end' => "\033[0;39m",
			'cyan-bg' => "\033[1;46m", 		'cyan-bg-end' => "\033[0;39m",
			'white-bg' => "\033[1;47m", 	'white-bg-end' => "\033[0;39m",
			
			'text' => "\033[0;39m", 		'text-end' => "\033[0;39m",
			'variable' => "\033[1;34m", 	'variable-end' => "\033[0;39m",
			'symbol' => "\033[0;37m", 		'symbol-end' => "\033[0;39m",
			'emphasize' => "\033[1;38m", 	'emphasize-end' => "\033[0;39m",
			'header' => "\033[1;38m", 		'header-end' => "\033[0;39m",
			'strong' => "\033[1;39m", 		'strong-end' => "\033[0;39m",
			'mark' => "\033[1;30m", 		'mark-end' => "\033[0;39m",
			'bold' => "\033[1;38m", 		'bold-end' => "\033[0;39m",
			'italic' => "\033[0;39m", 		'italic-end' => "\033[0;39m",
			'underline' => "\033[0;39m", 	'underline-end' => "\033[0;39m",
			'paragraph' => "\033[0;39m", 	'paragraph-end' => "\033[0;39m",
			'normal' => "\033[0;39m", 		'normal-end' => "\033[0;39m",
		);

		$this->argv = $argv;

		if (in_array('-v', $this->argv)) $this->verbose = true;

		if ($this->verbose) fwrite (STDOUT, get_class($this)." constructed [PID: ".posix_getpid()."]\n");

		/*
		if (in_array('-run', $this->argv)) {
			if (method_exists('run')) $this->run($param);
		}
		*/
	}

	/**
	 * Write text to the STDOUT if verbose mode is on
	 * Use this instead of fwrite!
	 *
	 * @param   string  text
	 * @param   bool    (optional) true to add end of line, default: true
	 * @param	string	(optional) terminalstyle, default: none
	 * @return  void
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 * @since	06.09.2007
	 */
	public function output($text, $addEol = true, $style = ''){
		if ($style!='') $this->switchStyle($style);
		if ($this->verbose) fwrite(STDOUT, $text . ($addEol ? $this->eol:''));
		if ($style!='') $this->switchStyle($style.'-end');
	}

	/**
	 * Switches terminal style. See constructor for modes
	 *
	 * @param 	string	Style
	 * @author 	Fabrizio Branca <mail@fabrizio-branca.de>
	 */
	public function switchStyle($name){
		if (isset($this->TerminalStyles[$name])){
			$this->output($this->TerminalStyles[$name], 0);
		}
	}

	/**
	 * Checks if the given string is in the
	 *
	 * @param 	string	parameter
	 * @return 	bool
	 * @author	Fabrizio Branca <mail@fabrizio-branca.de>
	 */
	public function isParameter($parameter){
		return in_array($parameter, $this->argv);
	}

	/**
	 * Returns the next parameter to a given parameter
	 *
	 * @param 	string	parameter
	 * @return 	string|false 	next parameter
	 * @author 	Fabrizio Branca <mail@fabrizio-branca.de>
	 */
	public function valueIfParameter($parameter){
		if (($pos = array_search($parameter, $this->argv)) !== false) {
			$parameter = $this->argv[$pos+1];
			$parameter = ($parameter[0] == '\'') ? substr($this->argv[$pos+1],1,-1) : $parameter;
			return $parameter;
		} else {
			return false;
		}
	}

}

?>