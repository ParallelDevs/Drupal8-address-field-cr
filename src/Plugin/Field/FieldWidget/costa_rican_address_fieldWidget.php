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

class costa_rican_address_fieldWidget extends WidgetBase implements WidgetInterface  {
	/**
	 * {@inheritdoc}
	 *
	 * This function generates the arrays used by Drupal to construct the HTML input elements for our custom field on the node edit page. Thus, it is called once for every element in our widget.
	 *
	 * The body of the function is comprised of a large if statement to figure out the situation under which the elements are being built (or rebuilt), and to create elements and load data into them as required by that situation.
	 */

	public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

		$fieldCurrentlyModifying =
			isset($form_state -> getUserInput()['field_company_address'][$delta])
			? $form_state -> getUserInput()['field_company_address'][$delta]
			: null;

		$triggeringElement = $_REQUEST['_triggering_element_name'];

		if ($triggeringElement === null || $triggeringElement == "field_company_address_add_more")
		{
			$deltaUpdated = null;
		}
		else
		{
			$filteredVar = filter_var($triggeringElement, FILTER_SANITIZE_NUMBER_INT);
			$deltaUpdated = intval($filteredVar);
		}

		$values = $items -> getValue();

		// If the field we're currently building is the field that was changed, update it appropriately
		if ($delta === $deltaUpdated)
		{
			// If we have address data in the database, load it into the form
			if (!empty($values[$delta]))
			{
				$element['province'] = $this -> generateProvinceField();
				$element['province']['#default_value'] = $values[$delta]['province'];

				$element['canton'] = $this -> generateCantonField($values[$delta]['province']);
				$element['canton']['#default_value'] = $values[$delta]['canton'];

				$element['district'] = $this -> generateDistrictField($values[$delta]['canton']);
				$element['district']['#default_value'] = $values[$delta]['district'];

				$element['zipcode'] = $this -> generateZipCodeField(null, null);
				$element['zipcode']['#value'] = $values[$delta]['zipcode'];

				$element['additionalinfo'] = $this -> generateAdditionalInfoField();
			}

			// Else, if a dropdown field was changed, rebuild the form accordingly
			else if (isset($_REQUEST['_triggering_element_name']))
			{
				// If canton/province/district was changed.
				if ($_REQUEST['_triggering_element_name'] == "field_company_address[" . $delta . "][province]" ||
					$_REQUEST['_triggering_element_name'] == "field_company_address[" . $delta . "][canton]" ||
					$_REQUEST['_triggering_element_name'] == "field_company_address[" . $delta . "][district]")
				{
					// Always show the Province field
					$element['province'] = $this -> generateProvinceField();

					// If we have a valid province, show the Canton field
					if (in_array($fieldCurrentlyModifying['province'], $element['province']['#options']))
					{
						$element['canton'] = $this -> generateCantonField($fieldCurrentlyModifying['province']);
					}

					// If we have a valid Canton, show the District field
					if(in_array($fieldCurrentlyModifying['canton'], $element['canton']['#options'])) // this should evaluate to true "if a canton has been selected that belongs to the selected province"
					{
						// We need to skip this operation if the user updated the province and has not yet selected a canton
						$element['district'] = $this -> generateDistrictField($fieldCurrentlyModifying['canton']);
					}

					$validCantonSelected = in_array($fieldCurrentlyModifying['canton'], $element['canton']['#options']);
					$validDistrictSelected = in_array($fieldCurrentlyModifying['district'], $element['district']['#options']);

					// Display the zipcode field if the user has selected a valid canton and district
					if ( $validCantonSelected && $validDistrictSelected)
					{
						$element['zipcode'] = $this -> generateZipCodeField($fieldCurrentlyModifying['district'], $fieldCurrentlyModifying['canton']);
					}

					$element['additionalinfo'] = $this -> generateAdditionalInfoField();
				}

				// Else if the zipcode field was changed.
				else if ($_REQUEST['_triggering_element_name'] == "field_company_address[" . $delta . "][zipcode]")
				{
					$address = NgetAddressByZIPCode($fieldCurrentlyModifying['zipcode']);

					$province = $address['province'];
					$canton = $address['canton'];
					$district = $address['district'];

					$element['province'] = $this -> generateProvinceField();
					$element['canton'] = $this -> generateCantonField($province);
					$element['district'] = $this -> generateDistrictField($canton);


					$element['province']['#default_value'] = $province;
					$element['canton']['#default_value'] =  $canton;
					$element['district']['#default_value'] = $district;
				}
//				else if ($_REQUEST['_triggering_element_name'] == "field_company_address_add_more")
//				{
//					$element['province'] = $this -> generateProvinceField();
//					$element['zipcode'] = $this -> generateZipCodeField($fieldCurrentlyModifying['district'], $fieldCurrentlyModifying['canton']);
//					$element['additionalinfo'] = $this -> generateAdditionalInfoField();
//				}
			}
		}

