<?php
/**
 * Plugin Name: Elastic Email Sub-account
 * Description: A plugin to allow Elastic Email sub-accounts to manage their account from the WordPress frontend.
 * Version: 1.2
 * Author: Your Name
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

require_once plugin_dir_path(__FILE__) . 'elastic-email-api.php';

class Elastic_Email_Sub_Account {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_shortcode('elastic_email_sub_account', array($this, 'display_sub_account_info'));
        add_shortcode('elastic_email_total_sent', array($this, 'display_total_sent'));
        add_shortcode('elastic_email_credits', array($this, 'display_credits_only'));
    }

    public function add_admin_menu() {
        add_menu_page(
            'Elastic Email Sub-account',
            'Elastic Email Sub-account',
            'manage_options',
            'elastic-email-sub-account',
            array($this, 'create_admin_page'),
            'dashicons-email',
            6
        );
    }

    public function register_settings() {
        register_setting('elastic_email_sub_account_options', 'elastic_email_api_key');
    }

    public function create_admin_page() {
        ?>
        <div class="wrap">
            <h1>Elastic Email Sub-account Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('elastic_email_sub_account_options');
                do_settings_sections('elastic_email_sub_account_options');
                ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Elastic Email API Key</th>
                        <td><input type="text" name="elastic_email_api_key" value="<?php echo esc_attr(get_option('elastic_email_api_key')); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function display_sub_account_info() {
        if (!is_user_logged_in()) {
            return 'You must be logged in to view this information.';
        }

        if (isset($_POST['submit_linked_email'])) {
            $this->handle_linked_email_submission();
        }

        $api_key = get_option('elastic_email_api_key');
        if (empty($api_key)) {
            return 'The Elastic Email API key has not been configured.';
        }

        $api = new Elastic_Email_Api($api_key);
        $user = wp_get_current_user();
        $linked_email = get_user_meta($user->ID, 'elastic_email_linked_email', true);
        $email_to_check = !empty($linked_email) ? $linked_email : $user->user_email;
        $subaccounts = $api->get_sub_account_list($email_to_check);

        if (empty($subaccounts)) {
            // Display a form to enter a different email address
            return $this->display_email_linking_form();
        }

        $subaccount = $subaccounts[0];

        ob_start();
        ?>
        <div class="wrap">
            <h2>Account Information</h2>
            <p><strong>Email:</strong> <?php echo $subaccount->email; ?></p>
            <p><strong>Credits:</strong> <?php echo $subaccount->emailcredits; ?></p>
        </div>
        <?php
        return ob_get_clean();
    }

    private function handle_linked_email_submission() {
        if (!isset($_POST['linked_email']) || !is_email($_POST['linked_email'])) {
            return;
        }

        $user_id = get_current_user_id();
        $linked_email = sanitize_email($_POST['linked_email']);

        update_user_meta($user_id, 'elastic_email_linked_email', $linked_email);

        echo '<div class="updated"><p>Email address linked successfully!</p></div>';
    }

    private function display_email_linking_form() {
        ob_start();
        ?>
        <div class="wrap">
            <h2>Link your Elastic Email Sub-account</h2>
            <p>The email address associated with your WordPress account is not registered as an Elastic Email sub-account.</p>
            <p>If you have a sub-account with a different email address, please enter it below to link it to your WordPress account.</p>
            <form method="post">
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Elastic Email Address</th>
                        <td><input type="email" name="linked_email" value="" /></td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="submit_linked_email" class="button-primary" value="Link Email" />
                </p>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    public function display_total_sent() {
        if (!is_user_logged_in()) {
            return '';
        }

        $api_key = get_option('elastic_email_api_key');
        if (empty($api_key)) {
            return '';
        }

        $api = new Elastic_Email_Api($api_key);
        $user = wp_get_current_user();
        $linked_email = get_user_meta($user->ID, 'elastic_email_linked_email', true);
        $email_to_check = !empty($linked_email) ? $linked_email : $user->user_email;
        $subaccounts = $api->get_sub_account_list($email_to_check);

        if (empty($subaccounts)) {
            return '';
        }

        $subaccount = $subaccounts[0];

        return $subaccount->totalemailssent;
    }

    public function display_credits_only() {
        if (!is_user_logged_in()) {
            return '';
        }

        $api_key = get_option('elastic_email_api_key');
        if (empty($api_key)) {
            return '';
        }

        $api = new Elastic_Email_Api($api_key);
        $user = wp_get_current_user();
        $linked_email = get_user_meta($user->ID, 'elastic_email_linked_email', true);
        $email_to_check = !empty($linked_email) ? $linked_email : $user->user_email;
        $subaccounts = $api->get_sub_account_list($email_to_check);

        if (empty($subaccounts)) {
            return '';
        }

        $subaccount = $subaccounts[0];

        return $subaccount->emailcredits;
    }
}

new Elastic_Email_Sub_Account();
