<?php

namespace Drupal\amu_hal\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
* Configure amu_hal settings for this site.
*/
class amu_halSettingsForm extends ConfigFormBase {
/**
* {@inheritdoc}
*/
public function getFormId() {
return 'amu_hal_admin_settings';
}

/**
* {@inheritdoc}
*/
protected function getEditableConfigNames() {
return [
'amu_hal.settings',
];
}

/**
* {@inheritdoc}
*/
public function buildForm(array $form, FormStateInterface $form_state) {
$config = $this->config('amu_hal.settings');

$form['amu_hal_url_ws'] = array(
'#type' => 'textfield',
'#title' => $this->t('URL du webservice HAL'),
'#default_value' => $config->get('amu_hal_url_ws'),
);

$form['docType_s'] = array(
    '#type' => 'select',
    '#title' => $this->t('Selectionner le / les types de documents HAL (docTypes)'),
    '#options' => $config->get('docType_s'),
       /* [
        '1' => $this->t('One'),
        '2' => [
            '2.1' => $this->t('Two point one'),
            '2.2' => $this->t('Two point two'),
        ],
        '3' => $this->t('Three'),
    ],*/
     '#default_value' => $config->get('docType_s'),
    '#multiple' => true
);

    $form['hal_last_pub_rows'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Dernières publications'),
        '#description' => t('Nombre de publications à afficher dans le bloc Dernières publications'),
        '#default_value' => $config->get('hal_last_pub_rows'),
    );

    $form['halId_s'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Identifiant HAL du dépôt (halId_s)'),
        '#description' => t('ex: LPC, DICE '),
        '#default_value' => $config->get('halId_s'),
    );

    $form['hal_date_start'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Date (année) pour laquelle des données sont ccnsultable sur HAL'),
        '#default_value' => $config->get('hal_date_start'),
    );

    $form['structId_i'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Identifiant HAL de la structure (structId_i)'),
        '#description' => t('ex: 182200 '),
        '#default_value' => $config->get('structId_i'),
    );


    return parent::buildForm($form, $form_state);
}

/**
* {@inheritdoc}
*/
public function submitForm(array &$form, FormStateInterface $form_state) {
// Retrieve the configuration
$this->config('amu_hal.settings')
// Set the submitted configuration setting
->set('amu_hal_url_ws', $form_state->getValue('amu_hal_url_ws'))
->set('docType_s', $form_state->getValue('docType_s'))
    ->set('hal_last_pub_rows', $form_state->getValue('hal_last_pub_rows'))
    ->set('halId_s', $form_state->getValue('halId_s'))
    ->set('hal_date_start', $form_state->getValue('hal_date_start'))
    ->set('structId_i', $form_state->getValue('structId_i'))
->save();

parent::submitForm($form, $form_state);
}
}
