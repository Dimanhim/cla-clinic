<?php

use yii\db\Migration;
use yii\db\Schema;

/**
 * Class m241016_122948_clinics
 */
class m241016_122948_clinics extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%clinics}}', [
            'id'                    => Schema::TYPE_PK,

            'name'                  => Schema::TYPE_STRING . ' NOT NULL',
            'amo_name'              => Schema::TYPE_STRING,
            'amo_id'                => Schema::TYPE_INTEGER . ' NOT NULL',
            'mis_id'                => Schema::TYPE_INTEGER . ' NOT NULL',
            'api_key'               => Schema::TYPE_STRING,
            'reception_time'        => Schema::TYPE_INTEGER,

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
        $this->dropTable('{{%clinics}}');
    }
}
