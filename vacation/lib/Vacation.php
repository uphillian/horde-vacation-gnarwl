<?php
/**
 * Vacation base class.
 *
 * Copyright 2000-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
 *
 * @author Mike Cochrane <mike@graftonhall.co.nz>
 * @package Vacation
 */
class Vacation
{
    static public function getBackends()
    {
        $allbackends = Horde::loadConfiguration('backends.php', 'backends', 'vacation');
        if (!isset($allbackends) || !is_array($allbackends)) {
            throw new Vacation_Exception(_("No backends configured in backends.php"));
        }

        $backends = array();
        foreach ($allbackends as $name => $backend) {
            if (!empty($backend['disabled'])) {
                continue;
            }

            /* Make sure the 'params' entry exists. */
            if (!isset($backend['params'])) {
                $backend['params'] = array();
            }

            if (!empty($backend['preferred'])) {
                if (is_array($backend['preferred'])) {
                    foreach ($backend['preferred'] as $val) {
                        if (($val == $_SERVER['SERVER_NAME']) ||
                            ($val == $_SERVER['HTTP_HOST'])) {
                            $backends[$name] = $backend;
                        }
                    }
                } elseif (($backend['preferred'] == $_SERVER['SERVER_NAME']) ||
                          ($backend['preferred'] == $_SERVER['HTTP_HOST'])) {
                    $backends[$name] = $backend;
                }
            } else {
                $backends[$name] = $backend;
            }
        }

        /* Check for valid backend configuration. */
        if (empty($backends)) {
            throw new Vacation_Exception(_("No backend configured for this host"));
        }

        return $backends;
    }

    /**
     * Determines if the given backend is the "preferred" backend for this web
     * server.
     *
     * This decision is based on the global 'SERVER_NAME' and 'HTTP_HOST'
     * server variables and the contents of the 'preferred' field in the
     * backend's definition.  The 'preferred' field may take a single value or
     * an array of multiple values.
     *
     * @param array $backend  A complete backend entry from the $backends hash.
     *
     * @return boolean  True if this entry is "preferred".
     */
    static public function isPreferredBackend($backend)
    {
        if (!empty($backend['preferred'])) {
            if (is_array($backend['preferred'])) {
                foreach ($backend['preferred'] as $backend) {
                    if ($backend == $_SERVER['SERVER_NAME'] ||
                        $backend == $_SERVER['HTTP_HOST']) {
                        return true;
                    }
                }
            } elseif ($backend['preferred'] == $_SERVER['SERVER_NAME'] ||
                      $backend['preferred'] == $_SERVER['HTTP_HOST']) {
                return true;
            }
        }

        return false;
    }

}
