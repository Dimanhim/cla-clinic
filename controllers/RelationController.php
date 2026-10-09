<?php

namespace app\controllers;

use app\components\BaseWebhook;
use app\models\Relation;
use app\models\RelationSearch;
use yii\db\Query;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * RelationController implements the CRUD actions for Relation model.
 */
class RelationController extends BaseController
{
    private const IMPORT_DUPLICATE_DATE = '2026-08-20';
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
     * Один скрипт из трёх частей:
     * 1. Сбор массива дублей → relation-dedupe_step_1-log.txt
     * 2. Id на удаление из п.1 → relation-dedupe_step_2-log.txt
     * 3. Удаление из п.2 → relation-dedupe_step_3-log.txt
     *
     * step=1 — только п.1; step=2 — п.1+2; step=3 — п.1+2+3.
     */
    public function actionDedupe()
    {
        return true;

        set_time_limit(0);
        ini_set('max_execution_time', '0');

        $step = (int)$this->request->get('step', 0);
        if(!in_array($step, [1, 2, 3], true)) {
            return $this->formatDedupeResponse(
                'Укажите step: 1 (сбор дублей), 2 (id на удаление), 3 (удаление). Пример: /relation/dedupe?step=1'
            );
        }

        return $this->runDedupe($step);
    }

    /**
     * @param int $step
     * @return string
     */
    private function runDedupe(int $step): string
    {
        // 1. Собираем массив дублей
        $doubles = $this->collectDuplicateGroups();
        $summary = $this->buildDuplicateSummary($doubles);
        $this->appendDedupeStepLog(1, [
            'SUMMARY' => $summary,
            'EXAMPLES' => $this->formatDuplicateExamples($doubles, 10),
        ]);

        if($step === 1) {
            return $this->formatDedupeResponse(implode("\n", [
                'Шаг 1: сбор дублей завершён',
                'Групп (lead_id + appointment_id): ' . $summary['groups_count'],
                'С оригиналом deleted=1: ' . $summary['with_original_count'],
                'Без оригинала deleted=1: ' . $summary['without_original_count'],
                'К удалению всего (deleted IS NULL): ' . $summary['to_delete_count'],
                'К удалению за ' . $summary['import_date_label'] . ' (шаги 2–3): ' . $summary['import_to_delete_count'],
                '',
                'Примеры (до 10 групп, даты в формате дд.мм.гггг чч:мм:сс):',
                $this->formatDuplicateExamples($doubles, 10),
                '',
                'Лог: ' . $this->getDedupeStepLogFile(1),
            ]));
        }

        // 2. Id на удаление из массива п.1
        $deleteList = $this->buildDeleteList($doubles);
        $idsToDelete = $this->getDuplicateIdsToDelete($doubles);
        $this->appendDedupeStepLog(2, [
            'IMPORT_DATE' => self::IMPORT_DUPLICATE_DATE,
            'COUNT' => count($deleteList),
            'IDS' => $idsToDelete,
            'DELETE_LIST_PREVIEW' => $this->formatDeleteListPreview($deleteList, 20),
        ]);

        if($step === 2) {
            return $this->formatDedupeResponse(implode("\n", [
                'Шаг 2: id на удаление — только дубли от ' . $summary['import_date_label'],
                'Групп дублей всего: ' . $summary['groups_count'],
                'К удалению за ' . $summary['import_date_label'] . ': ' . count($deleteList),
                'Всего active-дублей (любая дата): ' . $summary['to_delete_count'],
                'Первые 20 записей:',
                $this->formatDeleteListPreview($deleteList, 20),
                count($deleteList) > 20 ? '...' : '',
                'Лог: ' . $this->getDedupeStepLogFile(2),
                'Для удаления: /relation/dedupe?step=3',
            ]));
        }

        // 3. Удаление позиций из п.2
        $deleteResult = $this->executeDedupeDelete($idsToDelete);
        $this->appendDedupeStepLog(3, [
            'IMPORT_DATE' => self::IMPORT_DUPLICATE_DATE,
            'IDS_TO_DELETE_COUNT' => count($idsToDelete),
            'DELETED' => $deleteResult['deleted'],
            'FAILED' => $deleteResult['failed'],
            'RESULT_PREVIEW' => array_slice($deleteResult['results'], 0, 20),
        ]);

        return $this->formatDedupeResponse(implode("\n", [
            'Шаг 3: удаление завершено (soft delete, строки остаются в таблице с deleted=1)',
            'Дата отбора дублей: ' . self::IMPORT_DUPLICATE_DATE,
            'К удалению было: ' . count($idsToDelete),
            'Успешно: ' . $deleteResult['deleted'],
            'Ошибок / не найдено: ' . $deleteResult['failed'],
            'Лог: ' . $this->getDedupeStepLogFile(3),
        ]));
    }

