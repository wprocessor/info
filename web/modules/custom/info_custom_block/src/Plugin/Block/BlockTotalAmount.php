<?php

namespace Drupal\info_custom_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a Total amount block.
 *
 * @Block(
 *   id = "block_total_amount",
 *   admin_label = @Translation("Block total amount"),
 *   category = @Translation("Info"),
 * )
 */
class BlockTotalAmount extends BlockBase {

  /**
   * {@inherit}
   */
  public function build() {
    $terms = $this->getCurrencyItems();
    $amount = [];

    foreach ($terms as $nextTerm) {
      $income = $this->getAmountByTermAndDirection($nextTerm->id(), 'income');
      $outgo = $this->getAmountByTermAndDirection($nextTerm->id(), 'outgo');

      $amount[] = [
        $this->getHtmlItem($nextTerm->label()),
        $this->getHtmlItem(($income - $outgo)),
      ];
    }

    return [
      '#theme' => 'item_list',
      '#type' => 'ul',
      '#items' => $amount,
    ];
  }

  /**
   * Returns currency items.
   *
   * @return array
   *   Currency items.
   */
  private function getCurrencyItems() {
    return \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadByProperties([
        'vid' => 'currency',
      ]);
  }

  /**
   * Returns amount by currency (tid) and direction (income|outgo).
   *
   * @param type $tid
   *   Taxonomy term id.
   * @param string $direction
   *   Direction allowed: (income|outgo).
   *
   * @return float
   *   Returns amount.
   */
  private function getAmountByTermAndDirection($tid, $direction = 'income') {
    $data = views_get_view_result('amount_' . $direction, NULL, $tid);
    $result = reset($data);
    return $result->node__field_sum_field_sum_value ?? 0;
  }

  /**
   * Returns HTML markup for specific value.
   *
   * @param string $value
   *   Speciic value.
   *
   * @return array
   *   Html markup.
   */
  private function getHtmlItem($value) {
    return [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => $value,
    ];
  }

}
