<?php
namespace App\Commands;
use V2\Core\Logs\Logger;
use Symfony\Component\Console\Command\Command as base;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Exception;
abstract class Command extends base
{
    protected $_output = null;
    protected $_input  = null;
    protected $prefix_filename_log = "";
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
    	$this->_output   = $output;
        $this->_input    = $input;
    }
    public function __call($func, $arg)
    {
        $func = strtolower($func);

        if (in_array($func, ["log", "error", "info", "warn"])) {
            $string = $arg[0];
            if ($this->_input instanceof InputInterface and null != $this->_input->hasOption("log")) {
                $filename = $this->prefix_filename_log;
                if ($this->_input->hasArgument("processId")) {
                    if (!empty($this->_input->getArgument("processId"))) {
                        $filename = $this->_input->getArgument("processId")."-".$filename;
                    }
                } 
                if ($filename!="") {
                    Logger::$func($string,$filename);
                }else{
                    Logger::$func($string);
                }
            }
            switch ($func) {
                default:case 'log':case 'info':$string = "<info>{$string}</info>"; break;
                case 'error':$string = "<error>{$string}</error>"; break;
                case 'warn':$string = "<comment>{$string}</comment>"; break;
            }
            if ($this->_output instanceof OutputInterface) {
                $this->_output->writeln($string);
            }else{
            	echo $string;
            }
        }else{
        	throw new Exception("Bad Method", 1);
        }
    }
    public function argument($arg)
    {
    	if ($this->_input->hasArgument($arg)) {
    		return $this->_input->getArgument($arg);
    	}
    	return null;
    }
    
    public function option($arg)
    {   
        if ($this->_input->hasOption($arg)) {
    		return $this->_input->getOption($arg);
    	}
    	return null;
    }
}
