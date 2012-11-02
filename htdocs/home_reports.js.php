<?php

/**
 * Report ajax helpers.
 *
 * @category   ClearOS
 * @package    Home_Reports
 * @subpackage Javascript
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2012 ClearFoundation
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
        var id_prefix = $(value).val();

        var app = $("#" + id_prefix + "_app_name").val();
        var report_basename = $("#" + id_prefix + "_basename").val();
        var report_key = $("#" + id_prefix + "_key_value").val();
        var report_id = id_prefix;

        $("#" + report_id + "_chart").html('<br><p align="center"><span class="theme-loading-normal">' + lang_loading + '</span></p><br>'); // TODO - merge HTML

        generate_report(app, report_basename, report_key, report_id);
    });
});

/**
 * Ajax call for standard report.
 */

function generate_report(app, report_basename, report_key, report_id) {

console.log(app + '/' + report_basename + '/' + report_key);
    $.ajax({
        url: '/app/' + app + '/' + report_basename + '/get_data/' + report_key,
        method: 'GET',
        dataType: 'json',
        success : function(payload) {
            var header = payload.header;
            var data_type = payload.type;
            var data = new Array();
            var format = new Array();

            if (payload.format)
                format = payload.format;

            if (payload.data)
                data = payload.data;

            create_chart(report_id, header, data_type, data, format);
            create_table(report_id, header, data_type, data, format);
        },
        error: function (XMLHttpRequest, textStatus, errorThrown) {
            // TODO window.setTimeout(generate_report, 3000);
        }
    });
}

/**
 * Creates chart.
 */

