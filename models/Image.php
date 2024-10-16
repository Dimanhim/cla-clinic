<?php

namespace app\models;

use himiklab\thumbnail\EasyThumbnailImage;
use Yii;

/**
 * This is the model class for table "stv_images".
 *
 * Изображения
 *
 */
class Image extends BaseModel
{
    const DEFAULT_AVATAR_PATH = '/images/default_avatar.png';

    public $_images_extensions = [
        'jpg', 'jpeg', 'png', 'gif', 'svg'
    ];
    public $_icon_classes = [
        'csv' => 'bi-filetype-csv',
        'doc' => 'bi-filetype-doc',
        'docx' => 'bi-filetype-docx',
        'exe' => 'bi-filetype-exe',
        'file' => 'bi-filetype-file',
        'html' => 'bi-filetype-html',
        'pdf' => 'bi-filetype-pdf',
        'ppt' => 'bi-filetype-ppt',
        'pptx' => 'bi-filetype-pptx',
        'psd' => 'bi-filetype-psd',
        'txt' => 'bi-filetype-txt',
        'xls' => 'bi-filetype-xls',
        'xlsx' => 'bi-filetype-xlsx',
        'zip' => 'bi-file-earmark-zip',
    ];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%images}}';
    }

    /**
     * @return string
     */
    public static function modelName()
    {
        return 'Изображения';
    }

    /**
     * @return int
     */
    public static function typeId()
    {
        return Gallery::TYPE_IMAGE;
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['description', 'short_description'], 'string'],
            [['gallery_id'], 'integer'],
            [['name', 'path'], 'string', 'max' => 255],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'name' => 'Название',
            'description' => 'Описание',
            'short_description' => 'Короткое описание',
            'path' => 'Путь',
            'gallery_id' => 'Галерея',
        ]);
    }

    /**
     * @return bool
     */
    public function beforeDelete()
    {
        if (file_exists(Yii::getAlias('@upload').$this->path)) {
            unlink(Yii::getAlias('@upload').$this->path);
        }
        return parent::beforeDelete();
    }

    /**
     * @param $path
     * @return Image
     */
    public static function create($path, $galleryId = null) {
        $image = new self();
        $image->path = $path;
        if($galleryId) $image->gallery_id = $galleryId;
        $image->save();
        return $image;
    }

    /**
     * Получает расширение файла
     */
    public function getExtension()
    {
        if($this->path and ($pathValues = explode('.', $this->path))) {
            return end($pathValues);
        }
        return false;
    }

    /**
     * Возвращает является ли файл изображением
     */
    public function getIsImage()
    {
        return $this->extension and in_array($this->extension, $this->_images_extensions);
    }

    /**
     * Возвращает тег изображения
     */
    public function getImg($width = 100, $height = 100)
    {
        if($extension = $this->extension) {
            if(in_array($extension, $this->_images_extensions)) {
                return EasyThumbnailImage::thumbnailImg(Yii::getAlias('@upload').$this->path, $width, $height, EasyThumbnailImage::THUMBNAIL_OUTBOUND);
            }
            else {
                return $this->getExtensionSvg($width, $height, '#000');
            }
        }
        return false;
    }

    /**
     * возвращает URL для просмотра изображения, либо скачивания файла
     */
    public function getFileUrl($width = 1000, $height = 1000)
    {
        if($extension = $this->extension) {
            if($this->isImage) {
                return EasyThumbnailImage::thumbnailFileUrl(Yii::getAlias('@upload').$this->path, $width, $height, EasyThumbnailImage::THUMBNAIL_OUTBOUND);
            }
            return '/upload'.$this->path;
        }
        return false;
    }

    public function getFilePath()
    {
        return '/upload/'.$this->path;
    }

    /**
     * Возвращает иконку файла в формате svg
     */
    public function getExtensionSvg($width = 16, $height = 16, $fill = 'currentColor')
    {
        if($this->extension) {
            $filePath = Yii::getAlias('@backend').'/views/icons/'.$this->extension.'.php';
            $viewName = 'file';
            if(file_exists($filePath)) {
                $viewName = $this->extension;
            }
            return Yii::$app->controller->renderPartial('//icons/'.$viewName, [
                'width' => $width,
                'height' => $height,
                'fill' => $fill,
            ]);
        }
        return false;
    }

    /**
     * Возвращает иконку файла в теге <i>
     */
    public function getExtensionI($sizeClass = '')
    {
        if($this->extension) {
            $iconClass = $this->_icon_classes['file'];
            if(array_key_exists($this->extension, $this->_icon_classes)) {
                $iconClass = $this->_icon_classes[$this->extension];
            }
            return "<i class='bi {$iconClass} {$sizeClass}'></i>";
        }
        return false;
    }

    /**
     * добавляет файлам атрибут для галереи или скачивания
     */
    public function galleryAttributes()
    {
        $totalAttributes = '';
        $fancyBox = ' data-fancybox="gallery" ';
        $blanc = ' target="_blanc" download ';
        if($this->isImage) {
            $totalAttributes .= $fancyBox;
        }
        else {
            $totalAttributes .= $blanc;
        }
        return $totalAttributes;
    }
}
