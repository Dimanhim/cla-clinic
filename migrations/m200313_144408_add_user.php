<?php

use yii\db\Migration;

/**
 * Class m240313_144408_add_user
 */
class m200313_144408_add_user extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->insert('{{%user}}', [
            'username' => 'admin',
            'name' => 'Администратор',
            'password' => '123456',
            'password_hash' => '$2y$13$1fnAOhEBVzCcOfl3n2QXaO4ai1dQyK2eOUL4CcOc9c5sdkEYe9X.O',
            'email' => 'dimanhim@list.ru',
            'status' => '10',
            'created_at' => time(),
            'updated_at' => time()
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m240313_144408_add_user cannot be reverted.\n";

        return false;
    }
}
