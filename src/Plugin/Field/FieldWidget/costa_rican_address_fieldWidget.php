<?php

namespace Drupal\costa_rican_address_field\Plugin\Field\FieldWidget;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Field\WidgetInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'address_cr' widget.
 *
 * @FieldWidget(
 *   id = "costa_rican_address_field_default",
 *   module = "costa_rican_address_field",
 *   label = @Translation("Costa Rican Address Field Widget"),
 *   field_types = {
 *     "address_cr"
 *   }
 * )
 */
class costa_rican_address_fieldWidget extends WidgetBase implements WidgetInterface {

  /**
   * {@inheritdoc}
   *
   * This function generates the arrays used by Drupal to construct the HTML input elements for our custom field on the node edit page. Thus, it is called once for every element in our widget.
   *
   * The body of the function is comprised of a large if statement to figure out the situation under which the elements are being built (or rebuilt), and to create elements and load data into them as required by that situation.
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $fieldCurrentlyModifying =
    isset($form_state->getUserInput()['field_company_address'][$delta])
                ? $form_state->getUserInput()['field_company_address'][$delta]
                : NULL;

    $triggeringElement =
    isset($_REQUEST['_triggering_element_name'])
                ? $_REQUEST['_triggering_element_name']
                : NULL;

    if ($triggeringElement === NULL || $triggeringElement == "field_company_address_add_more") {
      $deltaUpdated = NULL;
    }
    else {
      $filteredVar = filter_var($triggeringElement, FILTER_SANITIZE_NUMBER_INT);
      $deltaUpdated = intval($filteredVar);
    }

    $values = $items->getValue();

    // If the field we're currently building is the field that was changed, update it appropriately.
    if ($delta === $deltaUpdated) {
      // If a dropdown field was changed, rebuild the form accordingly.
      if (isset($_REQUEST['_triggering_element_name'])) {
        // If canton/province/district was changed.
        if ($_REQUEST['_triggering_element_name'] == "field_company_address[" . $delta . "][province]" ||
        $_REQUEST['_triggering_element_name'] == "field_company_address[" . $delta . "][canton]" ||
        $_REQUEST['_triggering_element_name'] == "field_company_address[" . $delta . "][district]") {
          // Always show the Province field.
          $element['province'] = $this->generateProvinceField();

          // If we have a valid province, show the Canton field.
          if (in_array($fieldCurrentlyModifying['province'], $element['province']['#options'])) {
            $element['canton'] = $this->generateCantonField($fieldCurrentlyModifying['province']);
          }

          // If we have a valid Canton, show the District field
          // this should evaluate to true "if a canton has been selected that belongs to the selected province".
          if (in_array($fieldCurrentlyModifying['canton'], $element['canton']['#options'])) {
            // We need to skip this operation if the user updated the province and has not yet selected a canton.
            $element['district'] = $this->generateDistrictField($fieldCurrentlyModifying['canton']);
          }

          $validCantonSelected = in_array($fieldCurrentlyModifying['canton'], $element['canton']['#options']);
          $validDistrictSelected = in_array($fieldCurrentlyModifying['district'], $element['district']['#options']);

          // Display the zipcode field if the user has selected a valid canton and district.
          if ($validCantonSelected && $validDistrictSelected) {
            $element['zipcode'] = $this->generateZipCodeField($fieldCurrentlyModifying['district'], $fieldCurrentlyModifying['canton']);
          }

          $element['additionalinfo'] = $this->generateAdditionalInfoField();
        }

        // Else if the zipcode field was changed.
        // I think we need to check $delta here somehow.
        elseif ($_REQUEST['_triggering_element_name'] == "field_company_address[" . $delta . "][zipcode]") {
          // Get the address (province/canton/district) for the given zipcode.
          $address = NgetAddressByZIPCode($fieldCurrentlyModifying['zipcode']);

          // If the user changed the zipcode field to a valid zipcode, rebuild the input field.
          if (!empty($address)) {
            // Generate the fields.
            $element['province'] = $this->generateProvinceField();
            $element['province']['#default_value'] = $address["province"];

            $element['canton'] = $this->generateCantonField($address['province']);
            $element['canton']['#default_value'] = $address['canton'];

            $element['district'] = $this->generateDistrictField($address['canton']);
            $element['district']['#default_value'] = $address['district'];

            $element['zipcode'] = $this->generateZipCodeField(NULL, NULL);
            $element['zipcode']['#default_value'] = $fieldCurrentlyModifying['zipcode'];

            $element['additionalinfo'] = $this->generateAdditionalInfoField();
            $element['additionalinfo']['#default_value'] = $fieldCurrentlyModifying['additionalinfo'];
          }

          // Otherwise build a blank address field and set zipcode to null.
          else {
            $element = $this->loadBlankAddressField($element);
            $element['zipcode']['#value'] = "";
          }
        }
      }
    }

    // Else if the field we're currently building wasn't changed, rebuild it with the original data.
    elseif (is_int($deltaUpdated) && $delta != $deltaUpdated) {
      // Build and restore the value of the provice field.
      $element['province'] = $this->generateProvinceField();
      if ($fieldCurrentlyModifying['province'] != "" && $fieldCurrentlyModifying['province'] != NULL) {
        $element['province']['#default_value'] = $fieldCurrentlyModifying['province'];
      }

      // If we have a valid province, show the Canton field.
      if (in_array($fieldCurrentlyModifying['province'], $element['province']['#options'])) {
        $element['canton'] = $this->generateCantonField($fieldCurrentlyModifying['province']);
      }

      // If we have a valid Canton, show the District field
      // this should evaluate to true "if a canton has been selected that belongs to the selected province".
      if (in_array($fieldCurrentlyModifying['canton'], $element['canton']['#options'])) {
        // We need to skip this operation if the user updated the province and has not yet selected a canton.
        $element['district'] = $this->generateDistrictField($fieldCurrentlyModifying['canton']);
      }

      $validCantonSelected = in_array($fieldCurrentlyModifying['canton'], $element['canton']['#options']);
      $validDistrictSelected = in_array($fieldCurrentlyModifying['district'], $element['district']['#options']);

      // Display the zipcode field if the user has selected a valid canton and district.
      if ($validCantonSelected && $validDistrictSelected) {
        $element['zipcode'] = $this->generateZipCodeField($fieldCurrentlyModifying['district'], $fieldCurrentlyModifying['canton']);
      }

      $element['additionalinfo'] = $this->generateAdditionalInfoField();
    }

    // Else if nothing was updated, rebuild the field as it was, rebuild it from the DB, or load a blank one.
    else {
      if ($triggeringElement == "field_company_address_add_more") {
        // Build the province field.
        $element['province'] = $this->generateProvinceField();

        // If we have data stored for this field.
        if ($fieldCurrentlyModifying['province'] != "" && $fieldCurrentlyModifying['province'] != NULL) {
          // Populate the newly rebuilt field with that data.
          $element['province']['#default_value'] = $fieldCurrentlyModifying['province'];
        }

        // If we have a valid province, show the Canton field.
        if (in_array($fieldCurrentlyModifying['province'], $element['province']['#options'])) {
          $element['canton'] = $this->generateCantonField($fieldCurrentlyModifying['province']);
        }

        // If we have a valid Canton, show the District field.
        // this should evaluate to true "if a canton has been selected that belongs to the selected province".
        if (in_array($fieldCurrentlyModifying['canton'], $element['canton']['#options'])) {
          // We need to skip this operation if the user updated the province and has not yet selected a canton.
          $element['district'] = $this->generateDistrictField($fieldCurrentlyModifying['canton']);
        }

        $validCantonSelected = in_array($fieldCurrentlyModifying['canton'], $element['canton']['#options']);
        $validDistrictSelected = in_array($fieldCurrentlyModifying['district'], $element['district']['#options']);

        // Display the zipcode field if the user has selected a valid canton and district.
        if ($validCantonSelected && $validDistrictSelected) {
          $element['zipcode'] = $this->generateZipCodeField($fieldCurrentlyModifying['district'], $fieldCurrentlyModifying['canton']);
        }
        else {
          $element['zipcode'] = $this->generateZipCodeField(NULL, NULL);
        }

        $element['additionalinfo'] = $this->generateAdditionalInfoField();
      }

      // If we have address data in the database, load it into the form.
      elseif (!empty($values[$delta])) {
        $element['province'] = $this->generateProvinceField();
        $element['province']['#default_value'] = $values[$delta]['province'];

        $element['canton'] = $this->generateCantonField($values[$delta]['province']);
        $element['canton']['#default_value'] = $values[$delta]['canton'];

        $element['district'] = $this->generateDistrictField($values[$delta]['canton']);
        $element['district']['#default_value'] = $values[$delta]['district'];

        $element['zipcode'] = $this->generateZipCodeField(NULL, NULL);
        $element['zipcode']['#value'] = $values[$delta]['zipcode'];

        $element['additionalinfo'] = $this->generateAdditionalInfoField();
      }

      // Else, load a blank form.
      else {
        $element = $this->loadBlankAddressField($element);
      }
    }

    return $element;
  }

  /**
   *
   */
  public function loadBlankAddressField($element) {
    // Generate blank province, zipcode, and additional fields.
    $element['province'] = $this->generateProvinceField();
    $element['zipcode'] = $this->generateZipCodeField(NULL, NULL);
    $element['additionalinfo'] = $this->generateAdditionalInfoField();

    return $element;
  }

