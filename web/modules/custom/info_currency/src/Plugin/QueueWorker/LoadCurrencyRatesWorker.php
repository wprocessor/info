<?php

namespace Drupal\info_currency\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\info_currency\Service\CurrencyData;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Processes currency rates load.
 *
 * @QueueWorker(
 *   id = "info_currency_rates_load",
 *   title = @Translation("Currency rates loader"),
 *   cron = {"time" = 20}
 * )
 */
class LoadCurrencyRatesWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Provides service which help to get currency data.
   *
   * @var \Drupal\info_currency\Service\CurrencyData
   */
  private $currencyData;

  /**
   * Provides service to manage entities.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\info_currency\Service\CurrencyData $currencyData
   *   Contains currency data service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Contains entity type manager service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    CurrencyData $currencyData,
    EntityTypeManagerInterface $entityTypeManager) {

    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currencyData = $currencyData;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('info_currency.currency.data'),
      $container->get('entity_type.manager')
    );
  }
  
  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    if (!empty($data)) {
      $rate = $this->currencyData->getRate($data->asked, $data->base);

      $entities = $this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties([
        'field_code' => $data->asked,
      ]);

      if (!empty($entities)) {
        $askedEntity = reset($entities);
        $askedEntity->field_rate->value = $rate;
        $askedEntity->save();
      }
    }
  }

}
