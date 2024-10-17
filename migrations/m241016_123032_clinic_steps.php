<?php

use yii\db\Migration;
use yii\db\Schema;

/**
 * Class m241016_123032_clinic_steps
 */
class m241016_123032_clinic_steps extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%clinic_steps}}', [
            'id'                    => Schema::TYPE_PK,

            'type'                  => Schema::TYPE_STRING,
            'clinic_id'             => Schema::TYPE_INTEGER . ' NOT NULL',
            'step_id'               => Schema::TYPE_INTEGER . ' NOT NULL',

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
        $this->dropTable('{{%clinic_steps}}');
    }
}
