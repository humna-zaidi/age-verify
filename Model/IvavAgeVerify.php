<?php
class IvavAgeVerify
{
    private $age;
    private $status;

    public static function load($userId = null, $orderId = null)
    {
    
        $av = new IvavAgeVerify();
        $av->loadByReference($userId, $orderId);
        return $av;
    }

    public static function clear($userId = null, $orderId = null)
    {
        if ($userId > 0) {
            delete_user_meta($userId, 'ivav-guid');
            delete_user_meta($userId, 'ivav-status');
            delete_user_meta($userId, 'ivav-date');
            delete_user_meta($userId, 'ivav-birthdate');
            delete_user_meta($userId, 'ivav-age');
            delete_user_meta($userId, 'ivav-firstname');
            delete_user_meta($userId, 'ivav-lastname');
            delete_user_meta($userId, 'ivav-gender');
            delete_user_meta($userId, 'ivav-address');
            delete_user_meta($userId, 'ivav-city');
            delete_user_meta($userId, 'ivav-province');
            delete_user_meta($userId, 'ivav-postal');
            delete_user_meta($userId, 'ivav-reason');
            delete_user_meta($userId, 'ivav-guid-tmp');
            delete_user_meta($userId, 'ivav-similiarity');
            delete_user_meta($userId, 'ivav-referenceid');
        }
        if ($orderId > 0) {
            delete_post_meta($orderId, 'ivav-guid');
            delete_post_meta($orderId, 'ivav-status');
            delete_post_meta($orderId, 'ivav-date');
            delete_post_meta($orderId, 'ivav-birthdate');
            delete_post_meta($orderId, 'ivav-age');
            delete_post_meta($orderId, 'ivav-firstname');
            delete_post_meta($orderId, 'ivav-lastname');
            delete_user_meta($orderId, 'ivav-gender');
            delete_user_meta($orderId, 'ivav-address');
            delete_user_meta($orderId, 'ivav-city');
            delete_user_meta($orderId, 'ivav-province');
            delete_user_meta($orderId, 'ivav-postal');
            delete_post_meta($orderId, 'ivav-reason');
            delete_post_meta($orderId, 'ivav-guid-tmp');
            delete_post_meta($orderId, 'ivav-similiarity');
            delete_post_meta($orderId, 'ivav-referenceid');
        }
    }


    public static function verify($userId = null, $orderId = null) {

        IvavAgeVerify::clear($userId, $orderId);
        $av = new IvavAgeVerify();
        $av->guid   = 'manual';
        $av->date   = date('Y-m-d');
        $av->status = 'Approved';
        $av->save($userId, $orderId);
    }

    public function __get($name)
    {
        return $this->$name;
    }

    public function __set($name, $value)
    {
        $this->$name = $value;
    }

    private function loadByReference($userId = null, $orderId = null)
    {
        if ($userId > 0) {
            $this->guid        = get_user_meta($userId, 'ivav-guid', true);
            $this->guidTmp     = get_user_meta($userId, 'ivav-guid-tmp', true);
            $this->age         = get_user_meta($userId, 'ivav-age', true);
            $this->birthdate   = get_user_meta($userId, 'ivav-birthdate', true);
            $this->status      = get_user_meta($userId, 'ivav-status', true);
            $this->reason      = get_user_meta($userId, 'ivav-reason', true);
            $this->firstName   = get_user_meta($userId, 'ivav-firstname', true);
            $this->lastName    = get_user_meta($userId, 'ivav-lastname', true);
            $this->gender      = get_user_meta($userId, 'ivav-gender', true);
            $this->address     = get_user_meta($userId, 'ivav-address', true);
            $this->city        = get_user_meta($userId, 'ivav-city', true);
            $this->province    = get_user_meta($userId, 'ivav-province', true);
            $this->postal      = get_user_meta($userId, 'ivav-postal', true);
            $this->date        = get_user_meta($userId, 'ivav-date', true);
            $this->similiarity = get_user_meta($userId, 'ivav-similiarity', true);
            $this->referenceId = get_user_meta($userId, 'ivav-referenceid', true);

        } elseif ($orderId > 0) {
            $this->guid        = get_post_meta($orderId, 'ivav-guid', true);
            $this->guidTmp     = get_post_meta($orderId, 'ivav-guid-tmp', true);
            $this->age         = get_post_meta($orderId, 'ivav-age', true);
            $this->birthdate   = get_post_meta($orderId, 'ivav-birthdate', true);
            $this->status      = get_post_meta($orderId, 'ivav-status', true);
            $this->reason      = get_post_meta($orderId, 'ivav-reason', true);
            $this->firstName   = get_post_meta($orderId, 'ivav-firstname', true);
            $this->lastName    = get_post_meta($orderId, 'ivav-lastname', true);
            $this->gender      = get_post_meta($orderId, 'ivav-gender', true);
            $this->address     = get_post_meta($orderId, 'ivav-address', true);
            $this->city        = get_post_meta($orderId, 'ivav-city', true);
            $this->province    = get_post_meta($orderId, 'ivav-province', true);
            $this->postal      = get_post_meta($orderId, 'ivav-postal', true);
            $this->date        = get_post_meta($orderId, 'ivav-date', true);
            $this->similiarity = get_post_meta($orderId, 'ivav-similiarity', true);
            $this->referenceId = get_post_meta($orderId, 'ivav-referenceid', true);
        }
    }

