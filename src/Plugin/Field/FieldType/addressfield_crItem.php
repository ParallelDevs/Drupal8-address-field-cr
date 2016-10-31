<?php

namespace Drupal\addressfield_cr\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'address_cr' field type.
 *
 * @FieldType(
 *   id = "address_cr",
 *   label = @Translation("Address Field CR"),
 *   description = @Translation("This field stores a costa rican address field in the database."),
 *   category = @Translation("Address Field CR"),
 *   default_widget = "addressfield_cr_default",
 *   default_formatter = "addressfield_cr_default"
 * )
 */
class addressfield_crItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'province' => [
          'type' => 'varchar',
          'length' => 40,
        ],
        'canton' => [
          'type' => 'varchar',
          'length' => 40,
        ],
        'district' => [
          'type' => 'varchar',
          'length' => 40,
        ],
        'zipcode' => [
          'type' => 'varchar',
          'length' => 40,
        ],
        'additionalinfo' => [
          'type' => 'varchar',
          'length' => 40,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    // Add our properties.
    $properties['province'] = DataDefinition::create('string')
      ->setLabel(t('Province'))
      ->setRequired(FALSE);

    $properties['canton'] = DataDefinition::create('string')
      ->setLabel(t('Canton'))
      ->setRequired(FALSE);

    $properties['district'] = DataDefinition::create('string')
      ->setLabel(t('District'))
      ->setRequired(FALSE);

    $properties['zipcode'] = DataDefinition::create('string')
      ->setLabel(t('ZIP Code'))
      ->setRequired(FALSE);

    $properties['additionalinfo'] = DataDefinition::create('string')
      ->setLabel(t('Additional Information'))
      ->setRequired(FALSE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $province = $this->get('province')->getValue();
    $canton = $this->get('canton')->getValue();
    $district = $this->get('district')->getValue();
    $zipcode = $this->get('zipcode')->getValue();
    $additionalinfo = $this->get('additionalinfo')->getValue();
    return empty($province) && empty($canton) && empty($district) && empty($zipcode) && empty($additionalinfo);
  }

}
