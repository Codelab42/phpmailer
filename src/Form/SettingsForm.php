<?php

/**
 * @file
 * Contains \Drupal\phpmailer\Form\SettingsForm.
 */

namespace Drupal\phpmailer\Form;

use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form that configures PHPMailer settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'phpmailer_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return array(
      'phpmailer.settings',
      'phpmailer.smtp'
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $settings = $this->config('phpmailer.settings');
    $smtp = $this->config('phpmailer.smtp');
    $ini_sendmail_path = ini_get('sendmail_path');

    $form['mail'] = array(
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => t('Mail'),
      '#markup' => t("A mailsystem plugin that uses PHP's built-in <a href='!url'>mail()</a> function to send the e-mail.<br />To activate it place the following statement in your site's @file file:<blockquote><code>%code</code></blockquote>. This will use <code>@ini</code> to send mail, change that by setting <code>sendmail_path</code> in the server's <code>php.ini</code> to a value as desired.", array(
        '!url' => 'http://php.net/manual/en/function.mail.php',
        '%code' => '<?php $config["system.mail"]["interface"]["default"] = "phpmailer_mail"; ?>',
        '@file' => 'settings.php',
        '@ini' => $ini_sendmail_path,
      )),
    );
    $form['qmail'] = array(
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => t('Qmail'),
      '#markup' => t("A mailsystem plugin that uses a local instance of <a href='!url'>qmail</a> to send the e-mail.<br />To activate it place the following statement in your site's @file file:<blockquote><code>%code</code></blockquote>. This will use <code>@ini</code> to send mail, change that by setting <code>sendmail_path</code> in the server's <code>php.ini</code> to a value as desired.", array(
        '!url' => 'http://cr.yp.to/qmail.html',
        '%code' => '<?php $config["system.mail"]["interface"]["default"] = "phpmailer_qmail"; ?>',
        '@file' => 'settings.php',
        '@ini' => !stristr($ini_sendmail_path, 'qmail') ? '/var/qmail/bin/qmail-inject' : $ini_sendmail_path,
      )),
    );
    $form['sendmail'] = array(
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => t('Sendmail'),
      '#markup' => t("A mailsystem plugin that uses a local instance of <a href='!url'>sendmail</a> to send the e-mail.<br />To activate it place the following statement in your site's @file file:<blockquote><code>%code</code></blockquote>. This will use <code>@ini</code> to send mail, change that by setting <code>sendmail_path</code> in the server's <code>php.ini</code> to a value as desired.", array(
        '!url' => 'http://www.sendmail.org',
        '%code' => '<?php $config["system.mail"]["interface"]["default"] = "phpmailer_sendmail"; ?>',
        '@file' => 'settings.php',
        '@ini' => !stristr($ini_sendmail_path, 'sendmail') ? '/usr/sbin/sendmail' : $ini_sendmail_path,
      )),
    );

    $form['smtp'] = array(
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => t('SMTP'),
      '#markup' => t("A mailsystem plugin that uses the <a href='!url'>Simple Mail Transfer Protocol</a> to send the e-mail.<br />To activate it place the following statement in your site's @file file:<blockquote><code>%code</code></blockquote>", array(
        '!url' => 'http://en.wikipedia.org/wiki/Simple_Mail_Transfer_Protocol',
        '%code' => '<?php $config["system.mail"]["interface"]["default"] = "phpmailer_smtp"; ?>',
        '@file' => 'settings.php',
      )),
    );
    $form['smtp']['server'] = array(
      '#type' => 'fieldset',
      '#open' => TRUE,
      '#title' => t('Server settings'),
    );
    $form['smtp']['server']['host'] = array(
      '#type' => 'textfield',
      '#title' => t('Primary SMTP server'),
      '#description' => t('The host name or IP address of your SMTP server, e.g. smtp.example.org.'),
      '#default_value' => $smtp->get('host'),
      '#required' => TRUE,
    );
    /*
    //$form['smtp']['server']['host_backup'] = array(
    //  '#type' => 'textfield',
    //  '#title' => t('Backup SMTP server'),
    //  '#description' => t('')),
    //  '#default_value' => $smtp->get('host_backup'),
    //);
    */
    $form['smtp']['server']['protocol'] = array(
      '#type' => 'select',
      '#title' => t('Use secure protocol'),
      '#description' => t('Whether to use an encrypted connection to communicate with the SMTP server.'),
      '#options' => array(
        '' => t('No'),
        'ssl' => t('SSL: Secure Sockets Layer'),
        'tls' => t('TLS: Transport Layer Security'),
      ),
      '#default_value' => $smtp->get('protocol'),
    );
    $form['smtp']['server']['port'] = array(
      '#type' => 'textfield',
      '#title' => t('Port'),
      '#size' => 5,
      '#maxlength' => 5,
      '#description' => t('The standard SMTP port is 25, for SSL use 465, for TLS use 587.'),
      '#default_value' => $smtp->get('port'),
      '#required' => TRUE,
    );
    if (!function_exists('openssl_open')) {
      $form['smtp']['server']['protocol']['#default_value'] = '';
      $form['smtp']['server']['protocol']['#disabled'] = TRUE;
      $form['smtp']['server']['protocol']['#description'] .= ' ' . t('Note: This option has been disabled since your PHP installation does not seem to have support for OpenSSL.');
      $smtp->set('protocol', '');
      $smtp->save();
    }
    $form['smtp']['server']['authentication'] = array(
      '#type' => 'details',
      '#title' => t('Authentication'),
      '#description' => t('Leave blank if your SMTP server does not require authentication.'),
    );
    $form['smtp']['server']['authentication']['username'] = array(
      '#type' => 'textfield',
      '#title' => t('Username'),
      '#description' => t('Enter your username, e.g firstname.lastname, or firstname.lastname@example.org'),
      '#default_value' => $smtp->get('username'),
    );
    $form['smtp']['server']['authentication']['password'] = array(
      '#type' => 'textfield',
      '#title' => t('Password'),
      '#default_value' => $smtp->get('password'),
    );
    if ($settings->get('hide_password')) {
      $have_password = ($smtp->get('password') !== '');
      $form['smtp']['server']['authentication']['password'] = array(
        '#type' => 'password',
        '#title' => $have_password ? t('Change password') : t('Password'),
        '#description' => $have_password ? t('Leave empty if you do not intend to change the current password.') : '',
      );
      unset($form['smtp']['server']['authentication']['password']['#default_value']);
    }
    $form['smtp']['server']['authentication']['auth_type'] = array(
      '#type' => 'select',
      '#title' => t('Auth type'),
      '#options' => array(
        'LOGIN' => t('LOGIN'),
        'PLAIN' => t('PLAIN'),
        'NTLM' => t('NTLM'),
        'CRAM-MD5' => t('CRAM-MD5'),
      ),
      '#default_value' => $smtp->get('auth_type'),
    );
    $form['smtp']['server']['advanced'] = array(
      '#type' => 'details',
      '#title' => t('Advanced settings'),
      '#description' => t('Leave blank if your SMTP server does not require authentication.'),
    );
    $form['smtp']['server']['advanced']['timeout'] = array(
      '#type' => 'textfield',
      '#title' => t('Connection timeout'),
      '#default_value' => $smtp->get('timeout'),
    );
    $form['smtp']['server']['advanced']['timelimit'] = array(
      '#type' => 'textfield',
      '#title' => t('Connection timelimit'),
      '#default_value' => $smtp->get('timelimit'),
    );
    $form['smtp']['server']['advanced']['keepalive'] = array(
      '#type' => 'checkbox',
      '#title' => t('Keep connection alive'),
      '#default_value' => $smtp->get('keepalive'),
    );
    $form['general'] = array(
      '#type' => 'details',
      '#title' => t('General settings'),
    );
    $form['general']['from_name'] = array(
      '#type' => 'textfield',
      '#title' => t('From name'),
      '#description' => t('Enter a name that should appear as the sender for all messages. If left blank the site name will be used instead: @site.name.', array('@site.name' => \Drupal::config('system.site')->get('name'))),
      '#default_value' => $smtp->get('from_name'),
    );
    $form['general']['always_replyto'] = array(
      '#type' => 'checkbox',
      '#title' => t('Always set "Reply-To" address'),
      '#description' => t('Enables setting the "Reply-To" address to the original sender of the message, if unset.  This is required when using Google Mail, which would otherwise overwrite the original sender.'),
      '#default_value' => $settings->get('always_replyto'),
    );
    $form['general']['debug'] = array(
      '#type' => 'select',
      '#title' => t('Debug'),
      '#options' => array(0 => 0, 1 => 1, 2 => 2, 3 => 3, 4 => 4),
      '#default_value' => $smtp->get('debug'),
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('phpmailer.smtp')
      ->set('host', $values['host'])
      //->set('host_backup', $values['host_backup'])
      ->set('protocol', $values['protocol'])
      ->set('port', $values['port'])
      ->set('username', $values['username'])
      ->set('auth_type', $values['auth_type'])
      ->set('timeout', $values['timeout'])
      ->set('timeout', $values['timelimit'])
      ->set('keepalive', $values['keepalive']);

    // Set only if not empty.
    foreach (array('password') as $value) {
      if (!empty($values[$value])) {
        $this->config('phpmailer.smtp')
          ->set($value, $values[$value]);
      }
    }
    $this->config('phpmailer.smtp')->save();

    $this->config('phpmailer.settings')
      ->set('from_name', $values['from_name'])
      ->set('always_replyto', $values['always_replyto'])
      ->set('debug', $values['debug']);
    $this->config('phpmailer.settings')->save();
  }
}
