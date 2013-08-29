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

    // Map report data to easier to read local variables
    //--------------------------------------------------

    var header = report_data[report_id].header;
    var data_type = report_data[report_id].data_type;
    var data = report_data[report_id].data;
    var format = report_data[report_id].format;
    var detail = report_data[report_id].detail;
    var series_highlight = report_data[report_id].series_highlight;
    var series_sort = report_data[report_id].series_sort;
    var chart_series = report_data[report_id].chart_series;

    // Chart GUI details
    //------------------

    var id_prefix = report_id.replace(/(:|\.)/g,'\\$1');

    var chart_id = id_prefix + '_chart';
    var chart_type = $("#" + id_prefix + "_chart_type").val();
    var chart_loading = $("#" + id_prefix + "_chart_loading_id").val();

    // Series raw data
    //----------------

    var series = new Array();

    // Calculated mins/maxes to set the scale of the axes
    //---------------------------------------------------

    var baseline_data_points = (format.baseline_data_points) ? format.baseline_data_points : 200;
    var baseline_calculated_min = 0;
    var baseline_calculated_max = 0;
    var series_calculated_min = 0;
    var series_calculated_max = 0;

    // Put the data into key/value pairs - required by jqplot
    // - Convert IP addresses
    // - Select the x and y axes
    //-------------------------------------------------------

    var data_points = (data.length > baseline_data_points) ? baseline_data_points : data.length;

    if (data_points == 0) {
        $("#" + id_prefix + "_chart").html('<br><p align="center">Nothing to report...</p><br>'); // FIXME
        return;
    }

    $("#" + id_prefix + "_chart").html('');

    for (i = 0; i < data.length; i++) {
        series_number = data[i].length;
        x_item = convert_to_human(data[i][0], data_type[0]);

        for (j = 1; j < series_number; j++) {
            if (typeof series[j-1] == 'undefined')
                series[j-1] = new Array();

            // Jqlot: series format is reversed depending on chart type!
            if (chart_type == 'horizontal_bar')
                series[j-1].push([data[i][j], x_item]); 
            else
                series[j-1].push([x_item, data[i][j]]);

            if (data[i][j] < series_calculated_min)
                series_calculated_min = data[i][j];

            if (data[i][j] > series_calculated_max)
                series_calculated_max = data[i][j];

            if ((i == 0) && (j == 1))
                baseline_calculated_max = x_item;
        }
    }

    baseline_calculated_min = data[data_points-1][0];

    // Sort by value, javascript style
    // Charts don't always need the full series in the chart, just the top X data_points
    for (j = 1; j < series_number; j++) {
        if (typeof series[j-1] == 'undefined') 
            continue;

        // Jqlot: again, series format is reversed depending on chart type!
        if (chart_type == 'horizontal_bar') {
            series[j-1].sort(function(a, b) {return b[0] - a[0]});
            series[j-1] = series[j-1].slice(0, data_points);

            // jqplot horizontal_bar seems to like listing in reverse?  Reverse order just for this chart
            series[j-1].sort(function(a, b) {return a[0] - b[0]});
        } else {
            series[j-1].sort(function(a, b) {return b[1] - a[1]});
            series[j-1] = series[j-1].slice(0, data_points);
        }
    }

    // Labels, axes and formats
    //-------------------------

    // Round max values
    series_calculated_max = series_calculated_max * 1.1; // 10% buffer

    var tens_string = String(Math.round(series_calculated_max));
    var tens = tens_string.length - 1;
    series_calculated_max = Math.ceil(series_calculated_max / Math.pow(10,tens)) * Math.pow(10,tens);

    var baseline_min = (format.baseline_min) ? format.baseline_min : baseline_calculated_min;
    var baseline_max = (format.baseline_max) ? format.baseline_max : baseline_calculated_max;
    var baseline_label = (format.baseline_label) ? format.baseline_label : '';
    var baseline_format = (format.baseline_format) ? format.baseline_format : '';

    var series_min = (format.series_min) ? format.series_min : series_calculated_min;
    var series_max = (format.series_max) ? format.series_max : series_calculated_max;
    var series_label = (format.series_label) ? format.series_label : '';
    var series_format = (format.series_format) ? format.series_format : '%s';

    var legend_labels = Array();

    // If "timestamp" is specified as the format, use the following
    // TODO: would be nice to have a different format for days only
    if (baseline_format == 'timestamp')
        baseline_format = '%b %e %H:%M';

    // Legend - show all headers unless specific series is specified
    //--------------------------------------------------------------
    // Note #1 - pie charts auto generate the legend
    // Note #2 - legends are slightly different across charts

    if (typeof chart_series[0] == 'undefined') {
        if (chart_type == 'horizontal_bar') {
            legend_labels.push(header[series_highlight]);
        } else if (chart_type == 'bar') {
            var series_highlight_workaround = (series_highlight == 0) ? 1 : series_highlight;
            legend_labels.push(header[series_highlight_workaround]);
        } else if ((chart_type == 'line') || (chart_type == 'timeline') || (chart_type == 'line_stack') || (chart_type == 'timeline_stack')) {
            var series_highlight_workaround = (series_highlight == 0) ? 1 : series_highlight;
            legend_labels.push(header[series_highlight_workaround]);
        } else {
            legend_labels.push(header[series_highlight+1]);
        }
    } else {
        for (i = 1; i < chart_series.length; i++) {
            if (chart_series[i])
               legend_labels.push(header[i]);
        }
    }

    // Pie chart
    //----------

    if (chart_type == 'pie') {
        var seriesRenderer = function() {
            var pie_series = Array();
            pie_series[0] = series[series_highlight - 1];
            return pie_series;
        }

        var chart = jQuery.jqplot (chart_id, [],
        {
            dataRenderer: seriesRenderer,
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
            },
            highlighter: {
                lineWidthAdjust: 9.5,
                sizeAdjust: 5,
                showTooltip: true,
                fadeTooltip: true,
                formatString: '%s', 
                useAxesFormatters: false,
                tooltipFadeSpeed: 'slow',
                tooltipLocation: 's',
                tooltipSeparator: ' - '
            }
        });

    // Line chart
    //-----------

    } else if ((chart_type == 'line') || (chart_type == 'timeline') || (chart_type == 'line_stack') || (chart_type == 'timeline_stack')) {
        if ((chart_type == 'line') || (chart_type == 'timeline')) {
            stack_series = false;
            fill = false;
        } else {
            stack_series = true;
            fill = true;
        }

        
        // var seriesRenderer = function() {
        //    var line_series = Array();
        //    var series_number = (series_highlight == 0) ? 0 : series_highlight - 1;
        //    line_series[0] = series[series_number];
        //    return line_series;
        //}

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
                    fontSize: '8pt'
                },
                tickOptions: {
                    fontSize: '8pt'
                    /* FIXME formatString: "%d" */
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
                    /* FIXME max: series_max, */
                    labelOptions: {
                        angle: -90
                    },
                    tickOptions:{
                        formatString: series_format
                    }
                }
            },
            highlighter: {
                lineWidthAdjust: 9.5,
                sizeAdjust: 5,
                showTooltip: true,
                fadeTooltip: true,
                tooltipFadeSpeed: 'slow',
                tooltipLocation: 'n',
                tooltipSeparator: ' - '
            },
        });

    // Horizontal Bar Chart
    //---------------------

    } else if (chart_type == 'horizontal_bar') {
        var seriesRenderer = function() {
            var bar_series = Array();
            bar_series[0] = series[series_highlight - 1];
            return bar_series;
        }

        var chart = jQuery.jqplot (chart_id, [],
        {
            dataRenderer: seriesRenderer,
            animate: !$.jqplot.use_excanvas,
            legend: {
                show: true,
                location: 'e',
                labels: legend_labels
            },
            seriesDefaults: {
                renderer: jQuery.jqplot.BarRenderer,
                rendererOptions: {
                    barDirection: 'horizontal'
                },
                pointLabels: { show: true }
            },
            axesDefaults: {
                tickRenderer: $.jqplot.CanvasAxisTickRenderer,
                labelRenderer: $.jqplot.CanvasAxisLabelRenderer,
                labelOptions: {
                    fontSize: '8pt'
                },
            },
            axes: {
                xaxis: {
                    label: series_label,
                    min: series_min,
                    /* max: series_max, FIXME */
                    tickOptions:{
                        formatString: series_format,
                        fontSize: '7pt',
                        angle: -30
                    }
                },
                yaxis: {
                    renderer: $.jqplot.CategoryAxisRenderer,
                    label: baseline_label,
                    tickOptions: {
                        formatString: series_format,
                        fontSize: '8pt'
                    }
                }
            },
            highlighter: {
                show: false
            }
        });

    // Vertical Bar Chart
    //---------------------

    } else {
        var seriesRenderer = function() {
            var bar_series = Array();
            var series_number = (series_highlight == 0) ? 0 : series_highlight - 1;
            bar_series[0] = series[series_number];
            return bar_series;
        }

        var chart = jQuery.jqplot (chart_id, [],
        {
            dataRenderer: seriesRenderer,
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
                }
            },
            axesDefaults: {
                tickRenderer: $.jqplot.CanvasAxisTickRenderer,
                labelRenderer: $.jqplot.CanvasAxisLabelRenderer,
                labelOptions: {
                    fontSize: '8pt'
                },
            },
            axes: {
                xaxis: {
                    label: baseline_label,
                    min: baseline_min,
                    max: baseline_max,
                    renderer: $.jqplot.CategoryAxisRenderer,
                    tickOptions: {
                        formatString: baseline_format,
                        fontSize: '7pt',
                        angle: -30
                    },
                },
                yaxis: {
                    label: series_label,
                    min: series_min,
                    /* FIXME max: series_max, */
                    labelOptions: {
                        angle: -90
                    },
                    tickOptions:{
                        formatString: series_format,
                        fontSize: '8pt',
                    }
                }
            },
            highlighter: {
                show: false
            }
        });
    }

    // Hide the whirly and draw the chart
    //-----------------------------------

    $("#" + id_prefix + "_chart_loading_id").hide();

    chart.redraw();
}

