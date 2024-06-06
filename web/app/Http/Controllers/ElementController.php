<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Models\FormTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;
use App\Models\FormInstallation;
use Illuminate\Validation\ValidationException;

class ElementController extends Controller
{

    public function update(Request $request)
    {

      $status = 1;
      $message = ''; 
      $data = new \stdClass();    
      $errors = new \stdClass(); 

      $session = $request->get('shopifySession');
      $shopResponse = getShopIdFromDomain($session->getShop());
      $shopResponseData = $shopResponse->getData();

      if($shopResponseData->status == 0) 
        return response()->json(['status' => 0, 'message' => $shopResponseData->message, 'data' => $data, 'errors' => $errors ]);

      $shop_id = $shopResponseData->data;

      $formId = $request->input('formId');

      try {

        $formData = Form::select('name','status','fields', 'template_id')
        ->where('id', $formId)
        ->where('shop_id', $shop_id)
        ->first();
    
        if(!$formData)
          return response()->json(['status' => 0, 'message' => trans('formMessages.form_not_found'), 'data' => $data, 'errors' => $errors]);
      
      } catch (QueryException $ex) {
        return response()->json(['status' => 0, 'message' => trans('formMessages.form_fetch_failed'), 'data' => $data, 'errors' => $errors]);
      }

      $validateResult = validateElement($request, 'update', $formData);
      // Decode the JSON response
      $jsonResponse = json_decode($validateResult->getContent(), true);

      // Check the status
      if (isset($jsonResponse['status']) && $jsonResponse['status'] === 0) 
        return $validateResult;
      
      $elementType = $request->input('elementType');
      $elementName = $request->input('elementName');
      $elementOldName = $request->input('elementOldName');

      $fields=array();
      $fieldsExist = $formData['fields'];

      if($fieldsExist != "") {
        $fieldsData = json_decode($fieldsExist,true);

        foreach($fieldsData as $fdata) {
          if(strtolower($fdata['element_name']) == strtolower($elementOldName)) {

              $fields[] = createFields($request);                         
          }
          else {
              $fields[]=$fdata;
          }              
        }
          
        try {

          $fieldsJson = json_encode($fields);
          $elementUpdated = Form::where('id', $formId)->update(['fields' => $fieldsJson]);
          

          ///////Update form liquid code column/////////////////
          $updateFormCodeResult = updateFormCode($formId);
          $updateFormCodeResponse = json_decode($updateFormCodeResult->getContent(), true);

          if (isset($updateFormCodeResponse['status']) && $updateFormCodeResponse['status'] === 0) 
            return $updateFormCodeResult;
          
          /////////////////////////////////////////////////

          $data->fieldsJson = $fieldsJson;
          $data->formCode = $updateFormCodeResponse['data'];

          return response()->json(['status' => 1, 'message' => trans('elementMessages.element_updation_success'), 'data' => $data, 'errors' => $errors]);
          
        } catch (QueryException $ex) {
          return response()->json(['status' => 0, 'message' => trans('elementMessages.element_updation_failed'), 'data' => $data, 'errors' => $errors]);
        }
      }  
      
    }  

