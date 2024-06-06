import { Card, Page, Layout, Tabs } from "@shopify/polaris";
import { TitleBar } from "@shopify/app-bridge-react";
import { useTranslation } from "react-i18next";
import { useState, useCallback, useEffect } from 'react';
import { SettingsTab, ElementsTab, PublishTab } from '../components';
import { Toast } from "@shopify/app-bridge-react";
import { useAuthenticatedFetch } from "../hooks";
import { handleResponseErrors } from "../common";
import { FORM_VIEW } from "../Constants";
import { CircleSpinnerOverlay } from 'react-spinner-overlay';
import { useParams } from "react-router-dom";

export default function FormEdit() {
    const { t } = useTranslation();
    const [selected, setSelected] = useState(0);
    const [isLoading, setIsLoading] = useState(true);
    const [data, setData] = useState({});
    const [fields, setFields] = useState([]);
    const emptyToastProps = { content: null };
    const [toastProps, setToastProps] = useState(emptyToastProps);
    const fetch = useAuthenticatedFetch();
    const [code, setCode] = useState("");
    const { formId } = useParams();

    const handleTabChange = useCallback(
        (selectedTabIndex) => setSelected(selectedTabIndex),
        [],
    );

    const tabs = [
        {
            id: 'elements',
            content: t("FormEdit.elements"),
            accessibilityLabel: t("FormEdit.elements"),
            panelID: 'elements-content',
        },
        {
            id: 'settings',
            content: t("FormEdit.settings"),
            accessibilityLabel: t("FormEdit.settings"),
            panelID: 'settings-content',
        },
        {
            id: 'publish',
            content: t("FormEdit.publish"),
            accessibilityLabel: t("FormEdit.publish"),
            panelID: 'publish-content',
        }
    ];

    const fetchData = async () => {
        try {
            const response = await fetch(`/api/form-view/${formId}`);
            const data = await response.json();
            const message = data.message;

            if (response.ok) {
                if (data.status == 1) {
                    setData(data);
                    setFields(JSON.parse(data.data.formData.fields));
                    setCode(data.data.formData.code);
                }
                else {
                    setToastProps({ content: message, error: true, });
                }
            }
            else {
                await handleResponseErrors(message, setToastProps, setIsLoading);
            }
            setIsLoading(false);
        } catch (error) {
            await handleResponseErrors(t('Exception.unableToProccessRequest', { error: error }), setToastProps, setIsLoading);
            setIsLoading(false);
        }

    };

    useEffect(() => {
        fetchData();
    }, [])

    const toastMarkup = toastProps.content && (
        <Toast {...toastProps} onDismiss={() => setToastProps(emptyToastProps)} />
    );

    return (
        <>
            {toastMarkup}
            {isLoading ?
                < CircleSpinnerOverlay loading={isLoading} overlayColor="rgba(255,255,255,0.2)" />
                :
                <Page>
                    {data.status == 1 ?
                        <>
                            <TitleBar
                                title={t("FormEdit.title", {
                                    formName: isLoading ? '' : data.data.formData.name,
                                })}
                            />
                            <Layout>
                                <Layout.Section>
                                    <Card>
                                        <Tabs tabs={tabs} selected={selected} onSelect={handleTabChange} fitted>
                                            <Card.Section >
                                                {selected === FORM_VIEW.ELEMENT_TAB && <ElementsTab fields={fields} setFields={setFields} setCode={setCode} formId={formId} />}
                                                {selected === FORM_VIEW.SETTINGS_TAB && <SettingsTab settingsData={data.data.formData} setCode={setCode} formId={formId} />}
                                                {selected === FORM_VIEW.PUBLISH_TAB && <PublishTab code={code} setToastProps={setToastProps} />}
                                            </Card.Section>
                                        </Tabs>
                                    </Card>
                                </Layout.Section>
                            </Layout>
                        </>
                        :
                        null}
                </Page>
            }
        </>
    );
}
