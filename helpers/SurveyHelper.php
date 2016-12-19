<?php namespace squiz\surveys\helpers;

use squiz\surveys\models\FormModel;
use squiz\surveys\entities;
use squiz\surveys\exceptions\FieldTemplateNotFoundException;

class SurveyHelper
{
    /**
     * Get form query string from current url
     * @return mixed|string
     */
    public static function getFormQueryString()
    {
        $matches = array();
        if (preg_match('/(\?.+)/', $_SERVER['REQUEST_URI'], $matches)) {
            return $matches[1];
        }
        return '';
    }

    /**
     * Get page title
     * @param $prefix
     * @return string
     */
    public static function getPageTitle($prefix)
    {
        return $prefix . ' - Help us make GOV.UK better - GOV.UK';
    }

    /**
     * Get label for field
     * @param \squiz_SurveyQuestions $field
     * @param bool $noColon
     * @param bool $ignoreDescription
     * @return bool|mixed|string
     */
    public static function getFieldLabel(\squiz_SurveyQuestions $field, $noColon = false, $ignoreDescription = false)
    {
        $def = $field->getDefinition();
        $field_label = '';
        if ($field->description && !$ignoreDescription) {
            $field_label = $field->description;
        } elseif ($ignoreDescription) {
            $field_label = translate($def['vname'], $field->form_module);

            if ($noColon) {
                $field_label = preg_replace('/:$/', '', $field_label);
            }
        }
        return $field_label;
    }

    /**
     * @param \squiz_SurveyQuestions $field
     * @throws FieldTemplateNotFoundException
     * @return bool|string
     */
    public static function getTemplateNameForField(\squiz_SurveyQuestions $field)
    {
        $field_type = $field->getFieldType();
        if (in_array($field_type, array('multienum', 'enum', 'radioenum'))) {
            if ($field->isCheckboxesList()) {
                return 'checkboxes.twig';
            } elseif ($field->isRadio()) {
                return 'radio.twig';
            } else {
                return 'select.twig';
            }
        } elseif (in_array(
            $field_type,
            array('varchar', 'name', 'phone', 'currency', 'url', 'int', 'text', 'relate', 'email')
        )) {
            return 'text.twig';
        } elseif ($field_type == 'bool') {
            return 'bool.twig';
        } elseif ($field_type == 'date') {
            return 'date.twig';
        }
        throw new FieldTemplateNotFoundException('Field type: '.$field_type);
    }

    /**
     * Get label for an option
     * @param string $option
     * @return string
     */
    public static function getOptionLabel($option)
    {
        if (strpos($option, '||') !== false) {
            $optionArr = explode('||', $option);
            $option = $optionArr[0] . ' - ' . $optionArr[1];
        }
        return $option;
    }

    /**
     * Get hint text for field
     * @param \squiz_SurveyQuestions $field
     * @return string
     */
    public static function getHelpText(\squiz_SurveyQuestions $field)
    {
        $def = $field->getDefinition();
        return $def['help'];
    }

    /**
     * Returns the name of the corresponding field
     * i.e. a field that is displayed when someone choose "Other" in dropdown
     * @param string $field_name
     * @param string $field_option
     * @return string
     */
    public static function getOtherFieldName($field_name, $field_option)
    {
        $otherFieldName = '';
        if (preg_match('/^Other(.+)?/i', $field_option)) {
            $otherFieldName = preg_replace('/_c$/', '', $field_name) . '_other_c';
        }
        return $otherFieldName;
    }

    /**
     * Get validation error message for field
     * @param \squiz_SurveyQuestions $field
     * @param string $type - type of error (empty|invalid)
     * @return string
     */
    public static function getValidationErrorMessage(\squiz_SurveyQuestions $field, $type = 'empty')
    {
        $def = $field->getDefinition();
        if ($type == 'invalid' && $def['type'] == 'date') {
            return 'Enter a valid date';
        }
        //get label if description is empty
        $label = SurveyHelper::getFieldLabel($field, true) ?: SurveyHelper::getFieldLabel($field, true, true);
        $default = $label . ' is required.';
        if ($def['comments']) {
            return $def['comments'];
        }
        return $default;
    }

    /**
     * Get survey results with formatted labels
     * @param FormModel $survey
     * @param array $formData - 'field_name' => field_value
     * @param array $fieldsByStepKeys - 'step_number' => [field_names...]
     * @return entities\SurveyResultsCollection
     */
    public static function getSurveyResultsCollection(FormModel $survey, array $formData, array $fieldsByStepKeys)
    {
        global $app_list_strings;
        $answers = new entities\SurveyResultsCollection();

        foreach ($formData as $field => $value) {
            if (in_array($field, ['identifier', 'contact', 'campaign_id', 'module', 'uk_citizen_c']) ||
                preg_match('/other_c$/', $field)
            ) {
                continue;
            }
            $fieldBean = $survey->getField($field);
            if (!$fieldBean) {
                continue;
            }
            $label = ($fieldBean ? self::getFieldLabel($fieldBean, true, true) : null);
            if (!$label) {
                continue;
            }

            $def = $fieldBean->getDefinition();
            $step = null;

            foreach ($fieldsByStepKeys as $stepOrder => $fieldsNames) {
                if (in_array($def['name'], $fieldsNames)) {
                    $step = $stepOrder;
                    break;
                }
            }

            if (in_array($def['type'], ['multienum', 'enum', 'radioenum'])) {
                $value = (array)$value;
                foreach ($value as $key => $val) {
                    if (!$val) {
                        unset($value[$key]);
                        continue;
                    }
                    $otherFieldName = self::getOtherFieldName(
                        $field,
                        $app_list_strings[$def['options']][$val]
                    );
                    if ($otherFieldName &&
                        isset($formData[$otherFieldName]) &&
                        !empty($formData[$otherFieldName])
                    ) {
                        $value[$key] = $formData[$otherFieldName];
                    } else {
                        $value[$key] = self::getOptionLabel($app_list_strings[$def['options']][$val]);
                    }
                }
            } elseif ($def['type'] == 'date') {
                $value = \DateTime::createFromFormat('Y-m-d', $value)->format('d F Y');
            }
            $answer = new entities\SurveyResult();
            $answer
                ->setField($field)
                ->setLabel($label)
                ->setStep($step)
                ->setValue($value);
            $answers->addItem($answer);
        }

        return $answers;
    }
}