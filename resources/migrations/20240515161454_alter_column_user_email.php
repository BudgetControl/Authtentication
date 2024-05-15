<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AlterColumnUserEmail extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change(): void
    {
        try {
            $table = $this->table('users');
            $table->removeIndex('email');
            $table->update();
        } catch (\Exception $e) {
            // do nothing
        }
        

        $table = $this->table('users');
        $table->changeColumn('email', 'text', ['limit' => \Phinx\Db\Adapter\MysqlAdapter::TEXT_LONG, 'null' => false]);
        $table->update();

    }
}
