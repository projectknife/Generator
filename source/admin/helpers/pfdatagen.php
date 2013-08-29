<?php
/**
 * @package      com_pfdatagen
 *
 * @author       Tobias Kuhn (eaxs)
 * @copyright    Copyright (C) 2013 Tobias Kuhn. All rights reserved.
 * @license      http://www.gnu.org/licenses/gpl.html GNU/GPL, see LICENSE.txt
 */

defined('_JEXEC') or die();


// Include lorem ipsum class
require_once dirname(__FILE__) . '/loremipsum.php';


/**
 * Projectfork Data Generator Helper Class
 *
 */
abstract class PFdatagenHelper
{
    /**
     * The component name
     *
     * @var    string
     */
    public static $extension = 'com_pfdatagen';


    /**
     * Returns a list of available models
     *
     * @return    array    $list    The list of available models
     */
    public static function getModelList()
    {
        $list = array();

        //$list['group'] 			= JText::_('COM_PFDATAGEN_MODEL_GROUPS');
        //$list['user'] 			= JText::_('COM_PFDATAGEN_MODEL_USERS');
        $list['project'] 		= JText::_('COM_PFDATAGEN_MODEL_PROJECTS');
        $list['milestone'] 		= JText::_('COM_PFDATAGEN_MODEL_MILESTONES');
        $list['tasklist'] 		= JText::_('COM_PFDATAGEN_MODEL_TASKLISTS');
        $list['task'] 			= JText::_('COM_PFDATAGEN_MODEL_TASKS');
        $list['time'] 			= JText::_('COM_PFDATAGEN_MODEL_TIME');
        $list['topic'] 			= JText::_('COM_PFDATAGEN_MODEL_TOPICS');
        $list['reply'] 			= JText::_('COM_PFDATAGEN_MODEL_REPLIES');
        //$list['directory'] 		= JText::_('COM_PFDATAGEN_MODEL_DIRECTORIES');
        //$list['file'] 			= JText::_('COM_PFDATAGEN_MODEL_FILES');
        //$list['note'] 			= JText::_('COM_PFDATAGEN_MODEL_NOTES');
        //$list['comment'] 		= JText::_('COM_PFDATAGEN_MODEL_COMMENTS');

        return $list;
    }


    /**
     * Generates a random string
     *
     * @param     integer    $max       Maximum string length
     * @param     string     $prefix    String prefix
     *
     * @return               $string    The generated string
     */
    public static function getRandomString($max = 64, $prefix = '')
    {
        $chars    = ' 0123456789 abcdefghijklmnopqrstuvwxyz ABCDEFGHIJKLMNOPQRSTUVWXYZ - _ ';
        $char_max = strlen($chars) - 1;

        $string = $prefix;
        $length = strlen($string);
        $max    = rand(($length ? ($length + 1) : 8), $max);

        while($length < $max)
        {
            $string .= $chars[rand(0, $char_max)];

            $length++;
        }

        return $string;
    }


    /**
     * Generates a random lorem ipsum tet
     *
     * @param     integer    $min       Minimum word count
     * @param     integer    $max       Maximum word count
     * @param     string     $format    Output format. Can be "html" or "plain"
     *
     * @return    string                The generated text
     */
    public static function getRandomText($min = 0, $max = 300, $format = 'html')
    {
        static $class = null;

        if (is_null($class)) {
            $class = new LoremIpsumGenerator();
        }

        $count = rand($min, $max);

        if ($count == 0) return '<p></p>';

        return $class->getContent($count);
    }


    /**
     * Returns a random id of an existing user
     *
     * @param     integer    $null_chance    Chance in percent to return no one (0)
     *
     * @return    integer    $id             The user id
     */
    public static function getRandomUserId($null_chance = 0)
    {
        static $min   = null;
        static $max   = null;
        static $cache = array();

        if (is_null($min) || is_null($max)) {
            $db    = JFactory::getDbo();
            $query = $db->getQuery(true);

            $query->select('id')
                  ->from('#__users')
                  ->order('id ASC');

            $db->setQuery($query, 0, 1);
            $min = (int) $db->loadResult();

            $query->clear();
            $query->select('id')
                  ->from('#__users')
                  ->order('id DESC');

            $db->setQuery($query, 0, 1);
            $max = (int) $db->loadResult();
        }

        if (rand(1, 100) <= $null_chance) {
            return 0;
        }

        $id = rand($min, $max);

        if (isset($cache[$id])) {
            if ($cache[$id] == false) {
                return self::getRandomUserId();
            }

            return $cache[$id];
        }

        $db    = JFactory::getDbo();
        $query = $db->getQuery(true);

        $query->select('id')
              ->from('#__users')
              ->where('id = ' . $id);

        $db->setQuery($query);
        $exists = (int) $db->loadResult();

        $cache[$id] = ($exists == $id ? $id : false);

        return $cache[$id];
    }


