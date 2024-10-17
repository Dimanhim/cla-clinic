<?php

use yii\db\Migration;
use yii\db\Schema;

/**
 * Class m241016_122905_steps
 */
class m241016_122905_steps extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%steps}}', [
            'id'                    => Schema::TYPE_PK,

            'name'                  => Schema::TYPE_STRING . ' NOT NULL',
            'pipeline_id'           => Schema::TYPE_INTEGER . ' NOT NULL',
            'amo_id'                => Schema::TYPE_INTEGER . ' NOT NULL',

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
        $this->dropTable('{{%steps}}');
    }
}
