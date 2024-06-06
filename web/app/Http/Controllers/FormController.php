<?php

namespace App\Http\Controllers;
use App\Models\Form;
use App\Models\FormTemplate;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;
use App\Models\FormInstallation;

class FormController extends Controller
{
  
    public function create(Request $request)
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

      try {
        $incomingFields = $request->validate([
          'name' => ['required', 'string'],
          'template_id' => ['required', 'integer']
        ]);

      } catch (\Exception $e) { // Handle exceptions or validation errors here
          
        $errors = $e->validator->errors()->messages();
        return response()->json(['status' => 0, 'message' => trans('commonMessages.input_validation_failed'), 'data' => $data, 'errors' => $errors]);
      } 
          
      try {
        // Attempt to retrieve the FormTemplate
        $formTemplate = FormTemplate::where('id', $incomingFields['template_id'])->first();

        if(!$formTemplate){
          return response()->json(['status' => 0, 'message' => trans('templateMessages.template_not_found'), 'data' => $data, 'errors' => $errors]);
        }

        // Access properties of $formTemplate if needed
        $formBodyHtml = $formTemplate->form_body_html;
        $formElementHtml = $formTemplate->form_element_html;
        $formSubmitHtml = $formTemplate->form_submit_html;

        } catch (QueryException $ex) {
          return response()->json(['status' => 0, 'message' => trans('templateMessages.template_fetch_failed'), 'data' => $data, 'errors' => $errors]);
        }          

        $fields[] = array(
            'element_display_name'	=>	'Name',
            'element_name'	=>	'Name',
            'element_type'	=>	config('constants.ELEMENT_TYPE_TEXTFIELD'),
            'element_required'	=>	config('constants.ELEMENT_REQUIRED'),
            'css_class'	=>	config('constants.ELEMENT_DEFAULT_CLASS_TEXTFIELD'),
            'max_length'	=>	'',
            'default_value'	=>	''
        );
            
        $fields[] = array(
            'element_display_name'	=>	'Email',
            'element_name'	=>	'email',
            'element_type'	=>	config('constants.ELEMENT_TYPE_EMAIL'),
            'element_required'	=>	config('constants.ELEMENT_REQUIRED'),
            'css_class'	=>	config('constants.ELEMENT_DEFAULT_CLASS_EMAIL'),
            'max_length'	=>	'',
            'default_value'	=>	''
        );
        
        $fields[] = array(
            'element_display_name'	=>	'Subject',
            'element_name'	=>	'Subject',
            'element_type'	=>	config('constants.ELEMENT_TYPE_TEXTFIELD'),
            'element_required'	=>	config('constants.ELEMENT_REQUIRED'),
            'css_class'	=>	config('constants.ELEMENT_DEFAULT_CLASS_TEXTFIELD'),
            'max_length'	=>	'',
            'default_value'	=>	''
        );
      
        $fields[] = array(
            'element_display_name'	=>	'Message',
            'element_name'	=>	'Message',
            'element_type'	=>	config('constants.ELEMENT_TYPE_TEXTAREA'),
            'element_required'	=>	config('constants.ELEMENT_REQUIRED'),
            'css_class'	=>	config('constants.ELEMENT_DEFAULT_CLASS_TEXTAREA'),
            'rows'	=>	config('constants.ELEMENT_TEXTAREA_DEFAULT_ROWS'),
            'default_value'	=>	''
        );
      
        $incomingFields['shop_id'] = $shop_id;
        $incomingFields['fields'] = json_encode($fields);
        $incomingFields['status'] = config('constants.FORM_STATUS_ACTIVE');
        $incomingFields['code'] = '';
        $incomingFields['after_submission'] = config('constants.AFTER_FORM_SUBMISSION_SHOW_MSG');
        $incomingFields['redirect_url'] = "";
        $incomingFields['thanks_message'] = 'Your message has been sent. Thank you for contacting us!';
        $incomingFields['submit_button_text'] = 'Submit';
        $incomingFields['submit_button_class'] = 'submit_button';
        
