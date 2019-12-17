<?php
namespace core\inc
{
    class base extends basic implements \ArrayAccess, \JsonSerializable
    {
        protected $_lazyloading = true;

        //name from the table that should connect
        /** @var string $_stableName */
        protected $_stableName = "";

        //name of the unique field in the tables. normally index1
        /** @var string $_scolumnIndex */
        protected $_scolumnIndex = "";

        //save all data that load from the table
        /** @var array $_datarow */
        protected $_datarow = null;

        //these columns will not update by the save function
        /**
         * @var string[]
         */
        protected $_acolumnsIgnoreOnSave = array(
            //'cpcreated',
            'cpupdated'
        );

        // Santi playing around with static stuff.
        protected static $_tableName = null; // will contain the name of the table.
        protected static $_indexColumn = null; // will contain the name of the table.

        /**
         * base constructor.
         *
         * @param string       $stableName
         * @param string       $scolumnIndex
         * @param \cconfig3    $oconfig
         * @param \ccustomers3 $ocustomer
         */
        public function __construct($stableName, $scolumnIndex, $oconfig, $ocustomer)
        {
            $this->_stableName = $stableName;
            $this->_scolumnIndex = $scolumnIndex;
            parent::__construct($oconfig, $ocustomer);
        }

        public function __toString()
        {
            return json_encode($this->getData());
        }

        /**
         * @return string tablename
         */
        public function getTableName()
        {
            return $this->_stableName;
        }

        /**
         * @return string index column name
         */
        public function getIndexColumn()
        {
            return $this->_scolumnIndex;
        }

        /**
         * load a row from a table
         * return: true if data is loaded, othewise false
         *
         * @param      $id
         * @param bool $debug
         *
         * @return bool
         */
        public function load($id, $debug=false)
        {
            $this->_lazyloading = false;
            $id                 = $this->getConfig()->escapeString($id);

            $sqlstring = "SELECT * FROM `{$this->_stableName}` WHERE `{$this->_scolumnIndex}`='$id'";
            if($debug)
            {
                echo $sqlstring;
                die("");
            }
            $this->_datarow = $this->getConfig()->getRow($sqlstring, false);

            $ret = $this->loadByResult($this->_datarow);

            return $ret;
        }

        /**
         * Creates the object instance with the values of a resultset
         *
         * @param $row
         *
         * @return bool
         */
        public function loadByResult($row)
        {
            if (!is_array($row)) {
                $row = json_decode(json_encode($row), true);
            }

            $ret            = false;
            $this->_datarow = $row;
            if ($this->_datarow !== null) {
                $this->_datarow = array_change_key_case($this->_datarow, CASE_LOWER);

                if ($this->isLoaded()) {
                    $ret = true;
                }
            }

            return $ret;
        }


        /**
         * reload the data from the table with the current index1
         *
         * return: true if data is loaded, othewise false
         **/
        public function reload()
        {
            $index1 = $this->getId();
            $this->clearObject();

            return $this->load($index1);
        }

        /**
         * magic getter function
         * with this function, you can access all variables from the loaded data in a objective way
         * $object->index1;
         * $object->oxordernr;
         *
         * if variable found, return the value, otherwise null
         **/
        public function __get($sName)
        {
            $value = null;
            if ($this->_datarow !== null && isset($this->_datarow[strtolower($sName)])) {
                $value = $this->_datarow[strtolower($sName)];
            } else {
                if ($this->_lazyloading && $this->getId() != "") {
                    if ($this->load($this->getId())) {
                        if (isset($this->_datarow[strtolower($sName)])) {
                            $value = $this->_datarow[strtolower($sName)];
                        }
                    }
                }
            }

            return $value;
        }

        /**
         * magic setter function
         * when a value is set to the class, it will automatic add to the data array
         *
         * @param $sName
         * @param $value
         */
        public function __set($sName, $value)
        {
            $this->_datarow[strtolower($sName)] = $value;
        }

        /**
         * clear current object
         **/
        public function clearObject()
        {
            $this->_datarow = null;
        }


        /**
         * get all loaded data as array
         *
         * @return array|null
         **/
        public function getData()
        {
            return $this->_datarow;
        }

        /**
         * Get the changed data, only makes sense when we call the assign() method
         * @return array
         */
        public function getChangedData() {
            return $this->_aassign;
        }

