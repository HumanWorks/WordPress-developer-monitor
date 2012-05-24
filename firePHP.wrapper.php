<?php
/*
 * Wrapper for FirePHP class in case you have problems with headers sent (usually a problem with some NginX and Varnish setups
 */
 class FirePHP {
 	
 	public $buffer;
 	protected static $instance = null;
 	
 	public function log($txt){
 		$this->buffer .= $txt . "\n";
 	}
 	
 	public function table($txt, $data){
 		$this->log($txt);
 		$this->log(var_export($data, 1));	
 	}
 	
 	public function group($txt, $options){
 		$this->log($txt);
 	}
 	
 	public function groupEnd(){
 		return;	
 	}
 	
 	public static function getInstance($AutoCreate = false){
        if ($AutoCreate===true && !self::$instance) {
            self::init();
        }
        return self::$instance;
    }
    
    public static function init(){
        return self::setInstance(new self());
    }
	
	public static function setInstance($instance){
        return self::$instance = $instance;
    }    
 }