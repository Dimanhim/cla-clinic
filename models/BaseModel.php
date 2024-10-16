<?php

namespace app\models;

use himiklab\sortablegrid\SortableGridBehavior;
use himiklab\thumbnail\EasyThumbnailImage;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\grid\ActionColumn;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\UploadedFile;

/**
1. добавить static метод modelName
2. удалить во вью $this->title
3. В классе Product поменять beforeSave
// 4. добавить static метод typeName - это для сохранения сущности картинок. По ходу не нужно
5. в класс Gallery добавить типы изображений
6. добавить static метод typeId
 * включить ajax валидацию в форме
 */
class BaseModel extends ActiveRecord
{
    //свойство для администрирования
    public $admin;
    public $image_field;
    //public $image_fields = ['image_field' => 'image_id'];
    public $image_fields;
    public $image_preview_field = [];
    protected $_general_attributes = [
        'id', 'unique_id', 'is_active', 'deleted', 'position', 'created_at', 'updated_at'
    ];

    /**
     * @return array
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::className(),
            'sort' => [
                'class' => SortableGridBehavior::className(),
                'sortableAttribute' => 'position',
            ],
        ];
    }

    /**
     * @return mixed
     */
    public function getModelName()
    {
        return self::className()::modelName();
    }

    /**
     * @return mixed
     */
    public function getTypeId()
    {
        return self::className()::typeId();
    }

