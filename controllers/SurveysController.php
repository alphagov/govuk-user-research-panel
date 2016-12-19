<?php namespace squiz\surveys\controllers;

use squiz\surveys\constraints\BirthDate;
use squiz\surveys\constraints\OtherField;
use squiz\surveys\constraints\StepExistsInSurvey;
use squiz\surveys\helpers\FormSessionHelper;
use squiz\surveys\helpers\SecurityHelper;
use squiz\surveys\helpers\SurveyHelper;
use squiz\surveys\lib\SurveyStepResolver;
use squiz\surveys\models\FormModel;
use squiz\surveys\entities;
use squiz\utils\SquizConfig;
use squiz\utils\FormUtils;
use squiz\utils\ContactsUtils;
use squiz\surveys\exceptions\SessionExpiredException;
use squiz\surveys\traits\PreviewModeTrait;
use squiz\surveys\events\AfterOnboardingFormSubmittedEvent;
use squiz\surveys\events\AfterSurveySubmittedEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class SurveysController
 * @package squiz\surveys
 */
class SurveysController extends AbstractController
{
    use PreviewModeTrait;

    /**
     * @var FormModel $model
     */
    public $model;
    /**
     * @var FormSessionHelper $sessionHelper
     */
    public $sessionHelper;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->handlePreviewMode();

        $formId = FormUtils::findFormByFriendlyName(
            $this->request->get('name'),
            ($this->isPreviewMode() ? null : 'Active')
        );

        if (!$formId) {
            throw new ResourceNotFoundException(FormUtils::getLastError());
        }

        $this->sessionHelper = new FormSessionHelper($formId);
        $this->model = new FormModel($formId);

        $this->handleSessionExpiration();

        $this->twig->addFunction(new \Twig_SimpleFunction(
            'getFieldValue',
            function ($field_name) {
                $formData = array_merge(
                    $this->sessionHelper->getFormContent(),
                    $this->request->request->all()
                );
                return (isset($formData[$field_name]) ? $formData[$field_name] : '');
            }
        ));
        $this->twig->addFunction(new \Twig_SimpleFunction(
            'getTemplateNameForField',
            function (\squiz_SurveyQuestions $field) {
                return SurveyHelper::getTemplateNameForField($field);
            }
        ));
        $this->twig->addFunction(new \Twig_SimpleFunction(
            'getOtherFieldName',
            function ($field_name, $field_option) {
                return SurveyHelper::getOtherFieldName($field_name, $field_option);
            }
        ));

