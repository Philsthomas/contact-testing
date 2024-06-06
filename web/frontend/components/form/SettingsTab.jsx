import { Card, Button, TextField, Text, Grid, RadioButton, Form, Layout, Box } from "@shopify/polaris";
import { useTranslation } from "react-i18next";
import { useState } from 'react';
import { SETTINGS } from "../../Constants";
import { useCsrf } from '../../hooks/useCsrf'
import { useAuthenticatedFetch } from "../../hooks";
import { Toast } from "@shopify/app-bridge-react";
import { useField, useForm, notEmpty } from '@shopify/react-form';
import { handleResponseErrors } from "../../common";
import { CircleSpinnerOverlay } from 'react-spinner-overlay'

export function SettingsTab(props) {
  const { t } = useTranslation();
  const { settingsData, setCode, formId } = props;
  const fetch = useAuthenticatedFetch();
  const csrf = useCsrf();
  const emptyToastProps = { content: null };
  const [toastProps, setToastProps] = useState(emptyToastProps);
  const [isLoading, setIsLoading] = useState(false);
  const [after_submission, setAfterSubmission] = useState(settingsData ? settingsData.after_submission : SETTINGS.AFTER_SUBMISSION_MESSAGE);

  const { fields, submit } =
    useForm({
      fields: {
        submit_button_text: useField({
          value: settingsData ? settingsData.submit_button_text : "Submit",
          validates: [
            notEmpty(t("Settings.submitButtonTextRequired")),
          ],
        }),
        submit_button_class: useField({
          value: settingsData ? settingsData.submit_button_class : "submit_button",
          validates: [
            notEmpty(t("Settings.submitButtonClassRequired")),
          ],
        }),
        thanks_message: useField({
          value: settingsData ? settingsData.thanks_message : t('Settings.defaultMessage'),
          validates: (value) => {
            if (after_submission == SETTINGS.AFTER_SUBMISSION_MESSAGE && value == '') {
              fields.redirect_url.setError('')
              return t("Settings.confirmationMessageRequired");
            }
          },
        }, [after_submission]),
        redirect_url: useField({
          value: settingsData ? settingsData.redirect_url : '',
          validates: (value) => {
            if (after_submission == SETTINGS.AFTER_SUBMISSION_URL) {
              if (value == '') {
                fields.thanks_message.setError('')
                return t("Settings.urlRequired");
              }
              else {
                const urlPattern = /^(ftp|http|https):\/\/[^ "]+$/;
                if (!urlPattern.test(value)) {
                  fields.thanks_message.setError('')
                  return t("Settings.invalidUrl");
                }
              }
            }
          },
        }, [after_submission]),
      },
      async onSubmit(formData) {
        try {
          const csrfToken = await csrf();
          formData._token = csrfToken;
          formData.after_submission = after_submission;

          const requestOptions = {
            method: 'PUT',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': csrfToken  // Include as header
            },
            body: JSON.stringify(formData)
          };
          setIsLoading(true);

          const response = await fetch("/api/form-update/" + formId, requestOptions);
          const data = await response.json();
          const message = data.message;

          if (response.ok) {
            setIsLoading(false);
            if (data.status == 1) {
              setToastProps({ content: message })
              setCode(data.data.formCode)
              return { status: 'success' };
            }
            else {
              if (Object.keys(data.errors).length === 0) {
                setToastProps({ content: message, error: true, })
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
      },
    });

  const toastMarkup = toastProps.content && (
    <Toast {...toastProps} onDismiss={() => setToastProps(emptyToastProps)} />
  );

  return (
    <>
      {toastMarkup}
      < CircleSpinnerOverlay loading={isLoading} overlayColor="rgba(255,255,255,0.2)" />
      <Form onSubmit={submit}>
        <Layout>
          <Layout.Section>
            <Grid>
              <Grid.Cell columnSpan={{ xs: 6, sm: 3, md: 3, lg: 6, xl: 6 }}>
                <Card title={t('Settings.formSubmission')} sectioned>
                  <Box className="elementContent">
                    <TextField
                      label={t('Settings.submitButtonText')}
                      autoComplete="off"
                      {...fields.submit_button_text}
                    />
                    <TextField
                      label={t('Settings.submitButtonClass')}
                      autoComplete="off"
                      {...fields.submit_button_class}
                    />
                    <Text as="h2" variant="bodyMd">{t('Settings.afterFormSubmission')}</Text>
                    <RadioButton
                      label={t('Settings.afterFormSubmissionOption1')}
                      checked={after_submission == SETTINGS.AFTER_SUBMISSION_MESSAGE}
                      id="1"
                      name="afterSubmission"
                      onChange={(e, newValue) => { setAfterSubmission(newValue) }}
                    />
                    <RadioButton
                      label={t('Settings.afterFormSubmissionOption2')}
                      id="2"
                      name="afterSubmission"
                      checked={after_submission == SETTINGS.AFTER_SUBMISSION_URL}
                      onChange={(e, newValue) => { setAfterSubmission(newValue) }}
                    />
                    {after_submission == SETTINGS.AFTER_SUBMISSION_URL && <TextField
                      label={t('Settings.urlTip')}
                      placeholder={t('Settings.urlLabel')}
                      autoComplete="off"
                      {...fields.redirect_url}
                    />}
                  </Box>
                </Card>
              </Grid.Cell>
              <Grid.Cell columnSpan={{ xs: 6, sm: 3, md: 3, lg: 6, xl: 6 }}>
                <Card title={t('Settings.messages')} sectioned>
                  <TextField
                    label={t('Settings.confirmationMessage')}
                    multiline={4}
                    autoComplete="off"
                    {...fields.thanks_message}
                  />
                </Card>
              </Grid.Cell>
            </Grid>
            <div className="buttonClass"><Button submit>{t('Common.update')}</Button></div>
          </Layout.Section>
        </Layout>
      </Form>
    </>
  );
}
