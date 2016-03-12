<?php

$config_directories['site'] = 'config/site';

if (!empty($settings['http_basic_auth']['password'])
    && php_sapi_name() !== 'cli'
) {
  $auth = $settings['http_basic_auth'];
  if (empty($_SERVER['PHP_AUTH_USER'])
    || $_SERVER['PHP_AUTH_USER'] !== $auth['user']
    || $_SERVER['PHP_AUTH_PW'] !== $auth['password']
  ) {
    header('WWW-Authenticate: Basic realm="restricted site"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'user cancelled authentication';
    exit;
  }
}
