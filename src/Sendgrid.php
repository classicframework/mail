<?php

namespace classicframework\mail;

class Sendgrid
{
  protected $config = array();

  public function __construct($config = array())
  {
    $this->config = is_array($config) ? $config : array();
  }

  public function send($to, $subject, $body, $headers = array())
  {
    $api_key = $this->api_key();

    if ($api_key === '') {
      return false;
    }

    $payload = array(
      'personalizations' => array(
        array(
          'to' => $this->to_list($to),
        ),
      ),
      'from' => $this->from_array(),
      'subject' => (string) $subject,
      'content' => $this->content($body)
    );

    return $this->post_json('https://api.sendgrid.com/v3/mail/send', $payload, $api_key);
  }

  protected function post_json($url, $payload, $api_key)
  {
    if (!function_exists('curl_init')) {
      return false;
    }

    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'Authorization: Bearer ' . $api_key,
      'Content-Type: application/json',
    ));
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

    curl_exec($ch);

    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    return $status >= 200 && $status < 300;
  }

  protected function to_list($to)
  {
    if (!is_array($to)) {
      $to = array($to);
    }

    $result = array();

    foreach ($to as $email) {
      $result[] = array(
        'email' => (string) $email,
      );
    }

    return $result;
  }

  protected function from_array()
  {
    $from = isset($this->config['from']) ? $this->config['from'] : '';

    if (is_array($from)) {
      return array(
        'email' => isset($from[0]) ? (string) $from[0] : '',
        'name' => isset($from[1]) ? (string) $from[1] : '',
      );
    }

    return array(
      'email' => (string) $from,
    );
  }

  protected function api_key()
  {
    return isset($this->config['sendgrid_api_key']) ? (string) $this->config['sendgrid_api_key'] : '';
  }

  protected function content($message)
  {
    if (!is_array($message)) {
      $message = array(
        'text' => strip_tags((string) $message),
        'html' => (string) $message,
      );
    }

    $content = array();

    if (isset($message['text']) && trim((string) $message['text']) !== '') {
      $content[] = array(
        'type' => 'text/plain',
        'value' => (string) $message['text'],
      );
    }

    if (isset($message['html']) && trim((string) $message['html']) !== '') {
      $content[] = array(
        'type' => 'text/html',
        'value' => (string) $message['html'],
      );
    }

    return $content;
  }
}