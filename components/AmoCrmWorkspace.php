<?php

namespace app\components;

use AmoCRM\Collections\ContactsCollection;
use AmoCRM\Collections\CustomFieldsValuesCollection;
use AmoCRM\Collections\Leads\LeadsCollection;
use AmoCRM\Collections\LinksCollection;
use AmoCRM\EntitiesServices\Leads;
use AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Filters\BaseEntityFilter;
use AmoCRM\Filters\BaseRangeFilter;
use AmoCRM\Filters\ContactsFilter;
use AmoCRM\Filters\LeadsFilter;
use AmoCRM\Helpers\CustomFieldHelper;
use AmoCRM\Helpers\EntityTypesInterface;
use AmoCRM\Models\CompanyModel;
use AmoCRM\Models\ContactModel;
use AmoCRM\Models\CustomFieldsValues\TextCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\TextCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueModels\TextCustomFieldValueModel;
use AmoCRM\Models\LeadModel;

class AmoCrmWorkspace extends AmoCrmClient
{
    /**
     * Создаем новый контакт
     * @param array $options
     * @return bool
     * @throws \AmoCRM\Exceptions\InvalidArgumentException
     */
    public function addContact($options = [])
    {
        if(!empty($options)) {
            $contact = ContactModel::addFromArray($options);
            try {
                return $this->apiClient->contacts()->addOne($contact);
            } catch (AmoCRMApiException $e) {
                return false;
            }
        }
        return true;
    }

    /**
     * Просмотр контакта по его id
     * @param $id
     * @throws AmoCRMApiException
     * @throws \AmoCRM\Exceptions\AmoCRMMissedTokenException
     * @throws \AmoCRM\Exceptions\AmoCRMoAuthApiException
     */
    public function viewContact($id)
    {
        $data = $this->apiClient->contacts()->getOne($id);

        return $data;
    }

    public function getLead($id)
    {
        $data = $this->apiClient->leads()->getOne($id, ['contacts']);

        return $data;
    }

    // больше не нужен !!!
    public function getLeadsByPiplineId($pipelineId = null)
    {
        $filter = new LeadsFilter();
        if($pipelineId) {
            $filter->setPipelineIds([$pipelineId]);
            $filter->setLimit(1000);
        }

        $data = $this->apiClient->leads()->get($filter);

        return $data;
    }

    /**
     * @param $appointmentId
     * @return LeadsCollection|null
     * @throws AmoCRMApiException
     * @throws \AmoCRM\Exceptions\AmoCRMMissedTokenException
     * @throws \AmoCRM\Exceptions\AmoCRMoAuthApiException
     */
    public function getLeadsByAppointmentId($appointmentId)
    {
        $filter = new LeadsFilter();
        if($appointmentId) {
            $filter->setQuery($appointmentId);
            $filter->setLimit(1000);
        }

        $data = $this->apiClient->leads()->get($filter);

        return $data;
    }

    /**
     * получает сделку
     * @param null $pipelineId
     * @param null $appointment_id
     * @param $mis_field_id        - ID поля МИС ID
     * @return bool|mixed
     */
    public function getLeadByMisId($pipelineId = null, $appointment_id = null, $mis_field_id)
    {
        $query = $this->getLeadsByAppointmentId($appointment_id);

        if($query) {
            if(isset($query[0])) {
                return $query[0];
            }
        }

        return false;

        if($pipelineLeads = $this->getLeadsByPiplineId($pipelineId)) {
            foreach($pipelineLeads as $lead) {
                if($customFieldValues = $lead->getCustomFieldsValues()) {
                    foreach($customFieldValues as $customFieldValue) {
                        $fieldId = $customFieldValue->getFieldId();
                        if($fieldId == $mis_field_id) {
                            if($values = $customFieldValue->getValues()) {
                                foreach($values as $value) {
                                    if($value->getValue() == $appointment_id) return $lead;
                                }
                            }
                        }
                    }
                }
            }
        }
        return false;
    }

    public function getContact($id)
    {
        $data = $this->apiClient->contacts()->getOne($id);

        return $data;
    }

    public function getContactIdByPhone($phone = null)
    {
        if(!$phone) return false;

        $symbols = [' ', '+', '-', '(', ')'];

        $phoneNumber = str_replace($symbols, '', $phone);
        $filter = new ContactsFilter();
        $filter->setQuery($phoneNumber);

        try
        {
            $data = $this->apiClient->contacts()->get($filter);
            file_put_contents('test_trace-log.txt', date('d.m.Y H:i:s').' АМО контакт по телефону найден - '.print_r('', true)."\n", FILE_APPEND);
            return $this->getFirstContactId($data);
        }
        catch (AmoCRMApiException $ex)
        {
            file_put_contents('test_trace-log.txt', date('d.m.Y H:i:s').' АМО контакт по телефону не найден - '.print_r('', true)."\n", FILE_APPEND);
            return false;
        }
    }

