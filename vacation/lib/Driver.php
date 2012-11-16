<?php
/**
 * Vacation_Driver defines an API for implementing systems for
 * setting vacation messages.
 *
 * Copyright 2000-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @author  Eric Rostetter <eric.rostetter@physics.utexas.edu>
 * @package Vacation
 */
abstract class Vacation_Driver
{
    /**
     * Hash containing configuration parameters.
     *
     * @var array
     */
    protected $_params = array();

    // Vacation message
    protected $_message;

    // Vacation message subject
    protected $_subject;

    // Vacation message from address
    protected $_from;

    // How often to send back the message
    protected $_howoften;

    // Beginnig date of validity
    protected $_fromdate;

    // End date of validity
    protected $_todate;

    // Is it active
    protected $_active;

    /**
     * Constructor.
     *
     * @param $params   A hash containing connection parameters.
     */
    public function __construct($params = array())
    {
        $this->_params = $params;
    }

    /**
     * Sets up vacation notices for a user.
     *
     * @abstract
     *
     * @param string $active    True if to be turned on, false otherwise
     * @param string $message   The text of the vacation notice.
     * @param string $subject   The subject of the vacation notice.
     * @param string $from      The From: address of the vacation notice.
     * @param string $alias     The alias email address.
     */
    abstract public function setVacation($active, $message, $subject, $from, $fromdate, $todate, $howoften, $alias = '');

    /**
     * Retrieves status of vacation for a user.
     *
     * @return mixed  Returns 'Y' if vacation is enabled for the user, 'N' if
     *                vacation is currently disabled, false if the status
     *                cannot be determined.
     */
    public function isActive()
    {
	if (!isset($this->_active)) {
	    $this->_processMessage();
	}

        // Check vacation flag.
        if ($this->_active === 'y' ||
            $this->_active === 'Y' ||
            $this->_active === $this->_params['enabled']) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Retrieves current vacation message.
     *
     *
     * @return string  The current vacation message, or false if none.
     */
    public function currentMessage()
    {
        if (!isset($this->_message)) {
            $this->_processMessage();
        }
        return $this->_message;
    }

    /**
     * Retrieves current vacation subject.
     *
     *
     * @return string  The current vacation subject, or false if none.
     */
    public function currentSubject()
    {
        if (!isset($this->_subject)) {
            $this->_processMessage();
        }
        return $this->_subject;
    }

    /**
     * Retrieves current vacation From: address.
     *
     *
     * @return string  The current vacation From: address, or false if none.
     */
    public function currentFrom()
    {
        if (!isset($this->_from)) {
            $this->_processMessage();
        }
        return $this->_from;
    }

    /**
     * Retrieves current vacation frequency of replies
     *
     *
     * @return string  The current frequency in hours, or false if none.
     */
    public function currentHowoften()
    {
        if (!isset($this->_from)) {
            $this->_processMessage();
        }
        return $this->_howoften;
    }
    public function currentFromdate()
    {
        if (!isset($this->_fromdate)) {
            $this->_processMessage();
        }
        return $this->_fromdate;
    }
    public function currentTodate()
    {
        if (!isset($this->_todate)) {
            $this->_processMessage();
        }
        return $this->_todate;
    }

    /**
     * Builds a vacation message.
     *
     * @param string $message  The text of the vacation notice.
     * @param string $subject  The subject of the vacation notice.
     * @param string $from     The From: address of the vacation notice.
     *
     * @return string  The complete vacation message including all headers.
     */
    public function _buildMessage($message, $subject, $from)
    {
        $vacationtxt = '';
        // Include the mail subject if the driver supports it.
        if ($this->_params['subject']) {
            $vacationtxt .= 'Subject: '
                . Horde_Mime::encode($subject, 'UTF-8') . "\n";
        }
        // Include the mail sender if the driver supports it.
        if ($this->_params['from']) {
            $vacationtxt .= 'From: '
                . Horde_Mime::encode($from, 'UTF-8') . "\n";
        }

        if (Horde_Mime::is8bit($message)) {
            $vacationtxt .= "Content-Transfer-Encoding: quoted-printable\n"
                . 'Content-Type: text/plain; charset=' . 'UTF-8'
                . "\n" . Horde_Mime::quotedPrintableEncode($message, "\n");
        } else {
            $vacationtxt .= $message;
        }

        return $vacationtxt;
    }

    /**
     * Processes the current vacation message.
     *
     */
    private function _processMessage()
    {
        $current_details = $this->_getUserDetails();
        if ($current_details === false) {
            return $current_details;
        }

        $this->_message = isset($current_details['message'])
            ? $current_details['message']
            : $GLOBALS['conf']['vacation']['default_message'];
        $this->_subject = isset($current_details['subject'])
            ? $current_details['subject']
            : $GLOBALS['conf']['vacation']['default_subject'];
        $this->_from    = isset($current_details['from'])
            ? $current_details['from']
            : $this->getFrom();
        $this->_howoften= isset($current_details['howoften'])
            ? $current_details['howoften']
            : 24;
        $this->_fromdate= isset($current_details['start'])
            ? $current_details['start']
            : time();
        $this->_todate= isset($current_details['end'])
            ? $current_details['end']
            : time()+24*3600;
	$this->_active= isset($current_details['active'])
	    ? $current_details['active']
	    : $this->_params['disabled'];
    }

    /**
     * Parses a vacation message.
     *
     * @param string $message   A vacation message.
     *
     * @return array  A hash with parsed results in the field 'message',
     *                'subject' and 'from'.
     */
    protected function _parseMessage($message)
    {  
        // Split the vacation text in a subject and a message if the driver
        // supports it.
        $subject = '';
	if ($this->_params['subject']) {
            if (preg_match('/^Subject: ([^\n]*)\n(.+)$/s',
                           $message, $matches)) {
                $subject = Horde_Mime::decode($matches[1], 'UTF-8');
                $message = $matches[2];
            }
        }

        // Split the vacation text in a sender and a message if the driver
        // supports it.
        $from = '';
        if ($this->_params['from']) {
            if (preg_match('/^From: ([^\n]*)\n(.+)$/s',
                           $message, $matches)) {
                $from = Horde_Mime::decode($matches[1], 'UTF-8');
                $message = $matches[2];
            } else {
                $from = $this->getFrom();
            }
        }

        // Detect Content-Type and Content-Transfer-Encoding headers.
        if (preg_match('/^Content-Transfer-Encoding: ([^\n]+)\n(.+)$/s',
                       $message, $matches)) {
            $message = $matches[2];
            if ($matches[1] == 'quoted-printable') {
                $message = quoted_printable_decode($message);
            }
        }
        if (preg_match('/^Content-Type: ([^\n]+)\n(.+)$/s',
                       $message, $matches)) {
            $message = $matches[2];
            if (preg_match('/^text\/plain; charset=(.*)$/',
                           $matches[1], $matches)) {
                $message = String::convertCharset($message, $matches[1]);
            }
        }

        return array('message' => $message,
                     'subject' => $subject,
                     'from' => $from);
    }

    /**
     * Retrieves the current vacation details for the user.
     *
     * @abstract
     *
     * @return array  Vacation details or PEAR_Error on failure.
     */
    abstract function _getUserDetails();

    /**
     * Returns the default From: address of the current user.
     *
     * @return string  The default From: address.
     */
    public function getFrom()
    {
	$identity = $GLOBALS['injector']
                ->getInstance('Horde_Core_Factory_Identity')
                ->create();
        // Default "From:" from identities, with name (name <address>)
        return $identity->getDefaultFromAddress(true);
    }


}