    /**
     * Returns a list of user group id's
     *
     * @param     integer    $access    Optional access level constraint
     * @param     integer    $max       Maximum number of groups to return
     *
     * @return    array                 List containing the groups
     */
    public static function getRandomUserGroupIds($access = 0, $max = 5)
    {
        static $cache = array();

        if (!isset($cache[$access])) {
            $db    = JFactory::getDbo();
            $query = $db->getQuery(true);

            if ($access) {
                $query->select('rules')
                      ->from('#__viewlevels')
                      ->where('id = ' . (int) $access);

                $db->setQuery($query);
                $pks = json_decode($db->loadResult());

                if (!count($pks)) {
                    $result = array();
                }
                else {
                    $query->clear();
                    $query->select('a.id')
                          ->from($db->quoteName('#__usergroups') . ' AS a, ' . $db->quoteName('#__usergroups') . ' AS b')
                          ->where('a.lft BETWEEN b.lft AND b.rgt')
                          ->where('b.id IN(' . implode(', ', $pks) . ')')
                          ->group('a.id, a.title, a.lft, a.rgt')
                          ->order('a.lft ASC');

                    $db->setQuery($query);
                    $result = $db->loadColumn();

                    if (empty($result)) $result = array();
                }
            }
            else {
                $query->select('id')
                      ->from('#__usergroups')
                      ->where('id NOT IN(1,9)');

                $db->setQuery($query);
                $result = $db->loadColumn();

                if (empty($result)) $result = array();
            }

            $cache[$access] = $result;

            return self::getRandomUserGroupIds($access, $max);
        }

        $count = count($cache[$access]);

        if ($count == 0)   return array();
        if ($max > $count) $max = $count;

        $ids = array();
        $i   = 0;

        while($i < $max)
        {
            $id = $cache[$access][rand(0, $max - 1)];

            if (!in_array($id, $ids)) {
                $ids[] = $id;
            }

            $i++;
        }

        return $ids;
    }


    /**
     * Returns a random extension category id
     *
     * @param     string     $extension      The extension name. Eg. com_pfprojects
     * @param     integer    $null_chance    Chance in percent, that no category id is returned (0)
     *
     * @return    integer                    A category id
     */
    public static function getRandomCategoryId($extension, $null_chance = 30)
    {
        static $cache = array();

        if (rand(1, 100) <= $null_chance) {
            return 0;
        }

        if (isset($cache[$extension])) {
            if (!$cache[$extension]) return 0;

            return $cache[$extension][rand(0, count($cache[$extension]) - 1)];
        }

        $db    = JFactory::getDbo();
        $query = $db->getQuery(true);

        $query->select('id')
              ->from('#__categories')
              ->where('extension = ' . $db->quote($extension));

        $db->setQuery($query);
        $result = $db->loadColumn();

        if (empty($result)) {
            $cache[$extension] = false;

            return false;
        }

        $cache[$extension] = $result;

        return $cache[$extension][rand(0, count($cache[$extension]) - 1)];
    }


    /**
     * Returns a sql date time string that lies in the past
     *
     * @param     mixed      $max_days       Maximum past distance. Also accepts a date time string
     * @param     integer    $null_chance    Chance in percent, that the date returned equals the sql null date
     *
     * @return    string                     The generated date
     */
    public static function getRandomPastDateTime($max_days = 365, $null_chance = 30)
    {
        if (rand(1, 100) <= $null_chance) {
            return '0000-00-00 00:00:00';
        }

        if (is_string($max_days)) {
            if ($max_days == '0000-00-00 00:00:00') {
                $max_days = rand(1, 365);
            }
            else {
                $max_days = round((time() - strtotime($max_days)) / 86400);
            }
        }
        elseif (empty($max_days)) {
            $max_days = rand(1, 365);
        }

        return date('Y-m-d H:i:s', (time() - (rand(1, $max_days) * 86400)));
    }


