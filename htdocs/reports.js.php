<?php

/**
 * Report ajax helpers.
 *
 * All reports generate IDs to allow this javascript to take action.  Here's
 * an example of the Domains report in the Proxy Report app:
 *
 * <input type='hidden' id='clearos_report_proxy_report_domains_basename' value='proxy_report_domains'>
 * <input type='hidden' id='proxy_report_domains_app_name' value='proxy_report'>
 * <input type='hidden' id='proxy_report_domains_report_name' value='domains'>
 *
 * @category   ClearOS
 * @package    Reports
 * @subpackage Javascript
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2012 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/reports/
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

clearos_load_language('reports');

///////////////////////////////////////////////////////////////////////////////
// J A V A S C R I P T
///////////////////////////////////////////////////////////////////////////////

header('Content-Type:application/x-javascript');
?>

///////////////////////////////////////////////////////////////////////////
// M A I N
///////////////////////////////////////////////////////////////////////////

$(document).ready(function() {

    // Translations
    //-------------

    lang_received = '<?php echo lang("proxy_report_received"); ?>';

    // Date range form action
    //-----------------------

    $("#report_range").click(function(){
        $('form#report_form').submit();
    });

    // Scan for reports on the page
    //-----------------------------

    var report_list = $("input[id^='clearos_report']");

    $.each(report_list, function(index, value) {
        var id_prefix = $(value).val();

        var app = $("#" + id_prefix + "_app_name").val();
        var report_name = $("#" + id_prefix + "_report_name").val();
        var report_id = id_prefix;

        generate_report(app, report_name, report_id);
    });
});

/**
 * Ajax call for standard report.
 */

function generate_report(app, report_name, report_id) {

    $.ajax({
        url: '/app/' + app + '/' + report_name + '/get_data',
        method: 'GET',
        dataType: 'json',
        success : function(payload) {
            var header = payload.header;
            var type = payload.type;
            var data = payload.data;

            create_chart(header, type, data, report_id);
            create_table(header, type, data, report_id);
        },
        error: function (XMLHttpRequest, textStatus, errorThrown) {
            // FIXME window.setTimeout(generate_report, 3000);
        }
    });
}

/**
 * Creates chart.
 */

function create_chart(header, type, data, report_id) {
    var chart_id = report_id + '_chart';
    var chart_data = Array();

    // Put the data into key/value pairs - required by jqplot
    // - Convert IP addresses
    // - Select the x and y axes
    //-------------------------------------------------------

    for (i = 0; i < data.length; i++) {

        if (type[0] == 'ip')
            x_item = long2ip(data[i][0]);
        else
            x_item = data[i][0];

        chart_data.unshift([data[i][1], x_item]);
    } 

    // Pump the data into jqplot call
    //-------------------------------

    var chart = jQuery.jqplot (chart_id, [chart_data],
    {
        animate: !$.jqplot.use_excanvas,
        seriesDefaults: {
            renderer: jQuery.jqplot.BarRenderer,
            rendererOptions: {
                barDirection: 'horizontal'
            },
            pointLabels: { show: true, location: 'e', edgeTolerance: -15 },
        },
        axes: {
            yaxis: {
                renderer: $.jqplot.CategoryAxisRenderer,
            }
        }
    });

    // Draw the chart
    //---------------

    chart.redraw();
}

/**
 * Creates data table.
 */

function create_table(header, type, data, report_id) {
    var table = $('#' + report_id + '_table').dataTable();

    table.fnClearTable();

    for (i = 0; i < data.length; i++) {
        var row = Array();

        for (j = 0; j < data[i].length; j++) {
            if (type[j] == 'ip')
                item = '<span style="display: none">' + data[i][j] + '</span>' + long2ip(data[i][j]);
            else
                item = data[i][j];

            row.push(item);
        }

        table.fnAddData(row);
    }
}

/**
 * Returns IP address in human-readable format.
 */

// TODO: Not IPv6 friendly
function long2ip(ip_long) {
    var ip = ip_long%256;

    for (var i = 3; i > 0; i--) { 
        ip_long = Math.floor(ip_long/256);
        ip = ip_long%256 + '.' + ip;
    }

    return ip;
}

// vim: ts=4 syntax=javascript