    public function add(Request $request)
    {

      $status = 1;
      $message = '';   
      $data = new \stdClass(); 
      $errors = new \stdClass(); 

      $session = $request->get('shopifySession');
      $shopResponse = getShopIdFromDomain($session->getShop());
      $shopResponseData = $shopResponse->getData();

      if($shopResponseData->status == 0) 
        return response()->json(['status' => 0, 'message' => $shopResponseData->message, 'data' => $data, 'errors' => $errors ]);

      $shop_id = $shopResponseData->data;
   
      $formId = $request->input('formId');

      try {

        $formData = Form::select('name','status','fields', 'template_id')
        ->where('id', $formId)
        ->where('shop_id', $shop_id)
        ->first();
    
        if(!$formData)
          return response()->json(['status' => 0, 'message' => trans('formMessages.form_not_found'), 'data' => $data, 'errors' => $errors]);
      
      } catch (QueryException $ex) {
        return response()->json(['status' => 0, 'message' => trans('formMessages.form_fetch_failed'), 'data' => $data, 'errors' => $errors]);
      }

      $validateResult = validateElement($request, 'add', $formData);
      // Decode the JSON response
      $jsonResponse = json_decode($validateResult->getContent(), true);

      // Check the status
      if (isset($jsonResponse['status']) && $jsonResponse['status'] === 0) 
        return $validateResult;
      
      $elementType = $request->input('elementType');
      $elementName = $request->input('elementName');

      $fieldsExist = $formData['fields'];

      if($fieldsExist!="") {
        $fields = json_decode($fieldsExist,true);
      }

      $fields[] = createFields($request);  
        
      try {

        $fieldsJson = json_encode($fields);
        $elementUpdated = Form::where('id', $formId)->update(['fields' => $fieldsJson]);

        ///////Update form liquid code column/////////////////
        $updateFormCodeResult = updateFormCode($formId);
        $updateFormCodeResponse = json_decode($updateFormCodeResult->getContent(), true);

        if (isset($updateFormCodeResponse['status']) && $updateFormCodeResponse['status'] === 0) 
          return $updateFormCodeResult;
        /////////////////////////////////////////////////

        if($elementUpdated > 0) {

          $data->fieldsJson = $fieldsJson;
          $data->formCode = $updateFormCodeResponse['data'];
          
          return response()->json(['status' => 1, 'message' => trans('elementMessages.element_add_success'), 'data' => $data, 'errors' => $errors]);
        }
        else 
          return response()->json(['status' => 0, 'message' => trans('elementMessages.element_add_failed'), 'data' => $data, 'errors' => $errors]);

      } catch (QueryException $ex) {
        return response()->json(['status' => 0, 'message' => trans('formMessages.form_update_failed'), 'data' => $data, 'errors' => $errors]);
      }
                  
    }

    public function view(Request $request,$formId,$elementName){
      $status = 1;
      $message = '';
      $data = new \stdClass(); 
    
      $session = $request->get('shopifySession');
      $shopResponse = getShopIdFromDomain($session->getShop());
      $shopResponseData = $shopResponse->getData();

      if($shopResponseData->status == 0) 
        return response()->json(['status' => 0, 'message' => $shopResponseData->message, 'data' => $data ]);

      $shop_id = $shopResponseData->data;

        $validator = Validator::make(['formId' => $formId], [
              'formId' => 'required|integer',
          ]);
  
          
          if ($validator->fails()) {
              $response =  response()->json(['status' => 0, 'message' =>$validator->errors()->getMessageBag()->get('formId')[0],'data' => $data ]);
              return  $response;
          }
  
   
  try{
  $form = Form::where('id', $formId)
           ->where('shop_id',  $shop_id)
           ->first();
           if (!$form) {
              $response =  response()->json(['status' => 0, 'message' => trans('elementMessages.form_not_found'), 'data' => $data ]);
              return  $response;
          }
          
  $fields = json_decode($form->fields, true);
  
  if (!$fields) {
      $response =  response()->json(['status' => 0, 'message' => trans('elementMessages.fields_not_found'), 'data' => $data ]);
      return  $response;
  }
  
  $field = collect($fields)->firstWhere('element_name', $elementName);
  
  if (!$field) {
      $response =  response()->json(['status' => 0, 'message' => trans('elementMessages.element_not_found'), 'data' => $data ]);
      return  $response;
  }
  }catch(QueryException $ex){
  $response =  response()->json(['status' => 0, 'message' => trans('elementMessages.form_fetch_failed'), 'data' => $data ]);
  return  $response;
  }
  $response = response()->json(['status' => $status, 'message' => $message, 'data' => $field]);
  return $response;
   } 
   
