import React, { useState, useEffect } from 'react';
import { Page, Card, DataTable, Link, Button, Box } from '@shopify/polaris';
import { useAuthenticatedFetch } from "../hooks";
import { useCsrf } from '../hooks/useCsrf';
import { Toast, TitleBar, useNavigate } from "@shopify/app-bridge-react";
import { useTranslation } from "react-i18next";
import { CircleSpinnerOverlay } from 'react-spinner-overlay'
import { handleResponseErrors } from '../common';
import "../assets/style.css";
import { DeleteModal } from '../components';

export default function ManageForms() {

  const [isLoading, setIsLoading] = useState(false);
  const { t } = useTranslation();
  const csrf = useCsrf();
  const fetch = useAuthenticatedFetch();
  const emptyToastProps = { content: null };
  const [toastProps, setToastProps] = useState(emptyToastProps);
  const navigate = useNavigate();
  const [currentPage, setCurrentPage] = useState(1);
  const itemsPerPage = 10; // Number of items per page
  const [listForms, setListForms] = useState([]);
  const [open, setOpen] = useState(false);
  const [formName, setName] = useState('');
  const [formId, setFormId] = useState(0);

  const onOpenModal = (formId, formName) => {
    setOpen(true);

    setName(formName)
    setFormId(formId)
  };

  const onDelete = async () => {
    setOpen(false);
    deleteForm(formId)
  };


  const getFormsList = async () => {
    try {
      const requestOptions = {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json'
        }
      };

      setIsLoading(true);
      const response = await fetch("/api/form-list", requestOptions);
      const responseData = await response.json();

      if (response.ok) {
        if (responseData.status == 1) {
          let formArray = [];
          responseData.data.forEach((key) => {
            let tempArray = [];
            tempArray.push(
              key['id'],
              key['name'],
              key['template_name'],
              key['formatted_created_at'],
              key['formatted_updated_at'],
              <Box className="manageActions">
                <Button onClick={() => { onOpenModal(key['id'], key['name']) }}>{t('manageForm.delete')}</Button>
                <Button onClick={() => navigate(`/form-edit/${key['id']}`)}>{t('manageForm.edit')}</Button>
              </Box>);
            formArray.push(tempArray);
          });
          setListForms(formArray);
        }
        else if (responseData.status == 0) {
          setToastProps({
            content: responseData.message,
            error: true,
          });
        }
        setIsLoading(false);
      }
      else {
        handleResponseErrors(responseData.message, setToastProps, setIsLoading);
      }
    }
    catch (error) {
      handleResponseErrors(t('Exception.unableToProccessRequest'), setToastProps, setIsLoading);
    }
  }
  const deleteForm = async (id) => {
    try {
      const csrfToken = await csrf();
      const requestOptions =
      {
        method: 'DELETE',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({
          'Content-Type': 'application/json',
          _token: csrfToken
        })
      };
      setIsLoading(true);
      const response = await fetch("/api/form-delete/" + id, requestOptions);
      const responseData = await response.json();

      if (response.ok) {
        if (responseData.status == 1) {
          setToastProps({
            content: responseData.message
          });
          setListForms(oldValues => {
            return oldValues.filter(data => data[0] !== id)
          })
        }
        else if (responseData.status == 0) {
          setToastProps({
            content: responseData.message,
            error: true,
          });
        }
        setIsLoading(false);
      }
      else {
        handleResponseErrors(responseData.message, setToastProps, setIsLoading);
      }
      setListForms(oldValues => {
        return oldValues.filter(data => data[0] !== id)
      })
    }
    catch (error) {
      handleResponseErrors(t('Exception.unableToProccessRequest'), setToastProps, setIsLoading);
    }
  }
  useEffect(() => {
    getFormsList();
  }, []);

  const startIndex = (currentPage - 1) * itemsPerPage;
  const endIndex = Math.min(startIndex + itemsPerPage, listForms.length);
  const rowsForCurrentPage = listForms.slice(startIndex, endIndex);

  const toastMarkup = toastProps.content && (
    <Toast {...toastProps} onDismiss={() => setToastProps(emptyToastProps)} />
  );

  return (
    <>
      {toastMarkup}
      < CircleSpinnerOverlay loading={isLoading} overlayColor="rgba(255,255,255,0.2)" />

        <Page >
          <TitleBar title={t('manageForm.manage_forms')} />
        {isLoading ?
          null
          :
          <Card>
            {(rowsForCurrentPage && rowsForCurrentPage.length > 0) ?
              <>
            <DataTable
              columnContentTypes={[
                'numeric',
                'text',
                'text',
                'text',
                'text',
                'text'
              ]}
              headings={[
                t('manageForm.id'),
                t('manageForm.name'),
                t('manageForm.template_name'),
                t('manageForm.created_at'),
                t('manageForm.updated_at'),
                t('manageForm.actions')
              ]}
              rows={rowsForCurrentPage}
              totals={[]}
            />           
            <DeleteModal
              open={open}
              setOpen={setOpen}
              onDelete={onDelete}
              t={t}
              name={formName}
              dataFrom={t("manageForm.form")} />

              </>
              :
              <Box className="noRecordsFound">{t("manageForm.noRecordsFound")}</Box>
            }
          </Card>
        }
        </Page>
    </>
  );
}