    /**
     * Returns a sql date time string that lies in the future
     *
     * @param     mixed      $max_days       Maximum future distance. Also accepts a date time string
     * @param     integer    $null_chance    Chance in percent, that the date returned equals the sql null date
     *
     * @return    string                     The generated date
     */
    public static function getRandomFutureDateTime($max_days = 365, $null_chance = 30)
    {
        if (rand(1, 100) <= $null_chance) {
            return '0000-00-00 00:00:00';
        }

        if (is_string($max_days)) {
            if ($max_days == '0000-00-00 00:00:00') {
                $max_days = rand(1, 365);
            }
            else {
                $max_days = round((strtotime($max_days) - time()) / 86400);
            }
        }
        elseif (empty($max_days)) {
            $max_days = rand(1, 365);
        }

        return date('Y-m-d H:i:s', (time() + (rand(1, $max_days) * 86400)));
    }


    /**
     * Returns a random publishing state
     *
     * @return    integer    The publishing state
     */
    public static function getRandomState()
    {
        $states = array(-2, 0, 1, 2);

        return $states[rand(0, 3)];
    }


    /**
     * Returns a random project record object
     *
     * @return    object    The project record
     */
    public static function getRandomProject()
    {
        static $min   = null;
        static $max   = null;
        static $cache = array();

        if (is_null($min) || is_null($max)) {
            $db    = JFactory::getDbo();
            $query = $db->getQuery(true);

            $query->select('id')
                  ->from('#__pf_projects')
                  ->order('id ASC');

            $db->setQuery($query, 0, 1);
            $min = (int) $db->loadResult();

            $query->clear();
            $query->select('id')
                  ->from('#__pf_projects')
                  ->order('id DESC');

            $db->setQuery($query, 0, 1);
            $max = (int) $db->loadResult();
        }

        if ($max == 0) return false;

        $id = rand($min, $max);

        // Check cache
        if (isset($cache[$id])) {
            if ($cache[$id] == false) {
                return self::getRandomProject();
            }

            return $cache[$id];
        }

        $db    = JFactory::getDbo();
        $query = $db->getQuery(true);

        $query->select('id, catid, title, alias, created, created_by, modified, modified_by, access, state, start_date, end_date')
              ->from('#__pf_projects')
              ->where('id = ' . $id);

        $db->setQuery($query);
        $cache[$id] = $db->loadObject();

        if (empty($cache[$id])) {
            $cache[$id] = false;

            return self::getRandomProject();
        }

        return $cache[$id];
    }


    /**
     * Returns a random milestone record object
     *
     * @param     integer    $project    The milestone project
     *
     * @return    object                 The milestone record
     */
    public static function getRandomMilestone($project)
    {
        static $cache = array();

        if (!isset($cache[$project])) {
            $db    = JFactory::getDbo();
            $query = $db->getQuery(true);

            $query->select('id')
                  ->from('#__pf_milestones')
                  ->where('project_id = ' . $project)
                  ->order('id ASC');

            $db->setQuery($query);
            $cache[$project] = $db->loadColumn();

            if (empty($cache[$project])) $cache[$project] = array();

            return self::getRandomMilestone($project);
        }

        $max = count($cache[$project]);

        if ($max == 0) return false;

        $id    = $cache[$project][rand(0, $max - 1)];
        $db    = JFactory::getDbo();
        $query = $db->getQuery(true);

        $query->select('id, title, alias, created, created_by, modified, modified_by, access, state, start_date, end_date')
              ->from('#__pf_milestones')
              ->where('id = ' . $id);

        $db->setQuery($query);
        $object = $db->loadObject();

        return $object;
    }


    /**
     * Returns a random task list record object
     *
     * @param     integer    $project      The task list project
     * @param     integer    $milestone    Optional task list milestone
     *
     * @return    object                   The task list record
     */
    public static function getRandomTasklist($project, $milestone = -1)
    {
        static $cache = array();

        $key = $project . '.' . $milestone;

        if (!isset($cache[$key])) {
            $db    = JFactory::getDbo();
            $query = $db->getQuery(true);

            $query->select('id')
                  ->from('#__pf_task_lists');

            if ($milestone > -1) {
                if ($milestone == 0) {
                    $query->where('project_id = ' . $project);
                }

                $query->where('milestone_id = ' . $milestone);
            }
            else {
                $query->where('project_id = ' . $project);
            }

            $query->order('id ASC');

            $db->setQuery($query);
            $cache[$key] = $db->loadColumn();

            if (empty($cache[$key])) $cache[$key] = array();

            return self::getRandomTasklist($project, $milestone);
        }

        $max = count($cache[$key]);

        if ($max == 0) return false;

        $id    = $cache[$key][rand(0, $max - 1)];
        $db    = JFactory::getDbo();
        $query = $db->getQuery(true);

        $query->select('id, title, alias, created, created_by, modified, modified_by, access, state')
              ->from('#__pf_task_lists')
              ->where('id = ' . $id);

        $db->setQuery($query);
        $object = $db->loadObject();

        return $object;
    }