  /**
   *
   */
  public function generateProvinceField() {
    return [
      '#type' => 'select',
      '#required' => FALSE,
      '#title' => 'Province',
      '#empty_option' => t('- Select a Province -'),
      '#options' => NGetProvinces(),
      '#ajax' => [
        'callback' => 'Drupal\costa_rican_address_field\Plugin\Field\FieldWidget\costa_rican_address_fieldWidget::replaceFormCallback',
        'progress' => [
          'type' => 'throbber',
          'event' => 'change',
          'message' => 'Getting Cantons',
        ],
      ],
    ];
  }

  /**
   *
   */
  public function generateCantonField($province) {
    return [
      '#type' => 'select',
      '#required' => FALSE,
      '#title' => t('Canton'),
      '#empty_option' => t('- Select a Canton -'),
      '#options' => NgetCantons($province),
      '#ajax' => [
        'callback' => 'Drupal\costa_rican_address_field\Plugin\Field\FieldWidget\costa_rican_address_fieldWidget::replaceFormCallback',
        'progress' => [
          'type' => 'throbber',
          'event' => 'change',
          'message' => 'Getting Cantons',
        ],
      ],
    ];
  }

  /**
   *
   */
  public function generateDistrictField($canton) {
    return [
      '#type' => 'select',
      '#required' => FALSE,
      '#title' => t('District'),
      '#empty_option' => t('- Select a District -'),
      '#options' => NgetDistricts($canton),
      '#ajax' => [
        'callback' => 'Drupal\costa_rican_address_field\Plugin\Field\FieldWidget\costa_rican_address_fieldWidget::replaceFormCallback',
        'progress' => [
          'type' => 'throbber',
          'event' => 'change',
          'message' => 'Getting Districts',
        ],
      ],
    ];
  }

