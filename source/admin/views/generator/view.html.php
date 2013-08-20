<?php
/**
 * @package      com_pfdatagen
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2013 Tobias Kuhn. All rights reserved.
 * @license      http://www.gnu.org/licenses/gpl.html GNU/GPL, see LICENSE.txt
 */

defined('_JEXEC') or die();


jimport('joomla.application.component.view');


/**
 * Projectfork Data Generator View Class
 *
 */
class PFdatagenViewGenerator extends JViewLegacy
{
    protected $models;


    /**
     * Display the view
     *
     */
    public function display($tpl = null)
    {
        $this->models = PFdatagenHelper::getModelList();

        // Check for errors
        if (count($errors = $this->get('Errors'))) {
            JError::raiseError(500, implode("\n", $errors));
            return false;
        }

        if ($this->getLayout() !== 'modal') {
            $this->addToolbar();
        }

        parent::display($tpl);
    }


    /**
     * Add the page title and toolbar.
     *
     * @return  void
     */
    protected function addToolbar()
    {
        JToolBarHelper::title(JText::_('COM_PFDATAGEN'), 'article.png');
    }
}
