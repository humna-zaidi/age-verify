<?php

trait IvavAdminTrait {
    public function ivav_wc_order_manual_verify_action($order)
    {
        $userId = $order->get_user_id();
        $orderId = ($this->ivav_version_check(2.7)) ? $orderId = $order->get_id() : $order->id;
        IvavAgeVerify::verify($userId, $orderId);
        $order->add_order_note('AgeVerify by Inverite: Manually verified');
        do_action('ivav_verification', $userId, $orderId, 'Approved', 'Manual Verification from order ID ' . $orderId);
    }

    public function ivav_wc_order_manual_clear_action($order)
    {
        $userId = $order->get_user_id();
        $orderId = ($this->ivav_version_check(2.7)) ? $orderId = $order->get_id() : $order->id;
        IvavAgeVerify::clear($userId, $orderId);
        $order->add_order_note('AgeVerify by Inverite: age verification manually cleared.');
        do_action('ivav_verification', $userId, $orderId, 'Failed', 'Manual Verification Clear from order ID ' . $orderId);
    }

    public function ivav_register_bulk_verify($actions)
    {
        $actions['ivav-bulk-verify'] = __('AgeVerify: mark user as manually age verified.');
        return $actions;
    }

    public function ivav_bulk_action_edit_user($redirectTo, $action, $userIds)
    {
        if ($action == 'ivav-bulk-verify') {
            foreach ($userIds as $userId) {
                IvavAgeVerify::verify($userId);
            }
        }
        return $redirectTo;
    }

    public function ivav_register_bulk_verify_order($actions)
    {
        $actions['ivav-bulk-verify'] = __('AgeVerify: mark user as manually age verified.');
        return $actions;
    }

    public function ivav_bulk_action_edit_order($redirectTo, $action, $orderIds)
    {
        if ($action == 'ivav-bulk-verify')
        {
            foreach ($orderIds as $orderId)
            {
                $order = new WC_Order($orderId);
                $userId = $order->get_user_id();

                IvavAgeVerify::verify($userId, $orderId);
            }
        }
        return $redirectTo;
    }

    public function ivav_custom_order_option($post)
    {
        $orderId = $post->ID;
        $order = new WC_Order( $orderId );
        $userId = $order->get_user_id();

        $av = IvavAgeVerify::load($userId, $orderId);

        if ($av->isManual()) {
            $class = 'dashicons dashicons-yes';
            $color = 'green';
            $msg = 'Age manually verified';
        } elseif ($av->isVerified()) {
            $class = 'dashicons dashicons-yes';
            $color = 'green';
            $description = 'Age Verified';

            if (in_array($this->forceName, ['billing', 'both']) && !$av->isNameMatch($order->billing_first_name, $order->billing_last_name)) {
                $color = 'orange';
                $description .= ' - Name Mismatch';
            }
            if (in_array($this->forceName, ['shipping', 'both']) && !$av->isNameMatch($order->shipping_first_name, $order->shipping_last_name)) {
                $color = 'orange';
                $description .= ' - Name Mismatch';
            }

            $msg = '<a href="' . $this->hostname . '/merchant/request/view/' . $av->guid . '">' . $description . '</a>';
            if (is_numeric($av->age)) {
                $msg .= '<br />Name: ' . $av->firstName . ' ' . $av->lastName;
                $msg .= '<br />Birthdate: ' . $av->birthdate;
                $msg .= '<br />Age at verification: ' . $av->age;
            }
        } elseif ($av->isDenied()) {
            $class = 'dashicons dashicons-no';
            $color = 'red';
            $msg   = 'Age verification failed';
        } else {
            $class = 'dashicons dashicons-no';
            $color = 'black';
            $msg = 'Age unverified';
        }
        echo "
        <table class='form-table'>
            <tr><td><span style='color: $color' class='$class'></span></td><td>$msg</td></tr>
        </table>
        ";
    }

    public function ivav_shop_order_columns($columns)
    {
        $columns = (is_array($columns)) ? $columns : array();

        $ageVerify = array('ageVerify' => 'AgeVerify');
        $position = 3;

        $newColumns = array_slice($columns, 0, $position, true) +  $ageVerify +  array_slice($columns, $position, count($columns)-$position, true);
            //stop editing
        return $newColumns;
    }

