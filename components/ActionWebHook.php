<?php

namespace app\components;

use AmoCRM\Helpers\CustomFieldHelper;
use app\components\ApiComponent;
use app\models\Clinic;
use app\models\Doctor;
use app\models\Relation;
use app\models\Step;

trait ActionWebHook
{
    /**
     * ловит хук на создание сделки
     * получает сделку и отправляет ее в МИС
     */
    public function actionCreateAppointment()
    {
        if(
            isset($this->requestBody['leads']) and
            isset($this->requestBody['leads']['add']) and
            isset($this->requestBody['leads']['add'][0])
        ) {
            $this->_lead_values = $this->requestBody['leads']['add'][0];

            // проверяем, обрабатывать ли эту сделку - воронка, клиника
            $this->checkFilterLead();



            if(!$this->hasErrors()) {

                $this->addMessage('----------------------------------------------------------');
                $this->addMessage($this->method);

                $this->setAppActionRelation();

                // устанавливает контакт _contact этой сделки
                $this->setContact();

                $this->addMessage('Установлен контакт create-appointment');

                if(!$this->hasErrors()) {
                    $this->_lead = [
                        'lead_id' => $this->_lead_values['id'],
                        'lead_name' => $this->_lead_values['name'],
                        'doctor_id' => $this->getDoctorId(),
                        'doctor' => $this->getDoctorName(),
                        'clinic_id' => $this->getClinicId(),
                        'clinic' => $this->getClinicName(),
                        'consultation_datetime' => $this->getDateTime(),
                        'consultation_type' => $this->getConsultationType(),
                        'consultation_type_id' => $this->getConsultationTypeId(),
                        'budget' => $this->_lead_values['price'],
                        'pipeline_status' => $this->getPipelineStatus(),
                        'client_name' => $this->getContactAttribute('contact_name'),
                        'client_phone' => $this->getContactAttribute('contact_phone'),
                        'client_email' => $this->getContactAttribute('contact_email'),
                        'client_birthday' => $this->getContactAttribute('contact_birthday'),
                        'client_gender' => $this->getClientGender(),
                    ];

                    $this->setLog('', $this->_lead, 'webhook-create-log');

                    $this->addMessage('Создана сделка '.$this->_lead_values['id'].', записан веб-хук');

                    // на основе сделки создает визит в МИС

                    $this->createMisAppointment();
                }
            }
            else {
                return $this->getResult();
            }
        }
        else {
            $this->addError('Веб-хук не найден или не обрабатывается', true);
        }
        return $this->getResult();
    }

    /**
     *
     */
    public function setAppActionRelation()
    {
        $this->setAppCreatedRelation();
        $this->setAppUpdatedRelation();
        $this->setHookUpdate();
    }

    /**
     * @return mixed
     */
    public function setAppUpdatedRelation()
    {
        if(!file_exists($this->_app_updated_filename)) {
            file_put_contents($this->_app_updated_filename, '');
        }
        if($content = file_get_contents($this->_app_updated_filename)) {
            if(json_decode($content, true)) return $this->getResult();
            $this->_app_updated = json_decode($content, true);
            $this->addMessage('Установлено свойство _app_updated');
        }
    }

    public function setHookUpdate()
    {
        if(!file_exists($this->_update_hook_filename)) {
            file_put_contents($this->_update_hook_filename, '');
        }
        if($content = file_get_contents($this->_update_hook_filename)) {
            $this->_update_hook = json_decode($content, true);
            $this->addMessage('Установлено свойство _hook_update');
        }
    }

    /**
     * @param $leadId
     */
    public function setLeadIdToUpdateRelation($leadId)
    {
        file_put_contents($this->_app_updated_filename, json_encode(['lead_id' => $leadId]));
        //$this->addMessage('В файл _app_updated установлен lead_id '.$leadId);
    }

    /**
     *
     */
    public function removeAppUpdatedRelation()
    {
        file_put_contents($this->_app_updated_filename, '');
    }

    /**
     *
     */
    public function setAppCreatedRelation()
    {
        $this->_app_created = Relation::getRelations();
        if($this->_app_created) {
            $this->addMessage('Установлено свойство _app_created');
        }
        else {
            $this->addMessage('Ошибка установления свойства _app_created');
        }
        return true;

        if(!file_exists($this->_app_created_filename)) {
            file_put_contents($this->_app_created_filename, '');
        }
        if($content = file_get_contents($this->_app_created_filename)) {

            $this->_app_created = json_decode($content, true);
            if($this->_app_created) {
                $this->addMessage('Установлено свойство _app_created');
            }
            else {
                $this->addMessage('Ошибка установления свойства _app_created');
            }
        }
    }

    public function unsetAppCreatedRelation($appointmentId = null, $leadId = null)
    {
        if(!$appointmentId && !$leadId) return false;

        Relation::deleteRelation($leadId, $appointmentId);
        $this->setAppActionRelation();
        return true;

        if($appointmentId) {
            $key = array_search($appointmentId, $this->_app_created);
            unset($this->_app_created[$key]);
        }

        if($leadId) {
            if(isset($this->_app_created[$leadId])) {
                unset($this->_app_created[$leadId]);
            }
        }

        file_put_contents($this->_app_created_filename, json_encode($this->_app_created));

    }

    /**
     * @param array $values
     * @return bool|mixed
     */
    public function setAppointmentRelationValues($values = [])
    {
        if(!$values) {
            $this->addError('пустой массив передан в setAppointmentRelationValues', true);
            return $this->getResult();
        }

        $content = [];

        if($fileContent = file_get_contents($this->_app_created_filename)) {
            $content = json_decode($fileContent, true);
        }

        $amoLeadId = array_key_first($values);
        $misAppointmentId = $values[$amoLeadId];
        if(!$amoLeadId or !$misAppointmentId) return false;

        Relation::setRelation($amoLeadId, $misAppointmentId);
        $this->addMessage('В файл установлен новый визит '.$amoLeadId.' - '.$misAppointmentId);
        $this->setAppCreatedRelation();
        //return true;

        $content[$amoLeadId] = $misAppointmentId;

        file_put_contents($this->_app_created_filename, json_encode($content));
        //$this->addMessage('В файл установлен новый визит '.$amoLeadId.' - '.$misAppointmentId);
        //$this->setAppCreatedRelation();

    }

    /**
     *  ловит хук на обновление сделки
     *  редактирует ее в МИС, если соблюдены условия
     */
    public function actionUpdateAppointment()
    {
        //$this->addMessage('Статус сделки изменен');
        //$this->addMessage($this->requestBody);
        if(
            isset($this->requestBody['leads']) and
            isset($this->requestBody['leads']['update']) and
            isset($this->requestBody['leads']['update'][0])
        ) {
            $this->_lead_values = $this->requestBody['leads']['update'][0];

            //$this->checkLeadAppointment();

            $this->setClinic($this->getClinicId());

            // проверяем, обрабатывать ли эту сделку
            $this->checkFilterLead();

            if(!$this->hasErrors()) {

                $this->addMessage('----------------------------------------------------------');
                $this->addMessage($this->method);

                $this->addMessage('Установлена клиника _clinic');
                $this->addMessage($this->_clinic);

                //$this->addMessage('_lead_values');
                //$this->addMessage($this->_lead_values);

                $this->setAppActionRelation();

                // устанавливает контакт _contact этой сделки
                $this->setContact();

                $this->addMessage('Установлен контакт update-appointment');

                $this->setLeadIdToUpdateRelation($this->_lead_values['id']);

                $this->_lead = [
                    'lead_id' => $this->_lead_values['id'],
                    'lead_name' => $this->_lead_values['name'],
                    'doctor_id' => $this->getDoctorId(),
                    'doctor' => $this->getDoctorName(),
                    'clinic_id' => $this->getClinicId(),
                    'clinic' => $this->getClinicName(),
                    'consultation_datetime' => $this->getDateTime(),
                    'consultation_type' => $this->getConsultationType(),
                    'consultation_type_id' => $this->getConsultationTypeId(),
                    'budget' => $this->_lead_values['price'],
                    'pipeline_status' => $this->getPipelineStatus(),
                    'pipeline_status_id' => $this->_lead_values['status_id'],
                    'client_name' => $this->getContactAttribute('contact_name'),
                    'client_phone' => $this->getContactAttribute('contact_phone'),
                    'client_email' => $this->getContactAttribute('contact_email'),
                    'client_birthday' => $this->getContactAttribute('contact_birthday'),
                    'client_gender' => $this->getClientGender(),
                    'custom_datetime' => $this->getCustomDatetime(),
                    'custom_duration' => $this->getCustomDuration(),
                    'mis_id' => $this->getMisId(),
                ];

                $this->addMessage('_lead');
                $this->addMessage($this->_lead);

                $this->setAppointmentByMisId();

                $this->addMessage('Обновляется сделка _lead_values '.$this->_lead_values['id']);
                //$this->addMessage($this->_lead_values);
                $this->addMessage('_lead');
                $this->addMessage($this->_lead);

                $this->updateMisAppointment();
            }
        }
        return $this->getResult();
    }