    /**
     * Returns a random task record object
     *
     * @param     integer    $project      The task project
     * @param     integer    $milestone    Optional task milestone
     * @param     integer    $list         Optional task list
     *
     * @return    object                   The task record
     */
    public static function getRandomTask($project, $milestone = -1, $list = -1)
    {
        static $cache = array();

        $key = $project . '.' . $milestone . '.' . $list;

        if (!isset($cache[$key])) {
            $db    = JFactory::getDbo();
            $query = $db->getQuery(true);

            $query->select('id')
                  ->from('#__pf_tasks');

            if ($list <= 0 || $milestone <= 0) {
                $query->where('project_id = ' . $project);
            }

            if ($milestone > -1) {
                $query->where('milestone_id = ' . $milestone);
            }

            if ($list > -1) {
                $query->where('list_id = ' . $list);
            }

            $query->order('id ASC');

            $db->setQuery($query);
            $cache[$key] = $db->loadColumn();

            if (empty($cache[$key])) $cache[$key] = array();

            return self::getRandomTask($project, $milestone, $list);
        }

        $max = count($cache[$key]);

        if ($max == 0) return false;

        $id    = $cache[$key][rand(0, $max - 1)];
        $db    = JFactory::getDbo();
        $query = $db->getQuery(true);

        $query->select('id, title, alias, created, created_by, modified, modified_by, access, state, start_date, end_date, rate')
              ->from('#__pf_tasks')
              ->where('id = ' . $id);

        $db->setQuery($query);
        $object = $db->loadObject();

        return $object;
    }


    /**
     * Returns a list of label id's
     *
     * @param     integer    $project    The label project id
     * @param     string     $contet     The label item context
     * @param     integer    $max        Maximum of labels to return
     *
     * @return    array      $labels     The list of labels
     */
    public static function getRandomLabelIds($project, $context, $max = 5)
    {
        $db    = JFactory::getDbo();
        $query = $db->getQuery(true);

        $query->select('id')
              ->from('#__pf_labels')
              ->where('project_id = ' . $project)
              ->where('asset_group = ' . $db->quote($context));

        $db->setQuery($query);
        $result = $db->loadColumn();

        if (empty($result)) {
            return array();
        }

        $count = count($result);

        if ($max > $count) $max = $count;
        if ($max == 0)     return array();

        $max    = rand(0, $max - 1);
        $labels = array();
        $i      = 0;

        while ($i < $max)
        {
            $id = $result[rand(0, $max - 1)];

            if (!in_array($id, $labels)) {
                $labels[] = $id;
            }

            $i++;
        }

        return $labels;
    }
	
	/**
	* Returns a random task rate
	*
	* @param     integer    $value    Optional task rate
	*
	* @return    float    The task rate
	*/
	public static function getRandomTaskRate($value = 0)
	{
		$rate = rand(0, 100) . '.' . rand(0, 99);

		if ($value > 0){
			$rates = array($value, $rate);
			return $rates[rand(0,1)];
		}

		return $rate;		
	}	
	
	/**
	* Returns a random forum topic
	*
	* @param     integer    $project    The Project 
	*
	* @return		object		The Topic record
	*/
	
	public static function getRandomTopic ($project)
	{
		static $cache = array();

		if (!isset($cache[$project])) {
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true);

			$query->select('id')
				->from('#__pf_topics')
				->where('project_id = ' . $project)
				->order('id ASC');

			$db->setQuery($query);
			$cache[$project] = $db->loadColumn();

			if (empty($cache[$project])) $cache[$project] = array();

			return self::getRandomMilestone($project);
		}

		$max = count($cache[$project]);

		if ($max == 0) return false;

		$id    = $cache[$project][rand(0, $max - 1)];
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query->select('id, title, description, alias, created, created_by, modified, modified_by, access, state')
              ->from('#__pf_topics')
              ->where('id = ' . $id);

		$db->setQuery($query);
		$object = $db->loadObject();

		return $object;
	}
}
