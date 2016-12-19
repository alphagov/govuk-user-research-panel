<?php namespace squiz\surveys\models;

use squiz\utils\BeanUtils;
use squiz\utils\StringUtils;
use squiz\surveys\entities\SurveyFieldsCollection;
use squiz\surveys\entities\SurveyStepsCollection;
use squiz\surveys\exceptions\SurveyNotFoundException;

/**
 * Class FormModel
 * @package squiz\surveys\models
 */
class FormModel
{
    protected static $steps;
    protected static $fieldsByStep = array();
    protected static $fieldsBySurvey = array();
    protected $db;
    protected $context;
    protected $formBean;

    /**
     * FormModel constructor.
     * @param $formId
     * @throws \Exception
     */
    public function __construct($formId)
    {
        $this->db = \DBManagerFactory::getInstance();
        $this->formBean =  \BeanFactory::getBean('squiz_Surveys', $formId);
        if (!$this->formBean->id) {
            throw new SurveyNotFoundException('Form hasnt been found.');
        }
    }

    /**
     * Get formBean property
     * @param $name
     * @return mixed
     */
    public function get($name)
    {
        return $this->formBean->{$name};
    }

    /**
     * Get all steps for given form
     * @param bool $cache
     * @return SurveyStepsCollection
     */
    public function getSteps($cache = true)
    {
        if (!self::$steps || !$cache) {
            $collection = new SurveyStepsCollection();
            $this->formBean->load_relationship('squiz_surveypages_squiz_surveys');
            foreach ($this->formBean->squiz_surveypages_squiz_surveys->getBeans() as $step) {
                $collection->addItem($step);
            }
            //sort objects by step_order
            $collection->usort(function ($a, $b) {
                return $a->step_order > $b->step_order;
            });

            self::$steps = $collection;

        }
        return self::$steps;
    }

    /**
     * @param bool $cache
     * @return SurveyFieldsCollection
     */
    public function getAllSurveyFields($cache = true)
    {
        $survey_id = $this->get('id');
        if (!isset(self::$fieldsBySurvey[$survey_id]) || !$cache) {
            $db = \DBManagerFactory::getInstance();
            $sql = 'SELECT ff.*, ssc.*, sss.squiz_surveypages_squiz_surveyssquiz_surveypages_idb as step_id FROM squiz_surveyquestions ff INNER JOIN squiz_surveyquestions_squiz_surveypages_c ff_fs 
                ON ff.id = ff_fs.squiz_surveyquestions_squiz_surveypagessquiz_surveyquestions_idb  
                INNER JOIN squiz_surveypages_squiz_surveys_c sss ON sss.squiz_surveypages_squiz_surveyssquiz_surveypages_idb = ff_fs.squiz_surveyquestions_squiz_surveypagessquiz_surveypages_ida
                LEFT JOIN squiz_surveyquestions_cstm as ssc ON ff.id = ssc.id_c
                WHERE ff.deleted="0" 
                AND ff_fs.deleted="0" 
                AND sss.deleted="0"
                AND sss.squiz_surveypages_squiz_surveyssquiz_surveys_ida = "'.StringUtils::mres($survey_id).'" 
                ORDER BY ff.field_order ASC';

            $query = $db->query($sql);
            $fields = new SurveyFieldsCollection();
            while ($row = mysqli_fetch_assoc($query)) {
                $field = \BeanFactory::getBean('squiz_SurveyQuestions');
                $field->populateFromRow($row);
                $fields->addItem($field);
            }
            self::$fieldsBySurvey[$survey_id] = $fields;
        }
        return self::$fieldsBySurvey[$survey_id];
    }

    /**
     * Get squiz_SurveyQuestion data along with definition of the related field
     * @param string $field_name
     * @return \squiz_SurveyQuestions|bool
     */
    public function getField($field_name)
    {
        $fields = $this->getAllSurveyFields();
        foreach ($fields as $field) {
            if ($field->field == $field_name) {
                return $field;
            }
        }
        return false;
    }
    /**
     * Returns a SugarBean representation of the form.
     * i.e. if form creates a Contact it returns a Contact bean
     * @param string $id
     * @param array data to populate the context with
     * @return \SugarBean
     */
    public function getContext($id = null, array $data = array())
    {
        $bean = \BeanFactory::getBean($this->formBean->form_module, $id);
        if ($data) {
            $bean = BeanUtils::populateBeanWithValues($bean, $data);
        }
        return  $bean;
    }

    /**
     * Return form bean
     * @return \SugarBean
     */
    public function getBean()
    {
        return $this->formBean;
    }
}