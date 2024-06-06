
import React from 'react';
import { Box, Button, Card, Grid, Text } from '@shopify/polaris';
import { ClipboardIcon } from '@shopify/polaris-icons';
import { useTranslation } from "react-i18next";

export function PublishTab(props) {

    const { t } = useTranslation();
    const { code, setToastProps } = props;

    const copyToClipboard = () => {
        navigator.clipboard.writeText(code)
            .then(() => {
                setToastProps({ content: t("Publish.copied") });
            })
            .catch(error => {
                setToastProps({ content: t("Publish.copyFailed", { error: error }), error: true, });
            });
    }

    return (
        <Card roundedAbove="sm">
            <Box className="publish">
                <Grid className="fff">
                    <Box>
                        <Text as="h2" variant="headingSm">{t("Publish.scriptCode")}</Text>
                        <Text as="p" tone="success">{t("Publish.scriptCodeNote")}</Text>
                    </Box>
                    <Button
                        onClick={copyToClipboard}
                        accessibilityLabel={t("Publish.copy")}
                        icon={ClipboardIcon}
                    >
                        {t("Publish.copy")}
                    </Button>
                </Grid>
                <Text as="p" variant="bodyMd">{code}</Text>
            </Box>
        </Card>
    )
}
