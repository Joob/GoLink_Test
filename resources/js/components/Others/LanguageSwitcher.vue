<template>
    <div class="lang-switcher" v-click-outside="hideSwitch">
        <a :class="{'is-active': isOpen}" @click="toggleSwitch">
            <country-flag :country="typeof selected.flag === 'undefined' ? selected.code : selected.flag" size="normal"/>
        </a>

        <transition name="slide-in">
            <div class="absolute right-0 w-48 py-2 mt-2 bg-white bg-gray-100 rounded-md shadow-xl" style="top: 50px; margin-left: ; z-index: 9;" v-if="isOpen">
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

            if ((this.$router.path = '/page/termos-e-servicos') || (this.$router.path = '/page/termes-et-services') ||
                (this.$router.path = '/page/terms-of-services') ||
                (this.$router.path = '/page/terminos-de-servicios') ||
                (this.$router.path = '/page/privacy-policy') ||
                (this.$router.path = '/page/privacidade') ||
                (this.$router.path = '/page/politique-de-confidentialite') ||
                (this.$router.path = '/page/politica-privacidade') ||
                (this.$router.path = '/page/cookies') ||
                (this.$router.path = '/page/politique-de-cookies') ||
                (this.$router.path = '/page/cookie-policy') ||
                (this.$router.path = '/page/politica-cookies') ||
                (this.$router.path = '/page/sobre-nos') ||
                (this.$router.path = '/page/propos-de-nous') ||
                (this.$router.path = '/page/about-us') ||
                (this.$router.path = '/page/sobre-nosotros')) {
                
                //setTimeout(() => location.reload(), 1)
                this.$router.push('/')
                //return
            } //else {
                //setTimeout(() => location.reload(), 10)
            //}

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
    border-radius: 8px;
    background: linear-gradient(#eee, #fff);
    position: absolute;
    overflow: hidden;
    top: 55px;
    right: 0;
    z-index: 9;
    white-space: nowrap;
    max-height: 295px;
    overflow-y: auto;
    display: inline-block;

    .option-list {
        display: inline-block;

        .option-item {
            padding: 8px 15px;
            display: inline-block;
            cursor: pointer;

            &.active {
                color: $theme;
                background: rgba($theme, 0.1);
            }

            &:hover {
                color: $theme;
                background: rgba($theme, 0.1);
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

@media only screen and (max-width: 800px) {

    .lang-switcher {
    }

    .input-options {
        //box-shadow: 0 5px 15px rgba(0, 0, 0, 0.12);
        //border-radius: 4px;
        //position: relative;
        display: block;
        position: relative;
        margin-right: auto;
        margin-left: auto;
        top: 35px;

        .option-list {

            .option-item {
                padding: 5px 8px;
                position: relative;
                margin-right: auto;
                margin-left: auto;
                display: inline-block;
                cursor: pointer;

                &.active {
                }

                &:hover {
                }

                &:last-child {
                }
            }
        }
    }
}

@media only screen and (max-device-width : 480px) {

    .input-options {

        .option-list {
            right: 5%;

            .option-item {

                &.active {
                }

                &:hover {
                }

                &:last-child {
                }
            }
        }
    }

}

@media (prefers-color-scheme: dark) {

    .input-options {
        //background: rgba($theme, 0.1);

        .option-list {

            .option-item {

                &.active {
                    color: $dark_mode_text_secondary;;
                    background: rgba($theme, 0.1);
                }

                &:hover {
                    color: $dark_mode_text_secondary;;
                    background: rgba($theme, 0.1);
                }
            }
        }
    }
}
</style>