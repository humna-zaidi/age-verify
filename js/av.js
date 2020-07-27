/*

*/
var IVAV_IS_DEV         = 1;
var IVAV_IS_STAGE       = 2;
var IVAV_ALLOW_EMAIL    = 4;
var IVAV_ALLOW_PHONE    = 8;
var IVAV_FORCE_BILLING  = 16;
var IVAV_FORCE_SHIPPING = 32;
var IVAV_IS_SANDBOX     = 64;

if ( ! window.console ) console = { log: function(){} };

function checkoutListener(event)
{
	if (check_origin(event.origin)) {
		var data = event.data;
		if (data.verified == 1) {
			jQuery('#av-overlay').remove();
			jQuery('#av-popup').remove();
			jQuery('form[name=checkout]').prepend('<input type="hidden" name="ivav-similarity" value="' + data.similarity + '" />');
			jQuery('form[name=checkout]').prepend('<input type="hidden" name="ivav-guid" value="' + data.guid + '" />');
			jQuery('form[name=checkout]').prepend('<input type="hidden" name="ivav-birthdate" value="' + data.dateofbirth + '" />');
			jQuery('form[name=checkout]').prepend('<input type="hidden" name="ivav-age" value="' + data.age + '" />');
			jQuery('form[name=checkout]').prepend('<input type="hidden" name="ivav-status" value="Approved" />');
			jQuery('form[name=checkout]').prepend('<input type="hidden" name="ivav-firstname" value="' + data.firstname + '" />');
			jQuery('form[name=checkout]').prepend('<input type="hidden" name="ivav-lastname" value="' + data.lastname + '" />');

			jQuery("#place_order").trigger("click");

			if (forceBilling)
			{
				jQuery('#billing_first_name').val(data.firstname);
				jQuery('#billing_first_name').attr('readonly', true);
				jQuery('#billing_last_name').val(data.lastname);
				jQuery('#billing_last_name').attr('readonly', true);

			}
			if (forceShipping)
			{
				jQuery('#shipping_first_name').val(data.firstname);
				jQuery('#shipping_first_name').attr('readonly', true);
				jQuery('#shipping_last_name').val(data.lastname);
				jQuery('#shipping_last_name').attr('readonly', true);
			}
		}
		else {
			jQuery('#av-overlay').remove();
			jQuery('#av-popup').remove();
			jQuery('.woocommerce-error').show();
			jQuery('.woocommerce-error').prepend('<li><strong>Sorry, we are unable to verify your age.</strong></li>');
		   console.log('IV-AV: failure');
		}
		return;
	}
}

function thankyouListener(event)
{
	if (event.origin == "https://sandbox.inverite.com"  ||
		  event.origin == "https://i1.inverite.com" ||
		  event.origin == "https://live.inverite.com" ||
		  event.origin == "https://www.inverite.com" ) {
		var data = event.data;
		jQuery('#av-overlay').remove();
		jQuery('#av-popup').remove();

		if (data.verified == 1) {
			var message = '<p><span style="color: green;" class="dashicons dashicons-yes"></span>Thank you for verifying your age!</p>';
			if (forceShipping || forceBilling) {
				var description = (forceShipping) ? 'shipping' : 'billing';
				message = message + '<p>Your ' + description + ' name will be set to: ' + data.firstname + ' ' + data.lastname + '</p>';
			}

      jQuery('#ageVerifyMessage').html(message);
			jQuery('form[name=checkout]').prepend('<input type="hidden" name="ivav-similarity" value="' + data.similarity + '" />');
			jQuery('form[name=checkout]').prepend('<input type="hidden" name="ivav-guid" value="' + data.guid + '" />');
			jQuery('form[name=checkout]').prepend('<input type="hidden" name="ivav-birthdate" value="' + data.dateofbirth + '" />');
			jQuery('form[name=checkout]').prepend('<input type="hidden" name="ivav-age" value="' + data.age + '" />');
			jQuery('form[name=checkout]').prepend('<input type="hidden" name="ivav-status" value="Approved" />');
			jQuery('form[name=checkout]').prepend('<input type="hidden" name="ivav-firstname" value="' + data.firstname + '" />');
			jQuery('form[name=checkout]').prepend('<input type="hidden" name="ivav-lastname" value="' + data.lastname + '" />');
    } else {
      console.log('IV-AV: fail on mode ' + mode);
      if (mode == 'profile') {
        if (data.reason == 'namematch_failure') {
          jQuery('#ageVerifyMessage').html('<p>Sorry, we were unable to verify your name.  Please update your account name to: ' + data.firstname + ' ' + data.lastname + '</p>');
        } else if (data.reason == 'age_failure') {
          jQuery('#ageVerifyMessage').html('<p>Sorry, we were unable to verify your age.</p>');
        }
      } else if (mode == 'thankyou') {
        if (data.reason == 'namematch_failure') {
          jQuery('#ageVerifyMessage').html('<p>Sorry, we were unable to verify your name.  Please create a new order using: ' + data.firstname + ' ' + data.lastname + '</p>');
        } else if (data.reason == 'age_failure') {
          jQuery('#ageVerifyMessage').html('<p>Fail Thankyou Age</p>');
        }
      }
    }
  }
}

