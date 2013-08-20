<?php
/**
 * @package      com_pfdatagen
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2013 Tobias Kuhn. All rights reserved.
 * @license      http://www.gnu.org/licenses/gpl.html GNU/GPL, see LICENSE.txt
 */

defined('_JEXEC') or die();


/**
 * Projectfork Data Generator JSON Controller
 *
 */
class PFdatagenControllerGenerator extends JControllerAdmin
{
    protected $model;

    protected $limit;

    protected $limitstart;

    protected $total;


    /**
     * Constructor.
     *
     * @param    array    $config    An optional associative array of configuration settings.
     */
    public function __construct($config = array())
    {
        parent::__construct($config);

        $app = JFactory::getApplication();

        if (!isset($config['model'])) {
            $config['model'] = $app->input->get('model', null, 'cmd');
        }

        if (!isset($config['limit'])) {
            $config['limit'] = $app->input->get('limit', 0, 'uint');
        }

        if (!isset($config['limitstart'])) {
            $config['limitstart'] = $app->input->get('limitstart', 0, 'uint');
        }

        if (!isset($config['total'])) {
            $config['total'] = $app->input->get('total', 0, 'uint');
        }

        $this->model      = $config['model'];
        $this->limit      = $config['limit'];
        $this->limitstart = $config['limitstart'];
        $this->total      = $config['total'];

        // Validate requested model
        $valid_models = PFdatagenHelper::getModelList();

        if (!isset($valid_models[$this->model])) {
            $this->model = null;
        }
    }


    /**
     * Proxy for getModel.
     *
     * @param     string    $name      The name of the model.
     * @param     string    $prefix    The prefix for the class name.
     * @param     array     $config    Configuration array for model. Optional.
     *
     * @return    object
     */
    public function getModel($name = '', $prefix = 'PFdatagenModel', $config = array('ignore_request' => true))
    {
        // Override model name
        $name = $this->model;

        if (empty($name)) {
            $model = false;
        }
        else {
            $model = parent::getModel($name, $prefix, $config);
        }

        return $model;
    }


    /**
     * Generates Projectfork data.
     *
     * @return    void
     */
    public function generate()
    {
        $model = $this->getModel();

        if ($model === false) {
            $this->sendResponse(false, JText::sprintf('COM_PFDATAGEN_GENERATE_ERROR_MODEL_NOT_FOUND', $this->model));
        }

        $i = 0;

        while ($this->limitstart < $this->total)
        {
            if (!$model->generate()) {
                $this->sendResponse(false, $model->getError());
            }

            $this->limitstart++;
            $i++;

            if ($i > $this->limit) break;
        }

        $this->sendResponse();
    }


    /**
     * Sends a JSON encoded response
     *
     * @param     boolean    $success    Whether the request was successful or not. Optional. Defaults to True.
     * @param     string     $error      Optional error message
     *
     * @return    void
     */
    protected function sendResponse($success = true, $error = null)
    {
        // Set the MIME type for JSON output.
        JFactory::getDocument()->setMimeEncoding('application/json');

        // Change the suggested filename.
        JResponse::setHeader('Content-Disposition', 'attachment;filename="generator.json"');

        // Create response data
        $rsp = array('success' => $success);

        if (!empty($error)) {
            $rsp['error'] = $error;
        }

        // Output the JSON data.
        echo json_encode($rsp);

        jexit();
    }
}
