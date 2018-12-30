<?php

namespace Drupal\info_extra_fields\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\extra_field\Plugin\ExtraFieldDisplayBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * History item title extra field display.
 *
 * @ExtraFieldDisplay(
 *   id = "history_item_title",
 *   label = @Translation("History item title"),
 *   bundles = {
 *     "node.income",
 *     "node.outgo"
 *   },
 *   visible = true
 * )
 */

class HistoryItemTitle extends ExtraFieldDisplayBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function view(ContentEntityInterface $entity) {
    $entityLabel = $entity->label();
    $class = ['history-item-title'];

    if ($entityLabel === '[none]') {
      $class = array_merge($class, ['empty']);
    }

    return [
      '#type' => 'container',
      '#attributes' => [
        'class' => $class,
      ],
      'content' => [
        '#markup' => $entityLabel === '[none]' ? '' : $entityLabel,
      ],
    ];
  }
}