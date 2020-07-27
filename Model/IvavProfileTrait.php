<?php
trait IvavProfileTrait {

    public function ivav_profile_shortcode($attributes, $content = null)
    {
# error_log('IV-AV: ivav_profile_shortcode attributes: [' . print_r($attributes,TRUE) . '], content: [' . $content . ']');
        $this->ivav_setup_js_internal();

        return $this->ivav_edit_profile_action($attributes);
    }

    public function ivav_wc_edit_account_form()
    {
        $msg = $this->ivav_edit_profile_action();

        echo "
        <fieldset>
            <legend><b>Age Verify Status</legend>
            <div id='ageVerifyMessage'>
            $msg
            </div>
        </fieldset>
        ";
    }

    private function ivav_edit_profile_action($attributes = null)
    {
        $userId    = get_current_user_id();
        $av = IvavAgeVerify::load($userId, null);

        $verified = 0;
        if ($av->isVerified() == true) {
            $description = "Verified";
            if (isset($attributes['verified'])) {
                $description = $attributes['verified'];
            }
            $msg = "
            <span style='color: green' class='dashicons dashicons-yes'></span>Verified
            <script>
            jQuery(document).ready(function() {
				jQuery('#account_first_name').attr('readonly', true);
				jQuery('#account_last_name').attr('readonly', true);
            });
            </script>
            ";
        } else {
            $ip     = $_SERVER['REMOTE_ADDR'];
            $user = wp_get_current_user();
            $data = [];
            if ($user !== null) {
                $data['firstname'] = $user->first_name;
                $data['lastname']  = $user->last_name;
            }
            $r    = $this->ivav_api_create($userId, $ip, 'profile', $av->guidTmp, $data);
            error_log('IV-AV ivav_profile: api create returned r: [' . print_r($r,TRUE) . ']');
            $guid = $r['request_guid'];
            if ($userId > 0 && $guid != $av->guidTmp) {
                $av->guidTmp = $guid;
#error_log('IV-AV: edit profile save');
                $av->save($userId);
            }

            // schedule reminder email
            if ($this->reminderDelay > 0) {
                $user = wp_get_current_user();
                // need to prevent duplicate emails here!
                wp_schedule_single_event(time() + $this->reminderDelay, 'ivav_send_reminder', array($user->user_email, $guid));
            }

            $this->iframe = $r['iframeurl'];
            $templateTheme = apply_filters('ivav_template_theme', $this->templateTheme);

# BF-CUSTOM: Set the template Theme at the end of the URL
            if ($templateTheme != '') {
                $this->iframe .= '/' . $templateTheme;
#error_log('IV-AV: profile Templated iframe URL: ' . $this->iframe);
            }
# /BF-CUSTOM

            $customerMessage = $this->customerMessage;
            if ($this->thankyouInline == 'inline') {
                $msg = "
                <script>jQuery(document).ready(function() { showInline(\"$this->iframe\");});</script>
                ";
            } else {
                $description = "Unverified. To complete an order, <a href='#' style='cursor: pointer;' onclick='return showPopup(\"$this->iframe\")'>click here</a> to verify your age.</p>";
                if (isset($attributes['unverified'])) {
                    $description = "<a href='#' style='cursor: pointer;' onclick='return showPopup(\"$this->iframe\")'>{$attributes[unverified]}</a>";
                }

                $msg = "<div id='ageVerifyMessage'>
                <p><span style='color: red;' class='dashicons dashicons-warning'></span>$description</p>
                </div>";
            }
        }

        return $msg;
    }
}
