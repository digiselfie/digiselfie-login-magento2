<?php
namespace Digiselfie\Login\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{

    /**
     * {@inheritdoc}
     */
    public function upgrade(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $installer = $setup;

        $installer->startSetup();

        if (version_compare($context->getVersion(), '0.0.4', '<')) {
          $installer->getConnection()->addColumn(
                $installer->getTable('admin_user'),
                'digiselfie_login_id',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 256,
                    'nullable' => true,
                    'default' => null,
                    'comment' => 'Digiselfie login'
                ]
            );
        }
        $installer->endSetup();
    }
}