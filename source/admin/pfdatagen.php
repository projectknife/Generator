<?php
/**
 * @package      com_pfdatagen
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2013 Tobias Kuhn. All rights reserved.
 * @license      http://www.gnu.org/licenses/gpl.html GNU/GPL, see LICENSE.txt
 */

defined('_JEXEC') or die();


// Access check
if (!JFactory::getUser()->authorise('core.admin')) {
	return JError::raiseWarning(403, JText::_('JERROR_ALERTNOAUTHOR'));
}

// Include dependencies
jimport('joomla.application.component.controller');
jimport('joomla.application.component.helper');
jimport('projectfork.framework');

// Register classes to autoload
JLoader::register('PFdatagenHelper', JPATH_ADMINISTRATOR . '/components/com_pfdatagen/helpers/pfdatagen.php');

// Load language
$lang = JFactory::getLanguage();
$lang->load('com_pfdatagen', JPATH_ADMINISTRATOR);

$controller = JControllerLegacy::getInstance('PFdatagen');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();
