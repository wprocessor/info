<?php

namespace Drupal\info_currency\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Description of CurrencyDataController
 */
class CurrencyDataController extends ControllerBase {
  public function listRates() {
    $config = \Drupal::config('info_currency.settings');
    
    $baseCurrency = $config->get('base_currency');

    
    $currencyData = \Drupal::service('info_currency.currency.data');
    $data = $currencyData->getRate('RUB', 'USD');

    $queue = \Drupal::service('queue')->get('info_currency_rates_load');
    
    var_dump($data);

    info_currency_cron();

    exit;
  }
}
