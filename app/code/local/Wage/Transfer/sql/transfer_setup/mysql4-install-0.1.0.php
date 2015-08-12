<?php

$installer = $this;
$installer->startSetup();

$installer->getConnection()
    ->addColumn($installer->getTable('cms/page'),
    'push_to_live',
    array(
        'type' => Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
        'nullable' => true,
        'default' => null,
        'comment' => 'PUSH TO LIVE'
    )
);


$installer->getConnection()
    ->addColumn($installer->getTable('cms/block'),
    'push_to_live',
    array(
        'type' => Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
        'nullable' => true,
        'default' => null,
        'comment' => 'PUSH TO LIVE'
    )
);


//$installer->run("ALTER TABLE  {$this->getTable('cms_page')} ADD `push_to_live` timestamp default NULL;");
//$installer->run("ALTER TABLE  {$this->getTable('cms_block')} ADD `push_to_live` timestamp default NULL;");

$installer->endSetup();