<?php /** Doctor: treatment plan form (Quill) */
$extraScripts = '
<script>
function initTreatmentEditor() {
    if (typeof Quill !== "undefined" && document.getElementById("editor-container")) {
        if (!document.querySelector(".ql-toolbar")) {
            // ⚡ Quill default: LTR toolbar, LTR editor (original English design)
            // Doctor uses the "direction" button (RTL/LTR icon) in toolbar to
            // switch any paragraph to RTL when writing Arabic.
            var quill = new Quill("#editor-container", {
                theme: "snow",
                modules: {
                    toolbar: [
                        [{ header: [1, 2, 3, 4, 5, 6, false] }],
                        ["bold", "italic", "underline", "strike"],
                        [{ color: [] }, { background: [] }],
                        [{ list: "ordered" }, { list: "bullet" }],
                        [{ align: [] }],
                        [{ direction: [] }],
                        ["link", "blockquote"],
                        ["clean"]
                    ]
                }
            });

            // Sync content to hidden input
            var hidden = document.getElementById("description_html");
            if (hidden) {
                quill.on("text-change", function() {
                    hidden.value = quill.root.innerHTML;
                });
                if (hidden.value) {
                    quill.root.innerHTML = hidden.value;
                }
            }
        }
    } else {
        setTimeout(initTreatmentEditor, 100);
    }
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initTreatmentEditor);
} else {
    initTreatmentEditor();
}
document.addEventListener("spa:navigated", initTreatmentEditor);
</script>
';
?>
<div class="page-header">
    <div>
        <h2 class="page-title"><i class="bi bi-capsules"></i> <?= e($title) ?></h2>
        <div class="page-subtitle">المريض: <strong><?= e($order['patient_name']) ?></strong> — التحليل: <span class="loinc-code" dir="ltr"><?= e($order['loinc_code']) ?></span> <?= e($order['name_ar']) ?></div>
    </div>
    <a href="<?= url('/doctor/patients/' . $order['patient_id']) ?>" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-right"></i> رجوع</a>
</div>

<div class="card mb-3">
    <div class="card-header"><i class="bi bi-clipboard2-data text-purple"></i> نتيجة التحليل</div>
    <div class="card-body">
        <table class="table table-sm">
            <tr><td>القيمة:</td><td dir="ltr" style="text-align:right;" class="fw-bold"><?= e($order['result_value']) ?> <?= e($order['unit']) ?></td>
            <td>النطاق الطبيعي:</td><td dir="ltr" style="text-align:right;"><?= e($order['normal_range']) ?></td></tr>
            <tr><td>العلم:</td><td><?= statusBadge($order['flag']) ?></td>
            <td>تاريخ التنفيذ:</td><td><?= formatDate($order['performed_at'], true) ?></td></tr>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header gradient"><i class="bi bi-pencil-square"></i> كتابة خطة العلاج</div>
    <div class="card-body">
        <form method="post" action="<?= url('/doctor/orders/' . $order['id'] . '/treatment') ?>">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">اسم العلاج / الدواء <span class="text-danger">*</span></label>
                <input type="text" name="treatment_name" class="form-control" required placeholder="مثال: أوميبرازول 20mg">
            </div>
            <div class="mb-3">
                <label class="form-label">الوصف وطريقة الاستخدام <span class="text-danger">*</span></label>
                <div id="editor-container" style="min-height:200px;"></div>
                <input type="hidden" name="description_html" id="description_html">
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> حفظ خطة العلاج</button>
                <a href="<?= url('/doctor/patients/' . $order['patient_id']) ?>" class="btn btn-secondary">إلغاء</a>
            </div>
        </form>
    </div>
</div>
