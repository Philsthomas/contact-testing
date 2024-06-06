import { Box, Button } from "@shopify/polaris";
import Modal from 'react-responsive-modal';
import 'react-responsive-modal/styles.css';

export function DeleteModal(props) {

    const { open, setOpen, onDelete, t, name, dataFrom } = props;
    const onCloseModal = () => setOpen(false);

    return (
        <Box>
            <Modal open={open} onClose={onCloseModal} center>
                <h2 className="modalHeader">{t('Elements.elementDeleteConfirmaion', { dataFrom: dataFrom })}</h2>
                <Box className="modalContent">{t('Elements.elementDeletionMessage', { name: name })}</Box>
                <div className="modalButton">
                    <Button onClick={onCloseModal}>{t('Elements.no')}</Button>
                    <Button onClick={onDelete}>{t('Elements.yes')}</Button>
                </div>
            </Modal>
        </Box>
    )
}