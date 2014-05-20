<?php

//Deal with Symlink-Fuckup
if ( file_exists('abstract.php') ) {
    require_once('abstract.php');
} elseif ( file_exists(getcwd().'/'.'shell/abstract.php') ) {
    require_once(getcwd().'/'.'shell/abstract.php');
}

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
        print "Starting Index - Process (all, only required Parts)\n";

        /** @var Hackathon_AsyncIndex_Model_Manager $indexManager */
        $indexManager = Mage::getModel('hackathon_asyncindex/manager');
        
        
        $pCollection = Mage::getSingleton('index/indexer')->getProcessesCollection();

        /** @var Mage_Index_Model_Process $process */
        foreach ($pCollection as $process) {
            $indexManager->executePartialIndex($process);
        }

    }

}

$shell = new Hackathon_AsyncIndex_Shell();
$shell->run();
