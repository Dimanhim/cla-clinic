<?php

namespace app\controllers;

use app\components\BaseWebhook;
use app\models\Relation;
use app\models\RelationSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * RelationController implements the CRUD actions for Relation model.
 */
class RelationController extends BaseController
{
    /**
     * @inheritDoc
     */
    public function behaviors()
    {
        return array_merge(
            parent::behaviors(),
            [
                'className' => Relation::className(),
            ]
        );
    }

    /**
     * Lists all Relation models.
     *
     * @return string
     */
    public function actionIndex()
    {
        $searchModel = new RelationSearch();
        $dataProvider = $searchModel->search($this->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionJson()
    {
        //return true;
        ini_set('memory_limit','9048M');
        $filename = 'app_created.json';
        $json = file_get_contents($filename);
        $data = json_decode($json, true);
        if($data === null) return 'Данных не обнаружено';

        $count = 0;
        // здесь проверить
        // дописать методы быстрой выгрузки app_created, c учетом is_deleted
        foreach($data as $leadId => $appId) {
            $relation = new Relation();
            $relation->lead_id = $leadId;
            $relation->appointment_id = $appId;
            if($relation->save()){
                $count++;
            }
        }
        return 'Добавлено ' . $count . ' из ' . count($data);
    }

    /**
     * Displays a single Relation model.
     * @param int $id ID
     * @return string
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        throw new NotFoundHttpException('The requested page does not exist.');
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new Relation model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        throw new NotFoundHttpException('The requested page does not exist.');
        $model = new Relation();

        if ($this->request->isPost) {
            if ($model->load($this->request->post()) && $model->save()) {
                return $this->redirect(['view', 'id' => $model->id]);
            }
        } else {
            $model->loadDefaultValues();
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing Relation model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param int $id ID
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        throw new NotFoundHttpException('The requested page does not exist.');
        $model = $this->findModel($id);

        if ($this->request->isPost && $model->load($this->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    public function actionProblem()
    {
        $time = time();
        $problemStr = Relation::getProblem();
        $problemArr = explode("\n", $problemStr);
        $data = [];
        $count = 0;

        if($problemArr){
            foreach($problemArr as $problem){
                if(trim($problem)){
                    $data[] = trim($problem);
                }
            }
        }

        if($data) {
            foreach($data as $leadId){

                if($countRel = Relation::deleteLead($leadId)) {
                    $count += $countRel;
                }
            }
        }
        return 'Удалено ' . $count . ' за ' . (time() - $time) . ' секунд';

    }
}
