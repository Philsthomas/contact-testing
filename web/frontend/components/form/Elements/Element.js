import { ELEMENT_TYPES } from "../../../Constants";
import { handleResponseErrors } from "../../../common";

export const alphanumeric = (errorMessage) => (value) => {
  if (!/^[a-zA-Z][a-zA-Z0-9]*$/.test(value)) {
    return errorMessage;
  }
};

export function CloseElement(elementDetails, setEditElement, setElement) {
  (elementDetails && Object.keys(elementDetails).length !== 0) ?
    setEditElement(ELEMENT_TYPES.EMPTY)
    :
    setElement(ELEMENT_TYPES.EMPTY)
}

export async function FormSubmit(csrf, fetch, formData, setIsLoading, elementDetails, setToastProps, t, setFields, fields, setCode) {
  try {
    const csrfToken = await csrf();

    formData._token = csrfToken;
    const requestOptions = {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken  // Include as header
      },
      body: JSON.stringify(formData)
    };

    setIsLoading(true);

    let response = '';
    if (elementDetails && Object.keys(elementDetails).length !== 0)
      response = await fetch("/api/element-update", requestOptions);
    else
      response = await fetch("/api/element-add", requestOptions);

    const data = await response.json();
    const message = data.message;

    if (response.ok) {
      setIsLoading(false);

      if (data.status == 1) {
        setToastProps({ content: message })
        setFields(JSON.parse(data.data.fieldsJson));
        setCode(data.data.formCode);
        return { status: 'success' };
      }
      else {
        if (Object.keys(data.errors).length === 0) {
          setToastProps({ content: message, error: true, });
          return { status: 'fail', errors: [{ message: message }] };
        }
        else {
          Object.keys(data.errors).forEach(key => {
            data.errors[key].forEach(errorMessage => {
              if (fields.hasOwnProperty(key)) {
                fields[key].setError(errorMessage);
              }
            });
          })
          return { status: 'fail', errors: [{ message: t('Exception.fieldErrors') }] };
        }

      }

    }
    else {
      await handleResponseErrors(message, setToastProps, setIsLoading);
      return { status: 'fail', errors: [{ message: message }] };
    }

  }
  catch (error) {
    await handleResponseErrors(t('Exception.unableToProccessRequest', { error: error }), setToastProps, setIsLoading);
    return { status: 'fail', errors: [{ message: error.message }] };
  }
}

export async function ElementDelete(csrf, fetch, formData, setIsLoading, setToastProps, t, setFields, setCode) {
  try {
    const csrfToken = await csrf();

    formData._token = csrfToken;
    const requestOptions = {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken  // Include as header
      },
      body: JSON.stringify(formData)
    };

    setIsLoading(true);

    let response = await fetch("/api/element-delete", requestOptions)
    const data = await response.json();
    const message = data.message;

    if (response.ok) {
      setIsLoading(false);
      setToastProps({ content: message })
      if (data.status == 1) {
        setFields(JSON.parse(data.data.fieldsJson));
        setCode(data.data.formCode);
        return { status: 'success' };
      }
      else
        return { status: 'fail', errors: [{ message: message }] };
    }
    else {
      await handleResponseErrors(message, setToastProps, setIsLoading);
      return { status: 'fail', errors: [{ message: message }] };
    }
  }
  catch (error) {
    await handleResponseErrors(t('Exception.unableToProccessRequest', { error: error }), setToastProps, setIsLoading);
    return { status: 'fail', errors: [{ message: error.message }] };
  }
}

export async function ElementSort(csrf, fetch, formData, setIsLoading, setToastProps, t, setCode) {
  try {
    const csrfToken = await csrf();

    formData._token = csrfToken;
    const requestOptions = {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken  // Include as header
      },
      body: JSON.stringify(formData)
    };

    setIsLoading(true);

    let response = await fetch("/api/element-sort", requestOptions)
    const data = await response.json();
    const message = data.message;

    if (response.ok) {
      setIsLoading(false);
      setToastProps({ content: message })
      if (data.status == 1) {
        setCode(data.data.formCode);
        return { status: 'success' };
      }
      else
        return { status: 'fail', errors: [{ message: message }] };
    }
    else {
      await handleResponseErrors(message, setToastProps, setIsLoading);
      return { status: 'fail', errors: [{ message: message }] };
    }
  }
  catch (error) {
    await handleResponseErrors(t('Exception.unableToProccessRequest', { error: error }), setToastProps, setIsLoading);
    return { status: 'fail', errors: [{ message: error.message }] };
  }
}
