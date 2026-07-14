/**
 * MedCore Calendar Component
 * Creates a beautiful monthly calendar with clickable days.
 * Days with data show a badge. Clicking a day calls onDayClick(dateStr).
 */
function createCalendar(containerId, options) {
    var container = document.getElementById(containerId);
    if (!container) return;

    var currentDate = options.currentDate || new Date();
    var selectedDate = options.selectedDate || null;
    var dataMap = options.dataMap || {}; // { '2026-07-15': [{start:'09:00', end:'13:00'}], ... }
    var onDayClick = options.onDayClick || function() {};
    var minDate = options.minDate || null;

    function render() {
        var year = currentDate.getFullYear();
        var month = currentDate.getMonth();
        var monthNames = ['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر'];
        var dayNames = ['الأحد','الإثنين','الثلاثاء','الأربعاء','الخميس','الجمعة','السبت'];

        var firstDay = new Date(year, month, 1);
        var lastDay = new Date(year, month + 1, 0);
        var startDayOfWeek = firstDay.getDay();
        var daysInMonth = lastDay.getDate();
        var todayStr = new Date().toISOString().split('T')[0];

        var html = '<div class="medcal">';
        html += '<div class="medcal-header">';
        html += '<button class="medcal-nav-btn" onclick="medcalPrev(\'' + containerId + '\')"><i class="bi bi-chevron-right"></i></button>';
        html += '<div class="medcal-title">' + monthNames[month] + ' ' + year + '</div>';
        html += '<button class="medcal-nav-btn" onclick="medcalNext(\'' + containerId + '\')"><i class="bi bi-chevron-left"></i></button>';
        html += '</div>';
        html += '<div class="medcal-grid">';
        dayNames.forEach(function(d) {
            html += '<div class="medcal-day-name">' + d + '</div>';
        });

        // Empty cells before first day
        for (var i = 0; i < startDayOfWeek; i++) {
            html += '<div class="medcal-day empty"></div>';
        }

        // Days
        for (var d = 1; d <= daysInMonth; d++) {
            var dateObj = new Date(year, month, d);
            var dateStr = year + '-' + String(month + 1).padStart(2, '0') + '-' + String(d).padStart(2, '0');
            var isToday = dateStr === todayStr;
            var isSelected = dateStr === selectedDate;
            var hasData = dataMap[dateStr] && dataMap[dateStr].length > 0;
            var isPast = minDate && dateStr < minDate;

            var classes = 'medcal-day';
            if (isToday) classes += ' today';
            if (isSelected) classes += ' selected';
            if (hasData) classes += ' has-data';
            if (isPast) classes += ' past';

            html += '<div class="' + classes + '" onclick="' + (isPast ? '' : 'medcalClick(\'' + containerId + '\',\'' + dateStr + '\')') + '">';
            html += '<span class="medcal-day-num">' + d + '</span>';
            if (hasData) {
                html += '<span class="medcal-badge">' + dataMap[dateStr].length + '</span>';
            }
            html += '</div>';
        }

        html += '</div></div>';
        container.innerHTML = html;
    }

    // Store state
    container._medcalState = { currentDate: currentDate, selectedDate: selectedDate, dataMap: dataMap, onDayClick: onDayClick, minDate: minDate, render: render };
    render();
}

function medcalPrev(containerId) {
    var c = document.getElementById(containerId);
    if (!c._medcalState) return;
    c._medcalState.currentDate.setMonth(c._medcalState.currentDate.getMonth() - 1);
    c._medcalState.render();
}

function medcalNext(containerId) {
    var c = document.getElementById(containerId);
    if (!c._medcalState) return;
    c._medcalState.currentDate.setMonth(c._medcalState.currentDate.getMonth() + 1);
    c._medcalState.render();
}

function medcalClick(containerId, dateStr) {
    var c = document.getElementById(containerId);
    if (!c._medcalState) return;
    c._medcalState.selectedDate = dateStr;
    c._medcalState.render();
    if (c._medcalState.onDayClick) c._medcalState.onDayClick(dateStr);
}

function medcalUpdateData(containerId, dataMap) {
    var c = document.getElementById(containerId);
    if (!c._medcalState) return;
    c._medcalState.dataMap = dataMap;
    c._medcalState.render();
}
