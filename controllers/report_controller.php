<?php

/**
 * Home reports engine controller.
 *
 * The meat and potatoes are in reports/controllers/report_core_controller.php.
 *
 * @category   Apps
 * @package    Home_Reports
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2012 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/home_reports/
 */

///////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once clearos_app_base('reports') . '/controllers/report_engine_controller.php';

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Home reports engine controller.
 *
 * @category   Apps
 * @package    Home_Reports
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2012 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/home_reports/
 */

class Report_Controller extends Report_Engine_Controller
{
    /**
     * Reports engine constructor.
     *
     * @param string $report report details
     *
     * @return view
     */

    function __construct($report)
    {
        parent::__construct($report);
    }

    /**
     * Default controller.
     *
     * @return view
     */

    function index($type = 'dashboard')
    {
        // parent::_index($type, 'home_reports');

        $this->page->view_report($type, $this->report_info, $report['title'], $options);

    }

    function _get_summary_range()
    {
        $this->load->library('home_reports/Report_Driver');

        return $this->report_driver->get_date_range();
    }

    function _get_summary_ranges()
    {
        $this->load->library('home_reports/Report_Driver');

        return $this->report_driver->get_date_ranges();
    }


    /**
     * Date range handler.
     */

    function _handle_range()
    {
        if ($this->input->post('report_range'))
            $this->session->set_userdata('report_sr', $this->input->post('report_range'));

        // FIXME: hard-coded today
/*
        if (!$this->session->userdata('report_sr'))
            $this->session->set_userdata('reports_sr', 'today');
*/
    }
}