        try {
          $form = Form::create($incomingFields);

          if (!$form->id) 
            return response()->json(['status' => 0, 'message' => trans('formMessages.form_creation_failed'), 'data' => $data, 'errors' => $errors]);

          $data->id = $form->id;
          
          ///////Update form liquid code column/////////////////
          $updateFormCodeResult = updateFormCode($form->id);
          $jsonResponse = json_decode($updateFormCodeResult->getContent(), true);

          if (isset($jsonResponse['status']) && $jsonResponse['status'] === 0) 
            return $updateFormCodeResult;

          $data->formCode = $jsonResponse['data'];
          /////////////////////////////////////////////////
          

          return response()->json(['status' => $status, 'message' => trans('formMessages.form_creation_success'), 'data' => $data, 'errors' => $errors]);
            
        } catch (QueryException $ex) {
          return response()->json(['status' => 0, 'message' => trans('formMessages.form_creation_failed'), 'data' => $data, 'errors' => $errors]);
        }     

    }
    
    public function update(Request $request, $formId)
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
    
      $validator = Validator::make(['formId' => $formId], [
        'formId' => 'required|integer',
      ]);

      if ($validator->fails()) {
        return response()->json(['status' => 0, 'message' =>$validator->errors()->getMessageBag()->get('formId')[0], 'data' => $data, 'errors' => $errors ]);
      }

      try {

        $rules = [
          'submit_button_text' => ['required', 'string'],
          'submit_button_class' => ['required', 'string'],
          'after_submission' => ['required', 'integer', 'in:'. config('constants.AFTER_FORM_SUBMISSION_SHOW_MSG') . ',' . config('constants.AFTER_FORM_SUBMISSION_REDIRECT_TO_PAGE')],
        ];
      
        if ($request->input('after_submission') == config('constants.AFTER_FORM_SUBMISSION_SHOW_MSG')) {
          $rules['thanks_message'] = ['required', 'string'];
        }
        if ($request->input('after_submission') == config('constants.AFTER_FORM_SUBMISSION_REDIRECT_TO_PAGE')) {
          $rules['redirect_url'] = ['required', 'url'];
        }
      
        $incomingFields = $request->validate($rules);
        
      } catch (\Exception $e) { // Handle exceptions or validation errors here

        $errors = $e->validator->errors()->messages();
        return response()->json(['status' => 0, 'message' => trans('commonMessages.input_validation_failed'), 'data' => $data, 'errors' => $errors]);
      }

      try {

        $query = 'formFetch';
        $formData = Form::select('name','status','fields', 'template_id','after_submission','redirect_url','thanks_message','submit_button_text','submit_button_class')
        ->where('id', $formId)
        ->where('shop_id', $shop_id)
        ->first();

        if(!$formData){
          $response = response()->json(['status' => 0, 'message' => trans('formMessages.form_not_found'), 'data' => $data, 'errors' => $errors]);
          return $response;
        }

       if ($request->input('after_submission') == config('constants.AFTER_FORM_SUBMISSION_SHOW_MSG')) 
            $incomingFields['redirect_url'] = '';
          $query = 'formUpdate';
          $formUpdated = Form::where('id', $formId)->update($incomingFields);
        
            
        ///////Update form liquid code column/////////////////
        $updateFormCodeResult = updateFormCode($formId);
        $jsonResponse = json_decode($updateFormCodeResult->getContent(), true);

        if (isset($jsonResponse['status']) && $jsonResponse['status'] === 0) 
          return $updateFormCodeResult;

        $data->formCode = $jsonResponse['data'];
        /////////////////////////////////////////////////

        return response()->json(['status' => 1, 'message' => trans('formMessages.form_updation_success'), 'data' => $data, 'errors' => $errors]);  

      } catch (QueryException $ex) {
        $message = ($query == 'formFetch') ? trans('formMessages.form_fetch_failed') : trans('formMessages.form_updation_failed');
        return response()->json(['status' => 0, 'message' => $message ,'data' => $data,  'errors' => $errors]);
      }

    }

 public function view(Request $request,$formId){
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

        
         if($validator->fails()) {
          $response = response()->json(['status' => 0, 'message' =>$validator->errors()->getMessageBag()->get('formId')[0],'data' => $data ]);
          return $response;
        }
        try {
          $query = 'form';
          $formData = Form::select('name', 'status', 'fields', 'template_id','after_submission','redirect_url','thanks_message','submit_button_text','submit_button_class','code')
              ->where('id', $formId)
              ->where('shop_id', $shop_id)
              ->first();
  
          if (!$formData) {
              $response = response()->json(['status' => 0, 'message' => trans('formMessages.form_not_found'), 'data' => $data]);
              return $response;
          }
  
          $templateId = $formData->template_id;
          
          $query = 'template';
          $formTemplateData = FormTemplate::select('layout', 'form_body_html', 'form_element_html', 'form_submit_html')
              ->where('id', $templateId)
              ->first();
  
          if (!$formTemplateData) {
              $response = response()->json(['status' => 0, 'message' => trans('formMessages.template_not_found'), 'data' => $data]);
              return $response;
          }
  
       $data->formData = $formData;
          $data->formTepmlateData = $formTemplateData;
         

      } catch (QueryException $ex) {
       
      $message = ($query == 'form') ? trans('formMessages.form_fetch_failed') : trans('formMessages.template_fetch_failed');
      $response = response()->json(['status' => 0,'message' => $message, 'data' =>$data]);
    return   $response;
      }
   $response = response()->json(['status' => $status,'message' => $message, 'data' =>$data]);
    return   $response;

  }
 

  public function delete(Request $request,$formId){
    $status = 1;
    $message = '';

    $session = $request->get('shopifySession');
    $shopResponse = getShopIdFromDomain($session->getShop());
    $shopResponseData = $shopResponse->getData();

    if($shopResponseData->status == 0) 
      return response()->json(['status' => 0, 'message' => $shopResponseData->message ]);

    $shop_id = $shopResponseData->data;


    $validator = Validator::make(['formId' => $formId], [
      'formId' => 'required|integer',
  ]);

 
  if ($validator->fails()) {
    $response = response()->json(['status' => 0,  'message' =>$validator->errors()->getMessageBag()->get('formId')[0]]);
      return  $response;
  }
   try{
    $query = "form";
    $formData = Form::where('id', $formId)
    ->where('shop_id', $shop_id)
    ->first();

if (!$formData) {
    $response = response()->json(['status' => 0, 'message' => trans('formMessages.form_not_found')]);
    return $response;
}

  $query = "installation";
  $formInstallationData = FormInstallation::where('form_id', $formId)
      ->get();

  if ($formInstallationData->isNotEmpty()) {
      $response = response()->json(['status' => 0, 'message' => trans('formMessages.installation_exist')]);
      return $response;
  }

  $query = "deletion";
  $deletedForm = Form::where('id', $formId)
      ->where('shop_id', $shop_id)
      ->delete();

  if ($deletedForm === 0) {
      $response = response()->json(['status' => 0, 'message' => trans('formMessages.deletion_failed')]);
      return $response;
  }
} catch (QueryException $ex) {
  switch ($query) {
      case 'installation':
          $message = trans('formMessages.installation_fetch_failed');
          break;
      case 'deletion':
          $message = trans('formMessages.deletion_failed');
          break;
     case 'form':
          $message = trans('formMessages.form_fetch_failed');
          break;
  }

  $response = response()->json(['status' => 0, 'message' => $message]);
  return $response;
}
      $response = response()->json(['status' => $status, 'message' => trans('formMessages.deleted') ]);
      return  $response;
}