  public function delete(Request $request){
  
      $status = 1;
      $message = '';
      $error = new \stdClass(); 
      $data = new \stdClass(); 
        
      $session = $request->get('shopifySession');
      $shopResponse = getShopIdFromDomain($session->getShop());
      $shopResponseData = $shopResponse->getData();

      if($shopResponseData->status == 0) 
        return response()->json(['status' => 0, 'message' => $shopResponseData->message,'data'=>$data ]);

      $shop_id = $shopResponseData->data;
   
    try{
      $request->validate([
          'formId' => 'required|integer',
          'elementName' => 'required|string'
      ],

   );
  
      $formId = $request->input('formId');
      $elementName = $request->input('elementName');

   }
      catch( ValidationException $e)
      {
      
        return response()->json([ 'status' => 0,'message' =>  trans('elementMessages.invalid_input_parameters'),'data'=>$data],);
        
      }

      if($elementName == "email") {
        return response()->json([ 'status' => 0,'message' =>  trans('elementMessages.email_cant_delete'),'data'=>$data],);

      }

          try {
              $query = 'form';
              $form = Form::where('id', $formId)
                  ->where('shop_id', $shop_id)
                  ->first();
          
              if (!$form) {
                  $response = response()->json(['status' => 0, 'message' => trans('elementMessages.form_not_found'),'data'=>$data]);
                  return $response;
              }
          
              $fields = json_decode($form->fields, true);
          
              if (!$fields) {
                  $response = response()->json(['status' => 0, 'message' => trans('elementMessages.fields_not_found'),'data'=>$data]);
                  return $response;
              }
             
              $field = collect($fields)->firstWhere('element_name', $elementName);
          
              if (!$field) {
                  $response = response()->json(['status' => 0, 'message' => trans('elementMessages.element_not_found'),'data'=>$data]);
                  return $response;
              }
          
              $query = 'delete';
              $updatedFields = collect($fields)->reject(function ($field) use ($elementName) {
                  return $field['element_name'] === $elementName;
              });
            
              $form->fields = json_encode($updatedFields->values());
              $form->save();
              ///////Update form liquid code column/////////////////
          $updateFormCodeResult = updateFormCode($formId);
          
          $updateFormCodeResponse = json_decode($updateFormCodeResult->getContent(), true);

          if (isset($updateFormCodeResponse['status']) && $updateFormCodeResponse['status'] === 0) 
            return $updateFormCodeResult;
          
          /////////////////////////////////////////////////

          $data->fieldsJson = $form->fields;
          $data->formCode = $updateFormCodeResponse['data'];

          

          $response = response()->json(['status' => $status, 'message' => trans('elementMessages.deleted'),'data'=>$data]);
          return $response;

          } catch (QueryException $ex) {
             
              $message = ($query == 'delete') ? trans('elementMessages.deletion_failed') : trans('elementMessages.form_fetch_failed');
              $response = response()->json(['status' => 0, 'message' => $message,'data'=>$data]);
              return $response;
          }
          
     }

    public function sort(Request $request)
    {

      $status = 1;
      $message = '';   
      $data = new \stdClass();    
      $errors = new \stdClass(); 

      $session = $request->get('shopifySession');
      $shopResponse = getShopIdFromDomain($session->getShop());
      $shopResponseData = $shopResponse->getData();

      if($shopResponseData->status == 0) 
        return response()->json(['status' => 0, 'message' => $shopResponseData->message, 'data' => $data ]);

      $shop_id = $shopResponseData->data;

      

      $validator = Validator::make(['formId' => $request->input('formId')], [
        'formId' => 'required|integer',
      ]);

      if ($validator->fails()) {
        return response()->json(['status' => 0, 'message' =>$validator->errors()->getMessageBag()->get('formId')[0], 'data' => $data ]);
      }

      $validator = Validator::make(['sortedElements' => $request->input('sortedElements')], [
        'sortedElements' => 'required',
      ]);

      if ($validator->fails()) {
        return response()->json(['status' => 0, 'message' =>$validator->errors()->getMessageBag()->get('sortedElements')[0], 'data' => $data ]);
      }

      $formId = $request->input('formId');
      $fields = $request->input('sortedElements');

      try {

        $formData = Form::select('fields')
        ->where('id', $formId)
        ->where('shop_id', $shop_id)
        ->first();
    
        if(!$formData)
          return response()->json(['status' => 0, 'message' => trans('formMessages.form_not_found'), 'data' => $data]);
      
      } catch (QueryException $ex) {
        return response()->json(['status' => 0, 'message' => trans('formMessages.form_fetch_failed'), 'data' => $data]);
      }

      try {

        $fieldsJson = json_encode($fields);
        $elementUpdated = Form::where('id', $formId)->update(['fields' => $fields]);

        ///////Update form liquid code column/////////////////
        $updateFormCodeResult = updateFormCode($formId);
        $updateFormCodeResponse = json_decode($updateFormCodeResult->getContent(), true);

        if (isset($updateFormCodeResponse['status']) && $updateFormCodeResponse['status'] === 0) 
          return $updateFormCodeResult;
        /////////////////////////////////////////////////

        $data->formCode = $updateFormCodeResponse['data'];
        return response()->json(['status' => 1, 'message' => $message, 'data' => $data]);
        
      } catch (QueryException $ex) {
        return response()->json(['status' => 0, 'message' => trans('elementMessages.element_sort_failed'), 'data' => $data]);
      }


    }
     
}

