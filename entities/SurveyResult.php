<?php namespace squiz\surveys\entities;

/**
 * Class SurveyResult
 * @package squiz\surveys\entities
 */
class SurveyResult implements SurveyResultInterface
{
    protected $step;
    protected $label;
    protected $field;
    protected $value;

    /**
     * @param $field
     * @return $this
     */
    public function setField($field)
    {
        $this->field = $field;
        return $this;
    }

    /**
     * @param $label
     * @return $this
     */
    public function setLabel($label)
    {
        $this->label = $label;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }

    /**
     * @param $step
     * @return $this
     */
    public function setStep($step)
    {
        $this->step = $step;
        return $this;
    }

    /**
     * @return string
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @return int
     */
    public function getStep()
    {
        return $this->step;
    }

    /**
     * @return string|array
     */
    public function getValue()
    {
        return $this->value;
    }
}