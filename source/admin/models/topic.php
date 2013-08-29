<?php
/**
 * @package      com_pfdatagen
 *
 * @author       Tobias Kuhn (eaxs) and Kon Angelopoulos (angek)
 * @copyright    Copyright (C) 2013 Tobias Kuhn. All rights reserved.
 * @license      http://www.gnu.org/licenses/gpl.html GNU/GPL, see LICENSE.txt
 */

defined('_JEXEC') or die();


jimport('joomla.application.component.modellist');


/**
 * Projectfork Data Generator Milestone Model
 *
 */
class PFdatagenModelTopic extends JModelLegacy
{
	/**
	* Form model class name of the item to generate
	*
	* @var    string
	*/
	protected $model_type   = 'Topic';

	/**
	* Form model class name prefix of the item to generate
	*
	* @var    string
	*/
	protected $model_prefix = 'PFforumModel';


	/**
	* Method to generate an item
	*
	* @return    boolean    True on success, False on error
	*/
	public function generate()
	{
		// Attempt to get the form model
		$model = $this->getFormModel();

		if ($model === false) {
			$this->setError(JText::_('COM_PFDATAGEN_GENERATE_ERROR_FORM_MODEL_NOT_FOUND'));
			return false;
		}

		// Prepare item data
		$data = $this->getFormData();

		// Pre-process data
		$data = $this->preProcess($data);

		// Create the item
		$success = $model->save($data);

		// Check for errors
		if (!$success) {
			$error = $model->getError();

			if (!empty($error)) $this->setError(JText::_($error));

			return false;
		}

		// Run any post-process operations
		if (!$this->postProcess($model, $data)) {
			return false;
		}

		return true;
	}


	/**
	 * Generates the item data and then returns it
	 *
	 * @return    array    $data    Item data
	 */
	protected function getFormData()
	{
		$nulldate 		= $this->getDbo()->getNullDate();
		$project 		= PFdatagenHelper::getRandomProject();
		
		if (!$project){
			return false;
		}
		
		$data      		= array();
		
		$parent_access 	= $project->access;

		$data['project_id'] 	= $project->id;		
		$data['title'] 			= PFdatagenHelper::getRandomString(64, 'Topic - ');
		$data['description'] 	= PFdatagenHelper::getRandomText();		
		$data['created'] 		= PFdatagenHelper::getRandomPastDateTime($project->created, 0);
		$data['created_by'] 	= PFdatagenHelper::getRandomUserId();
		$data['modified_by'] 	= PFdatagenHelper::getRandomUserId(50);
		$data['modified'] 		= PFdatagenHelper::getRandomPastDateTime($data['created'], ($data['modified_by'] ? 0 : 100));
		$data['state'] 			= PFdatagenHelper::getRandomState();
		$data['rules'] 			= PFdatagenHelper::getRandomUserGroupIds($parent_access);
		$data['access'] 		= $parent_access;		

		return $data;
	}


	/**
	* Alters the form data and then returns it
	*
	* @param     array    $data    The original item data
	*
	* @return    array    $data    The modified data
	*/
	protected function preProcess($data)
	{
		return $data;
	}


	/**
	* Performs any other action once the item is created
	*
	* @param     object     $model    The item form model instance
	* @param     array      $data     The generated form data
	*
	* @return    boolean              True on success
	*/
	protected function postProcess(&$model, $data)
	{
		// Get the project id
		$name  = $model->getName();
		$id    = $model->getState($name . '.id');

		$db    = $this->getDbo();
		$query = $db->getQuery(true);

		// Randomise the creation date
		$query->update('#__pf_timesheet')
			->set('created = ' . $db->quote($data['created']))
			->where('id = ' . (int) $id);

		$db->setQuery($query);
		$db->execute();

		return true;
	}


	/**
	* Method to get a form model instance of the item to generate.
	*
	* @return    mixed    $model    Model instance on success, False on error
	*/
	protected function getFormModel()
	{
		static $model = null;

		// Check if in cache
		if (!is_null($model)) {
			// Check if model exists
			if ($model === false) return false;

			$name  = $model->getName();
			$table = $model->getTable();

			// Reset the table data
			$table->reset();
			$table->id = null;

			// Reset the model states
			$model->setState($name . '.id', 0);
			$model->setState($name . '.new', true);

			return $model;
		}

		// Model not yet cached, get new instance
		$config = array('ignore_request' => true);
		$model  = $this->getInstance($this->model_type, $this->model_prefix, $config);

		return $model;
	}
}
