<template>
    <nav class="main-navigation">
        <router-link :to="{ name: 'Homepage' }" tag="div" class="logo">
            <!--<img
				class="max-h-7"
                v-if="config.app_logo_horizontal"
                :src="$getImage(logoSrc)"
                :alt="config.app_name"
            />-->
            <img
				height="auto"
                width="150px"
                v-if="config.app_logo_horizontal"
                :src="$getImage(logoSrc)"
                :alt="config.app_name"
            />
            <b v-if="!config.app_logo_horizontal" class="logo-text">{{ config.app_name }}</b>
        </router-link>
        <div class="navigation">
            <ul class="navigation-links">
                <li>
                    <a href="https://golink.co" class="hover-text-theme">
                        {{ $t('home_page') }}
                    </a>
                </li>
                <!--<li v-if="config.stripe_public_key">
                    <a href="/#pricing">
                        {{ $t('pricing') }}
                    </a>
                </li>-->
                <li>
                    <router-link :to="{ name: 'ContactUs' }" class="hover-text-theme">
                        {{ $t('contact_us') }}
                    </router-link>
                </li>
            </ul>
            <ul v-if="!config.isAuthenticated" class="navigation-links">
                <li>
                    <router-link :to="{ name: 'SignIn' }" class="hover-text-theme">
                        {{ $t('log_in') }}
                    </router-link>
                </li>
                <li v-if="config.userRegistration">
                    <router-link class="cta-button text-theme bg-theme-100" :to="{ name: 'SignUp' }">
                        {{ $t('page_index.menu.sign_in') }}
                    </router-link>
                </li>
            </ul>
            <ul v-if="config.isAuthenticated" class="navigation-links">
                <li>
                    <router-link class="cta-button text-theme bg-theme-100" :to="{ name: 'Files' }">
                        {{ $t('go_to_files') }}
                    </router-link>
                </li>
            </ul>
            <ul class="navigation-links">
                <li>
                    <language-switcher></language-switcher>
                </li>
            </ul>   

            <div class="navigation-links-light" @click="$store.dispatch('toggleThemeMode')" :title="$t('dark_mode_toggle')">
                <!--Toggle Dark/Light mode-->
                <div class="button-icon inline-block cursor-pointer rounded-xl">
                    <sun-icon v-if="isDarkMode" size="20" />
                    <moon-icon v-if="!isDarkMode" size="20" />
                </div>
            </div>

        </div>
        <div class="navigation-mobile">
            <router-link v-if="!config.isAuthenticated" class="cta-button log-in text-theme bg-theme-100" :to="{ name: 'SignIn' }">
                {{ $t('log_in') }}
            </router-link>
            <router-link v-if="config.isAuthenticated" class="cta-button text-theme bg-theme-100" :to="{ name: 'Files' }">
                {{ $t('go_to_files') }}
            </router-link>
        </div>       

    </nav>
</template>

<script>
import { mapGetters } from 'vuex'
import LanguageSwitcher from '../Others/LanguageSwitcher'
import { MoonIcon, SunIcon } from 'vue-feather-icons'

export default {
    name: 'IndexNavigation',
    components: { LanguageSwitcher, MoonIcon, SunIcon, },
    computed: {
        ...mapGetters(['config', 'index', 'isDarkMode']),
		logoSrc() {
			return this.isDarkMode && this.config.app_logo_horizontal ? this.config.app_logo_horizontal_dark : this.config.app_logo_horizontal
		}
    },
}
</script>

<style lang="scss" scoped>
@import '../../../sass/vuefilemanager/landing-page';
@import '../../../sass/vuefilemanager/variables';
@import '../../../sass/vuefilemanager/mixins';

.main-navigation {
    justify-content: space-between;
    padding-bottom: 25px;
    align-items: center;
    padding-top: 25px;
    display: flex;
}

.logo {
    cursor: pointer;

    img {
        cursor: pointer;
        height: 38px;
        width: auto;
    }

    .logo-text {
        font-weight: 800;
        @include font-size(25);
    }
}

.navigation-mobile {
    display: none;
}

.navigation-links {
    display: inline-block;
    margin-left: 25px;

    &:first-child {
        margin-left: 0;
    }

    li {
        display: inline-block;

        a {
            padding: 14px;
            font-weight: 700;
            @include font-size(17);
            @include transition(150ms);
        }
    }
}

.navigation-links-light {
    display: inline-block;
    position: relative;
    margin-left: 25px;
    top: 7px;
}

.cta-button {
    border-radius: 6px;
    padding: 8px 23px;
    @include font-size(17);
    font-weight: 700;

    &.log-in {
        display: none;
    }
}

@media only screen and (max-width: 690px) {
    .navigation {
        display: none;
    }

    .navigation-mobile {
        display: block;
    }   

    .logo {
        img {
            height: auto;
            width: 190px;
        }
    }

    .cta-button.log-in {
        display: block;
    }
}
</style>
