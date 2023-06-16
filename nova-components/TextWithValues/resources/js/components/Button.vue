<template>
    <span>
        <div v-if="button.dropdown" class="dropdown inline-block relative">
            <button type="button" class="ml-auto shadow relative bg-primary-500 hover:bg-primary-400 active:bg-primary-600 text-white dark:text-gray-900 ml-auto cursor-pointer rounded text-sm font-bold focus:outline-none focus:ring inline-flex items-center justify-center h-9 px-3 ml-auto shadow relative bg-primary-500 hover:bg-primary-400 active:bg-primary-600 text-white dark:text-gray-900 ml-auto mb-1" @click.prevent="toggle" v-click-outside="close">
              <span class="mr-1" v-if="button.name_html" v-html="button.name_html"></span>
              <span class="mr-1" v-else>{{ button.name }}</span>
              <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/> </svg>
            </button>
            <ul class="dropdown-menu absolute text-gray-700 pt-1 z-50 bg-white shadow-md list-reset" :class="{hidden: !opened}">
              <li v-for="dropdownButton in button.dropdown" :key="dropdownButton.name" class="px-1">
                <a href="#" @click.prevent="triggerChange(dropdownButton)" class="ml-auto shadow relative bg-primary-500 hover:bg-primary-400 active:bg-primary-600 text-white dark:text-gray-900 ml-auto cursor-pointer rounded text-sm font-bold focus:outline-none focus:ring inline-flex items-center justify-center h-9 px-3 ml-auto shadow relative bg-primary-500 hover:bg-primary-400 active:bg-primary-600 text-white dark:text-gray-900 ml-auto mb-1">
                    <span v-if="dropdownButton.name_html" v-html="dropdownButton.name_html"></span>
                    <span v-else>{{ dropdownButton.name }}</span>
                </a>
              </li>
            </ul>
        </div>
        <a v-else href="#" @click.prevent="triggerChange(button)" class="ml-auto shadow relative bg-primary-500 hover:bg-primary-400 active:bg-primary-600 text-white dark:text-gray-900 ml-auto cursor-pointer rounded text-sm font-bold focus:outline-none focus:ring inline-flex items-center justify-center h-9 px-3 ml-auto shadow relative bg-primary-500 hover:bg-primary-400 active:bg-primary-600 text-white dark:text-gray-900 ml-auto mb-1 mr-1">
            {{ button.name }}
        </a>
    </span>
</template>

<script>
    import vClickOutside from "click-outside-vue3"

    export default {
        directives: {
            clickOutside: vClickOutside.directive
        },
        props: {
            button: {
                type: Object,
                required: true,
            },
        },
        methods: {
            triggerChange (button) {
                this.$emit('change', button);
                this.close()
            },
            close () {
                this.opened = false
            },
            toggle () {
                this.opened = ! this.opened
            },
        },
        data () {
            return {
                opened: false,
            }
        },
    }
</script>
