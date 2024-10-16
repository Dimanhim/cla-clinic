<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use himiklab\sortablegrid\SortableGridAction;
use yii\filters\AccessControl;

/**
 1. переписываем behavours
 2. удаляем actionDelete
 3. удаляем findModel
*/
class BaseController extends Controller
{
    /**
     * @param $action
     * @return bool
     * @throws \yii\web\BadRequestHttpException
     */
    public function beforeAction($action)
    {
        if(($model = $this->getModel()) && ($modelName = $model::modelName())) {
            $this->view->title = $modelName;
        }

        return parent::beforeAction($action);
    }

    /**
     * @inheritDoc
     */
    public function behaviors()
    {
        return array_merge(
            parent::behaviors(),
            [
                'access' => [
                    'class' => AccessControl::className(),
                    'rules' => [
                        [
                            'allow' => true,
                            'roles' => ['@'],
                        ],
                    ]
                ],
            ],
        );
    }

    /**
     * @return array
     */
    public function actions()
    {
        return [
            'sort' => [
                'class' => SortableGridAction::className(),
                'modelName' => $this->behaviors()['className'],
            ],
        ];
    }

    /**
     * Deletes an existing ClinicType model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param int $id ID
     * @return \yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        $model->deleted = 1;
        if($model->save()) {
            Yii::$app->session->setFlash('success', 'Запись удалена успешно');
        }
        return $this->redirect(Yii::$app->request->referrer);
        //$this->findModel($id)->delete();
        //return $this->redirect(['index']);
    }

    /**
     * Finds the ClinicType model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id ID
     * @return ClinicType the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function findModel($id)
    {
        if(array_key_exists('className', $this->behaviors()) && ($model = $this->getModel())) {
            if(($findModel = $model::findOne(['id' => $id])) !== null) {
                return $findModel;
            }
        }
        throw new NotFoundHttpException('Запрошенная страница не существует');
    }

    public function getModel()
    {
        $behaviors = $this->behaviors();
        if(array_key_exists('className', $this->behaviors())) {
            return $behaviors['className'];
        }
        return false;
    }
}
