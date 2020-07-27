<?php
if ( ! defined( 'ABSPATH' ) ) {
        exit;
}

do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

Your order is currently on hold.

You must verify you age by clicking <a href='<?php echo $url ?>'>here</a> to complete your order.

<?php
do_action( 'woocommerce_email_footer', $email );
?>

