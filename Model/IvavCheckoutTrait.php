<?php
trait IvavCheckoutTrait {
    public function ivav_before_checkout_form()
    {
        $user = wp_get_current_user();
        $tmpGuid = '';
        $data = [];
        if ($user->ID > 0) {
            $userId = $user->ID;
            $av = IvavAgeVerify::load($userId);
            if ($av->isDenied()) {
               return;
            }
            $data['firstname'] = $user->first_name;
            $data['lastname']  = $user->last_name;
        } else {
            $session = new WC_Session_Handler();
            $userId = $session->generate_customer_id();
        }

        $ip     = $_SERVER['REMOTE_ADDR'];
        $r      = $this->ivav_api_create($userId, $ip, 'precheckout', $tmpGuid, $data);
        $guid   = $r['request_guid'];

        if ($userId > 0) {
            if ($guid != $tmpGuid)
            {
                update_user_meta($userId, 'ivav-guid-tmp', $guid);
            }
            if ($this->reminderDelay > 0)
            {
                wp_schedule_single_event(time() + $this->reminderDelay, 'ivav_send_reminder', array($user->user_email, $guid));
            }
        }

        $this->iframe = $r['iframeurl'];
        echo "<div id='ageVerifyMessage'><script>jQuery(document).ready(function() { showPopup(\"$this->iframe\"); });</script></div>";
    }

    public function ivav_after_checkout_validation($posted)
    {
        throw new Exception('TODO: refactor using AgeVerify class, forceName.');
        $user = wp_get_current_user();
        $userId = $user->ID;
        if ($userId > 0) {
            $guid = get_user_meta($userId, 'ivav-guid', true);
        }

        if ($guid == 'manual') {
            return;
        }
        if ($guid != '') {
            $response = $this->ivav_api_check($guid);
            if ($response['status'] == 'Approved')
            {
                return;
            }
        }
        if (!isset($_POST['ivav-guid'])) {
            $errorCount = wc_notice_count('error');
            wc_add_notice(__('<div id="ivav_popup">Please email a selfie and a picture of the front and back of your ID to <a href="mailto:' . get_option('admin_email') . '">' . get_option('admin_email') . '</a> to verify your account.</div>', 'ivav_popup'), 'error');
        } else {
            $guid = $_POST['ivav-guid'];

            $response = $this->ivav_api_check($guid);
            if ($response['status'] == 'Approved') {
                // placeholder
            } else {
                wc_add_notice(__(IVAV_ERROR), 'error');
            }
        }
    }
    // Only fires in checkout mode
    public function ivav_checkout_order_processed($orderId)
    {
        throw new Exception('TODO: refactor for forceName.');
        $order = new WC_Order($orderId);
        $user = wp_get_current_user();
        $userId = $user->ID;

        if ($userId > 0)
        {
            $av = IvavAgeVerify::load($userId);
            if ($av->isVerified() == true) {
                if ($av->isManual() == true) {
                    $order->add_order_note('AgeVerify by Inverite: previously verified manually');
                } else {
                    $order->add_order_note('AgeVerify by Inverite: <a href="' . $this->hostname . '/merchant/request/view/' . $av->guid . '">' . $guid . '(Previously verified)</a>');
                }
                return;
            }
        }

        $av = new IvavAgeVerify();
        $av->guid       = $_POST['ivav-guid'];
        $av->similarity = $_POST['ivav-similarity'];
        $av->status     = $_POST['ivav-status'];
        $av->birthdate  = $_POST['ivav-birthdate'];
        $av->age        = $_POST['ivav-age'];
        $av->firstName  = $_POST['ivav-firstname'];
        $av->lastName   = $_POST['ivav-lastname'];

        $order->add_order_note('AgeVerify by Inverite: <a href="' . $this->hostname . '/merchant/request/view/' . $av->guid . '">' . $av->guid . '</a>');

        $av->save($userId, $orderId);
        if ($av->similarity < $this->minimumSimilarity) {
            $order->update_status('on-hold', __('AgeVerify by Inverite: WARNING, selfie was only a ' . $av->similarity . '% match to ID.'));
            //$order->add_order_note();
        }
    }
}