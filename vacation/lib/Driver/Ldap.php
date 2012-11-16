<?php
/**
 * The LDAP class attempts to change a user's vacation message stored in an LDAP
 * directory service.
 *
 * Copyright 2000-2012 Horde LLC (http://www.horde.org/)
 *
 * See http://www.horde.org/licenses/gpl.php for license information (GPL).
 *
 * @author  Josko Plazonic <plazonic@math.princeton.edu>
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @author  Tjeerd van der Zee <admin@xar.nl>
 * @author  Mattias Webjörn Eriksson <mattias@webjorn.org>
 * @author  Eric Jon Rostetter <eric.rostetter@physics.utexas.edu>
 * @author  Ralf Lang <lang@b1-systems.de>
 * @author  Jan Schneider <jan@horde.org>
 * @package Vacation
 */
class Vacation_Driver_Ldap extends Vacation_Driver
{
    /**
     * LDAP object.
     *
     * @var resource
     */
    protected $_ldap = false;

    /**
     * The user's DN.
     *
     * @var string
     */
    protected $_userdn;

    /**
     * Constructor.
     *
     * @param array $params  A hash containing connection parameters.
     *
     * @throws InvalidArgumentException
     */
    public function __construct($params = array())
    {
        foreach (array('basedn', 'ldap', 'uid') as $val) {
            if (!isset($params[$val])) {
                throw new InvalidArgumentException(__CLASS__ . ': Missing ' . $val . ' parameter.');
            }
        }

        $this->_ldap = $params['ldap'];
        unset($params['ldap']);

        $this->_params = array_merge(
            array('host' => 'localhost',
                  'port' => 389,
                  'uid' => 'uid',
                  'basedn' => '',
                  'tls' => false,
                  'realm' => '',
                  'filter' => null,
		  'subject' => true,
		  'from' => false,
		  'vacation' => 'mailAutoReplyText',
		  'active' => 'mailAutoReplyMode',
		  'enabled' => 'ON',
		  'disabled' => 'OFF',
		  'start' => '',
		  'end' => '',
                  'howoften' => ''),
            $params);

        if (!empty($this->_params['tls']) &&
            empty($this->_params['sslhost'])) {
            $this->_params['sslhost'] = $this->_params['host'];
        }
    }

    private function _connectToLdap()
    {
	$username = $GLOBALS['registry']->getAuth();
	// Append realm as username@realm if 'realm' parameter is set.
        if (!empty($this->_params['realm'])) {
            $username .= '@' . $this->_params['realm'];
        }

	// Get the user's dn from hook or fall back to Horde_Ldap::findUserDN.
        try {
            // findUserDN doesn't seem to work here. copying the bulk of the function to here instead.
	    $filter="(&(objectclass=*)(uid=$username))";
            $search = $this->_ldap->search(
            null,
            $filter,
            array('attributes' => array('username')));
	    
            if (!$search->count()) {
              throw new Horde_Exception_NotFound('DN for user ' . $user . ' not found');
            }
            $entry = $search->shiftEntry();
            $this->_userdn = $entry->currentDN();
        } catch (Horde_Exception_HookNotSet $e) {
	   // Josko's hack, won't work here
           // $this->_userdn = $this->_params['uid'] . '=' . $username . ',' . $this->_params['basedn'];
	   // we'll have to throw and exception sadly
           throw new Vacation_Exception($e);
        }

	// Bind first
	try {
            $this->_ldap->bind($this->_userdn, $GLOBALS['registry']->getAuthCredential('password'));
        } catch (Horde_Ldap_Exception $e) {
            throw new Vacation_Exception($e);
        }

	// Get existing user information.
        $entry = $this->_getUserEntry();
        if (!$entry) {
             throw new Vacation_Exception(_("User not found."));
        }

	return $entry;
    }

    private function _returnAttribute($entry, $attr) {
	if($entry->exists($this->_params[$attr])) {
	    return $entry->getValue($this->_params[$attr]);
	} else {
	    return false;
	}
    }

