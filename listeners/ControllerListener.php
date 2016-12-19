<?php namespace squiz\surveys\listeners;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use squiz\surveys\events;
use squiz\utils\EmailUtils;
use squiz\surveys\lib\SurveyParticipantsDescriptionFormatter;

/**
 * Class ControllerListener
 * @package squiz\surveys\listeners
 */
class ControllerListener implements EventSubscriberInterface
{
    /**
     * @param events\AfterSurveySubmittedEvent $event
     */
    public function afterSurveySubmitted(events\AfterSurveySubmittedEvent $event)
    {
        $model = $event->getModel();
        $record = $event->getContact();

        $campaign_id = $model->get('squiz_surveys_campaignscampaigns_idb');
        if ($campaign_id) {
            $db = \DBManagerFactory::getInstance();
            $marketing = \BeanFactory::getBean('EmailMarketing');
            $marketing_query = $marketing->create_new_list_query(
                'date_start desc, date_modified desc',
                "campaign_id = '{$campaign_id}' and status = 'active' and date_start < " . $db->convert('', 'today'),
                array('id')
            );
            $marketing_result = $db->limitQuery($marketing_query, 0, 1, true);
            $marketing_data = $db->fetchByAssoc($marketing_result);
            //create campaign log
            global $timedate;
            $camplog = \BeanFactory::getBean('CampaignLog');
            $camplog->team_id = $model->get('team_id');
            $camplog->team_set_id = $model->get('team_set_id');
            $camplog->campaign_id = $campaign_id;
            $camplog->related_id = $record->id;
            $camplog->related_type = $record->module_dir;
            $camplog->activity_type = "lead";
            $camplog->target_type = $record->module_dir;
            $camplog->activity_date = $timedate->now();
            $camplog->target_id = $record->id;
            if (isset($marketing_data['id'])) {
                $camplog->marketing_id = $marketing_data['id'];
            }
            $camplog->save();

            $record->load_relationship('campaigns');
            $record->campaigns->add($camplog->id);
        }

        $surveyParticipant = \BeanFactory::getBean('squiz_SurveyParticipants');
        if ($model->get('declaration_required_c')) {
            $surveyParticipant->declaration_received_c = \TimeDate::getInstance()->nowDb();
        }

        $formatter = new SurveyParticipantsDescriptionFormatter($event->getSurveyResults());

        $surveyParticipant->description= $formatter->format();
        $surveyParticipant->team_id = $model->get('team_id');
        $surveyParticipant->team_set_id = $model->get('team_set_id');
        $surveyParticipant->save();
        $surveyParticipant->load_relationship('squiz_surveyparticipants_contacts');
        $surveyParticipant->squiz_surveyparticipants_contacts->add($record->id);
        $surveyParticipant->load_relationship('squiz_surveyparticipants_squiz_surveys');
        $surveyParticipant->squiz_surveyparticipants_squiz_surveys->add($model->get('id'));
        //regenerate calculated title
        $surveyParticipant->save();
    }

    /**
     * @param events\AfterOnboardingFormSubmittedEvent $event
     */
    public function afterOnboardingFormSubmitted(events\AfterOnboardingFormSubmittedEvent $event)
    {
        EmailUtils::sendThankYouEmail($event->getContact());
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            events\AfterOnboardingFormSubmittedEvent::NAME => 'afterOnboardingFormSubmitted',
            events\AfterSurveySubmittedEvent::NAME => 'afterSurveySubmitted',
        );
    }
}