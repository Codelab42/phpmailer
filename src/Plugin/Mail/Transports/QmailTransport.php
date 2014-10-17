<?php

/**
 * @file
 * Contains \Drupal\phpmailer\Plugin\Mail\DrupalPhpMailer\QmailTransport.
 */

namespace Drupal\phpmailer\Plugin\Mail\Transports;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\phpmailer\Plugin\Mail\DrupalPhpMailer;
use Psr\Log\LoggerInterface;

/**
 * Defines a Drupal qmail backend, using PHPMailer.
 *
 * @Mail(
 *   id = "phpmailer_qmail",
 *   label = @Translation("PHPMailer qmail"),
 *   description = @Translation("The PHPMailer qmail plugin.")
 * )
 */
class QmailTransport extends DrupalPHPMailer {

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
    $this->isQmail();

    return parent::mail($message);
  }
}
