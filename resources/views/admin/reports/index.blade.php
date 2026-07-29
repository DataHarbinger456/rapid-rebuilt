@extends('layouts.admin.master')

@section('title')Reports @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('breadcrumb_title')
            <h3>Reports</h3>
        @endslot
        <li class="breadcrumb-item">Admin</li>
        <li class="breadcrumb-item active">Reports</li>
    @endcomponent

    <div class="container-fluid">
        <div class="row">
            @foreach($available_reports as $report)
                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-light rounded-circle p-3 me-3">
                                    @switch($report['id'])
                                        @case('revenue')
                                            <i data-feather="dollar-sign" class="text-success"></i>
                                            @break
                                        @case('cases')
                                            <i data-feather="briefcase" class="text-primary"></i>
                                            @break
                                        @case('inspections')
                                            <i data-feather="clipboard" class="text-warning"></i>
                                            @break
                                        @case('inspector-performance')
                                            <i data-feather="users" class="text-info"></i>
                                            @break
                                        @case('customers')
                                            <i data-feather="user-plus" class="text-secondary"></i>
                                            @break
                                        @default
                                            <i data-feather="bar-chart-2"></i>
                                    @endswitch
                                </div>
                                <h5 class="mb-0">{{ $report['name'] }}</h5>
                            </div>
                            <p class="text-muted mb-3">{{ $report['description'] }}</p>
                        </div>
                        <div class="card-footer bg-transparent">
                            <a href="#" class="btn btn-primary w-100" data-report="{{ $report['id'] }}" onclick="loadReport('{{ $report['id'] }}')">
                                <i data-feather="bar-chart-2"></i> View Report
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Report Display Area -->
        <div class="row" id="reportArea" style="display: none;">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 id="reportTitle">Report</h5>
                        <div>
                            <input type="date" id="startDate" class="form-control d-inline-block" style="width: auto;">
                            <input type="date" id="endDate" class="form-control d-inline-block" style="width: auto;">
                            <button class="btn btn-primary" onclick="refreshReport()">Refresh</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="reportContent">
                            <div class="text-center py-5">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let currentReport = null;
let reportChart = null;
const baseUrl = '{{ url("/") }}';

// Format money as US$1,234.56
function formatMoney(value) {
    return 'US$' + Number(value).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}
const reportPaths = {
    'revenue': '/admin/reports/revenue',
    'cases': '/admin/reports/cases',
    'inspections': '/admin/reports/inspections',
    'inspector-performance': '/admin/reports/inspector-performance',
    'customers': '/admin/reports/customers'
};

function loadReport(reportId) {
    currentReport = reportId;
    document.getElementById('reportArea').style.display = 'block';
    document.getElementById('reportTitle').textContent = reportId.replace('-', ' ').replace(/\b\w/g, l => l.toUpperCase()) + ' Report';
    refreshReport();
}

function refreshReport() {
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;

    // Use current origin to avoid cross-domain issues
    let url = window.location.origin + reportPaths[currentReport] + '?';
    if (startDate) url += `start_date=${startDate}&`;
    if (endDate) url += `end_date=${endDate}`;

    document.getElementById('reportContent').innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;

    fetch(url, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('HTTP error ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            renderReport(currentReport, data.data);
        } else {
            document.getElementById('reportContent').innerHTML = '<p class="text-danger">Error: ' + (data.message || 'Unknown error') + '</p>';
        }
    })
    .catch(error => {
        document.getElementById('reportContent').innerHTML = `
            <div class="alert alert-danger">
                <strong>Error loading report:</strong> ${error.message}
            </div>`;
    });
}

function renderReport(type, data) {
    let html = '<div class="row">';

    if (data.metrics || data.totals) {
        const metrics = data.metrics || data.totals;
        html += '<div class="col-12 mb-4"><div class="row">';
        for (const [key, value] of Object.entries(metrics)) {
            html += `
                <div class="col-md-3 mb-3">
                    <div class="card bg-light">
                        <div class="card-body text-center">
                            <h4 class="mb-1 text-dark">${(key.includes('revenue') || key.includes('price') || key.includes('amount')) ? formatMoney(value) : value}</h4>
                            <small class="text-dark">${key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}</small>
                        </div>
                    </div>
                </div>
            `;
        }
        html += '</div></div>';
    }

    html += '<div class="col-12"><canvas id="reportChart" height="100"></canvas></div>';
    html += '</div>';

    document.getElementById('reportContent').innerHTML = html;

    // Render chart based on data
    const ctx = document.getElementById('reportChart').getContext('2d');
    if (reportChart) reportChart.destroy();

    let chartData = data.chart_data || data.over_time || data.pass_rate_over_time || data.new_customers_over_time || [];

    if (chartData.length > 0) {
        reportChart = new Chart(ctx, {
            type: type === 'revenue' ? 'line' : 'bar',
            data: {
                labels: chartData.map(d => d.period || d.month),
                datasets: [{
                    label: type.replace('-', ' '),
                    data: chartData.map(d => d.revenue || d.count || d.created || d.total || 0),
                    backgroundColor: '#24695c',
                    borderColor: '#24695c',
                    fill: type === 'revenue'
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    }
}

// Set default dates
document.addEventListener('DOMContentLoaded', function() {
    const today = new Date();
    const sixMonthsAgo = new Date(today.setMonth(today.getMonth() - 6));
    document.getElementById('endDate').value = new Date().toISOString().split('T')[0];
    document.getElementById('startDate').value = sixMonthsAgo.toISOString().split('T')[0];
});
</script>
@endpush
