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
jimport('joomla.filesystem.folder');


/**
 * Projectfork Data Generator Project Model
 *
 */
class PFdatagenModelProject extends JModelLegacy
{
    /**
     * Form model class name of the item to generate
     *
     * @var    string    
     */
    protected $model_type   = 'Project';

    /**
     * Form model class name prefix of the item to generate
     *
     * @var    string    
     */
    protected $model_prefix = 'PFprojectsModel';


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
        $data = array();

        $data['catid']       = PFdatagenHelper::getRandomCategoryId('com_pfprojects');
        $data['title']       = PFdatagenHelper::getRandomString(64, 'Project - ');
        $data['description'] = PFdatagenHelper::getRandomText();
        $data['created']     = PFdatagenHelper::getRandomPastDateTime(30, 0);
        $data['created_by']  = PFdatagenHelper::getRandomUserId();
        $data['modified_by'] = PFdatagenHelper::getRandomUserId(50);
        $data['modified']    = PFdatagenHelper::getRandomPastDateTime($data['created'], ($data['modified_by'] ? 0 : 100));
        $data['state']       = PFdatagenHelper::getRandomState();
        $data['start_date']  = PFdatagenHelper::getRandomPastDateTime(30);
        $data['end_date']    = PFdatagenHelper::getRandomFutureDateTime(90);
        $data['rules']       = PFdatagenHelper::getRandomUserGroupIds();
        $data['labels']      = $this->getRandomLabels();

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
        static $base_path = null;

        // Get the base upload path if not set
        if (is_null($base_path)) {
            $path = JComponentHelper::getParams('com_pfrepo')->get('repopath');

            if (empty($path)) $path = 'media/com_projectfork/repo';

            $base_path = JPATH_SITE . '/' . $path;
        }

        // Get the project id
        $name  = $model->getName();
        $id    = $model->getState($name . '.id');

        $db    = $this->getDbo();
        $query = $db->getQuery(true);

        // Get the project alias
        $query->select('alias')
              ->from('#__pf_projects')
              ->where('id = ' . $id);

        $db->setQuery($query);
        $alias = $db->loadResult();

        // Delete the project repository folder
        $dir = $base_path . '/' . $alias;

        if (is_dir($dir) && !empty($alias)) {
            JFolder::delete($dir);
        }

        // Randomise the creation date
        $query->clear();
        $query->update('#__pf_projects')
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
     * Returns a list of project labels
     *
     * @return    array    
     */
    protected function getRandomLabels()
    {
        // 50% chance that no labels are created
        if (rand(1, 100) <= 50) return array();

        $labels   = array();
        $elements = array(
            'com_pfprojects.project',
            'com_pfmilestones.milestone',
            'com_pftasks.task',
            'com_pfforum.topic',
            'com_pfrepo.directory',
            'com_pfrepo.note',
            'com_pfrepo.file'
        );

        $styles = array(
            '',
            'label-success',
            'label-warning',
            'label-important',
            'label-info',
            'label-inverse'
        );

        foreach ($elements AS $element)
        {
            $data = array('title' => array(), 'style' => array(), 'id' => array());
            $i    = 0;
            $x    = 0;

            while ($i < 5)
            {
                // 50% chance that the label is created
                if (rand(0, 100) <= 50) {
                    $data['title'][$x] = PFdatagenHelper::getRandomString(24, 'Label - ');
                    $data['style'][$x] = $styles[rand(0, 5)];
                    $data['id'][$x]    = '';

                    $x++;
                }

                $i++;
            }

            if ($x) {
                $labels[$element] = $data;
            }
        }

        return $labels;
    }
}
