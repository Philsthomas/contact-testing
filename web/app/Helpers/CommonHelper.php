<?php

use App\Models\Shop;
use App\Models\Form;
use App\Models\FormTemplate;

use Illuminate\Database\QueryException;

if (!function_exists('getShopIdFromDomain')) {
    function getShopIdFromDomain($shopDomain) {

		try {

			$shopData = Shop::select('id')
			->where('shop_domain', $shopDomain)
			->first();

			if (!$shopData) 
				return response()->json(['status' => 0, 'message' => trans('commonMessages.shop_not_found'), 'data' => '']);
			
			return response()->json(['status' => 1, 'message' => '', 'data' => $shopData->id]);
		} catch (QueryException $ex) {
			return response()->json(['status' => 0, 'message' => trans('commonMessages.shop_fetch_failed'), 'data' => '']);
	  	}
	}
}

if (!function_exists('trimElementOptions')) {
    function trimElementOptions($string) {
    
        $explodeArray = explode(',',$string);
		$tempArray = array();
		$newArray = array();

		foreach ($explodeArray as $key => $value) {
			if(trim($value) != '') {
				if(strpos($value, '=>')) {

					$explodeArrowArray = explode('=>',$value);
					$tempArrayArrowKey = trim($explodeArrowArray[0]);
					$tempArrayArrowValue = trim($explodeArrowArray[1]);

					if(!array_key_exists($tempArrayArrowKey,$tempArray)) {
						$tempArray[$tempArrayArrowKey] = $tempArrayArrowValue;
						$newArray[] = $tempArrayArrowKey.'=>'.$tempArrayArrowValue;
					}
				}
				else {
					$tempArrayKey = trim($value);

					if(!array_key_exists($tempArrayKey,$tempArray)) {
						$tempArray[trim($value)] = trim($value);
						$newArray[] = trim($value);
					}
				}
			}
		}
		return implode(',', $newArray);
	}
}

