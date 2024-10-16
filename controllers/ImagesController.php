<?php

namespace app\controllers;

use app\models\Client;
use Yii;
use app\models\Image;
use yii\web\Response;

class ImagesController extends BaseController
{
    /**
     * @inheritDoc
     */
    public function behaviors()
    {
        return array_merge(
            parent::behaviors(),
            [
                'className' => Image::className(),
            ]
        );
    }

    /**
     * @param int $id
     * @return \yii\web\Response
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     * @throws \yii\web\NotFoundHttpException
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionSaveSort()
    {
        $response = ['result' => false, 'message' => null];
        Yii::$app->response->format = Response::FORMAT_JSON;
        $sort = Yii::$app->request->post();
        if($sort && $sort['ids'] && ($ids = json_decode($sort['ids'], true))) {
            foreach($ids as $position => $imageId) {
                if($image = Image::findOne($imageId)) {
                    $image->position = $position;
                    $image->save();
                }
            }
            $response['result'] = true;
            $response['message'] = 'Сортировка изображений успешно сохранена';
        }
        return $response;
    }

}
