<template>
    <div
        class="sticky top-0 z-[19] block flex w-full items-center justify-between px-4 bg-white text-center dark:bg-dark-background lg:hidden"
    >
        <NavigationBar />

        <div class="flex items-center gap-1">
            <!-- Language Switcher -->
            <div class="button-icon cursor-pointer rounded-xl p-2">
                <language-switcher />
            </div>

            <!-- Toggle Dark/Light mode -->
            <div 
                @click="$store.dispatch('toggleThemeMode')" 
                :title="$t('dark_mode_toggle')"
                class="button-icon cursor-pointer rounded-xl p-2"
            >
                <sun-icon v-if="isDarkMode" size="20" />
                <moon-icon v-if="!isDarkMode" size="20" />
            </div>

            <!-- Team Members Button -->
            <transition name="fade-scale">
                <TeamMembersButton
                    v-if="shouldShowTeamMembersButton"
                    size="5"
                    @click.stop.native="$showMobileMenu('team-menu')"
                    class="button-icon cursor-pointer rounded-xl p-2"
                />
            </transition>

            <!-- Divider -->
            <div v-if="!$isThisRoute($route, ['Public'])" class="w-px h-6 bg-gray-300 dark:bg-gray-600 mx-1"></div>

            <!-- Menu Actions -->
            <div 
                v-if="!$isThisRoute($route, ['Public'])" 
                @click="showMobileNavigation" 
                class="button-icon cursor-pointer rounded-xl p-2"
            >
                <menu-icon size="20" class="vue-feather dark:text-gray-100" />
            </div>
        </div>
    </div>
</template>

<script>
import TeamMembersPreview from '../../Teams/Components/TeamMembersPreview'
import TeamMembersButton from '../../Teams/Components/TeamMembersButton'
import LanguageSwitcher from '../../Others/LanguageSwitcherMobileMenu'
import { MenuIcon, MoonIcon, SunIcon } from 'vue-feather-icons'
import NavigationBar from './NavigationBar'
import { mapGetters } from 'vuex'

export default {
    name: 'MobileToolBar',
    components: {
        NavigationBar,
        TeamMembersPreview,
        TeamMembersButton,
        LanguageSwitcher,
        MenuIcon,
        MoonIcon,
        SunIcon,
    },
    computed: {
        ...mapGetters(['isDarkMode', 'currentTeamFolder', 'clipboard']),
        shouldShowTeamMembersButton() {
            // Mostra o botão apenas se:
            // 1. Estiver nas rotas corretas (TeamFolders ou SharedWithMe)
            // 2. Houver um item selecionado (clipboard não vazio) OU estiver dentro de uma pasta de equipe
            return this.$isThisRoute(this.$route, ['TeamFolders', 'SharedWithMe']) && 
                   (this.clipboard.length > 0 || this.currentTeamFolder || this.$route.params.id)
        },
    },
    methods: {
        showMobileNavigation() {
            this.$showMobileMenu('user-navigation')
            this.$store.commit('DISABLE_MULTISELECT_MODE')
        },
    },
}
</script>

<style scoped>
.fade-scale-enter-active,
.fade-scale-leave-active {
    transition: all 0.2s ease;
}

.fade-scale-enter,
.fade-scale-leave-to {
    opacity: 0;
    transform: scale(0.8);
}
</style>