function showInline(url)
{
  jQuery('#ageVerifyMessage').empty().append(
  '<iframe src="' + url + '" style="top: 10; border-style: solid; border-width: 1px; border-color: #ddd; border-radius: 3px; margin:0; padding:0; width: 100%; height: 700px;" /></iframe>' +
  '</div>');
  jQuery('html, body').stop();
  jQuery('html, body').scrollTop(0);
}

function showPopup(url)
{
  jQuery('body').prepend(getIframe(url));
  jQuery('html, body').stop();
  jQuery('html, body').scrollTop(0);
}

function getIframe(url)
{
   if (url == undefined) {
      // Default for old checkout mode
      url = hostname + '/customer/web/create?site=' + siteName + '&referenceid=' + reference + '&username=' + siteName + '_checkout_' + reference + '&email=' + email + '&phone=' + phone;
   }
   var s =
   '<div id="av-overlay" style="width: 100%; min-height: 100%; background-color: #000; opacity: 0.5; left: 0; right: 0; position: fixed; z-index: 111; overflow: hidden;"></div>' +
   '<div id="av-popup" style="left: 10%; top: 10%; width: 80%; height: 80%; background-color: #FFF; z-index: 200; position:absolute; ">';

   if (mode == 'profile' || siteKey == 'ca15f8bc2637626660651c02bd2f9c17' || siteKey == '5665acdd9a7d7d88cf16ac72bfb3bd65') {
	   s = s + '<span id="closeWindow" class=" dashicons dashicons-no" style="position: absolute; left: 100%; z-index: 300; background: #fff; width: 20px; height: 20px" onclick="jQuery(\'#av-overlay\').remove(); jQuery(\'#av-popup\').remove();"></span>';
   }
   s = s + '<div style="margin-left: auto; margin-right: auto; margin-top: 10px;  padding: 5px; font-size: 13px; color: #676a6c; border-style: solid; border-width: 1px; border-color: #ddd; border-radius: 3px; max-width: 1024px;">' + customerMsg + '</div>' +
   '<iframe src="' + url + '" style="top: 10; border:none; margin:0; padding:0; width: 100%; height: 100%;" /></iframe>' +
   '</div>';

   return s;
}
var me = document.currentScript || (function() {
     var scripts = document.getElementsByTagName('script');
       return scripts[scripts.length - 1];
})();
var siteName      = me.getAttribute('data-site-name');
var siteKey       = me.getAttribute('data-site-key');
var mode          = me.getAttribute('data-mode');
var config        = me.getAttribute('data-config');
var reference     = me.getAttribute('data-reference');
var customerMsg   = decodeURIComponent((me.getAttribute('data-msg')+'').replace(/\+/g, '%20'));
var allowEmail    = (config & IVAV_ALLOW_EMAIL) == IVAV_ALLOW_EMAIL;
var allowPhone    = (config & IVAV_ALLOW_PHONE) == IVAV_ALLOW_PHONE;
var isDev         = (config & IVAV_IS_DEV) == IVAV_IS_DEV;
var isStage       = (config & IVAV_IS_STAGE) == IVAV_IS_STAGE;
var isSandbox     = (config & IVAV_IS_SANDBOX) == IVAV_IS_SANDBOX;
var forceBilling  = (config & IVAV_FORCE_BILLING) == IVAV_FORCE_BILLING;
var forceShipping = (config & IVAV_FORCE_SHIPPING) == IVAV_FORCE_SHIPPING;
var email         = '';
var phone         = '';
var firstName     = '';
var lastName      = '';
var hostname      = 'https://www.inverite.com';
if (isDev) {
	hostname = 'https://i1.inverite.com';
} else if (isStage) {
	hostname = 'https://live.inverite.com';
} else if (isSandbox) {
   hostname = 'https://sandbox.inverite.com';
}

jQuery(document).ready(function() {
  if (mode == 'checkout') {
    if (window.addEventListener) {
      addEventListener("message", checkoutListener, false);
    } else {
      attachEvent("onmessage", checkoutListener);
    }
  }
  else if (mode == 'thankyou' || mode == 'precheckout' || mode == 'profile') {
    jQuery('#ageVerifyMessage').insertAfter(jQuery('.woocommerce-thankyou-order-received'));
    if (window.addEventListener) {
        addEventListener("message", thankyouListener, false);
    } else {
        attachEvent("onmessage", thankyouListener);
    }
  }

	jQuery(document.body).on('checkout_error', function() {
		var count = jQuery('.woocommerce-error').find('li').length;
		var eventElement = jQuery('.woocommerce-error').find('div').first();
		var eventText = eventElement.attr('id');

		if (eventText == 'ivav_popup') {
			eventElement.hide();
			if (count == 1) {
				jQuery('.woocommerce-error').hide();
				var email = '';
				var firstName = '';
				var lastName = '';
				var phone = '';
				try {
					firstName = encodeURIComponent(jQuery('#billing_first_name').val());
					lastName  = encodeURIComponent(jQuery('#billing_last_name').val());
					if (allowEmail) {
						email     = encodeURIComponent(jQuery('#billing_email').val());
					}
					if (allowPhone) {
						phone     = encodeURIComponent(jQuery('#billing_phone').val());
					}
				}
				catch (e) {
					console.log('IV-AV: exception=' + e.message);
				}
                showPopup();
			}
		}
	});
});
