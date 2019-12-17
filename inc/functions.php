<?php

spl_autoload_register( function ( $className ) {
    $oAutoloader = cpautoloader::getInstance();
    $ret         = $oAutoloader->load( $className );
    if ( $ret == false ) {
        //whatever
    }

    return $ret;
} );



/**
 * Class cpautoloader
 */
class cpautoloader {
    private static $instance = null;

    public static function getInstance() {
        if ( self::$instance === null ) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    private $_aClassPath = [
        /**
         * KEY: classname without \ at the beginning
         * VALUE: path, start from the BASE directory without / at the beginning
         */
        //'core\inc\super'                                       => 'core/inc/super.php',
    ];


    /**
     * @param $classNameOriginal
     *
     * @return bool
     */
    public function load( $classNameOriginal ) {

        $oConfig = getConfig();

        //Replace slashes
        $phpExtension = ".php";
        $className    = $this->_replaceSlashes( $classNameOriginal );


        //try find in each module in the modules/rs/* folder
        $modulePath = $oConfig->getBaseDir()."modules/rs";
        $aModulePath = glob($modulePath."/*", GLOB_ONLYDIR );
        foreach($aModulePath as $rootpath)
        {
            $rootpath.= DIRECTORY_SEPARATOR;


            if ( isset( $this->_aClassPath[$classNameOriginal] ) ) {
                $file = $rootpath . $this->_aClassPath[$classNameOriginal];
                if ( $this->_requireFileIfExist( $file ) ) {
                    return true;
                }
            }

            $file = $rootpath . "inc/" . strtolower($className) . $phpExtension;
            if ( $this->_requireFileIfExist( $file ) ) {
                return true;
            }
            $file = $rootpath . "inc/" . $className . $phpExtension;
            if ( $this->_requireFileIfExist( $file ) ) {
                return true;
            }


            $file = $rootpath . strtolower($className) . $phpExtension;
            if ( $this->_requireFileIfExist( $file ) ) {
                return true;
            }
            $file = $rootpath . $className . $phpExtension;
            if ( $this->_requireFileIfExist( $file ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Replace the back slashes(\) using DIRECTORY_SEPARATOR constant
     *
     * @param $className
     *
     * @return mixed
     */
    protected function _replaceSlashes( $className ) {
        return str_replace( '\\', DIRECTORY_SEPARATOR, $className );
    }

    /**
     * include the file if require.
     *
     * @param $file
     *
     * @return bool
     */
    protected function _requireFileIfExist( $file ) {

        if ( file_exists( $file ) ) {
            require_once $file;

            return true;
        }

        return false;
    }
}