    /**
     * @param int[] $idsToDelete
     * @return array{deleted:int,failed:int,results:array}
     */
    private function executeDedupeDelete(array $idsToDelete): array
    {
        if(!$idsToDelete) {
            return [
                'deleted' => 0,
                'failed' => 0,
                'results' => [],
            ];
        }

        $updatedAt = time();
        $deleted = 0;
        $results = [];

        foreach(array_chunk($idsToDelete, 500) as $chunk) {
            $deleted += Relation::updateAll(
                ['deleted' => 1, 'updated_at' => $updatedAt],
                ['and', ['id' => $chunk], ['deleted' => null]]
            );
        }

        $failed = count($idsToDelete) - $deleted;
        if($failed > 0) {
            $results[] = [
                'status' => 'not_found_or_already_deleted',
                'count' => $failed,
            ];
        }

        return [
            'deleted' => $deleted,
            'failed' => $failed,
            'results' => $results,
        ];
    }

    /**
     * @param int $step 1|2|3
     * @param array $data
     */
    private function appendDedupeStepLog(int $step, array $data): void
    {
        $lines = [
            str_repeat('=', 70),
            'DEDUPE этап ' . $step . ' @ ' . date('d.m.Y H:i:s'),
            str_repeat('=', 70),
            print_r($data, true),
            '',
        ];

        file_put_contents($this->getDedupeStepLogFile($step), implode("\n", $lines), FILE_APPEND);
    }

    /**
     * @param int $step 1|2|3
     * @return string
     */
    private function getDedupeStepLogFile(int $step): string
    {
        return \Yii::getAlias('@webroot/relation-dedupe_step_' . $step . '-log.txt');
    }

