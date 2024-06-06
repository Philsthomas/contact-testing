import { Card, Text, Box, Grid, Button, Tabs, Image } from "@shopify/polaris";
import { useTranslation } from "react-i18next";
import { useState, useCallback } from 'react';
import { TextFontIcon, CheckIcon, CaretDownIcon, EditIcon, CalendarIcon, EmailIcon, UploadIcon, StopCircleIcon } from '@shopify/polaris-icons';
import { RenderFields } from "./RenderFields";
import '../../assets/style.css';
import { ELEMENT, ELEMENT_TYPES } from "../../Constants";
import { TextElement, DateElement, CheckboxElement, DropdownElement, EmailElement, FileElement, RadioButtonElement, TextareaElement } from "./Elements";
import { CircleSpinnerOverlay } from "react-spinner-overlay";
import { Toast } from "@shopify/app-bridge-react";
import { DragDropContext, Droppable, Draggable } from "react-beautiful-dnd";
import { ElementSort, ElementDelete } from "./Elements/Element";
import { useCsrf } from "../../hooks/useCsrf";
import { useAuthenticatedFetch } from "../../hooks";
import Modal from 'react-responsive-modal';
import 'react-responsive-modal/styles.css';
import { dragAndDrop } from "../../assets";
import { DeleteModal } from "../DeleteModal";

