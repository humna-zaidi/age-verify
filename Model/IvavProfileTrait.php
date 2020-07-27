<?php
trait IvavProfileTrait {

    public function ivav_profile_shortcode($attributes, $content = null)
    {
        $this->ivav_setup_js_internal();

        return $this->ivav_edit_profile_action($attributes);
    }

    public function ivav_iframe_shortcode($attributes, $content = null)
    {
        $this->ivav_setup_js_internal();

        $userId    = get_current_user_id();
        $av = IvavAgeVerify::load($userId, null);

        if ($av->isVerified == true) {
           return "";
        }

        $ip     = $_SERVER['REMOTE_ADDR'];
        $user = wp_get_current_user();
        $data = [];
        if ($user !== null) {
            $data['firstname'] = $user->first_name;
            $data['lastname']  = $user->last_name;
        }
        $r    = $this->ivav_api_create($userId, $ip, 'profile', $av->guidTmp, $data);
        $guid = $r['request_guid'];
        if ($userId > 0 && $guid != $av->guidTmp) {
            $av->guidTmp = $guid;
            $av->save($userId);
        }

        $this->iframe = $r['iframeurl'];
        $iframe = "<iframe src='" . $r['iframeurl'] . "'";
        foreach ($attributes as $k => $v) {
            $iframe = $iframe . " $k='$v'";
        }
        $iframe = $iframe . ">";

        return $iframe;
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
            $guid = $r['request_guid'];
            if ($userId > 0 && $guid != $av->guidTmp) {
                $av->guidTmp = $guid;
                $av->save($userId);
            }

            $this->iframe = $r['iframeurl'];
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

                $msg = "
                <p><span style='color: red;' class='dashicons dashicons-warning'></span>$description</p>
                ";
            }
        }

        return $msg;
    }
}