    /**
     * @return array<int, array{
     *     lead_id: int,
     *     appointment_id: int,
     *     original_appointment: array|null,
     *     appointments_doubles: array<int, array>
     * }>
     */
    private function collectDuplicateGroups(): array
    {
        $rows = \Yii::$app->db->createCommand("
            SELECT r.id, r.lead_id, r.appointment_id, r.deleted, r.created_at
            FROM relations r
            INNER JOIN (
                SELECT lead_id, appointment_id
                FROM relations
                GROUP BY lead_id, appointment_id
                HAVING COUNT(*) >= 2
            ) d ON r.lead_id = d.lead_id AND r.appointment_id = d.appointment_id
            ORDER BY r.lead_id ASC, r.appointment_id ASC, r.created_at ASC, r.id ASC
        ")->queryAll();

        if(!$rows) {
            return [];
        }

        $groups = [];
        foreach($rows as $row) {
            $groups[$row['lead_id']][$row['appointment_id']][] = $this->formatRelationRow($row);
        }

        $doubles = [];
        foreach($groups as $leadId => $byAppointment) {
            foreach($byAppointment as $appointmentId => $items) {
                if(count($items) < 2) {
                    continue;
                }

                $group = $this->splitDuplicateGroup((int)$leadId, (int)$appointmentId, $items);
                if(!$group['appointments_doubles']) {
                    continue;
                }

                $doubles[] = $group;
            }
        }

        return $doubles;
    }

    /**
     * @param array $row
     * @return array{id:int,lead_id:int,appointment_id:int,deleted:mixed,created_at:int}
     */
    private function formatRelationRow(array $row): array
    {
        $createdAt = (int)$row['created_at'];

        return [
            'id' => (int)$row['id'],
            'lead_id' => (int)$row['lead_id'],
            'appointment_id' => (int)$row['appointment_id'],
            'deleted' => $row['deleted'],
            'created_at' => $createdAt,
            'created_at_dt' => $this->formatCreatedAt($createdAt),
        ];
    }

    /**
     * @param int $timestamp
     * @return string
     */
    private function formatCreatedAt(int $timestamp): string
    {
        if($timestamp <= 0) {
            return '—';
        }

        return date('d.m.Y H:i:s', $timestamp);
    }

    /**
     * original_appointment — запись с deleted=1 (оригинал, снятый при отмене).
     * appointments_doubles — все записи с deleted IS NULL (подлежат удалению).
     *
     * @param int $leadId
     * @param int $appointmentId
     * @param array<int, array> $items
     * @return array{
     *     lead_id: int,
     *     appointment_id: int,
     *     original_appointment: array|null,
     *     appointments_doubles: array<int, array>
     * }
     */
    private function splitDuplicateGroup(int $leadId, int $appointmentId, array $items): array
    {
        $original = null;
        $doubles = [];

        foreach($items as $item) {
            if($this->isSoftDeletedRelation($item)) {
                if(!$original || $item['created_at'] < $original['created_at']) {
                    $original = $item;
                }
                continue;
            }

            if($this->isActiveRelation($item)) {
                $doubles[] = $item;
            }
        }

        return [
            'lead_id' => $leadId,
            'appointment_id' => $appointmentId,
            'original_appointment' => $original,
            'appointments_doubles' => array_values($doubles),
        ];
    }

    /**
     * @param array $row
     * @return bool
     */
    private function isActiveRelation(array $row): bool
    {
        return $row['deleted'] === null || $row['deleted'] === '';
    }

    /**
     * @param array $row
     * @return bool
     */
    private function isSoftDeletedRelation(array $row): bool
    {
        return (int)$row['deleted'] === 1;
    }

    /**
     * @param array $row
     * @return bool
     */
    private function isImportDuplicateRow(array $row): bool
    {
        $createdAt = (int)$row['created_at'];
        $range = $this->getImportDuplicateDateRange();

        return $createdAt >= $range['from'] && $createdAt <= $range['to'];
    }

    /**
     * @return array{from:int,to:int,label:string}
     */
    private function getImportDuplicateDateRange(): array
    {
        return [
            'from' => strtotime(self::IMPORT_DUPLICATE_DATE . ' 00:00:00'),
            'to' => strtotime(self::IMPORT_DUPLICATE_DATE . ' 23:59:59'),
            'label' => date('d.m.Y', strtotime(self::IMPORT_DUPLICATE_DATE)),
        ];
    }

    /**
     * @param array $doubles
     * @return int[]
     */
    private function getDuplicateIdsToDelete(array $doubles): array
    {
        $ids = [];

        foreach($doubles as $group) {
            foreach($group['appointments_doubles'] as $item) {
                if($this->isImportDuplicateRow($item)) {
                    $ids[] = $item['id'];
                }
            }
        }

        sort($ids);

        return $ids;
    }

    /**
     * @param array $doubles
     * @return array{
     *     groups_count: int,
     *     with_original_count: int,
     *     without_original_count: int,
     *     to_delete_count: int,
     *     import_to_delete_count: int,
     *     import_date_label: string
     * }
     */
    private function buildDuplicateSummary(array $doubles): array
    {
        $withOriginal = 0;
        $withoutOriginal = 0;
        $toDelete = 0;
        $importToDelete = 0;

        foreach($doubles as $group) {
            if($group['original_appointment']) {
                $withOriginal++;
            } else {
                $withoutOriginal++;
            }

            foreach($group['appointments_doubles'] as $item) {
                $toDelete++;
                if($this->isImportDuplicateRow($item)) {
                    $importToDelete++;
                }
            }
        }

        return [
            'groups_count' => count($doubles),
            'with_original_count' => $withOriginal,
            'without_original_count' => $withoutOriginal,
            'to_delete_count' => $toDelete,
            'import_to_delete_count' => $importToDelete,
            'import_date_label' => $this->getImportDuplicateDateRange()['label'],
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

        foreach(array_slice($doubles, 0, $limit) as $group) {
            $original = $group['original_appointment'];
            $dups = $group['appointments_doubles'];

            if($original) {
                $originalPart = sprintf(
                    'original: id=%s deleted=%s created=%s',
                    $original['id'],
                    $original['deleted'],
                    $original['created_at_dt']
                );
            } else {
                $originalPart = 'original: — (нет записи с deleted=1)';
            }

            $toDeleteParts = [];
            foreach($dups as $dup) {
                $mark = $this->isImportDuplicateRow($dup) ? '[удалить]' : '[пропуск]';
                $toDeleteParts[] = sprintf(
                    '%s id=%s created=%s',
                    $mark,
                    $dup['id'],
                    $dup['created_at_dt']
                );
            }

            $lines[] = sprintf(
                "- lead_id=%s appointment_id=%s\n  %s\n  to_delete (%s): %s",
                $group['lead_id'],
                $group['appointment_id'],
                $originalPart,
                count($dups),
                $toDeleteParts ? implode('; ', $toDeleteParts) : '—'
            );
        }

        return $lines ? implode("\n", $lines) : '- дублей не найдено';
    }

    /**
     * @param array $doubles
     * @return array<int, array{id:int,lead_id:int,appointment_id:int,created_at_dt:string}>
     */
    private function buildDeleteList(array $doubles): array
    {
        $list = [];

        foreach($doubles as $group) {
            foreach($group['appointments_doubles'] as $item) {
                if(!$this->isImportDuplicateRow($item)) {
                    continue;
                }

                $list[] = [
                    'id' => $item['id'],
                    'lead_id' => $item['lead_id'],
                    'appointment_id' => $item['appointment_id'],
                    'created_at_dt' => $item['created_at_dt'],
                ];
            }
        }

        return $list;
    }

    /**
     * @param array $deleteList
     * @param int $limit
     * @return string
     */
    private function formatDeleteListPreview(array $deleteList, int $limit = 20): string
    {
        $lines = [];

        foreach(array_slice($deleteList, 0, $limit) as $item) {
            $lines[] = sprintf(
                '- id=%s lead_id=%s appointment_id=%s created=%s',
                $item['id'],
                $item['lead_id'],
                $item['appointment_id'],
                $item['created_at_dt']
            );
        }

        return $lines ? implode("\n", $lines) : '—';
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

    public function actionAppSweb()
    {
        return true;
        $ids = (new Query())
            ->select('appointment_id')
            ->from('relations')
            ->where([
                'and',
                ['>=', 'created_at', strtotime('2026-08-27 11:55:13')],
                ['<=', 'created_at', strtotime('2026-09-08 14:54:00')],
                ['deleted' => null]
            ])
            ->orderBy('created_at')
            ->column();

        $json = json_encode([
            'total' => count($ids),
            'appointment_ids' => $ids
        ], JSON_PRETTY_PRINT);

        // Сохраняем в файл
        file_put_contents('appointment_ids.json', $json);

        echo "Экспортировано " . count($ids) . " записей в runtime/appointment_ids.json\n";
    }
}
