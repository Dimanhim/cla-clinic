<?php

namespace app\models;

use Yii;
use app\models\ClinicStep;

/**
 * This is the model class for table "clinics".
 *
 * @property int $id
 * @property string $name
 * @property int $amo_id
 * @property int $mis_id
 * @property string|null $api_key
 * @property int|null $reception_time
 * @property int|null $is_active
 * @property int|null $deleted
 * @property int|null $position
 * @property int|null $created_at
 * @property int|null $updated_at
 */
class Clinic extends \app\models\BaseModel
{
    public $_steps_create = [];
    public $_steps_update = [];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%clinics}}';
    }

    /**
     * @return string
     */
    public static function modelName()
    {
        return 'Клиники';
    }

    /**
     * @return int
     */
    public static function typeId()
    {
        return Gallery::TYPE_CLINIC;
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['name', 'amo_id', 'mis_id'], 'required'],
            [['amo_id', 'mis_id', 'reception_time'], 'integer'],
            [['name', 'amo_name', 'api_key'], 'string', 'max' => 255],
            [['_steps_create', '_steps_update'], 'safe'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'name' => 'Название',
            'amo_name' => 'Название в АМО',
            'amo_id' => 'ID в АМО',
            'mis_id' => 'ID в МИС',
            'api_key' => 'АПИ-ключ',
            'reception_time' => 'Время приема',
            '_steps_create' => ' Этапы воронки для создания визита',
            '_steps_update' => 'Этапы воронки для обновления визита',
        ]);
    }

    public function afterFind()
    {
        $this->setClinicSteps();

        return parent::afterFind();
    }

    /**
     * @param $insert
     * @param $changedAttributes
     * @throws \yii\db\Exception
     */
    public function afterSave($insert, $changedAttributes)
    {
        $this->handleSteps();

        return parent::afterSave($insert, $changedAttributes);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getClinicSteps()
    {
        return $this->hasMany(ClinicStep::className(), ['clinic_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     * @throws \yii\base\InvalidConfigException
     */
    public function getCreateSteps()
    {
        return $this->hasMany(Step::className(), ['id' => 'step_id'])
            ->viaTable('clinic_steps', ['clinic_id' => 'id'], function($query) {
                return $query->andWhere(['type' => ClinicStep::TYPE_STEP_CREATE]);
            });
    }

    /**
     * @return \yii\db\ActiveQuery
     * @throws \yii\base\InvalidConfigException
     */
    public function getUpdateSteps()
    {
        return $this->hasMany(Step::className(), ['id' => 'step_id'])
            ->viaTable('clinic_steps', ['clinic_id' => 'id'], function($query) {
                return $query->andWhere(['type' => ClinicStep::TYPE_STEP_UPDATE]);
            });
    }

    /**
     *
     */
    public function setClinicSteps()
    {
        if($clinicSteps = $this->clinicSteps) {
            foreach($clinicSteps as $clinicStep) {
                if($clinicStep->type == ClinicStep::TYPE_STEP_CREATE) {
                    $this->_steps_create[] = $clinicStep->step_id;
                }
                elseif($clinicStep->type == ClinicStep::TYPE_STEP_UPDATE) {
                    $this->_steps_update[] = $clinicStep->step_id;
                }
            }
        }
    }

    /**
     * @throws \yii\db\Exception
     */
    public function handleSteps()
    {
        ClinicStep::deleteAll(['clinic_id' => $this->id]);
        if($this->_steps_create) {
            foreach($this->_steps_create as $item) {
                $model = new ClinicStep();
                $model->clinic_id = $this->id;
                $model->step_id = $item;
                $model->type = ClinicStep::TYPE_STEP_CREATE;
                $model->save();
            }

        }
        if($this->_steps_update) {
            foreach($this->_steps_update as $item) {
                $model = new ClinicStep();
                $model->clinic_id = $this->id;
                $model->step_id = $item;
                $model->type = ClinicStep::TYPE_STEP_UPDATE;
                $model->save();
            }
        }
    }
}
