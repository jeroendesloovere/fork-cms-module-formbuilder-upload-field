<?php

namespace Frontend\Modules\FormBuilder\EventListener;

use Swift_Mailer;
use Frontend\Core\Engine\Language as FL;
use Frontend\Core\Engine\Model as FrontendModel;
use Frontend\Modules\FormBuilder\Event\FormBuilderSubmittedEvent;

/**
 * A Formbuilder submitted event subscriber that will send an email if needed
 *
 * @author Wouter Sioen <wouter@sumocoders.be>
 */
final class FormBuilderSubmittedMailToSubmitterSubscriber
{
    protected $mailer;

    /**
     * @param Swift_Mailer $mailer
     */
    public function __construct(Swift_Mailer $mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     * @param FormBuilderSubmittedEvent $event
     */
    public function onFormSubmitted(FormBuilderSubmittedEvent $event)
    {
        $form = $event->getForm();

        // need to send mail
        if (isset($form['send_mail_to_submitter']) && $form['send_mail_to_submitter'] == 'Y') {
            // build our message
            $from = FrontendModel::get('fork.settings')->get('Core', 'mailer_from');

            // build data
            $data = $event->getData();
            $data['submitter_info_message'] = array(
                'label' => ucfirst(FL::lbl('InfoMessage')),
                'value' => serialize(strip_tags($form['submitter_info_message'])),
            );

            $fieldData = $this->getEmailFields($data);
            $message = \Common\Mailer\Message::newInstance(
                    FL::getMessage('FormBuilderSubjectMailToSubmitter')
                )
                ->parseHtml(
                    FRONTEND_MODULES_PATH . '/FormBuilder/Layout/Templates/Mails/FormToSubmitter.tpl',
                    array(
                        'sentOn' => time(),
                        'name' => $form['name'],
                        'fields' => $fieldData,
                    ),
                    true
                )
                ->setTo($form['email'])
                ->setFrom(array($from['email'] => $from['name']))
            ;

            $this->mailer->send($message);
        }
    }

    /**
     * Converts the data to make sure it is nicely usable in the email
     *
     * @param  array $data
     * @return array
     */
    protected function getEmailFields($data)
    {
        return array_map(
            function ($item) {
                $value = unserialize($item['value']);
                return array(
                    'label' => $item['label'],
                    'value' => (is_array($value)
                        ? implode(',', $value)
                        : nl2br($value)
                    )
                );
            },
            $data
        );
    }
}
