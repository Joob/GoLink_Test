<template>
    <footer class="page-wrapper medium">
        <router-link :to="{ name: 'Homepage' }" tag="div" class="logo">
            <!--<img
                v-if="config.app_logo_horizontal"
                :src="$getImage(logoSrc)"
                :alt="config.app_name"
			    class="mx-auto max-h-7"
            />-->
            <img
                v-if="config.app_logo_horizontal"
                :src="$getImage(logoSrc)"
                :alt="config.app_name"
			    class="mx-auto"
                height="auto"
                width="150px"
            />
            <b v-if="!config.app_logo_horizontal" class="logo-text">{{ config.app_name }}</b>
        </router-link>
        <!--<ul class="navigation-links">
            <li>
                <a href="/#pricing">
                    {{ $t('pricing') }}
                </a>
            </li>
        </ul>-->
        <ul class="navigation-links">
            <li>
                <router-link :to="{ name: 'ContactUs' }" class="hover-text-theme">
                    {{ $t('contact_us') }}
                </router-link>
            </li>
            <li v-if="legal.visibility" v-for="(legal, index) in config.legal.filter(legal => legal.lang === getLocale())" :key="index">
                <router-link :to="{name: 'DynamicPage', params: {slug: legal.slug }}" class="hover-text-theme">
                    {{ legal.title }}
                </router-link>
            </li>

            <!--<li v-if="legal.visibility" v-for="(legal, index) in config.legal" :key="index">
                <router-link :to="{ name: 'DynamicPage', params: { slug: legal.slug } }" class="hover-text-theme">
                    {{ legal.title }}
                </router-link>
            </li>-->

            <div class="flag-mobile">
                <li>
                    <language-switcher></language-switcher>
                </li>
            </div>
        </ul>
        <p class="copyright" v-html="config.app_footer"></p>
    </footer>
</template>

<script>
import { mapGetters } from 'vuex'
import LanguageSwitcher from '../Others/LanguageSwitcherFooter'

export default {
    name: 'IndexPageFooter',
    components: { LanguageSwitcher },
    computed: {
        ...mapGetters(['config', 'isDarkMode']),
		logoSrc() {
			return this.isDarkMode && this.config.app_logo_horizontal ? this.config.app_logo_horizontal_dark : this.config.app_logo_horizontal
		},
    },
    methods: {
// GoLink CHANGE HERE
        getLocale() {
            return this.$i18n.locale ? this.$i18n.locale : config.locale;
        }
// GoLink END
    }
}
</script>

<style lang="scss" scoped>
@import '../../../sass/vuefilemanager/landing-page';
@import '../../../sass/vuefilemanager/variables';
@import '../../../sass/vuefilemanager/mixins';

footer {
    text-align: center;
    padding-top: 80px;
}

.logo {
    margin-bottom: 15px;
    cursor: pointer;

    img {
        height: 38px;
        width: auto;
    }

    .logo-text {
        font-weight: 800;
        @include font-size(25);
    }
}

.navigation-links {
    display: inline-block;

    li {
        display: inline-block;

        a {
            display: block;
            padding: 19px;
            font-weight: 700;
            @include font-size(17);
            @include transition(150ms);
        }
    }
}

.copyright {
    @include font-size(17);
    color: $text-muted;
    padding-top: 50px;
    padding-bottom: 20px;

    :deep() a {
        font-weight: 700;
    }
}

.flag-mobile {
    display: none;
}

@media only screen and (max-width: 800px) {

    .flag-mobile { 
        display: block;
        margin-left: auto;
        margin-right: auto;
    }

    .navigation-links {
        display: none;
    }

    .logo {

        img {
            height: auto;
            width: 190px;
        }
    }
}

@media only screen and (max-width: 960px) {
    .navigation-links {
        display: block;

        li {
            display: block;

            a {
                padding: 10px 0;
            }
        }
    }
}

.dark {
    .copyright {
        color: $dark_mode_text_secondary;
    }
}
</style>
