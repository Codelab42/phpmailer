<?php

/**
 * @file
 * Contains \Drupal\phpmailer\Plugin\Mail\DrupalPHPMailer.
 */

namespace Drupal\phpmailer\Plugin\Mail;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Mail\MailInterface;
use Drupal\Core\Mail\MailFormatHelper;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use PHPMailer;
use phpmailerException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a Drupal mail backend, using PHPMailer.
 */
abstract class DrupalPHPMailer extends PHPMailer implements MailInterface, ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * @var $config
   */
  protected $config;

  /**
   * @var ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var LoggerInterface
   */
  protected $logger;

  /**
   * Constructor.
   *
   * @param array $configuration
   *   .
   * @param $plugin_id
   *   .
   * @param $plugin_definition
   *   .
   * @param ConfigFactoryInterface $config_factory
   *   .
   * @param TranslationInterface $string_translation
   *   .
   * @param LoggerInterface $logger
   *   .
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory, TranslationInterface $string_translation, LoggerInterface $logger) {
    parent::__construct(TRUE);

    $this->configFactory = $config_factory;
    $this->setStringTranslation($string_translation);
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('string_translation'),
      $container->get('logger.channel.default')
    );
  }


  /**
   * Concatenates and wraps the email body for plain-text mails.
   *
   * @param array $message
   *   A message array, as described in hook_mail_alter().
   *
   * @return array
   *   The formatted $message.
   */
  public function format(array $message) {
    // Join the body array into one string.
    $message['body'] = implode("\n\n", $message['body']);
    // Convert any HTML to plain-text.
    $message['body'] = MailFormatHelper::htmlToText($message['body']);
    // Wrap the mail body for sending.
    $message['body'] = MailFormatHelper::wrapMail($message['body']);

    return $message;
  }

  /**
   * {@inheritdoc}
   */
  public function mail(array $message) {
    // Set some general defaults.
    $this->settings = $this->configFactory->get('phpmailer.settings');
    $this->message_defaults = $this->configFactory->get('phpmailer.message');

    $this->set('do_verp', $this->settings->get('do_verp'));

    // Enable / disable debugging.
    $this->set('SMTPDebug', $this->settings->get('debug'));
    $this->set('Debugoutput', $this->settings->get('debug_output'));

    // Set some message defaults.
    $this->set('Priority', $this->message_defaults->get('priority'));
    $this->set('CharSet', $this->message_defaults->get('charset'));
    $this->set('ContentType', $this->message_defaults->get('content_type'));
    $this->set('Encoding', $this->message_defaults->get('encoding'));

    // Set default From name.
    $from_name = $this->message_defaults->get('from_name');
    if ($from_name == '') {
      // Fall back on the site name.
      $from_name = $this->configFactory->get('system.site')->get('name');
    }
    $this->set('FromName', $from_name);

    // Parse 'From' e-mail address.
    $from = $this->parseAddress($message['from']);
    $from = reset($from);
    $this->set('From', $from['mail']);

    if ($from['name'] != '') {
      $this->set('FromName', $from['name']);
    }
    unset($message['headers']['From']);

    // Set recipients.
    foreach ($this->parseAddress($message['to']) as $address) {
      $this->AddAddress($address['mail'], $address['name']);
    }
    // Extract CCs and BCCs from headers.
    if (!empty($message['headers']['Cc'])) {
      foreach ($this->parseAddress($message['headers']['Cc']) as $address) {
        $this->AddCC($address['mail'], $address['name']);
      }
    }
    if (!empty($message['headers']['Bcc'])) {
      foreach ($this->parseAddress($message['headers']['Bcc']) as $address) {
        $this->AddBCC($address['mail'], $address['name']);
      }
    }
    unset($message['headers']['Cc'], $message['headers']['Bcc']);

    // Extract Reply-To from headers.
    if (isset($message['headers']['Reply-To'])) {
      foreach ($this->parseAddress($message['headers']['Reply-To']) as $address) {
        $this->AddReplyTo($address['mail'], $address['name']);
      }
      unset($message['headers']['Reply-To']);
    }
    elseif ($this->settings->get('always_replyto')) {
      // If no Reply-To header has been explicitly set, use the From address
      // to be able to respond to e-mails sent via Google Mail.
      $this->AddReplyTo($from['mail'], $from['name']);
    }

    // Extract Content-Type and charset.
    if (isset($message['headers']['Content-Type'])) {
      $content_type = explode(';', $message['headers']['Content-Type']);
      $this->ContentType = trim(array_shift($content_type));
      foreach ($content_type as $param) {
        $param = explode('=', $param, 2);
        $key = trim($param[0]);
        if ($key == 'charset') {
          $this->set('CharSet', trim($param[1]));
        }
        else {
          $this->set('ContentType', $this->ContentType . '; ' . $key . '=' . trim($param[1]));
        }
      }
      unset($message['headers']['Content-Type']);
    }

    // Set additional properties.
    $properties = [
      'X-Priority'                => 'Priority',
      'Content-Transfer-Encoding' => 'Encoding',
      'Sender'                    => 'Sender',
      'Message-ID'                => 'MessageID',
      'Return-Path'               => 'ReturnPath',
    ];
    foreach ($properties as $source => $property) {
      if (isset($message['headers'][$source])) {
        $this->set($property, $message['headers'][$source]);
        unset($message['headers'][$source]);
      }
    }

    // This one is always set by PHPMailer.
    unset($message['headers']['MIME-Version']);

    // Add remaining header lines.
    // Note: Any header lines MUST already be checked by the caller for
    // unwanted newline characters to avoid header injection.
    // @see PHPMailer::SecureHeader()
    foreach ($message['headers'] as $key => $value) {
      $this->AddCustomHeader("$key:$value");
    }

    $this->set('Subject', $message['subject']);
    $this->set('Body', $message['body']);

    try {
      return $this->send();
    }
    catch (phpmailerException $e) {
      $this->logger->error(
        'An attempt to send an e-mail message failed, and the following error message was returned : @exception_message',
        ['@exception_message' => $e->getMessage()]
      );
    }
    return FALSE;
  }

  /**
   * Extract address and optional display name of an e-mail address.
   *
   * @param string $string
   *   A string containing one or more valid e-mail address(es) separated with
   *   commas.
   *
   * @return array
   *   An array containing all found e-mail addresses split into mail and name.
   *
   * @see http://tools.ietf.org/html/rfc5322#section-3.4
   */
  protected function parseAddress($string) {
    $parsed = [];

    // The display name may contain commas (3.4). Extract all quoted strings
    // (3.2.4) to a stack and replace them with a placeholder to prevent
    // splitting at wrong places.
    $string = preg_replace_callback('(".*?(?<!\\\\)")', [$this, 'phpmailerStack'], $string);

    // Build a regex that matches a name-addr (3.4).
    // @see valid_email_address()
    $user = '[a-zA-Z0-9_\-\.\+\^!#\$%&*+\/\=\?\`\|\{\}~\']+';
    $domain = '(?:(?:[a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9])\.?)+';
    $ipv4 = '[0-9]{1,3}(?:\.[0-9]{1,3}){3}';
    $ipv6 = '[0-9a-fA-F]{1,4}(?:\:[0-9a-fA-F]{1,4}){7}';
    $address = "$user@(?:$domain|(?:\[(?:$ipv4|$ipv6)\]))";
    $adr_rx = "/^(?P<name>.*)\s<(?P<address>$address)>$/";

    // Split string into multiple parts and process each.
    foreach (explode(',', $string) as $email) {
      // Re-inject stripped placeholders.
      $email = preg_replace_callback('(\x01)', [$this, 'phpmailerStack'], trim($email));
      // Check if it's a name-addr or a plain address (3.4).
      if (preg_match($adr_rx, $email, $matches)) {
        // PHPMailer expects an unencoded display name.
        $parsed[] = ['mail' => $matches['address'], 'name' => mime_header_decode(stripslashes($matches['name']))];
      }
      else {
        $parsed[] = ['mail' => trim($email, '<>'), 'name' => ''];
      }
    }
    return $parsed;
  }

  /**
   * Implements a FIFO stack to store extracted quoted strings.
   */
  protected function phpmailerStack($matches = NULL) {
    $string = $matches[0];
    static $stack = [];

    if ($string == "\x01") {
      // Unescape quoted characters (3.2.4) to prevent double escaping.
      return str_replace(['\"', '\\\\'], ['"', '\\'], array_shift($stack));
    }
    // Remove surrounding quotes and push on stack.
    array_push($stack, substr($string, 1, -1));
    // Return placeholder substitution. 0x01 may never appear outside a quoted
    // string (3.2.3).
    return "\x01";
  }

  /**
   * Provide more user-friendly error messages.
   *
   * Note: messages should not end with a dot.
   */
  public function setLanguage($langcode = 'en', $lang_path = '') {
    // Retrieve English defaults to ensure all message keys are set.
    parent::setLanguage('en', '');

    // Overload with Drupal translations.
    $this->language = [
      'authenticate' => $this->t('SMTP error: Could not authenticate.'),
      'connect_host' => t('SMTP error: Could not connect to host.'),
      'data_not_accepted' => t('SMTP error: Data not accepted.'),
      'smtp_connect_failed' => t('SMTP error: Could not connect to SMTP host.'),
      'smtp_error' => t('SMTP server error:') . ' ',

      // Messages used during email generation.
      'empty_message' => t('Message body empty'),
      'encoding' => t('Unknown encoding:') . ' ',
      'variable_set' => t('Cannot set or reset variable: '),

      'file_access' => t('File error: Could not access file: '),
      'file_open' => t('File error: Could not open file: '),

      // Non-administrative messages.
      'from_failed' => t('The following From address failed: '),
      'invalid_address' => t('Invalid address'),
      'provide_address' => t('You must provide at least one recipient e-mail address.'),
      'recipients_failed' => t('The following recipients failed: '),
    ] + $this->language;
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public static function rfcDate() {
    return date('r');
  }
}
