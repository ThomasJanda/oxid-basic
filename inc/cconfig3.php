<?php

use OxidEsales\Eshop\Core\Request;
use OxidEsales\EshopCommunity\Core\Registry;

if ( !function_exists( 'getConfig' ) ) {
    function getConfig() {
        return \cconfig3::getInstance();
    }
}


/**
 * Class cconfig3
 */
class cconfig3 {
    // region Singleton

    public function __construct()
    {
        include __DIR__."/../../../../config.inc.php";
    }

    static $_instance = null;
    public static function getInstance() {
        if(cconfig3::$_instance===null)
            cconfig3::$_instance = new cconfig3();
        return cconfig3::$_instance;
    }



    protected $_globalValues = [];
    public function getGlobalValue( $key ) {
        if ( array_key_exists( $key, $this->_globalValues ) ) {
            return $this->_globalValues[$key];
        }

        return false;
    }
    public function setGlobalValue( $key, $value ) {
        $this->_globalValues[$key] = $value;
    }



    /**
     * @param string $name Name of the parameter
     * @param        $default
     *
     * @return array|string Value that is found in the Global Arrays
     *
     * 2016-08-04
     * change from static to public,
     * otherwise phpstorm do not display this method in the autocomplete box
     * i have not found a call that need static in the base project
     */
    public function getRequestParameter( $name, $default = null ) {

        $oConfig = Registry::getConfig();
        $request = oxNew(Request::class);
        return $request->getRequestEscapedParameter($name, $default);
    }

    public function hasRequestParameter( $name ) {

        if($this->getRequestParameter($name)!==null)
            return true;
        return false;
    }

    /**
     * Returns the request parameter escaped by mysqli
     *
     * @param $name
     * @param $default
     *
     * @return array|string
     */
    public function getRequestParameterEscaped( $name, $default = null ) {
        $string = $this->getRequestParameter( $name, $default );
        $string = $this->escapeString($string);
        return $string;
    }

    /**
     * @param $name
     *
     * @return string
     *
     * 2016-08-04
     * change from static to public,
     * otherwise phpstorm do not display this method in the autocomplete box
     * i have not found a call that need static in the base project
     */
    public function getRequestFile( $name ) {
        $file = null;
        if ( isset( $_FILES[$name] ) ) {
            $file = $_FILES[$name];
        }

        return $file;
    }


    /**
     * returns SERVER informations
     *
     * @param string|null $sServVar
     *
     * @return string|array|null
     */
    public function getServerVar( $sServVar = null ) {
        $sValue = null;
        if ( isset( $_SERVER ) ) {
            if ( $sServVar && isset( $_SERVER[$sServVar] ) ) {
                $sValue = $_SERVER[$sServVar];
            } elseif ( !$sServVar ) {
                $sValue = $_SERVER;
            }
        }

        return $sValue;
    }


    /**
     * @var null|mysqli
     */
    protected $_MySqli=null;

    protected $_affectedrows=0;
    protected $dbHost = ''; // database host name
    protected $dbPort  = 3306; // tcp port to which the database is bound
    protected $dbName = ''; // database name
    protected $dbUser = ''; // database user name
    protected $dbPwd  = ''; // database user password
    protected $sShopURL     = ''; // eShop base url, required
    protected $sSSLShopURL  = null;            // eShop SSL url, optional
    protected $sAdminSSLURL = null;            // eShop Admin SSL url, optional
    protected $sShopDir     = '';
    protected $sCompileDir  = '';
    protected $iUtfMode = 1;

    /**
     * @return int
     */
    public function getAffectedRows() {
        return $this->_affectedrows;
    }


    /**
     * set connection data for the db
     * example:
     * $connectionData['dbHost'] = '123';
     * $connectionData['dbName'] = '123';
     * $connectionData['dbUser'] = '123';
     * $connectionData['dbPwd'] = '123';
     * $connectionData['iUtfMode'] = '123';
     *
     * @param array $connectionData
     */
    public function setConnectionData( $connectionData ) {
        if ( isset( $connectionData['dbHost'] ) ) {
            $this->dbHost = $connectionData['dbHost'];
        }
        if ( isset( $connectionData['dbPort'] ) ) {
            $this->dbPort = $connectionData['dbPort'];
        }
        if ( isset( $connectionData['dbName'] ) ) {
            $this->dbName = $connectionData['dbName'];
        }
        if ( isset( $connectionData['dbUser'] ) ) {
            $this->dbUser = $connectionData['dbUser'];
        }
        if ( isset( $connectionData['dbPwd'] ) ) {
            $this->dbPwd = $connectionData['dbPwd'];
        }
        if ( isset( $connectionData['iUtfMode'] ) ) {
            $this->iUtfMode = $connectionData['iUtfMode'];
        }
    }


