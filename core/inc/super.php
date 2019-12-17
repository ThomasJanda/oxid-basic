<?php
namespace core\inc
{
    class super
    {
        /** @var \cconfig3 $_oconfig */
        protected $_oconfig = null;


        /**
         * super constructor.
         *
         * @param \cconfig3 $oConfig
         */
        public function __construct($oConfig)
        {
            $this->_oconfig = $oConfig;
        }


        /**
         * super destructor.
         */
        public function __destruct()
        {
            if ($this->_oconfig !== null) {
                unset($this->_oconfig);
                $this->_oconfig = null;
            }
        }

        /**
         * Unserialize
         */
        public function __wakeup()
        {
            $this->_oconfig = getConfig();
        }

        /**
         * Remove _oconfig and _omail settings
         *
         * Serialize
         */
        public function __sleep()
        {
            $vars = get_object_vars($this);
            unset($vars['_oconfig']);

            return array_keys($vars);
        }


        /**
         * @return \cconfig3
         */
        public function getConfig()
        {
            return $this->_oconfig;
        }


        /**
         * @param \cconfig3 $oConfig
         */
        public function setConfig(\cconfig3 $oConfig)
        {
            $this->_oconfig = $oConfig;
        }


        /**
         * @return \awsmail
         */
        public function getMail()
        {
            return false;
        }
        public static function log($message)
        {

        }
    }
}
