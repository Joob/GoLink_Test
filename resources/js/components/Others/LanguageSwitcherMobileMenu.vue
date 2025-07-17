<template>
    <div class="lang-switcher" v-click-outside="hideSwitch">
        <a :class="{'is-active': isOpen}" @click="toggleSwitch">
            <country-flag :country="typeof selected.flag === 'undefined' ? selected.code : selected.flag" size="normal"/>
        </a>

        <transition name="slide-in">
            <div class="absolute right-0 w-48 py-2 mt-2 bg-white bg-gray-100 rounded-md shadow-xl" style="top: 120px; right: -30px;" v-if="isOpen">
                    <li class="block px-4 py-2 text-sm text-gray-300 text-gray-700 hover:bg-gray-200 hover:text-white" @click="changeLanguage(option)" v-for="(option, i) in $store.getters.languages" :key="i">
                        <span class="option-value text-gray-500 dark:text-gray-500">
                            {{ option.name }}
                            <country-flag :country="typeof option.flag === 'undefined' ? option.code : option.flag" size="small"/>
                        </span>
                    </li>
            </div>
        </transition>
    </div>
</template>

<script>
import CountryFlag from 'vue-country-flag'
import { ChevronDownIcon } from 'vue-feather-icons'
export default {
    name: 'LanguageSwitcher',
    components: { CountryFlag, ChevronDownIcon },
    data() {
        return {
            language: this.$store.getters.currentLanguage,
            isOpen: false,
        }
    },
    computed: {
        selected: function () {
            return this.$store.getters.languages.find(language => language.code === this.language);
        },
    },
    
    beforeMount() {
        if (! this.selected) {
            this.language = this.$store.getters.languages[0].code;
        }
    },
    watch: {
        locale() {
            this.$router.replace({ params: { lang: this.language } }).catch(() => {})
        }
    },
    methods: {
        changeLanguage(language) {
            // Emit selected
            this.$emit('input', language.value)
            // Get selected
            this.language = language.code

            // Close menu
            this.isOpen = false
            this.$store.dispatch('setLanguage', language.code)

            setTimeout(() => location.reload(), 5)
        },
        toggleSwitch() {
            this.isOpen = !this.isOpen
        },
        hideSwitch() {
            this.isOpen = false
        },
    },
}
</script>

<style lang="scss" scoped>
@import '../../../sass/vuefilemanager/variables';
@import '../../../sass/vuefilemanager/mixins';
.flag {
    vertical-align: middle;
}
.lang-switcher {
    position: relative;
    display: inline-block;
    cursor: pointer;
    padding: 0;
    top: -4px;
    margin: 0;
    border: none;
    background: none;
    outline: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
}

.input-options {
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.12);
    text-align: center; 
    float: none;
    margin: 0 auto;
    background: linear-gradient(#eee, #fff);
    border-radius: 8px;
    position: absolute;
    overflow: hidden;
    top: 135px;
    right: -40px;
    z-index: 9;
    white-space: nowrap;
    max-height: 295px;
    overflow-y: auto;
    display: inline-block;

    .option-list {
        display: inline-block;

        .option-item {
            padding: 7px 7px;
            display: inline-block;
            cursor: pointer;

            &.active {
                color: $theme;
                //color: rgb(112, 112, 112);
                background: rgba($theme, 0.5);
            }

            &:hover {
                color: $theme;
                //color: rgb(112, 112, 112);
                background: rgba($theme, 0.5);
            }

            &:last-child {
                border-bottom: none;
            }
        }
    }
}

.option-value {
    width: 100%;
    vertical-align: middle;
    display: inline;
}
.slide-in-enter-active {
    transition: all 150ms ease;
}
.slide-in-enter /* .list-leave-active below version 2.1.8 */
{
    opacity: 0;
    transform: translateY(-50px);
}


@media (prefers-color-scheme: dark) {

    .input-options {
        //background: rgba($theme, 0.1);
        //background: green;
        //color: rgb(54, 54, 54);
        //color: $dark_mode_text_secondary;

        .option-list {

            .option-item {

                &.active {
                    //color: rgb(54, 54, 54);
                    color: $dark_mode_text_secondary;
                    background: rgba($theme, 0.1);
                }

                &:hover {
                    //color: rgb(54, 54, 54);
                    color: $dark_mode_text_secondary;
                    background: rgba($theme, 0.1);
                }
            }
        }
    }
}

</style>