    /**
     * return database connection data as an array
     *
     * @return string[]
     */
    public function getConnectionData() {
        $connectionData             = [];
        $connectionData['dbHost']   = $this->dbHost;
        $connectionData['dbPort']   = $this->dbPort;
        $connectionData['dbName']   = $this->dbName;
        $connectionData['dbUser']   = $this->dbUser;
        $connectionData['dbPwd']    = $this->dbPwd;
        $connectionData['iUtfMode'] = $this->iUtfMode;

        return $connectionData;
    }



    /**
     * disconnects to the database
     **/
    public function disconnectToDb() {
        $mysqli = $this->_MySqli;
        if ( $mysqli !== null ) {
            @$mysqli->close();
            $mysqli = null;
        }
    }


    /**
     * @return mysqli|null
     */
    public function getDbId() {
        return $this->connectToDb();
    }


    /**
     * connects to the database with the gifen settings
     **/
    protected function connectToDb() {
        /**  @var \mysqli $mysqli */
        $mysqli = $this->_MySqli;

        if ( $mysqli == null || is_object( $mysqli ) == false ) {

            $mysqli                              = new mysqli( $this->dbHost, $this->dbUser, $this->dbPwd, $this->dbName, $this->dbPort );

            if ( $mysqli->connect_errno ) {
                throw(new Exception( 'Connect Error: ' . $mysqli->connect_errno . ' ' . $mysqli->connect_error, $mysqli->connect_errno ));
            }
            if ( $this->iUtfMode == 1 ) {
                if ( !$mysqli->set_charset( "utf8" ) ) {
                    throw(new Exception( "Error loading character set utf8: " . $mysqli->error, $mysqli->errno ));
                }
            }
            $this->_MySqli = $mysqli;
        }

        return $mysqli;
    }

    /**
     * execute a sql statment and returns the mysqli result
     * if the connection to the db is not connected, the connection gets established automatically
     * if the connection is disconnected, the function tries 3 times to establish the connection
     *
     * @param      $sqlstring
     * @param int  $iRetries           = how ofter should try if a error occur.
     * @param bool $closeOnZeroResults should the connection to the db be closed if there are zero results
     * @return bool|mysqli_result|null
     */
    public function execute( $sqlstring, $iRetries = 1, bool $closeOnZeroResults = true ) {
        //TR 2015-05-20 add feature affected rows
        $this->_affectedrows = null;
        $rs                  = null;
        $mysqli              = null;

        try {
            $mysqli = $this->connectToDb();
            $rs     = $mysqli->query( $sqlstring );

            //TR 2015-05-20 add feature affected rows
            $this->_affectedrows = $mysqli->affected_rows;

            if ( $rs ) {
                if ( is_object( $rs ) == false || ($rs->num_rows == 0 && $closeOnZeroResults) ) {
                    $this->close( $rs );
                    $rs = null;
                }
            }
        } catch (Exception $exception) {
            return $this->_executeErrorHandling( $mysqli, $sqlstring, $iRetries );
        }

        return $rs;
    }

    /**
     * @param mysqli $mysqli
     *
     * @return bool|mysqli_result|null
     */
    protected function _executeErrorHandling( $mysqli, $sqlstring, $iRetries ) {
        if ( $iRetries > 0 ) {
            $this->disconnectToDb();
            $iRetries--;
            return $this->execute( $sqlstring, $iRetries );
        } else {
            //If is not a connect error, add the error to the stack of errors and send the error to NewRelic.
            $e                     = new mysqli_sql_exception( $mysqli->error, $mysqli->errno );
            $error                 = [];
            $error['exception']    = $e;
            $error['sqlstatment']  = $sqlstring;
            $error['mysql']        = $mysqli->errno;
            $this->_mysqli_error[] = $error;
        }
    }