    public function actionUpdateStatusLead()
    {
        if(
            isset($this->requestBody['leads']) and
            isset($this->requestBody['leads']['status']) and
            isset($this->requestBody['leads']['status'][0])
        ) {
            $this->_lead_values = $this->requestBody['leads']['status'][0];

            //$this->setLog('hook', $this->_lead_values, '--hook.txt');

            $this->setClinic($this->getClinicId());

            // проверяем, обрабатывать ли эту сделку
            $this->checkFilterLead();

            if(!$this->hasErrors()) {

                $this->setLog('_lead_values', $this->_lead_values, '--lead_values-log.txt');

                $this->addMessage('----------------------------------------------------------');
                $this->addMessage($this->method);

                $this->addMessage('Установлена клиника _clinic');
                $this->addMessage($this->_clinic);

                $this->setAppActionRelation();

                // устанавливает контакт _contact этой сделки
                $this->setContact();

                $this->addMessage('Установлен контакт update-appointment');

                $this->setLeadIdToUpdateRelation($this->_lead_values['id']);

                $this->_lead = [
                    'lead_id' => $this->_lead_values['id'],
                    'lead_name' => $this->_lead_values['name'],
                    'doctor_id' => $this->getDoctorId(),
                    'doctor' => $this->getDoctorName(),
                    'clinic_id' => $this->getClinicId(),
                    'clinic' => $this->getClinicName(),
                    'consultation_datetime' => $this->getDateTime(),
                    'consultation_type' => $this->getConsultationType(),
                    'consultation_type_id' => $this->getConsultationTypeId(),
                    'budget' => $this->_lead_values['price'],
                    'pipeline_status' => $this->getPipelineStatus(),
                    'pipeline_status_id' => $this->_lead_values['status_id'],
                    'client_name' => $this->getContactAttribute('contact_name'),
                    'client_phone' => $this->getContactAttribute('contact_phone'),
                    'client_email' => $this->getContactAttribute('contact_email'),
                    'client_birthday' => $this->getContactAttribute('contact_birthday'),
                    'client_gender' => $this->getClientGender(),
                    'custom_datetime' => $this->getCustomDatetime(),
                    'custom_duration' => $this->getCustomDuration(),
                    'mis_id' => $this->getMisId(),
                ];

                $this->addMessage('_lead');
                $this->addMessage($this->_lead);

                $this->addMessage('_lead clinic_id '.$this->_lead['clinic_id']);

                $this->setClinic($this->_lead['clinic_id']);
                $this->addMessage('Установлена клиника _clinic');
                $this->addMessage($this->_clinic);

                //$this->setAppointmentByMisId();

                $this->addMessage('Обновляется сделка '.$this->_lead_values['id']);

                // если статус отменяющий визит
                if($this->isStatusForCancel()) {
                    $this->cancelAppointment();
                    return $this->getResult();
                }

                if($this->isStatusForChange()) {
                    $this->addMessage('Сделка находится в контролируемом статусе');

                    // проверяем custom_datetime

                    //if(!$this->_lead['custom_datetime']) {
                        $this->getLeadFromAmo();
                    //}
                    //
                    //if($this->_lead['mis_id']) {
                    // ???
                    if($this->unableForUpdate()) {
                        $this->addError('В сделке уже установлен параметр mis_id='.$this->_lead['mis_id'], true);
                    }

                    if($this->hasErrors()) {
                        $this->addMessage('Перед созданием визита выявлены ошибки:');
                        $this->addMessage($this->result);
                        return $this->getResult();
                    }

                    $this->addMessage('Создается визит в МИС');
                    $this->createMisAppointment('update-status');

                    // если визит уже создан
                    if($this->hasErrors()) {
                        $this->checkLeadDatetimeAndCancelAppointment();
                    }
                }
                else {
                    $this->addMessage('Сделка находитмя в неконтролируемом статусе '.$this->_lead_values['status_id']);
                }
                /*if($this->_lead_values['status_id'] == $this->params['update_status'] and !$this->_lead['mis_id']) {
                    $this->addMessage('Создается визит в МИС');
                    $this->createMisAppointment('update-status');
                }*/
            }
        }
        return $this->getResult();
    }

    public function unableForUpdate()
    {
        if(!$this->_lead['mis_id']) return false;
        $appointmentId = $this->_app_created[$this->_lead['lead_id']] ?? null;

        if(!$appointmentId) return false;

        return !!$this->_lead['mis_id'];
    }

    public function checkLeadDatetimeAndCancelAppointment()
    {
        $this->addMessage('Визит уже создан, нужно отменить errors, lead');
        $this->addMessage($this->result);
        $this->addMessage($this->_lead);

        // идем за визитом
        $this->setAppointmentByMisId();

        $this->addMessage('Обновляется сделка _lead_values '.$this->_lead_values['id']);
        $this->deleteErrors();
        //$this->addMessage($this->_lead_values);

        $this->updateMisAppointment();

        // проверяем дату [custom_datetime] => 1738047600

    }

    public function getLeadFromAmo()
    {
        sleep(30);
        $lead = $this->amo->getLead($this->_lead['lead_id']);
        $this->addMessage('запрос в amo на поиск лида с ID '.$this->_lead['lead_id']);
        //$this->addMessage($lead);
        //$this->setLog('amo_lead', $lead, '__amo-lead.txt');





        //-----------------------------
        $leadCustomFieldValues = $lead->getCustomFieldsValues();
        $this->addMessage('customFieldsValues');
        $this->addMessage($leadCustomFieldValues);
        //$this->setLog('_lead_values', $this->_lead_values, '__amo_lead_values.txt');
        //$this->setLog('lead', $lead, '__amo_lead.txt');
        //$this->setLog('customFieldValues', $leadCustomFieldValues, '__amo_lead_custom.txt');

        foreach($leadCustomFieldValues as $customFieldValue) {
            $fieldId = $customFieldValue->fieldId;
            //$this->setLog('fieldId', $fieldId, '__amo_lead_custom_items.txt');

            // если datetime
            if(!$this->_lead['custom_datetime']) {
                if($fieldId == $this->params['datetime_field_id']) {
                    $this->addMessage('fieldId = '. $fieldId);
                    if($values = $customFieldValue->values) {
                        foreach($values as $value) {
                            $this->addMessage('value = '. $value->value);
                            $this->_lead_values['custom_fields'][] = [
                                'id' => $this->params['datetime_field_id'],
                                'name' => 'Дата|Время визита Rnova',
                                'values' => [
                                    $value->value
                                ],
                            ];
                        }
                    }
                }
            }

            // если duration
            if(!$this->_lead['custom_duration']) {
                if($fieldId == $this->params['duration_field_id']) {
                    $this->addMessage('fieldId = '. $fieldId);
                    if($values = $customFieldValue->values) {
                        foreach($values as $value) {
                            $this->addMessage('value = '. $value->value);
                            $this->_lead_values['custom_fields'][] = [
                                'id' => $this->params['duration_field_id'],
                                'name' => 'Длительность визита Rnova',
                                'values' => [
                                    [
                                        'value' => $value->value
                                    ],
                                ],
                            ];
                        }
                    }
                }
            }

            // если mis_id
            if(!$this->_lead['mis_id']) {
                if($fieldId == $this->params['mis_field_id']) {
                    $this->addMessage('fieldId = '. $fieldId);
                    if($values = $customFieldValue->values) {
                        foreach($values as $value) {
                            $this->addMessage('value = '. $value->value);
                            $this->_lead_values['custom_fields'][] = [
                                'id' => $this->params['mis_field_id'],
                                'name' => 'МИС id',
                                'values' => [
                                    [
                                        'value' => $value->value
                                    ],
                                ],
                            ];
                        }
                    }
                }
            }
        }
        $this->addMessage('_lead_values 2');
        $this->addMessage($this->_lead_values);
        //-----------------------------

        $this->_lead['custom_datetime'] = $this->getCustomDatetime();
        $this->_lead['custom_duration'] = $this->getCustomDuration();
        $this->_lead['mis_id'] = $this->getMisId();

        $this->addMessage('Найден и установлен лид');
        $this->addMessage($this->_lead);

    }

