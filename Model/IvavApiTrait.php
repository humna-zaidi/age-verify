<?php
trait IvavApiTrait {
    public function ivav_api_check ($guid)
    {
        $r = wp_remote_post($this->hostname . '/api/v2/fetch/' . $guid, array(
            'method' => 'POST',
            'headers' => array('Auth' => $this->apiKey)
        ));

        $response = json_decode($r['body'], 1);
        return $response;
    }

    public function ivav_api_create ($reference, $ip, $type = '', $tmpGuid = '', $data = array())
    {
        if ($type == '') {
            $type = $this->verificationMode;
        }

        $body = array(
            'siteID'          => $this->siteId,
            'ip'              => $ip,
            'referenceid'     => $this->siteName . '_' . $type . '_' . $reference,
            'requestedfields' => $this->requiredFields,
        );


        if (isset($data['firstname']) && isset($data['lastname'])) {
            $body['firstname'] = $data['firstname'];
            $body['lastname']  = $data['lastname'];
        }
        if ($tmpGuid) {
            $body['prior_guid'] = $tmpGuid;
            //$body['username']    = $this->siteName . '_' . $type . '_' . $reference,
        } else {
            $body['username'] = $this->siteName . '_' . $type . '_' . $reference;
            //$body['username']    = $this->siteName . '_' . $type . '_' . $reference,
        }
        if ($this->templateTheme != '') {
            $body['template'] = $this->templateTheme;
        }
        $r = wp_remote_post($this->hostname . '/api/v2/create', array(
            'method'  => 'POST',
            'headers' => array('Auth' => $this->apiKey),
            'body'    => $body,
        ));
        $rr = json_decode($r['body'], 1);
        if (isset($rr['errors'])) {
            $this->logger->error('IV-AV: api error:');
            $this->logger->error(print_r($rr['errors'], 1));
        }
        return $rr;
    }

    public function ivav_handle_callback()
    {
        $this->logger->info('IV-AV: callback');
        $data = json_decode(file_get_contents('php://input'), true);
        $this->logger->info(print_r($data,1));

        $av = new IvavAgeVerify();
        $av->referenceId = $data['referenceid'];
        $av->guid        = $data['request'];
        $av->status      = $data['status'];
        $av->reason      = $data['failure_reason'];
        $av->similarity  = $data['similarity'];
        $av->siteName    = $this->siteName;
        $av->age         = $data['calculated_age'];
        $av->gender      = $data['gender'];
        $av->birthdate   = $data['birthdate'];
        $av->firstName   = $data['firstname'];
        $av->lastName    = $data['lastname'];
        $av->date        = date('Y-m-d');

        $userId = 0;
        $orderId = 0;

        // original checkout mode - can almost be ignored due since it relies on iframe events
        if (is_numeric($av->referenceId)) {
            $orderId = $av->referenceId;
            $av->save(0, $orderId);
        } elseif (preg_match('/^'.$this->siteName.'_thankyou_(.+)$/', $av->referenceId, $matches)) {
            $orderId = $matches[1];
            $order = new WC_Order($orderId);
            $userId = $order->get_user_id();

            $av->save($userId, $orderId);

            if ($this->forceName != 'none') {
                $this->overrideUser($av, $userId);
                $this->overrideOrder($av, $order);
            }

            $order->add_order_note('AgeVerify by Inverite: <a href="' . $this->hostname . '/merchant/request/view/' . $av->guid . '">' . $av->guid . '</a>');
            if ($this->minimumSimilarity > 0 && $similarity < $this->minimumSimilarity) {
                $order->add_order_note(__('AgeVerify by Inverite: WARNING, selfie was only a ' . $av->similarity . '% match to ID.'));
            }
        } elseif (preg_match('/^'.$this->siteName.'_(?:pre)?profile_(.+)$/', $av->referenceId, $matches)) {
            $userId = $matches[1];
            if ($av->isDenied() && $av->reason == 'namematch_failure') {
                $this->logger->info("IV-AV: namematch_failure");
                $av->guidTmp = '';
            }
            $av->save($userId);
            $this->overrideUser($av, $userId);
        } else {
            $this->logger->error("IV-AV: unknown type $av->referenceId");
            $this->logger->error(print_r($data,1));
            exit;
        }

        do_action('ivav_verification', $userId, $orderId, $av->status, $av->reason);

        exit;
    }

    private function overrideUser($av, $userId = 0)
    {
        if ($av->isVerified() == true) {
            if ($userId > 0) {
                $user = new WC_Customer($userId);
                $user->set_first_name($av->firstName);
                $user->set_last_name($av->lastName);
                if ($this->forceName == 'billing' || $this->forceName == 'both') {
                    $user->set_billing_first_name($av->firstName);
                    $user->set_billing_last_name($av->lastName);
                }
                if ($this->forceName == 'shipping' || $this->forceName == 'both') {
                    $user->set_shipping_first_name($av->firstName);
                    $user->set_shipping_last_name($av->lastName);
                }

                $user->save();
            }
        }
    }

    private function overrideOrder($av, $order)
    {
        if ($av->isVerified() == true) {
            if ($this->forceName == 'billing' || $this->forceName == 'both') {
                if ($this->ivav_version_check(2.7)) {
                    $order->set_billing_first_name($av->firstName);
                    $order->set_billing_last_name($av->lastName);
                }
                else {
                    $order->billing_first_name = $av->firstName;
                    $order->billing_last_name  = $av->lastName;
                }
            }
            if ($this->forceName == 'shipping' || $this->forceName == 'both') {
                if ($this->ivav_version_check(2.7)) {
                    $order->set_shipping_first_name($av->firstName);
                    $order->set_shipping_last_name($av->lastName);
                }
                else {
                    $order->shipping_first_name = $av->firstName;
                    $order->shipping_last_name  = $av->lastName;
                }
            }
            $order->save();
        }
    }
}
