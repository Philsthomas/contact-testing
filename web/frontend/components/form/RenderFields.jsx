import { TextField, Text, Checkbox, RadioButton, Button, Tooltip } from "@shopify/polaris";
import { DeleteIcon, EditIcon } from "@shopify/polaris-icons";
import { useTranslation } from "react-i18next";
import { useState, useCallback, useEffect } from 'react';
import Select from 'react-select';
import DatePicker from "react-datepicker";
import "react-datepicker/dist/react-datepicker.css";
import { ELEMENT, ELEMENT_TYPES } from "../../Constants";

export function RenderFields(props) {
  const { t } = useTranslation();
  const { item, setSelected, setEditElement, itemIndex, setItemIndex, onOpenModal } = props;
  const [textareaElement, setTextareaElement] = useState("");
  const [emailElement, setEmailElement] = useState("");
  const [textField, setTextField] = useState("");
  const [radioElement, setRadioElement] = useState("");
  const [checkboxElements, setCheckboxElements] = useState([]);
  const [dropdownElements, setDropdownElements] = useState('');
  const [startDate, setStartDate] = useState(new Date());
  const [isHovered, setIsHovered] = useState(false);

  useEffect(() => {
    setTextField((item && item.default_value) ? item.default_value : "");
    setEmailElement((item && item.default_value) ? item.default_value : "");
    setTextareaElement((item && item.default_value) ? item.default_value : "");
    setRadioElement((item && item.default_value) ? item.default_value : "");
    setCheckboxElements((item && item.default_value) ? (item.default_value).split(",") : [])
    setDropdownElements((item && item.default_value) ? (item.default_value).split(",").map((value, index) => { return { label: value, value: value } }) : '')
  }, [item])

  const handleChangeTextField = useCallback(
    (newValue) => setTextField(newValue),
    [],
  );

  const handleChangeEmail = useCallback(
    (newValue) => setEmailElement(newValue),
    [],
  );

  const handleChangeTextarea = useCallback(
    (newValue) => setTextareaElement(newValue),
    [],
  );

  const handleChangeDropdown = useCallback(
    (value) => setDropdownElements(value),
    [],
  );

  const handleChangeRadio = useCallback(
    (_checked, newValue) => setRadioElement(newValue),
    [],
  );

  const handleChangeCheckbox = useCallback(
    (newValue) => {
      if (checkboxElements.includes(newValue)) {
        setCheckboxElements(checkboxElements.filter(item => item !== newValue));
      } else {
        setCheckboxElements([...checkboxElements, newValue]);
      }
    },
    [checkboxElements],
  );

  function IdentifyOptionsFormat(optionsString) {
    return optionsString.includes("=>") ?
      optionsString.split(',').map(option => {
        const [label, value] = option.split('=>');
        return { label, value };
      })
      :
      optionsString.split(',').map(option => ({
        label: option, value: option
      }));;
  }

  function EditElement(elementType, itemIndex) {
    setSelected(ELEMENT.EDIT);
    setEditElement(elementType);
    setItemIndex(itemIndex);
  }

  return (
    <>
      {(item && item.element_type == ELEMENT_TYPES.TEXTFIELD) && <div className="marginBottom5" onMouseEnter={() => setIsHovered(true)} onMouseLeave={() => setIsHovered(false)} >
        <TextField
          label={item.element_display_name}
          value={textField}
          onChange={handleChangeTextField}
          autoComplete="off"
          maxLength={item.max_length}
          connectedRight={isHovered &&
            <div className="editIconClass">
              <Tooltip content={t("Elements.editElement")}>
                <Button icon={EditIcon} onClick={() => EditElement(ELEMENT_TYPES.TEXTFIELD, itemIndex)}></Button>
              </Tooltip>
              <Tooltip content={t("Elements.deleteElement")}>
                <Button icon={DeleteIcon} onClick={() => onOpenModal(item.element_name)}></Button>
              </Tooltip>
            </div>}
        />
      </div>}
      {(item && item.element_type == ELEMENT_TYPES.EMAIL) && <div className="marginBottom5" onMouseEnter={() => setIsHovered(true)} onMouseLeave={() => setIsHovered(false)} >
        <TextField
          label={item.element_display_name}
          type="email"
          autoComplete="email"
          value={emailElement}
          onChange={handleChangeEmail}
          maxLength={item.max_length}
          connectedRight={isHovered &&
            <div className="editIconClass">
              <Tooltip content={t("Elements.editElement")}>
                <Button icon={EditIcon} onClick={() => EditElement(ELEMENT_TYPES.EMAIL, itemIndex)}></Button>
              </Tooltip>
              <Tooltip content={t("Elements.deleteElement")}>
                <Button icon={DeleteIcon} onClick={() => onOpenModal(item.element_name)} disabled={item.element_name == 'email' ? true : false}></Button>
              </Tooltip>
            </div>}
        />
      </div>}
      {(item && item.element_type == ELEMENT_TYPES.TEXTAREA) && <div className="marginBottom5" onMouseEnter={() => setIsHovered(true)} onMouseLeave={() => setIsHovered(false)} >
        <TextField
          label={item.element_display_name}
          multiline={(item && item.rows) ? parseInt(item.rows) : 4}
          value={textareaElement}
          onChange={handleChangeTextarea}
          autoComplete="off"
          connectedRight={isHovered &&
            <div className="editIconClass">
              <Tooltip content={t("Elements.editElement")}>
                <Button icon={EditIcon} onClick={() => EditElement(ELEMENT_TYPES.TEXTAREA, itemIndex)}></Button>
              </Tooltip>
              <Tooltip content={t("Elements.deleteElement")}>
                <Button icon={DeleteIcon} onClick={() => onOpenModal(item.element_name)}></Button>
              </Tooltip>
            </div>}
        />
      </div>}
      {(item && item.element_type == ELEMENT_TYPES.DROPDOWN) &&
        <div className="dropdownClass marginBottom5" onMouseEnter={() => setIsHovered(true)} onMouseLeave={() => setIsHovered(false)} >
          <div className="width100">
            <Text>{item.element_display_name}</Text>
            <Select
              options={IdentifyOptionsFormat(item.options)}
              value={dropdownElements}
              onChange={handleChangeDropdown}
              isClearable={true}
              isSearchable={true}
              placeholder={t('Elements.selectAnOption')}
              isMulti={(item.multi_select_drop_down) ? true : false}
            />
          </div>
          {isHovered &&
            (<>
              <div className="dropdownEdit editIconClass">
                <Tooltip content={t("Elements.editElement")}>
                  <Button icon={EditIcon} onClick={() => EditElement(ELEMENT_TYPES.DROPDOWN, itemIndex)}></Button>
                </Tooltip>
              </div>
              <div className="dropdownEdit editIconClass">
                <Tooltip content={t("Elements.deleteElement")}>
                  <Button icon={DeleteIcon} onClick={() => onOpenModal(item.element_name)}></Button>
                </Tooltip>
              </div>
            </>)}
        </div>
      }
      {(item && item.element_type == ELEMENT_TYPES.DATE) &&
        <div className="marginTop5" onMouseEnter={() => setIsHovered(true)} onMouseLeave={() => setIsHovered(false)} >
          <Text>{item.element_display_name}</Text>
          <div className="datePickerClass">
            <DatePicker
              selected={startDate}
              onChange={(date) => setStartDate(date)}
              className="datePicker"
            />
            {isHovered &&
              (<><div className="editIconClass">
                <Tooltip content={t("Elements.editElement")}>
                  <Button icon={EditIcon} onClick={() => EditElement(ELEMENT_TYPES.DATE, itemIndex)}></Button>
                </Tooltip>
              </div>
                <div className="editIconClass">
                  <Tooltip content={t("Elements.deleteElement")}>
                    <Button icon={DeleteIcon} onClick={() => onOpenModal(item.element_name)}></Button>
                  </Tooltip>
                </div></>)}
          </div>
        </div>
      }
      {(item && item.element_type == ELEMENT_TYPES.CHECKBOX) &&
        <div className="checkBoxContainer">
          <Text>{item.element_display_name}</Text>
          <div className="checkboxClass marginBottom5" onMouseEnter={() => setIsHovered(true)} onMouseLeave={() => setIsHovered(false)} >
            <div className="checkBoxItems">
              {(IdentifyOptionsFormat(item.options)).map((option, index) => (
                <div key={index} className={item.check_radio_line_break === ELEMENT.LINE_BREAK ? 'lineBreak1' : 'lineBreak2'}>
                  <Checkbox
                    label={option.label}
                    checked={checkboxElements.includes(option.value)}
                    onChange={() => handleChangeCheckbox(option.value)}
                  />
                </div>
              ))}
            </div>
            {isHovered &&
              <div className="editIconClass">
                <Tooltip content={t("Elements.editElement")}>
                  <Button icon={EditIcon} onClick={() => EditElement(ELEMENT_TYPES.CHECKBOX, itemIndex)}></Button>
                </Tooltip>
                <Tooltip content={t("Elements.deleteElement")}>
                  <Button icon={DeleteIcon} onClick={() => onOpenModal(item.element_name)}></Button>
                </Tooltip>
              </div>}
          </div>
        </div>
      }
      {(item && item.element_type == ELEMENT_TYPES.RADIO) &&
        <div className="radioButtonContainer">
          <Text>{item.element_display_name}</Text>
          <div className="radioButtonClass marginBottom5" onMouseEnter={() => setIsHovered(true)} onMouseLeave={() => setIsHovered(false)} >
            <div className="radioButtonItems">
              {(IdentifyOptionsFormat(item.options)).map((option, index) => (
                <div key={index} className={item.check_radio_line_break === ELEMENT.LINE_BREAK ? 'lineBreak1' : 'lineBreak2'}>
                  <RadioButton
                    label={option.label}
                    name={item.element_name}
                    id={option.value}
                    checked={radioElement === option.value}
                    onChange={handleChangeRadio}
                  />
                </div>
              ))}
            </div>
            {isHovered &&
              <div className="editIconClass">
                <Tooltip content={t("Elements.editElement")}>
                  <Button icon={EditIcon} onClick={() => EditElement(ELEMENT_TYPES.RADIO, itemIndex)}></Button>
                </Tooltip>
                <Tooltip content={t("Elements.deleteElement")}>
                  <Button icon={DeleteIcon} onClick={() => onOpenModal(item.element_name)}></Button>
                </Tooltip>
              </div>}
          </div>
        </div>
      }
      {(item && item.element_type == ELEMENT_TYPES.FILE) &&
        <div className="fileUploadClass marginBottom5" onMouseEnter={() => setIsHovered(true)} onMouseLeave={() => setIsHovered(false)} >
          <div className="fileUploadContainer">
            <Text>{item.element_display_name}</Text>
            <input type="file" />
          </div>
          {isHovered &&
            <div className="fileUploadEdit editIconClass">
              <Tooltip content={t("Elements.editElement")}>
                <Button icon={EditIcon} onClick={() => EditElement(ELEMENT_TYPES.FILE, itemIndex)}></Button>
              </Tooltip>
              <Tooltip content={t("Elements.deleteElement")}>
                <Button icon={DeleteIcon} onClick={() => onOpenModal(item.element_name)}></Button>
              </Tooltip>
            </div>}
        </div>}
    </>
  );
}