        if (!$this->sessionHelper->getFormContent('contact') && $this->getContactId()) {
            $this->sessionHelper->rememberFormValue('contact', $this->getContactId());
        }
    }

    /**
     * Handle session expiration
     * @throws SessionExpiredException
     */
    protected function handleSessionExpiration()
    {
        $maxInactivityTime = 1200; //60 * 20
        $last_activity_time = $this->sessionHelper->get('last_activity_time');
        if ($last_activity_time &&
            (time() - $last_activity_time) > $maxInactivityTime &&
            !in_array($this->action, array('home', 'contact', 'completed'))
        ) {
            //expire session after 20mins of inactivity
            $this->sessionHelper->forgetFormContent();
            throw new SessionExpiredException();
        }
        //set last activity time
        $this->sessionHelper->set('last_activity_time', time());
    }

    /**
     * Is current form onboarding?
     * @return bool
     */
    protected function isOnboarding()
    {
        return $this->model->get('id') == SquizConfig::get('onboarding_form_id');
    }

    /**
     * @return bool
     */
    protected function isDeclarationRequired()
    {
        return $this->isOnboarding() || $this->model->get('declaration_required_c');
    }

    /**
     * @param \squiz_SurveyPages $step
     * @return bool
     */
    protected function isExitStep(\squiz_SurveyPages $step)
    {
        return in_array($step->id, SquizConfig::get('onboarding_form_exist_steps'));
    }

    /**
     * Is route permitted to access?
     * @return bool
     */
    protected function isPermitted()
    {
        if ($this->isPreviewMode()) {
            return true;
        }
        if (!$this->getContactId()) {
            return false;
        } else {
            $contact = $this->model->getContext($this->getContactId());
            if (!$contact->id) {
                //contact_id provided in query doesn't exist
                return false;
            }
        }
        return true;
    }

    /**
     * Returns contact id that current survey is related to (from session or request)
     * @return string|null
     */
    protected function getContactId()
    {
        if ($this->request->get('contact')) {
            return $this->request->get('contact');
        } elseif ($this->sessionHelper->getFormContent('contact')) {
            return $this->sessionHelper->getFormContent('contact');
        } elseif ($this->request->get('identifier')) {
            return ContactsUtils::getContactByIdentifier($this->request->get('identifier'));
        }
        return null;
    }


    /**
     * {@inheritdoc}
     */
    public function setCommonViewVariables()
    {
        parent::setCommonViewVariables();
        if ($this->model) {
            $this->set('onboarding', $this->isOnboarding());
            $this->set('survey_title', $this->model->get('name'));
        }

        $this->set('survey_url', FormUtils::getFormUrl($this->request->get('name')));
        $this->set('csfr_token', SecurityHelper::generateCSRFToken());
        $this->set('preview_mode', $this->isPreviewMode());
        $this->set('validation_errors', $this->session->getFlashBag()->get('error', []));
    }

    /**
     * Validate survey step
     * @param Request $request
     * @return bool
     */
    protected function validateStep(Request $request)
    {
        $validator = Validation::createValidator();
        $form_data = $request->request->all();
        $violations = $validator->validate($form_data['step_id'], [
            new StepExistsInSurvey(['model' => $this->model])
        ]);
        if (count($violations)) {
            return false;
        }

        $step = \BeanFactory::getBean('squiz_SurveyPages');
        $fieldsData = $step->getFields($form_data['step_id']);

        $errorArr = array();
        //loop through all the fields related to the given step
        foreach ($fieldsData as $fieldObj) {
            //field value sent in request
            $val = null;
            if (isset($form_data[$fieldObj->field])) {
                $val = $form_data[$fieldObj->field];
            }
            if (is_array($val)) {
                $val = array_filter($val);
            }
            $validators = array();
            if ($fieldObj->required_c == '1') {
                $validators[]= new NotBlank([
                    'message' => SurveyHelper::getValidationErrorMessage($fieldObj)
                ]);
            }

            if ($fieldObj->getFieldType() == 'date' && $val) {
                $validators[]= new BirthDate([
                    'message' => SurveyHelper::getValidationErrorMessage($fieldObj, 'invalid')
                ]);
            }

            if ($fieldObj->isRadio() || $fieldObj->isCheckboxesList()) {
                $validators[]= new OtherField([
                    'form_data' => $form_data,
                    'field' => $fieldObj,
                    'message' => SurveyHelper::getValidationErrorMessage($fieldObj)
                ]);
            }
            $violations = $validator->validate($val, $validators);
            if (count($violations)) {
                $errorArr[$fieldObj->field] = [
                    'value' => $val,
                    'messages' => [$violations[0]->getMessage()],
                ];
            }
        }
        if ($errorArr) {
            $this->session->getFlashBag()->set('error', $errorArr);
            return false;
        }

        return true;
    }

    /**
     * Have all the steps been successfully validated?
     * @return bool
     */
    protected function isSurveyValid()
    {
        $allSteps = $this->model->getSteps();
        foreach ($allSteps as $step) {
            if (!$this->sessionHelper->get('validated_steps/'.$step->step_order)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Action for /check-answers
     * @param string $name - survey name
     * @return Response
     */
    public function checkAnswersAction($name)
    {
        if (!$this->isPermitted()) {
            throw new ResourceNotFoundException();
        }
        if (!$this->isSurveyValid()) {
            if ($this->getPreviousUrl()) {
                return new RedirectResponse($this->getPreviousUrl());
            } else {
                return new RedirectResponse(FormUtils::getFormUrl($name));
            }
        }
        if ($this->isDeclarationRequired()) {
            $this->set('form_action', FormUtils::getFormUrl($name) . '/declaration');
        } else {
            //final submission
            $this->set('form_action', FormUtils::getFormUrl($name) . '/submit');
        }

        $this->sessionHelper->set('change_mode_fields_to_check', array());

        $header = ($this->isOnboarding() ?
            'Check your answers before sending your application' :
            'Check your answers before sending the survey');

        return $this->render(null, [
            'header' => $header,
            'survey_title' => SurveyHelper::getPageTitle($header),
            'context' => $this->model->getContext($this->getContactId()),
            'form_content' => SurveyHelper::getSurveyResultsCollection(
                $this->model,
                $this->sessionHelper->getFormContent(),
                $this->sessionHelper->get('form_steps_fields')
            ),
            'previous_step_url' => max(array_keys($this->sessionHelper->get('form_steps_fields'))),
            'declaration_required' => $this->isDeclarationRequired()
        ]);
    }

    /**
     * Action for /declaration
     * @param Request $request
     * @param string $name - survey name
     * @return Response
     */
    public function declarationAction(Request $request, $name)
    {
        if (!$this->isPermitted()) {
            throw new ResourceNotFoundException();
        }
        if (!$this->isSurveyValid()) {
            return new RedirectResponse(FormUtils::getFormUrl($name));
        }
        if ($request->isMethod('POST')) {
            //redirect via GET to make sure forward/backward browser buttons work fine
            return new RedirectResponse(FormUtils::getFormUrl($name) . '/declaration');
        }

        $header = 'Declaration';

        return $this->render(null, [
            'previous_step_url' => 'check-answers',
            'form_action' => FormUtils::getFormUrl($name) . '/submit',
            'header' => $header,
            'survey_title' => SurveyHelper::getPageTitle($header),
        ]);
    }

    /**
     * Check where to redirect from the /change url
     * @param entities\SurveyStepsCollection $allSteps - all the steps
     * @param \squiz_SurveyPages $currentStep
     * @param array $fields - all the fields from previous steps
     * @return bool
     */
    public function checkWhereToRedirect(
        entities\SurveyStepsCollection $allSteps,
        \squiz_SurveyPages $currentStep,
        $fields = array()
    ) {
        $fields = array_unique($fields);
        $context = $this->model->getContext(null, $this->sessionHelper->getFormContent());
        foreach ($currentStep->getFields() as $fieldObj) {
            if (!in_array($fieldObj->field, $fields)) {
                //merge fields from the current step with fields from previous steps
                $fields[]= $fieldObj->field;
            }
        }
        $emptyStepBean = \BeanFactory::getBean('squiz_SurveyPages');
        foreach ($allSteps as $key => $step) {
            //check if any of the next steps is dependant of any of the fields from the previous steps or current step
            if ($currentStep->step_order >= $step->step_order || !$step->display_condition) {
                continue;
            }
            foreach ($fields as $field) {
                //if so, check actually which field the next step is dependant of
                if (strpos($step->display_condition, $field) == false) {
                    continue;
                }
                //parse sugar logic - check condition against current context
                $condition = html_entity_decode($step->display_condition);
                $res = \Parser::evaluate($condition, $context)->evaluate();
                //check if we should visit that step - by checking both if the sugarlogic condition evaluation is true
                //and if we already visited that step, if we do, we dont need to visit it again
                if ((string)$res == 'true' &&
                    $this->sessionHelper->get('validated_steps/' . $step->step_order) !== $step->id
                ) {
                    //we changed our "path" so we have to remove
                    //from session fields from a step we went through i previous path
                    $fieldsToRemoveFromSession = $emptyStepBean->getFields(
                        $this->sessionHelper->get('validated_steps/'.$step->step_order)
                    );
                    foreach ($fieldsToRemoveFromSession as $fieldToRemoveFromSession) {
                        $this->sessionHelper->forgetFormContent($fieldToRemoveFromSession->field);
                    }
                    return $step;
                }
            }
        }
        return false;
    }

    /**
     * Submit survey
     * @param Request $request
     * @param string $name - survey name
     * @param string $mode - survey mode ("change")
     * @return RedirectResponse
     */
    public function surveySubmitStepAction(Request $request, $name, $mode)
    {
        $survey_url = FormUtils::getFormUrl($name);
        $this->sessionHelper->saveFormValuesFromRequest($request);

        $step = \BeanFactory::getBean('squiz_SurveyPages', $request->get('step_id'));

        if (!$this->validateStep($request)) {
            $this->sessionHelper->set('validated_steps/'.$step->step_order, false);
            return new RedirectResponse($survey_url.'/'.$step->step_order.'/'.$mode);
        }

        $resolver = new SurveyStepResolver($this->model->getSteps());

        //request is vlalid
        $this->sessionHelper->set('validated_steps/'.$step->step_order, $step->id);
        if (!$resolver->getNextStepKey($step->step_order)) {
            $goTo = 'check-answers';
        } elseif ($mode == 'change') {
            //mode is change, it means we want to go back to the check-answers page, not go through all the pages again
            //we have to check if any of the next steps is dependant of changes we did
            $fieldsToCheck = $this->sessionHelper->get('change_mode_fields_to_check') ?: array();

            $destStep = $this->checkWhereToRedirect($this->model->getSteps(), $step, $fieldsToCheck);
            //merge fields from the current step with fields from the previous steps
            $fieldsToCheck = array_merge(array_keys($request->request->all()), $fieldsToCheck);

            $this->sessionHelper->set('change_mode_fields_to_check', $fieldsToCheck);
            if ($destStep) {
                $goTo = $destStep->step_order.'/'.$mode;
            } else {
                $goTo = 'check-answers';
            }
        } else {
            $goTo = $resolver->getNextStepKey($step->step_order).'/'.$mode;
        }

        return new RedirectResponse($survey_url.'/'.$goTo);
    }

    /**
     * Display survey step
     * Action for /survey/{step_nr} or /{step_nr} (for onboarding form)
     * @param string $name - name of the survey
     * @param string $step - step number
     * @param null|string $mode - mode (currently only "change" available)
     * @return Response
     */
    public function surveyAction($name, $step, $mode = null)
    {
        $survey_url = FormUtils::getFormUrl($name);
        if (!$this->isPermitted()) {
            if ($this->isOnboarding()) {
                return new RedirectResponse($survey_url.'/home'.SurveyHelper::getFormQueryString());
            } else {
                throw new ResourceNotFoundException();
            }
        }
        $resolver = new SurveyStepResolver($this->model->getSteps());
        $context = $this->model->getContext(null, $this->sessionHelper->getFormContent());
        $currentStep = $resolver->getCurrentStepByStepKey($step, $context);

        if ($resolver->getPreviousStepKey($step) &&
            !$this->sessionHelper->get('validated_steps/'.$resolver->getPreviousStepKey($step))
        ) {
            //there was a previous step but it wasnt succesfully validated
            //In case of someone trying to access the next step url by hand
            return new RedirectResponse($survey_url);
        }

        if ($this->isExitStep($currentStep)) {
            //if current step is a final step, we shouldnt allow to access further steps
            $this->sessionHelper->set('validated_steps/'.$currentStep->step_order, false);
        }

        //filter fields to display - exclude empty fields or fields that doesn't exist in model
        $fields = $currentStep
            ->getFields()
            ->filter(function ($field) {
                if (!$field->field) {
                    return false;
                }
                if (!$field->getDefinition()) {
                    return false;
                }
                return true;
            });
        foreach ($currentStep->getFields() as $fieldObj) {
            $arrKey = 'form_steps_fields/'.$currentStep->step_order.'/'.$fieldObj->field;
            $this->sessionHelper->set($arrKey, $fieldObj->field);
        }

        $previous_step_url = '';
        if ($mode === 'change') {
            if (!$this->getPreviousUrl()
                || strpos($this->getPreviousUrl(), $this->request->server->get('REQUEST_URI')) !== false) {
                //previous url doesnt exist or its the same as current url
                $previous_step_url = $survey_url.'/check-answers';
            } else {
                $previous_step_url = $this->getPreviousUrl();
            }
        } elseif ($resolver->getPreviousStepKey($step)) {
            $previous_step_url = $survey_url.'/'.$resolver->getPreviousStepKey($step).'/'.$mode;
        } elseif ($this->isOnboarding()) {
            $previous_step_url = $survey_url.'/email-confirmed';
        }

        $titlePrefix = $currentStep->title_c ?: $currentStep->name;

        return $this->render(null, [
            'isExistStep' => $this->isExitStep($currentStep),
            'current_step' => $currentStep,
            'form_action' => $survey_url.'/'.$currentStep->step_order.'/'.$mode,
            'fields' => $fields,
            'previous_step_url' => $previous_step_url,
            'header' => html_entity_decode($currentStep->name),
            'survey_title' => SurveyHelper::getPageTitle($titlePrefix),
            'step_description' => html_entity_decode($currentStep->description),
        ]);
    }

    /**
     * @param $name
     * @return RedirectResponse
     */
    public function exitPreviewAction($name)
    {
        $this->exitPreviewMode();
        return new RedirectResponse(FormUtils::getFormUrl($name));
    }

    /**
     * Action for /completed
     */
    public function completedAction($contact_id)
    {
        $header = 'Thanks';

        return $this->render(null, [
            'header' => $header,
            'survey_title' => SurveyHelper::getPageTitle($header),
            'content' => html_entity_decode($this->model->get('thank_you_content_c')),
            'unsubscribe_url' => FormUtils::getOptOutUrl($contact_id)
        ]);
    }

    /**
     * Final submission
     */
    public function surveySubmitAction(Request $request, $name)
    {
        if (!$request->get('csfr_token') ||  $request->get('csfr_token') !== SecurityHelper::getCSFRToken()) {
            throw new ResourceNotFoundException();
        }

        if (!$this->isPermitted()) {
            throw new ResourceNotFoundException();
        }

        if (($this->isOnboarding() || $this->model->get('declaration_required_c'))
            && $request->get('declaration_c') != 1
        ) {
            //validate declaration checkbox
            $context = \BeanFactory::newBean('Contacts');
            $field_def = $context->field_defs['declaration_c'];
            $this->session->getFlashBag()->set(
                'error',
                ['declaration_c' => ['messages' => [$field_def['comments']], 'value' => '']]
            );
            return new RedirectResponse(FormUtils::getFormUrl($name).'/declaration');
        }
        //update Contact with form data
        $record = $this->model->getContext($this->getContactId(), $this->sessionHelper->getFormContent());
        $record->declaration_c = $request->get('declaration_c');
        $record->save();

        if ($this->isOnboarding()) {
            $event = new AfterOnboardingFormSubmittedEvent($record);
        } else {
            $event = new AfterSurveySubmittedEvent(
                $this->model,
                $record,
                SurveyHelper::getSurveyResultsCollection(
                    $this->model,
                    $this->sessionHelper->getFormContent(),
                    $this->sessionHelper->get('form_steps_fields')
                )
            );
        }
        $this->getDispatcher()->dispatch($event::NAME, $event);

        //clean session
        $this->sessionHelper->forgetFormContent();

        return new RedirectResponse(FormUtils::getFormUrl($name).'/completed/'.$record->id);
    }
}