<?php

namespace app\components;

use app\models\Clinic;
use app\models\Doctor;
use app\models\Pipeline;
use yii\base\Component;

class WebhookConfig extends Component
{
    public $mis_field_id;
    public $amo_status_field_id;
    public $datetime_field_id;
    public $duration_field_id;
    public $config;

    public function init()
    {
        $this->config = $this->defaultParams();
        return parent::init();
    }

    public function setConfig()
    {
        if($clinic = Clinic::findModels()->one()) {
            $this->config['clinic_id'] = $clinic->amo_id;
            $this->config['clinic'] = $clinic->name;
            $this->config['clinic_mis_id'] = $clinic->mis_id;
            $this->config['time'] = $clinic->reception_time;

            if($steps = $clinic->updateSteps) {
                $i = 0;
                foreach($steps as $step) {
                    $this->config['edit_statuses'][$step->amo_id] = $step->name;
                    if($i == 0 || $step->amo_status) {
                        $this->config['status_id'] = $step->amo_id;
                    }
                    if($i == 0 || $step->mis_status) {
                        $this->config['update_status'] = $step->amo_id;
                    }
                    $i++;
                }
            }
        }
        if($clinics = Clinic::findModels()->all()) {
            foreach($clinics as $clinic) {
                $this->config['clinics'][$clinic->id] = [
                    'id' => $clinic->id,
                    'name' => $clinic->name,
                    'amo_name' => $clinic->amo_name ?: $clinic->name,
                    'amo_id' => $clinic->amo_id,
                    'mis_id' => $clinic->mis_id,
                    'reception_time' => $clinic->reception_time,
                ];

                if($steps = $clinic->updateSteps) {
                    $i = 0;
                    foreach($steps as $step) {
                        $this->config['clinics'][$clinic->id]['edit_statuses'][$step->amo_id] = $step->name;
                        if($i == 0 || $step->amo_status) {
                            $this->config['clinics'][$clinic->id]['status_id'] = $step->amo_id;
                        }
                        if($i == 0 || $step->mis_status) {
                            $this->config['clinics'][$clinic->id]['update_status'][] = $step->amo_id;
                        }
                        $i++;
                    }
                }
            }
        }



        if($pipeline = Pipeline::findModels()->one()) {
            $this->config['pipeline_id'] = $pipeline->amo_id;
        }
        if($doctors = Doctor::findModels()->all()) {
            foreach($doctors as $doctor) {
                $this->config['doctors'][] = [
                    'name' => $doctor->name,
                    'amo_id' => $doctor->amo_id,
                    'mis_id' => $doctor->mis_id,
                ];
            }
        }
    }

    public function getConfig()
    {
        $this->setConfig();
        return $this->config;
    }

    public function defaultParams()
    {
        return [
            'mis_field_id' => $this->mis_field_id,
            'mis_status_name' => $this->amo_status_field_id,
            'datetime_field_id' => $this->datetime_field_id,
            'duration_field_id' => $this->duration_field_id,
            'pipeline_id' => null,
            'status_id' => null,
            'clinic_id' => null,
            'clinic' => null,
            'clinic_mis_id' => null,
            'time' => null,
            'update_status' => null,
            'edit_statuses' => [],
            'doctors' => [],
            'clinics' => [],
        ];
    }
}
