<template>
    <AuthenticatedLayout>
        <div class="q-gutter-md">
            <div class="row items-center justify-between q-mb-md">
                <div class="text-h5">Create Script</div>
            </div>

            <q-card>
                <q-form @submit="submit">
                    <q-card-section>
                        <div class="q-gutter-md">
                            <q-input
                                v-model="form.name"
                                label="Script Name"
                                filled
                                :error="!!form.errors.name"
                                :error-message="form.errors.name"
                            />

                            <q-input
                                v-model="form.description"
                                label="Description"
                                filled
                                type="textarea"
                                rows="3"
                                :error="!!form.errors.description"
                                :error-message="form.errors.description"
                            />

                            <div>
                                <div class="text-subtitle2 q-mb-sm">Tinker Code</div>
                                <div class="text-caption text-grey-7 q-mb-md">
                                    Write PHP code that will be executed in Laravel Tinker context. 
                                    Variables like $client will be injected when executing with a client context.
                                </div>
                                <CodeMirrorEditor
                                    v-model="form.tinker_code"
                                    :options="editorOptions"
                                    @input="onCodeChange"
                                />
                                <div v-if="form.errors.tinker_code" class="text-negative text-caption q-mt-sm">
                                    {{ form.errors.tinker_code }}
                                </div>
                            </div>
                        </div>
                    </q-card-section>

                    <q-separator />

                    <q-card-actions align="right">
                        <q-btn
                            flat
                            label="Cancel"
                            @click="$inertia.visit(route('scripts.index'))"
                        />
                        <q-btn
                            type="submit"
                            color="primary"
                            label="Create Script"
                            :loading="form.processing"
                        />
                    </q-card-actions>
                </q-form>
            </q-card>
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

    data() {
        return {
            form: useForm({
                name: '',
                description: '',
                tinker_code: '// Example: Get all active clients\n$clients = \\App\\Models\\Client::where(\'status\', \'active\')\n    ->get();\n\nforeach ($clients as $client) {\n    echo $client->name . " - " . $client->fly_org_id . "\\n";\n}',
            }),
            editorOptions: {
                mode: 'text/x-php',
                theme: 'material-darker',
                lineNumbers: true,
                lineWrapping: true,
                autoCloseBrackets: true,
                matchBrackets: true,
                indentUnit: 4,
                tabSize: 4,
                indentWithTabs: false,
                extraKeys: {
                    'Ctrl-/': 'toggleComment',
                    'Cmd-/': 'toggleComment',
                },
            },
        };
    },

    methods: {
        onCodeChange(code) {
            this.form.tinker_code = code;
        },

        submit() {
            this.form.post(route('scripts.store'), {
                onSuccess: () => {
                    this.$q.notify({
                        type: 'positive',
                        message: 'Script created successfully',
                    });
                },
            });
        },
    },
};
</script>