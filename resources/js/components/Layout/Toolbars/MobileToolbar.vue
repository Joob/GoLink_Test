<template>
    <div
        class="sticky top-0 z-[19] block flex w-full items-center justify-between px-4 bg-white text-center dark:bg-dark-background lg:hidden"
    >
        <NavigationBar />

        <div class="relative flex items-center">
            <TeamMembersButton
                v-if="shouldShowTeamMembersButton"
                size="28"
                @click.stop.native="$showMobileMenu('team-menu')"
                class="absolute right-10"
            />

<!-- GoLink CHANGE HERE -->
            <div class="flex items-center mr-[4px]">
                <div class="mr-4 button-icon inline-block cursor-pointer rounded-xl p-3">
                    <language-switcher />
                </div>

                <!--Toggle Dark/Light mode-->
                <div @click="$store.dispatch('toggleThemeMode')" :title="$t('dark_mode_toggle')">
                    <div style="margin-top: 10px" class="mr-4 button-icon inline-block cursor-pointer rounded-xl p-3">
                        <sun-icon v-if="isDarkMode" size="20" />
                        <moon-icon v-if="!isDarkMode" size="20" />
                    </div>
                </div>
            </div>
<!-- GoLink CHANGE HERE -->

            <!--More Actions-->
            <div class="flex items-center relative mr-[4px]">               
				<div v-if="! $isThisRoute($route, ['Public'])" @click="showMobileNavigation" class="cursor-pointer p-1.5 -m-1.5">
					<menu-icon size="20" class="vue-feather dark:text-gray-100" />
				</div>
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