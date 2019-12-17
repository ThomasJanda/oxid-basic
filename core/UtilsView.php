<?php

namespace rs\basic\core;

/**
 * Class UtilsView
 *
 * @package rs\basic\core
 * @see \OxidEsales\Eshop\Core\UtilsView
 */
class UtilsView extends UtilsView_parent
{

    /**
     * override smarty function
     * @param $smarty
     */
    protected function _fillCommonSmartyProperties($smarty)
    {
        parent::_fillCommonSmartyProperties($smarty);

        $smarty->register_resource(
            'ox',
            [
                'rs_get_template',
                'ox_get_timestamp',
                'ox_get_secure',
                'ox_get_trusted'
            ]
        );
    }
}