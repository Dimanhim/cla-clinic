<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "clinic_steps".
 *
 * @property int $id
 * @property string|null $type
 * @property int $clinic_id
 * @property int $step_id
 * @property int|null $is_active
 * @property int|null $deleted
 * @property int|null $position
 * @property int|null $created_at
 * @property int|null $updated_at
 */
class ClinicStep extends \app\models\BaseModel
{
    const TYPE_STEP_CREATE = 'create';
    const TYPE_STEP_UPDATE = 'update';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%clinic_steps}}';
    }

    /**
     * @return string
     */
    public static function modelName()
    {
        return '';
    }

    /**
     * @return int
     */
    public static function typeId()
    {
        return Gallery::TYPE_ANY;
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['clinic_id', 'step_id'], 'required'],
            [['clinic_id', 'step_id'], 'integer'],
            [['type'], 'string', 'max' => 255],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'type' => 'Type',
            'clinic_id' => 'Clinic ID',
            'step_id' => 'Step ID',
        ]);
    }
}
