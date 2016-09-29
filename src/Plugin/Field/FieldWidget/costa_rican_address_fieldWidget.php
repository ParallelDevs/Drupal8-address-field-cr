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
	 */

	public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

		if (isset($form_state -> getUserInput()['field_company_address']))
		{
			$optionSelected = $form_state -> getUserInput()['field_company_address'][0];
		}
		else
		{
			$optionSelected = null;
//			$items->getValue()
		}

		// If the Province/Canton/District fields were updated
		if ($optionSelected['province'] != null || $optionSelected['canton'] != null || $optionSelected['district'] != null)
		{
			// Always show the Province field
			$element['province'] = $this -> generateProvinceField();

			// If we have a valid province, show the Canton field
			if (in_array($optionSelected['province'], $element['province']['#options']))
			{
				$element['canton'] = $this -> generateCantonField($optionSelected['province']);
			}

			// If we have a valid Canton, show the District field
			if(in_array($optionSelected['canton'], $element['canton']['#options'])) // this should evaluate to true "if a canton has been selected that belongs to the selected province"
			{
				// We need to skip this operation if the user updated the province and has not yet selected a canton
				$element['district'] = $this -> generateDistrictField($optionSelected['canton']);
			}


			$validCantonSelected = in_array($optionSelected['canton'], $element['canton']['#options']);
			$validDistrictSelected = in_array($optionSelected['district'], $element['district']['#options']);
			// Always display the zipcode and additional info fields
			if ( $validCantonSelected && $validDistrictSelected)
			{
				$element['zipcode'] = $this -> generateZipCodeField($optionSelected['district'], $optionSelected['canton']);
			}
			$element['additionalinfo'] = $this -> generateAdditionalInfoField();
		}
		// Else if the address field was updated
		else if ($optionSelected['zipcode'] != null)
		{
//			$address = NgetAddressByZIPCode($optionSelected['zipcode']);
//
//			$province = $address['province'];
//			$canton = $address['canton'];
//			$district = $address['district'];
		}
		// Default load functionality
		else
		{
			// if we have data from the database, load it, otherwise load a blank form
			$element['province'] = $this -> generateProvinceField();
			$element['zipcode'] = $this -> generateZipCodeField($optionSelected['district'], $optionSelected['canton']);
			$element['additionalinfo'] = $this -> generateAdditionalInfoField();
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
			'#options' => NgetDistricts($canton) != null ? NgetDistricts($canton) : null,
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
		return ['#type' => 'textfield',
			'#title' => t('ZIP Code'),
			'#required' => FALSE,
			'#size' => 10,
			'#value' => NgetZIPCodeByDistrict($district, $canton),
			'#ajax' => [
				'callback' => 'Drupal\costa_rican_address_field\Plugin\Field\FieldWidget\costa_rican_address_fieldWidget::zipcodeChanged',
				'progress' => [
					'type' => 'throbber',
					'event' => 'change',
					'message' => 'Getting Address'
				]
			]
		];
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

//	function buildFormFieldsFromZipcode($zipcode)
//	{
//		$address = NgetAddressByZIPCode($zipcode);
//
//		$province = $address['province'];
//		$canton = $address['canton'];
//		$district = $address['district'];
//
//
//	}

}