    /**
     * обновляет визит в МИС на основе сделки
     *
     * @return bool
     */
    public function updateMisAppointment()
    {
        if($this->hasErrors()) return $this->getResult();

        if(!$this->_lead) {
            $this->addError('Лид для обновления не найден', true);
            return $this->getResult();
        }

        if(!$this->_appointment) {
            $this->addError('Сделка для обновления в МИС не найдена', true);
            return $this->getResult();
        }

        if($this->_appointment['status_id'] == 5) {
            $this->addError('Найденная сделка уже отменена', true);
            return $this->getResult();
        }

        // проверяем изменились ли контролируемые поля
        if($changedParam = $this->checkUpdatedParams()) {
            $this->addMessage('Найдены измененные поля сделки '.$this->_lead_values['id'].' - '.$changedParam);
            $this->createMisAppointment('update');
        }
        else {
            $this->addMessage('Измененные поля сделки '.$this->_lead_values['lead_id'].' не найдены');
        }
        return $this->getResult();
    }

    /**
     * проверяет изменились ли контролируемые поля
     *
     * @return bool
     */
    public function checkUpdatedParams()
    {
        $controlParams = [
            //'doctor_id',
            'time_start',
            //'time_end',
            //'clinic_id',
            //'last_comment',
        ];

        foreach($controlParams as $controlParam) {
            switch ($controlParam) {
                case 'doctor_id' : {
                    $amoParam = $this->_lead['doctor_id'];
                    $misParam = null;
                    foreach($this->params['doctors'] as $doctor) {
                        if($doctor['amo_id'] == $amoParam) {
                            $misParam = $doctor['mis_id'];
                            break;
                        }
                    }
                    if($misParam and $misParam != $this->_appointment['doctor_id']) {
                        return 'doctor_id';
                    }
                }
                    continue 2;
                case 'time_start' : {
                    $amoParam = date('d.m.Y H:i', $this->_lead['custom_datetime']);
                    $this->addMessage('appointment time_start - '. $this->_appointment['time_start']);
                    $this->addMessage('amo param time_start - '. $amoParam);
                    if(strtotime($amoParam) != strtotime($this->_appointment['time_start'])) {
                        $this->addMessage('time_start');
                        return 'time_start';
                    }
                }
                    continue 2;
                case 'time_end' : {
                    $amoParam = date('d.m.Y H:i', $this->_lead['custom_datetime'] + $this->getDurationTime() * 60);
                    if($amoParam != $this->_appointment['time_end']) {
                        return 'time_end';
                    }
                }
                    continue 2;
                case 'last_comment' : {
                    $amoParam = $this->_lead['consultation_type'];
                    if($amoParam != $this->_appointment['last_comment']) {
                        return 'last_comment';
                    }
                }
                    continue 2;

            }
        }

        return false;
    }

    /*public function checkLeadAppointment()
    {
        if(!$lead = $this->_lead_values) {
            $this->addError('Лид в вебхуке не найден');
            return $this->getResult();
        }

        if(!$this->_app_created) {
            $this->addError('app_created не установлен');
            return $this->getResult();
        }

        if(isset($this->_app_created[$this->_lead_values['id']])) {
            $this->addError('Сделка '.$this->_lead_values['id'].' уже обрабатывалась', true);
            return $this->getResult();
        }
    }*/

    /**
     * проверяет, обрабатывать ли сделку
     *
     * @param $lead
     * @return bool
     */
    public function checkFilterLead()
    {
        if(!$lead = $this->_lead_values) {
            $this->addError('Лид в вебхуке не найден', true);
            return false;
        }

        if(!isset($lead['status_id'])) {
            $this->addError('В лиде не найден статус', true);
            return false;
        }

        if($this->hasErrors()) return false;

        // проверяем воронку
        if(isset($lead['pipeline_id'])) {

            if(in_array($lead['pipeline_id'], $this->params['pipeline_id'])) {

                // если хук на изменение, проверяем еще и статусы (этапы) воронки
                if($this->method == 'update-appointment') {
                    if(!array_key_exists($lead['status_id'], $this->_clinic['edit_statuses'])) {
                        $this->addError('Сделка находится в неконтролируемом статусе '.$lead['status_id'], true);
                        return false;
                    }
                }

                // проверяем клинику
                $amoClinicId = $this->getClinicId($lead);

                /*if($this->params and isset($this->params['clinic_id'])) {
                    if($this->params['clinic_id'] != $amoClinicId) {
                        $this->addError('Данная клиника не обрабатывается '.$amoClinicId);
                        return false;
                    }
                    return true;
                }*/

                return $this->validateClinicSetApi($amoClinicId);
            }

            $this->addError('Данная воронка не обрабатывается '.$lead['pipeline_id'], true);
            return false;
        }

        $this->addError('Воронка не задана в ответе сделки', true);
        return false;
    }

    /**
     * @return bool
     */
    public function validateClinicSetApi()
    {
        if($clinicModel = Clinic::findSearch()->where(['id' => $this->_clinic['id']])->one()) {
            $this->api = new ApiComponent($this->isTest, $clinicModel->api_key);
            $this->addMessage('Клиника найдена в системе с api_key='.$clinicModel->api_key.' Создан объект API');
            return true;
        }
        $this->addError('Клиника не найдена в системе', true);
        return false;
    }

    /**
     * @return bool
     */
    public function checkAppointmentRelation()
    {
        if($this->method == 'create-appointment') {
            if(isset($this->_app_created[$this->_lead['lead_id']])) {
                $this->addError('Сделка '.$this->_lead['lead_id'].' уже обрабатывалась', true);
                return $this->getResult();
            }
        }
        elseif($this->method == 'update-appointment') {
            if(!isset($this->_app_created[$this->_lead['lead_id']])) {
                $this->addError('Сделка '.$this->_lead['lead_id'].' не добавлялась', true);
                return $this->getResult();
            }
            else {
                $this->cancelAppointment();
            }
            //if($this->_app_updated) {
            //    $this->addError('Сделка еще не обработана');
            //    return false;
            //}
            //else
            //{
            //$this->cancelAppointment();
            //}
        }
        elseif($this->method == 'update-status-lead') {
            $this->cancelAppointment();
        }
    }

