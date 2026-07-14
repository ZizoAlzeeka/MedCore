<?php /** Lab Tech: all orders in one table with status column, live search, pagination, status filters, view results modal, print */ ?>
<div class="page-header">
    <div>
        <h2 class="page-title"><i class="bi bi-list-task"></i> <?= e($title) ?></h2>
        <div class="page-subtitle">جميع الطلبات — <?= count($orders) ?> طلب</div>
    </div>
</div>

<!-- Status filter buttons -->
<div class="card mb-3">
    <div class="card-body py-2">
        <div class="d-flex flex-wrap gap-1 mb-2">
            <button class="btn btn-sm btn-outline-primary active" data-status-filter="all" onclick="setStatusFilter('all')">الكل (<span id="cnt-all">0</span>)</button>
            <button class="btn btn-sm btn-outline-warning" data-status-filter="ordered" onclick="setStatusFilter('ordered')">بانتظار التنفيذ (<span id="cnt-ordered">0</span>)</button>
            <button class="btn btn-sm btn-outline-info" data-status-filter="in_progress" onclick="setStatusFilter('in_progress')">قيد التنفيذ (<span id="cnt-in_progress">0</span>)</button>
            <button class="btn btn-sm btn-outline-success" data-status-filter="result_uploaded" onclick="setStatusFilter('result_uploaded')">مرفوعة النتائج (<span id="cnt-result_uploaded">0</span>)</button>
            <button class="btn btn-sm btn-outline-danger" data-status-filter="cancelled" onclick="setStatusFilter('cancelled')">ملغاة (<span id="cnt-cancelled">0</span>)</button>
            <button class="btn btn-sm btn-outline-secondary" data-status-filter="duplicate_skipped" onclick="setStatusFilter('duplicate_skipped')">اكتفاء بالسابق (<span id="cnt-duplicate_skipped">0</span>)</button>
        </div>
        <div class="row g-2 align-items-end">
            <div class="col-md-8">
                <label class="form-label small">بحث لحظي</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" id="searchInput" class="form-control" placeholder="ابحث برقم المريض، الاسم، كود التحليل، اسم الطبيب..." oninput="applyFilters()">
                </div>
            </div>
            <div class="col-md-4">
                <label class="form-label small">عدد الصفوف</label>
                <select id="pageSizeSelect" class="form-select form-select-sm" onchange="renderTable()">
                    <option value="10">10</option>
                    <option value="25" selected>25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-table text-purple"></i> قائمة الطلبات</span>
        <small class="text-muted" id="resultCount"><?= count($orders) ?> سجل</small>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 medcore-table">
                <thead>
                    <tr>
                        <th style="width:40px;">#</th>
                        <th>المريض</th>
                        <th>التحليل</th>
                        <th>العينة</th>
                        <th>الطبيب</th>
                        <th>التاريخ</th>
                        <th>الحالة</th>
                        <th style="width:140px;">إجراءات</th>
                    </tr>
                </thead>
                <tbody id="ordersTableBody"></tbody>
            </table>
        </div>
    </div>
    <div class="card-footer" id="paginationBar"></div>
</div>

<!-- Results Modal -->
<div class="modal fade" id="resultsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-clipboard2-data text-purple"></i> تفاصيل النتيجة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="resultsModalBody"></div>
            <div class="modal-footer">
                <button class="btn btn-outline-secondary" onclick="printResult()"><i class="bi bi-printer"></i> طباعة</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
            </div>
        </div>
    </div>
</div>

<!-- Print area (hidden, used only for printing single order) -->
<div id="printArea" style="display:none;"></div>

<script>
var allOrders = <?= json_encode($orders, JSON_UNESCAPED_UNICODE) ?>;
var currentPage = 1;
var currentStatusFilter = 'all';
var currentResultOrder = null;

var statusLabels = {
    ordered: 'بانتظار التنفيذ',
    in_progress: 'قيد التنفيذ',
    result_uploaded: 'تم رفع النتيجة',
    cancelled: 'ملغى',
    duplicate_skipped: 'اكتفاء بالسابق'
};
var statusBadgeClass = {
    ordered: 'bg-warning',
    in_progress: 'bg-info',
    result_uploaded: 'bg-success',
    cancelled: 'bg-danger',
    duplicate_skipped: 'bg-secondary'
};