        /**
         * returns the current index form the loaded data
         **/
        public function getId()
        {
            $sValue = null;
            $sName  = $this->_scolumnIndex;
            if ($this->_datarow !== null && isset($this->_datarow[strtolower($sName)])) {
                $sValue = $this->_datarow[strtolower($sName)];
            }
            return $sValue;
        }

        /**
         * test, if current row is loaded
         **/
        public function isLoaded()
        {
            if ($this->getId() !== null) {
                return true;
            }

            return false;
        }


        protected $_dbcolumnnames = null;

        protected function _getColumnNames()
        {
            if ($this->_dbcolumnnames === null) {
                $sQ = "SHOW COLUMNS FROM " . $this->_stableName;
                $rs = $this->getConfig()->execute($sQ);
                if ($rs) {
                    while ($row = $rs->fetch_object()) {
                        $this->_dbcolumnnames[] = trim(strtolower($row->Field));
                    }
                }
                /** important, close the connection after you been finished **/
                $this->getConfig()->close($rs);
            }

            return $this->_dbcolumnnames;
        }


        /**
         * assign new values to the object
         **/
        protected $_aassign = array();

        public function assign($params, $save = false, $forceInsert = false)
        {
            $columnnames = $this->_getColumnNames();

            if (is_null($columnnames)) {
                throw new \RuntimeException("Column names are null");
            }

            $ret = true;
            foreach ($params as $col => $value) {
                $col = trim(strtolower($col));

                if(is_array($columnnames))
                {
                    if (in_array($col, $columnnames)) {
                        $this->_aassign[$col] = $value;
                    }
                }
            }
            if ($save) {
                $ret = $this->save($forceInsert);
            }

            return $ret;
        }


        /**
         * assign the data to the object, data can not save
         * but can use as data that is load from the database
         *
         * @param $data
         */
        public function assignData($params)
        {
            $this->clearObject();

            $columnnames = $this->_getColumnNames();
            foreach ($params as $col => $value) {
                $col = trim(strtolower($col));

                if (in_array($col, $columnnames)) {
                    $this->_datarow[$col] = $value;
                }
            }
        }


        /**
         * save assigned values to the database and assign the parameters to the object
         *
         * @param bool $forceInsert Commands the process to create a new row on the DB, this helps when a index1 value has been assigned
         *
         * @return bool
         */
        public function save($forceInsert = false)
        {
            $ret = false;

            if (count($this->_aassign) == 0) {
                return $ret;
            }

            //remove before update
            if(is_array($this->_acolumnsIgnoreOnSave) && count($this->_acolumnsIgnoreOnSave) > 0)
            {
                foreach($this->_acolumnsIgnoreOnSave as $col)
                {
                    if(isset($this->_aassign[$col]))
                        unset($this->_aassign[$col]);
                }
            }

            if ($this->getId() == "" || $forceInsert) {
                //insert
                if (!isset($this->_aassign[$this->_scolumnIndex])) {
                    $this->_aassign[$this->_scolumnIndex] = uniqid("");
                }

                $sql = $this->_insert();
                //echo $sql;
                $this->getConfig()->executeNoReturn($sql);

                $this->load($this->_aassign[$this->_scolumnIndex]);

                if(!$this->isLoaded()){
                    echo "Something hapenned trying to make an insert on the table '{$this->getTableName()}'.";
                    echo $sql;
                    die("");
                }

                $ret = true;
            } else {
                //update
                $sql = $this->_update();
                //echo $sql;

                $this->getConfig()->executeNoReturn($sql);

                if (isset($this->_aassign[$this->_scolumnIndex])) {
                    $this->_datarow[$this->_scolumnIndex] = $this->_aassign[$this->_scolumnIndex];
                }

                $this->reload();

                $ret = true;
            }

            $this->_aassign = array();

            return $ret;
        }

        /**
         * insert data into the database
         *
         * return sqlstring
         **/
        protected function _insert()
        {
            $tmp1 = '';
            $tmp2 = '';
            foreach ($this->_aassign as $col => $value) {
                if ($tmp1 != "") {
                    $tmp1 .= ",";
                    $tmp2 .= ",";
                }
                $tmp1 .= "`" . $col . "`";
                if($value==="#NULL#")
                    $tmp2 .= "NULL";
                else
                    $tmp2 .= "'" . $this->getConfig()->escapeString($value) . "'";
            }
            $sqlstring = "INSERT INTO `" . $this->_stableName . "` (" . $tmp1 . ") VALUES (" . $tmp2 . ")";

            return $sqlstring;
        }

