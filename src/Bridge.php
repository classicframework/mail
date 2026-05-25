<?php

namespace classicframework\mail;

use classicframework\core\App;
use classicframework\core\Config;
use classicframework\core\BridgeInterface;

class Bridge implements BridgeInterface
{
  public static function register(App $app)
  {
    $config = Config::extract('mail');

    $mail = new Mail($config);

    $app->set_service('mail', $mail);
  }
}