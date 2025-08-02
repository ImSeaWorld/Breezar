<template>
    <div ref="editorContainer"></div>
</template>

<script>
import { onMounted, onUnmounted, ref, watch } from 'vue';
import CodeMirror from 'codemirror';
import 'codemirror/lib/codemirror.css';
import 'codemirror/theme/material-darker.css';
import 'codemirror/mode/php/php';
import 'codemirror/addon/edit/closebrackets';
import 'codemirror/addon/edit/matchbrackets';
import 'codemirror/addon/comment/comment';

export default {
    props: {
        modelValue: {
            type: String,
            default: '',
        },
        options: {
            type: Object,
            default: () => ({}),
        },
    },
    
    emits: ['update:modelValue', 'input'],
    
    setup(props, { emit }) {
        const editorContainer = ref(null);
        let editor = null;
        
        onMounted(() => {
            const defaultOptions = {
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
            };
            
            editor = CodeMirror(editorContainer.value, {
                ...defaultOptions,
                ...props.options,
                value: props.modelValue || '',
            });
            
            editor.on('change', () => {
                const value = editor.getValue();
                emit('update:modelValue', value);
                emit('input', value);
            });
        });
        
        watch(() => props.modelValue, (newValue) => {
            if (editor && newValue !== editor.getValue()) {
                editor.setValue(newValue);
            }
        });
        
        onUnmounted(() => {
            if (editor) {
                // Clear the editor instance
                editor.getWrapperElement().remove();
                editor = null;
            }
        });
        
        return {
            editorContainer,
        };
    },
};
</script>

<style>
.CodeMirror {
    height: 400px;
    border: 1px solid #ddd;
    font-size: 14px;
}
</style>