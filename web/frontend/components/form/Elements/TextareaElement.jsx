import { Box, Button, Form, Layout, TextField, Checkbox, Tooltip } from "@shopify/polaris";
import { useTranslation } from "react-i18next";
import { useState } from 'react';
import { useField, useForm, notEmpty } from '@shopify/react-form';
import { useAuthenticatedFetch } from "../../../hooks";
import { useCsrf } from "../../../hooks/useCsrf";
import { ELEMENT_TYPES, ELEMENT_CLASS } from "../../../Constants";
import { XIcon } from '@shopify/polaris-icons';
import { CloseElement, FormSubmit, alphanumeric } from "./Element";

export function TextareaElement(props) {

  const { t } = useTranslation();
  const fetch = useAuthenticatedFetch();
  const csrf = useCsrf();
  const { formId, elementDetails, setEditElement, setElement, setIsLoading, setToastProps, setFields, setCode } = props;
  const [required, setRequired] = useState((elementDetails && elementDetails['element_required']) || 0);

  const { fields, submit } =
    useForm({
      fields: {
        elementName: useField({
          value: (elementDetails && elementDetails['element_name']) || '',
          validates: [
            notEmpty(t("Elements.elementNameRequired")),
            alphanumeric(t("Elements.elementNameAlphanumeric")),
          ],
        }),
        elementDisplayName: useField({
          value: (elementDetails && elementDetails['element_display_name']) || '',
          validates: [
            notEmpty(t("Elements.elementDisplayNameRequired")),
          ],
        }),
        className: useField({
          value: (elementDetails && elementDetails['css_class']) || ELEMENT_CLASS.TEXTAREA,
          validates: []
        }),
        rowLength: useField({
          value: (elementDetails && elementDetails['rows']) || '',
          validates: [
            (value) => {
              if (value.trim() !== '' && !Number.isInteger(Number(value))) {
                return t("Elements.maxLengthInteger");
              }
            }],
        }),
        defaultValue: useField({
          value: (elementDetails && elementDetails['default_value']) || '',
          validates: [],
        }),
        required: useField({
          value: required,
          validates: []
        }),
        formId: useField({
          value: formId,
          validates: []
        }),
        elementType: useField({
          value: ELEMENT_TYPES.TEXTAREA,
          validates: []
        }),
      },
      async onSubmit(formData) {
        try {

          if (elementDetails && Object.keys(elementDetails).length !== 0) {
            formData.elementOldName = elementDetails.element_name;
          }

          return FormSubmit(csrf, fetch, formData, setIsLoading, elementDetails, setToastProps, t, setFields, fields, setCode)

        }
        catch (error) {
          setIsLoading(false);
          setToastProps({ content: t('Exception.unableToProccessRequest', { error: error }), error: true, });
          return { status: 'fail', errors: [error.message] };
        }
      },
    });

  return (
    <div className="elementBox">
      <Form onSubmit={submit}>
        <Layout>
          <Layout.Section>
            <h3 className="elementTitle">{t('Elements.textArea')}<div className="closeElement"><Tooltip content={t("Elements.closeElement")}><Button icon={XIcon} onClick={() => CloseElement(elementDetails, setEditElement, setElement)}></Button></Tooltip></div></h3>
            <Box className="elementContent">
              <Checkbox
                checked={required}
                label={t('Elements.required')}
                onChange={(newValue) => setRequired(newValue ? 1 : 0)}
                error={fields['required'].error ? fields['required'].error : ''}
              />
              <TextField
                label={<>{t('Elements.elementName')} <span className="required">*</span></>}
                autoComplete="off"
                {...fields.elementName}
              />
              <TextField
                label={<>{t('Elements.elementDisplayName')} <span className="required">*</span></>}
                autoComplete="off"
                {...fields.elementDisplayName}
              />
              <TextField
                label={t('Elements.className')}
                autoComplete="off"
                {...fields.className}
              />
              <TextField
                label={t('Elements.defaultValue')}
                autoComplete="off"
                {...fields.defaultValue}
              />
              <TextField
                label={t('Elements.rowLength')}
                autoComplete="off"
                {...fields.rowLength}
              />
              <div className="buttonClass"><Button submit>{(elementDetails && Object.keys(elementDetails).length !== 0) ? t('Common.update') : t('Common.add')}</Button></div>
            </Box>
          </Layout.Section>
        </Layout>
      </Form>
    </div>
  )
}
