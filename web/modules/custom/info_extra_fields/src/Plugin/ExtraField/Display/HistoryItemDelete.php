<?php

namespace Drupal\info_extra_fields\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\extra_field\Plugin\ExtraFieldDisplayBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * History item edit.
 *
 * @ExtraFieldDisplay(
 *   id = "history_item_delete",
 *   label = @Translation("History item delete"),
 *   bundles = {
 *     "node.income",
 *     "node.outgo"
 *   },
 *   visible = true
 * )
 */

class HistoryItemDelete extends ExtraFieldDisplayBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * Contains information about current drupal http request.
   *
   * @var Symfony\Component\HttpFoundation\Request
   */
  private $request;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Request $request) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->request = $request;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('request_stack')->getCurrentRequest()
    );
  }

  /**
   * {@inheritdoc}
   */
  public function view(ContentEntityInterface $entity) {
    $url = Url::fromRoute('entity.node.delete_form',
      [
        'node'  => $entity->id(),
      ],
      [
        'query' => [
          'destination' => $this->request->getRequestUri(),
        ],
        'attributes' => [
          'class' => [
            'use-ajax',
            'this-is-drupal-dialog',
          ],
          'data-dialog-type' => 'modal',
        ],
      ]
    );

    $link = Link::fromTextAndUrl($this->t('delete'), $url);

    return [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'history-item-delete',
        ],
      ],
      'content' => [
        '#markup' => $link->toString(),
      ],
      '#attached' => ['library' => ['core/drupal.dialog.ajax']],
    ];
  }
}
