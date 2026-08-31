<?php
// Used by includes/dashboard-layout.php only on admin-dashboard.php to show inventory reports.
?>
<section id="section-reports" class="page-section" aria-label="Database reports" hidden>
    <div class="section-intro">
        <h2 class="h6 font-weight-bold">
            Inventory Reports
        </h2>
    </div>
    <div class="toolbar">
        <div class="search-field">
            <label for="report-type">Report</label><select id="report-type" class="custom-select"><option value="report_equipment">Equipment utilization · LEFT JOIN</option><option value="report_users">User activity · RIGHT JOIN</option><option value="report_full">Catalog &amp; release activity · FULL JOIN equivalent</option></select>
        </div>
    </div>
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive" tabindex="0" role="region" aria-label="Report table">
                <table class="table mb-0">
                    <caption class="sr-only">
                        Selected inventory report
                    </caption>
                    <thead class="thead-light" id="report-head">
                    </thead>
                    <tbody id="report-body">
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div id="report-pagination" class="pagination-bar">
    </div>
</section>
