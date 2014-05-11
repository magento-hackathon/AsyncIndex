<?php

//Deal with Symlink-Fuckup
if ( file_exists('abstract.php') )
    require_once('abstract.php');
else if ( file_exists(getcwd().'/'.'shell/abstract.php') )
    require_once(getcwd().'/'.'shell/abstract.php');

/**
 * Magento Compiler Shell Script
 *
 * @category    Mage
 * @package     Mage_Shell
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Hackathon_AsyncIndex_Shell extends Mage_Shell_Abstract
{

    /**
     * Run script
     *
     */
    public function run()
    {
        echo "Starting Index - Process (all, only required Parts)\n";
        Mage::getModel('index/process')->indexEvents();
        echo "Complete\n";
    }

}

$shell = new Hackathon_AsyncIndex_Shell();
$shell->run();
