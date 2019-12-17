<?php

$sMetadataVersion = '2.0';

$aModule = array(
    'id'          => 'rs-basic',
    'title'       => '*RS Basic',
    'description' => 'Wrapper module',
    'thumbnail'   => '',
    'version'     => '1.0.0',
    'author'      => '',
    'url'         => '',
    'email'       => '',
    'controllers' => array(
    ),
    'extend'      => array(
        \OxidEsales\Eshop\Core\UtilsView::class => rs\basic\core\UtilsView::class,
    ),
    'templates' => array(
    ),
    'blocks'      => array(
    ),
    'settings'    => array(
    ),
);