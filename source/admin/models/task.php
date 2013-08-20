<?php
/**
 * @package      com_pfdatagen
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2013 Tobias Kuhn. All rights reserved.
 * @license      http://www.gnu.org/licenses/gpl.html GNU/GPL, see LICENSE.txt
 */

defined('_JEXEC') or die();


jimport('joomla.application.component.modellist');


/**
 * Projectfork Data Generator Milestone Model
 *
 */
class PFdatagenModelTask extends JModelLegacy
{
    /**
     * Form model class name of the item to generate
     *
     * @var    string
     */
    protected $model_type   = 'Task';

    /**
     * Form model class name prefix of the item to generate
     *
     * @var    string
     */
    protected $model_prefix = 'PFtasksModel';


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
        $nulldate  = $this->getDbo()->getNullDate();
        $project   = PFdatagenHelper::getRandomProject();
        $milestone = PFdatagenHelper::getRandomMilestone($project->id);
        $list      = PFdatagenHelper::getRandomTasklist($project->id, (rand(1, 100) <= 50 ? -1 : ($milestone == false ? -1 : $milestone->id)));
        $data      = array();

        $parent_start = ($milestone == false ? $project->start_date : ($milestone->start_date == $nulldate ? $project->start_date : $milestone->start_date));
        $parent_end   = ($milestone == false ? $project->end_date   : ($milestone->end_date == $nulldate   ? $project->end_date   : $milestone->end_date));

        if ($list) {
            $parent_access = $list->access;
        }
        elseif ($milestone) {
            $parent_access = $milestone->access;
        }
        else {
            $parent_access = $project->access;
        }

        $data['project_id']  = $project->id;
        $data['milestone_id']= ($milestone == false ? 0 : $milestone->id);
        $data['list_id']     = ($list == false ? 0 : $list->id);
        $data['title']       = PFdatagenHelper::getRandomString(64, 'Task - ');
        $data['description'] = PFdatagenHelper::getRandomText();
        $data['created']     = PFdatagenHelper::getRandomPastDateTime($project->created, 0);
        $data['created_by']  = PFdatagenHelper::getRandomUserId();
        $data['modified_by'] = PFdatagenHelper::getRandomUserId(50);
        $data['modified']    = PFdatagenHelper::getRandomPastDateTime($data['created'], ($data['modified_by'] ? 0 : 100));
        $data['state']       = PFdatagenHelper::getRandomState();
        $data['start_date']  = PFdatagenHelper::getRandomPastDateTime(($parent_start == $nulldate ? 30 : $parent_start));
        $data['end_date']    = PFdatagenHelper::getRandomFutureDateTime(($parent_end == $nulldate ? 90 : $parent_end));
        $data['rules']       = PFdatagenHelper::getRandomUserGroupIds($parent_access);
        $data['access']      = $parent_access;
        $data['labels']      = PFdatagenHelper::getRandomLabelIds($project->id, 'com_pftasks.task');
        $data['priority']    = rand(1, 5);
        $data['complete']    = rand(0, 1);
        $data['completed']   = ($data['complete'] ? PFdatagenHelper::getRandomPastDateTime($data['created'], 0) : $nulldate);
        $data['completed_by']= ($data['complete'] ? PFdatagenHelper::getRandomUserId() : 0);
        $data['rate']        = rand(0, 100) . '.' . rand(0, 99);
        $data['estimate']    = rand(0, 168) * 3600;
        $data['users']       = $this->getRandomUsers();
        $data['dependency']  = $this->getRandomDependencies($project->id, $data['end_date']);

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
        $query->update('#__pf_tasks')
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
     * Returns up to 5 random user id's
     *
     * @return array $users The user id's
     */
    protected function getRandomUsers()
    {
        $users = array();
        $i = 0;

        while ($i < 5)
        {
            $uid = PFdatagenHelper::getRandomUserId();

            if (!in_array($uid, $users)) {
                $users[] = $uid;
            }

            $i++;
        }

        return $users;
    }


    protected function getRandomDependencies($project, $end)
    {
        $db       = JFactory::getDbo();
        $query    = $db->getQuery(true);
        $nulldate = $db->getNullDate();
        $dep      = array();

        $query->select('id')
              ->from('#__pf_tasks')
              ->where('project_id = ' . $project)
              ->where('start_date < ' . $db->quote($end));

        $db->setQuery($query);
        $tasks = $db->loadColumn();

        if (empty($tasks)) return array();

        $count = count($tasks);
        $max   = rand(0, 5);
        $i     = 0;

        if (!$count) return array();

        while ($i < $max)
        {
            $id = $tasks[rand(0, $count - 1)];

            if (!in_array($id, $dep)) {
                $dep[] = $id;
            }

            $i++;
        }

        return $dep;
    }
}
