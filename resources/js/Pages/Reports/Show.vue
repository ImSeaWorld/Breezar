<template>
    <AuthenticatedLayout>
        <div class="q-gutter-md">
            <div class="row items-center justify-between q-mb-md">
                <div>
                    <div class="text-h5">{{ formatType(report.type) }} Report</div>
                    <div class="text-caption text-grey-7">
                        {{ report.client.name }} - {{ formatMonth(report.report_month) }}
                    </div>
                </div>
                <div>
                    <q-btn
                        color="primary"
                        icon="mdi-arrow-left"
                        label="Back to Reports"
                        @click="$inertia.visit(route('reports.index'))"
                        class="q-mr-sm"
                    />
                    <q-btn
                        color="secondary"
                        icon="mdi-download"
                        label="Download"
                        @click="downloadReport"
                    />
                </div>
            </div>

            <!-- Report Meta Info -->
            <q-card>
                <q-card-section>
                    <div class="row q-col-gutter-md">
                        <div class="col-12 col-md-3">
                            <div class="text-caption text-grey-7">Client</div>
                            <div class="text-subtitle1">{{ report.client.name }}</div>
                        </div>
                        <div class="col-12 col-md-3">
                            <div class="text-caption text-grey-7">Report Type</div>
                            <div class="text-subtitle1">{{ formatType(report.type) }}</div>
                        </div>
                        <div class="col-12 col-md-3">
                            <div class="text-caption text-grey-7">Report Month</div>
                            <div class="text-subtitle1">{{ formatMonth(report.report_month) }}</div>
                        </div>
                        <div class="col-12 col-md-3">
                            <div class="text-caption text-grey-7">Generated At</div>
                            <div class="text-subtitle1">{{ formatDate(report.generated_at) }}</div>
                        </div>
                    </div>
                </q-card-section>
            </q-card>

            <!-- Report Content -->
            <component
                :is="getReportComponent(report.type)"
                :data="report.data"
            />
        </div>
    </AuthenticatedLayout>
</template>

<script>
import AuthenticatedLayout from '@/Layouts/Authenticated.vue';
import UsageReport from './Components/UsageReport.vue';
import PerformanceReport from './Components/PerformanceReport.vue';
import WorkItemsReport from './Components/WorkItemsReport.vue';
import CustomReport from './Components/CustomReport.vue';

export default {
    components: {
        AuthenticatedLayout,
        UsageReport,
        PerformanceReport,
        WorkItemsReport,
        CustomReport,
    },

    props: {
        report: Object,
    },

    methods: {
        formatType(type) {
            const types = {
                'usage': 'Usage',
                'performance': 'Performance',
                'work_items': 'Work Items',
                'custom': 'Custom',
            };
            
            return types[type] || type;
        },

        formatMonth(date) {
            return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'long' });
        },

        formatDate(date) {
            return new Date(date).toLocaleString();
        },

        getReportComponent(type) {
            const components = {
                'usage': 'UsageReport',
                'performance': 'PerformanceReport',
                'work_items': 'WorkItemsReport',
                'custom': 'CustomReport',
            };
            
            return components[type] || 'CustomReport';
        },

        downloadReport() {
            // Create a blob from the report data
            const reportContent = {
                client: this.report.client.name,
                type: this.report.type,
                month: this.report.report_month,
                generated_at: this.report.generated_at,
                data: this.report.data,
            };

            const blob = new Blob([JSON.stringify(reportContent, null, 2)], { type: 'application/json' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `${this.report.client.name}_${this.report.type}_${this.formatMonth(this.report.report_month)}.json`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);

            this.$q.notify({
                type: 'positive',
                message: 'Report downloaded successfully',
            });
        },
    },
};
</script>