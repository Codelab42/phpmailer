<?php

/**
 * @file
 * Contains \Drupal\phpmailer\Plugin\Mail\DrupalPhpMailer\SmtpTransport.
 */

namespace Drupal\phpmailer\Plugin\Mail\Transports;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\phpmailer\Plugin\Mail\DrupalPhpMailer;
use Psr\Log\LoggerInterface;

/**
 * Defines a Drupal mail backend, using PHPMailer.
 *
 * @Mail(
 *   id = "phpmailer_smtp",
 *   label = @Translation("PHPMailer SMTP"),
 *   description = @Translation("The PHPMailer SMTP plugin.")
 * )
 */
class SmtpTransport extends DrupalPHPMailer {

  /**
   * Constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory, TranslationInterface $string_translation, LoggerInterface $logger) {
    // Invoke parent constructor.
    parent::__construct($configuration, $plugin_id, $plugin_definition, $config_factory, $string_translation, $logger);
  }

  /**
   * {@inheritdoc}
   */
  public function mail(array $message) {
    // Set the configured transport.
    $this->isSMTP();

    // Get the configuration.
    $smtp = \Drupal::config('phpmailer.smtp');

    // Configure smtp transport.
    $this->set('Host', $smtp->get('host'));
    $this->set('Port', $smtp->get('port'));
    $this->set('SMTPSecure', $smtp->get('protocol'));

    // Use SMTP authentication if both username and password are given.
    if ($smtp->get('username') !== '' &&
      $smtp->get('password') !== '') {

      $this->set('SMTPAuth', TRUE);
      $this->set('Username', $smtp->get('username'));
      $this->set('Password', $smtp->get('password'));
      $this->set('AuthType', $smtp->get('auth_type'));
    }

    $this->set('Realm', $smtp->get('realm'));
    $this->set('Workstation', $smtp->get('workstation'));

    $this->set('Timeout', $smtp->get('timeout'));
    $this->set('Timelimit', $smtp->get('timelimit'));
    $this->set('SMTPKeepAlive', $smtp->get('keepalive'));

    return parent::mail($message);
  }
}
