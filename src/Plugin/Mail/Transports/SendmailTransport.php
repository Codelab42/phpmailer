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
 *   id = "phpmailer_sendmail",
 *   label = @Translation("PHPMailer sendmail"),
 *   description = @Translation("The PHPMailer sendmail plugin.")
 * )
 */
class SendmailTransport extends DrupalPHPMailer {

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
    $this->isSendmail();

    return parent::mail($message);
  }
}
