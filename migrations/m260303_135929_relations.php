<?php

use yii\db\Migration;
use yii\db\Schema;

/**
 * Class m260303_135929_relations
 */
class m260303_135929_relations extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%relations}}', [
            'id'                    => Schema::TYPE_PK,

            'lead_id'               => Schema::TYPE_INTEGER . ' NOT NULL',
            'appointment_id'        => Schema::TYPE_INTEGER . ' NOT NULL',

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
        $this->dropTable('{{%relations}}');
    }
}