    /**
     * Converts a resultset to a dataset with a fetch_object
     *
     * @param mysqli_result|boolean $rs
     *
     * @param bool                  $fetchObject if false, the fetch gonna by as array
     *
     * @return array
     */
    public function fetchRows( $rs, $fetchObject = true ) {
        $dataset = [];

        if ( $rs ) {
            while ( $row = $fetchObject ? $rs->fetch_object() : $rs->fetch_assoc() ) {
                $dataset[] = $row;
            }
        }

        return $dataset;
    }


    /**
     * execute a sqlstatment and returns only the first value on row 1, column 1
     * usefull if you want to get only one value like the number of rows form a table
     *
     * @param string $sqlstring
     *
     * @return string|null
     */
    public function getScalar( $sqlstring ) {
        //        self::log($sqlstring);

        $one = null;
        $rs  = $this->execute( $sqlstring );
        if ( $rs && $rs->num_rows > 0 ) {
            $row = $rs->fetch_row();
            $one = is_array( $row ) ? reset( $row ) : null;
            $this->close( $rs );
        }

        return $one;
    }

    /**
     * compatiblity reasons for the oxid shop
     *
     * @param $sqlstring
     *
     * @return string|null
     */
    public function getOne( $sqlstring ) {
        return $this->getScalar( $sqlstring );
    }



    /**
     * execute the insert, update, create, alter, delete statment
     *
     * @param string $sqlstring
     *
     * @return int affected rows
     */
    public function executeNoReturn( $sqlstring ) {
        $rs = $this->execute( $sqlstring );
        $af = $this->getAffectedRows();
        $this->close( $rs );

        return $af;
    }

    /**
     * execute a sqlstatment and retuns only the first row.
     * example: select * from oxarticles limit 0,1
     *
     * @param string $sqlstring
     * @param bool   $AsObject
     *
     * @return mixed|null|object|stdClass
     */
    public function getRow( $sqlstring, $AsObject = true ) {
        $row = null;
        $rs  = $this->execute( $sqlstring );
        if ( $rs && $rs->num_rows > 0 ) {
            try {
                if ( $AsObject ) {
                    $row = $rs->fetch_object();
                } else {
                    //MYSQLI_ASSOC, MYSQLI_NUM, or MYSQLI_BOTH
                    $row = $rs->fetch_array( MYSQLI_ASSOC );
                }

                $this->close( $rs );
            } catch (Exception $e) {
                $error['exception']    = $e;
                $error['sqlstatment']  = $sqlstring;
                $this->_mysqli_error[] = $error;
            }
        }

        return $row;
    }

    /**
     * Get all the rows as array of a query
     *
     * @param $sql
     *
     * @return array
     */
    public function getRows( $sql ) {
        $rs = $this->execute( $sql );

        return $this->fetchRows( $rs );
    }

    public function prepareStatement( $string ) {
        $mysqli = $this->connectToDb();

        return $mysqli->prepare( $string );
    }


    /**
     * close a recordset
     *
     * @param mysqli_result $rs
     */
    public function close( $rs ) {
        if ( $rs && is_object( $rs ) ) {
            @$rs->close();
        }
    }

    /**
     * escapes a string and mark special characters like '
     *
     * @param $string
     *
     * @return string
     */
    public function escapeString( $string ) {
        $mysqli = $this->connectToDb();

        if ( $mysqli ) {
            $string = $mysqli->real_escape_string( $string );
        }

        return $string;
    }

    /**
     * @var null|array
     */
    protected $_mysqli_error = null;

    /**
     * get the last mysqli error
     * example:
     * $error['exception']=$e;
     * $error['sqlstatment']=$sqlstring;
     * $error['mysql']=$mysqli->errno;
     *
     * @return null|array
     */
    public function mysqlLastError() {
        return $this->_mysqli_error;
    }

    /**
     * clear the last error messages
     **/
    public function mysqlClearLastError() {
        $this->_mysqli_error = null;
    }










    /**
     * BASE configuration
     */


    /**
     * returns the current url to BASE
     *
     * @return string
     **/
    public function getBaseUrl() {
        $ret = $this->sShopURL;
        if ( substr( $ret, strlen( $ret ) - 1 ) != "/" ) {
            $ret .= "/";
        }
        return $ret;
    }

