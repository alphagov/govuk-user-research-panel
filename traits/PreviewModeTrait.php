<?php namespace squiz\surveys\traits;

trait PreviewModeTrait
{
    /**
     * Is preview mode?
     * @return bool
     */
    protected function isPreviewMode()
    {
        $session = $this->getRequest()->getSession();
        return $this->getRequest()->get('preview') || $session->get('preview');
    }

    /**
     * Set preview mode
     * @void
     */
    protected function handlePreviewMode()
    {
        $session = $this->getRequest()->getSession();
        if ($this->getRequest()->get('preview')) {
            //preview mode
            $session->set('preview', true);
        }
    }

    /**
     * Exit preview mode
     * @void
     */
    protected function exitPreviewMode()
    {
        $session = $this->getRequest()->getSession();
        $session->set('preview', false);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Request
     */
    abstract public function getRequest();
}