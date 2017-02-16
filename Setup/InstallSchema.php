<?php
/**
 * Pmclain_Tfa extension
 * NOTICE OF LICENSE
 *
 * This source file is subject to the GPL v3 License
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://www.gnu.org/licenses/gpl.txt
 *
 * @category  Pmclain
 * @package   Pmclain_Tfa
 * @copyright Copyright (c) 2017
 * @license   https://www.gnu.org/licenses/gpl.txt GPL v3 License
 */

namespace Pmclain\Tfa\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

class InstallSchema implements InstallSchemaInterface
{
  public function install(SchemaSetupInterface $setup, ModuleContextInterface $context) {
    $installer = $setup;

    $installer->startSetup();

    $table = $installer->getTable('admin_user');

    $installer->getConnection()->addColumn(
      $table,
      'require_tfa',
      [
        'type' => Table::TYPE_SMALLINT,
        'nullable' => true,
        'default' => 0,
        'comment' => 'Require TFA for Login'
      ]
    );
    $installer->getConnection()->addColumn(
      $table,
      'tfa_secret',
      [
        'type' => Table::TYPE_TEXT,
        'nullable' => true,
        'comment' => 'TFA Secret'
      ]
    );

    $installer->endSetup();
  }
}