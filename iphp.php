<?php

	require_once(dirname(__FILE__) . '/lib/temp_file.php');
// vim: set expandtab tabstop=4 shiftwidth=4:

/**
 * The iphp shell is an interactive PHP shell for working with your php applications.
 *
 * The shell includes readline support with tab-completion and history.
 *
 * To start the shell, simply run this script from your command line.
 *
 * Use ctl-d to exit the shell, or enter the command "exit".
 */
class iphp
{
    protected $lastResult = NULL;
    protected $lastCommand = NULL;
    protected $prompt = '> ';
    protected $autocompleteList = array();
    protected $options = array();
		protected $specialCommands = array('exit', 'reload!', 'help');
    protected $phpExecutable = null;
		protected $running = true;

    const OPT_TAGS_FILE     = 'tags';
    const OPT_REQUIRE       = 'require';
    

    const OPT_TMP_DIR       = 'tmp_dir';
    const OPT_PROMPT_HEADER = 'prompt_header';


    /**
     * Constructor
     *
     * @param array Options hash:
     *                  - OPT_TAGS_FILE: the path to a tags file produce with ctags for your project -- all tags will be used for autocomplete
     */
    public function __construct($options = array()) {
        $this->initialize($options);
    }

    private function initialize($options = array()) {
        $this->initializeOptions($options);
        $this->initializeTempFiles();
        $this->initializeAutocompletion();
        $this->initializeTags();
        $this->phpExecutable = self::PHPExecutableLocation();
        $this->requireFiles();
    }

    private function initializeOptions($options = array()) {
        // merge opts
        $this->options = array_merge(array(
                                            // default options
                                            self::OPT_TAGS_FILE     => NULL,
                                            self::OPT_REQUIRE       => NULL,
                                            self::OPT_TMP_DIR       => NULL,
                                            self::OPT_PROMPT_HEADER => $this->getPromptHeader(),
                                            self::OPT_PHP_BIN       => $this->getPhpBin(),
                                          ), $options);
    }



    private function initializeTempFiles() {
       TempFile::fileName('command');
       TempFile::fileName('requires');
       TempFile::fileName('state');
    }


    private function initializeAutocompletion() {
        $phpList = get_defined_functions();
        $this->autocompleteList = array_merge($this->autocompleteList, $phpList['internal']);
        $this->autocompleteList = array_merge($this->autocompleteList, get_defined_constants());
        $this->autocompleteList = array_merge($this->autocompleteList, get_declared_classes());
        $this->autocompleteList = array_merge($this->autocompleteList, get_declared_interfaces());
    }

    private function initializeTags() {
        $tagsFile = $this->options[self::OPT_TAGS_FILE];
        if (file_exists($tagsFile))
        {
            $tags = array();
            $tagLines = file($tagsFile);
            foreach ($tagLines as $tag) {
                $matches = array();
                if (preg_match('/^([A-z0-9][^\W]*)\W.*/', $tag, $matches))
                {
                    $tags[] = $matches[1];
                }
            }
            $this->autocompleteList = array_merge($this->autocompleteList, $tags);
        }
    }

    private function requireFiles() {
        if ($this->options[self::OPT_REQUIRE])
        {
            if (!is_array($this->options[self::OPT_REQUIRE]))
            {
                $this->options[self::OPT_REQUIRE] = array($this->options[self::OPT_REQUIRE]);
            }
						TempFile::writeToFile('requires', serialize($this->options[self::OPT_REQUIRE]));
        }
    }

    
		public function cleanUp() {
			TempFile::clear();
		}

    public function prompt()
    {
        return $this->prompt;
    }

    public function historyFile()
    {
        return getenv('HOME') . '/.iphpHistory';
    }

    public function readlineCallback($command)
    {
        if ($command === NULL) exit;
        $this->lastCommand = $command;
    }

    public function readlineCompleter($str)
    {
        return $this->autocompleteList;
    }
    
		public function specialCommands() {
			return $this->specialCommands;
		}

		private function process_special_commands($command) {
			switch($command) {
				case 'exit':
					$this->running = false;
				break;
				case 'reload!': 
					TempFile::reset('state');
					print("Cleaning Previous data\n");
				break;
				case 'help':
					//Handle Help here
					print("\n");
				break;
			}
		}


