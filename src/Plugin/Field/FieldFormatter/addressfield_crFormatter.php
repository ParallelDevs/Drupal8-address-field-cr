<?php

namespace Drupal\addressfield_cr\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'address_cr' formatter.
 *
 * @FieldFormatter(
 *   id = "addressfield_cr_default",
 *   label = @Translation("Address Field CR Display"),
 *   field_types = {
 *     "address_cr"
 *   }
 * )
 */
class addressfield_crFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();
    $settings = $this->getSettings();

    $summary[] = t('Displays the random string.');

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode = NULL) {
    $elements = [];

    foreach ($items as $delta => $item) {

      $information = isset($item->zipcode) ? $item->zipcode : "";
      $information .= isset($item->province) ? ", " . $item->province : "";
      $information .= isset($item->canton) ? ", " . $item->canton : "";
      $information .= isset($item->district) ? ", " . $item->district : "";
      $information .= !empty($item->additionalinfo) ? ", " . $item->additionalinfo : "";

      $elements[$delta] = array(
        '#type' => 'markup',
        '#markup' => $information,
      );
    }
    return $elements;
  }

}
