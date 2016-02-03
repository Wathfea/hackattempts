<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       http://hackattempts.zengo.eu
 * @since      1.1
 *
 * @package    Hackattempts
 * @subpackage Hackattempts/admin/partials
 */
?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->

<div id="hackattemptsContainer" class="container">

    <h1><?php echo __('Hackattempts Administration Panel', 'hackattempts'); ?></h1>

    <div class="notify">
    </div>

    <h2 class="nav-tab-wrapper hackattempts_tab">
        <a class="nav-tab nav-tab-active" href="#"><?php echo __('Attacks', 'hackattempts'); ?></a>
        <a class="nav-tab" href="#"><?php echo __('File modifications', 'hackattempts'); ?></a>
        <a class="nav-tab" href="#"><?php echo __('Settings', 'hackattempts'); ?></a>
        <a class="nav-tab" href="#"><?php echo __('Help', 'hackattempts'); ?></a>
    </h2>

    <div id="hackattempts_sections">
        <!-- List of the attacks -->
        <section  class="hc_section">
            <div class="attacks_tab">
                <table class="attacks_table">
                    <tr>
                        <th><?php echo __('IP', 'hackattempts'); ?></th>
                        <th><?php echo __('COUNTRY', 'hackattempts'); ?></th>
                        <th><?php echo __('CITY', 'hackattempts'); ?></th>
                        <th><?php echo __('TIME', 'hackattempts'); ?></th>
                        <th><?php echo __('FILE', 'hackattempts'); ?></th>
                        <th><?php echo __('COUNTER', 'hackattempts'); ?></th>
                        <th><?php echo __('STATUS', 'hackattempts'); ?></th>
                        <th><?php echo __('OPTION', 'hackattempts'); ?></th>
                    </tr>
                    <?php
                    foreach ($this->get_attacks() as $attack) {
                        $status = "<span class='ip activeip'>" . __('Active', 'hackattempts') . "</span>";
                        $s = 1;
                        if ($attack->banned) {
                            $status = "<span class='ip banned'>" . __('Banned', 'hackattempts') . "</span>";
                            $s = 0;
                        }
                        echo '<tr id="row">';
                        echo '<td><span class="ip-addr">' . $attack->ip . '</span></td>';
                        echo '<td>' . $attack->country . '</td>';
                        echo '<td>' . $attack->city . '</td>';
                        echo '<td>' . date("d-m-Y H:m", $attack->timestamp) . '</td>';
                        echo '<td>' . $attack->uri . '</td>';
                        echo '<td>' . $attack->counter . '</td>';
                        echo '<td>' . $status . '</td>';
                        echo '<td>';
                        if ($s === 1) {
                            echo '<button class="hack_btn add_block" name="add_block"  data-ip="' . $attack->ip . '">' . __("Block", "hackattempts") . '</button>';
                        } else {
                            echo '<button class="hack_btn delete" name="delete"  data-ip="' . $attack->ip . '">' . __("Unblock", "hackattempts") . '</button>';
                        }

                        echo '<button class="hack_btn firewall" name="firewall">' . __("Firewall", "hackattempts") . '</button></td>';
                        echo '</tr>';
                    }
                    ?>
                </table>
                <input type="hidden" name="ajax_url" class="ajax_url" id="ajax_url" value="<?php echo admin_url('admin-ajax.php'); ?>" />
            </div>
        </section>
        <!-- / END List of the attacks -->

        <!-- File modifications Page -->
        <section  class="hc_section">
            <div class="file_tab">
                <table class="attacks_table">
                    <tr>
                        <th><?php echo __('File name', 'hackattempts'); ?></th>
                        <th><?php echo __('Last modified date', 'hackattempts'); ?></th>
                    </tr>
                    <?php foreach ($this->file_mods as $file) : ?>
                        <tr>
                            <td><?php echo $file->filename; ?></td>
                            <td><?php echo $file->mod_date; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </section>
        <!-- / END File modifications Page -->

        <!-- Settings Page -->
        <section  class="hc_section">
            <div class="settings_tab">
                <form method="POST" id="hackattemptsForm">
                    <?php wp_nonce_field('hackattemptNonceField'); ?>
                    <h3><?php echo __('Set login attempts', 'hackattempts'); ?></h3>
                    <table class="attacks_table">
                        <tr>
                            <th><?php echo __('Attempts', 'hackattempts'); ?></th>
                            <th></th>
                            <th><?php echo __('Time limit (in minute)', 'hackattempts'); ?></th>
                        </tr>
                        <tr>
                            <td><input type="number" max="999" min="0" maxlength="3" id="attempts" name="attempts" value="<?php echo $this->login_attempts; ?>" /></td>
                            <td><?php echo __('allowed retries in', 'hackattempts_allowed_retries_in'); ?></td>
                            <td><input type="number" max="999" min="0" maxlength="3" id="limit" name="limit" value="<?php echo $this->time_limit; ?>" /> <?php echo __('minutes', 'hackattempts'); ?>.</td>
                        </tr>
                    </table>

                    <div class="hackattempts_left">
                        <h3><?php echo __('Protected files', 'hackattempts'); ?></h3>

                        <table class="attacks_table" style="width: 80%;">
                            <tr>
                                <td><?php echo __('Add new:', 'hackattempts_add_new'); ?></td>
                                <td><input type="text" name="protected_files" class="protected_files" /></td>
                                <td><span class="hack_btn add_file" name="add_file"><?php echo __('Add', 'hackattempts'); ?></span></td>
                            </tr>
                        </table>

                        <table class="attacks_table" style="width: 80%;">
                            <tr>
                                <th><?php echo __('File name', 'hackattempts_file_name'); ?></th>
                                <th><?php echo __('Option', 'hackattempts_option'); ?></th>
                            </tr>
                            <?php foreach ($this->protected_files as $file) : ?>
                                <tr>
                                    <td><?php echo $file; ?></td>
                                    <td><span class="hack_btn removeFile" name="remove_security" data-remove="<?php echo $file; ?>"><?php echo __('Remove', 'hackattempts'); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                        
                        <h3><?php echo __('Watched files for file modifications', 'hackattempts'); ?></h3>
                        <table class="attacks_table" style="width: 80%;">
                            <tr>
                                <td><?php echo __('Add new:', 'hackattempts_add_new'); ?></td>
                                <td><input type="text" name="watched_files" class="watched_files" /></td>
                                <td><span class="hack_btn add_watch_file" name="add_watch_file"><?php echo __('Add', 'hackattempts'); ?></span></td>
                            </tr>
                        </table>

                        <table class="attacks_table" style="width: 80%;">
                            <tr>
                                <th><?php echo __('File name', 'hackattempts_file_name'); ?></th>
                                <th><?php echo __('Option', 'hackattempts_option'); ?></th>
                            </tr>
                            <?php foreach ($this->watched_files as $wfile) : ?>
                                <tr>
                                    <td><?php echo $wfile; ?></td>
                                    <td><span class="hack_btn removeWatch" name="remove_watch" data-remove="<?php echo $wfile; ?>"><?php echo __('Remove', 'hackattempts'); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </table>                        
                    </div>

                    <div class="hackattempts_right">
                        <h3><?php echo __('Notification', 'hackattempts'); ?></h3>
                        <table class="attacks_table" style="width: 80%; text-align: left;">
                            <tr>
                                <td><?php echo __('Enable email notification', 'hackattempts'); ?>:</td>
                                <td><input type="checkbox" name="admin_notification" <?php if ($this->email_notify == 'true') echo 'checked'; ?>/></td>
                            </tr>
                            <tr>
                                <td><?php echo __('Counter limit after send notification', 'hackattempts'); ?></td>
                                <td><input type="number" min="0" maxlength="4" id="noti_num" name="noti_num" value="<?php echo $this->email_counter; ?>" /></td>
                            </tr>
                            <tr>
                                <td><?php echo __('Email address', 'hackattempts'); ?></td>
                                <td><input type="email" id="noti_email" name="noti_email" placeholder="something@info.com" <?php if ($this->email_address !== '') echo 'value="' . $this->email_address . '" '; ?>/></td>
                            </tr>
                        </table>

                        <h3><?php echo __('Login settings', 'hackattempts'); ?></h3>
                        <table class="attacks_table" style="width: 80%; text-align: left;">
                            <tr>
                                <td><?php echo __('Disable wp-login.php', 'hackattempts'); ?>:</td>

                                <td><input type="checkbox" class="disable_wp_login" name="disable_wp_login" <?php if ($this->disable_login == 'true') echo 'checked'; ?>/></td>
                            </tr>
                            <tr>
                                <td><?php echo __('New login url', 'hackattempts'); ?>:</td>
                                <td><input type="input" id="new_login_url" name="new_login_url" <?php if ($this->new_login_url !== '')
                                echo 'value="' . $this->new_login_url . '" ';
                            if ($this->disable_login == 'false')
                                echo 'disabled';
                            ?> /></td>
                            </tr>
                        </table>

                        <h3><?php echo __('ZPM settings', 'hackattempts'); ?></h3>
                        <table class="attacks_table" style="width: 80%; text-align: left;">
                            <tr>
                                <td><?php echo __('ZPM host url', 'hackattempts'); ?>:</td>
                                <td><input type="input" id="zpm_url" name="zpm_url" <?php if ($this->zpm_url !== '') echo 'value="' . $this->zpm_url . '" '; ?> /></td>
                            </tr>
                        </table>
                    </div>

                    <div class="clear"></div>
                    <br />
                    <h3><?php echo __('Cron Settings', 'hackattempts'); ?></h3>
                    <h4><?php echo __('Set the log files lifetime (hour)', 'hackattempts'); ?></h4>
                    <input type="number" min="0" id="file_lifetime" name="file_lifetime" value="<?php echo $this->file_life_time; ?>" />
                    <br /><br />
                    <input type="submit" id="save" name="save" class="hack_btn save" value="<?php echo __('Save', 'hackattempts'); ?>" />
                </form>
            </div>
        </section>
        <!-- / END Settings Page -->

        <!-- Help Page -->
        <section  class="hc_section">
            <div class="help_tab">
                <div style="margin: 10px;">
                    <p><?php echo __('Do you have a question or need help?', 'hackattempts'); ?> <a href=""><?php echo __('Get answer on support forum', 'hackattempts'); ?></a>.</p>
                    <p><?php echo __('Do you have a suggestion?', 'hackattempts'); ?> <a href=""><?php echo __('Help us improve!', 'hackattempts'); ?></a></p>
                    <p><?php echo __('Are you ready to translate into your language?', 'hackattempts'); ?> <a href=""><?php echo __('Please notify us', 'hackattempts'); ?></a>.</p>

                    <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
                        <input type="hidden" name="cmd" value="_s-xclick">
                        <input type="hidden" name="hosted_button_id" value="4DDQG5M6SMXHQ">
                        <input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
                        <img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
                    </form>

                </div>
            </div>
        </section>
        <!-- / END Help Page -->
    </div>
</div>
