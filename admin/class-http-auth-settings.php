<?php
/**
 * @package HTTPAuth
 */

class HTTP_Auth_Settings
{

    /**
     * Call page Settings Function.
     */
    public function __construct()
    {
        $this->http_auth_configs();
    }

    /**
     * Check server software if apache then add HTTP Auth config in .htaccess file.
     *
     * @access private
     * @since 1.0.0
     */
    private function apache_config()
    {
        if ( isset( $_SERVER['SERVER_SOFTWARE'] )
            && 'apache' === strtolower( $_SERVER['SERVER_SOFTWARE'] )
        ) {
            $filename    = ABSPATH . '.htaccess';
            $get_content = file_get_contents( $filename, true );
            if ( false !== $get_content ) {
                if ( false === strpos( $get_content, '# BEGIN HTTP Auth' ) ) {
                    $http_rule  = PHP_EOL . '# BEGIN HTTP Auth';
                    $http_rule .= PHP_EOL . '<IfModule mod_rewrite.c>';
                    $http_rule .= PHP_EOL . 'RewriteEngine on';
                    $http_rule .= PHP_EOL . 'RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization},L]';
                    $http_rule .= PHP_EOL . '</IfModule>';
                    $http_rule .= PHP_EOL . '# END HTTP Auth';
                    $http_rule .= PHP_EOL;

                    file_put_contents( $filename, $http_rule, FILE_APPEND | LOCK_EX );
                }
            }
        }
    }

    /**
     * Generate Credentials section HTML.
     *
     * @access private
     * @since 1.0.0
     *
     * @param string $username HTTP Auth Username.
     * @param string $password HTTP Auth Password.
     */
    private function get_credentials_output( $username, $password )
    {
    ?>
        <table class="http-auth-table">
          <caption>
            <?php
              esc_html_e( 'Credentials', 'http-auth' );
            ?>
          </caption>
          <tbody>
            <tr>
              <th>
              <?php
                esc_html_e( 'Username :', 'http-auth' );
              ?>
              </th>
              <td>
                <input type="text" name="http_auth_username" value="<?php echo esc_attr( $username ); ?>" class="regular-text" required />
              </td>
            </tr>
            <tr>
              <th>
              <?php
                esc_html_e( 'Password :', 'http-auth' );
              ?>
              </th>
              <td>
                <input type="password" name="http_auth_password" value="<?php echo esc_attr( $password ); ?>" class="regular-text" required />
              </td>
            </tr>
          </tbody>
        </table>
    <?php
    }

    /**
     * Generate Message section HTML.
     *
     * @access private
     * @since 1.0.0
     *
     * @param string $message HTTP Auth Cancel message.
     */
    private function get_message_output( $message )
    {
    ?>
        <table class="http-auth-table">
          <caption>
            <?php
              esc_html_e( 'Message (Optional)', 'http-auth' );
            ?>
            </caption>
          <tbody>
            <tr>
              <th>
                <?php
                  esc_html_e( 'Cancel Message :', 'http-auth' );
                ?>
              </th>
              <td>
                <textarea name="http_auth_message" rows="5" cols="45"><?php esc_html_e( $message ); ?></textarea>
              </td>
            </tr>
          </tbody>
        </table>
    <?php
    }

    /**
     * Generate for section HTML.
     *
     * @access private
     * @since 1.0.0
     *
     * @param string $http_apply_site Applicable on site-wide.
     * @param string $http_apply_admin Applicable on Admin pages only.
     */
    private function get_for_output( $http_apply_site, $http_apply_admin )
    {
    ?>
        <table class="http-auth-table http-for">
          <caption>
            <?php
              esc_html_e( 'For', 'http-auth' );
            ?>
          </caption>
          <tbody>
            <tr>
              <td>
                <input type="radio" name="http_auth_apply" value="site" <?php esc_html_e( $http_apply_site ); ?> />
                <strong>
                <?php
                  esc_html_e( 'Complete Site', 'http-auth' );
                ?>
                </strong>
              </td>
            </tr>
            <tr>
              <td>
                <input type="radio" name="http_auth_apply" value="admin" <?php esc_html_e( $http_apply_admin ); ?> />
                  <strong>
                  <?php
                    esc_html_e( 'Login and Admin Pages', 'http-auth' );
                  ?>
                  </strong>
              </td>
            </tr>
          </tbody>
        </table>
    <?php
    }