    /**
     * создает визит в МИС
     * @return bool
     */
    public function createMisAppointment($from = 'create')
    {
        $this->addMessage('Метод createMisAppointment - '.$from);

        if(!$this->_lead) {
            $this->addError('при создании визита лид _lead не найден', true);
            return $this->getResult();
        }

        if($from == 'update-status') {
            $this->deleteErrors();
            if(!in_array($this->_lead_values['pipeline_id'], $this->avaliable_pipelines)) {
                if(is_array($this->_app_created) && array_key_exists($this->_lead_values['id'], $this->_app_created)) {
                    // здесь проверка на существование mis_id
//                if($this->_lead && isset($this->_lead['mis_id']) && $this->_lead['mis_id']) {
                    $this->addError('Визит к сделке '.$this->_lead_values['id']. ' уже создавался в системе', true);
                    return $this->getResult();
//                }
                }
            }
        }
        else {
            $this->checkAppointmentRelation();
        }


        if($this->hasErrors()) return $this->getResult();

        $params = $this->getParamsForCreateMisAppointment();

        $this->addMessage('$params:');
        $this->addMessage($params);

        if($this->hasErrors()) return $this->getResult();

        $misRequest = $this->api->createAppointment($params);

        if($misRequest and isset($misRequest['data']) and !is_array($misRequest['data'])) {
            $appointmentId = $misRequest['data'];

            $this->addMessage('Создан визит с ID '.$appointmentId);
            sleep(1);
            if($request = $this->api->getAppointments(['appointment_id' => $appointmentId])) {
                $this->addMessage('appointment после создания');
                $this->addMessage($request);
                if($request['data'] && $request['error'] == 0) {
                    $appointment = $request['data'][0] ?? $request['data'];
                    // отправляем ID визита обратно в АМО
                    $this->setAppointmentToAmo($appointment, $from);
                }
            }


        }
        else {
            $this->addMessage('misRequest error:');
            $this->addMessage($misRequest);
        }

        return $this->getResult();
    }

    /**
     * @return mixed
     */
    public function getDurationTime()
    {
        return $this->getLeadCustomFieldValue($this->_lead_values, 'custom_duration') ?: $this->params['time'];
    }

    public static function setBasePhoneFormat($phone)
    {
        $res = preg_replace('/[^0-9]/', '', $phone);

        if (strlen($res) === 11 && $res[0] === '8') {
            $res[0] = '7';
        }

        return '+' . $res;
    }

    /**
     * @return array
     */
    public function getParamsForCreateMisAppointment()
    {
        if(!$this->_lead['custom_datetime'] || $this->_lead['custom_datetime'] < 3600 * 24) {
            $this->addError('Не установлено время приемаа', true);
            return false;
        }

        $params = [
            //'first_name' => $this->_lead['client_name'],
            'birth_date' => date('d.m.Y', $this->_lead['client_birthday'] + 6 * 3600),
            'mobile' => self::setBasePhoneFormat($this->_lead['client_phone']),
            'gender' => $this->_lead['client_gender'] ? ($this->_lead['client_gender'] == 'М' ? 1 : 2) : null,
            'email' => $this->_lead['client_email'],
            'doctor_id' => $this->getMisDoctorId(),
            'time_start' => date('d.m.Y H:i', $this->_lead['custom_datetime']),
            'time_end' => date('d.m.Y H:i', $this->_lead['custom_datetime'] + $this->getDurationTime() * 60),
            'clinic_id' => $this->getMisClinicId(),
            'is_handled' => true,
        ];
        if(isset($this->_contact['name']) and $this->_contact['name']) {
            $params['first_name'] = $this->_contact['name'];
        }
        if(isset($this->_contact['surname']) and $this->_contact['surname']) {
            $params['last_name'] = $this->_contact['surname'];
        }
        if(isset($this->_contact['fathername']) and $this->_contact['fathername']) {
            $params['third_name'] = $this->_contact['fathername'];
        }
        if(!isset($params['first_name']) and !isset($params['last_name']) and !isset($params['third_name'])) {
            $params['first_name'] = $this->_lead['client_name'];
        }

        if($patientId = $this->getPatientIdByEmailPhone()) {
            $params['patient_id'] = $patientId;
        }

        if($this->_canceled_appointment_id) {
            $params['moved_from'] = $this->_canceled_appointment_id;
        }

        if(isset($this->_lead['consultation_type'])) {
            $params['comment'] = $this->_lead['consultation_type'];
        }

        return $params;
    }

    /**
     * @return bool|string
     */
    public function getPatientIdByEmailPhone()
    {
        if(!$this->_contact_full) {
            $this->addError('не установлен _contact_full');
            return false;
        }

        // 1-й запрос ФИО + телефон
        if(
            isset($this->_contact_full['phone']) and
            isset($this->_contact_full['fio'])
        ) {
            $params = [
                'mobile' => $this->_contact_full['phone'],
                'first_name' => $this->_contact_full['fio']
            ];
            if($patient = $this->getFirstPatient($params)) {
                if(trim($patient['patient_id'])) {
                    return trim($patient['patient_id']);
                }
            }
        }
        if(
            isset($this->_contact_full['phone']) and
            isset($this->_contact_full['name']) and
            isset($this->_contact_full['surname']) and
            isset($this->_contact_full['fathername'])
        ) {
            $firstName = $this->_contact_full['surname'] . ' ' . $this->_contact_full['name'] . ' ' . $this->_contact_full['fathername'];

            $params = [
                'mobile' => $this->_contact_full['phone'],
                'first_name' => $firstName,
            ];
            if($patient = $this->getFirstPatient($params)) {
                if(trim($patient['patient_id'])) {
                    return trim($patient['patient_id']);
                }
            }
        }
        if(
            isset($this->_contact_full['phone']) and
            isset($this->_contact_full['email'])
        ) {
            $params = [
                'mobile' => $this->_contact_full['phone'],
                'email' => $this->_contact_full['email'],
            ];
            if($patient = $this->getFirstPatient($params)) {
                if(trim($patient['patient_id'])) {
                    return trim($patient['patient_id']);
                }
            }
        }

        return false;
    }

    /**
     * @param array $params
     * @return bool
     */
    public function getFirstPatient($params = [])
    {
        $patient = $this->api->getPatient($params);

        if(
            $patient and
            isset($patient['data']) and
            $patient['data'] and
            is_array($patient['data'])
        ) {
            if(isset($patient['data'][0])) {
                return $patient['data'][0];
            }
            elseif(isset($patient['data']['patient_id'])) {
                return $patient['data'];
            }

        }
        return false;
    }

    /**
     * @return bool
     */
    public function cancelAppointment()
    {
        $this->addMessage('отменяем сделку '. $this->_lead['lead_id']);
        if(!isset($this->_app_created[$this->_lead['lead_id']])) {
            $this->addError('Сделка для отмены не найдена по lead_id '.$this->_lead['lead_id'], true);
            return $this->getResult();
        }

        $appointmentIdForCancel = $this->_app_created[$this->_lead['lead_id']];
        $this->_canceled_appointment_id = $appointmentIdForCancel;

        $this->addMessage('id визита для отмены - '.$appointmentIdForCancel);

        $canceledAppointment = $this->api->cancelAppointment(['appointment_id' => $appointmentIdForCancel]);

        if(!$canceledAppointment or !isset($canceledAppointment['data']) or !$canceledAppointment['data'] or is_array($canceledAppointment['data'])) {
            $this->setLog('невозможно отменить визит', $canceledAppointment, 'info');
            $this->addError('Невозможно отменить визит', true);
            return $this->getResult();
        }

        $this->unsetAppCreatedRelation($appointmentIdForCancel, $this->_lead['lead_id']);
        $this->addMessage('Отменен визит '.$appointmentIdForCancel);
    }

