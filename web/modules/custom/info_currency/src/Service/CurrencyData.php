<?php

namespace Drupal\info_currency\Service;

use Drupal\Core\Url;

/**
 * Description of CurrencyData
 */
class CurrencyData {

  /**
   * Returns rate for second currency relatively to first.
   *
   * @param string $asked
   *   Asked currency rate.
   * @param string $base
   *   Base currency to relate.
   *
   * @return string
   *   Currency rate.
   */
  public function getRate(string $asked, string $base) {
    $uri = "https://free.currencyconverterapi.com/api/v6/convert";

    $pair = implode(
      "_",
      [
        $asked,
        $base,
      ]
    );

    $options = [
      'query' => [
        'q' => $pair,
        'compact' => 'ultra',
      ]
    ];

    $url = Url::fromUri($uri, $options);

    $response = file_get_contents($url->toString());
    $data = json_decode($response, TRUE);

    return floatval($data[$pair]);
  }

}