    public function getBaseUrlOnly() {
        $url   = $this->getBaseUrl();
        $parts = parse_url( $url );

        return $parts['scheme'] . "://" . $parts['host'];
    }

    public function getBaseOutUrl() {
        $address = $this->getBaseUrl() . "out/";

        //canonicalize
        $address = explode( '/', $address );
        $keys    = array_keys( $address, '..' );

        foreach ( $keys AS $keypos => $key ) {
            array_splice( $address, $key - ($keypos * 2 + 1), 2 );
        }

        $address = implode( '/', $address );
        $address = str_replace( './', '', $address );

        return $address;
    }

    /**
     * returns the current url to the tmp folder
     *
     * @return string
     **/
    public function getBaseTmpUrl() {
        return $this->sCompileDir;
    }

    /**
     * @deprecated replaced by getBaseDir()
     * returns the absolute path to BASE
     *
     * @return string
     **/
    public function getShopBaseDir() {
        return $this->getBaseDir();
    }

    /**
     * returns the absolute path to BASE
     *
     * @return string
     **/
    public function getBaseDir() {
        return rtrim(realpath( $this->sShopDir ),"/")."/";
    }

    public function getBaseOutDir() {
        return realpath( $this->getBaseDir() . "out" ) . "/";
    }




    /**
     * @deprecated replaced by getBaseTmpDir()
     *
     * returns the absolute path to the tmp folder
     *
     * @return string
     **/
    public function getShopBaseTmpDir() {
        return $this->getBaseTmpDir();
    }

    /**
     * returns the absolute path to the tmp folder
     *
     * @return string
     **/
    public function getBaseTmpDir() {
        return rtrim($this->sCompileDir,"/")."/";
    }

    /**
     * returns the absolute path to the tmp folder
     *
     * @return string
     **/
    public function getBaseTemplateDir() {
        return $this->getBaseDir() . "views/tpl/";
    }

    /**
     * return the absolute path to the module folder
     * @return string
     */
    public function getBaseModulesDir() {
        return $this->getBaseDir() . "modules/";
    }

    /**
     * return the link to the module folder
     * @return string
     */
    public function getBaseModulesUrl() {
        return $this->getBaseUrl() . "modules/";
    }

    /**
     * return the absolute path to the standar tpl folder
     *
     * @param string $moduleid
     *
     * @return string
     */
    public function getBaseDirTemplate( $moduleid = "" ) {
        $ret = $this->getBaseDir() . "views/tpl/";
        if ( $moduleid != "" ) {
            $ret = $this->getBaseModulesDir() . $moduleid . "/views/tpl/";
        }

        return $ret;
    }

    /**
     * return the absolute path to the standard css folder
     *
     * @param string $moduleid
     *
     * @return string
     */
    public function getBaseDirTemplateSrcCss( $moduleid = "" ) {
        $ret = $this->getBaseDir() . "views/src/css/";
        if ( $moduleid != "" ) {
            $ret = $this->getBaseModulesDir() . $moduleid . "/views/src/css/";
        }

        return $ret;
    }

    /**
     * return the link to the standard css folder
     * @return string
     */
    public function getBaseUrlTemplateSrcCss( $moduleid = "" ) {
        $ret = $this->getBaseUrl() . "views/src/css/";
        if ( $moduleid != "" ) {
            $ret = $this->getBaseModulesUrl() . $moduleid . "/views/src/css/";
        }

        return $ret;
    }

    public function getBaseExternUrlTemplateSrcCss( $moduleid = "" ) {
        $ret = $this->getBaseExternUrl() . "views/src/css/";
        if ( $moduleid != "" ) {
            $ret = $this->getBaseExternModulesUrl() . $moduleid . "/views/src/css/";
        }

        return $ret;
    }

    /**
     * return the absolute path to the standard js folder
     * @return string
     */
    public function getBaseDirTemplateSrcJs( $moduleid = "" ) {
        $ret = $this->getBaseDir() . "views/src/js/";
        if ( $moduleid != "" ) {
            $ret = $this->getBaseModulesDir() . $moduleid . "/views/src/js/";
        }

        return $ret;
    }

