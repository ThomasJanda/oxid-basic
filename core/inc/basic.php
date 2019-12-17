<?php
namespace core\inc
{
    class basic extends super
    {

        /**
         * basic constructor.
         *
         * @param \cconfig3    $oconfig
         * @param \ccustomers3 $ocustomer
         */
        public function __construct($oconfig, $ocustomer)
        {
            parent::__construct($oconfig);
        }

        /**
         * basic destructor
         */
        public function __destruct()
        {
            parent::__destruct();
        }

        /**
         * Unserialize
         */
        public function __wakeup()
        {
            parent::__wakeup();
        }

        /**
         * Remove _ocustomer and mail settings
         *
         * Serialize
         */
        public function __sleep()
        {
            $vars = get_object_vars($this);
            //unset($vars['_ocustomer']);

            return array_intersect(parent::__sleep(), array_keys($vars));
        }


        /**
         * @return \ccustomers3
         */
        public function getCustomer()
        {
            return null;
        }

        /**
         * @param \ccustomers3 $oCustomer
         */
        public function setCustomer($oCustomer)
        {
        }

    }
}
