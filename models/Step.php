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
            [['amo_id', 'amo_status', 'mis_status', 'is_for_cancel'], 'integer'],
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
            'amo_status' => 'Для создания сделки в АМО',
            'mis_status' => 'Для создания визита в МИС',
            'is_for_cancel' => 'Для отмены визитов в МИС',
        ]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPipeline()
    {
        return $this->hasOne(Pipeline::className(), ['id' => 'pipeline_id']);
    }

    /**
     * @return array
     */
    public static function getListWithPipeline()
    {
        $data = [];
        if($steps = self::findModels()->all()) {
            foreach($steps as $step) {
                $item = '';
                $pipelineName = $step->pipeline ? $step->pipeline->name : '';
                $item .= $pipelineName . ' / ';
                $item .= $step->name;
                $data[$step->id] = $item;
            }
        }
        return $data;
    }

    /**
     * @return array
     */
    public static function statusesForCancel()
    {
        $data = [];
        if($steps = self::find()->select(['amo_id'])->where(['is_for_cancel' => 1])->asArray()->all()) {
            foreach($steps as $step) {
                $data[] = $step['amo_id'];
            }
        }
        return array_unique($data);
    }
}
