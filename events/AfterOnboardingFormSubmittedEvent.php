<?php namespace squiz\surveys\events;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class AfterOnboardingFormSubmittedEvent
 * @package squiz\surveys\events
 */
class AfterOnboardingFormSubmittedEvent extends Event
{
    const NAME = 'onboarding.submitted.after';

    /**
     * @var \SugarBean
     */
    protected $contact;

    /**
     * AfterOnboardingFormSubmittedEvent constructor.
     * @param \SugarBean $contact
     */
    public function __construct(\SugarBean $contact)
    {
        $this->contact = $contact;
    }

    /**
     * @return \SugarBean
     */
    public function getContact()
    {
        return $this->contact;
    }
}