    public function setVacation($active, $message, $subject, $from, $vacationstart = "", $vacationend = "", $vacationhowoften = "48", $alias = '')
    {
	$entry = $this->_connectToLdap();

	// Prepare the message. \n->\n\n and UTF-8 encode.
        $full_message = $this->_buildMessage($message, $subject, $from);
        //$full_message = str_replace("\r\n", "\\n", $full_message);
        $full_message = ereg_replace("Subject:([^\n]*)", "Subject:\\1\n", $full_message);
        //$full_message = Horde_String::convertCharset($full_message, NLS::getCharset(), 'UTF-8');

	try {
	    $entry->replace(array($this->_params['vacation'] => $full_message), true);
	    $entry->replace(array($this->_params['active'] => $this->_params[$active ? 'enabled':'disabled']), true);
	    if (!empty($this->_params['start'])) {
		$entry->replace(array($this->_params['start'] => $vacationstart), true);
	    }
	    if (!empty($this->_params['end'])) {
		$entry->replace(array($this->_params['end'] => $vacationend), true);
	    }
	    if (!empty($this->_params['howoften'])) {
		$entry->replace(array($this->_params['howoften'] => $vacationhowoften), true);
	    }
	    $entry->update();
        } catch (Horde_Ldap_Exception $e) {
            throw new Vacation_Exception($e);
        }

        $res = $this->_setVacationAlias($userdn);

        return $res;
    }

    /**
     * Sets or creates a vacation mail alias.
     *
     * Some MTA/LDAP/Vacation implementations require an extra mail alias
     * (ex. user@example.org -> user@example.org, user@autoreply.example.org)
     *
     * You should override this method in your extended LDAP driver class, if
     * you need this feature.
     *
     * @param string $userdn  The LDAP DN for the current user.
     */
    private function _setVacationAlias($userdn)
    {
        return true;
    }

    /**
     * Deactivates the vacation notice.
     *
     * This does not delete the vacation message, just marks it as disabled.
     *
     * @param string $password  The password of the user.
     */
    public function unsetVacation()
    {
	$entry = $this->_connectToLdap();

	try {
	    $entry->replace(array($this->_params['active'] => $this->_params['disabled']), true);
	    $entry->update();
	} catch (Horde_Ldap_Exception $e) {
            throw new Vacation_Exception($e);
        }

	// Delete the unnecessary vacation alias (if present).
        $result = $this->_unsetVacationAlias($userdn);
    }

    /**
     * Unsets or removes a vacation mail alias.
     *
     * @see _setVacationAlias()
     *
     * @param string $userdn  The LDAP DN for the current user.
     */
    function _unsetVacationAlias($userdn)
    {
        return true;
    }

   /**
     * Retrieves the current vacation details for the user.
     *
     * @param string $password  The password for user.
     *
     * @return array  Vacation details or PEAR_Error on failure.
     */
    public function _getUserDetails()
    {
	// get the data first
	$entry = $this->_connectToLdap();

	// result array, set sane defaults
	$result = array(
		'message' => '',
		'subject' => '', 
		'from' => '', 
		'vacation' => '',
		'active' => $this->_params['disabled'],
		'start' => '',
		'end' => '',
		'howoften' => '48'
	);

	// note that values we are checking for are a tad different
	$all_attrs = array('vacation', 'start', 'end', 'howoften', 'active');
	foreach ($all_attrs as $x) {
		$v = $this->_returnAttribute($entry, $x);
		if ($v !== false) {
			$result[$x]=$v;
		}
	}
	// Prepare the message.
	$result['vacation'] = Horde_String::convertCharset($result['vacation'], 'UTF-8');

	// Parse message.
        $result = array_merge($result, $this->_parseMessage($result['vacation']));

	return $result;
    }

   /**
     * Returns the LDAP entry for the user.
     *
     * @return Horde_Ldap_Entry  The user's LDAP entry if it exists.
     * @throws Vacation_Exception
     */
    protected function _getUserEntry()
    {
        try {
            return $this->_ldap->getEntry($this->_userdn);
        } catch (Horde_Ldap_Exception $e) {
            throw new Vacation_Exception($e);
        }
    }
}
