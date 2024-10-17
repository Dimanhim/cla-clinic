<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "pipelines".
 *
 * @property int $id
 * @property string $name
 * @property int $amo_id
 * @property int|null $is_active
 * @property int|null $deleted
 * @property int|null $position
 * @property int|null $created_at
 * @property int|null $updated_at
 */
class Pipeline extends \app\models\BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%pipelines}}';
    }

    /**
     * @return string
     */
    public static function modelName()
    {
        return 'Воронки';
    }

    /**
     * @return int
     */
    public static function typeId()
    {
        return Gallery::TYPE_PIPELINE;
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['name', 'amo_id'], 'required'],
            [['amo_id'], 'integer'],
            [['name'], 'string', 'max' => 255],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'name' => 'Название',
            'amo_id' => 'Amo ID',
        ]);
    }
}