function create_chart(report_id, header, data_type, data, format) {

    // Chart GUI details
    //------------------

    var chart_id = report_id + '_chart';
    var chart_type = $("#" + report_id + "_chart_type").val();
    var chart_loading = $("#" + report_id + "_chart_loading_id").val();

    // Series raw data
    //----------------

    var series = new Array();

    for (i = 0; i < header.length - 1; i++)
        series[i] = new Array();

    // Calculated mins/maxes
    //----------------------

    var baseline_data_points = (format.baseline_data_points) ? format.baseline_data_points : 200;
    var baseline_calculated_min = 0;
    var baseline_calculated_max = 0;
    var series_calculated_min = 0;
    var series_calculated_max = 0;

    // Put the data into key/value pairs - required by jqplot
    // - Convert IP addresses
    // - Select the x and y axes
    //-------------------------------------------------------

    var rows = (data.length > baseline_data_points) ? baseline_data_points : data.length;

    if (rows == 0) {
        $("#" + report_id + "_chart").html('<br><p align="center">Nothing to report...</p><br>'); // FIXME
        return;
    }

    $("#" + report_id + "_chart").html('');

    for (i = 0; i < rows; i++) {
        // Pie charts can only show one series
        series_number = (chart_type == 'pie') ? 2 : data[i].length;
        x_item = convert_to_human(data[i][0], data_type[0]);

        for (j = 1; j < series_number; j++) {
            if (chart_type == 'bar')
                series[j-1].push([x_item, data[i][j]]);
            else
                series[j-1].unshift([x_item, data[i][j]]);

            if (data[i][j] < series_calculated_min)
                series_calculated_min = data[i][j];

            if (data[i][j] > series_calculated_max)
                series_calculated_max = data[i][j];

            if ((i == 0) && (j == 1))
                baseline_calculated_max = x_item;
        }
    } 

    baseline_calculated_min = data[rows-1][0];

    // Labels, axes and formats
    //-------------------------

    // Round max values
    var tens_string = String(Math.round(series_calculated_max));
    var tens = tens_string.length - 1;
    series_calculated_max = Math.ceil(series_calculated_max / Math.pow(10,tens)) * Math.pow(10,tens);

    var baseline_min = (format.baseline_min) ? format.baseline_min : baseline_calculated_min;
    var baseline_max = (format.baseline_max) ? format.baseline_max : baseline_calculated_max;
    var baseline_label = (format.baseline_label) ? format.baseline_label : ''; // FIXME: translate
    var baseline_format = '%b %e %H:%M'; // FIXME

    var series_min = (format.series_min) ? format.series_min : series_calculated_min;
    var series_max = (format.series_max) ? format.series_max : series_calculated_max;
    var series_label = (format.series_label) ? format.series_label : '';
    var series_format = (format.series_units) ? '%s ' + format.series_units : '%s';

    var legend_labels = header;
    legend_labels.shift();

// console.log(baseline_calculated_min + ' - ' + baseline_calculated_max);
// console.log(series_calculated_min + ' - ' + series_calculated_max);

    // Pie chart
    //----------

    if (chart_type == 'pie') {

        var chart = jQuery.jqplot (chart_id, series,
        {
            grid: {
                gridLineColor: 'transparent',
                background: 'transparent',
                borderColor: 'transparent',
                shadow: false
            },
            legend: {
                show: true,
                location: 'e',
            },
            seriesDefaults: {
                renderer: jQuery.jqplot.PieRenderer,
                shadow: true,
                rendererOptions: {
                    showDataLabels: true,
                    sliceMargin: 8,
                    dataLabels: 'value'
                }
            }
        });

    // Line chart
    //----------

    } else if ((chart_type == 'line') || (chart_type == 'line_stack')) {
        if (chart_type == 'line') {
            stack_series = false;
            fill = false;
        } else {
            stack_series = true;
            fill = true;
        }

        var chart = jQuery.jqplot (chart_id, series,
        {
            stackSeries: stack_series,
            legend: {
                show: true,
                location: 'ne',
                labels: legend_labels
            },
            seriesDefaults: { 
                fill: fill,
                shadow: true,
                showMarker: false,
                pointLabels: { show: false }
            },
            axesDefaults: {
                tickRenderer: $.jqplot.CanvasAxisTickRenderer,
                labelRenderer: $.jqplot.CanvasAxisLabelRenderer,
                labelOptions: {
                    fontSize: '10pt'
                },
                tickOptions: {
                    fontSize: '8pt'
                }
            },
            axes:{
                xaxis: {
                    label: baseline_label,
                    min: baseline_min,
                    max: baseline_max,
                    renderer: $.jqplot.DateAxisRenderer,
                    tickOptions:{
                        formatString: baseline_format
                    }
                },
                yaxis: {
                    label: series_label,
                    min: series_min,
                    max: series_max,
                    labelOptions: {
                        angle: -90
                    },
                    tickOptions:{
                        formatString: series_format
                    }
                }
            },
        });

    // Bar chart
    //----------

    } else {
        var chart = jQuery.jqplot (chart_id, series,
        {
            animate: !$.jqplot.use_excanvas,
            legend: {
                show: true,
                location: 'ne',
                labels: legend_labels
            },
            seriesDefaults: {
                renderer: jQuery.jqplot.BarRenderer,
                rendererOptions: {
                    barDirection: 'vertical'
                },
                pointLabels: { show: true, location: 'e', edgeTolerance: -15 },
            },
            axesDefaults: {
                tickRenderer: $.jqplot.CanvasAxisTickRenderer,
                labelRenderer: $.jqplot.CanvasAxisLabelRenderer,
                labelOptions: {
                    fontSize: '10pt'
                },
                tickOptions: {
                    fontSize: '8pt'
                }
            },
            axes: {
                xaxis: {
                    label: baseline_label,
                    min: baseline_min,
                    max: baseline_max,
                    renderer: $.jqplot.CategoryAxisRenderer,
                    tickOptions: {
                        formatString: baseline_format,
                        angle: -30
                    },
                },
                yaxis: {
                    min: series_min,
                    max: series_max,
                    labelOptions: {
                        angle: -90
                    },
                    tickOptions:{
                        formatString: series_format
                    }
                }
            }
        });
    }

    // Hide the whirly and draw the chart
    //-----------------------------------

    $("#" + report_id + "_chart_loading_id").hide();

    chart.redraw();
}

/**
 * Creates data table.
 */

function create_table(report_id, header, data_type, data, format) {
    var table = $('#' + report_id + '_table').dataTable();

    table.fnClearTable();

    for (i = 0; i < data.length; i++) {
        var row = new Array();

        for (j = 0; j < data[i].length; j++) {
            if (data_type[j] == 'ip')
                item = '<span style="display: none">' + data[i][j] + '</span>' + long2ip(data[i][j]);
            else
                item = data[i][j];

            row.push(item);
        }

        table.fnAddData(row);
    }

    table.fnAdjustColumnSizing();
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

/**
 * Converts a value to a human-readable format, e.g. integer IPs into quad-format
 */

function convert_to_human(value, type) {
    if (type == 'ip')
        return long2ip(value);
    else
        return value;
}

// vim: ts=4 syntax=javascript