  /**
   *
   */
  public function generateZipCodeField($district, $canton) {
    $zipcode_field = [
      '#type' => 'textfield',
      '#title' => t('ZIP Code'),
      '#required' => FALSE,
      '#size' => 10,
      '#ajax' => [
        'callback' => 'Drupal\costa_rican_address_field\Plugin\Field\FieldWidget\costa_rican_address_fieldWidget::replaceFormCallback',
        'progress' => [
          'type' => 'throbber',
          'event' => 'change',
          'message' => 'Getting Address',
        ],
        'disable-refocus' => TRUE,
      ],
    ];

    if ($district != NULL && $canton != NULL) {
      $zipcode_field['#value'] = NgetZIPCodeByDistrict($district, $canton);
    }

    return $zipcode_field;
  }

  /**
   *
   */
  public function generateAdditionalInfoField() {
    return [
      '#type' => 'textfield',
      '#title' => t('Additional Information'),
      '#size' => 40,
      '#required' => FALSE,
    ];
  }

  /**
   *
   */
  public function replaceFormCallback(&$form) {
    $ajax_response = new AjaxResponse();
    $ajax_response->addCommand(new ReplaceCommand('.field--widget-costa-rican-address-field-default', $form['field_company_address']));
    return $ajax_response;
  }

}