    /**
     * отправляет appointment_id обратно в Amo
     * @param integer $appointmentId
     */
    public function setAppointmentToAmo($appointment, $from)
    {
        if(!isset($appointment['id'])) return false;

        $params = [
            'id' => $this->_lead['lead_id'],
            'custom_fields_values' => [
                [
                    'field_id' => $this->params['mis_field_id'],
                    'field_type' => CustomFieldHelper::FIELD_TYPE_CODE_TEXT,
                    'values' => [
                        [
                            'value' => (string) $appointment['id'],
                        ]
                    ]
                ],
                [
                    'field_id' => $this->params['mis_status_name'],
                    'field_type' => CustomFieldHelper::FIELD_TYPE_CODE_SELECT,
                    'values' => [
                        [
                            'value' => $this->_mis_statuses[1],
                        ]
                    ]
                ],
            ],
        ];
        if(isset($appointment['source'])) {
            $params['custom_fields_values'][] = [
                'field_id' => $this->params['source_field_id'],
                'field_type' => CustomFieldHelper::FIELD_TYPE_CODE_TEXT,
                'values' => [
                    [
                        'value' => $appointment['source'],
                    ]
                ]
            ];
        }
        /*if($from == 'create') {
            $params['custom_fields_values'][] = [
                'field_id' => $this->params['mis_status_name'],
                'field_type' => CustomFieldHelper::FIELD_TYPE_CODE_SELECT,
                'values' => [
                    [
                        'value' => $this->_mis_statuses[1],
                    ]
                ]
            ];
        }*/
        //$this->addMessage('параметры отправки appointment в АМО');
        //$this->addMessage($params);
        $result = $this->amo->updateLead($params);
        $this->setAppointmentRelationValues([$this->_lead['lead_id'] => $appointment['id']]);
        $this->addMessage('Результат отправки appointmentId='.$appointment['id'].' в АМО - ');
        $this->addMessage($result->getId());

        return $this->getResult();
    }

    /**
     * устанавлявает визит по MIS ID
     *
     * @return bool
     */
    public function setAppointmentByMisId()
    {
        if($this->_appointment or !isset($this->_lead['mis_id']) or !$this->_lead['mis_id']) {
            $this->addError('Визит по mis_id ' . $this->_lead['mis_id'] . ' не найден', true);
            return false;
        };

        if($request = $this->api->getAppointments(['appointment_id' => $this->_lead['mis_id']])) {
            if(isset($request['data']) and isset($request['data'][0])) {
                $this->_appointment = $request['data'][0];
            }
            else {
                $this->addError('Визит по mis_id ' . $this->_lead['mis_id'] . ' некорректен', true);
                return false;
            }
        }
        if($this->_appointment) {
            $this->addMessage('Установлен _appointment - ');
            $this->addMessage($this->_appointment);
        }
    }

    /**
     * получает значение параметра в МИС
     * по параметру из АМО
     * в конфигах
     *
     * @param null $paramId
     * @param string $attribute
     * @return bool|mixed
     */
    public function getMisParamId($paramId = null, $attribute = '')
    {
        if($this->params and isset($this->params[$attribute])) {
            foreach($this->params[$attribute] as $item) {
                if($item['amo_id'] and $item['amo_id'] == $paramId) {
                    return $item['mis_id'];
                }
            }
        }
        return false;
    }

    /**
     * получает id врача в МИС
     * по его ID из АМО
     *
     * @param null $amoDoctorId
     * @return bool|mixed
     */
    public function getMisDoctorId()
    {
        // если к доктору привязана клиника, и ее amo_id = clinic_id в лиде, то прокидываем mis_id этого доктора
        if($this->params['doctors']) {
            foreach($this->params['doctors'] as $doctor) {
                if(!$doctor['clinic_amo_id']) continue;
                if($doctor['clinic_amo_id'] == $this->_lead['clinic_id'] && $doctor['amo_id'] == $this->_lead['doctor_id']) {
                    return $doctor['mis_id'];
                }
            }
        }

        return $this->getMisParamId($this->_lead['doctor_id'], 'doctors');
    }

    /**
     * получает id клиники из МИС*
     * из конфига
     *
     * @return mixed|null
     */
    public function getMisClinicId()
    {
        if($this->_lead && isset($this->_lead['clinic_id']) && $this->params['clinics']) {
            foreach($this->params['clinics'] as $clinic) {
                if($this->_lead['clinic_id'] == $clinic['amo_id']) {
                    return $clinic['mis_id'];
                }
            }
        }
        return null;

        // _lead[clinic_id]
        if($this->params and isset($this->params['clinic_mis_id'])) {
            return $this->params['clinic_mis_id'];
        }
        return null;
    }

