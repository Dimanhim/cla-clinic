<?php

use yii\db\Migration;
use yii\db\Schema;

/**
 * Class m220528_123857_galleries
 */
class m220528_123857_galleries extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%galleries}}', [
            'id'                    => Schema::TYPE_PK,

            'name'                  => Schema::TYPE_STRING . ' NOT NULL',
            'description'           => Schema::TYPE_TEXT,
            'short_description'     => Schema::TYPE_TEXT,
            'object_id'             => Schema::TYPE_INTEGER,
            'object_type'           => Schema::TYPE_INTEGER,

            'is_active'             => Schema::TYPE_SMALLINT . ' DEFAULT 1',
            'deleted'               => Schema::TYPE_SMALLINT,
            'position'              => Schema::TYPE_INTEGER,
            'created_at'            => Schema::TYPE_INTEGER,
            'updated_at'            => Schema::TYPE_INTEGER,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%galleries}}');
    }
}