		// Else if the field we're currently building wasn't changed
		else if (is_int($deltaUpdated) && $delta != $deltaUpdated)
		{
			// Build and restore the value of the provice field
			$element['province'] = $this -> generateProvinceField();
			if ($fieldCurrentlyModifying['province'] != "" && $fieldCurrentlyModifying['province'] != null)
			{
				$element['province']['#default_value'] = $fieldCurrentlyModifying['province'];
			}

			// If we have a valid province, show the Canton field
			if (in_array($fieldCurrentlyModifying['province'], $element['province']['#options']))
			{
				$element['canton'] = $this -> generateCantonField($fieldCurrentlyModifying['province']);
			}

			// If we have a valid Canton, show the District field
			if(in_array($fieldCurrentlyModifying['canton'], $element['canton']['#options'])) // this should evaluate to true "if a canton has been selected that belongs to the selected province"
			{
				// We need to skip this operation if the user updated the province and has not yet selected a canton
				$element['district'] = $this -> generateDistrictField($fieldCurrentlyModifying['canton']);
			}

			$validCantonSelected = in_array($fieldCurrentlyModifying['canton'], $element['canton']['#options']);
			$validDistrictSelected = in_array($fieldCurrentlyModifying['district'], $element['district']['#options']);

			// Display the zipcode field if the user has selected a valid canton and district
			if ( $validCantonSelected && $validDistrictSelected)
			{
				$element['zipcode'] = $this -> generateZipCodeField($fieldCurrentlyModifying['district'], $fieldCurrentlyModifying['canton']);
			}

			$element['additionalinfo'] = $this -> generateAdditionalInfoField();
		}

		else
		{
			if ($_REQUEST['_triggering_element_name'] == "field_company_address_add_more")
			{
				// Build and restore the value of the provice field
				$element['province'] = $this -> generateProvinceField();
				if ($fieldCurrentlyModifying['province'] != "" && $fieldCurrentlyModifying['province'] != null)
				{
					$element['province']['#default_value'] = $fieldCurrentlyModifying['province'];
				}

				// If we have a valid province, show the Canton field
				if (in_array($fieldCurrentlyModifying['province'], $element['province']['#options']))
				{
					$element['canton'] = $this -> generateCantonField($fieldCurrentlyModifying['province']);
				}

				// If we have a valid Canton, show the District field
				if(in_array($fieldCurrentlyModifying['canton'], $element['canton']['#options'])) // this should evaluate to true "if a canton has been selected that belongs to the selected province"
				{
					// We need to skip this operation if the user updated the province and has not yet selected a canton
					$element['district'] = $this -> generateDistrictField($fieldCurrentlyModifying['canton']);
				}

				$validCantonSelected = in_array($fieldCurrentlyModifying['canton'], $element['canton']['#options']);
				$validDistrictSelected = in_array($fieldCurrentlyModifying['district'], $element['district']['#options']);

				// Display the zipcode field if the user has selected a valid canton and district
				if ( $validCantonSelected && $validDistrictSelected)
				{
					$element['zipcode'] = $this -> generateZipCodeField($fieldCurrentlyModifying['district'], $fieldCurrentlyModifying['canton']);
				}

				$element['additionalinfo'] = $this -> generateAdditionalInfoField();
			}

			// Else, load a blank form
			else
			{
				$element['province'] = $this -> generateProvinceField();
				$element['zipcode'] = $this -> generateZipCodeField($fieldCurrentlyModifying['district'], $fieldCurrentlyModifying['canton']);
				$element['additionalinfo'] = $this -> generateAdditionalInfoField();
			}
		}

