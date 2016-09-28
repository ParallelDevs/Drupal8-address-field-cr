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
		}

		// Always show the Province field
		$element['province'] = $this->generateProvinceField();

		// If we have a province, show the Canton field
		if ($optionSelected['province'] != null)
		{
			$element['canton'] = $this -> generateCantonField($optionSelected);
		}

		// If we have a Canton, show the District field
		if($optionSelected['canton'] != null)
		{
			$element['district'] = $this -> generateDistrictField($optionSelected);
		}

		// Always display the zipcode field
		$element['zipcode'] = $this ->generateZipCodeField($optionSelected);

		$element['additionalinfo'] = $this -> generateAdditionalInfoField();

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
				'callback' => 'Drupal\costa_rican_address_field\Plugin\Field\FieldWidget\costa_rican_address_fieldWidget::rebuildForm',
				'progress' => [
					'type' => 'throbber',
					'event' => 'change',
					'message' => 'Getting Cantons'
				]
			]
		];
	}

	function generateCantonField($optionSelected)
	{
		$cantons = NgetCantons($optionSelected['province']);

		return [
			'#type' => 'select',
			'#required' => FALSE,
			'#title' => t('Canton'),
			'#empty_option' => t('- Select a Canton -'),
			'#options' => $cantons,
			'#ajax' => [
				'callback' => 'Drupal\costa_rican_address_field\Plugin\Field\FieldWidget\costa_rican_address_fieldWidget::rebuildForm',
				'progress' => [
					'type' => 'throbber',
					'event' => 'change',
					'message' => 'Getting Cantons'
				]
			]
		];
	}

	function generateDistrictField($optionSelected)
	{
		$districts = NgetDistricts($optionSelected['canton']);

		return [
			'#type' => 'select',
			'#required' => FALSE,
			'#title' => t('District'),
			'#empty_option' => t('- Select a District -'),
			'#options' => $districts,
			'#ajax' => [
				'callback' => 'Drupal\costa_rican_address_field\Plugin\Field\FieldWidget\costa_rican_address_fieldWidget::rebuildForm',
				'progress' => [
					'type' => 'throbber',
					'event' => 'change',
					'message' => 'Getting Cantons'
				]
			]
		];
	}

	function generateZipCodeField($optionSelected)
	{
		return ['#type' => 'textfield',
			'#title' => t('ZIP Code'),
			'#required' => FALSE,
			'#size' => 10,
			'#value' => NgetZIPCodeByDistrict($optionSelected['district'], $optionSelected['canton']),
			'#ajax' => [
//				'progress' => [
//					'type' => 'throbber',
//					'event' => 'change',
//					'message' => 'Getting Cantons'
//				]
				'callback' => 'Drupal\costa_rican_address_field\Plugin\Field\FieldWidget\costa_rican_address_fieldWidget::rebuildForm',
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

	function rebuildForm(&$form)
	{
		$ajax_response = new AjaxResponse();
		$ajax_response -> addCommand(new ReplaceCommand('.field--widget-costa-rican-address-field-default', $form['field_company_address']));
		return $ajax_response;
	}

}