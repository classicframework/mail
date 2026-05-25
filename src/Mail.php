<?php

namespace classicframework\mail;

class Mail
{
  protected $config = array();
  protected $driver = null;

  public function __construct($config = array())
  {
    $this->config = is_array($config) ? $config : array();
  }

  public function send($to, $subject, $body, $headers = array())
  {
    if (!$this->enabled()) {
      return false;
    }

    $message = $this->message($body);

    $driver = $this->driver();

    if ($driver !== null) {
      return $driver->send($to, $subject, $message, $headers);
    }

    return $this->send_with_mail($to, $subject, $message, $headers);
  }

  protected function driver()
  {
    if ($this->driver !== null) {
      return $this->driver;
    }

    foreach ($this->config as $key => $value) {
      if (substr($key, -8) !== '_enabled') {
        continue;
      }

      if ($key === 'enabled') {
        continue;
      }

      if ($value !== true) {
        continue;
      }

      $name = substr($key, 0, -8);
      $class = __NAMESPACE__ . '\\' . ucfirst($name);

      if (class_exists($class)) {
        $this->driver = new $class($this->config);
        return $this->driver;
      }
    }

    return null;
  }

  protected function send_with_mail($to, $subject, $message, $headers)
  {
    $to = $this->normalize_email($to);
    $subject = (string) $subject;

    $header_lines = $this->headers($headers);

    return mail($to, $subject, (string) $message['html'], implode("\r\n", $header_lines));
  }

  protected function headers($headers)
  {
    $result = array();

    $from = $this->from();

    if ($from !== '') {
      $result[] = 'From: ' . $from;
    }

    $result[] = 'MIME-Version: 1.0';
    $result[] = 'Content-Type: text/html; charset=UTF-8';

    if (is_array($headers)) {
      foreach ($headers as $name => $value) {
        $result[] = (string) $name . ': ' . (string) $value;
      }
    }

    return $result;
  }

  protected function from()
  {
    if (!isset($this->config['from'])) {
      return '';
    }

    if (is_array($this->config['from'])) {
      $email = isset($this->config['from'][0]) ? (string) $this->config['from'][0] : '';
      $name = isset($this->config['from'][1]) ? (string) $this->config['from'][1] : '';

      if ($name !== '') {
        return $this->encode_header($name) . ' <' . $email . '>';
      }

      return $email;
    }

    return (string) $this->config['from'];
  }

  protected function normalize_email($email)
  {
    if (is_array($email)) {
      return implode(', ', $email);
    }

    return (string) $email;
  }

  protected function encode_header($value)
  {
    return '=?UTF-8?B?' . base64_encode((string) $value) . '?=';
  }

  protected function enabled()
  {
    return isset($this->config['enabled']) ? (bool) $this->config['enabled'] : true;
  }

  protected function message($body)
  {
    if (is_array($body)) {
      return array(
        'text' => isset($body['text']) ? (string) $body['text'] : '',
        'html' => isset($body['html']) ? (string) $body['html'] : '',
      );
    }

    $html = (string) $body;

    return array(
      'text' => $this->html_to_text($html),
      'html' => $html,
    );
  }

  protected function html_to_text($html)
  {
    $text = (string) $html;
    $text = str_replace(array("\r\n", "\r"), "\n", $text);
    $text = preg_replace('/<br\s*\/?>/i', "\n", $text);
    $text = preg_replace('/<\/p>/i', "\n\n", $text);
    $text = strip_tags($text);
    $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
    $text = preg_replace("/\n{3,}/", "\n\n", $text);

    return trim($text);
  }
}