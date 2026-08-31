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
        return true;

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
        return true;

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

    /**
     * Дедупликация relations: /relation/dedupe?step=1|2|3
     *
     * step=1 — собрать группы дублей, залогировать, вывести сводку
     * step=2 — п.1 + список id на удаление (только активные deleted IS NULL), залогировать, вывести количество
     * step=3 — п.1–2 + soft delete, залогировать результат
     */
    public function actionDedupe()
    {
        $step = (int)$this->request->get('step', 0);
        if(!in_array($step, [1, 2, 3], true)) {
            return $this->formatDedupeResponse(
                'Укажите step: 1 (сбор дублей), 2 (id на удаление), 3 (удаление). Пример: /relation/dedupe?step=1'
            );
        }

        $doubles = $this->collectDuplicateGroups();
        $idsToDelete = $this->getDuplicateIdsToDelete($doubles);

        if($step === 1) {
            $summary = $this->buildDuplicateSummary($doubles);
            $this->logDedupe('step=1 summary', $summary);
            $this->logDedupe('step=1 groups', $doubles);

            return $this->formatDedupeResponse(implode("\n", [
                'Шаг 1: сбор дублей завершён',
                'Групп дублей (lead_id + appointment_id): ' . $summary['groups_count'],
                'Всего лишних строк в группах: ' . $summary['extra_rows_count'],
                'Из них активных (deleted IS NULL): ' . $summary['active_extra_rows_count'],
                'Примеры (до 10 групп):',
                $this->formatDuplicateExamples($doubles, 10),
                'Полный массив записан в relation-dedupe-log.txt',
            ]));
        }

        if($step === 2) {
            $summary = $this->buildDuplicateSummary($doubles);
            $this->logDedupe('step=2 summary', $summary);
            $this->logDedupe('step=2 ids_to_delete', $idsToDelete);

            return $this->formatDedupeResponse(implode("\n", [
                'Шаг 2: id на удаление (активные дубли, кроме самой старой записи в группе)',
                'Групп дублей: ' . $summary['groups_count'],
                'К удалению (deleted IS NULL): ' . count($idsToDelete),
                'Первые 50 id: ' . implode(', ', array_slice($idsToDelete, 0, 50)),
                count($idsToDelete) > 50 ? '...' : '',
                'Полный список id записан в relation-dedupe-log.txt',
                'Для удаления: /relation/dedupe?step=3',
            ]));
        }

        $deleted = 0;
        $failed = 0;
        $results = [];

        foreach($idsToDelete as $id) {
            $model = Relation::findOne($id);
            if(!$model) {
                $failed++;
                $results[] = ['id' => $id, 'status' => 'not_found'];
                continue;
            }

            if($model->delete()) {
                $deleted++;
                $results[] = [
                    'id' => $id,
                    'status' => 'deleted',
                    'lead_id' => $model->lead_id,
                    'appointment_id' => $model->appointment_id,
                ];
            } else {
                $failed++;
                $results[] = [
                    'id' => $id,
                    'status' => 'error',
                    'errors' => $model->errors,
                ];
            }
        }

        $this->logDedupe('step=3 delete_result', [
            'deleted' => $deleted,
            'failed' => $failed,
            'results' => $results,
        ]);

        return $this->formatDedupeResponse(implode("\n", [
            'Шаг 3: удаление завершено (soft delete)',
            'Успешно: ' . $deleted,
            'Ошибок / не найдено: ' . $failed,
            'Подробности в relation-dedupe-log.txt',
        ]));
    }

    /**
     * @return array<int, array<int, array<int, array>>>
     */
    private function collectDuplicateGroups(): array
    {
        $rows = Relation::find()
            ->orderBy([
                'lead_id' => SORT_ASC,
                'appointment_id' => SORT_ASC,
                'created_at' => SORT_ASC,
                'id' => SORT_ASC,
            ])
            ->asArray()
            ->all();

        $groups = [];
        foreach($rows as $row) {
            $groups[$row['lead_id']][$row['appointment_id']][] = [
                'id' => (int)$row['id'],
                'lead_id' => (int)$row['lead_id'],
                'appointment_id' => (int)$row['appointment_id'],
                'deleted' => $row['deleted'],
                'created_at' => (int)$row['created_at'],
            ];
        }

        $doubles = [];
        foreach($groups as $leadId => $byAppointment) {
            foreach($byAppointment as $appointmentId => $items) {
                if(count($items) > 1) {
                    $doubles[$leadId][$appointmentId] = $items;
                }
            }
        }

        return $doubles;
    }

    /**
     * Оставляем самую старую запись в группе, остальные активные — на удаление.
     *
     * @param array $doubles
     * @return int[]
     */
    private function getDuplicateIdsToDelete(array $doubles): array
    {
        $ids = [];

        foreach($doubles as $itemsByAppointment) {
            foreach($itemsByAppointment as $items) {
                array_shift($items);

                foreach($items as $item) {
                    if($item['deleted'] === null || $item['deleted'] === '') {
                        $ids[] = $item['id'];
                    }
                }
            }
        }

        sort($ids);

        return $ids;
    }

    /**
     * @param array $doubles
     * @return array{groups_count:int,extra_rows_count:int,active_extra_rows_count:int}
     */
    private function buildDuplicateSummary(array $doubles): array
    {
        $groupsCount = 0;
        $extraRows = 0;
        $activeExtraRows = 0;

        foreach($doubles as $itemsByAppointment) {
            foreach($itemsByAppointment as $items) {
                $groupsCount++;
                $keepers = array_shift($items);
                unset($keepers);
                $extraRows += count($items);

                foreach($items as $item) {
                    if($item['deleted'] === null || $item['deleted'] === '') {
                        $activeExtraRows++;
                    }
                }
            }
        }

        return [
            'groups_count' => $groupsCount,
            'extra_rows_count' => $extraRows,
            'active_extra_rows_count' => $activeExtraRows,
        ];
    }

    /**
     * @param array $doubles
     * @param int $limit
     * @return string
     */
    private function formatDuplicateExamples(array $doubles, int $limit = 10): string
    {
        $lines = [];
        $shown = 0;

        foreach($doubles as $leadId => $itemsByAppointment) {
            foreach($itemsByAppointment as $appointmentId => $items) {
                if($shown >= $limit) {
                    break 2;
                }

                $ids = array_column($items, 'id');
                $deletedFlags = array_map(static function ($item) {
                    return $item['deleted'] === null ? 'null' : (string)$item['deleted'];
                }, $items);

                $lines[] = sprintf(
                    '- lead_id=%s appointment_id=%s rows=%s ids=[%s] deleted=[%s]',
                    $leadId,
                    $appointmentId,
                    count($items),
                    implode(',', $ids),
                    implode(',', $deletedFlags)
                );
                $shown++;
            }
        }

        return $lines ? implode("\n", $lines) : '- дублей не найдено';
    }

    /**
     * @param string $title
     * @param mixed $data
     */
    private function logDedupe(string $title, $data): void
    {
        $logFile = \Yii::getAlias('@webroot/relation-dedupe-log.txt');
        $payload = is_string($data) ? $data : print_r($data, true);
        file_put_contents(
            $logFile,
            date('d.m.Y H:i:s') . ' ' . $title . "\n" . $payload . "\n\n",
            FILE_APPEND
        );
    }

    /**
     * @param string $text
     * @return string
     */
    private function formatDedupeResponse(string $text): string
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_RAW;
        \Yii::$app->response->headers->set('Content-Type', 'text/plain; charset=UTF-8');

        return $text;
    }
}
