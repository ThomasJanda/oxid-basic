<?php
namespace core\inc;

class listing extends super implements \ArrayAccess, \Iterator, \Countable
{

    /**
     * Array of objects (some object list).
     *
     * @var array $_aArray
     */
    protected $_aArray = array();

    /**
     * Save the state, that active element was unset
     * needed for proper foreach iterator functionality
     *
     * @var bool $_blRemovedActive
     */
    protected $_blRemovedActive = false;

    /**
     * Template object used for some methods before the list is built.
     *
     * @var \core\inc\base
     */
    private $_oBaseObject = null;

    /**
     * Flag if array is ok or not
     *
     * @var boolean $_blValid
     */
    private $_blValid = true;

    /**
     * -----------------------------------------------------------------------------------------------------
     *
     * Implementation of SPL Array classes functions follows here
     *
     * -----------------------------------------------------------------------------------------------------
     */

    /**
     * implementation of abstract classes for ArrayAccess follow
     */
    /**
     * offsetExists for SPL
     *
     * @param mixed $offset SPL array offset
     *
     * @return boolean
     */
    public function offsetExists($offset)
    {
        if (isset($this->_aArray[$offset])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * offsetGet for SPL
     *
     * @param mixed $offset SPL array offset
     *
     * @return super|false
     */
    public function offsetGet($offset)
    {
        if ($this->offsetExists($offset)) {
            return $this->_aArray[$offset];
        } else {
            return false;
        }
    }

    /**
     * offsetSet for SPL
     *
     * @param mixed          $offset SPL array offset
     * @param \core\inc\base $oBase  Array element
     */
    public function offsetSet($offset, $oBase)
    {
        if (isset($offset)) {
            $this->_aArray[$offset] = &$oBase;
        } else {
            $id = $oBase->getId();
            if ($id != "" && isset($id)) {
                $this->_aArray[$id] = &$oBase;
            } else {
                $this->_aArray[] = &$oBase;
            }
        }
    }

    /**
     * offsetUnset for SPL
     *
     * @param mixed $offset SPL array offset
     */
    public function offsetUnset($offset)
    {
        if (strcmp($offset, $this->key()) === 0) {
            // #0002184: active element removed, next element will be prev / first
            $this->_blRemovedActive = true;
        }
        unset($this->_aArray[$offset]);
    }

    /**
     * Returns SPL array keys
     *
     * @return array
     */
    public function arrayKeys()
    {
        return array_keys($this->_aArray);
    }

    /**
     * rewind for SPL
     */
    public function rewind()
    {
        $this->_blRemovedActive = false;
        $this->_blValid = (false !== reset($this->_aArray));
    }

    /**
     * current for SPL
     *
     * @return null;
     */
    public function current()
    {
        return current($this->_aArray);
    }

    /**
     * key for SPL
     *
     * @return mixed
     */
    public function key()
    {
        return key($this->_aArray);
    }

    /**
     * previous / first array element
     *
     * @return mixed
     */
    public function prev()
    {
        $oVar = prev($this->_aArray);
        if ($oVar === false) {
            // the first element, reset pointer
            $oVar = reset($this->_aArray);
        }
        $this->_blRemovedActive = false;

        return $oVar;
    }

    /**
     * next for SPL
     */
    public function next()
    {
        if ($this->_blRemovedActive === true && current($this->_aArray)) {
            $oVar = $this->prev();
        } else {
            $oVar = next($this->_aArray);
        }

        $this->_blValid = (false !== $oVar);
    }

    /**
     * valid for SPL
     *
     * @return boolean
     */
    public function valid()
    {
        return $this->_blValid;
    }

    /**
     * count for SPL
     *
     * @return integer
     */
    public function count()
    {
        return count($this->_aArray);
    }


    /**
     * clears/destroys list contents
     */
    public function clear()
    {
        $this->_aArray = array();
    }

    /**
     * copies a given array over the objects internal array (something like old $myList->aList = $aArray)
     *
     * @param array $aArray array of list items
     */
    public function assign($aArray)
    {
        $this->_aArray = $aArray;
    }

    /**
     * returns the array reversed, the internal array remains untouched
     *
     * @return array
     */
    public function reverse()
    {
        return array_reverse($this->_aArray);
    }

    /**
     * -----------------------------------------------------------------------------------------------------
     * SPL implementation end
     * -----------------------------------------------------------------------------------------------------
     */



    /**
     * @var array SQL Limit, 0 => Start, 1 => Records
     */
    protected $_aSqlLimit = array();

    /**
     * @var string
     */
    protected $_sSqlWhere = "";

    /**
     * @var string
     */
    protected $_sSqlGroupBy="";

    /**
     * @var string
     */
    protected $_sSqlHaving="";

    /**
     * @var string
     */
    protected $_sSqlWhereCommand=" WHERE ";

    /**
     * @var string
     */
    protected $_sSqlGroupByCommand=" GROUP BY ";

    /**
     * @var string
     */
    protected $_sSqlHavingCommand=" HAVING ";

    /**
     * @var string
     */
    protected $_sSqlOrderBy = "";

    /**
     * @var string
     */
    protected $_sSqlOrderByCommand = " ORDER BY ";

    /**
     * @var array
     */
    protected $_aSqlJoin=[];

    /**
     * @var string
     */
    protected $_sLastSql = "";

    protected $_bCalculateFoundRows=false;
    protected $_iFoundRows=0;

    public function getFoundRows()
    {
        return $this->_iFoundRows;
    }

    /**
     * @param bool $enable
     *
     * @return $this
     */
    public function calculateFoundRows($enable=true)
    {
        $this->_bCalculateFoundRows=$enable;
        return $this;
    }


    /**
     * Class Constructor
     *
     * @param \cconfig3 $oconfig
     * @param \core\inc\base $oBaseObject Associated list item object type
     */
    public function __construct($oconfig, $oBaseObject)
    {
        parent::__construct($oconfig);
        $this->_oBaseObject = $oBaseObject;
        $this->_aSqlLimit[0] = false;
        $this->_aSqlLimit[1] = false;
    }


    public function getLastSql()
    {
        return $this->_sLastSql;
    }


    /**
     * Returns list items array
     *
     * @return array
     */
    public function getArray()
    {
        return $this->_aArray;
    }


    /**
     * Initializes or returns existing list template object.
     *
     * @return \core\inc\base|false
     */
    protected function getBaseObject()
    {
        if (!$this->_oBaseObject) {
            //create a new object
            return false;
        }
        return $this->_oBaseObject;
    }

    /**
     * Sets base object for list.
     *
     * @param object $oObject Base object
     */
    protected function setBaseObject($oObject)
    {
        $this->_oBaseObject = $oObject;
    }

    /**
     * Selects and SQL, creates objects and assign them
     *
     * @param string $sSql SQL select statement
     * @param boolean $echoSql : echo sql statment
     *
     */
    protected function selectString($sSql, $echoSql=false)
    {
        $this->clear();

        $join="";
        if(is_array($this->_aSqlJoin) && count($this->_aSqlJoin)>0)
        {
            foreach($this->_aSqlJoin as $aItem)
            {
                //$aItem['type']=$joinType;
                //$aItem['table']=$sTable;
                //$aItem['condition']=$sOnCondition;
                $join.=" ".$aItem['type']." JOIN ".$aItem['table']." ON ".$aItem['condition']." ";

            }
        }
        $sSql = str_replace("#JOIN#",$join,$sSql);

        //where
        $where = "";
        if($this->_sSqlWhere!="")
            $where = " ".$this->_sSqlWhereCommand." ".$this->_sSqlWhere." ";
        $sSql = str_replace("#WHERE#",$where,$sSql);

        //group by
        $groupby = "";
        if($this->_sSqlGroupBy!="")
            $groupby = " ".$this->_sSqlGroupByCommand." ".$this->_sSqlGroupBy." ";
        $sSql = str_replace("#GROUPBY#",$groupby,$sSql);

        //having
        $having = "";
        if($this->_sSqlHaving!="")
            $having = " ".$this->_sSqlHavingCommand." ".$this->_sSqlHaving." ";
        $sSql = str_replace("#HAVING#",$having,$sSql);

        //order by
        $orderby = "";
        if($this->_sSqlOrderBy!="")
            $orderby =" ".$this->_sSqlOrderByCommand." ".$this->_sSqlOrderBy." ";
        $sSql = str_replace("#ORDERBY#",$orderby,$sSql);

        //limit
        $limit = "";
        if ($this->_aSqlLimit[0]!==false || $this->_aSqlLimit[1]!==false)
        {
            $start = $this->_aSqlLimit[0];
            if($start===false)
                $start = 0;
            $limit=" limit ".$start;

            if($this->_aSqlLimit[1]!==false)
                $limit.=",".$this->_aSqlLimit[1];
        }
        $sSql = str_replace("#LIMIT#",$limit,$sSql);

        /*
        if(strpos($sSql,'SQL_CALC_FOUND_ROWS')===false)
        {
            $start = strpos(strtolower($sSql),'select ');
            if($start!==false)
            {
                //replace
                $tmp = substr($sSql,0,$start)."SELECT SQL_CALC_FOUND_ROWS ".(substr($sSql,$start + strlen('select ')));
                $sSql = $tmp;
            }
        }
        */

        $this->_sLastSql=$sSql;
        if($echoSql)
            echo $sSql;
        $rs = $this->getConfig()->execute($sSql);
        if($rs)
        {

            if($this->_bCalculateFoundRows)
            {
                $sql="SELECT FOUND_ROWS()";
                $this->_iFoundRows = $this->getConfig()->getScalar($sql);
            }

            $oSaved = clone $this->getBaseObject();
            $oSaved->setConfig($this->getBaseObject()->getConfig());
            $oSaved->setCustomer($this->getBaseObject()->getCustomer());

            while($row = $rs->fetch_array(MYSQLI_ASSOC))
            {
                $oListObject = clone $oSaved;

                $this->_assignElement($oListObject, $row);

                $this->add($oListObject);
            }
            /** important, close the connection after you been finished **/
            $this->getConfig()->close($rs);
        }
    }


    /**
     * Add an entry to object array.
     *
     * @param object $oObject Object to be added.
     */
    protected function add($oObject)
    {
        if ($oObject->getId()) {
            $this->_aArray[$oObject->getId()] = $oObject;
        } else {
            $this->_aArray[] = $oObject;
        }
    }

    /**
     * Assign data from array to list
     *
     * @param array $aData data for list
     */
    protected function assignArray($aData)
    {
        $this->clear();
        if (count($aData)) {

            $oSaved = clone $this->getBaseObject();

            foreach ($aData as $aItem) {
                $oListObject = clone $oSaved;
                $this->_assignElement($oListObject, $aItem);
                if ($oListObject->getId()) {
                    $this->_aArray[$oListObject->getId()] = $oListObject;
                } else {
                    $this->_aArray[] = $oListObject;
                }
            }
        }
    }


    /**
     * Sets SQL Limit
     *
     * @param integer|false $iStart   Start e.g. limit Start,xxxx
     * @param integer|false $iRecords Nr of Records e.g. limit xxx,Records
     *
     * @return $this
     */
    public function setSqlLimit($iStart, $iRecords)
    {
        $this->_aSqlLimit[0] = $iStart;
        $this->_aSqlLimit[1] = $iRecords;

        return $this;
    }

    /**
     * Sets SQL Limit
     *
     * @param integer|false $iStart   Start e.g. limit Start,xxxx
     * @param integer|false $iRecords Nr of Records e.g. limit xxx,Records
     *
     * @return $this
     */
    public function limit($iStart, $iRecords)
    {
        return $this->setSqlLimit($iStart, $iRecords);
    }

    /**
     * Sets SQL Limit
     *
     * @param int|false $iRecord : Nr of Records e.g. limit xxx,Records
     *
     * @return $this
     */
    public function setSqlLimitOffset($iRecord)
    {
        $this->_aSqlLimit[0] = false;
        $this->_aSqlLimit[1] = $iRecord;

        return $this;
    }

    /**
     * Sets SQL Where
     *
     * @param string $where
     *
     * @return $this
     */
    public function setSqlWhere($where)
    {
        $this->_sSqlWhere = $where;
        return $this;
    }

    /**
     * Sets SQL Where
     *
     * @param string $where
     *
     * @return $this
     */
    public function where($where)
    {
        return $this->setSqlWhere($where);
    }

    /**
     * @param string $sGroupBy
     * @return $this
     */
    public function setSqlGroupBy($sGroupBy)
    {
        $this->_sSqlGroupBy = $sGroupBy;
        return $this;
    }

    /**
     * @param $sGroupBy
     * @return $this
     */
    public function groupBy($sGroupBy)
    {
        return $this->setSqlGroupBy($sGroupBy);
    }

    /**
     * @param string $sHaving
     * @return $this
     */
    public function setSqlHaving($sHaving)
    {
        $this->_sSqlHaving = $sHaving;
        return $this;
    }

    /**
     * @param $sHaving
     * @return $this
     */
    public function having($sHaving)
    {
        return $this->setSqlHaving($sHaving);
    }

    /**
     * @param string $sTable
     * @param string $sOnCondition
     * @return $this
     */
    public function join($sTable, $sOnCondition)
    {
        return $this->setJoin("", $sTable, $sOnCondition);
    }

    /**
     * @param string $sTable
     * @param string $sOnCondition
     * @return $this
     */
    public function joinLeft($sTable, $sOnCondition)
    {
        return $this->setJoin("left", $sTable, $sOnCondition);
    }

    /**
     * @param string $sTable
     * @param string $sOnCondition
     * @return $this
     */
    public function joinRight($sTable, $sOnCondition)
    {
        return $this->setJoin("right", $sTable, $sOnCondition);
    }

    /**
     * @param string $joinType : emtpy, "left", "right"
     * @param string $sTable
     * @param string $sOnCondition
     *
     * @return $this
     */
    public function setJoin($joinType, $sTable, $sOnCondition)
    {
        $joinType=trim(strtolower($joinType));
        if($joinType=="" || $joinType=="left" || $joinType=="right")
        {
            $aItem=[];
            $aItem['type']=$joinType;
            $aItem['table']=$sTable;
            $aItem['condition']=$sOnCondition;
            $this->_aSqlJoin[]=$aItem;
        }
        return $this;
    }

    /**
     * Sets SQL OrderBy
     *
     * @param $orderby
     *
     * @return $this
     */
    public function setSqlOrderBy($orderby)
    {
        $this->_sSqlOrderBy = $orderby;
        return $this;
    }

    /**
     * Sets SQL OrderBy
     *
     * @param $orderby
     *
     * @return $this
     */
    public function orderBy($orderby)
    {
        return $this->setSqlOrderBy($orderby);
    }

    /**
     * Sets SQL Where Command
     *
     * @param string $command
     *
     * @return $this
     */
    public function setSqlWhereCommand($command)
    {
        $this->_sSqlWhereCommand = $command;
        return $this;
    }

    /**
     * Sets SQL OrderBy Command
     *
     * @param $command
     *
     * @return $this
     */
    public function setSqlOrderByCommand($command)
    {
        $this->_sSqlOrderByCommand = $command;
        return $this;
    }

    /**
     * Function checks if there is at least one object in the list which has the given value in the given field
     *
     * @param mixed  $oVal       The searched value
     * @param string $sFieldName The name of the field
     *
     * @return boolean
     */
    public function containsFieldValue($oVal, $sFieldName)
    {
        foreach ($this->_aArray as $obj) {
            if ($obj->{$sFieldName} == $oVal) {
                return true;
            }
        }
        return false;
    }

    /**
     * Generic function for loading the list
     *
     * @param boolean $echoSql : echo sql statment
     *
     * @return $this
     */
    public function getList($echoSql = false)
    {
        $oListObject = $this->getBaseObject();
        $sQ = "select ".($this->_bCalculateFoundRows?'SQL_CALC_FOUND_ROWS':'')." `" . $oListObject->getTableName()."`.`".$oListObject->getIndexColumn()."` from `" . $oListObject->getTableName()."` #JOIN# #WHERE# #GROUPBY# #HAVING# #ORDERBY# #LIMIT#";
        $this->selectString($sQ, $echoSql);

        return $this;
    }

    /**
     * @param bool $echoSql
     *
     * @return false|object
     */
    public function getFirst($echoSql = false)
    {
        $this->limit(0,1);
        $this->getList($echoSql);

        if($this->count()!=0)
        {
            return reset($this->_aArray);
        }
        return false;
    }



    /**
     * Executes assign() method on list object. This method is called in loop in oxList::selectString().
     * It is if you want to execute any functionality on every list ELEMENT after it is fully loaded (assigned).
     *
     * @param \core\inc\base $oListObject List object (the one derived from oxBase)
     * @param array  $aDbFields   An array holding db field values (normally the result of oxDb::Execute())
     */
    protected function _assignElement($oListObject, $aDbFields)
    {
        $oListObject->assignData($aDbFields);
    }

}