    /**
     * Save HTTP Auth Settings.
     *
     * @access private
     * @since 1.0.0
     */
    private function save_settings()
    {
        $form_submit = filter_input( INPUT_POST, 'submit' );
        $user_id     = get_current_user_id();

        if ( $form_submit
            && check_admin_referer( 'http-auth-settings_' . $user_id,
                '_http_auth_settings_nonce'
            )
        ) {
            $http_settings = array(
                'username' => '',
                'password' => '',
                'message'  => '',
                'apply'    => 'site',
                'activate' => 'off',
            );

            $activate_auth = filter_input( INPUT_POST, 'http_auth_activate' );
            $set_apply     = filter_input( INPUT_POST, 'http_auth_apply' );
            $set_message   = filter_input( INPUT_POST, 'http_auth_message' );
            $set_password  = filter_input( INPUT_POST, 'http_auth_password' );
            $set_username  = filter_input( INPUT_POST, 'http_auth_username' );

            if ( $activate_auth ) {
                $http_settings['activate'] = $activate_auth;
            }

            if ( $set_apply && 'admin' === $set_apply ) {
                $http_settings['apply'] = $set_apply;
            }

            if ( $set_message ) {
                $http_settings['message'] = esc_html( trim( $set_message ) );
            }

            if ( $set_password ) {
                $http_settings['password'] = esc_attr( $set_password );
            }

            if ( $set_username ) {
                $http_settings['username'] = esc_attr( $set_username );
            }

            update_option( 'http_auth_settings', serialize( $http_settings ) );

            $this->apache_config();
        }
    }

    /**
     * HTTP Auth Settings.
     *
     * @access private
     * @since 0.1
     */
    private function http_auth_configs()
    {
        $this->save_settings();

        $http_apply_admin = 'checked';
        $http_apply_site  = '';
        $get_settings     = unserialize( get_option( 'http_auth_settings' ) );
        $user_id          = get_current_user_id();
        $username = $password = $message = $http_activated = '';

        if ( isset( $get_settings ) && ! empty( $get_settings ) ) {
            $username       = $get_settings['username'];
            $password       = $get_settings['password'];
            $message        = $get_settings['message'];
            $applicable     = $get_settings['apply'];
            $auth_activated = $get_settings['activate'];

            if ( 'site' == $applicable ) {
                $http_apply_admin = '';
                $http_apply_site  = 'checked';
            }

            if ( 'on' == $auth_activated ) {
                $http_activated = 'checked';
            }
        }
        ?>
        <div class="wrap">
          <h1>
          <?php
            esc_html_e( 'HTTP Auth SETTINGS', 'http-auth' );
          ?>
          </h1>
          <form enctype="multipart/form-data" method="POST" action="" id="http-auth">
            <?php
              wp_nonce_field( 'http-auth-settings_' . $user_id,
                  '_http_auth_settings_nonce', true
              );

              $this->get_credentials_output( $username, $password );
              $this->get_message_output( $message );
              $this->get_for_output( $http_apply_site, $http_apply_admin );
            ?>

            <table class="http-auth-table">
              <tbody>
                <tr>
                  <td>
                    <input type="checkbox" name="http_auth_activate" value="on" <?php esc_html_e( $http_activated ); ?> />
                    <strong>
                      <?php
                        esc_html_e( 'Activate', 'http-auth' );
                      ?>
                    </strong>
                  </td>
                </tr>
              </tbody>
            </table>
            <p class="submit">
              <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_html_e( 'Save Changes', 'http-auth' ); ?>" />
            </p>
          </form>
        </div>
        <?php
    }
}