    public function isStatusForChange()
    {
        return in_array($this->_lead['pipeline_status_id'], $this->_clinic['update_status']);
        return false;
        if($this->_lead && isset($this->_lead['clinic_id'])) {
            foreach($this->params['clinics'] as $clinic) {
                if(in_array($amoStatusId, $clinic['update_status'])) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @return bool
     */
    public function isStatusForCancel()
    {
        if(empty($this->_lead['pipeline_status_id']) || empty($this->params['statuses_for_cancel'])) return false;

        return in_array($this->_lead['pipeline_status_id'], $this->params['statuses_for_cancel']);
    }

    /**
     * устанавливает контакт по MIS ID
     */
    public function setContact()
    {
        if(!$this->_lead_values) {
            $this->addError('setContact Lead ID не найден, контакт не установлен');
            return false;
        }

        $leadId = $this->_lead_values['id'];
        $this->addMessage('$leadId - '.$leadId);
        // получаем сделку
        $lead = $this->amo->getLead($leadId);

        //$this->addMessage($lead);

        // получаем ID ее главного контакта
        $leadContactId = null;
        if($leadContacts = $lead->getContacts()) {
            foreach($leadContacts as $leadContact) {
                if($leadContact->getIsMain()) {
                    $leadContactId = $leadContact->id;
                }
            }
        }
        else {
            $this->addError('setContact Сделка из АМО не получена', true);
        }

        if($leadContactId) {
            $this->addMessage('leadContactId:');
            $this->addMessage($leadContactId);

            // получаем контакт по его ID
            if($contact = $this->amo->getContact($leadContactId)) {

                $this->_contact['id'] = $contact->id;
                $this->addMessage('получен contact');
                if($customFieldValues = $contact->getCustomFieldsValues()) {
                    foreach($customFieldValues as $customFieldValue) {
                        $fieldId = $customFieldValue->fieldId;
                        if($contactAttribute = $this->getContactFieldAttribute($fieldId)) {
                            if($values = $customFieldValue->values) {
                                foreach($values as $value) {
                                    $this->_contact[$contactAttribute] = $value->value;
                                }
                            }
                            else {
                                //$this->setLog('значения атрибута ' . $contactAttribute . ' контакта ' . $contact->id . ' по fieldId=' . $fieldId . ' не получены');
                            }
                        }
                        else {
                            //$this->setLog('атрибут контакта ' . $contact->id . ' по fieldId=' . $fieldId . ' не получен');
                        }
                    }
                }
                else {
                    $this->addError('setContact customFieldValues контакта ' . $contact->id . ' не получены', true);
                }


                // приводим полученные контакты _contacts в нужный формат
                $this->prepareContactAttributes($contact);
                $this->addMessage('_contact');
                $this->addMessage($this->_contact);
            }
            else {
                $this->addError('setContact Контакт из АМО не получен по ID ' . $leadContactId, true);
            }
        }
        else {
            $this->addError('setContact ID главного контакта из АМО не получен', true);
        }

        //return $this->_contact['id'];
    }
    /*public function getLeadCustomFieldValue($data, $fieldName = null, $attributeName = 'value')
    {
        if(!$data or !$fieldName or !array_key_exists($fieldName, $this->fieldIds['leads'])) return false;

        foreach($data['custom_fields'] as $customField) {

            if(in_array($customField['id'], $this->fieldIds['leads'][$fieldName]) and isset($customField['values'])) {
                if(isset($customField['values'][0]) and isset($customField['values'][0][$attributeName])) return $customField['values'][0][$attributeName];
                if(isset($customField['values'][$attributeName])) return $customField['values'][$attributeName];
                if(isset($customField['values'][0]) and !is_array($customField['values'][0])) return $customField['values'][0];
            }
        }

        return false;
    }*/


    /**
     * приводит _contact в нужный формат
     *
     * @param $contact
     */
    public function prepareContactAttributes($contact)
    {
        $this->_contact_full = $this->_contact;

        if(isset($this->_contact['fio'])) {
            $this->_contact['contact_name'] = $this->_contact['fio'];
            unset($this->_contact['fio']);
        }
        else {
            $this->_contact['contact_name'] = '';
            if(isset($this->_contact['surname'])) {
                $this->_contact['contact_name'] .= $this->_contact['surname'] . ' ';
                unset($this->_contact['surname']);
            }
            if(isset($this->_contact['name'])) {
                $this->_contact['contact_name'] .= $this->_contact['name'] . ' ';
                unset($this->_contact['name']);
            }
            if(isset($this->_contact['fathername'])) {
                $this->_contact['contact_name'] .= $this->_contact['fathername'];
                unset($this->_contact['fathername']);
            }
        }

        if(isset($this->_contact['email'])) {
            $this->_contact['contact_email'] = $this->_contact['email'];
            unset($this->_contact['email']);
        }
        if(isset($this->_contact['phone'])) {
            $this->_contact['contact_phone'] = $this->_contact['phone'];
            unset($this->_contact['phone']);
        }
        if(isset($this->_contact['birthday'])) {
            $this->_contact['contact_birthday'] = $this->_contact['birthday'];
            unset($this->_contact['birthday']);
        }
        if(isset($this->_contact['gender'])) {
            $this->_contact['contact_gender'] = $this->_contact['gender'];
            unset($this->_contact['gender']);
        }

        // если в контакте нет значений имени, возвращаем название контакта
        if(!$this->_contact['contact_name']) $this->_contact['contact_name'] = $contact->getName();

    }

    /**
     * получает название поля по его ID АМО
     *
     * @param $contactFieldId
     * @return bool|int|string
     */
    public function getContactFieldAttribute($contactFieldId)
    {
        if(!$contactFieldId) return false;

        foreach($this->fieldIds['contacts'] as $fieldName => $fieldIds) {
            if(in_array($contactFieldId, $fieldIds)) return $fieldName;
        }

        return false;
    }

    /**
     * @param $leadValues
     * @return bool|mixed
     */
    public function getClientGender()
    {
        $leadValues = $this->_lead_values;

        if($gender = $this->getContactAttribute('contact_gender')) return $gender;
        return $this->getLeadCustomFieldValue($leadValues, 'gender');
    }

    /**
     * @param $leadValues
     * @return bool|mixed
     */
    public function getDoctorName()
    {
        $leadValues = $this->_lead_values;

        return $this->getLeadCustomFieldValue($leadValues, 'doctor');
    }

    /**
     * @param $leadValues
     * @return bool|mixed
     */
    public function getDoctorId()
    {
        $leadValues = $this->_lead_values;

        return $this->getLeadCustomFieldValue($leadValues, 'doctor', 'enum');
    }

    /**
     * @return bool|mixed
     */
    public function getMisId()
    {
        $leadValues = $this->_lead_values;

        return $this->getLeadCustomFieldValue($leadValues, 'mis_id');
    }

    /**
     * @return bool|mixed
     */
    public function getCustomDatetime()
    {
        $leadValues = $this->_lead_values;

        return $this->getLeadCustomFieldValue($leadValues, 'custom_datetime');
    }

    /**
     * @return bool|mixed
     */
    public function getCustomDuration()
    {
        $leadValues = $this->_lead_values;

        return $this->getLeadCustomFieldValue($leadValues, 'custom_duration');
    }

    /**
     * @param $leadValues
     * @return bool|mixed
     */
    public function getPipelineStatus()
    {
        $leadValues = $this->_lead_values;

        if(isset($leadValues['status_id'])) {
            if(isset($this->params['edit_statuses'][$leadValues['status_id']])) {
                return $this->params['edit_statuses'][$leadValues['status_id']];
            }
        }

        return false;
    }

    /**
     * @param $leadValues
     * @return bool|mixed
     */
    public function getClinicId()
    {
        $leadValues = $this->_lead_values;

        return $this->getLeadCustomFieldValue($leadValues, 'clinic', 'enum');
    }

    /**
     * @param $leadValues
     * @return bool|mixed
     */
    public function getClinicName()
    {
        $leadValues = $this->_lead_values;

        return $this->getLeadCustomFieldValue($leadValues, 'clinic');
    }


    /**
     * @param $leadValues
     * @return bool|mixed|string
     */
    public function getDateTime()
    {
        $leadValues = $this->_lead_values;

        if($datetime = $this->getLeadCustomFieldValue($leadValues, 'custom_datetime')) {
            return $datetime;
        }
        $date = $this->getLeadCustomFieldValue($leadValues, 'consultation_date');
        $time = $this->getLeadCustomFieldValue($leadValues, 'consultation_time');
        return $date . ' ' . $time;
    }

    /**
     * @param $leadValues
     * @return bool|mixed
     */
    public function getConsultationType()
    {
        $leadValues = $this->_lead_values;

        return $this->getLeadCustomFieldValue($leadValues, 'consultation_type');
    }

    /**
     * @param $leadValues
     * @return bool|mixed
     */
    public function getConsultationTypeId()
    {
        $leadValues = $this->_lead_values;

        return $this->getLeadCustomFieldValue($leadValues, 'consultation_type', 'enum');
    }

    /**
     * @param $attributeName
     * @return mixed
     */
    public function getContactAttribute($attributeName)
    {
        // здесь получаем атрибут по его имени
        if($this->_contact) {
            if(isset($this->_contact[$attributeName])) return $this->_contact[$attributeName];
        }
    }

    /**
     * @param $data
     * @param null $fieldName
     * @param string $attributeName
     * @return bool|mixed
     */
    public function getLeadCustomFieldValue($data, $fieldName = null, $attributeName = 'value')
    {
        if(!$data or !$fieldName or !array_key_exists($fieldName, $this->fieldIds['leads'])) return false;

        foreach($data['custom_fields'] as $customField) {

            if(in_array($customField['id'], $this->fieldIds['leads'][$fieldName]) and isset($customField['values'])) {
                if(isset($customField['values'][0]) and isset($customField['values'][0][$attributeName])) return $customField['values'][0][$attributeName];
                if(isset($customField['values'][$attributeName])) return $customField['values'][$attributeName];
                if(isset($customField['values'][0]) and !is_array($customField['values'][0])) return $customField['values'][0];
            }
        }

        return false;
    }





    public function actionCreateMisAppointment()
    {
        $this->addMessage('----------------------------------------------------------');
        $this->addMessage('create-appointment-hook');

        if(isset($this->requestBody['data']) and is_array($this->requestBody['data']) and !empty($this->requestBody['data'])) {
            $this->_appointment = $this->requestBody['data'];
            $this->addMessage('CMS - Установлено свойство _appointment');
            $this->addMessage($this->_appointment);
        }

        sleep(2);

        $this->setAppActionRelation();

        // проверяем установлен ли визит и не прокидывался он ранее в АМО
        $this->checkCreatedAppointment();

        $this->setMisClinic();

        $this->checkDoctorAvaliable();

        if($this->hasErrors()) return $this->getResult();

        $this->setClinic(null, $this->_mis_clinic['mis_id']);

        // собираем поля для АМО и прокидываем лид
        $this->createAmoLead();

        return $this->getResult();

    }

    /**
     * @return bool
     */
    public function setMisClinic()
    {
        $this->addMessage('Устанавливается клиника из МИС');
        if(!$this->_appointment) {
            $this->addError('Не установлен _appointment для получения клиники', true);
            return false;
        }
        $this->addMessage('_appointments установлен');
        if($clinic = Clinic::findModels()->andWhere(['mis_id' => $this->_appointment['clinic_id']])->one()) {
            $this->addMessage('Найдена клиника из запроса ID='.$clinic->id);
            $this->_mis_clinic = $clinic;
            $this->addMessage('Установлено свойство _mis_clinic');
            $this->addMessage($clinic->attributes);
            $this->addMessage('Ошибки:');
            $this->addMessage($this->result);
            return $this->_mis_clinic;
        }

        $this->addError('Не найдена клиника из запроса', true);
        return false;
    }

    public function checkDoctorAvaliable()
    {
        if($this->hasErrors()) {
            $this->addError('При проверке доктора '.$this->_appointment['doctor_id'].' найдены ошибки', true);
            return false;
        }
        $misClinic = $this->_mis_clinic;

        $doctorId = $this->_appointment['doctor_id'];

        $exists = Doctor::findModels()->andWhere(['clinic_id' => $misClinic->id, 'mis_id' => $doctorId])->exists();

        if(!$exists) {
            $this->addError('Доктор '.$this->_appointment['doctor_id'].' в системе не найден', true);
            return false;
        }

        $this->addMessage('Доктор '.$this->_appointment['doctor_id'].' в системе найден');
    }

    /**
     * @param null $amoClinicId
     * @param null $misClinicId
     * @return bool
     */
    public function setClinic($amoClinicId = null, $misClinicId = null)
    {
//        $this->addMessage('setClinic');
//        $this->addMessage('amoClinicId');
//        $this->addMessage($amoClinicId);
//        $this->addMessage('misClinicId');
//        $this->addMessage($misClinicId);

        //if($misClinicId) return false;
        // посмотреть и залогировать _clinic
        // и что то не так в этой логике

        if($amoClinicId && $misClinicId) return false;

        if($this->params['clinics']) {
            foreach($this->params['clinics'] as $clinic) {
                if($amoClinicId && $clinic['amo_id'] == $amoClinicId) {
                    $this->_clinic = $clinic;
                }
                if($misClinicId && $clinic['mis_id'] == $misClinicId) {
                    $this->_clinic = $clinic;
                }
            }
        }

        if(!$this->_clinic) {
            $this->addError('Клиника _clinic не установлена');
        }
    }

    public function getPipelineIdForAmo()
    {
        if(!$this->_clinic) {
            $this->addError('Не установлена клиника _clinic для получения pipeline_id');
            return false;
        }

        if($clinic = Clinic::findOne($this->_clinic['id'])) {
            $this->addMessage('clinic model');
            $this->addMessage($clinic->attributes);
            if($updateStatuses = $clinic->updateSteps) {
                foreach ($updateStatuses as $updateStatus) {
                    $this->addMessage('clinic update steps');
                    $this->addMessage($updateStatus);
                    if($updateStatus->is_active && $updateStatus->pipeline) return $updateStatus->pipeline->amo_id;
                }
            }
        }
        return false;
    }

    public function createAmoLead()
    {
        $this->addMessage('create amo lead');
        $pipelineIdForAmo = $this->getPipelineIdForAmo();
        $this->addMessage('_clinic');
        $this->addMessage($this->_clinic);
        $this->addMessage('pipeline_id for amo');
        $this->addMessage($pipelineIdForAmo);
        $params = [
            'name' => 'Новая сделка из МИС',
            'status_id' => $this->getConfigClinicParam('status_id'),
            'pipeline_id' => $pipelineIdForAmo,
            'custom_fields_values' => [

                // clinic
                [
                    'field_id' => 457485,
                    'field_type' => CustomFieldHelper::FIELD_TYPE_CODE_TEXT,
                    'values' => [
                        [
                            'value' => $this->getConfigClinicParam('amo_name'),
                            'enum_id' => $this->getConfigClinicParam('status_id'),
                        ]
                    ]
                ],

                // mis status
                [
                    'field_id' => $this->params['mis_status_name'],
                    'field_type' => CustomFieldHelper::FIELD_TYPE_CODE_SELECT,
                    'values' => [
                        [
                            'value' => $this->_mis_statuses[1],
                        ]
                    ]
                ],

                // mis_id
                [
                    'field_id' => $this->params['mis_field_id'],
                    'field_type' => CustomFieldHelper::FIELD_TYPE_CODE_TEXT,
                    'values' => [
                        [
                            'value' => (string) $this->_appointment['id'],
                        ]
                    ]
                ],

                // client
            ],
        ];

        // doctor
        if($this->_appointment['doctor_id'] and $this->_appointment['doctor'] and ($doctorAmoId = $this->getAmoDoctorId()) and ($doctorAmoName = $this->getAmoDoctorName())) {
            $params['custom_fields_values'][] = [
                'field_id' => 512391,
                'field_type' => CustomFieldHelper::FIELD_TYPE_CODE_TEXT,
                'values' => [
                    [
                        'value' => $doctorAmoName,
                        'enum' => $doctorAmoId,
                    ]
                ]
            ];
        }

        // source
        if(isset($this->_appointment['source']) && $this->_appointment['source']) {
            $params['custom_fields_values'][] = [
                'field_id' => $this->params['source_field_id'],
                'field_type' => CustomFieldHelper::FIELD_TYPE_CODE_TEXT,
                'values' => [
                    [
                        'value' => $this->_appointment['source'],
                    ]
                ]
            ];
        }

        // услугу прокидываем в комментарий к сделке
//        if(isset($this->_appointment['services']) and isset($this->_appointment['services'][0])) {
//            $params['custom_fields_values'][] = [
//                //'field_id' => 172013,
//                'field_id' => 686369,
//                'field_type' => CustomFieldHelper::FIELD_TYPE_CODE_TEXT,
//                'values' => [
//                    [
//                        'value' => $this->_appointment['services'][0]['title'],
//                    ]
//                ]
//            ];
//        }
        if(!empty($this->_appointment['services'])) {
            $appointmentServices = $this->getAppointmentServices();
            $this->addMessage('Услуги визита '.$this->_appointment['id']);
            $this->addMessage($appointmentServices);
            $params['custom_fields_values'][] = [
                //'field_id' => 172013,
                'field_id' => $this->params['services_field_id'],
                'field_type' => CustomFieldHelper::FIELD_TYPE_CODE_TEXT,
                'values' => [
                    [
                        'value' => implode('; ', $appointmentServices),
                    ]
                ]
            ];
        }

        // consultation_datetime
        // custom_datetime
        if(isset($this->_appointment['time_start'])) {
            $params['custom_fields_values'][] = [
                //'field_id' => 681425,
                'field_id' => 704837,
                'field_type' => CustomFieldHelper::FIELD_TYPE_CODE_TEXT,
                'values' => [
                    [
                        'value' => strtotime($this->_appointment['time_start']),
                    ]
                ]
            ];
        }

        // получаем контакт ID по номеру телефона
        if($contactId = $this->getAmoContactId()) {
            $params['_embedded'] = [
                'contacts' => [
                    [
                        'id' => $contactId,
                    ]
                ],
            ];
        }

        $this->addMessage('Поля для создания лида в амо');
        $this->addMessage($params);

        $this->addMessage('this amo '.!!$this->amo);

        $result = $this->amo->addLead($params);

        $this->addMessage('Результат создания лида в амо');
        $this->addMessage($result);

        if($result and ($leadId = $result->getId())) {
            $this->addMessage('leadId - '.$leadId);
            $this->addMessage('appointmentId - '.$this->_appointment['id']);
            $this->setAppointmentRelationValues([$leadId => $this->_appointment['id']]);
        }

        return $this->getResult();
    }

    /**
     * @return array
     */
    public function getAppointmentServices()
    {
        return array_map(function($item) {
            return $item['title'] . ' кол-во:' . $item['count'] . ' сто-ть:' . $item['value'];
        }, $this->_appointment['services']);
    }

    /**
     * @return bool
     */
    public function getConfigClinicParam($paramName = null)
    {
        $this->addMessage('getConfigClinicParam - '.$paramName);

        if(!$this->_clinic) {
            $this->addError('Не установлена клиника _clinic для получения параметра amo '.$paramName, true);
            return false;
        }

        return $this->_clinic[$paramName] ?? null;
    }

    /**
     * возвращает contactId существующего контакта по номеру телефона
     * либо рекурсивно создает новый контакт и возвращает его ID
     *
     * @return bool
     */
    private function getAmoContactId()
    {
        if($this->_create_amo_contacts > 3) return false;

        $this->addMessage('Поиск контакта в AMO по номеру телефона');
        if(!isset($this->_appointment['patient_phone'])) return false;
        $this->addMessage($this->_appointment['patient_phone']);

        if($contactId = $this->amo->getContactIdByPhone($this->_appointment['patient_phone'])) {
            $this->addMessage('найден contactId - '.$contactId);
            return $contactId;
        }
        $this->createAmoContact();
        return $this->getAmoContactId();
    }

    public function createAmoContact()
    {
        $this->addMessage('Создание контакта в АМО');
        $params = [];

        if(!empty($this->_appointment['patient_name'])) {
            $params['name'] = $this->_appointment['patient_name'];
            $params['custom_fields_values'][] = [
                'field_id' => 172333,
                'field_type' => CustomFieldHelper::FIELD_TYPE_CODE_TEXT,
                'values' => [
                    [
                        'value' => $this->_appointment['patient_name'],
                    ]
                ]
            ];
        }
        if(!empty($this->_appointment['patient_gender'])) {
            $params['custom_fields_values'][] = [
                'field_id' => 493129,
                'field_type' => CustomFieldHelper::FIELD_TYPE_CODE_TEXT,
                'values' => [
                    [
                        'value' => $this->_appointment['patient_gender'],
                    ]
                ]
            ];
        }
        if(!empty($this->_appointment['patient_birth_date'])) {
            $params['custom_fields_values'][] = [
                'field_id' => 327131,
                'field_type' => CustomFieldHelper::FIELD_TYPE_CODE_TEXT,
                'values' => [
                    [
                        'value' => strtotime($this->_appointment['patient_birth_date']),
                    ]
                ]
            ];
        }
        if(!empty($this->_appointment['patient_phone'])) {
            $params['custom_fields_values'][] = [
                'field_id' => 19193,
                'field_type' => CustomFieldHelper::FIELD_TYPE_CODE_TEXT,
                'values' => [
                    [
                        'value' => $this->_appointment['patient_phone'],
                    ]
                ]
            ];
        }
        if(!empty($this->_appointment['patient_email'])) {
            $params['custom_fields_values'][] = [
                'field_id' => 19195,
                'field_type' => CustomFieldHelper::FIELD_TYPE_CODE_TEXT,
                'values' => [
                    [
                        'value' => $this->_appointment['patient_email'],
                    ]
                ]
            ];
        }

        $this->addMessage('Поля для создания контакта');
        $this->addMessage($params);

        $result = $this->amo->addContact($params);

        $this->addMessage('Создается контакт');
        $this->addMessage($result);

        $this->_create_amo_contacts++;

        return false;
    }

    /**
     * @param null $misId
     * @return bool
     */
    public function getAmoDoctorId()
    {
        if(!isset($this->_appointment['doctor_id'])) return false;

        foreach($this->params['doctors'] as $doctor) {
            if($doctor['mis_id'] == $this->_appointment['doctor_id']) {
                return $doctor['amo_id'];
            }
        }

        return false;
    }

    public function getAmoDoctorName()
    {
        if(!isset($this->_appointment['doctor_id'])) return false;

        foreach($this->params['doctors'] as $doctor) {
            if($doctor['mis_id'] == $this->_appointment['doctor_id']) {
                return $doctor['name'];
            }
        }

        return false;
    }

    public function actionUpdateMisAppointment()
    {
        $this->addMessage('----------------------------------------------------------');
        $this->addMessage('update-appointment-hook');

        if(isset($this->requestBody['data']) and isset($this->requestBody['data'][0]) and !empty($this->requestBody['data'][0])) {
            $this->_appointment = $this->requestBody['data'][0];
            $this->addMessage('UMS - Установлено свойство _appointment');
        }

        $this->setAppActionRelation();

        // проверяем установлен ли визит и прокидывался ли он ранее в АМО
        $this->checkUpdateAppointment();

        if($this->hasErrors()) return $this->getResult();

        // собираем поля для АМО и прокидываем лид
        $this->updateAmoLead();

        return $this->getResult();









        //$this->addMessage($this->requestBody);

        /*
         * будут контролироваться только поле услуг и статус в МИС, в котором оказывается визит
         * сначала проверяем есть ли этот визит в АМО через _app_created
         * нужно проконтролировать обратный веб-хук, который прилетит после редактирования.
         * для этого перед запуливанием изменений в АМО нужно куда-то сохранить mis_id
         * сделать свойство _mis_app и в него складывать СОЗДАННЫЕ в МИСе визиты и не принимать
         * веб-хуки от них из АМО
         * тогда сохранять и при обратном веб-хуке из АМО чистить mis_id и возвращать getResult
         *
         * еще непонятный кейс - создаем визит в МИС, он появляется в АМО. После того как его редактируем в АМО ничего не происходит
         *
         *
         * */
        $fields = [
            'patient_name',
            'patient_birth_date',
            'patient_gender',
            'patient_phone',
            'patient_email',
        ];
        // услуги можно только добавить
        // здесь нужно искать лид в АМО, в котором amo_id будет равен appointment_id
        // далее прокидывать все поля
    }

    public function updateAmoLead()
    {
        if(!array_key_exists($this->_appointment['status_id'], $this->_mis_statuses)) {
            $this->addError('Неизвестный статус сделки - ' . $this->_appointment['status_id'], true);
            return $this->getResult();
        }

        $lead = $this->amo->getLeadByMisId(null, $this->_appointment['id'], $this->params['mis_field_id']);

        if(!$lead) {
            $this->addError('Сделка с mis_id '.$this->_appointment['id'].' не найдена', true);
        }

        if($this->hasErrors()) return $this->getResult();

        $this->addMessage('id найденного лида '.$lead->getId());
        //$this->addMessage($lead->getId());

        $params = [
            'id' => $lead->getId(),
            'custom_fields_values' => [
                [
                    'field_id' => $this->params['mis_status_name'],
                    'field_type' => CustomFieldHelper::FIELD_TYPE_CODE_SELECT,
                    'values' => [
                        [
                            'value' => $this->_mis_statuses[$this->_appointment['status_id']],
                        ]
                    ]
                ],
            ],
        ];

        $this->addMessage('Параметры обновления сделки');
        $this->addMessage($params);

        $result = $this->amo->updateLead($params);

        if($result and ($leadId = $result->getId())) {
            $this->addMessage('обновлена сделка leadId - '.$leadId);
            $this->addMessage('визит обновленной сделки appointmentId - '.$this->_appointment['id']);
        }

        return $this->getResult();
    }

    public function checkCreatedAppointment()
    {
        if(!$this->_appointment) {
            $this->addError('Визит из веб-хука не найден', true);
            return $this->getResult();
        }

        if(in_array($this->_appointment['id'], $this->_app_created)) {
            $this->addError('ID визита '.$this->_appointment['id'].' уже существует в АМО', true);
            return $this->getResult();
        }
        $this->addMessage('проверка на существование mis_id в АМО отработал errors - ' . $this->hasErrors());
    }

    public function checkUpdateAppointment()
    {
        if(!$this->_appointment) {
            $this->addError('Визит из веб-хука не найден', true);
            return $this->getResult();
        }

        if(!in_array($this->_appointment['id'], $this->_app_created)) {
            $this->addError('ID визита '.$this->_appointment['id'].' не существует в АМО', true);
            return $this->getResult();
        }
        $this->addMessage('проверка на существование mis_id в АМО отработал errors - ' . $this->hasErrors());
    }


}