    /**
     * return the link to the standard js folder
     * @return string
     */
    public function getBaseUrlTemplateSrcJs( $moduleid = "" ) {
        $ret = $this->getBaseUrl() . "views/src/js/";
        if ( $moduleid != "" ) {
            $ret = $this->getBaseModulesUrl() . $moduleid . "/views/src/js/";
        }

        return $ret;
    }

    public function getBaseExternUrlTemplateSrcJs( $moduleid = "" ) {
        $ret = $this->getBaseExternUrl() . "views/src/js/";
        if ( $moduleid != "" ) {
            $ret = $this->getBaseExternModulesUrl() . $moduleid . "/views/src/js/";
        }

        return $ret;
    }

    /**
     * return absolute path to the standard picture folder
     * @return string
     */
    public function getBaseDirTemplatePictures( $moduleid = "" ) {
        $ret = $this->getBaseDir() . "views/pictures/";
        if ( $moduleid != "" ) {
            $ret = $this->getBaseModulesDir() . $moduleid . "/views/pictures/";
        }

        return $ret;
    }

    /**
     * return link to the standard picture folder
     * @return string
     */
    public function getBaseUrlTemplatePictures( $moduleid = "" ) {
        $ret = $this->getBaseUrl() . "views/pictures/";
        if ( $moduleid != "" ) {
            $ret = $this->getBaseModulesUrl() . $moduleid . "/views/pictures/";
        }

        return $ret;
    }

    public function getBaseExternUrlTemplatePictures( $moduleid = "" ) {
        $ret = $this->getBaseExternUrl() . "views/pictures/";
        if ( $moduleid != "" ) {
            $ret = $this->getBaseExternModulesUrl() . $moduleid . "/views/pictures/";
        }

        return $ret;
    }


    /**
     * load a parameter from the database
     *
     * @param string $name     : name of the variable
     * @param string $moduleid : moduleid
     *
     * @return string : value
     */
    public function getConfigParam( $name, $moduleid = "" ) {
        Registry::getConfig()->getConfigParam($name);
    }

    /**
     * return array with all modules back witch contain a "metadata.php"
     *
     * @return array
     */
    public function getModulesDirectorys() {
        return $this->_getModulesDirectorys();
    }

    /**
     * search for all modules witch contain a "metadata.php"
     * @param string $vendorfolder
     * @return array
     */
    protected function _getModulesDirectorys( $vendorfolder = "" ) {
        if ( $vendorfolder != "" ) {
            $vendorfolder .= "/";
        }

        $path = $this->getBaseModulesDir() . $vendorfolder;

        //search in the module folder
        $modules = [];
        if ( $handle = opendir( $path ) ) {
            while ( false !== ($entry = readdir( $handle )) ) {
                if ( is_dir( $path . "/" . $entry ) && $entry != "." && $entry != ".." ) {
                    if ( file_exists( $path . "/" . $entry . "/metadata.php" ) ) {
                        $modules[] = $vendorfolder . $entry;
                    } elseif ( file_exists( $path . "/" . $entry . "/vendormetadata.php" ) ) {
                        $tmp = $this->_getModulesDirectorys( $vendorfolder . $entry );
                        if ( is_array( $tmp ) ) {
                            $modules = array_merge( $modules, $tmp );
                        }
                    }
                }
            }
            closedir( $handle );
        }

        sort( $modules );
        return $modules;
    }


    /**
     * prove if a parameter exists in the database
     *
     * @param string $name
     * @param string $moduleid
     *
     * @return bool
     */
    public function existConfigParam( $name, $moduleid = "" ) {
        return false;
    }





    public function sendMail( $sTo="", $sSubject="", $sText="") {
        $oMail = new \OxidEsales\Eshop\Core\Email();
        $oMail->sendEmail($sTo,$sSubject,$sText);
    }



    /**
     * function used in many cronjobs to print text to the log and screen
     *
     * @param $text
     */
    public function echoMessage( $text ) {
        echo $text . "<br>\n\r";
    }

    public function printMessage( $message ) {
        echo "<pre>";
        print_r( $message );
        echo "</pre>";
    }


    /**
     * @param string $message
     * @param null   $exception
     */
    public function writeToLog( $message, $exception = null ) {
    }

    /**
     * @param string          $msg
     * @param null|\Exception $e
     * @param string[]        $addedParams
     */
    public function sendError( $msg, $e = null, $addedParams = [] ) {
    }

    public static function log( $message ) {
    }

}
