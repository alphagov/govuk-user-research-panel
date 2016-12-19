<?php namespace squiz\surveys\entities;

/**
 * Interface SurveyResultInterface
 * @package squiz\surveys\entities
 */
interface SurveyResultInterface
{
    /**
     * @return int
     */
    public function getStep();

    /**
     * @return string
     */
    public function getLabel();

    /**
     * @return string
     */
    public function getField();

    /**
     * @return string|array
     */
    public function getValue();
}