    /**
     * @return mixed
     */
    /*public function getTypeName()
    {
        return self::className()::typeName();
    }*/

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['image_field', 'image_fields', 'image_preview_field', 'is_active', 'deleted', 'position', 'created_at', 'updated_at'], 'safe'],
        ];
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'image_field' => 'Изображение',
            'image_fields' => 'Изображение',
            'image_preview_field' => 'Превью изображения',
            'is_active' => 'Активность',
            'deleted' => 'Удален',
            'position' => 'Сортировка',
            'created_at' => 'Дата создания',
            'updated_at' => 'Дата редактирования',
        ];
    }

    /**
     * @param bool $insert
     * @return bool
     */
    public function beforeSave($insert)
    {
        return parent::beforeSave($insert);
    }

    public function afterSave($insert, $changedAttributes)
    {
        if($insert) {
            Yii::$app->session->setFlash('success', 'Запись успешно добавлена');
        }
        else {
            Yii::$app->session->setFlash('success', 'Запись успешно обновлена');
        }
        $this->handleImages();
        parent::afterSave($insert, $changedAttributes);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImage()
    {
        return $this->hasOne(Image::className(), ['id' => 'image_id']);
    }

    /**
     * @return bool
     */
    public function getMainImage()
    {
        if($this->gallery && $this->gallery->images) {
            return $this->gallery->images[0];
        }
        return false;
    }

    /**
     * @return bool
     */
    public function getSecondImage()
    {
        if($this->gallery && $this->gallery->images) {
            return isset($this->gallery->images[1]) ? $this->gallery->images[1] : $this->gallery->images[0];
        }
        return false;
    }

    public function fullPath($path)
    {
        $prefix = Yii::$app->db->tablePrefix;
        $dirName = str_replace($prefix, '', self::className()::tableName());
        return '/images'.$dirName.'/'.$path;
    }

    public static function fullTableName($model)
    {
        return str_replace(
            ['%', '{', '}'],
            [Yii::$app->db->tablePrefix, '', ''],
            $model::tableName()
        );
    }

    /**
     * @return mixed
     */
    public static function findModels($admin = false, $model = null)
    {
        $tableName = '';
        if($model) {
            $tableName = self::fullTableName($model).'.';
        }

        return $admin
            ?
            self::className()::find()->where(['is', $tableName.'deleted', null])->orderBy([$tableName.'position' => 'SORT ASC'])
            :
            self::className()::find()->where(['is', $tableName.'deleted', null])->andWhere([$tableName.'is_active' => 1])->orderBy([$tableName.'position' => 'SORT ASC']);
    }

    /**
     * @return mixed
     */
    public function findSearch()
    {
        return self::className()::find()->where(['is', 'deleted', null])->orderBy(['position' => 'SORT ASC', 'id' => 'SORT DESC']);
    }

    /**
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function deleteModel()
    {
        $this->is_active = null;
        $this->deleted = 1;
        $this->update(false);
    }

    /**
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function addModel()
    {
        $this->is_active = 1;
        $this->deleted = null;
        $this->update(false);
    }

    /**
     * @return array
     */
    public static function getList()
    {
        return ArrayHelper::map(self::findModels()->andWhere(['not', ['name' => null]])->asArray()->all(), 'id', 'name');
    }

    /**
     * @return array|ActiveRecord[]
     */
    public function getGallery()
    {
        return Gallery::find()->where(['object_type' => $this->typeId, 'object_id' => $this->id])->one();
    }

    /**
     * @param $models
     * @return bool
     */
    public function getListChunk($models)
    {
        if(!$models) return false;
        return Yii::$app->controller->renderPartial('//chunks/_list_names', [
            'models' => $models,
        ]);
    }

    /**
     * @param $models
     * @return bool
     */
    public function getListLinksChunk($models, $link)
    {
        if(!$models) return false;
        return Yii::$app->controller->renderPartial('//chunks/_list_links', [
            'models' => $models,
            'link' => $link,
        ]);
    }

    private function handleImages() {

        $repSymb = [Yii::$app->db->tablePrefix, '{','}','%'];
        $replaced = ['', '', '', ''];
        $dirName = str_replace($repSymb, $replaced, self::className()::tableName());
        $filesDir = Yii::getAlias('@upload')."/{$dirName}/";
        if (!file_exists($filesDir)) mkdir($filesDir, 0777, true);

        if($files = UploadedFile::getInstances($this, 'image_fields')) {
            if(!$gallery = $this->gallery) {
                $gallery = new Gallery();
                $gallery->object_id = $this->id;
                $gallery->object_type = $this->typeId;
                $gallery->save();
            }
            foreach($files as $file) {
                $fileName = $this->id.'_'.uniqid();
                $filePath = "/{$dirName}/{$fileName}.{$file->extension}";

                if (!$file->saveAs(Yii::getAlias('@upload').$filePath)) {
                    continue;
                }

                $image = Image::create($filePath, $gallery->id);
            }
        }
    }

    /**
     * @return string
     */
    public function getImg($width = 100, $height = 100)
    {
        if($this->image) {
            $img = EasyThumbnailImage::thumbnailImg(Yii::getAlias('@upload').$this->image->path, $width, $height, EasyThumbnailImage::THUMBNAIL_OUTBOUND);
            return Html::a($img, '/upload/'.$this->image->path, ['target' => '_blanc']);
        }
    }

    public function getImageByPath($path, $width, $height)
    {
        $prefix = Yii::$app->db->tablePrefix;
        $dirName = str_replace($prefix, '', self::className()::tableName());

        $img = EasyThumbnailImage::thumbnailImg(Yii::getAlias('@upload').'/'.$path, $width, $height, EasyThumbnailImage::THUMBNAIL_OUTBOUND);
        return $img;
    }

    public function getMainImageHtml()
    {
        if($this->mainImage) {
            if(in_array($this->mainImage->extension, $this->mainImage->_images_extensions)) {
                return Html::a(
                    EasyThumbnailImage::thumbnailImg(Yii::getAlias('@upload').$this->mainImage->path, 100, 100, EasyThumbnailImage::THUMBNAIL_OUTBOUND),
                    $this->mainImage->filePath,
                    ['data-fancybox' => 'gallery']
                );
            }
            else {
                return Html::a($this->mainImage->getExtensionSvg(20, 20, '#000'), $this->mainImage->fileUrl, ['target' => '_blanc', 'download' => true]);
            }
        }
    }
    public function getImages()
    {
        if($this->gallery && $this->gallery->images) {
            return $this->gallery->images;
        }
        return false;
    }
    public function getAllImages()
    {
        $images = [];
        if($this->images) {
            foreach($this->images as $image) {
                if($image->isImage) $images[] = $image;
            }
        }
        return $images;
    }
    public function getImagesHtml($rows = null)
    {
        if($this->gallery) return $this->gallery->getPreviewListHTML($rows);
    }
    public function getAvatar()
    {
        if($this->mainImage) {
            return '/upload'.$this->mainImage->path;
        }
        return '../img/no-img.png';
    }

    public function getActive()
    {
        return $this->is_active ? 'Да' : 'Нет';
    }
    public function getCreatedAt()
    {
        return date('d.m.Y H:i', $this->created_at);
    }
    public function getUpdatedAt()
    {
        return date('d.m.Y H:i', $this->updated_at);
    }

    public function getImagesField($form, $rows = null)
    {
        return Yii::$app->controller->renderPartial('//chunks/_images_form_field', [
            'form' => $form,
            'model' => $this,
            'rows' => $rows,
        ]);
    }

    public function getFormCard($attributes = [], $cardName = '', $hidden = false)
    {
        return Yii::$app->controller->renderPartial('//chunks/_form_card', [
            'attributes' => $attributes,
            'cardName' => $cardName,
            'hidden' => $hidden,
        ]);
    }

    protected function updateMainAttributes($model)
    {
        $attributes = [];
        if($model->attributes) {
            foreach ($model->attributes as $attributeName => $attributeValue) {
                if(!in_array($attributeName, $this->_general_attributes)) {
                    $attributes[$attributeName] = $attributeValue;
                }
            }
        }
        $model->attributes = $attributes;
        return $model->save();
    }

    public function getRequiredClassHtml($attributeName)
    {
        return $this->isAttributeRequired($attributeName) ? ' class="required"' : '';
    }

    public function delete()
    {
        $this->deleted = 1;
        return $this->update();
    }





}