    public function getFirstContactId($contacts = [])
    {
        if(!$contacts or !isset($contacts[0])) return false;
        $contact = $contacts[0];
        $contactId = $contact->getId();
        return $contactId;
    }

    public function getContacts($params = [])
    {
        $filter = new ContactsFilter();
        if(isset($params['phone'])) {
            //$filter->setIds([4176497]);
            /*$filter->setCustomFieldsValues([


                    'field_id' => 19193,
                    'field_type' => CustomFieldHelper::FIELD_TYPE_CODE_TEXT,
                    'values' => [
                        [
                            'value' => $params['phone'],
                        ],
                    ],



            ]);*/
            $filter->setCustomFieldsValues([
                //19193 => $params['phone'],
                19193 => +79887608134,
            ]);


        }

        /*echo "<pre>";
        print_r($filter->getCustomFieldsValues());
        echo "</pre>";
        exit;*/



        $data = $this->apiClient->contacts()->get($filter);

        return $data;
    }

    public function getLeadsList()
    {
        $data = $this->apiClient->leads()->get();
        return $data;
    }

    public function getCustomFields()
    {
        $data = $this->apiClient->customFields(EntityTypesInterface::LEAD)->get();
        return $data;
    }

    public function updateLead($params = [])
    {

        if(!empty($params)) {
            $lead = LeadModel::fromArray($params);
            try {
                $leadModel = $this->apiClient->leads()->updateOne($lead);
                file_put_contents('test_trace-log.txt', date('d.m.Y H:i:s').' - '.print_r('АМО Сделка обновлена', true)."\n", FILE_APPEND);
                return $leadModel;
            } catch (AmoCRMApiException $e) {
                file_put_contents('test_trace-log.txt', date('d.m.Y H:i:s').' АМО сделка не обновлена - '.print_r($e, true)."\n", FILE_APPEND);
                return false;
            }
        }
        return true;
    }
    /**
     * Редактируем контакт
     * @param array $options
     * @return bool
     * @throws \AmoCRM\Exceptions\InvalidArgumentException
     */
    public function updateContact($options = [])
    {
        if(!empty($options)) {
            $contact = ContactModel::fromArray($options);
            try {
                $contactModel = $this->apiClient->contacts()->updateOne($contact);

            } catch (AmoCRMApiException $e) {
                //printError($e);
                die;
            }
        }
        return true;
    }

    public function addLead($params = [])
    {
        if(!$params) return false;

        $lead = LeadModel::addFromArray($params);
        try {
            $leadModel = $this->apiClient->leads()->addOne($lead);
            file_put_contents('test_trace-log.txt', date('d.m.Y H:i:s').' - '.print_r('АМО Сделка добавлена', true)."\n", FILE_APPEND);
            return $leadModel;
        } catch (AmoCRMApiException $e) {
            file_put_contents('test_trace-log.txt', date('d.m.Y H:i:s').' АМО Сделка не добавлена - '.print_r($e, true)."\n", FILE_APPEND);
            return false;
        }

    }





