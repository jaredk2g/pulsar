<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @see http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', true);
date_default_timezone_set('America/Chicago');

require __DIR__.'/../vendor/autoload.php';

function modelValidate(&$value, array $options, Pulsar\Model $model)
{
    $model->getErrors()->add('Custom error message from callable', ['field' => $options['field']]);

    return false;
}
