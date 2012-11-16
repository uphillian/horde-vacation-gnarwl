<?php
/**
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/gpl.php.
 *
 * @author  Eric Rostetter <eric.rostetter@physics.utexas.edu>
 * @author  Jan Schneider <jan@horde.org>
 * @package Vacation
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('vacation');

$backends = Vacation::getBackends();
// Choose the prefered backend from config/backends.php.
foreach ($backends as $key => $current_backend) {
    if (!isset($backend_key) && substr($key, 0, 1) != '_') {
        $backend_key = $key;
    }
    if (Vacation::isPreferredBackend($current_backend)) {
        $backend_key = $key;
        break;
    }
}

// Create a Vacation_Driver instance.
try {
    $driver = $GLOBALS['injector']->getInstance('Vacation_Factory_Driver')->setBackends($backends)->create($backend_key);
}
catch (Vacation_Exception $e) {
    Horde::logMessage($e);
    $notification->push(_("Vacation module is not properly configured"),
                        'horde.error');
    Horde::url('', true)->redirect();
}


$userid = $registry->getAuth();

/* Load libraries. */
$vars = Horde_Variables::getDefaultVariables();
if ($vars->submitbutton == _("Return to e-mail")) {
    Horde::url('/imp', true)->redirect();
}

/* Build form. */
require_once __DIR__ . '/lib/Form/Vacation.php';
$form = new Vacation_Form($vars);

/* Perform requested actions. */
if ($form->validate($vars)) {
    $old_active = Horde_Util::getFormData('active');
    $form->getInfo($vars, $info);
    if ($vars->submitbutton == _("Save")) {
	$new_active = $old_active;
    } else {
	$new_active = ! $old_active;
    }
    try {
	$driver->setVacation($new_active, $info['message'], $info['subject'], '', $info['start'], $info['end'], $info['howoften']);
	if ($new_active != $old_active) {
	    $notification->push(_("Vacation settings saved and vacation message " . ($new_active?"enabled":"disabled")), 'horde.success');
	} else {
	    $notification->push(_("Vacation settings saved"), 'horde.success');
	}
    } catch (Vacation_Exception $e) {
	$notification->push($e);
    }
}

/* Add buttons depending on the above actions. */
$form->setCustomButtons($driver->isActive());

/* Set default values. */
$vars->set('howoften', $driver->currentHowoften());
$vars->set('subject', $driver->currentSubject());
$vars->set('message', $driver->currentMessage());
$vars->set('start', $driver->currentFromdate());
$vars->set('end', $driver->currentTodate());
$vars->set('active', $driver->isActive());

/* Set form title. */
$form_title = _("Vacation message");
if ($driver->isActive()) {
    $form_title .= ' [<span class="horde-success">' . _("Enabled") . '</span>]';
} else {
    $form_title .= ' [<span class="horde-form-error">' . _("Disabled") . '</span>]';
}
$form->setTitle($form_title);

Horde::startBuffer();
$form->renderActive(new Horde_Form_Renderer(array('encode_title' => false)), $vars, Horde::url('index.php'), 'post');
$form_output = Horde::endBuffer();

//$menu = Ingo::menu();
$page_output->header(array(
    'title' => _("Vacation Edit")
));
// echo $menu;
$notification->notify(array('listeners' => 'status'));
echo $form_output;
$page_output->footer();

