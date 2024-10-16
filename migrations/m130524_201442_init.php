<?php

use yii\db\Migration;

class m130524_201442_init extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // https://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%user}}', [
            'id' => $this->primaryKey(),

            'username' => $this->string()->notNull()->unique()->defaultValue(''),
            'name' => $this->string()->notNull(),
            'auth_key' => $this->string(32)->notNull()->defaultValue(''),
            'password' => $this->string(),
            'password_hash' => $this->string(),
            'password_reset_token' => $this->string()->unique(),
            'email' => $this->string(),

            'status' => $this->smallInteger()->notNull()->defaultValue(10),

            'is_active' => $this->smallInteger()->defaultValue(1),
            'deleted' => $this->smallInteger(),
            'position' => $this->integer(),
            'created_at' => $this->integer(),
            'updated_at' => $this->integer(),
        ], $tableOptions);
    }

    public function down()
    {
        $this->dropTable('{{%user}}');
    }
}
