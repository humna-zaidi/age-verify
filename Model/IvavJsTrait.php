<?php
trait IvavJsTrait
{
    public function ivav_apply_js_attributes($tag, $handle)
    {
        $pagename = basename(get_permalink());
        if (!in_array($pagename,array('checkout','thankyou','my-account'))) return $tag;
        if (!preg_match('/js\/av\.js/', $tag)) {
            return $tag;
        }

        $msg = $this->customerMessage;
        if ($pagename == 'my-account') {
            if (!is_user_logged_in()) {
                return $tag;
            }
            $this->verificationMode = 'profile';
            $msg = $this->customerMessageAccount;
        }

        $session = new WC_Session_Handler();
        $customerId = $session->generate_customer_id();

        if ($handle !== 'ivav') {
            return $tag;
        }

        $config = 0;
        if ($this->isDev == 1) {
            $config = $config | IVAV_IS_DEV;
        } elseif ($this->isStage == 1) {
            $config = $config | IVAV_IS_STAGE;
        } elseif ($this->isSandbox == 1) {
            $config = $config | IVAV_IS_SANDBOX;
        }

        if ($this->allowEmail == 'yes') {
            $config = $config | IVAV_ALLOW_EMAIL;
        }
        if ($this->allowPhone == 'yes') {
            $config = $config | IVAV_ALLOW_PHONE;
        }

        if ($this->forceName == 'billing' || $this->forceName == 'both') {
            $config = $config | IVAV_FORCE_BILLING;
        }
        if ($this->forceName == 'shipping' || $this->forceName == 'both') {
            $config = $config | IVAV_FORCE_SHIPPING;
        }

        return str_replace( ' src', ' data-site-name="'. $this->siteName . '" data-reference="' . $customerId .  '" data-config="' . $config . '" data-mode="' . $this->verificationMode . '" data-msg="' . urlencode($msg) . '" data-iframe="' . urlencode($this->iframe) . '" data-site-key="' . $this->siteKey . '" src', $tag );
    }

    public function ivav_setup_js()
    {
        $pagename = basename(get_permalink());

        if (!in_array($pagename,array('checkout','thankyou','my-account')))
            return;
        if ($pagename == 'my-account' && !is_user_logged_in()) {
            return;
        }


        $this->ivav_setup_js_internal();
    }

    private function ivav_setup_js_internal()
    {
        wp_enqueue_style('dashicons');
        wp_register_script('ivav-origin', 'https://www.inv-cdn-ca.com/origin.js', array('jquery'));
        wp_enqueue_script('ivav-origin');
        wp_register_script('ivav', plugin_dir_url( __FILE__ ) . '../js/av.js', array('jquery'));
        wp_enqueue_script('ivav');
    }
}