function updateCounters() {
    var counts = { all: allOrders.length, ordered: 0, in_progress: 0, result_uploaded: 0, cancelled: 0, duplicate_skipped: 0 };
    allOrders.forEach(function(o) {
        if (counts[o.status] !== undefined) counts[o.status]++;
    });
    Object.keys(counts).forEach(function(k) {
        var el = document.getElementById('cnt-' + k);
        if (el) el.textContent = counts[k];
    });
}

function setStatusFilter(s) {
    currentStatusFilter = s;
    document.querySelectorAll('[data-status-filter]').forEach(function(b) {
        b.classList.remove('active');
    });
    var activeBtn = document.querySelector('[data-status-filter="' + s + '"]');
    if (activeBtn) activeBtn.classList.add('active');
    currentPage = 1;
    renderTable();
}

function applyFilters() { currentPage = 1; renderTable(); }

function renderTable() {
    var search = (document.getElementById('searchInput').value || '').toLowerCase().trim();
    var pageSize = parseInt(document.getElementById('pageSizeSelect').value);

    var filtered = allOrders.filter(function(o) {
        var matchStatus = (currentStatusFilter === 'all') || (o.status === currentStatusFilter);
        if (!matchStatus) return false;
        if (!search) return true;
        return (o.patient_name||'').toLowerCase().includes(search) ||
               (o.patient_uid||'').toLowerCase().includes(search) ||
               (o.loinc_code||'').toLowerCase().includes(search) ||
               (o.name_ar||'').toLowerCase().includes(search) ||
               (o.doctor_name||'').toLowerCase().includes(search) ||
               (String(o.id||'')).includes(search);
    });

    var totalPages = Math.ceil(filtered.length / pageSize) || 1;
    if (currentPage > totalPages) currentPage = totalPages;
    if (currentPage < 1) currentPage = 1;
    var start = (currentPage - 1) * pageSize;
    var pageData = filtered.slice(start, start + pageSize);

    var tbody = document.getElementById('ordersTableBody');
    if (pageData.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-4"><i class="bi bi-inbox" style="font-size:24px;opacity:0.4;"></i><p class="mt-2">لا نتائج مطابقة</p></td></tr>';
    } else {
        tbody.innerHTML = pageData.map(function(o, i) {
            var num = start + i + 1;
            var statusLabel = statusLabels[o.status] || o.status;
            var statusClass = statusBadgeClass[o.status] || 'bg-secondary';
            var actions = '';
            if (o.status === 'ordered') {
                actions = '<a href="<?= url("/labtech/orders") ?>/' + o.id + '/upload" class="btn btn-sm btn-success spa-link" data-spa="1" data-url="<?= url("/labtech/orders") ?>/' + o.id + '/upload" title="رفع النتيجة"><i class="bi bi-upload"></i></a> ';
            }
            if (o.status === 'result_uploaded' && o.result_value) {
                actions += '<button class="btn btn-sm btn-outline-primary" onclick="viewResult(' + o.id + ')" title="عرض النتيجة"><i class="bi bi-eye"></i></button> ';
                actions += '<button class="btn btn-sm btn-outline-secondary" onclick="printOrder(' + o.id + ')" title="طباعة"><i class="bi bi-printer"></i></button>';
            }
            if (!actions) actions = '<span class="text-muted">—</span>';
            return '<tr>' +
                '<td class="text-muted">' + num + '</td>' +
                '<td class="fw-bold">' + (o.patient_name||'') + ' <span class="uid-code">' + (o.patient_uid||'') + '</span></td>' +
                '<td><span class="loinc-code">' + (o.loinc_code||'') + '</span> ' + (o.name_ar||'') + '</td>' +
                '<td><span class="badge bg-info">' + (o.sample_type||'') + '</span></td>' +
                '<td class="small">' + (o.doctor_name||'-') + '</td>' +
                '<td class="small text-muted">' + (o.ordered_at||'').split(' ')[0] + '</td>' +
                '<td><span class="badge ' + statusClass + '">' + statusLabel + '</span></td>' +
                '<td><div class="d-flex gap-1">' + actions + '</div></td>' +
                '</tr>';
        }).join('');
    }

    document.getElementById('resultCount').textContent = filtered.length + ' سجل';
    renderPagination(totalPages, filtered.length, start, pageData.length);
}

