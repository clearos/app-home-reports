<?php

/**
 * Chart view.
 *
 * @category   ClearOS
 * @package    Home_Reports
 * @subpackage Views
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
// Load dependencies
///////////////////////////////////////////////////////////////////////////////

$this->lang->load('reports');

///////////////////////////////////////////////////////////////////////////////
// Form
///////////////////////////////////////////////////////////////////////////////

$unique_key = $report['app'] . '_' . $report['report'];

echo summary_table(
    lang('reports_report_data'),
    array(),
    $report['headers'],
    NULL,
    array(
        'id' => $unique_key . '_table',
        'no_action' => TRUE,
        'table_size' => 'large',
        'paginate' => TRUE,
        'paginate_large' => TRUE,
        'filter' => TRUE,
        'default_rows' => 50,
        'sort-default-col' => 1,
        'sort-default-dir' => 'desc',
        'sorting-type' => $report['types'],
    )
);
