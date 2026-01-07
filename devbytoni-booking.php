<?php
/**
 * Plugin Name:       DevByToni Booking Pro
 * Plugin URI:        https://github.com/anthonyagughasi/devbytoni-booking
 * Description:       Advanced booking system with time slots, PayPal payments, and admin calendar view.
 * Version:           2.0.0
 * Author:            DevByToni
 * License:           GPL-2.0+
 * Text Domain:       devbytoni-booking
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Register CPT
function dbt_register_booking_cpt() {
    register_post_type( 'dbt_booking', array(
        'labels'      => array(
            'name'          => __( 'Bookings', 'devbytoni-booking' ),
            'singular_name' => __( 'Booking', 'devbytoni-booking' ),
            'menu_name'     => __( 'Bookings', 'devbytoni-booking' ),
            'all_items'     => __( 'All Bookings', 'devbytoni-booking' ),
        ),
        'public'      => false,
        'show_ui'     => true,
        'supports'    => array( 'title' ),
        'menu_icon'   => 'dashicons-calendar-alt',
    ) );
}
add_action( 'init', 'dbt_register_booking_cpt' );

// Add meta boxes
function dbt_add_meta_boxes() {
    add_meta_box( 'dbt_details', __( 'Booking Details', 'devbytoni-booking' ), 'dbt_render_details_meta', 'dbt_booking', 'normal', 'high' );
    add_meta_box( 'dbt_payment', __( 'Payment Status', 'devbytoni-booking' ), 'dbt_render_payment_meta', 'dbt_booking', 'side' );
}
add_action( 'add_meta_boxes', 'dbt_add_meta_boxes' );

function dbt_render_details_meta( $post ) {
    $name    = get_post_meta( $post->ID, '_dbt_name', true );
    $email   = get_post_meta( $post->ID, '_dbt_email', true );
    $phone   = get_post_meta( $post->ID, '_dbt_phone', true );
    $date    = get_post_meta( $post->ID, '_dbt_date', true );
    $time    = get_post_meta( $post->ID, '_dbt_time', true );
    $message = get_post_meta( $post->ID, '_dbt_message', true );
    $status  = get_post_meta( $post->ID, '_dbt_payment_status', true );
    ?>
    <p><strong>Name:</strong> <?php echo esc_html( $name ); ?></p>
    <p><strong>Email:</strong> <?php echo esc_html( $email ); ?></p>
    <p><strong>Phone:</strong> <?php echo esc_html( $phone ); ?></p>
    <p><strong>Date:</strong> <?php echo esc_html( $date ); ?></p>
    <p><strong>Time:</strong> <?php echo esc_html( $time ); ?></p>
    <p><strong>Message:</strong> <?php echo esc_html( $message ); ?></p>
    <?php
}

function dbt_render_payment_meta( $post ) {
    $status = get_post_meta( $post->ID, '_dbt_payment_status', true );
    $txn_id = get_post_meta( $post->ID, '_dbt_txn_id', true );
    echo '<p><strong>Status:</strong> ' . esc_html( ucfirst( $status ?: 'pending' ) ) . '</p>';
    if ( $txn_id ) echo '<p><strong>Txn ID:</strong> ' . esc_html( $txn_id ) . '</p>';
}

// Settings
function dbt_register_settings() {
    register_setting( 'dbt_booking_options', 'dbt_admin_email' );
    register_setting( 'dbt_booking_options', 'dbt_paypal_email' );
    register_setting( 'dbt_booking_options', 'dbt_booking_fee' );
    register_setting( 'dbt_booking_options', 'dbt_time_slots' );
    register_setting( 'dbt_booking_options', 'dbt_recaptcha_site_key' );
    register_setting( 'dbt_booking_options', 'dbt_recaptcha_secret_key' );
}
add_action( 'admin_init', 'dbt_register_settings' );

function dbt_settings_menu() {
    add_options_page( __( 'Booking Settings', 'devbytoni-booking' ), __( 'Booking Settings', 'devbytoni-booking' ), 'manage_options', 'dbt-settings', 'dbt_settings_page' );
}
add_action( 'admin_menu', 'dbt_settings_menu' );

function dbt_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php _e( 'DevByToni Booking Settings', 'devbytoni-booking' ); ?></h1>
        <form method="post" action="options.php">
            <?php settings_fields( 'dbt_booking_options' ); ?>
            <h2><?php _e( 'General', 'devbytoni-booking' ); ?></h2>
            <table class="form-table">
                <tr>
                    <th><?php _e( 'Admin Email', 'devbytoni-booking' ); ?></th>
                    <td><input type="email" name="dbt_admin_email" value="<?php echo esc_attr( get_option('dbt_admin_email', get_option('admin_email')) ); ?>" class="regular-text" required /></td>
                </tr>
                <tr>
                    <th><?php _e( 'Booking Fee (USD)', 'devbytoni-booking' ); ?></th>
                    <td><input type="number" step="0.01" name="dbt_booking_fee" value="<?php echo esc_attr( get_option('dbt_booking_fee', '50.00') ); ?>" /></td>
                </tr>
            </table>

            <h2><?php _e( 'PayPal', 'devbytoni-booking' ); ?></h2>
            <table class="form-table">
                <tr>
                    <th><?php _e( 'PayPal Business Email', 'devbytoni-booking' ); ?></th>
                    <td><input type="email" name="dbt_paypal_email" value="<?php echo esc_attr( get_option('dbt_paypal_email') ); ?>" class="regular-text" required /></td>
                </tr>
            </table>

            <h2><?php _e( 'Time Slots (one per line, format HH:MM)', 'devbytoni-booking' ); ?></h2>
            <textarea name="dbt_time_slots" rows="8" class="large-text"><?php echo esc_textarea( get_option('dbt_time_slots', "09:00\n10:30\n14:00\n15:30\n17:00") ); ?></textarea>

            <h2><?php _e( 'reCAPTCHA (optional)', 'devbytoni-booking' ); ?></h2>
            <table class="form-table">
                <tr><th>Site Key</th><td><input type="text" name="dbt_recaptcha_site_key" value="<?php echo esc_attr( get_option('dbt_recaptcha_site_key') ); ?>" class="regular-text" /></td></tr>
                <tr><th>Secret Key</th><td><input type="text" name="dbt_recaptcha_secret_key" value="<?php echo esc_attr( get_option('dbt_recaptcha_secret_key') ); ?>" class="regular-text" /></td></tr>
            </table>

            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Admin Calendar Page
function dbt_calendar_menu() {
    add_submenu_page( 'edit.php?post_type=dbt_booking', __( 'Calendar', 'devbytoni-booking' ), __( 'Calendar', 'devbytoni-booking' ), 'manage_options', 'dbt-calendar', 'dbt_calendar_page' );
}
add_action( 'admin_menu', 'dbt_calendar_menu' );

function dbt_calendar_page() {
    $year  = isset($_GET['y']) ? intval($_GET['y']) : date('Y');
    $month = isset($_GET['m']) ? intval($_GET['m']) : date('n');
    $day   = cal_days_in_month(CAL_GREGORIAN, $month, $year);

    $bookings = get_posts(array(
        'post_type'   => 'dbt_booking',
        'post_status' => 'private',
        'numberposts' => -1,
        'meta_query'  => array(
            array('key' => '_dbt_date', 'value' => "$year-" . sprintf('%02d', $month), 'compare' => 'LIKE')
        )
    ));

    $booked = array();
    foreach ($bookings as $b) {
        $d = get_post_meta($b->ID, '_dbt_date', true);
        $t = get_post_meta($b->ID, '_dbt_time', true);
        $n = get_post_meta($b->ID, '_dbt_name', true);
        $s = get_post_meta($b->ID, '_dbt_payment_status', true) ?: 'pending';
        $booked[$d][] = "$t - $n (" . ucfirst($s) . ")";
    }

    echo '<div class="wrap"><h1>' . esc_html(date_i18n('F Y', mktime(0,0,0,$month,1,$year))) . '</h1>';
    echo '<nav><a href="?post_type=dbt_booking&page=dbt-calendar&m=' . ($month-1 ? $month-1 : 12) . '&y=' . ($month-1 ? $year : $year-1) . '">« Prev</a> | ';
    echo '<a href="?post_type=dbt_booking&page=dbt-calendar&m=' . ($month==12 ? 1 : $month+1) . '&y=' . ($month==12 ? $year+1 : $year) . '">Next »</a></nav>';

    echo '<table class="wp-list-table widefat fixed striped" style="margin-top:20px;"><thead><tr><th>Sun</th><th>Mon</th><th>Tue</th><th>Wed</th><th>Thu</th><th>Fri</th><th>Sat</th></tr></thead><tbody><tr>';

    $first = date('w', mktime(0,0,0,$month,1,$year));
    for ($i = 0; $i < $first; $i++) echo '<td></td>';

    for ($d = 1; $d <= $day; $d++) {
        $date_str = "$year-" . sprintf('%02d', $month) . "-" . sprintf('%02d', $d);
        $current = ($i + $d) % 7 == 0 ? '</tr><tr>' : '';
        echo '<td style="vertical-align:top; height:100px;"><strong>' . $d . '</strong><br>';
        if (isset($booked[$date_str])) {
            foreach ($booked[$date_str] as $slot) {
                echo '<div style="font-size:11px; margin:2px 0;">' . esc_html($slot) . '</div>';
            }
        }
        echo '</td>' . $current;
    }
    echo '</tr></tbody></table></div>';
}

// Frontend Assets
function dbt_frontend_assets() {
    wp_enqueue_script( 'jquery' );
    wp_enqueue_script( 'jquery-ui-datepicker' );
    wp_enqueue_style( 'jquery-ui-css', '//code.jquery.com/ui/1.12.1/themes/smoothness/jquery-ui.css' );

    $site_key = get_option('dbt_recaptcha_site_key');
    if ($site_key) wp_enqueue_script('google-recaptcha', 'https://www.google.com/recaptcha/api.js', array(), null, true);
}
add_action( 'wp_enqueue_scripts', 'dbt_frontend_assets' );

// Shortcode Form
function dbt_booking_form_shortcode() {
    ob_start();
    $slots = preg_split('/\r\n|\r|\n/', trim(get_option('dbt_time_slots', "09:00\n10:30\n14:00")));
    $fee = floatval(get_option('dbt_booking_fee', 0));
    $paypal_email = get_option('dbt_paypal_email');
    $site_key = get_option('dbt_recaptcha_site_key');
    ?>
    <style>
        #dbt-booking-form label { display:block; margin:10px 0 5px; }
        #dbt-booking-form input, #dbt-booking-form select, #dbt-booking-form textarea { width:100%; max-width:400px; padding:8px; }
        #dbt-form-response { margin-top:15px; font-weight:bold; }
    </style>

    <form id="dbt-booking-form">
        <?php wp_nonce_field('dbt_submit_booking', 'dbt_nonce'); ?>
        <p><label><?php _e('Name *', 'devbytoni-booking'); ?><br><input type="text" name="dbt_name" required></label></p>
        <p><label><?php _e('Email *', 'devbytoni-booking'); ?><br><input type="email" name="dbt_email" required></label></p>
        <p><label><?php _e('Phone', 'devbytoni-booking'); ?><br><input type="text" name="dbt_phone"></label></p>
        <p><label><?php _e('Date *', 'devbytoni-booking'); ?><br><input type="text" id="dbt_datepicker" name="dbt_date" required readonly></label></p>
        <p><label><?php _e('Time Slot *', 'devbytoni-booking'); ?><br>
            <select name="dbt_time" required>
                <option value=""><?php _e('Select time', 'devbytoni-booking'); ?></option>
                <?php foreach ($slots as $slot): ?>
                    <option value="<?php echo esc_attr(trim($slot)); ?>"><?php echo esc_html(trim($slot)); ?></option>
                <?php endforeach; ?>
            </select>
        </label></p>
        <p><label><?php _e('Message', 'devbytoni-booking'); ?><br><textarea name="dbt_message"></textarea></label></p>

        <?php if ($site_key): ?>
            <div class="g-recaptcha" data-sitekey="<?php echo esc_attr($site_key); ?>" style="margin:15px 0;"></div>
        <?php endif; ?>

        <?php if ($fee > 0): ?>
            <p><strong><?php printf(__('Booking Fee: $%s (via PayPal)', 'devbytoni-booking'), number_format($fee, 2)); ?></strong></p>
        <?php endif; ?>

        <p><input type="submit" value="<?php _e('Book Now', 'devbytoni-booking'); ?>"></p>
        <div id="dbt-form-response"></div>
    </form>

    <script>
    jQuery(function($) {
        $("#dbt_datepicker").datepicker({
            dateFormat: "yy-mm-dd",
            minDate: 0
        });

        $('#dbt-booking-form').on('submit', function(e) {
            e.preventDefault();
            $('#dbt-form-response').html('<p>Processing...</p>');

            var data = $(this).serialize();
            <?php if ($site_key): ?>
            data += '&g-recaptcha-response=' + grecaptcha.getResponse();
            <?php endif; ?>

            $.post('<?php echo admin_url('admin-ajax.php'); ?>', data + '&action=dbt_submit_booking', function(res) {
                if (res.success) {
                    <?php if ($fee > 0 && $paypal_email): ?>
                        // Redirect to PayPal
                        var ppForm = $('<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">');
                        ppForm.append('<input type="hidden" name="cmd" value="_xclick">');
                        ppForm.append('<input type="hidden" name="business" value="<?php echo esc_attr($paypal_email); ?>">');
                        ppForm.append('<input type="hidden" name="item_name" value="Booking on ' + $('input[name=dbt_date]').val() + ' at ' + $('select[name=dbt_time]').val() + '">');
                        ppForm.append('<input type="hidden" name="amount" value="<?php echo $fee; ?>">');
                        ppForm.append('<input type="hidden" name="currency_code" value="USD">');
                        ppForm.append('<input type="hidden" name="custom" value="' + res.data.booking_id + '">');
                        ppForm.append('<input type="hidden" name="return" value="<?php echo home_url('/booking-success'); ?>">');
                        ppForm.append('<input type="hidden" name="cancel_return" value="<?php echo home_url('/booking-cancel'); ?>">');
                        ppForm.append('<input type="hidden" name="notify_url" value="<?php echo home_url('/?dbt_paypal_ipn=1'); ?>">');
                        $('body').append(ppForm);
                        ppForm.submit();
                    <?php else: ?>
                        $('#dbt-form-response').html('<p style="color:green;">' + res.data.message + '</p>');
                        $('#dbt-booking-form')[0].reset();
                    <?php endif; ?>
                } else {
                    $('#dbt-form-response').html('<p style="color:red;">' + res.data + '</p>');
                    <?php if ($site_key): ?>grecaptcha.reset();<?php endif; ?>
                }
            });
        });
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('devbytoni_booking_form', 'dbt_booking_form_shortcode');

// AJAX Submission
function dbt_handle_booking_submission() {
    check_ajax_referer('dbt_submit_booking', 'dbt_nonce');

    // reCAPTCHA
    $secret = get_option('dbt_recaptcha_secret_key');
    if ($secret && !empty($_POST['g-recaptcha-response'])) {
        $verify = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', array(
            'body' => array('secret' => $secret, 'response' => $_POST['g-recaptcha-response'])
        ));
        $result = json_decode(wp_remote_retrieve_body($verify));
        if (!$result->success) wp_send_json_error('reCAPTCHA failed');
    }

    $name = sanitize_text_field($_POST['dbt_name']);
    $email = sanitize_email($_POST['dbt_email']);
    $phone = sanitize_text_field($_POST['dbt_phone']);
    $date = sanitize_text_field($_POST['dbt_date']);
    $time = sanitize_text_field($_POST['dbt_time']);
    $message = sanitize_textarea_field($_POST['dbt_message']);

    if (empty($name) || empty($email) || empty($date) || empty($time) || !is_email($email)) {
        wp_send_json_error('Please fill all required fields correctly.');
    }

    // Check availability
    $existing = get_posts(array(
        'post_type' => 'dbt_booking',
        'meta_query' => array(
            array('key' => '_dbt_date', 'value' => $date),
            array('key' => '_dbt_time', 'value' => $time)
        ),
        'post_status' => 'any',
        'numberposts' => 1
    ));

    if (!empty($existing)) {
        wp_send_json_error('Sorry, this date and time is already booked.');
    }

    // Create pending booking
    $post_id = wp_insert_post(array(
        'post_title'   => "Booking: $name - $date $time",
        'post_type'    => 'dbt_booking',
        'post_status'  => 'private'
    ));

    update_post_meta($post_id, '_dbt_name', $name);
    update_post_meta($post_id, '_dbt_email', $email);
    update_post_meta($post_id, '_dbt_phone', $phone);
    update_post_meta($post_id, '_dbt_date', $date);
    update_post_meta($post_id, '_dbt_time', $time);
    update_post_meta($post_id, '_dbt_message', $message);
    update_post_meta($post_id, '_dbt_payment_status', 'pending');

    if (floatval(get_option('dbt_booking_fee', 0)) == 0) {
        update_post_meta($post_id, '_dbt_payment_status', 'completed');
        wp_mail($email, 'Booking Confirmed', 'Your booking is confirmed!');
        wp_mail(get_option('dbt_admin_email'), 'New Free Booking', "New booking from $name on $date at $time");
        wp_send_json_success(array('message' => 'Booking confirmed!'));
    } else {
        wp_send_json_success(array('booking_id' => $post_id));
    }
}
add_action('wp_ajax_nopriv_dbt_submit_booking', 'dbt_handle_booking_submission');
add_action('wp_ajax_dbt_submit_booking', 'dbt_handle_booking_submission');

// PayPal IPN Listener
add_action('init', function() {
    if (isset($_GET['dbt_paypal_ipn'])) {
        $raw_post = file_get_contents('php://input');
        $req = 'cmd=_notify-validate&' . $raw_post;
        $res = wp_remote_post('https://www.paypal.com/cgi-bin/webscr', array('body' => $req, 'timeout' => 30));

        if (wp_remote_retrieve_body($res) === 'VERIFIED') {
            if ($_POST['payment_status'] === 'Completed' && $_POST['receiver_email'] === get_option('dbt_paypal_email')) {
                $booking_id = intval($_POST['custom']);
                update_post_meta($booking_id, '_dbt_payment_status', 'completed');
                update_post_meta($booking_id, '_dbt_txn_id', sanitize_text_field($_POST['txn_id']));

                $email = get_post_meta($booking_id, '_dbt_email', true);
                wp_mail($email, 'Payment Received - Booking Confirmed', 'Thank you! Your booking is now confirmed.');
            }
        }
        exit;
    }
});