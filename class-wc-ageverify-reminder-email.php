<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class WC_AgeVerify_Reminder_Email extends WC_Email
{
    public function __construct()
    {
        $this->id    = 'wc_ageverify_reminder_email';
        $this->title = 'Inverite Reminder';

        $this->heading = 'Age Verification Reminder';
        $this->subject = 'Age Verification Reminder';

        $this->template_html = 'emails/customer-ageverify-reminder.php';

        add_action( 'ivav_trigger_inverite_reminder_email', array( $this, 'trigger' ), 10, 2);

        parent::__construct();
    }

    public function trigger($email, $url) {
        $this->url = $url;
        $this->send($email, $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments());
    }

    public function get_content_html() {
        return wc_get_template_html( $this->template_html, array(
            'email_heading' => $this->get_heading(),
            'sent_to_admin' => false,
            'plain_text'    => false,
            'url'           => $this->url,
            'email'         => $this,
        ));
    }
}
?>