		return $element;
	}

	function generateProvinceField()
	{
		return [
			'#type' => 'select',
			'#required' => FALSE,
			'#title' => 'Province',
			'#empty_option' => t('- Select a Province -'),
			'#options' => NGetProvinces(),
			'#ajax' => [
				'callback' => 'Drupal\costa_rican_address_field\Plugin\Field\FieldWidget\costa_rican_address_fieldWidget::provinceChanged',
				'progress' => [
					'type' => 'throbber',
					'event' => 'change',
					'message' => 'Getting Cantons'
				]
			]
		];
	}

	function generateCantonField($province)
	{
		return [
			'#type' => 'select',
			'#required' => FALSE,
			'#title' => t('Canton'),
			'#empty_option' => t('- Select a Canton -'),
			'#options' => NgetCantons($province),
			'#ajax' => [
				'callback' => 'Drupal\costa_rican_address_field\Plugin\Field\FieldWidget\costa_rican_address_fieldWidget::cantonChanged',
				'progress' => [
					'type' => 'throbber',
					'event' => 'change',
					'message' => 'Getting Cantons'
				]
			]
		];
	}

	function generateDistrictField($canton)
	{
		return [
			'#type' => 'select',
			'#required' => FALSE,
			'#title' => t('District'),
			'#empty_option' => t('- Select a District -'),
			'#options' => NgetDistricts($canton),
			'#ajax' => [
				'callback' => 'Drupal\costa_rican_address_field\Plugin\Field\FieldWidget\costa_rican_address_fieldWidget::districtChanged',
				'progress' => [
					'type' => 'throbber',
					'event' => 'change',
					'message' => 'Getting Districts'
				]
			]
		];
	}

	function generateZipCodeField($district, $canton)
	{
		$zipcode_field = [
			'#type' => 'textfield',
			'#title' => t('ZIP Code'),
			'#required' => FALSE,
			'#size' => 10,
			'#ajax' => [
				'callback' => 'Drupal\costa_rican_address_field\Plugin\Field\FieldWidget\costa_rican_address_fieldWidget::zipcodeChanged',
				'progress' => [
					'type' => 'throbber',
					'event' => 'change',
					'message' => 'Getting Address'
				]
			]
		];

		if($district != null || $canton != null)
		{
			$zipcode_field['#value'] = NgetZIPCodeByDistrict($district, $canton);
		}

		return $zipcode_field;
	}

	function generateAdditionalInfoField()
	{
		return [
			'#type' => 'textfield',
			'#title' => t('Additional Information'),
			'#size' => 40,
			'#required' => FALSE
		];
	}

	function provinceChanged(&$form)
	{
		$ajax_response = new AjaxResponse();
		$ajax_response -> addCommand(new ReplaceCommand('.field--widget-costa-rican-address-field-default', $form['field_company_address']));
		return $ajax_response;
	}

	function cantonChanged(&$form)
	{
		$ajax_response = new AjaxResponse();
		$ajax_response -> addCommand(new ReplaceCommand('.field--widget-costa-rican-address-field-default', $form['field_company_address']));
		return $ajax_response;
	}

	function districtChanged(&$form)
	{
		$ajax_response = new AjaxResponse();
		$ajax_response -> addCommand(new ReplaceCommand('.field--widget-costa-rican-address-field-default', $form['field_company_address']));
		return $ajax_response;
	}

	function zipcodeChanged(&$form)
	{
//		$ajax_response = new AjaxResponse();
//		$ajax_response -> addCommand(new ReplaceCommand('.field--widget-costa-rican-address-field-default', $form['field_company_address']));
//		return $ajax_response;
	}

}