    public function addLeadOLD($options = [], $contactModel = false)
    {
        if(!empty($options)) {
            $contact = LeadModel::addFromArray($options);
            /*if($contactModel) {
                $contact->setContacts(
                    (new ContactsCollection())
                        ->add(
                            (new ContactModel())
                                ->setId($contactModel->getId())
                        )
                );
            }*/
            try {
                $leadModel = $this->apiClient->leads()->addOne($contact);

                /*if($contactModel) {
                    $lead = $apiClient->leads()->getOne($leadModel->getId());
                    $links = new LinksCollection();
                    $links->add($lead);
                    $apiClient->contacts()->link($contactModel, $links);
                }*/
            } catch (AmoCRMApiException $e) {
                echo "<pre>";
                print_r($e);
                echo "</pre>";
                exit;
                //printError($e);
                die;
            }
        }
        return true;
    }
    public function addRecord()
    {
        if(($options_contact = $this->getOptionsContact()) && !empty($options_contact)) {
            $options_lead = $this->getOptionsLead();
            if($contact_id = self::getContactId($options_contact)) {
                $contact_model = $this->compareClient($contact_id);
                if($contact_model !== false) {
                    $this->addLead(self::getLeadOptionsContact($contact_model, $options_lead, $this));
                } else {
                    $contact_model = $this->addContact($options_contact);
                    $this->addLead(self::getLeadOptionsContact($contact_model, $options_lead, $this));
                }
            }
        }
    }
    public function getOptionsLead()
    {
        return [
            'name' => 'Сделка №'.$this->order->id,
            'price' => $this->order->offer->price,
            // продолжаю отсюда - выдает ошибку в строке обратной связи
            'feedback' => ($this->order && $this->order->offer) ? (bool) preg_match("/с обратной связью/", $this->order->offer->name) : false,
            'order_id' => $this->order->id,
        ];
    }
    public function getOptionsContact()
    {
        return [
            'first_name' => $this->options->name,
            'custom_fields_values' =>
                [
                    [
                        'field_id' => 767723,
                        'field_code' => 'PHONE',
                        'field_type' => CustomFieldHelper::FIELD_TYPE_CODE_TEXT,
                        'values' =>
                            [
                                [
                                    'value' => $this->options->phone,
                                ]
                            ],
                    ],
                    // E-mail
                    [
                        'field_id' => 767725,
                        'field_code' => 'EMAIL',
                        'field_type' => CustomFieldHelper::FIELD_TYPE_CODE_TEXT,
                        'values' =>
                            [
                                [
                                    'value' => $this->options->email,
                                ]
                            ],
                    ],
                    // ID - личный кабинет. Будет использоваться как id юзера
                    [
                        'field_id' => 1058379,
                        'field_type' => CustomFieldHelper::FIELD_TYPE_CODE_TEXT,
                        'values' =>
                            [
                                [
                                    'value' => $this->order->user_id,
                                ],
                            ],
                    ],
                ],
        ];
    }
    public function getContactId($options)
    {
        if(isset($options['custom_fields_values'])) {
            foreach($options['custom_fields_values'] as $value) {
                if(isset($value['field_id']) && ($value['field_id'] == 1058379) && isset($value['values'])) {
                    if(is_array($value['values'])) {
                        foreach($value['values'] as $val) {
                            return $val['value'];
                        }
                    }
                }
            }
        }
    }
    public function isLeadsContact($contact_model)
    {
        if($contact = $this->apiClient->contacts()->getOne($contact_model->getId(), [ContactModel::LEADS])) {
            if($contact->getLeads()) return true;
        }
        return false;
    }
    public function getLeadOptionsContact($contact_model, $options_lead, $model)
    {
        if($contact_id = $contact_model->getId()) {
            $options_lead['_embedded'] = [
                'contacts' => [
                    [
                        'id' => $contact_id,
                    ]
                ],
            ];
        }
        $isLeadsContact = $model->isLeadsContact($contact_model);
        //$feedback = $model->order->offer ? preg_match("/с обратной связью/", $model->order->offer->name) : false;
        $feedback = $options_lead['feedback'];
        $options_lead['custom_fields_values'] = [
            // Номер заказа
            [
                'field_id' => 1058415,
                'field_type' => CustomFieldHelper::FIELD_TYPE_CODE_NUMERIC,
                'values' =>
                    [
                        [
                            'value' => $options_lead['order_id'],
                        ]
                    ],
            ],
            // тип сделки
            [
                'field_id' => 1058417,
                'field_type' => CustomFieldHelper::FIELD_TYPE_CODE_SELECT,
                'values' =>
                    [
                        [
                            'value' => 'Заказ',
                        ],
                    ],
            ],

            // обратная связь
            [
                'field_id' => 1058419,
                'field_type' => CustomFieldHelper::FIELD_TYPE_CODE_CHECKBOX,
                'values' =>
                    [
                        [
                            'value' => $feedback,
                        ],
                    ],
            ],
            // повторная покупка
            [
                'field_id' => 1058423,
                'field_type' => CustomFieldHelper::FIELD_TYPE_CODE_CHECKBOX,
                'values' =>
                    [
                        [
                            'value' => $isLeadsContact,
                        ],
                    ],
            ],
            // первая покупка
            [
                'field_id' => 1058425,
                'field_type' => CustomFieldHelper::FIELD_TYPE_CODE_CHECKBOX,
                'values' =>
                    [
                        [
                            'value' => $isLeadsContact ? false : true,
                        ],
                    ],
            ],
        ];
        return $options_lead;
    }
    public function compareClient($contact_id)
    {
        if(!$contact_id) return false;
        if($contacts = $this->apiClient->contacts()->get()) {
            foreach($contacts as $contact) {
                if($custom_fields = $contact->getCustomFieldsValues()) {
                    if($field = $custom_fields->getBy('fieldId', 1058379)) {
                        if($field_values = $field->getValues()) {
                            if($value = $field_values->getBy('value', (string) $contact_id)) {
                                return $contact;
                            }
                        }
                    }
                }
            }
        }
        return false;
    }

    public function getPipelines()
    {
        return $this->apiClient->pipelines()->get();
    }
}
?>