/**
 * Creates data table.
 */

function create_table(report_id) {

    // Map report data to easier to read local variables
    //--------------------------------------------------

    var header = report_data[report_id].header;
    var data_type = report_data[report_id].data_type;
    var data = report_data[report_id].data;
    var format = report_data[report_id].format;
    var detail = report_data[report_id].detail;
    var series_highlight = report_data[report_id].series_highlight;
    var series_sort = report_data[report_id].series_sort;

    // Generate table
    //---------------

    var id_prefix = report_id.replace(/(:|\.)/g,'\\$1');

    var table = $('#' + id_prefix + '_table').dataTable();

    // Bail if no data table exists (e.g. dashboard only shows a chart)
    if ($('#' + id_prefix + '_table').val() == undefined)
        return;

    table.fnClearTable();

    for (i = 0; i < data.length; i++) {
        var row = new Array();

        for (j = 0; j < data[i].length; j++) {
            // IP addresses need special handling for sorting
            var item = '';
            if (data_type[j] == 'ip') {
                var hidden_item = '<span style="display: none">' + data[i][j] + '</span>';
                if (detail[j])
                    item = hidden_item + '<a href="' + detail[j] + long2ip(data[i][j]) + '">' + long2ip(data[i][j]) + '</a>';
                else
                    item = hidden_item + long2ip(data[i][j]);
            } else {
                if (detail[j])
                    item = '<a href="' + detail[j] + data[i][j] + '">' + data[i][j] + '</a>';
                else
                    item = data[i][j];
            }

            row.push(item);
        }

        table.fnAddData(row);
    }

    table.fnSort( [ [series_highlight, series_sort] ] );
    table.fnAdjustColumnSizing();
    table.bind('sort', function () { data_table_event( 'Sort', table, report_id ); })
}

// Sort event handler
//-------------------

function data_table_event(type, tableref, report_id) {
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
