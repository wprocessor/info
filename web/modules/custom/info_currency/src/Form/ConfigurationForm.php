<?php

namespace Drupal\info_currency\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Term;

/**
 * Description of ConfigurationForm.
 */
class ConfigurationForm extends ConfigFormBase {

  /** @var string Config settings */
  const SETTINGS = 'info_currency.settings';

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'info_currency_configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);
    $baseCurrency = $config->get('base_currency', NULL);
    $defaultCurrency = $this->getDefaultBaseCurrency($baseCurrency) ?? '';

    $form['base_currency'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Site base currency'),
      '#target_type' => 'taxonomy_term',
      '#selection_settings' => [
        'target_bundles' => [
          'currency'
        ],
      ],
      '#default_value' => $defaultCurrency,
      '#required' => TRUE,
    ];

    $form['currency_update_interval'] = [
      '#type' => 'number',
      '#title' => $this->t('Currency update interval'),
      '#default_value' => $config->get('currency_update_interval'),
      '#min' => 1,
      '#step' => 1,
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Returns term is exists by currency code.
   *
   * @param string $currencyCode
   *   Contains currency code.
   *
   * @return Drupal\taxonomy\Entity\Term|null
   *   Returns term related to selected currency.
   */
  private function getDefaultBaseCurrency($currencyCode) {
    if (!empty($currencyCode)) {
      $entityTypeManager = \Drupal::entityTypeManager();
      $entities = $entityTypeManager->getStorage('taxonomy_term')->loadByProperties([
        'field_code' => $currencyCode,
      ]);

      if (count($entities)) {
        return reset($entities);
      }
    }

    return NULL;
  }
  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $editableConfig = $this->configFactory->getEditable(static::SETTINGS);
    $baseCurrencyId = $form_state->getValue('base_currency');
    $baseCurrency = Term::load($baseCurrencyId);
    $editableConfig->set('base_currency', $baseCurrency->field_code->value);
    $editableConfig->set('currency_update_interval', $form_state->getValue('currency_update_interval'));
    $editableConfig->save();

    parent::submitForm($form, $form_state);
  }

}
