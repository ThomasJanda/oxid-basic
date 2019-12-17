# Oxid basic

This basic module is required to use some modules.

## Requirements

"oxid-formedit" module required. 

##Install

1. Add following code to to the file "modules/functions.php". If not present, create it and add <? at the beginn of the file.
    
        /* rs autoloader */
        include __DIR__."/rs/basic/bootstrap.php";
        
        /* extend smarty function */
        /**
         * Sets template content from cache. In demoshop enables security mode.
         *
         * @see http://www.smarty.net/docsv2/en/template.resources.tpl
         *
         * @param string $sTplName    name of template
         * @param string &$sTplSource Template source
         * @param object $oSmarty     not used here
         *
         * @return bool
         */
        function rs_get_template($sTplName, &$sTplSource, $oSmarty)
        {
            $sTmp = $oSmarty->oxidcache->rawValue;
            $sTplSource = str_replace("-&gt;","->", $sTmp);
        
            return true;
        }
    
2. Execute following Sql statment: 

        CREATE TABLE `rsholiday` (
         `index1` char(32) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
         `holiday` date DEFAULT NULL,
         `title` varchar(255) DEFAULT NULL,
         PRIMARY KEY (`index1`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='List of holidays on the year'