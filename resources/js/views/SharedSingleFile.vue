<template>
    <!--<div class="flex h-screen items-center justify-center p-4">-->
    <div class="landing-page">

        <!--App logo-->
        <!--<div v-if="file && !isVideo" class="fixed top-4 left-0 right-0">-->
        <div v-if="file && !isVideo" class="top-4 left-0 right-0">
            <!--Navigation-->
            <Navigation class="page-wrapper medium" />

            <span style="padding: 20px"></span>

            <!--<router-link :to="{ name: 'SignIn' }" class="block">
                Image logo-->
                <!--<img
                    v-if="config.app_logo_horizontal"
                    class="mx-auto w-44"
                    :src="$getImage(logoSrc)"
                    :alt="config.app_name"
                />-->

                <!--Text logo if image isn't available-->
                <!--<b v-if="!config.app_logo_horizontal" class="mb-4 block text-xl font-bold text-center">
                    {{ config.app_name }}
                </b>-->
            <!--</router-link>-->
        </div>

        <!--File preview-->
        <div v-if="file" class="w-full text-center">

            <!--Navigation-->
            <Navigation v-if="isVideo" class="page-wrapper medium block" />
            
            <span style="padding: 20px"></span>

            <!--App logo-->
            <!--<router-link v-if="isVideo" :to="{ name: 'SignIn' }" class="block">
                Image logo-->
                <!--<img
                    v-if="config.app_logo_horizontal"
                    class="mx-auto w-44"
                    :src="$getImage(logoSrc)"
                    :alt="config.app_name"
                />-->

                <!--Text logo if image isn't available-->
                <!--<b v-if="!config.app_logo_horizontal" class="mb-4 block text-xl font-bold">
                    {{ config.app_name }}
                </b>-->
            <!--</router-link>-->

            <!--File item-->
            <ItemGrid v-if="!isVideo" :entry="file" :mobile-handler="false" class="mt-6"/>

            <!--Video preview-->
            <div v-if="isVideo" class="mb-4">
                <!--Video preview-->
                <Video
                    :file="file"
                    class="mx-auto my-10 w-full self-center rounded-lg shadow-xl lg:max-w-xl xl:max-w-3xl 2xl:max-w-5xl"
                />

                <!--Item Info-->
                <div class="text-center">
                    <!--Item Title-->
                    <b class="inline-block w-full text-sm tracking-tight md:px-6">
                        {{ file.data.attributes.name }}
                    </b>

                    <!--Item sub line-->
                    <div class="flex items-center justify-center">
                        <!--File & Image sub line-->
                        <small class="block text-xs text-gray-500 dark:text-gray-500">
                            {{ file.data.attributes.filesize }}<span class="text-xs text-gray-500 lg:inline-block">, {{ file.data.attributes.created_at }}</span>
                        </small>
                    </div>
                </div>
            </div>

            <!--Download button-->
            <ButtonBase @click.native="downloadFile" button-style="theme" class="mx-auto">
                {{ $t('page_shared.download_file') }}
            </ButtonBase>

            <!--Google Adsense banner-->
            <!--<div v-if="config.allowedAdsense && config.adsenseBanner02" v-html="config.adsenseBanner02" class="mt-5 min-h-[120px]"></div>-->

        <br>
        <br>
        <!--Footer-->
        <PageFooter />

        </div>
    </div>
</template>

<script>
import PageFooter from '../components/IndexPage/IndexPageFooter'
import Navigation from '../components/IndexPage/IndexNavigation'
import ButtonBase from '../components/UI/Buttons/ButtonBase'
import Video from '../components/FilePreview/Media/Video'
import ItemGrid from '../components/UI/Entries/ItemGrid'
import { events } from '../bus'
import { mapGetters } from 'vuex'
import axios from 'axios'

export default {
    name: 'SharedSingleItem',
    components: {
        PageFooter,
        Navigation,
        ButtonBase,
        ItemGrid,
        Video,
    },
    computed: {
        ...mapGetters(['config', 'isDarkMode']),
		logoSrc() {
			return this.isDarkMode && this.config.app_logo_horizontal ? this.config.app_logo_horizontal_dark : this.config.app_logo_horizontal
		},
        isVideo() {
            return this.file.data.type === 'video'
        },
    },
    data() {
        return {
            file: undefined,
        }
    },
    methods: {
        downloadFile() {
            this.$downloadSelection(this.file)
            setTimeout(() => {
                events.$emit('toaster', { 
                    type: 'success',
                    message: this.$t('toaster.download_completed'),
                })
            }, 1000) // 1 seconds delay
        },
    },
    mounted() {
        axios
        .get(`/api/sharing/file/${this.$router.currentRoute.params.token}`)
        .then((response) => {
            this.file = response.data
        })
        .catch((error) => {
            if (error.response.status === 403) {
                this.$router.push({ name: 'SharedAuthentication' })
            }
        })
    },
}
</script>
