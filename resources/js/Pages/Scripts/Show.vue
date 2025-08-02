<template>
    <AuthenticatedLayout>
        <div class="q-gutter-md">
            <div class="row items-center justify-between q-mb-md">
                <div>
                    <div class="text-h5">{{ script.name }}</div>
                    <div class="text-caption text-grey-7">{{ script.description }}</div>
                </div>
                <div>
                    <q-btn
                        color="primary"
                        icon="mdi-pencil"
                        label="Edit"
                        @click="$inertia.visit(route('scripts.edit', script.id))"
                        class="q-mr-sm"
                    />
                    <q-btn
                        color="secondary"
                        icon="mdi-arrow-left"
                        label="Back"
                        @click="$inertia.visit(route('scripts.index'))"
                    />
                </div>
            </div>

            <div class="row q-col-gutter-md">
                <!-- Script Details & Code -->
                <div class="col-12 col-lg-8">
                    <q-card>
                        <q-card-section>
                            <div class="text-h6 q-mb-md">Script Code</div>
                            <CodeMirrorEditor
                                :modelValue="script.tinker_code"
                                :options="editorOptions"
                                @update:modelValue="() => {}"
                            />
                        </q-card-section>

                        <q-separator />

                        <q-card-section>
                            <div class="text-h6 q-mb-md">Execute Script</div>
                            
                            <q-form @submit="executeScript">
                                <div class="row q-col-gutter-md items-end">
                                    <div class="col-12 col-md-8">
                                        <q-select
                                            v-model="executeForm.client_id"
                                            :options="clientOptions"
                                            label="Select Client (Optional)"
                                            filled
                                            emit-value
                                            map-options
                                            clearable
                                            hint="Select a client to inject $client variable"
                                        />
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <q-btn
                                            type="submit"
                                            color="positive"
                                            icon="mdi-play"
                                            label="Execute"
                                            :loading="executing"
                                            class="full-width"
                                        />
                                    </div>
                                </div>
                            </q-form>

                            <!-- Execution Output -->
                            <div v-if="executionOutput" class="q-mt-md">
                                <div class="text-subtitle2 q-mb-sm">Output:</div>
                                <q-card flat bordered>
                                    <q-card-section>
                                        <pre class="q-ma-none execution-output">{{ executionOutput }}</pre>
                                    </q-card-section>
                                </q-card>
                            </div>
                        </q-card-section>
                    </q-card>
                </div>

                <!-- Script Info & History -->
                <div class="col-12 col-lg-4">
                    <q-card>
                        <q-card-section>
                            <div class="text-h6 q-mb-md">Script Information</div>
                            
                            <q-list>
                                <q-item>
                                    <q-item-section>
                                        <q-item-label caption>Created By</q-item-label>
                                        <q-item-label>{{ script.creator?.name || 'Unknown' }}</q-item-label>
                                    </q-item-section>
                                </q-item>

                                <q-item>
                                    <q-item-section>
                                        <q-item-label caption>Created At</q-item-label>
                                        <q-item-label>{{ formatDate(script.created_at) }}</q-item-label>
                                    </q-item-section>
                                </q-item>

                                <q-item>
                                    <q-item-section>
                                        <q-item-label caption>Last Run</q-item-label>
                                        <q-item-label>{{ script.last_run_at ? formatDate(script.last_run_at) : 'Never' }}</q-item-label>
                                    </q-item-section>
                                </q-item>

                                <q-item>
                                    <q-item-section>
                                        <q-item-label caption>Total Executions</q-item-label>
                                        <q-item-label>{{ script.executions?.length || 0 }}</q-item-label>
                                    </q-item-section>
                                </q-item>
                            </q-list>
                        </q-card-section>
                    </q-card>

                    <!-- Execution History -->
                    <q-card class="q-mt-md">
                        <q-card-section>
                            <div class="text-h6 q-mb-md">Recent Executions</div>
                            
                            <q-list>
                                <q-expansion-item
                                    v-for="execution in script.executions"
                                    :key="execution.id"
                                    group="executions"
                                    :label="formatDate(execution.executed_at)"
                                    :caption="`By ${execution.executor?.name || 'Unknown'}${execution.client ? ' - ' + execution.client.name : ''}`"
                                >
                                    <q-card>
                                        <q-card-section>
                                            <pre class="q-ma-none execution-output text-caption">{{ execution.output || 'No output' }}</pre>
                                        </q-card-section>
                                    </q-card>
                                </q-expansion-item>
                                <q-item v-if="!script.executions || script.executions.length === 0">
                                    <q-item-section>
                                        <q-item-label class="text-grey-6">No execution history</q-item-label>
                                    </q-item-section>
                                </q-item>
                            </q-list>
                        </q-card-section>
                    </q-card>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script>
import AuthenticatedLayout from '@/Layouts/Authenticated.vue';
import { useForm } from '@inertiajs/vue3';
import CodeMirrorEditor from '@/Components/CodeMirrorEditor.vue';

export default {
    components: {
        AuthenticatedLayout,
        CodeMirrorEditor,
    },

    props: {
        script: Object,
        clients: Array,
    },

    data() {
        return {
            executing: false,
            executionOutput: this.$page.props.flash?.execution_output || null,
            executeForm: useForm({
                client_id: null,
            }),
            editorOptions: {
                mode: 'text/x-php',
                theme: 'material-darker',
                lineNumbers: true,
                lineWrapping: true,
                readOnly: true,
                indentUnit: 4,
                tabSize: 4,
            },
        };
    },

    computed: {
        clientOptions() {
            const clients = this.clients || this.$page.props.clients || [];
            return clients.map(client => ({
                label: client.name,
                value: client.id,
            }));
        },
    },

    methods: {
        formatDate(date) {
            return new Date(date).toLocaleString();
        },

        executeScript() {
            this.executing = true;
            
            this.executeForm.post(route('scripts.execute', this.script.id), {
                preserveScroll: true,
                onSuccess: (page) => {
                    this.$q.notify({
                        type: 'positive',
                        message: 'Script executed successfully',
                    });
                    // The output will be in flash data
                    this.executionOutput = page.props.flash?.execution_output || 'Script executed with no output';
                },
                onError: () => {
                    this.$q.notify({
                        type: 'negative',
                        message: 'Script execution failed',
                    });
                },
                onFinish: () => {
                    this.executing = false;
                },
            });
        },
    },

    mounted() {
        // Load clients if not passed as prop
        if (!this.clients && !this.$page.props.clients) {
            this.$inertia.reload({ only: ['clients'] });
        }
    },
};
</script>

<style>
.execution-output {
    font-family: 'Courier New', Courier, monospace;
    font-size: 12px;
    line-height: 1.5;
    white-space: pre-wrap;
    word-break: break-all;
    max-height: 300px;
    overflow-y: auto;
}
</style>