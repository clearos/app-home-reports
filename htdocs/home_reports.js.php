<?php

/**
 * Home report ajax helpers.
 *
 * @category   apps
 * @package    home-reports
 * @subpackage javascript
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2012-2013 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/home_reports/
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
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('base');

///////////////////////////////////////////////////////////////////////////////
// J A V A S C R I P T
///////////////////////////////////////////////////////////////////////////////

header('Content-Type:application/x-javascript');
?>

///////////////////////////////////////////////////////////////////////////
// M A I N
///////////////////////////////////////////////////////////////////////////

// Report data is a global variable - no need to refetch data on plot redrawing
var report_data = new Array();

$(document).ready(function() {

    // Translations
    //-------------

    lang_loading = '<?php echo lang("base_loading"); ?>';

    // Date range form action
    //-----------------------

    $("#report_range").change(function(){
        $('form#report_form').submit();
    });

    // Scan for reports on the page
    //-----------------------------

    var report_list = $("input[id^='clearos_report']");

    $.each(report_list, function(index, value) {
        var report_id = $(value).val();

        id_prefix = report_id.replace(/(:|\.)/g,'\\$1');

        var app = $("#" + id_prefix + "_app_name").val();
        var report_basename = $("#" + id_prefix + "_basename").val();
        var report_key = $("#" + id_prefix + "_key_value").val();

        $("#" + id_prefix + "_chart").html('<br><p align="center"><span class="theme-loading-normal">' + lang_loading + '</span></p><br>'); // TODO - merge HTML

        generate_report(app, report_basename, report_key, report_id);
    });
});

/**
 * Ajax call for standard report.
 */

function generate_report(app, report_basename, report_key, report_id) {

    $.ajax({
        url: '/app/' + app + '/' + report_basename + '/get_data/' + report_key,
        method: 'GET',
        dataType: 'json',
        success : function(payload) {
        
            // Throw report data into our global variable
            report_data[report_id] = new Array();
            report_data[report_id].header = payload.header;
            report_data[report_id].data_type = payload.type;
            report_data[report_id].data = (payload.data) ? payload.data : new Array();
            report_data[report_id].detail = (payload.detail) ? payload.detail : new Array();
            report_data[report_id].format = (payload.format) ? payload.format : new Array();
            report_data[report_id].units = (payload.units) ? payload.units : new Array();
            report_data[report_id].chart_series = (payload.chart_series) ? payload.chart_series : new Array();
            report_data[report_id].series_sort = (payload.series_sort) ? payload.series_sort : 'desc';

            // If first series is a timestamp, highlight it.  Otherwise, highlight the second series.
            if (payload.series_highlight)
                 report_data[report_id].series_highlight = payload.series_highlight;
            else if (payload.type[0] == 'timestamp')
                 report_data[report_id].series_highlight = 0;
            else
                 report_data[report_id].series_highlight = 1;

            // Draw the chart and load the data table
            create_chart(report_id);
            create_table(report_id);
        },
        error: function (XMLHttpRequest, textStatus, errorThrown) {
            // TODO window.setTimeout(generate_report, 3000);
        }
    });
}

/**
 * Creates chart.
 */

function create_chart(report_id) {

    // Map report data to local variables... just easier to read
    //----------------------------------------------------------

    var data = report_data[report_id].data;
    var data_titles = report_data[report_id].header;
    var data_types = report_data[report_id].data_type;
    var options = report_data[report_id].format;
    var data_units = report_data[report_id].units;

    var series_highlight = report_data[report_id].series_highlight;
    var chart_series = report_data[report_id].chart_series;

    // Chart GUI details
    //------------------

    var id_prefix = report_id.replace(/(:|\.)/g,'\\$1');

    var chart_id = id_prefix + '_chart';
    var chart_type = $("#" + id_prefix + "_chart_type").val();
    var chart_loading = $("#" + id_prefix + "_chart_loading_id").val();

    // Calculated mins/maxes to set the scale of the axes
    //---------------------------------------------------

    var baseline_data_points = (options.baseline_data_points) ? options.baseline_data_points : 200;

    // Put the data into key/value pairs - required by jqplot
    // - Convert IP addresses
    // - Select the x and y axes
    //-------------------------------------------------------

    var data_points = (data.length > baseline_data_points) ? baseline_data_points : data.length;
    var chart_data = data;

    if (data_points == 0) {
        $("#" + id_prefix + "_chart").html('<br><p align="center">Nothing to report...</p><br>'); // FIXME
        return;
    } else if (data_points > 0) {
        chart_data = [];
        for (inx = 0; inx < data_points; inx++) {
            chart_data.push(data[inx]);
        }
    }

    $("#" + id_prefix + "_chart").html('');

    // Call chart function
    //--------------------

    clearos_chart(
        chart_id,
        chart_type,
        chart_data,
        data_titles,
        data_types,
        data_units,
        options
    );

    // Hide the whirly and draw the chart
    //-----------------------------------

    $("#" + id_prefix + "_chart_loading_id").hide();
}

/**
 * Creates data table.
 */

function create_table(report_id) {

    var table_id = report_id.replace(/(:|\.)/g,'\\$1') + '_table';

    clearos_summary_table(
        table_id,
        report_data[report_id].data,
        report_data[report_id].data_type,
        report_data[report_id].detail,
        report_data[report_id].series_highlight,
        report_data[report_id].series_sort,
        report_id
    );
}

// Data table sort event handler
//------------------------------

function clearos_report_trigger(type, tableref, report_id) {
    // Datatables internal store with sorting info
    var sort_details = tableref.fnSettings().aaSorting;
    var column = sort_details[0][0];
    var direction = sort_details[0][1];

    if (column > 0) {
        report_data[report_id].series_highlight = column;
        report_data[report_id].series_sort = direction;

        create_chart(report_id);
    }
}

// vim: ts=4 syntax=javascript
