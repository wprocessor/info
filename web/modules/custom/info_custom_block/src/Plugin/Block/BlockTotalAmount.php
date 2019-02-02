<?php

namespace Drupal\info_custom_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

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
  protected function blockAccess(AccountInterface $account) {
    $access = parent::blockAccess($account);
    $auth = $account->isAuthenticated();
    return AccessResult::allowedIf($access && $auth);
  }

    /**
   * {@inherit}
   */
  public function build() {
    $terms = $this->getCurrencyItems();
    $amount = [];

    $user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
    $userDefaultCurrencyId = $user->field_default_currency->getValue()[0]['target_id'];
    $userDefaultCurrency = \Drupal\taxonomy\Entity\Term::load($userDefaultCurrencyId);

    $userTotal = 0;
    
    foreach ($terms as $nextTerm) {
      $income = $this->getAmountByTermAndDirection($nextTerm->id(), 'income');
      $outgo = $this->getAmountByTermAndDirection($nextTerm->id(), 'outgo');

      $total = $income - $outgo;

      $siteBaseTotal = $total * $nextTerm->field_rate->value;
      
      //var_dump($siteBaseTotal);
      //var_dump($userDefaultCurrency->field_rate->value);
      
      $userTotalIteration = $siteBaseTotal / $userDefaultCurrency->field_rate->value;
      
      $userTotal += $userTotalIteration;
      
      //var_dump($userTotal);
      
      
      $amount[] = [
        $this->getHtmlItem($nextTerm->label()),
        $this->getHtmlItem($total),
      ];
    }

    $amount[] = [
      $this->getHtmlItem($userDefaultCurrency->label()),
      $this->getHtmlItem(money_format('%i', $userTotal)),
    ];

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