function validateElement(Request $request, $action, $formData) {

  $status = 1;
  $message = '';   
  $data = new \stdClass();  
  $errors = new \stdClass(); 

  $formId = $request->input('formId');
  
  $validator = Validator::make(
    ['formId' => $formId], ['formId' => 'required|integer']
  );

  if ($validator->fails()) {
    return response()->json(['status' => 0, 'message' => $validator->errors()->getMessageBag()->get('formId')[0], 'data' => $data, 'errors' => $errors ]);
  }

  if($action == "update") {

    $elementOldName = $request->input('elementOldName');
    $elementName = $request->input('elementName');

    $validator = Validator::make(
      ['elementOldName' => $elementOldName], ['elementOldName' => 'required']
    );
  
    if ($validator->fails()) {
      return response()->json(['status' => 0, 'message' => $validator->errors()->getMessageBag()->get('elementOldName')[0], 'data' => $data, 'errors' => $errors ]);
    }

    if($elementOldName == "email" && $elementName != $elementOldName) {
      return response()->json(['status' => 0, 'message' => trans('elementMessages.email_cant_change'), 'data' => $data, 'errors' => $errors]);        	
    }
  }

  try {

      $rules = [
        'elementName' => ['required', 'regex:/^[a-zA-Z][a-zA-Z0-9]*$/'],
        'elementDisplayName' => ['required'],
        'elementType' => ['required', 'integer', 'in:' . 
        config('constants.ELEMENT_TYPE_TEXTFIELD') . ',' . config('constants.ELEMENT_TYPE_EMAIL'). ',' .
        config('constants.ELEMENT_TYPE_TEXTAREA') . ',' . config('constants.ELEMENT_TYPE_DROPDOWN'). ',' .
        config('constants.ELEMENT_TYPE_DATE') . ',' . config('constants.ELEMENT_TYPE_CHECKBOX'). ',' .
        config('constants.ELEMENT_TYPE_RADIO') . ',' . config('constants.ELEMENT_TYPE_FILE')],
        'required' => ['integer', 'in:' . config('constants.ELEMENT_REQUIRED'). ',' . config('constants.ELEMENT_NOT_REQUIRED')]
      ];

      $ruleMessages = [
        'elementName.regex' => 'The elementName must start with an alphabet and be alphanumeric.',
      ];

      if ($request->input('elementType') == config('constants.ELEMENT_TYPE_TEXTFIELD') ||
          $request->input('elementType') == config('constants.ELEMENT_TYPE_EMAIL') 
      ) {
        $rules['maxLength'] = ['integer', 'nullable'];
      }

      if ($request->input('elementType') == config('constants.ELEMENT_TYPE_DROPDOWN') ||
          $request->input('elementType') == config('constants.ELEMENT_TYPE_CHECKBOX') ||
          $request->input('elementType') == config('constants.ELEMENT_TYPE_RADIO')
      ) {
        $rules['options'] = ['required', 'string'];
      }

      if ($request->input('elementType') == config('constants.ELEMENT_TYPE_DROPDOWN')) {
        $rules['multipleSelect'] = ['integer', 'nullable', 'in:' . config('constants.ELEMENT_ALLOW_MULTISELECT_DROPDOWN'). ',' . config('constants.ELEMENT_NOT_ALLOW_MULTISELECT_DROPDOWN')];
      }

      if ($request->input('elementType') == config('constants.ELEMENT_TYPE_CHECKBOX') ||
          $request->input('elementType') == config('constants.ELEMENT_TYPE_RADIO')
      ) {
        $rules['singleLineView'] = ['integer', 'nullable', 'in:' . config('constants.ELEMENT_DISPLAY_OPTIONS_SINGLE_LINE'). ',' . config('constants.ELEMENT_DISPLAY_OPTIONS_NEW_LINE')];
      }

      if ($request->input('elementType') == config('constants.ELEMENT_TYPE_TEXTAREA') ) {
        $rules['rowLength'] = ['integer', 'nullable'];
      }

      if ($request->input('elementType') == config('constants.ELEMENT_TYPE_FILE') ) {
        $rules['fileType'] = ['string', 'nullable'];
      }
      
      $incomingFields = $request->validate($rules, $ruleMessages);

      $elementType = $request->input('elementType');
      $elementName = $request->input('elementName');

      $fields=array();

      $fieldsExist = $formData->fields;
      if($fieldsExist != "") {
        $fieldsData = json_decode($fieldsExist,true);

        if($action == "update") {
          $elementExists = checkElementExists($fieldsData, $elementOldName);
          if (!$elementExists) {
            return response()->json(['status' => 0, 'message' => trans('elementMessages.element_not_found'), 'data' => $data, 'errors' => $errors]);        	
          }
        }

        foreach($fieldsData as $fdata) {
          if($action == "update") {
            if(strtolower($fdata['element_name']) == strtolower($elementName) && $elementOldName != $elementName) {
              return response()->json(['status' => 0, 'message' => trans('elementMessages.element_name_already_exist'), 'data' => $data, 'errors' => $errors]);        	
            }
          }
          else {
            if(strtolower($fdata['element_name']) == strtolower($elementName)) {
              return response()->json(['status' => 0, 'message' => trans('elementMessages.element_name_already_exist'), 'data' => $data, 'errors' => $errors]);        	
            }
          }
        }
      }

      return response()->json(['status' => 1, 'message' => '', 'data' => $data, 'errors' => $errors]);        	

  } catch (\Exception $e) { // Handle exceptions or validation errors here
          
    $errors = $e->validator->errors()->messages();
    return response()->json(['status' => 0, 'message' => trans('commonMessages.input_validation_failed'), 'data' => $data, 'errors' => $errors]);
  }
    
}