    public function ivav_shop_order_posts_custom_column($column, $post_id)
    {
        $the_order = wc_get_order($post_id);

        if ($column == 'ageVerify') {
            $userId  = $the_order->get_user_id();
            $orderId = $the_order->get_id();
            $class = 'dashicons-no';
            $color = 'black';
            $title   = 'Age unverified';

            $av = IvavAgeVerify::load($userId, $orderId);

            if ($av->isVerified()) {
                $color = 'green';
                $title = 'Age Verified';
                if ($av->isManual()) {
                    $title .= ' Manually';
                }
                else {
                    if (in_array($this->forceName, ['shipping', 'both']) && !$av->isNameMatch($the_order->get_shipping_first_name(), $the_order->get_shipping_last_name())) {
                        $color = 'orange';
                        $title .= ' - Name Mismatch';
                    }
                    if (in_array($this->forceName, ['billing', 'both']) && !$av->isNameMatch($the_order->get_billing_first_name(), $the_order->get_billing_last_name())) {
                        $color = 'orange';
                        $title .= ' - Name Mismatch';
                    }

                }
                $class = 'dashicons-yes';
                if ($av->date != '')
                    $title .= "\nDate: $av->date";
            } elseif ($av->isDenied()) {
                $color = 'red';
                $title = 'Age verification failed';
                if ($av->date != '')
                      $title .= "\nDate: $av->date";
            }
            echo '<span style="color: ' . $color . '" class="dashicons ' . $class . '" title="' . $title . '"></span>';
        }
    }

    public function ivav_wc_order_actions($actions)
    {
        global $theorder;
        if ($theorder == null) {
            return $actions;
        }

        $orderId = ($this->ivav_version_check(2.7)) ? $orderId = $theorder->get_id() : $theorder->id;
        $order = new WC_Order($orderId);
        $userId = $order->get_user_id();

        $av = IvavAgeVerify::load($userId, $orderId);

        if (!$av->isManual()) {
            $actions['ivav_wc_order_manual_verify_action'] = __('AgeVerify: mark order as manually age verified.');
        }
        if ($av->guid != '')
        {
            $actions['ivav_wc_order_manual_clear_action'] = __('AgeVerify: clear age verification data.');
        }

        return $actions;
    }

    public function ivav_hide_order_status($order_statuses)
    {
        global $theorder;
        if ($theorder == null) {
            return $order_statuses;
        }

        $orderId = ($this->ivav_version_check(2.7)) ? $orderId = $theorder->get_id() : $orderId = $theorder->id;
        $order = new WC_Order($orderId);
        $userId = $order->get_user_id();

        $av = IvavAgeVerify::load($userId, $orderId);

        if ($this->forceName == 'billing' || $this->forceName == 'both'
            && !$av->isManual()
            && !$av->isNameMatch($order->billing_first_name, $order->billing_last_name)) {
            unset($order_statuses['wc-processing']);
        }

        if ($this->forceName == 'shipping' || $this->forceName == 'both'
            && !$av->isManual()
            && !$av->isNameMatch($order->shipping_first_name, $order->shipping_last_name)) {
            unset($order_statuses['wc-processing']);
        }

        return $order_statuses;
    }

    public function ivav_show_extra_user_fields($user)
    {
        $av = IvavAgeVerify::load($user->ID);
        if ($av->isManual() == true) {
            $msg = '<span style="color: green;" class="dashicons dashicons-yes"></span>Manually verified';
        } elseif ($av->isVerified() == true) {
           $msg = '<span style="color: green;" class="dashicons dashicons-yes"></span><a href="' . $this->hostname . '/merchant/request/view/' . $guid . '">' . $guid . '</a>';
            if (is_numeric($av->age))
            {
                $extra = "<tr><th>Name</th><td>$av->firstName $av->lastName</td></tr>";
                $extra .= "<tr><th>Birthdate</th><td>$av->birthdate</td></tr>";
                $extra .= "<tr><th>Age at verification</th><td>$av->age</td></tr>";
            }
        } elseif ($av->isDenied() == true) {
            $msg = '<span style="color: red:" class="dashicons dashicons-no"></span>Failed';
        } else {
            $msg = '<span style="color: black:" class="dashicons dashicons-no"></span>Unverified';
        }
        echo "
        <h3>AgeVerify by Inverite</h3>
        <table class='form-table'>
            <tr><th>Age Verification Status</th><td>$msg</td></tr>
             $extra
        </table>
        ";
    }
}
