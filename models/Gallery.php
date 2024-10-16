<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "stv_galleries".
 *
 * @property int $id
 * @property string $unique_id
 * @property string $name
 * @property string|null $description
 * @property string|null $short_description
 * @property int|null $object_id
 * @property int|null $object_type
 * @property int|null $is_active
 * @property int|null $deleted
 * @property int|null $position
 * @property int|null $created_at
 * @property int|null $updated_at
 */
class Gallery extends BaseModel
{
    const TYPE_ORDER          = 1;
    const TYPE_IMAGE          = 2;
    const TYPE_CLIENT         = 3;

    const TYPE_ANY            = 15;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%galleries}}';
    }

    /**
     * @return string
     */
    public static function modelName()
    {
        return 'Галереи';
    }

    /**
     * @return string
     */
    public static function typeName()
    {
        return 'gallery';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['description', 'short_description'], 'string'],
            [['object_id', 'object_type'], 'integer'],
            [['name'], 'string', 'max' => 255],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'name' => 'Name',
            'description' => 'Description',
            'short_description' => 'Short Description',
            'object_id' => 'Object ID',
            'object_type' => 'Object Type',
        ]);
    }

    /**
     * @return array
     */
    public static function getTypes()
    {
        return [
            self::TYPE_ORDER               => 'Заявки',
            self::TYPE_IMAGE               => 'Изображения',
            self::TYPE_CLIENT              => 'Клиенты',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImages()
    {
        return $this->hasMany(Image::className(), ['gallery_id' => 'id'])->andWhere(['is_active' => 1])->andWhere(['is', 'deleted', null])->orderBy(['position' => SORT_ASC]);
    }

    public function getPreviewListHTML($rows = null)
    {
        return Yii::$app->controller->renderPartial('//chunks/_gallery_preview_list', [
            'model' => $this,
            'rows' => $rows,
        ]);
    }
}