        /**
         * update data into the database
         *
         * return string
         **/
        protected function _update()
        {
            $tmp1 = '';
            foreach ($this->_aassign as $col => $value) {
                if ($tmp1 != "") {
                    $tmp1 .= ",";
                }
                $tmp1 .= "`" . $col . "`=";
                if($value==="#NULL#")
                    $tmp1.="NULL";
                else
                    $tmp1.="'" . $this->getConfig()->escapeString($value) . "'";
            }
            $sqlstring = "update `" . $this->_stableName . "` set " . $tmp1 . " where `" . $this->_scolumnIndex . "`='" . $this->getConfig()->escapeString($this->getId()) . "'";

            return $sqlstring;
        }

        public function delete()
        {
            if ($this->getId() == "") {
                return false;
            }

            $sql = $this->_delete();
            $this->getConfig()->executeNoReturn($sql);

            $this->clearObject();

            return true;
        }

        protected function _delete()
        {
            $sqlstring = "DELETE FROM `" . $this->_stableName . "` WHERE `" . $this->_scolumnIndex . "`='" . $this->getConfig()->escapeString($this->getId()) . "'";

            return $sqlstring;
        }



        /**
         * @param $id
         *
         * @return $this|false
         */
        public static function find($id)
        {
            $classname = get_called_class();
            $oConfig = new \cconfig3();
            /**
             * @var base $o
             */
            $o = new $classname($oConfig);
            if ($o->load($id)) {
                return $o;
            }

            return false;
        }


        /**
         * return listing object
         *
         * @return listing
         */
        public static function get()
        {
            $classname = get_called_class();
            $oConfig = new \cconfig3();
            $o = new $classname($oConfig);
            return new \core\inc\listing($oConfig,$o);
        }

        /**
         * Whether a offset exists
         *
         * @link  https://php.net/manual/en/arrayaccess.offsetexists.php
         *
         * @param mixed $offset <p>
         *                      An offset to check for.
         *                      </p>
         *
         * @return boolean true on success or false on failure.
         * </p>
         * <p>
         * The return value will be casted to boolean if non-boolean was returned.
         * @since 5.0.0
         */
        public function offsetExists($offset)
        {
            return array_key_exists($offset, $this->_datarow);
        }

        /**
         * Offset to retrieve
         *
         * @link  https://php.net/manual/en/arrayaccess.offsetget.php
         *
         * @param mixed $offset <p>
         *                      The offset to retrieve.
         *                      </p>
         *
         * @return mixed Can return all value types.
         * @since 5.0.0
         */
        public function offsetGet($offset)
        {
            return $this->_datarow[$offset];
        }

        /**
         * Offset to set
         *
         * @link  https://php.net/manual/en/arrayaccess.offsetset.php
         *
         * @param mixed $offset <p>
         *                      The offset to assign the value to.
         *                      </p>
         * @param mixed $value  <p>
         *                      The value to set.
         *                      </p>
         *
         * @return void
         * @since 5.0.0
         */
        public function offsetSet($offset, $value)
        {
            $this->_datarow[$offset] = $value;
        }

        /**
         * Offset to unset
         *
         * @link  https://php.net/manual/en/arrayaccess.offsetunset.php
         *
         * @param mixed $offset <p>
         *                      The offset to unset.
         *                      </p>
         *
         * @return void
         * @since 5.0.0
         */
        public function offsetUnset($offset)
        {
            unset($this->_datarow[$offset]);
        }

        /**
         * Convert the object to its JSON representation.
         *
         * @param  int $options
         *
         * @return string
         */
        public function toJson($options = 0)
        {
            return json_encode($this->jsonSerialize(), $options);
        }

        /**
         * Specify data which should be serialized to JSON.
         *
         * @link  http://php.net/manual/en/jsonserializable.jsonserialize.php
         *
         * @return mixed data which can be serialized by <b>json_encode</b>,
         *               which is a value of any type other than a resource.
         *
         * @since 5.4.0
         */
        public function jsonSerialize()
        {
            return $this->getData();
        }
    }
}
