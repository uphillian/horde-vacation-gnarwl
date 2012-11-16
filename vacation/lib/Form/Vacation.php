<?php
/**
 * Vacation form class
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author   Josko Plazonic <plazonic@math.princeton.edu>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */
class Vacation_Form extends Horde_Form
{

   /**
     * The start date field.
     *
     * @var Horde_Form_Variable
     */
    protected $_start;

    /**
     * The end date field.
     *
     * @var Horde_Form_Variable
     */
    protected $_end;

    public function __construct($vars, $title = '', $name = null)
    {
        parent::__construct($vars, $title, $name);

	$howoften_options = array('1'=>'once in 60 minutes', '4'=>'once in 4 hours', '8'=>'once in 8 hours', '12'=>'once in 12 hours',
                               '24'=>'once a day', '48'=>'once in 2 days', '72'=>'once in 3 days', '120'=>'once in 5 days',
                               '168'=>'once in 7 days');

        $this->_start = $this->addVariable(_("Start of vacation:"), 'start', 'datetime', '');
        //$this->_start->setHelp('vacation-period');
        $this->_end = $this->addVariable(_("End of vacation:"), 'end', 'datetime', '');
        $v = $this->addVariable(_("Subject of vacation message:"), 'subject', 'text', false);
        //$v->setHelp('vacation-subject');
        $v = $this->addVariable(_("Vacation message:"), 'message', 'longtext', false, false, _("You can use special variables \$subject, \$sender and \$receiver in this text (e.g. Your email about \$subject will be ...)"), array(10, 70));
        //$v->setHelp('vacation-message');

        $v = $this->addVariable(_("How often to send vacation replies per sender:"), 'howoften', 'enum', false, false, null, array('enum'=>$howoften_options));
        //$v->setHelp('vacation-howoften');
        $this->addHidden('', 'active', 'boolean', false);
        $this->setButtons(_("Save"));
    }

    /**
     * Additional validate of start and end date fields.
     */
    public function validate($vars = null, $canAutoFill = false)
    {
        $valid = true;
        if (!parent::validate($vars, $canAutoFill)) {
            $valid = false;
        }

        $this->_start->getInfo($vars, $start);
        $this->_end->getInfo($vars, $end);
        if ($start && $end && $end < $start) {
            $valid = false;
            $this->_errors['end'] = _("Vacation end date is prior to start.");
        }
        if ($end && $end < mktime(0, 0, 0)) {
            $valid = false;
            $this->_errors['end'] = _("Vacation end date is prior to today.");
        }

        return $valid;
    }

    /**
     * Sets the form buttons.
     *
     * @param boolean $enabled  Whether the rule is currently enabled.
     */
    public function setCustomButtons($enabled)
    {  
        $this->setButtons(_("Save"));
        if ($enabled) {
            $this->appendButtons(_("Save and Disable"));
        } else {
            $this->appendButtons(_("Save and Enable"));
        }
	$this->appendButtons(_("Return to e-mail"));
    }
}

