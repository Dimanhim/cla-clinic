<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "steps".
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
class Step extends \app\models\BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%steps}}';
    }

    /**
     * @return string
     */
    public static function modelName()
    {
        return 'Этапы воронок';
    }

    /**
     * @return int
     */
    public static function typeId()
    {
        return Gallery::TYPE_STEP;
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['name', 'pipeline_id', 'amo_id'], 'required'],
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
            'pipeline_id' => 'Воронка',
            'amo_id' => 'Amo ID',
        ]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPipeline()
    {
        return $this->hasOne(Pipeline::className(), ['id' => 'pipeline_id']);
    }
}