export function ElementsTab(props) {
  const { t } = useTranslation();
  const csrf = useCsrf();
  const fetch = useAuthenticatedFetch();
  const { fields, setFields, setCode, formId } = props;
  const emptyToastProps = { content: null };
  const [toastProps, setToastProps] = useState(emptyToastProps);
  const [selected, setSelected] = useState(0);
  const [element, setElement] = useState(0);
  const [editElement, setEditElement] = useState(0);
  const [itemIndex, setItemIndex] = useState(0);
  const [isLoading, setIsLoading] = useState(false);
  const [open, setOpen] = useState(false);
  const [elementName, setName] = useState('');


  const onOpenModal = (elementName) => {
    setOpen(true);
    setName(elementName);
  };

  const onDelete = async () => {
    setOpen(false);
    const index = fields.findIndex(field => field.element_name === elementName);
    if (index == itemIndex)
      setEditElement(ELEMENT_TYPES.EMPTY)
    let formData = { formId: formId, elementName: elementName }
    await ElementDelete(csrf, fetch, formData, setIsLoading, setToastProps, t, setFields, setCode)
  };

  const [hoveredItem, setHoveredItem] = useState(null);

  const handleMouseEnter = (elementName) => {
    setHoveredItem(elementName);
  };

  const handleMouseLeave = () => {
    setHoveredItem(null);
  };


  const handleDragEnd = async (result) => {
    if (!result.destination) {
      return;
    }
    const { source, destination } = result;

    // If the item was dropped back into its original position, do nothing
    if (source.index === destination.index) {
      return;
    }

    // Reorder the items array according to the drag and drop result
    const reorderedItems = Array.from(fields);
    const [removed] = reorderedItems.splice(source.index, 1);
    reorderedItems.splice(destination.index, 0, removed);

    // Update the state with the new order of items
    setFields(reorderedItems);
    let formData = { sortedElements: reorderedItems, formId: formId }
    await ElementSort(csrf, fetch, formData, setIsLoading, setToastProps, t, setCode);
  };

  const handleTabChange = useCallback(
    (selectedTabIndex) => setSelected(selectedTabIndex),
    [],
  );

  const tabs = [
    {
      id: 'add-element',
      content: t("Elements.addElement"),
      accessibilityLabel: t("Elements.addElement"),
      panelID: 'add-element-content',
    },
    {
      id: 'edit-element',
      content: t("Elements.editElement"),
      accessibilityLabel: t("Elements.editElement"),
      panelID: 'edit-element-content',
    },
  ];

  const toastMarkup = toastProps.content && (
    <Toast {...toastProps} onDismiss={() => setToastProps(emptyToastProps)} />
  );

  return (
    <>
      {toastMarkup}
      < CircleSpinnerOverlay loading={isLoading} overlayColor="rgba(255,255,255,0.2)" />
      <Grid>
        <Grid.Cell columnSpan={{ xs: 6, sm: 3, md: 3, lg: 6, xl: 6 }}>
          <Card title={t('Elements.formElements')} sectioned>
            <Tabs tabs={tabs} selected={selected} onSelect={handleTabChange} fitted>
              {selected === ELEMENT.ADD &&
                <>
                  <div className="elementsList">
                    <Grid>
                      <Grid.Cell columnSpan={{ xs: 6, sm: 3, md: 3, lg: 6, xl: 6 }}>
                        <div className="elementButton"><Button icon={TextFontIcon} onClick={() => setElement(ELEMENT_TYPES.TEXTFIELD)} fullWidth>{t('Elements.text')}</Button></div>
                        <div className="elementButton"><Button icon={CheckIcon} onClick={() => setElement(ELEMENT_TYPES.CHECKBOX)} fullWidth>{t('Elements.checkbox')}</Button></div>
                        <div className="elementButton"><Button icon={CaretDownIcon} onClick={() => setElement(ELEMENT_TYPES.DROPDOWN)} fullWidth>{t('Elements.dropdown')}</Button></div>
                        <div className="elementButton"><Button icon={EditIcon} onClick={() => setElement(ELEMENT_TYPES.TEXTAREA)} fullWidth>{t('Elements.textArea')}</Button></div>
                      </Grid.Cell>
                      <Grid.Cell columnSpan={{ xs: 6, sm: 3, md: 3, lg: 6, xl: 6 }}>
                        <div className="elementButton"><Button icon={CalendarIcon} onClick={() => setElement(ELEMENT_TYPES.DATE)} fullWidth>{t('Elements.date')}</Button></div>
                        <div className="elementButton"><Button icon={StopCircleIcon} onClick={() => setElement(ELEMENT_TYPES.RADIO)} fullWidth>{t('Elements.radioButtons')}</Button></div>
                        <div className="elementButton"><Button icon={EmailIcon} onClick={() => setElement(ELEMENT_TYPES.EMAIL)} fullWidth>{t('Elements.email')}</Button></div>
                        <div className="elementButton"><Button icon={UploadIcon} onClick={() => setElement(ELEMENT_TYPES.FILE)} fullWidth>{t('Elements.fileUpload')}</Button></div>
                      </Grid.Cell>
                    </Grid>
                  </div>
                  <Box>
                    {element == ELEMENT_TYPES.TEXTFIELD && <Box><TextElement formId={formId} setElement={setElement} setIsLoading={setIsLoading} setToastProps={setToastProps} setFields={setFields} setCode={setCode} /></Box>}
                    {element == ELEMENT_TYPES.DATE && <Box><DateElement formId={formId} setElement={setElement} setIsLoading={setIsLoading} setToastProps={setToastProps} setFields={setFields} setCode={setCode} /></Box>}
                    {element == ELEMENT_TYPES.CHECKBOX && <Box><CheckboxElement formId={formId} setElement={setElement} setIsLoading={setIsLoading} setToastProps={setToastProps} setFields={setFields} setCode={setCode} /></Box>}
                    {element == ELEMENT_TYPES.RADIO && <Box><RadioButtonElement formId={formId} setElement={setElement} setIsLoading={setIsLoading} setToastProps={setToastProps} setFields={setFields} setCode={setCode} /></Box>}
                    {element == ELEMENT_TYPES.DROPDOWN && <Box><DropdownElement formId={formId} setElement={setElement} setIsLoading={setIsLoading} setToastProps={setToastProps} setFields={setFields} setCode={setCode} /></Box>}
                    {element == ELEMENT_TYPES.EMAIL && <Box><EmailElement formId={formId} setElement={setElement} setIsLoading={setIsLoading} setToastProps={setToastProps} setFields={setFields} setCode={setCode} /></Box>}
                    {element == ELEMENT_TYPES.TEXTAREA && <Box><TextareaElement formId={formId} setElement={setElement} setIsLoading={setIsLoading} setToastProps={setToastProps} setFields={setFields} setCode={setCode} /></Box>}
                    {element == ELEMENT_TYPES.FILE && <Box><FileElement formId={formId} setElement={setElement} setIsLoading={setIsLoading} setToastProps={setToastProps} setFields={setFields} setCode={setCode} /></Box>}
                  </Box>
                </>
              }
              {selected === ELEMENT.EDIT && (
                editElement ?
                  <Box>
                    {editElement == ELEMENT_TYPES.TEXTFIELD && <Box><TextElement formId={formId} elementDetails={fields[itemIndex]} setEditElement={setEditElement} setIsLoading={setIsLoading} setToastProps={setToastProps} setFields={setFields} setCode={setCode} /></Box>}
                    {editElement == ELEMENT_TYPES.DATE && <Box><DateElement formId={formId} elementDetails={fields[itemIndex]} setEditElement={setEditElement} setIsLoading={setIsLoading} setToastProps={setToastProps} setFields={setFields} setCode={setCode} /></Box>}
                    {editElement == ELEMENT_TYPES.CHECKBOX && <Box><CheckboxElement formId={formId} elementDetails={fields[itemIndex]} setEditElement={setEditElement} setIsLoading={setIsLoading} setToastProps={setToastProps} setFields={setFields} setCode={setCode} /></Box>}
                    {editElement == ELEMENT_TYPES.RADIO && <Box><RadioButtonElement formId={formId} elementDetails={fields[itemIndex]} setEditElement={setEditElement} setIsLoading={setIsLoading} setToastProps={setToastProps} setFields={setFields} setCode={setCode} /></Box>}
                    {editElement == ELEMENT_TYPES.DROPDOWN && <Box><DropdownElement formId={formId} elementDetails={fields[itemIndex]} setEditElement={setEditElement} setIsLoading={setIsLoading} setToastProps={setToastProps} setFields={setFields} setCode={setCode} /></Box>}
                    {editElement == ELEMENT_TYPES.EMAIL && <Box><EmailElement formId={formId} elementDetails={fields[itemIndex]} setEditElement={setEditElement} setIsLoading={setIsLoading} setToastProps={setToastProps} setFields={setFields} setCode={setCode} /></Box>}
                    {editElement == ELEMENT_TYPES.TEXTAREA && <Box><TextareaElement formId={formId} elementDetails={fields[itemIndex]} setEditElement={setEditElement} setIsLoading={setIsLoading} setToastProps={setToastProps} setFields={setFields} setCode={setCode} /></Box>}
                    {editElement == ELEMENT_TYPES.FILE && <Box><FileElement formId={formId} elementDetails={fields[itemIndex]} setEditElement={setEditElement} setIsLoading={setIsLoading} setToastProps={setToastProps} setFields={setFields} setCode={setCode} /></Box>}
                  </Box>
                  :
                  <Box className="elementEdit">{t('Elements.elementEditNote')}</Box>)}
            </Tabs>
          </Card>
        </Grid.Cell>
        <Grid.Cell columnSpan={{ xs: 6, sm: 3, md: 3, lg: 6, xl: 6 }}>
          <Card title={t('Elements.formPreview')} sectioned>
            <Text alignment="center">{t('Elements.contactUs')}</Text>
            <DragDropContext onDragEnd={handleDragEnd} >
              <Droppable droppableId="drop-target">
                {(provided) => (
                  <div ref={provided.innerRef} {...provided.droppableProps}>
                    <ul className="droppableUl">
                      {fields.map((item, index) => (
                        <Draggable key={item.element_name} draggableId={item.element_name} index={index}>
                          {(provided) => (
                            <div
                              ref={provided.innerRef}
                              {...provided.draggableProps}
                              {...provided.dragHandleProps}
                              onMouseEnter={() => handleMouseEnter(item.element_name)}
                              onMouseLeave={handleMouseLeave}
                              className="draggable"
                            >
                              <Box className="draggableImage">
                                {(hoveredItem === item.element_name && dragAndDrop )&& (
                                  <Image source={dragAndDrop} />
                                )}
                              </Box>
                              <RenderFields
                                item={item}
                                setSelected={setSelected}
                                setEditElement={setEditElement}
                                itemIndex={index}
                                setItemIndex={setItemIndex}
                                onOpenModal={onOpenModal}
                              />
                            </div>
                          )}
                        </Draggable>
                      ))}
                    </ul>
                    {provided.placeholder}
                  </div>
                )}
              </Droppable>
            </DragDropContext>
            <DeleteModal
              open={open}
              setOpen={setOpen}
              onDelete={onDelete}
              t={t}
              name={elementName}
              dataFrom={t("Elements.element")} />
          </Card>
        </Grid.Cell>
      </Grid>
    </>
  );
}
