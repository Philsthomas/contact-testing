import { Form, FormLayout, TextField, Button, Frame, OptionList, Card, Page, Layout, Box, Grid, Text, Icon, Image } from '@shopify/polaris';
import { useState, useCallback, useEffect } from 'react';
import { useTranslation } from "react-i18next";
import { useAuthenticatedFetch } from "../hooks";
import { useCsrf } from '../hooks/useCsrf';
import { Toast, TitleBar, useNavigate } from "@shopify/app-bridge-react";
import { CircleSpinnerOverlay } from 'react-spinner-overlay';
import "../assets/style.css";
import { handleResponseErrors } from '../common';
import { useField, useSubmit, notEmpty } from '@shopify/react-form';
import { errorImage } from '../assets';

export default function AddForm() {
  const [isLoading, setIsLoading] = useState(false);
  const [templateId, setSelected] = useState(0);
  const [templateOptions, setTemplateOptions] = useState([]);
  const navigate = useNavigate();
  const { t } = useTranslation();
  const csrf = useCsrf();
  const fetch = useAuthenticatedFetch();
  const emptyToastProps = { content: null };
  const [toastProps, setToastProps] = useState(emptyToastProps);

  const name = useField({
    value: '',
    validates: [notEmpty(t('addForm.name_is_required')),]
  });

  const template_id = useField({
    value: templateId ? parseInt(templateId) : '',
    validates: [notEmpty(t('addForm.template_id_is_required')),]
  });

  const fields = { name, template_id };

  const setFormErrors = (formErrors) => {
    for (const fieldName in formErrors) {
      if (formErrors.hasOwnProperty(fieldName)) {
        const errorMessages = formErrors[fieldName];
        let msg = '';
        errorMessages.forEach(errorMessage => {
          msg += `${errorMessage}`;
        });
        eval(fieldName).setError(msg);
      }
    }
  }

  const getTemplateList = async () => {
    try {
      const requestOptions = {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json'
        }
      };
      setIsLoading(true);
      const response = await fetch("/api/form-template-list", requestOptions);
      const responseData = await response.json();
      if (response.ok) {
        setIsLoading(false);
        if (responseData.status == 1) {
          const tempData = responseData.data;
          if (tempData.length > 0) {
            const optionInputs = [
              ...tempData.map(template => ({
                label:
                  (
                    <img src={template.img_url} ></img>
                  )
                , value: template.id.toString()
              }))
            ];
            setTemplateOptions(optionInputs);
          }
          else {
            setToastProps({
              content: t('addForm.templates_not_available')
            });
            setTimeout(() => {
              navigate('/');
            }, 1000);
          }
        }
      }
      else {
        handleResponseErrors(responseData.message, setToastProps, setIsLoading);
        navigate('/');
      }
    }
    catch (error) {
      handleResponseErrors(t('Exception.unableToProccessRequest'), setToastProps, setIsLoading);
    }
  }

  const { submit, setErrors } = useSubmit(
    async (fieldValues) => {
      try {
        const csrfToken = await csrf();
        const requestOptions = {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
          },
          body: JSON.stringify(fieldValues)
        };

        setIsLoading(true);
        const response = await fetch("/api/form-create", requestOptions);
        const responseData = await response.json();

        if (response.ok) {
          setIsLoading(false);
          if (responseData.status == 1 && Object.keys(responseData.errors).length === 0) {
            setToastProps({
              content: responseData.message
            });
            setTimeout(() => {
              navigate('/');
            }, 1000);
            return { status: 'success' };
          }
          else {
            if (Object.keys(responseData.errors).length === 0) {
              setToastProps({ content: responseData.message, error: true, })
              return { status: 'fail', errors: [{ message: responseData.message }] };
            }
            else {
              setFormErrors(responseData.errors);
              return { status: 'fail', errors: [{ message: t('Exception.fieldErrors') }] };
            }
          }
        }
        else {
          handleResponseErrors(responseData.message, setToastProps, setIsLoading);
          return { status: 'fail', errors: ['failed'] };
        }
      }
      catch (error) {
        handleResponseErrors(t('Exception.unableToProccessRequest'), setToastProps, setIsLoading);
      }
    },
    fields,
  );

  useEffect(() => {
    getTemplateList();
  }, []);

  const toastMarkup = toastProps.content && (
    <Toast {...toastProps} onDismiss={() => setToastProps(emptyToastProps)} />
  );

  return (
    <Frame>
      {toastMarkup}
      < CircleSpinnerOverlay loading={isLoading} overlayColor="rgba(255,255,255,0.2)" />
      {
        templateOptions.length ?
          <Page>
            <TitleBar title={t("addForm.add_a_new_form")} />
            <Layout>
              <Layout.Section>
                <Card sectioned>
                  <Form onSubmit={submit}>
                    <FormLayout>
                      <Grid.Cell columnSpan={{ xs: 12, sm: 12, md: 6, lg: 6, xl: 6 }}>
                        <TextField
                          label={t('addForm.formName')}
                          autoComplete="off"
                          helpText={''}
                          placeholder={t('addForm.add_new_form')}
                          {...fields.name}
                        />
                      </Grid.Cell>
                      <Grid.Cell columnSpan={{ xs: 12, sm: 12, md: 12, lg: 12, xl: 12 }} >
                        <Box className="select-template-outer">
                          <Text >{t('addForm.select_form')}</Text>
                          <OptionList
                            onChange={(selected) => { setSelected(selected) }}
                            options={templateOptions}
                            selected={templateId.toString()}
                          />
                          {fields['template_id'].error ?
                            <div className="errorStyle">
                              <Image source={errorImage} />
                              <span> {fields['template_id'].error ? fields['template_id'].error : ''}</span>
                            </div>
                            : null}
                        </Box>
                      </Grid.Cell>
                      <Grid.Cell columnSpan={{ xs: 12, sm: 12, md: 12, lg: 12, xl: 12 }}>
                        <div className="buttonClass"><Button submit>{t('addForm.submit')}</Button></div>
                      </Grid.Cell>
                    </FormLayout>
                  </Form>
                </Card>
              </Layout.Section>
            </Layout>
          </Page>
          : null
      }
    </Frame>
  );
}
