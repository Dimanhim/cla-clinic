<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "doctors".
 *
 * @property int $id
 * @property string $name
 * @property int $amo_id
 * @property int $mis_id
 * @property int|null $is_active
 * @property int|null $deleted
 * @property int|null $position
 * @property int|null $created_at
 * @property int|null $updated_at
 */
class Doctor extends \app\models\BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%doctors}}';
    }

    /**
     * @return string
     */
    public static function modelName()
    {
        return 'Врачи';
    }

    /**
     * @return int
     */
    public static function typeId()
    {
        return Gallery::TYPE_DOCTOR;
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['name', 'amo_id', 'mis_id', 'clinic_id'], 'required'],
            [['amo_id', 'mis_id'], 'integer'],
            [['name'], 'string', 'max' => 255],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'name' => 'ФИО',
            'clinic_id' => 'Клиника',
            'amo_id' => 'ID в АМО',
            'mis_id' => 'ID в МИС',
        ]);
    }

    public function getClinic()
    {
        return $this->hasOne(Clinic::className(), ['id' => 'clinic_id']);
    }
}