public function list(Request $request){
  $status = 1;
  $message = '';
  $data = new \stdClass(); 

  $session = $request->get('shopifySession');
  $shopResponse = getShopIdFromDomain($session->getShop());
  $shopResponseData = $shopResponse->getData();

  if($shopResponseData->status == 0) 
    return response()->json(['status' => 0, 'message' => $shopResponseData->message, 'data' => $data ]);

  $shop_id = $shopResponseData->data;
  

 try{
 
  $forms = Form::join('form_templates', 'forms.template_id', '=', 'form_templates.id')
    ->selectRaw('forms.id, forms.name, forms.template_id, forms.created_at, forms.updated_at, forms.status, form_templates.name as template_name, DATE_FORMAT(forms.created_at, "%Y-%m-%d") as formatted_created_at, DATE_FORMAT(forms.updated_at, "%Y-%m-%d") as formatted_updated_at')
    ->where('forms.shop_id', $shop_id)
    ->orderBy('forms.id', 'desc')
    ->get();
      
    if ($forms->isEmpty()) {
      $response = response()->json(['status' => 0, 'message' => trans('formMessages.form_not_found'), 'data' => $data]);
      return $response;
  }
  
}
catch(QueryException $ex){
    $message = trans('formMessages.list_fetch_failed');
    $response = response()->json(['status' => 0, 'message' => $message ,'data' => $data]);
    return  $response;
   }
   $response = response()->json(['status' => $status, 'message' => $message,'data' => $forms]);
   return  $response;
}
public function templateList(Request $request){
    $status = 1;
    $message = '';
    $data = new \stdClass(); 
  
   try{
    $formTemplateData = FormTemplate::select('id', 'name', 'img','img_url')->get();
                      
    
      if($formTemplateData->isEmpty()) {
            $response = response()->json(['status' => 0, 'message' => trans('formMessages.template_not_found'), 'data' => $data]);
            return $response;
        }
       
    }
     catch(QueryException $ex){
      $response = response()->json(['status' => 0, 'message' => trans('formMessages.template_list_fetch_failed') ]);
      return  $response;
     }
     $response = response()->json(['status' => $status, 'message' => $message,'data' => $formTemplateData]);
     return  $response;
  }
}
