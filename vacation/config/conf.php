<?php
/* CONFIG START. DO NOT CHANGE ANYTHING IN OR AFTER THIS LINE. */
// $Horde: vacation/config/conf.xml,v 1.15.2.13 2009-08-18 12:54:49 jan Exp $
$conf['vacation']['path'] = '/usr/bin/vacation';
$conf['vacation']['default_subject'] = _("On vacation message");
$conf['vacation']['default_message'] = _("I'm on vacation and will not be reading my mail for a while.\nYour mail will be dealt with when I return.");
$conf['vacation']['subject'] = true;
$conf['vacation']['from'] = false;
$conf['user']['refused'] = array('root', 'bin', 'daemon', 'adm', 'lp', 'shutdown', 'halt', 'uucp', 'ftp', 'anonymous', 'nobody', 'httpd', 'operator', 'guest', 'diginext', 'bind', 'cyrus', 'courier', 'games', 'kmem', 'mailnull', 'man', 'mysql', 'news', 'postfix', 'sshd', 'tty', 'www');
$conf['menu']['apps'] = array();
/* CONFIG END. DO NOT CHANGE ANYTHING IN OR BEFORE THIS LINE. */