if (!function_exists('updateFormCode')) {

	function updateFormCode($formId) { 

		$message = '';  
		$data = new \stdClass(); 
	  
		try {
	  
		  $query = 'formFetch';
		  $formData = Form::select('name','status','fields', 'template_id', 'submit_button_text', 'submit_button_class', 'after_submission', 'redirect_url', 'thanks_message')
		  ->where('id', $formId)
		  ->first();
	  
		  if(!$formData){
			return response()->json(['status' => 0, 'message' => trans('formMessages.form_not_found'), 'data' => $data]);
		  }
	    
		  $submitButtonText = $formData['submit_button_text'];
		  $submitButtonClass = $formData['submit_button_class'];
		  $afterSubmission = $formData['after_submission'];
		  $redirectUrl = $formData['redirect_url'];
		  $thanksMessage = $formData['thanks_message'];
		  
		  $query = 'templateFetch';
		  $formTemplateData = FormTemplate::select('layout', 'form_body_html', 'form_element_html', 'form_submit_html')
		  ->where('id', $formData['template_id'])
		  ->first();
	  
		  if(!$formTemplateData){
			return response()->json(['status' => 0, 'message' => trans('formMessages.template_not_found'), 'data' => $data]);
		  }
		  
		} catch (QueryException $ex) {
		  $message = ($query == 'formFetch') ? trans('formMessages.form_fetch_failed') : trans('formMessages.template_fetch_failed');
		  return response()->json(['status' => 0, 'message' => $message, 'data' => $data]);
		}
	  
		$contactFormId = "cfm_".$formId."_contact";              
	  
		$formContent = "";
		$formContent .= '{%- assign formId = "'.$contactFormId.'" -%}';
		$formContent .= '{% form "contact", id: formId %}';
		$formContent .= '{% if form.posted_successfully? %}';
	  
		if($afterSubmission == config('constants.AFTER_FORM_SUBMISSION_REDIRECT_TO_PAGE') && $redirectUrl != "") {
		  $formContent .= '<script>window.location.href= "'.$redirectUrl.'"</script>';
		}
		else {
		  $formContent .= '<p class="success-msg" style="color:#4828c5;text-align:center;">'.$thanksMessage.'</p>';
		}
	  
		$formContent .= '{% endif %}';  
		
		$requiredCheckboxes = [];
					  
		$formFields = json_decode($formData['fields'],true);
	  
		foreach($formFields as $elementData) {
	  
		  if($elementData['element_name'] != "") {
	  
			$class = "";
			$maxLength = "";
			
			if(isset($elementData['css_class']) && $elementData['css_class'] != "")
			  $class = 'class="'.$elementData['css_class'].'"'; 									  
												
			if(isset($elementData['max_length']) && $elementData['max_length'] > 0)
			  $maxLength = ' maxlength="'.$elementData['max_length'].'"';        
													  
			switch ($elementData['element_type']) {
	  
			  case config('constants.ELEMENT_TYPE_TEXTFIELD'):
	  
				$elementHtml = '<input type="text" '.$class.$maxLength.' id="{{ formId }}-'.$elementData['element_name'].'" name="contact['.$elementData['element_name'].']"';
													
				if($elementData['element_required'] == config('constants.ELEMENT_REQUIRED'))
				  $elementHtml .= ' required';

				$elementHtml .=' value="{% if form.'.$elementData['element_name'].' %}{{ form.'.$elementData['element_name'].' }}{% else %}'.$elementData['default_value'].'{% endif %}"';
														
				$elementHtml .= '>';
				break;
	  
			  case config('constants.ELEMENT_TYPE_EMAIL'): 
	  
				$elementHtml = '<input '.$class.$maxLength;
												  
				if($elementData['element_required'] == config('constants.ELEMENT_REQUIRED'))
				  $elementHtml .= ' required';				
				
				$elementHtml .=' value="{% if form.'.$elementData['element_name'].' %}{{ form.'.$elementData['element_name'].' }}{% else %}'.$elementData['default_value'].'{% endif %}"';
											  				
				$elementHtml .= ' type="email" id="{{ formId }}-'.$elementData['element_name'].'" name="contact['.$elementData['element_name'].']">';
															  				
				break;
	  
			  case config('constants.ELEMENT_TYPE_TEXTAREA'):
	  
				$elementHtml = '<textarea '.$class;
							  
				if($elementData['element_required'] == config('constants.ELEMENT_REQUIRED'))
				  $elementHtml .= ' required';
																	  				
				  $elementHtml .= ' rows="'.$elementData['rows'].'" id="{{ formId }}-'.$elementData['element_name'].'" name="contact['.$elementData['element_name'].']">';                 		
				  $elementHtml .='{% if form.'.$elementData['element_name'].' %}{{- form.'.$elementData['element_name'].' -}}{% else %}'.$elementData['default_value'].'{% endif %}';
				  $elementHtml .= '</textarea>';
				break;
	  
			  case config('constants.ELEMENT_TYPE_DROPDOWN'):
	  
				$multiple = "";
				$elementHtml11 = '<select '.$class.' id="{{ formId }}-'.$elementData['element_name'].'" name="contact['.$elementData['element_name'].']"';
																  
				if($elementData['element_required'] == config('constants.ELEMENT_REQUIRED'))
				  $elementHtml11 .= ' required';
	  
				if($elementData['multi_select_drop_down'] && $elementData['multi_select_drop_down'] == config('constants.ELEMENT_ALLOW_MULTISELECT_DROPDOWN'))
				  $multiple = " multiple";
																	  
				$elementHtml11 .= $multiple.'>';                                                      
				$options_exp = explode(",",$elementData['options']);
	  
				foreach ($options_exp as $keyOption => $valueOption) {
				  $selected = "";
				  $optionExplode = explode('=>', $valueOption);																						  

				  $optionKey = $optionExplode[0];
				  if(strpos($valueOption, '=>'))	
					$optionValue = $optionExplode[1];						  									  
				  else 			
					$optionValue = $optionExplode[0];

				  if($elementData['default_value'] != "" && $elementData['default_value'] == $optionValue)
					$selected = "selected";
																				  
				  $elementHtml11 .= '<option value="'.$optionValue.'" '.$selected.'>'.$optionKey.'</option>';
				}
																	  
				$elementHtml11 .= '</select>';
				$elementHtml = $elementHtml11;
				break;
	  
			  case config('constants.ELEMENT_TYPE_DATE'):
	  
				$elementHtml = '<input type="date" '.$class.' id="{{ formId }}-'.$elementData['element_name'].'" name="contact['.$elementData['element_name'].']"';
																			
				if($elementData['element_required'] == config('constants.ELEMENT_REQUIRED'))
				  $elementHtml .= ' required';
	  
				$elementHtml .= '>';
				break;
	  
			  case config('constants.ELEMENT_TYPE_CHECKBOX'):
	  
				$newLine = '';
							  
				if($elementData['check_radio_line_break'] == config('constants.ELEMENT_DISPLAY_OPTIONS_NEW_LINE'))
				  $newLine = '<br/>';
																	  
				$elementHtml11 = "";
				$options_exp = explode(",",$elementData['options']);

				if($elementData['element_required'] == config('constants.ELEMENT_REQUIRED'))
					$requiredCheckboxes[] = $elementData['element_name'];
								
				if(isset($elementData['css_class']) && $elementData['css_class'] != "")
			  		$checkboxClass = 'class="'.$elementData['css_class'].' '.$elementData['element_name'].'"';
				else 
					$checkboxClass = 'class="'.$elementData['element_name'].'"';

				foreach ($options_exp as $keyOption => $valueOption) {
																		  
				  $selected = "";			  
				  $optionExplode = explode('=>', $valueOption);
																		  
				  $optionKey = $optionExplode[0];
				  if(strpos($valueOption, '=>')) 	
					$optionValue = $optionExplode[1];						  									  
				  else 			
					$optionValue = $optionExplode[0];													  	
				  
				if($elementData['default_value'] != "" && $elementData['default_value'] == $optionValue)
					$selected="checked";
																				
				  $elementHtml11 .= '<input type="checkbox" '.$checkboxClass.' value="'.$optionValue.'" name="contact['.$elementData['element_name'].'_'.$optionValue.']" id="{{ formId }}-'.$elementData['element_name'].'_'.$optionKey.'"'.$selected.'>';
				  $elementHtml11 .= '<label for="{{ formId }}-'.$elementData['element_name'].'_'.$optionKey.'" class="checkbox">'.$optionKey.'</label>';
				  $elementHtml11 .= $newLine;
				}
	  
				$elementHtml = $elementHtml11;
				break;
	  
			  case config('constants.ELEMENT_TYPE_RADIO'):
	  
				$newLine = '';
						  
				if($elementData['check_radio_line_break'] == config('constants.ELEMENT_DISPLAY_OPTIONS_NEW_LINE'))
				  $newLine = '<br/>';
																	  
				$elementHtml11 = "";
				$options_exp = explode(",",$elementData['options']);
									
				$ii = 0;	

				foreach ($options_exp as $keyOption => $valueOption) {
																		  
				  $selected = "";
				  $optionExplode = explode('=>', $valueOption);
																		  
				  $optionKey = $optionExplode[0];
				  if(strpos($valueOption, '=>'))	
					$optionValue = $optionExplode[1];						  									  
				  else 			
					$optionValue = $optionExplode[0];
				
				  if(($elementData['default_value'] != "" && $elementData['default_value'] == $optionValue) || ($elementData['element_required'] == config('constants.ELEMENT_REQUIRED') && $elementData['default_value'] == "" && $ii == 0))
					$selected = "checked";
																											 
				  $elementHtml11 .= '<input type="radio" '.$class.' value="'.$optionValue.'" name="contact['.$elementData['element_name'].']" id="{{ formId }}-'.$elementData['element_name'].'_'.$optionKey.'"'.$selected.'>';
				  $elementHtml11 .= '<label for="{{ formId }}-'.$elementData['element_name'].'_'.$optionKey.'" class="radio">'.$optionKey.'</label>';
				  $elementHtml11 .= $newLine;

				  $ii++;
				}
																	  
				$elementHtml = $elementHtml11;
				break;
	  
			  case config('constants.ELEMENT_TYPE_FILE'):
				
				$fileTypes = "";
				$accept = "";
																  
				if($elementData['file_type'] != "") { 
							  
				  $fileTypeExplode = explode(",", $elementData['file_type']);
							  
				  if(count($fileTypeExplode) > 0) { 
	  
					for($i = 0; $i < count($fileTypeExplode); $i++) {
																			  
					  if($fileTypes != "")
						$fileTypes .= ",";  
	  
					  $fileTypes .= ".".$fileTypeExplode[$i];
					}
				  }                        
				  $accept = ' accept="'.$fileTypes.'"';
				}
																  
				$elementHtml = '<input type="file" '.$class.' id="{{ formId }}-'.$elementData['element_name'].'" '.$accept.' name="contact['.$elementData['element_name'].']"';
																  
				if($elementData['element_required'] == config('constants.ELEMENT_REQUIRED'))
				  $elementHtml .= ' required';
																	  
				$elementHtml .= '>';
				break;
			}
	  
			$formElementData = str_replace("{element_label}",$elementData['element_display_name'],$formTemplateData['form_element_html']);
			$formElementData = str_replace("{element_html}",$elementHtml,$formElementData);
			$formContent .= $formElementData;
			
		  }
		}
	  
		$submitClass = '';
	  
		if($submitButtonClass != "") 
		  $submitClass = ' class="'.$submitButtonClass.'"';
	   
		$formSubmitButton = '<input type="submit" value="'.$submitButtonText.'"'.$submitClass.' id="cfm_submit_'.$formId.'">';
		$formSubmit = str_replace("{submit_button_html}",$formSubmitButton,$formTemplateData['form_submit_html']);
		$formContent .= $formSubmit;
		$formContent .= '{% endform %}';
											
		$formCode = '<!--cfm-start-->';
		$formCode .= str_replace("{form_body}",$formContent,$formTemplateData['form_body_html']);  

		if(count($requiredCheckboxes) > 0) {
			$formCode .= '<script type="text/javascript">';
			$formCode .= 'document.addEventListener("DOMContentLoaded", function() {';
			$formCode .= 'const form = document.querySelector("form");';
			$formCode .= 'const submitButton = document.getElementById("cfm_submit_'.$formId.'");';
			$formCode .= 'submitButton.addEventListener("click", function(event) {';
			$formCode .= 'const checkboxGroups = '.json_encode($requiredCheckboxes).';';
			$formCode .= 'for (let i = 0; i < checkboxGroups.length; i++) {';
			$formCode .= 'const checkboxes = document.querySelectorAll("."+checkboxGroups[i]);';
			$formCode .= 'const isAnyChecked = Array.from(checkboxes).some(function(checkbox) {';
			$formCode .= 'return checkbox.checked;';
			$formCode .= '});';
			$formCode .= 'if (!isAnyChecked) {';
			$formCode .= 'event.preventDefault();';
			$formCode .= 'alert("Please select at least one option in "+checkboxGroups[i]);';
			$formCode .= 'return;';
			$formCode .= '}';
			$formCode .= '}';
			$formCode .= '});';
			$formCode .= '});';
			$formCode .= '</script>';		
		}
		$formCode .= '<!--cfm-end-->';
	  
		try {
	  
		  $codeUpdated = Form::where('id', $formId)->update(['code' => $formCode]);
	  
		  return response()->json(['status' => 1, 'message' => '', 'data' => $formCode]);
		  
		} catch (QueryException $ex) {
		  return response()->json(['status' => 0, 'message' => trans('formMessages.form_code_updation_failed'), 'data' => $data]);
		}
	  
	  }

}

?>