function renderPagination(totalPages, totalRows, start, count) {
    var paginationBar = document.getElementById('paginationBar');
    if (totalPages <= 1) { paginationBar.innerHTML = ''; return; }

    var info = '<div class="medcore-pagination-info">عرض ' + (start+1) + ' إلى ' + (start+count) + ' من ' + totalRows + ' سجل</div>';
    var html = '<div class="medcore-pagination">';

    html += '<button class="page-btn' + (currentPage <= 1 ? ' disabled' : '') + '" ' + (currentPage <= 1 ? '' : 'onclick="goPage('+(currentPage-1)+')"') + '><i class="bi bi-chevron-right"></i></button>';

    var maxButtons = 7;
    var pages = [];
    if (totalPages <= maxButtons) {
        for (var i = 1; i <= totalPages; i++) pages.push(i);
    } else {
        pages.push(1);
        if (currentPage > 3) pages.push('...');
        var s = Math.max(2, currentPage - 1);
        var e = Math.min(totalPages - 1, currentPage + 1);
        for (var i = s; i <= e; i++) pages.push(i);
        if (currentPage < totalPages - 2) pages.push('...');
        pages.push(totalPages);
    }
    pages.forEach(function(p) {
        if (p === '...') {
            html += '<span class="page-dots">…</span>';
        } else {
            html += '<button class="page-btn' + (p === currentPage ? ' active' : '') + '" onclick="goPage('+p+')">' + p + '</button>';
        }
    });

    html += '<button class="page-btn' + (currentPage >= totalPages ? ' disabled' : '') + '" ' + (currentPage >= totalPages ? '' : 'onclick="goPage('+(currentPage+1)+')"') + '><i class="bi bi-chevron-left"></i></button>';
    html += '</div>';

    paginationBar.innerHTML = info + html;
}

function goPage(p) { currentPage = p; renderTable(); }

function viewResult(orderId) {
    var o = allOrders.find(function(x) { return x.id == orderId; });
    if (!o) return;
    currentResultOrder = o;
    var flagLabel = { normal: 'طبيعي', high: 'مرتفع', low: 'منخفض', abnormal: 'غير طبيعي' }[o.flag] || o.flag;
    var flagClass = { normal: 'bg-success', high: 'bg-warning', low: 'bg-info', abnormal: 'bg-danger' }[o.flag] || 'bg-secondary';
    var html = '<table class="table table-sm">' +
        '<tr><td style="width:30%" class="text-muted">المريض:</td><td class="fw-bold">' + (o.patient_name||'') + ' <span class="uid-code">' + (o.patient_uid||'') + '</span></td></tr>' +
        '<tr><td class="text-muted">الهاتف:</td><td dir="ltr">' + (o.phone||'-') + '</td></tr>' +
        '<tr><td class="text-muted">التحليل:</td><td><span class="loinc-code" dir="ltr">' + (o.loinc_code||'') + '</span> ' + (o.name_ar||'') + '</td></tr>' +
        '<tr><td class="text-muted">نوع العينة:</td><td>' + (o.sample_type||'-') + '</td></tr>' +
        '<tr><td class="text-muted">الطبيب:</td><td>' + (o.doctor_name||'-') + '</td></tr>' +
        '<tr><td class="text-muted">قيمة النتيجة:</td><td dir="ltr" style="text-align:right;" class="fw-bold fs-5 text-purple">' + (o.result_value||'-') + ' ' + (o.unit||'') + '</td></tr>' +
        '<tr><td class="text-muted">النطاق الطبيعي:</td><td dir="ltr" style="text-align:right;">' + (o.normal_range||'-') + '</td></tr>' +
        '<tr><td class="text-muted">العلم:</td><td><span class="badge ' + flagClass + '">' + flagLabel + '</span></td></tr>' +
        '<tr><td class="text-muted">تاريخ التنفيذ:</td><td>' + (o.performed_at||'-') + '</td></tr>' +
        '<tr><td class="text-muted">تاريخ الرفع:</td><td>' + (o.uploaded_at||'-') + '</td></tr>' +
        '<tr><td class="text-muted">فني المختبر:</td><td>' + (o.lab_tech_name||'-') + '</td></tr>' +
        (o.result_notes ? '<tr><td class="text-muted">ملاحظات:</td><td>' + o.result_notes + '</td></tr>' : '') +
        '</table>';
    document.getElementById('resultsModalBody').innerHTML = html;
    new bootstrap.Modal(document.getElementById('resultsModal')).show();
}

function printOrder(orderId) {
    var o = allOrders.find(function(x) { return x.id == orderId; });
    if (!o) return;
    currentResultOrder = o;
    openPrintWindow(o);
}

