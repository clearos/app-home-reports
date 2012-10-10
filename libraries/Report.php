<?php

/**
 * Report base class.
 *
 * @category   Apps
 * @package    Reports
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2012 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/reports/
 */

///////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Lesser General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Lesser General Public License for more details.
//
// You should have received a copy of the GNU Lesser General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// N A M E S P A C E
///////////////////////////////////////////////////////////////////////////////

namespace clearos\apps\reports;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('reports');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\Configuration_File as Configuration_File;
use \clearos\apps\base\Engine as Engine;

clearos_load_library('base/Configuration_File');
clearos_load_library('base/Engine');

// Exceptions
//-----------

use \clearos\apps\base\File_Not_Found_Exception as File_Not_Found_Exception;

clearos_load_library('base/File_Not_Found_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Report base class.
 *
 * @category   Apps
 * @package    Reports
 * @subpackage Libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2012 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/reports/
 */

class Report extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const RANGE_TODAY = 'today';
    const RANGE_YESTERDAY = 'yesterday';
    const RANGE_LAST_7_DAYS = 'last7';
    const RANGE_LAST_30_DAYS = 'last30';

    const INTERVAL_HOURLY = 'hourly';
    const INTERVAL_DAILY = 'daily';
    const INTERVAL_WEEKLY = 'weekly';
    const INTERVAL_MONTHLY = 'monthly';

    const DEFAULT_RANGE = 'today';

    const FILE_CONFIG = '/etc/clearos/reports.conf';

    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    protected $config = NULL;
    protected $month_names = array();
    protected $ranges = array();

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Report constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->ranges = array(
            self::RANGE_TODAY => lang('reports_today'),
            self::RANGE_YESTERDAY => lang('reports_yesterday'),
            self::RANGE_LAST_7_DAYS => lang('reports_last_7_days'),
            self::RANGE_LAST_30_DAYS => lang('reports_last_30_days')
        );

        $this->month_names = array(
            '1' => lang('base_month_january'),
            '2' => lang('base_month_february'),
            '3' => lang('base_month_march'),
            '4' => lang('base_month_april'),
            '5' => lang('base_month_may'),
            '6' => lang('base_month_june'),
            '7' => lang('base_month_july'),
            '8' => lang('base_month_august'),
            '9' => lang('base_month_september'),
            '10' => lang('base_month_october'),
            '11' => lang('base_month_november'),
            '12' => lang('base_month_december')
        );
    }

    /**
     * Returns date range.
     *
     * @return string summary range
     */

    public function get_date_range()
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->_load_config();

        return $this->config['range'];
    }

    /**
     * Returns built-in date ranges.
     *
     * @return array summary ranges
     */

    public function get_date_ranges()
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->ranges;
    }

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N   R O U T I N E S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Validation routine for date ranges.
     *
     * @param string $range date range
     *
     * @return string error message if date range is invalid
     */

    public function validate_date_range($range)
    {
        clearos_profile(__METHOD__, __LINE__);

        // return lang('reports_range_invalid');
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E   M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Loads configuration.
     *
     * @return void.
     */

    protected function _load_config()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!is_null($this->config))
            return;

        try {
            $file = new Configuration_File(self::FILE_CONFIG, 'explode', '=', 2);
            $this->config = $file->load();
        } catch (File_Not_Found_Exception $e) {
            // Not fatal
        }

        if (empty($this->config['summary_range']))
            $this->config['summary_range'] = self::DEFAULT_RANGE;
    }
}