    public function doCommand($command)
    {
				if(in_array($command, $this->specialCommands)) {
					$this->process_special_commands($command);
					return;
				}
        print "\n";
        if (trim($command) == '')
        {
            return;
        }

        if(preg_match('/^(exit|die|quit|bye)(\(\))?;?$/', trim($command))){
            die("\n");
        }

        if (!empty($command) and function_exists('readline_add_history'))
        {
            readline_add_history($command);
            readline_write_history($this->historyFile());
        }

        $command = preg_replace('/^\//', '$_', $command);  // "/" as a command will just output the last result.

        $requires = unserialize(TempFile::readFromFile('requires'));
        if (!is_array($requires)) {
            $requires = array();
        }


				$replacments = array('{$command}' => $command, '{$requires}' => var_export($requires, true), '{$requiresFile}' => TempFile::fileName('requires'), '{$stateFile}' => TempFile::fileName('state'));
        $parsedCommand = str_replace(array_keys($replacments), array_values($replacments), self::getTemplate('command'));
				

        try {
            $_ = $this->lastResult;
						TempFile::writeToFile('command', $parsedCommand);
            $result = NULL;
            $output = array();

						$command_array = array($this->phpExecutable, TempFile::fileName('command'), '2>&1');
            $lastLine = exec(implode(' ', $command_array), $output, $result);

            if ($result != 0) throw( new Exception("Fatal error executing php: " . join("\n", $output)) );

            // boostrap requires environment of command
            $requires = unserialize(TempFile::readFromFile('requires'));
            foreach ($requires as $require) {
                if ($require === TempFile::fileName('command')) continue;
                require_once($require);
            }

            
            $lastState = unserialize(TempFile::readFromFile('state'));
            $this->lastResult = $lastState['_'];

            if ($lastState['__out'])
            {
                print $lastState['__out'] . "\n";
            }
            else
            {
                if (is_object($this->lastResult) && !is_callable(array($this->lastResult, '__toString')))
                {
                    print_r($this->lastResult) . "\n";
                }
                else
                {
                    print $this->lastResult . "\n";
                }
            }

            // after the eval, we might have new classes. Only update it if real readline is enabled
            if (!empty($this->autocompleteList)) $this->autocompleteList = array_merge($this->autocompleteList, get_declared_classes());
        } catch (Exception $e) {
            print "Uncaught exception with command:\n" . $e->getMessage() . "\n";
        }
    }

    private function myReadline()
    {
        $this->lastCommand = NULL;
        readline_callback_handler_install($this->prompt, array($this, 'readlineCallback'));
        while ($this->lastCommand === NULL) {
            $w = NULL;
            $e = NULL;
            $n = @stream_select($r = array(STDIN), $w, $e, NULL);       // @ to silence warning on ctl-c
            if ($n === false) break;                                    // ctl-c or other signal
            if (in_array(STDIN, $r))
            {
                readline_callback_read_char();
            }
        }
        readline_callback_handler_remove();
        return $this->lastCommand;
    }

    public function readline()
    {
        if (function_exists('readline'))
        {
            $command = $this->myReadline();
        }
        else
        {
            $command = rtrim( fgets( STDIN ), "\n" );
            // catch ctl-d
            if (strlen($command) == 0)
            {
                exit;
            }
        }
        return $command;
    }

    public function stop()
    {
        // no-op
    }

    public static function main($options = array())
    {
        $shell = new iphp($options);

        // install signal handlers if possible
        declare(ticks = 1);
        if (function_exists('pcntl_signal'))
        {
            pcntl_signal(SIGINT, array($shell, 'stop'));
        }

				$special = implode(', ', $shell->specialCommands());
        print self::getTemplate('help');

        // readline history
        if (function_exists('readline_read_history'))
        {
            readline_read_history($shell->historyFile());   // doesn't seem to work, even though readline_list_history() shows the read items!
        }
        // install tab-complete
        if (function_exists('readline_completion_function'))
        {
            readline_completion_function(array($shell, 'readlineCompleter'));
        }
        while ($shell->running)
        {
            $shell->doCommand($shell->readline());
        }
				print("Good Bye\n");
				$shell->cleanUp();
    }


    public static function PHPExecutableLocation()
    {
        if(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $phpExecutableName = 'php.exe';
        } else {
            $phpExecutableName = 'php';
        }
        return PHP_BINDIR . DIRECTORY_SEPARATOR . $phpExecutableName;
    }


		private static function getTemplate($file) {
			return file_get_contents(dirname(__FILE__) . '/templates/'. $file . '.tmpl');
		}


}