function checkElementExists($array, $elementName) {
  foreach ($array as $element) {
      // Check if the current element is an array and has the key 'element_name'
      if (is_array($element) && array_key_exists('element_name', $element)) {
          // If the 'element_name' matches the one we're looking for, return true
          if ($element['element_name'] == $elementName) {
              return true;
          }
      }
  }
  // If the element name is not found, return false
  return false;
}


function createFields(Request $request) {

  $elementType = $request->input('elementType');
  $elementName = $request->input('elementName');

  switch ($elementType) {
      
    case config('constants.ELEMENT_TYPE_TEXTFIELD'):
      $required = $request->filled('required') ? $request->input('required') : config('constants.ELEMENT_NOT_REQUIRED');
      $elementDisplayName=$request->input('elementDisplayName');
      $className = $request->filled('className') ? $request->input('className') : config('constants.ELEMENT_DEFAULT_CLASS_TEXTFIELD');
      $maxlength = $request->input('maxLength');

      if($maxlength == 0) {
          $maxlength = '';
      }

      $defaultValue = $request->filled('defaultValue') ? $request->input('defaultValue') : '';

      $fields = array(
        'element_display_name'	=>	$elementDisplayName,
        'element_name'	=>	$elementName,
        'element_type'	=>	$elementType,
        'element_required'	=>	$required,
        'css_class'	=>	$className,
        'max_length'	=>	$maxlength,
        'default_value'	=>	$defaultValue
      );
      break;
    case config('constants.ELEMENT_TYPE_EMAIL'):
      $required = $request->filled('required') ? $request->input('required') : config('constants.ELEMENT_NOT_REQUIRED');
      $elementDisplayName=$request->input('elementDisplayName');
      $className = $request->filled('className') ? $request->input('className') : config('constants.ELEMENT_DEFAULT_CLASS_EMAIL');
      $maxlength = $request->input('maxLength');

      if($maxlength == 0) {
        $maxlength = '';
      }

      $defaultValue = $request->filled('defaultValue') ? $request->input('defaultValue') : '';

      $fields = array(
        'element_display_name'	=>	$elementDisplayName,
        'element_name'	=>	$elementName,
        'element_type'	=>	$elementType,
        'element_required'	=>	$required,
        'css_class'	=>	$className,
        'max_length'	=>	$maxlength,
        'default_value'	=>	$defaultValue
      );
      break;
    case config('constants.ELEMENT_TYPE_TEXTAREA'):
      $required = $request->filled('required') ? $request->input('required') : config('constants.ELEMENT_NOT_REQUIRED');
      $elementDisplayName=$request->input('elementDisplayName');
      $className = $request->filled('className') ? $request->input('className') : config('constants.ELEMENT_DEFAULT_CLASS_TEXTAREA');
      
      $rowLength = $request->input('rowLength');
      if($rowLength == 0){
        $rowLength = '';
    }

      $defaultValue = $request->filled('defaultValue') ? $request->input('defaultValue') : '';

      $fields = array(
        'element_display_name'	=>	$elementDisplayName,
        'element_name'	=>	$elementName,
        'element_type'	=>	$elementType,
        'element_required'	=>	$required,
        'css_class'	=>	$className,
        'rows'	=>	$rowLength,
        'default_value'	=>	$defaultValue
      );
      break;
    case config('constants.ELEMENT_TYPE_DROPDOWN'):
      $required = $request->filled('required') ? $request->input('required') : config('constants.ELEMENT_NOT_REQUIRED');
      $elementDisplayName=$request->input('elementDisplayName');
      $className = $request->filled('className') ? $request->input('className') : config('constants.ELEMENT_DEFAULT_CLASS_DROPDOWN');
      $options = trimElementOptions($request->input('options'));
      $multipleSelect = $request->filled('multipleSelect') ? $request->input('multipleSelect') : config('constants.ELEMENT_NOT_ALLOW_MULTISELECT_DROPDOWN');

      $defaultValue = $request->filled('defaultValue') ? trimElementOptions($request->input('defaultValue')) : '';

      $fields = array(
        'element_display_name'	=>	$elementDisplayName,
        'element_name'	=>	$elementName,
        'element_type'	=>	$elementType,
        'element_required'	=>	$required,
        'css_class'	=>	$className,
        'options'	=>	$options,
        'default_value'	=>	$defaultValue,
        'multi_select_drop_down'	=>	$multipleSelect
      );      
      break;
    case config('constants.ELEMENT_TYPE_DATE'):
      $required = $request->filled('required') ? $request->input('required') : config('constants.ELEMENT_NOT_REQUIRED');
      $elementDisplayName=$request->input('elementDisplayName');
      $className = $request->filled('className') ? $request->input('className') : config('constants.ELEMENT_DEFAULT_CLASS_DATE');
      
      $fields = array(
        'element_display_name'	=>	$elementDisplayName,
        'element_name'	=>	$elementName,
        'element_type'	=>	$elementType,
        'element_required'	=>	$required,
        'css_class'	=>	$className
      );
      break;
    case config('constants.ELEMENT_TYPE_CHECKBOX'):
      $required = $request->filled('required') ? $request->input('required') : config('constants.ELEMENT_NOT_REQUIRED');
      $elementDisplayName=$request->input('elementDisplayName');
      $className = $request->filled('className') ? $request->input('className') : '';
      $options = trimElementOptions($request->input('options'));
      $singleLineView = $request->filled('singleLineView') ? $request->input('singleLineView') : config('constants.ELEMENT_DISPLAY_OPTIONS_SINGLE_LINE');
      $defaultValue = $request->filled('defaultValue') ? trimElementOptions($request->input('defaultValue')) : '';

      $fields = array(
        'element_display_name'	=>	$elementDisplayName,
        'element_name'	=>	$elementName,
        'element_type'	=>	$elementType,
        'element_required'	=>	$required,
        'css_class'	=>	$className,
        'options'	=>	$options,
        'check_radio_line_break'	=>	$singleLineView,
        'default_value'	=>	$defaultValue
      );
      break;
    case config('constants.ELEMENT_TYPE_RADIO'):
      $required = $request->filled('required') ? $request->input('required') : config('constants.ELEMENT_NOT_REQUIRED');
      $elementDisplayName=$request->input('elementDisplayName');
      $className = $request->filled('className') ? $request->input('className') : '';
      $options = trimElementOptions($request->input('options'));
      $singleLineView = $request->filled('singleLineView') ? $request->input('singleLineView') : config('constants.ELEMENT_DISPLAY_OPTIONS_SINGLE_LINE');
      $defaultValue = $request->filled('defaultValue') ? trimElementOptions($request->input('defaultValue')) : '';

      $fields = array(
        'element_display_name'	=>	$elementDisplayName,
        'element_name'	=>	$elementName,
        'element_type'	=>	$elementType,
        'element_required'	=>	$required,
        'css_class'	=>	$className,
        'options'	=>	$options,
        'check_radio_line_break'	=>	$singleLineView,
        'default_value'	=>	$defaultValue
      );
      break;
    case config('constants.ELEMENT_TYPE_FILE'):
      $required = $request->filled('required') ? $request->input('required') : config('constants.ELEMENT_NOT_REQUIRED');
      $elementDisplayName = $request->input('elementDisplayName');
      $className = $request->filled('className') ? $request->input('className') : config('constants.ELEMENT_DEFAULT_CLASS_FILE');
      
      $fileType = $request->input('fileType');

      $fields = array(
        'element_display_name'	=>	$elementDisplayName,
        'element_name'	=>	$elementName,
        'element_type'	=>	$elementType,
        'element_required'	=>	$required,
        'css_class'	=>	$className,
        'file_type'	=>	$fileType
      );
      break;       
  }

  return $fields;
}