    public function save($userId = null, $orderId = null)
    {



        if ($userId > 0)
        {

            update_user_meta($userId, 'ivav-guid',        $this->guid);
            update_user_meta($userId, 'ivav-guid-tmp',    $this->guidTmp);
            update_user_meta($userId, 'ivav-age',         $this->age);
            update_user_meta($userId, 'ivav-birthdate',   $this->birthdate);
            update_user_meta($userId, 'ivav-status',      $this->status);
            update_user_meta($userId, 'ivav-reason',      $this->reason);
            update_user_meta($userId, 'ivav-firstname',   $this->firstName);
            update_user_meta($userId, 'ivav-lastname',    $this->lastName);
            update_user_meta($userId, 'ivav-gender',      $this->gender);
            update_user_meta($userId, 'ivav-address',     $this->address);
            update_user_meta($userId, 'ivav-city',        $this->city);
            update_user_meta($userId, 'ivav-province',    $this->province);
            update_user_meta($userId, 'ivav-postal',      $this->postal);
            update_user_meta($userId, 'ivav-date',        $this->date);
            update_user_meta($userId, 'ivav-similiarity', $this->similiarity);
            update_user_meta($userId, 'ivav-referenceid', $this->referenceid);
        }
        if ($orderId > 0)
        {
            update_post_meta($orderId, 'ivav-guid',        $this->guid);
            update_post_meta($orderId, 'ivav-guid-tmp',    $this->guidTmp);
            update_post_meta($orderId, 'ivav-age',         $this->age);
            update_post_meta($orderId, 'ivav-date',        $this->date);
            update_post_meta($orderId, 'ivav-birthdate',   $this->birthdate);
            update_post_meta($orderId, 'ivav-status',      $this->status);
            update_post_meta($orderId, 'ivav-reason',      $this->reason);
            update_post_meta($orderId, 'ivav-firstname',   $this->firstName);
            update_post_meta($orderId, 'ivav-lastname',    $this->lastName);
            update_post_meta($orderId, 'ivav-gender',      $this->gender);
            update_post_meta($orderId, 'ivav-address',     $this->address);
            update_post_meta($orderId, 'ivav-city',        $this->city);
            update_post_meta($orderId, 'ivav-province',    $this->province);
            update_post_meta($orderId, 'ivav-postal',      $this->postal);
            update_post_meta($orderId, 'ivav-similiarity', $this->similiarity);
            update_post_meta($orderId, 'ivav-referenceid', $this->referenceId);
        }
    }

    public function isVerified() {
        if ($this->guid != '' && $this->status != 'Denied') {
            return true;
        }
        return false;
    }

    public function isDenied() {
        if ($this->guid != '' && $this->status == 'Denied') {
            return true;
        }
        return false;
    }

    public function isManual() {
        if ($this->guid == 'manual') {
            return true;
        }
        return false;
    }

    public function isNameMatch($firstName, $lastName)
    {
        if ($av->guid == 'manual') {
            return true;
        } elseif ($firstName != '' && $lastName != '' && (strtolower($firstName) != strtolower(strtok($this->firstName, ' ')) || strtolower($lastName) != strtolower($this->lastName))) {
            return false;
        }
        return true;
    }
}
