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
     * @return view
     */

    function __construct($app, $library, $report, $reports = array())
    {
        if (empty($reports))
            $this->is_overview = FALSE;
        else
            $this->is_overview = TRUE;

        $this->reports = $reports;

        parent::__construct($app, $library, $report);
    }

    /**
     * Default controller.
     *
     * @return view
     */

    function index($type = 'dashboard')
    {
        // Load dependencies
        //------------------

        $this->load->library('home_reports/Report_Driver');

        // Set validation rules
        //---------------------

        // FIXME: validate
        $form_ok = TRUE;

        // Handle form submit
        //-------------------

        if ($this->input->post('report_range') && $form_ok) {
            try {
                $this->session->set_userdata('report_range', $this->input->post('report_range'));
            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        if (! $this->session->userdata('report_range')) {
            $this->session->set_userdata('report_range', 'today'); // FIXME: hard-coded
        }

        // Load view data
        //---------------

        try {
            $data['report'] = $this->report_info;

            $data['range'] = $this->session->userdata('report_range');
            $data['ranges'] = $this->report_driver->get_date_ranges();

            $title = $data['report']['title'];
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load views
        //-----------

        if ($this->is_overview)
            $this->page->view_reports($this->reports, $data, $title);
        else
            $this->page->view_report($type, $data, $title, $options);
    }

    /**
     * Returns raw data from a report.
     *
     * @return json array
     */

    function get_data()
    {
        clearos_profile(__METHOD__, __LINE__);

        // Load dependencies
        //------------------

        $this->load->library($this->report_info['app'] . '/' . $this->report_info['library']);

        // Load data
        //----------

        try {
            $library = strtolower($this->report_info['library']);
            $method = $this->report_info['method'];

            $data = $this->$library->$method(
                $this->session->userdata('report_range'),
                10
            );
        } catch (Exception $e) {
            echo json_encode(array('code' => clearos_exception_code($e), 'errmsg' => clearos_exception_message($e)));
        }

        // Show data
        //----------

        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Fri, 01 Jan 2010 05:00:00 GMT');
        header('Content-type: application/json');
        echo json_encode($data);
    }
}
