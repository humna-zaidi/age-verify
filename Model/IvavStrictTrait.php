<?php

trait IvavStrictTrait {
    public function ivav_before_checkout_form_strict()
    {
        if ($this->forceName == 'none') {
            return;
        }

        $user = wp_get_current_user();
        $userId = $user->ID;
        if ($userId > 0) {
            $av = IvavAgeVerify::load($userId);
            if ($av->isVerified() == true) {
                if ($av->isManual() == true) {
                    $firstName = $user->first_name;
                    $lastName  = $user->last_name;
                }
                else {
                    $firstName = $av->firstName;
                    $lastName  = $av->lastName;
                }

                if ($this->forceName == 'billing' || $this->forceName == 'both') {
                    echo "
                    <script>
                    jQuery(document).ready(function() {
                        jQuery('#billing_first_name').val('$firstName');
                        jQuery('#billing_first_name').attr('readonly', true);
                        jQuery('#billing_last_name').val('$lastName');
                        jQuery('#billing_last_name').attr('readonly', true);
                    });
                    </script>
                    ";
                }
                if ($this->forceName == 'shipping' || $this->forceName == 'both') {
                    echo "
                    <script>
                    jQuery(document).ready(function() {
                        jQuery('#shipping_first_name').val('$firstName');
                        jQuery('#shipping_first_name').attr('readonly', true);
                        jQuery('#shipping_last_name').val('$lastName');
                        jQuery('#shipping_last_name').attr('readonly', true);
                    });
                    </script>
                    ";
                }

            }
        }
    }

    public function ivav_checkout_order_processed_strict($orderId)
    {
        if ($this->forceName == 'none') {
            return;
        }

        $order = new WC_Order($orderId);
        $user = wp_get_current_user();
        $userId = $user->ID;

        $av = IvavAgeVerify::load($userId, $orderId);
        if ($av->isVerified() == true) {
            if ($av->isManual() == true) {
                $firstName = $user->first_name;
                $lastName  = $user->last_name;
            }
            else {
                $firstName = $av->firstName;
                $lastName  = $av->lastName;
            }

            if ($this->forceName == 'billing' || $this->forceName == 'both') {
                if ($this->ivav_version_check(2.7)) {
                    $order->set_billing_first_name($firstName);
                    $order->set_billing_last_name($lastName);
                }
                else {
                    $order->billing_first_name = $firstName;
                    $order->billing_last_name  = $lastName;
                }
            }
            if ($this->forceName == 'shipping' || $this->forceName == 'both') {
                if ($this->ivav_version_check(2.7)) {
                    $order->set_shipping_first_name($firstName);
                    $order->set_shipping_last_name($lastName);
                }
                else {
                    $order->shipping_first_name = $firstName;
                    $order->shipping_last_name  = $lastName;
                }
            }
            $order->save();
        }
    }
}

