<?php
/**
 * @name      OpenImporter
 * @copyright OpenImporter contributors
 * @license   BSD http://opensource.org/licenses/BSD-3-Clause
 *
 * @version 1.0 Alpha
 *
 * This file contains code based on:
 *
 * Simple Machines Forum (SMF)
 * copyright:	2011 Simple Machines (http://www.simplemachines.org)
 * license:	BSD, See included LICENSE.TXT for terms and conditions.
 */

@set_time_limit(600);
@set_exception_handler(array('ImportException', 'exception_handler'));
@set_error_handler(array('ImportException', 'error_handler_callback'), E_ALL);

// Clean up after unfriendly php.ini settings.
if (function_exists('set_magic_quotes_runtime') && version_compare(PHP_VERSION, '5.3.0') < 0)
	@set_magic_quotes_runtime(0);

error_reporting(E_ALL);
ignore_user_abort(true);
umask(0);

ob_start();

// disable gzip compression if possible
if (is_callable('apache_setenv'))
	apache_setenv('no-gzip', '1');

if (@ini_get('session.save_handler') == 'user')
	@ini_set('session.save_handler', 'files');
@session_start();

// Add slashes, as long as they aren't already being added.
if (function_exists('get_magic_quotes_gpc') && @get_magic_quotes_gpc() != 0)
	$_POST = stripslashes_recursive($_POST);

require_once(__DIR__ . '/OpenImporter/SplClassLoader.php');
$classLoader = new SplClassLoader(null, __DIR__ . '/OpenImporter');
$classLoader->register();
$template = new Template();

$import = new ImportManager(new Lang(__DIR__ . '/Languages'), $template, new Cookie(), new ResponseHeader());

$response = $import->getResponse();
$template->render($response);

die();