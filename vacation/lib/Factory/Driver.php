<?php
/**
 * A Horde_Injector based Vacation_Driver factory.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
 *
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl.php
 * @package  Vacation
 */
class Vacation_Factory_Driver extends Horde_Core_Factory_Base
{
    /**
     * Backend configurations.
     *
     * @var array
     */
    protected $_backends = array();

    /**
     * Created Vacation_Driver instances.
     *
     * @var array
     */
    private $_instances = array();

    /**
     * Returns the Vacation_Driver instance.
     *
     * @param string $name   A string containing the internal name of this
     *                       backend.
     * @param array $params  Any backend parameters if not the defaults.
     *
     * @return Vacation_Driver  The singleton instance.
     * @throws Vacation_Exception
     */
    public function create($name, $params = array() )
    {
        if (!empty($params['is_subdriver'])) {
            $backends = array($name => $params);
        } else {
            $backends = $this->getBackends();
        }

        if (empty($backends[$name])) {
            throw new Vacation_Exception(sprintf(_("The vacation backend \"%s\" does not exist."), $name));
        }
        $backend = $backends[$name];

        if (!isset($this->_instances[$name])) {
            $class = 'Vacation_Driver_' . Horde_String::ucfirst(basename($backend['driver']));
            if (!class_exists($class)) {
                throw new Vacation_Exception(sprintf(_("Unable to load the definition of %s."), $class));
            }

            if (empty($backend['params'])) {
                $backend['params'] = array();
            }
            if (empty($backend['policy'])) {
                $backend['policy'] = array();
            }
            if (!empty($params)) {
                $backend['params'] = array_merge($backend['params'], $params);
            }

            switch ($class) {
            case 'Vacation_Driver_Ldap':
                if (isset($backend['params']['host'])) {
                    $backend['params']['hostspec'] = $backend['params']['host'];
                }

                try {
                    $backend['params']['ldap'] = new Horde_Ldap($backend['params']);
                } catch (Horde_Ldap_Exception $e) {
                    throw new Vacation_Exception($e);
                }
                break;

            /* more to come later as drivers are upgraded to H4 / PHP5 */
            }

            try {
                $driver = new $class($backend['params']);
            } catch (Vacation_Exception $e) {
                throw $e;
            } catch (Exception $e) {
                throw new Vacation_Exception($e);
            }

            /* Shouldn't we fetch policy from backend and inject some handler
             * class here? */

            if (!empty($backend['params']['is_subdriver'])) {
                return $driver;
            }

            $this->_instances[$name] = $driver;
        }

        return $this->_instances[$name];
    }

    /**
     * Sets the backends available in this factory.
     *
     * @param array $backends  A list of backends in the format of backends.php.
     *
     * @return Vacation_Factory_Driver  The object itself for fluid interface.
     */
    public function setBackends(array $backends)
    {
        $this->_backends = $backends;
        return $this;
    }

    /**
     * Returns the backends available in this factory.
     *
     * @return array  A list of backends in the format of backends.php.
     * @throws Vacation_Exception if no backends have been set.
     */
    public function getBackends()
    {
        if (empty($this->_backends)) {
            throw new Vacation_Exception('No backends have been set before getBackends() was called');
        }
        return $this->_backends;
    }
}
