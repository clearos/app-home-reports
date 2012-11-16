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
    function dashboard($key = NULL)
    {
        $this->_index($key, 'dashboard');
    }

    function index($key = NULL)
    {
        $this->_index($key, 'full');
    }

    /**
     * Returns raw data from a report.
     *
     * @return json array
     */

    function get_data($key = NULL)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Initialize report data
        //-----------------------

        $report_info = $this->_get_report_info($key);

        // Load dependencies
        //------------------

        $this->load->library($this->report_info['app'] . '/' . $this->report_info['library']);

        // Load data
        //----------

        try {
            $library = strtolower($this->report_info['library']);
            $method = $report_info['api_data'];

            // As noted above, some reports are keyed, while others are not.
            if (empty($key)) {
                $data = $this->$library->$method(
                    $this->session->userdata('report_range')
                );
            } else {
                $data = $this->$library->$method(
                    $key,
                    $this->session->userdata('report_range')
                );
            }
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

    /**
     * Default controller.
     *
     * @return view
     */

    function _index($key, $type)
    {
        // Initialize report data
        //-----------------------

        $report_info = $this->_get_report_info($key);

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

        if (! $this->session->userdata('report_range'))
            $this->session->set_userdata('report_range', 'today'); // FIXME: hard-coded

        // Load view data
        //---------------

        try {
            $data['report'] = $report_info;
            $data['report']['key_value'] = $key;
            $data['report']['form'] = $this->uri->uri_string();

            $data['range'] = $this->session->userdata('report_range');
            $data['ranges'] = $this->report_driver->get_date_ranges();

            $title = $data['report']['title'];
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load views
        //-----------

        if ($report_info['report'] === 'overview') // See FIXME about constant (above)
            $this->page->view_reports($report_info['dashboards'], $data, $title);
        else
            $this->page->view_report($type, $data, $title, $options);
    }

    /**
     * Returns the report info from underlying class/API.
     *
     * @param string $report report name
     */

    function _get_report_info($key)
    {
        $this->load->library($this->report_info['app'] . '/' . $this->report_info['library']);

        $ci_library = strtolower($this->report_info['library']);

        // Non-intuitive.
        //
        // Some reports are keyed on a value.  For example,
        // the "Network Report" passes the value of the network interface 
        // (eth0, eth1, etc).  Other reports do not require key values, for
        // example, the "System Load" is just that... the system load.
        //
        // In addition, we can automatically detect the special "overview"
        // report -- the "app" basename will match the controller class.

        try {
            if ($this->report_info['app'] === strtolower(get_class($this)))
                $report = 'overview';  // FIXME: make this a constant?
            else if ($this->$ci_library->report_exists($key))
                $report = $key;
            else
                $report = strtolower(get_class($this));

            $report_info = $this->$ci_library->get_report_info($report);
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        return $report_info;
    }
}
