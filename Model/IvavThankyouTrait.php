<?php

trait IvavThankyouTrait {
    // In Thankyou mode order has been placed, now determine whether to show popup.
    public function ivav_thankyou($orderId)
    {
        // skip hack for client, ignore
        {
            $skip = get_post_meta($orderId, 'ivav-skip', true);
            if ($skip == '1') {
                return;
            }
        }
        // basic environment info
        $order = new WC_Order($orderId);
        $user = wp_get_current_user();
        $userId = $user->ID;

        // Start by loading User's AV data
        $av = IvavAgeVerify::load($userId, $orderId);
        if ($av->isVerified() === true) {
            return;
        }

        if ($av->guidTmp != '') {
            // Existing guid, let's fire that one up.
            $iframeUrl = $this->hostname . '/customer/web/start_age/' . $av->guidTmp . '/' . $this->templateTheme;
            $guid = $av->guidTmp;
        } else {
            // Looks like we're going to need a new request.  They must not have emails enabled.
            $data = [];
            $this->overrideNames($order, $data);

            $ip        = get_post_meta($orderId, '_customer_ip_address', true );
            $r         = $this->ivav_api_create($orderId, $ip, 'thankyou', $tmpGuid, $data);
            $guid      = $r['request_guid'];
            $iframeUrl = $r['iframeurl'];

            $av->guidTmp = $guid;
            $av->save($userId, $orderId);
        }

        // schedule reminder email
        if ($this->reminderDelay > 0) {
            wp_schedule_single_event(time() + $this->reminderDelay, 'ivav_send_reminder', array($order->billing_email, $guid, $orderId));
        }

        // show iframe
        {
            $customerMessage = $this->customerMessage;
            if ($this->thankyouInline == 'inline') {
                echo "
                <div id='ageVerifyMessage'>
                <script>jQuery(document).ready(function() { showInline(\"$iframeUrl\");});</script>
                </div>
                ";
            } elseif ($this->thankyouInline == 'click') {
                echo "
                <div id='ageVerifyMessage'>
                <p><span style='color: red;' class='dashicons dashicons-warning'></span>To complete your order, <a onclick='return showPopup(\"$this->iframe\")'>click here</a> to verify your age.</p>
                </div>
                ";
            } elseif ($this->thankyouInline == 'popup') {
                echo "
                <div id='ageVerifyMessage'>
                <script>jQuery(document).ready(function() { showPopup(\"$iframeUrl\");});</script>
                </div>
                ";
            }
        }
    }

    public function ivav_thankyou_email_before_order_table($order, $sent_to_admin)
    {
        if ($this->verificationMode != 'thankyou') return;

        if (!$sent_to_admin) {
            if ( !$order->has_status('completed') ) {
                if ($this->ivav_version_check(2.7)) {
                    $orderId = $order->get_id();
                } else {
                    $orderId = $order->id;
                }

                $userId = $order->get_user_id();

                $av = IvavAgeVerify::load($userId, $orderId);
                if ($av->isVerified() === true) {
                    return;
                }

                $data = [];
                $this->overrideNames($order, $data);

                $ip = get_post_meta( $orderId, '_customer_ip_address', true );
                $r = $this->ivav_api_create($orderId, $ip, 'thankyou', $tmpGuid, $data);

                $guid      = $r['request_guid'];
                $iframeUrl = $r['iframeurl'];

                $av->guidTmp = $guid;
                $av->save($userId, $orderId);

                echo "
        You must verify your age to complete the order.  <a href='$iframeUrl'>Please click here to verify your age.</a>
                ";

            }
        }
    }

    private function overrideNames($order, &$data)
    {
        if ($this->forceName =='billing' || $this->forceName == 'both') {
            if ($this->ivav_version_check(2.7)) {
                $data['firstname'] = $order->get_billing_first_name();
                $data['lastname']  = $order->get_billing_last_name();
            }
            else {
                $data['firstname'] = $order->billing_first_name;
                $data['lastname']  = $order->billing_last_name;
            }
        }

        if ($this->forceName == 'shipping' || $this->forceName == 'both') {
            if ($this->ivav_version_check(2.7)) {
                $data['firstname'] = ($order->get_shipping_first_name() !== '') ? $order->get_shipping_first_name() : $order->get_billing_first_name();
                $data['lastname']  = ($order->get_shipping_last_name() !== '') ? $order->get_shipping_last_name() : $order->get_billing_last_name();
            }
            else {
                $data['firstname'] = ($order->shipping_first_name !== '') ? $order->shipping_first_name : $order->billing_first_name;
                $data['lastname']  = ($order->shipping_last_name !== '') ? $order->shipping_last_name : $order->billing_last_name;
            }
        }
    }

    public function ivav_custom_order_button_text ($text)
    {
        if ($this->verificationMode != 'thankyou' || $this->placeOrderText == '') {
            return __($text, 'woocommerce');
        }

        $user = wp_get_current_user();
        $userId = $user->ID;
        if ($userId > 0) {
            $av = IvavAgeVerify::load($userId);

            if ($av->isVerified()) {
                return __($text, 'woocommerce');
            }
        }
        return __($this->placeOrderText, 'woocommerce');
    }

    // Only fires in thankyou mode to allow for specific client
    public function ivav_checkout_order_processed_skip($orderId)
    {
        $skip = $_POST['ivav-skip'];
        if ($skip != '') {
            update_post_meta($orderId, 'ivav-skip', $skip);
        }
    }
}
