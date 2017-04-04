<?php

namespace Drupal\wraith\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * WraithSettingsForm
 */
class WraithSettingsForm extends ConfigFormBase {

  protected function getEditableConfigNames() {
    return ['wraith.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'wraith_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('wraith.settings');

    $form['wraith_settings']['basics'] = [
      '#title' => $this->t('Basics'),
      '#type' => 'fieldset',
    ];

    $form['wraith_settings']['basics']['current_domain'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Current domain'),
      '#default_value' => $config->get('current_domain'),
      '#required' =>true
    ];
    $form['wraith_settings']['basics']['new_domain'] = [
      '#type' => 'textfield',
      '#title' => $this->t('New domain'),
      '#default_value' => $config->get('new_domain'),
      '#required' =>true
    ];

    $form['wraith_settings']['basics']['percentage'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Average percentages'),
      '#description' => $this->t('How many urls should be exported in percent each selected bundle'),
      '#default_value' => $config->get('percentage',10),
      '#required' =>true
    ];

    $form['wraith_settings']['basics']['min'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Minimum urls'),
      '#description' => $this->t('How many urls should be minimum exported each selected bundle'),
      '#default_value' => $config->get('min',10),
      '#required' =>true
    ];

    $form['wraith_settings']['basics']['max'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Maximum urls'),
      '#description' => $this->t('How many urls should be maximum exported each selected bundle'),
      '#default_value' => $config->get('max',10),
      '#required' =>true
    ];

    $form['wraith_settings']['languages'] = [
      '#title' => $this->t('Languages'),
      '#type' => 'fieldset',
    ];

    $languageManager = \Drupal::service('language_manager');
    $languages = $languageManager->getLanguages();
    $language_options = [];
    foreach ($languages as $lang_code => $language) {
      $language_options[$lang_code] = $language->getName();
    }
    $language_default_value = $config->get('languages') == NULL ? [] : $config->get('languages');
    $form['wraith_settings']['languages']['languages'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Select language'),
      '#default_value' => $language_default_value,
      '#options' => $language_options
    ];


    $form['wraith_settings']['types'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Generate Urls from: '),
    ];

    $types = ['node', 'taxonomy_term', 'media'];

    foreach ($types as $type) {
      $bundles = \Drupal::service('entity_type.bundle.info')
        ->getBundleInfo($type);
      $bundle_options = [];
      foreach ($bundles as $key => $bundle) {
        $bundle_options[$key] = $bundle['label'];
      }
      $bundle_default_value = $config->get('type_' . $type) == NULL ? [] : $config->get('type_' . $type);
      $form['wraith_settings']['types']['type_' . $type] = [
        '#type' => 'checkboxes',
        '#title' => $this->t($type . ' bundles'),
        '#default_value' => $bundle_default_value,
        '#options' => $bundle_options
      ];
    }
    $form['wraith_settings']['types']['additional_urls'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Additional urls'),
      '#default_value' => $config->get('additional_urls'),
      '#description' => $this->t('Add one internal url each line. Eg: frontpage: /node/11' )
    ];
    return parent::buildForm($form, $form_state);
  }

  private function getActiveCheckboxes($ary) {
    $active = [];
    foreach ($ary as $key => $value) {
      if ($value !== 0) {
        $active[] = $key;
      }
    }
    return $active;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('wraith.settings');
    $values = $form_state->getValues();
    $config->set('languages', $this->getActiveCheckboxes($form_state->getValue('languages')));
    $config->set('current_domain', $form_state->getValue('current_domain'));
    $config->set('new_domain', $form_state->getValue('new_domain'));
    $config->set('percentage', $form_state->getValue('percentage'));
    $config->set('min', $form_state->getValue('min'));
    $config->set('max', $form_state->getValue('max'));
    $config->set('additional_urls', $form_state->getValue('additional_urls'));

    foreach ($values as $key => $value) {
      if (strpos($key, 'type_') === 0) {
        $config->set($key, $this->getActiveCheckboxes($value));
      }
    }
    $config->save();
    parent::submitForm($form, $form_state);
  }
}