function printResult() {
    if (currentResultOrder) openPrintWindow(currentResultOrder);
}

function openPrintWindow(o) {
    var flagLabel = { normal: 'طبيعي', high: 'مرتفع', low: 'منخفض', abnormal: 'غير طبيعي' }[o.flag] || o.flag;
    var html = '<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="UTF-8">' +
        '<title>نتيجة تحليل - ' + (o.patient_name||'') + '</title>' +
        '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">' +
        '<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">' +
        '<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">' +
        '<style>body{font-family:"Cairo",sans-serif;padding:20px;font-size:13px;}' +
        '.logo{width:50px;height:50px;border-radius:8px;}' +
        '.pr-header{background:linear-gradient(135deg,#6C63FF,#9D4EDD);color:#fff;padding:12px 16px;border-radius:8px;display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;}' +
        'table{font-size:12.5px;}' +
        '.loinc-code{font-family:monospace;background:rgba(108,99,255,0.1);color:#6C63FF;padding:1px 6px;border-radius:4px;font-weight:700;}' +
        '.uid-code{font-family:"Courier New",monospace;background:rgba(108,99,255,0.08);padding:2px 6px;border-radius:4px;font-weight:700;color:#6C63FF;}' +
        '.footer{margin-top:30px;text-align:center;color:#666;font-size:11px;border-top:1px solid #ddd;padding-top:10px;}' +
        '</style></head><body>' +
        '<div class="pr-header"><span><i class="bi bi-file-medical-text"></i> تقرير نتيجة التحليل</span><img src="<?= asset("img/logo.png") ?>" class="logo" alt="logo"></div>' +
        '<table class="table table-sm table-bordered">' +
        '<tr><td style="width:30%" class="text-muted">المريض:</td><td class="fw-bold">' + (o.patient_name||'') + ' <span class="uid-code">' + (o.patient_uid||'') + '</span></td></tr>' +
        '<tr><td class="text-muted">الهاتف:</td><td dir="ltr">' + (o.phone||'-') + '</td></tr>' +
        '<tr><td class="text-muted">التحليل:</td><td><span class="loinc-code" dir="ltr">' + (o.loinc_code||'') + '</span> ' + (o.name_ar||'') + '</td></tr>' +
        '<tr><td class="text-muted">نوع العينة:</td><td>' + (o.sample_type||'-') + '</td></tr>' +
        '<tr><td class="text-muted">الطبيب:</td><td>' + (o.doctor_name||'-') + '</td></tr>' +
        '<tr><td class="text-muted">قيمة النتيجة:</td><td dir="ltr" style="text-align:right;color:#6C63FF;" class="fw-bold fs-5">' + (o.result_value||'-') + ' ' + (o.unit||'') + '</td></tr>' +
        '<tr><td class="text-muted">النطاق الطبيعي:</td><td dir="ltr" style="text-align:right;">' + (o.normal_range||'-') + '</td></tr>' +
        '<tr><td class="text-muted">العلم:</td><td>' + flagLabel + '</td></tr>' +
        '<tr><td class="text-muted">تاريخ التنفيذ:</td><td>' + (o.performed_at||'-') + '</td></tr>' +
        '<tr><td class="text-muted">تاريخ الرفع:</td><td>' + (o.uploaded_at||'-') + '</td></tr>' +
        '<tr><td class="text-muted">فني المختبر:</td><td>' + (o.lab_tech_name||'-') + '</td></tr>' +
        (o.result_notes ? '<tr><td class="text-muted">ملاحظات:</td><td>' + o.result_notes + '</td></tr>' : '') +
        '</table>' +
        '<div class="footer"><div>تم إنشاء هذا التقرير بواسطة منصة MedCore - ' + new Date().toLocaleString('ar') + '</div>' +
        '<div style="margin-top:8px;"><span style="margin:0 30px;">توقيع فني المختبر: ____________________</span><span>ختم المختبر: ____________________</span></div></div>' +
        '<script>window.onload=function(){setTimeout(function(){window.print();},300);};<\/script>' +
        '</body></html>';
    var w = window.open('', '_blank');
    if (w) {
        w.document.open();
        w.document.write(html);
        w.document.close();
    } else {
        alert('الرجاء السماح بالنوافذ المنبثقة للطباعة');
    }
}

updateCounters();
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', renderTable);
} else {
    renderTable();
}
document.addEventListener('spa:navigated', renderTable);
</script>
