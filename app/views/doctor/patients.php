<?php /** Doctor: patients list — live search + numbered pagination + print */ ?>
<div class="page-header">
    <div>
        <h2 class="page-title"><i class="bi bi-people-fill"></i> <?= e($title) ?></h2>
        <div class="page-subtitle">المرضى الذين عالجتهم + المحالون إليك — <?= count($patients) ?> مريض</div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-md-8">
                <label class="form-label small">بحث لحظي</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" id="searchInput" class="form-control" placeholder="ابحث بالاسم أو الرقم المميز أو الهاتف..." oninput="applyFilters()">
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

<?php if (!empty($referred)): ?>
<div class="card mb-3">
    <div class="card-header"><i class="bi bi-arrow-left-right text-pink"></i> مرضى محالون إليك (<?= count($referred) ?>)</div>
    <div class="card-body p-0">
        <div class="list-group list-group-flush">
            <?php foreach ($referred as $p): ?>
                <a href="<?= url('/doctor/patients/' . $p['id']) ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                    <div><span class="fw-bold"><?= e($p['full_name']) ?></span> <span class="uid-code"><?= e($p['unique_id']) ?></span></div>
                    <small class="text-muted" dir="ltr"><?= e($p['phone']) ?></small>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-person-lines-fill text-purple"></i> مرضاي</span>
        <small class="text-muted" id="resultCount"><?= count($patients) ?> سجل</small>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 medcore-table">
                <thead>
                    <tr>
                        <th style="width: 40px;">#</th>
                        <th>الاسم</th>
                        <th>الرقم المميز</th>
                        <th>الهاتف</th>
                        <th>الجنس</th>
                        <th>العمر</th>
                        <th style="width: 200px;">إجراءات</th>
                    </tr>
                </thead>
                <tbody id="patientsTableBody"></tbody>
            </table>
        </div>
    </div>
    <div class="card-footer" id="paginationBar"></div>
</div>

<script>
var allPatients = <?= json_encode($patients, JSON_UNESCAPED_UNICODE) ?>;
var currentPage = 1;

function applyFilters() { currentPage = 1; renderTable(); }

function renderTable() {
    var search = (document.getElementById('searchInput').value || '').toLowerCase().trim();
    var pageSize = parseInt(document.getElementById('pageSizeSelect').value);

    var filtered = allPatients.filter(function(p) {
        if (!search) return true;
        return (p.full_name||'').toLowerCase().includes(search) ||
               (p.unique_id||'').includes(search) ||
               (p.phone||'').includes(search);
    });

    var totalPages = Math.ceil(filtered.length / pageSize) || 1;
    if (currentPage > totalPages) currentPage = totalPages;
    if (currentPage < 1) currentPage = 1;
    var start = (currentPage - 1) * pageSize;
    var pageData = filtered.slice(start, start + pageSize);

    var tbody = document.getElementById('patientsTableBody');
    if (pageData.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4"><i class="bi bi-inbox" style="font-size:24px;opacity:0.4;"></i><p class="mt-2">لا نتائج مطابقة</p></td></tr>';
    } else {
        tbody.innerHTML = pageData.map(function(p, i) {
            var num = start + i + 1;
            var age = p.birth_date ? (new Date().getFullYear() - new Date(p.birth_date).getFullYear()) : '-';
            var genderLabel = p.gender === 'male' ? 'ذكر' : 'أنثى';
            var profileUrl = '<?= url("/doctor/patients") ?>/' + p.id;
            var orderUrl = profileUrl + '/order-test';
            var printUrl = profileUrl + '?print=1';
            return '<tr>' +
                '<td class="text-muted">' + num + '</td>' +
                '<td class="fw-bold">' + (p.full_name || '') + '</td>' +
                '<td><span class="uid-code">' + (p.unique_id || '') + '</span></td>' +
                '<td dir="ltr" class="small">' + (p.phone || '') + '</td>' +
                '<td>' + genderLabel + '</td>' +
                '<td>' + age + '</td>' +
                '<td><div class="d-flex gap-1 flex-wrap">' +
                '<a href="' + profileUrl + '" class="btn btn-sm btn-outline-primary spa-link" data-spa="1" data-url="' + profileUrl + '" title="الملف"><i class="bi bi-folder"></i></a>' +
                '<a href="' + orderUrl + '" class="btn btn-sm btn-info spa-link" data-spa="1" data-url="' + orderUrl + '" title="طلب تحليل"><i class="bi bi-plus"></i></a>' +
                '<button class="btn btn-sm btn-outline-secondary" onclick="printPatient(' + p.id + ')" title="طباعة التقرير الطبي"><i class="bi bi-printer"></i></button>' +
                '</div></td>' +
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

function printPatient(id) {
    window.open('<?= url("/doctor/patients") ?>/' + id + '?print=1', '_blank');
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', renderTable);
} else {
    renderTable();
}
document.addEventListener('spa:navigated', renderTable);
</script>
