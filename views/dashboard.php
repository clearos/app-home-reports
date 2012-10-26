<?php

/**
 * Chart view.
 *
 * @category   ClearOS
 * @package    Reports
 * @subpackage Views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2012 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/reports/
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
// Load dependencies
///////////////////////////////////////////////////////////////////////////////

$this->lang->load('reports');

///////////////////////////////////////////////////////////////////////////////
// Form
///////////////////////////////////////////////////////////////////////////////

$unique_key = $report['app'] . '_' . $report['report'];

$options['action'] = button_set(
    array(anchor_custom('/app/' . $report['app'] . '/' . $report['report'] . '/index/full', lang('reports_full_report')))
);

echo chart_widget($report['title'], "<div id='${unique_key}_chart'></div>", $options);
echo "
    <input type='hidden' id='clearos_report_${unique_key}_basename' value='$unique_key'>
    <input type='hidden' id='${unique_key}_app_name' value='" . $report['app'] . "'>
    <input type='hidden' id='${unique_key}_report_name' value='" . $report['report'] . "'>
    <input type='hidden' id='${unique_key}_chart_type' value='" . $report['chart_type'] . "'>
";