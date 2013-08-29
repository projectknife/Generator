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
class PFdatagenModelTime extends JModelLegacy
{
	/**
	* Form model class name of the item to generate
	*
	* @var    string
	*/
	protected $model_type   = 'Time';

	/**
	* Form model class name prefix of the item to generate
	*
	* @var    string
	*/
	protected $model_prefix = 'PFtimeModel';


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
		$task 			= PFdatagenHelper::getRandomTask($project->id);
		
		if (!$task){
			return false;
		}
		
		$data      		= array();
		
		$parent_access 	= $project->access;

		$data['project_id'] 	= $project->id;
		$data['task_id']		= $task->id;
		$data['task_title'] 	= $task->title;
		$data['description'] 	= PFdatagenHelper::getRandomText();
		$data['log_date'] 		= PFdatagenHelper::getRandomPastDateTime($task->created, 0);
		$data['log_time'] 		= $this->getRandomTaskTime();
		$data['billable'] 		= $this->getRandomBillable();
		$data['rate']			= PFdatagenHelper::getRandomTaskRate($task->rate ? $task->rate : 0);
		$data['created'] 		= PFdatagenHelper::getRandomPastDateTime($task->created, 0);
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
	
	/**
     * Returns a random Task Log Time between 30 mins and 24 hours
	 *
     * @return    integer    The task log time
     */
	protected function getRandomTaskTime()
	{
		return rand(30, 1440);
	}
	
	/**
     * Returns a random Billable state
     *
     * @return    integer    The billable state
     */
    protected function getRandomBillable()
    {
        $billable = array(0, 1);

        return $billable[rand(0, 1)];
    }
}
