<?php namespace squiz\surveys\controllers;

use squiz\surveys\forms\FormFactory;
use squiz\surveys\helpers\FormHelper;
use squiz\surveys\helpers\FormSessionHelper;
use squiz\surveys\helpers\SurveyHelper;
use squiz\surveys\models\FormModel;
use squiz\utils\FormUtils;
use squiz\utils\SquizConfig;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use squiz\utils\EmailUtils;
use squiz\surveys\traits\PreviewModeTrait;

class RegistrationController extends AbstractController
{
    use PreviewModeTrait;
    /**
     * @var FormSessionHelper $sessionHelper
     */
    private $sessionHelper;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();
        $this->handlePreviewMode();
        $this->sessionHelper = new FormSessionHelper(SquizConfig::get('onboarding_form_id'));
        $this->rememberQueryValues();
        $this->twig->addFunction(new \Twig_SimpleFunction('getOtherFieldName', function ($field_name, $field_option) {
            return SurveyHelper::getOtherFieldName($field_name, $field_option);
        }));
    }

    /**
     * {@inheritdoc}
     */
    public function setCommonViewVariables()
    {
        parent::setCommonViewVariables();
        $this->set('survey_url', FormUtils::getOnboardingFormUrl());
        $this->set('validation_errors', $this->session->getFlashBag()->get('error', array()));
    }

    /**
     * Remember in session query params from url
     */
    protected function rememberQueryValues()
    {
        global $app_list_strings;
        foreach (array('utm_source', 'utm_medium', 'utm_campaign', 't') as $var) {
            $val = $this->request->get($var);
            if (!$val) {
                continue;
            }
            //map utm codes to sugar fields
            if ($var == 'utm_source') {
                $var = 'lead_source';
            } elseif ($var == 'utm_medium') {
                $var .= '_c';
                foreach ((array)$app_list_strings['utm_medium_list'] as $key => $label) {
                    if (strtolower($val) === strtolower($key)) {
                        $val = $key;
                    }
                }
            } elseif ($var == 'utm_campaign') {
                $var .= '_c';
            }
            $this->sessionHelper->rememberFormValue($var, $val);
        }
    }
    /**
     * Action for /contact
     */
    public function contactAction()
    {
        $form = FormFactory::getForm('Registration');

        $formData = $this->session->getFlashBag()->get('formData');
        if ($formData) {
            $form->submit($formData);
        }

        $header = 'Your contact details';

        return $this->render(null, [
            'form' => $form->createView(),
            'validation_errors' => FormHelper::getErrorsAsAssociativeArray($form),
            'previous_step_url' => 'home',
            'header' => $header,
            'survey_title' => SurveyHelper::getPageTitle($header),
        ]);
    }

    /**
     * action for /home
     */
    public function homeAction()
    {
        $header = 'Help make GOV.UK better';

        return $this->render(null, [
            'survey_title' => $header. ' - GOV.UK',
            'header' => $header
        ]);
    }

    /**
     * Action for /more
     */
    public function moreAction()
    {
        $header = 'What you’ll be asked to do';

        return $this->render(null, [
            'previous_step_url' => $this->getPreviousUrl(),
            'header' => $header,
            'survey_title' => SurveyHelper::getPageTitle($header),
        ]);
    }

    /**
     * Action for /confirm-email/{contact_id}
     */
    public function confirmEmailAction($name, $contact)
    {
        $bean = \BeanFactory::getBean('Contacts', $contact);
        if (!$bean->id) {
            throw new ResourceNotFoundException();
        }

        $header = 'Check your email';

        return $this->render(null, [
            'form_action' => FormUtils::getFormUrl($name).'/resend-email/'.$bean->id,
            'contact' => $bean,
            'header' => $header,
            'survey_title' => SurveyHelper::getPageTitle($header),
        ]);
    }

    /**
     * Action for /resend-email/{contact_id}
     */
    public function resendEmailAction($name, $contact)
    {
        $bean = \BeanFactory::getBean('Contacts', $contact);
        if (!$bean->id) {
            throw new ResourceNotFoundException();
        }
        EmailUtils::sendOptInEmail($bean);
        return new RedirectResponse(FormUtils::getFormUrl($name).'/confirm-email/'.$bean->id);
    }

    /**
     * Action for /email-confirmed
     */
    public function emailConfirmedAction()
    {
        if (!$this->sessionHelper->getFormContent('contact')) {
            throw new ResourceNotFoundException();
        }

        $bean = \BeanFactory::getBean('Contacts', $this->sessionHelper->getFormContent('contact'));
        if (!$bean->id) {
            throw new ResourceNotFoundException();
        }

        $header = 'Thanks for confirming your email address';
        return $this->render(null, [
            'contact' => $bean,
            'header' => $header,
            'survey_title' => SurveyHelper::getPageTitle($header),
        ]);
    }

    /**
     * Action to verify email address
     */
    public function verifyAction($hash)
    {
        $contact_id = FormUtils::getContactIdByHash($hash);
        if (!$contact_id) {
            throw new ResourceNotFoundException('Contact hasn\'t been found.');
        }
        $contact = \BeanFactory::getBean('Contacts', $contact_id);
        $contact->email_optin_complete_c = 'Yes';
        $contact->save();
        $this->sessionHelper->rememberFormValue('contact', $contact->id);

        $query = new \SugarQuery();
        $query->select(array('id'));
        $query->from(\BeanFactory::getBean('Contacts'));
        $query->where()->equals('email1', $contact->email1)->notEquals('id', $contact->id);
        $res = $query->execute();
        if ($res) {
            foreach ($res as $contactData) {
                //remove all duplicated
                $bean = \BeanFactory::getBean('Contacts');
                $bean->mark_deleted($contactData['id']);
                $GLOBALS['log']->fatal(__METHOD__.': Duplicate Contact with id '.$contactData['id'].' has been deleted. Duplicated email address: '.$contact->email1);
            }
        }
        return new RedirectResponse(FormUtils::getOnboardingFormUrl().'/email-confirmed');
    }

    /**
     * User registration
     * @param Request $request
     * @return RedirectResponse
     */
    public function registrationSubmitAction(Request $request)
    {
        $form = FormFactory::getForm('Registration');
        $form->handleRequest($request);
        if (!$form->isValid()) {
            $this->session->getFlashBag()->set('formData', $form->getData());
            return new RedirectResponse(FormUtils::getOnboardingFormUrl() . '/contact');
        }

        $data = $form->getData();
        $nameArr = explode(' ', $data['full_name']);

        $contact = \BeanFactory::getBean('Contacts');
        $contact->first_name = $nameArr[0];
        $contact->last_name = $nameArr[1];
        $contact->email1 = $data['email1'];

        $team_id = FormUtils::getTeamByOnboardingCode($this->sessionHelper->getFormContent('t'));
        if ($team_id) {
            $contact->team_id = $team_id;
        } else {
            $model = new FormModel(SquizConfig::get('onboarding_form_id'));
            $contact->team_id = $model->get('team_id');
            $contact->team_set_id = $model->get('team_set_id');
        }
        //save utm params
        foreach ($this->sessionHelper->getFormContent() as $field => $value) {
            $contact->{$field} = $value;
        }
        $contact->save();

        $this->sessionHelper->forgetFormContent();

        return new RedirectResponse(FormUtils::getOnboardingFormUrl() . '/confirm-email/' . $contact->id);
    }

    /**
     * Unsubscribe and redirect
     */
    public function unsubscribeAction($hash)
    {
        $contact_id = FormUtils::getContactIdByHash($hash);
        if (!$contact_id) {
            throw new ResourceNotFoundException('Contact hasn\'t been found.');
        }
        $contact = \BeanFactory::getBean('Contacts', $contact_id);
        $contact->email_opt_out = '1';
        $contact->do_not_create_opt_out = 1;
        $contact->save();
        $optOut = \BeanFactory::newBean('squiz_OptOuts');
        $optOut->name = 'Opt out';
        $optOut->assigned_user_id = 1;
        $optOut->save();
        $optOut->load_relationship('squiz_optouts_squiz_surveys');
        $optOut->squiz_optouts_squiz_surveys->add(SquizConfig::get('onboarding_form_id'));
        $this->sessionHelper->forgetFormContent();
        return new RedirectResponse(
            FormUtils::getOnboardingFormUrl().'/unsubscribe/'.$optOut->id.'?email='.$contact->email1
        );
    }

    /**
     * Display unsubscribe reason form
     */
    public function unsubscribeReasonAction(Request $request, $optout_id)
    {
        global $app_list_strings;
        $optOut = \BeanFactory::getBean('squiz_OptOuts', $optout_id);
        if (!$optOut->id) {
            throw new ResourceNotFoundException('OptOut hasn\'t been found.');
        }
        $bean = \BeanFactory::getBean('squiz_OptOuts');
        $fieldDef = $bean->field_defs['unsubscribe_reason_c'];
        $header = 'You’ve successfully unsubscribed';

        return $this->render(null, [
            'email' => $request->query->get('email'),
            'field_label' => translate($fieldDef['vname'], 'squiz_OptOuts'),
            'help_text' => $fieldDef['help'],
            'header' => $header,
            'options_list' => $app_list_strings[$fieldDef['options']],
            'survey_title' => SurveyHelper::getPageTitle($header),
            'form_action' => '/unsubscribe-reson/'.$optout_id,
        ]);
    }

    /**
     * Thank you page for unsubscribe-reason form
     */
    public function unsubscribeReasonThankYouAction()
    {
        $header = 'Unsubscribe';

        return $this->render(null, [
            'header' => $header,
            'survey_title' => SurveyHelper::getPageTitle($header)
        ]);
    }

    /**
     * Save unsubscribtion reason in OptOut record
     */
    public function unsubscribeReasonSubmitAction(Request $request, $optout_id)
    {
        $optOut = \BeanFactory::getBean('squiz_OptOuts', $optout_id);
        if (!$optOut->id) {
            throw new ResourceNotFoundException('OptOut hasn\'t been found.');
        }
        $reasonArr = $request->get('unsubscribe_reason_c');
        if (is_array($reasonArr)) {
            $reason = '^'.implode('^,^', $reasonArr).'^';
            $optOut->unsubscribe_reason_c = $reason;
            $optOut->unsubscribe_reason_other_c = $request->get('unsubscribe_reason_other_c', '');
            $optOut->save();
        }
        return new RedirectResponse(FormUtils::getOnboardingFormUrl().'/unsubscribe-success');
    }
}