<?php
trait IvavApiTrait {
    public function ivav_api_check ($guid)
    {
        $r = wp_remote_post($this->hostname . '/api/fetch/' . $guid, array(
            'method' => 'POST',
            'headers' => array('Auth' => $this->apiKey)
        ));

        $response = json_decode($r['body'], 1);
        return $response;
    }

    public function ivav_api_create ($reference, $ip, $type = '', $tmpGuid = '', $data = array())
    {
#        error_log("IVAV: api-craete ref [ $reference ] , ip [ $ip ], type [ $type ], tmpGuid [ $tmpGuid ] ");
#        error_log('IVAV backtrace: ' . print_r(debug_backtrace(2),TRUE));

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

##No longer works as of 2020-04, moved instead to PofileTrait and ThankYouTrait and manually appending to iframe URL there.
#        error_log('IVAV: template 1: ' . $this->templateTheme);
#BF-CUSTOM: Set templateTheme.
### moved        $this->templateTheme = apply_filters('ivav_template_theme', $this->templateTheme);
#/BF-CUSTOM
#        error_log('IVAV: template 2: ' . $this->templateTheme);

        if ($this->templateTheme != '') {
            $body['template'] = $this->templateTheme;
        }
        //$this->logger->info("api call");
        //$this->logger->info(print_r($body,1));
#        error_log('IV-AV: h: ' . $this->hostname);
#        error_log('IV-AV: p: ' . print_r($body,1));
        $r = wp_remote_post($this->hostname . '/api/create', array(
            'method'  => 'POST',
            'headers' => array('Auth' => $this->apiKey),
            'body'    => $body,
        ));
        $rr = json_decode($r['body'], 1);
        $this->logger->info('IVAV: api rr: ' . print_r($rr,1));
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
        $av->birthdate   = $data['birthdate'];
        $av->firstName   = $data['firstname'];
        $av->lastName    = $data['lastname'];
        $av->gender      = $data['gender'];
        $av->address     = $data['address'];
        $av->city        = $data['city'];
        $av->province    = $data['province'];
        $av->postal    = $data['postal'];
        $av->date        = date('Y-m-d');

        if($data['firstname'] == null){
            $a = str_replace(',', '', $data['lastname']);
            if (count(explode(' ', $a)) > 1) {
                $first_last_name = explode(' ', $a);
                $av->firstName = $first_last_name[0];
                $av->lastName = $first_last_name[1];
            }
        }

        if($data['lastname'] == null){
            $a = str_replace(',', '', $data['firstname']);
            if (count(explode(' ', $a)) > 1) {
                $first_last_name = explode(' ', $a);
                $av->firstName = $first_last_name[0];
                $av->lastName = $first_last_name[1];
            }
        }

        $userId = 0;
        $orderId = 0;

#error_log('IV-AV: callback');
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
#error_log('IV-AV: profile - userID: ' . $userId);
            $orderId = 0;
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

error_log('IV-AV: Doing ivav_verification action.  userId: ' . $userId . ' OrderId: ' . $orderId . ' Status: ' . $av->status . ' Reason: ' . $av->reason);
        do_action('ivav_verification', $userId, $orderId, $av->status, $av->reason, $av);

        exit;
    }

    private function overrideUser($av, $userId = 0)
    {
        if ($av->isVerified() == true) {
            if ($userId > 0) {
                $this->logger->debug("IV-AV: override user $userId");
                $user = new WC_Customer($userId);
                $user->set_first_name($av->firstName);
                $user->set_last_name($av->lastName);
                if ($this->forceName == 'billing' || $this->forceName == 'both') {
                    $this->logger->info("override billing name {$av->firstName} {$av->lastName}");
                    $user->set_billing_first_name($av->firstName);
                    $user->set_billing_last_name($av->lastName);
                }
                if ($this->forceName == 'shipping' || $this->forceName == 'both') {
                    $this->logger->info("override shipping name {$av->firstName} {$av->lastName}");
                    $user->set_shipping_first_name($av->firstName);
                    $user->set_shipping_last_name($av->lastName);
                }

                $user->save();
                $this->logger->debug('IV-AV: done');
            }
        }
    }

    private function overrideOrder($av, $order)
    {
        $this->logger->debug('IV-AV: override order');
        if ($av->isVerified() == true) {
            if ($this->forceName == 'billing' || $this->forceName == 'both') {
                $this->logger->debug('IV-AV: override order billing');
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
            $this->logger->debug('IV-AV: done');

        }
    }
}
