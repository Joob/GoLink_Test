<template>
    <div @keydown.esc="closeOverlays" tabindex="-1" class="min-w-[320px]">
        <!--UI components-->
        <Alert />
        <ToasterWrapper />
        <CookieDisclaimer />
        <RemoteUploadProgress />
        <UploadBox 
            :files="allUploads"
            @cancel-upload="handleCancelUpload"
            @cancel-all-uploads="handleCancelAllUploads"
            @clear-completed="handleClearCompleted"
            @show-full-progress="showUploadProgressModal"
        />

        <!--Show spinner before translations is loaded-->
        <Spinner v-if="!isLoaded" />

        <!--Show warning bar when user functionality is restricted-->
        <RestrictionWarningBar />

		<div :class="{'lg:flex': isSidebarNavigation}">
			<SidebarNavigation v-if="isSidebarNavigation" />
			<!-- Adicione a key aqui para forçar re-renderização -->
			<router-view v-if="isLoaded" :key="routeKey" />
		</div>

        <!--Background under popups-->
        <Vignette />
        
        <!--Upload Progress Modal-->
        <div v-if="showUploadModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="closeUploadProgressModal"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <UploadProgress 
                            :files="allUploads"
                            @cancel-upload="handleCancelUpload"
                            @cancel-all-uploads="handleCancelAllUploads"
                            @clear-completed="handleClearCompleted"
                        />
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button 
                            @click="closeUploadProgressModal"
                            type="button" 
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm dark:bg-gray-600 dark:text-white dark:border-gray-500 dark:hover:bg-gray-700"
                        >
                            {{ $t ? $t('close') : 'Close' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import RestrictionWarningBar from './components/Subscription/RestrictionWarningBar'
import RemoteUploadProgress from "./components/RemoteUpload/RemoteUploadProgress"
import UploadBox from "./components/Upload/UploadBox"
import UploadProgress from "./components/Upload/UploadProgress"
import ToasterWrapper from './components/Toaster/ToasterNotifications'
import SidebarNavigation from "./components/Sidebar/SidebarNavigation"
import CookieDisclaimer from './components/UI/Others/CookieDisclaimer'
import Spinner from './components/UI/Others/Spinner'
import Vignette from './components/UI/Others/Vignette'
import Alert from './components/Popups/Alert'
import { mapGetters, mapActions } from 'vuex'
import { events } from './bus'

export default {
    name: 'App',
    components: {
        RestrictionWarningBar,
		RemoteUploadProgress,
		UploadBox,
		UploadProgress,
		SidebarNavigation,
        CookieDisclaimer,
        ToasterWrapper,
        Vignette,
        Spinner,
        Alert,
    },
    data() {
        return {
            isLoaded: false,
			isSidebarNavigation: undefined,
			showUploadModal: false,
        }
    },
    computed: {
        ...mapGetters(['config', 'user', 'allUploads', 'hasActiveUploads']),
		// Adicione este computed para gerar uma key única
		routeKey() {
			// Usa o fullPath para garantir que cada rota tenha uma key única
			// Adiciona timestamp para garantir re-renderização completa
			return `${this.$route.fullPath}-${Date.now()}`
		}
    },
    watch: {
        'config.defaultThemeMode': function () {
            this.handleDarkMode()
        },
		'$route' (to, from) {
			let section = this.$router.currentRoute.fullPath.split('/')[1]
			const app = document.getElementsByTagName('body')[0]

			// Set background color via theme setup
			if (['admin', 'user'].includes(section)) {
				app.classList.add('dark:bg-dark-background', 'bg-light-background')
			} else {
				app.classList.remove('dark:bg-dark-background', 'bg-light-background')
			}

			// Set sidebar navigation visibility
			this.isSidebarNavigation = ['admin', 'user', 'platform'].includes(section)
			
			// Força atualização do DOM quando muda entre seções diferentes
			const fromSection = from.fullPath.split('/')[1]
			const toSection = to.fullPath.split('/')[1]
			
			if (fromSection !== toSection) {
				// Força um ciclo de re-renderização
				this.$nextTick(() => {
					// Emite evento para componentes filhos se necessário
					events.$emit('route:section:changed', { from: fromSection, to: toSection })
				})
			}
		}
    },
    methods: {
        ...mapActions(['cancelUpload', 'cancelAllUploads', 'clearCompletedUploads']),
		closeOverlays() {
			events.$emit('popup:close')
			events.$emit('popover:close')

			this.$store.commit('CLOSE_NOTIFICATION_CENTER')
		},
		showUploadProgressModal() {
			this.showUploadModal = true
		},
		closeUploadProgressModal() {
			this.showUploadModal = false
		},
		handleCancelUpload(file) {
			this.cancelUpload(file.id)
		},
		handleCancelAllUploads() {
			this.cancelAllUploads()
		},
		handleClearCompleted() {
			this.clearCompletedUploads()
		},
        spotlightListener(e) {
			if (e.key === 'k' && e.metaKey || e.key === 'k' && e.ctrlKey) {
				e.preventDefault()
				events.$emit('spotlight:show');
			}
        },
        handleDarkMode() {
            const app = document.getElementsByTagName('html')[0]
            const prefersDarkScheme = window.matchMedia('(prefers-color-scheme: dark)')

            if (this.config.defaultThemeMode === 'dark') {
                app.classList.add('dark')
                this.$store.commit('UPDATE_DARK_MODE_STATUS', true)
            } else if (this.config.defaultThemeMode === 'light') {
                app.classList.remove('dark')
                this.$store.commit('UPDATE_DARK_MODE_STATUS', false)
            } else if (this.config.defaultThemeMode === 'system' && prefersDarkScheme.matches) {
                app.classList.add('dark')
                this.$store.commit('UPDATE_DARK_MODE_STATUS', true)
            } else if (this.config.defaultThemeMode === 'system' && !prefersDarkScheme.matches) {
                app.classList.remove('dark')
                this.$store.commit('UPDATE_DARK_MODE_STATUS', false)
            }
        },
    },
    beforeMount() {
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
            this.handleDarkMode()
        })

        // Commit config
        this.$store.commit('INIT', {
            config: this.$root.$data.config,
        })

        // Redirect to setup wizard
        if (this.$root.$data.config.installation === 'installation-needed') {
            this.isLoaded = true

            if (window.location.pathname.split('/')[1] !== 'setup-wizard') {
                this.$router.push({ name: 'StatusCheck' })
            }
        } else {
            /*this.$store.dispatch('getLanguageTranslations', this.$root.$data.config.locale).then(() => {
                this.isLoaded = true
            })*/
            // Get language translations
            this.$store.dispatch('getLanguageTranslations', this.$root.$data.config.language)
                .then(() => {
                    this.isLoaded = true

                    // Store config to vuex
                    this.$store.commit('INIT', {
                        authCookie: this.$root.$data.config.hasAuthCookie,
                        config: this.$root.$data.config,
                        rootDirectory: {
                            name: this.$t('locations.home'),
                            location: 'base',
                            unique_id: 0
                        }
                    })
                })
        }

        // Go to sign in page if homepage is disabled
		if (!this.$root.$data.config.allowHomepage && window.location.pathname === '/') {
			this.$router.push({ name: 'SignIn' })
		}
    },
    created() {
        if (this.$isWindows()) {
            document.body.classList.add('windows')
        }

        window.addEventListener('keydown', this.spotlightListener)
    },
    destroyed() {
        window.removeEventListener('keydown', this.spotlightListener)
    },
}
</script>

<style lang="scss">
//@import url('https://fonts.googleapis.com/css2?family=Nunito:wght@200;300;400;600;700;800;900&display=swap');
@import url('https://golink.co/css/fonts.css?family=Nunito:wght@200;300;400;600;700;800;900&display=swap');
@import '../sass/vuefilemanager/variables';
@import '../sass/vuefilemanager/mixins';

input:-webkit-autofill {
    transition-delay: 999999999999s;
}

[v-cloak],
[v-cloak] > * {
    display: none;
}

* {
    outline: 0;
    margin: 0;
    padding: 0;
    font-family: 'Nunito', sans-serif;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
    box-sizing: border-box;
    -webkit-tap-highlight-color: rgba(0, 0, 0, 0);
    font-size: 16px;
    text-decoration: none;
    color: $text;
}

.vue-feather {
    path,
    circle,
    line,
    rect,
    polyline,
    ellipse,
    polygon {
        color: inherit;
    }
}

// Dark mode
.dark {
    * {
        color: $dark_mode_text_primary;
    }

    body,
    html {
        background: $dark_mode_background;
        color: $dark_mode_text_primary;

        img {
            opacity: 0.95;
        }
    }
}
</style>