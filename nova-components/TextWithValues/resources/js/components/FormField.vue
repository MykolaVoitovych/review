<template>
    <DefaultField
        :field="field"
        :errors="errors"
        :full-width-content="true"
        :show-help-text="showHelpText"
    >
        <template slot="field" #field>
            <div>
                <action-button v-for="button in buttons" :key="button.name" :button="button" @change="runButton" />
            </div>
            <textarea
                ref="theTextarea"
                v-bind="extraAttributes"
                class="w-full form-control form-input form-input-bordered py-3 h-auto"
                :id="field.uniqueKey"
                :dusk="field.attribute"
                :value="value"
                @input="handleChange"
            />
        </template>
    </DefaultField>
</template>

<script>
import { FormField, HandlesValidationErrors } from 'laravel-nova'
import ActionButton from './Button.vue'

export default {
    mixins: [HandlesValidationErrors, FormField],

    components: {
        ActionButton,
    },

    data: () => ({buttons: []}),

    /**
     * Mount the component.
     */
    mounted() {
        this.buttons = this.field.buttons
    },
    methods: {
        setCursorPosition(el, pos) {
            el.focus();
            el.setSelectionRange(pos, pos);
        },
        runButton(button) {
            var insertText = button.replace
            var textArea = this.$refs.theTextarea
            var cursorPosition = textArea.selectionStart

            this.value = this.value.slice(0, cursorPosition) + insertText + this.value.slice(cursorPosition, this.value.length);
            this.$nextTick(() => this.setCursorPosition(textArea, cursorPosition + insertText.length));
        }
    },
    computed: {
        defaultAttributes() {
            return {
                rows: this.field.rows,
                class: this.errorClasses,
                placeholder: this.field.name,
            }
        },

        extraAttributes() {
            const attrs = this.field.extraAttributes

            return {
                ...this.defaultAttributes,
                ...attrs,
            }
        },
    },
}
</script>
