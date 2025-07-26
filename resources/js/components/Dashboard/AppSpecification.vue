<template>
    <div class="mb-4 items-center justify-between md:mb-6">
        <!--VueFileManager logo-->
        <a href="https://golink.co" target="_blank">
            <img
                :src="
                    isDarkMode
                        ? 'https://golink.co/system/89973667-c27b-42e1-afd2-98f91a24f808.png'
                        : 'https://golink.co/system/a19b7560-a90b-40bb-89a9-17f1b609361e.png'
                "
                alt="VueFileManager"
                class="light-mode"
                height="auto"
                width="150px"
            />
        </a>

        <!--App Info-->
        <div class="mt-4 flex items-center lg:mt-0">
            <!--<a
                href="https://gist.github.com/MakingCG/9c07f8af392081ae5d5290d920a79b5d"
                target="_blank"
                class="mr-4 inline-block"
            >
                <span class="text-sm font-bold"> {{ $t('version') }}: </span>
                <ColorLabel color="purple">
                    {{ data.app.version }}
                </ColorLabel>
            </a>
            <a
                href="https://codecanyon.net/item/vue-file-manager-with-laravel-backend/25815986"
                target="_blank"
                class="mr-4 inline-block"
            >
                <span class="text-sm font-bold"> {{ $t('license') }}: </span>
                <ColorLabel color="purple">
                    {{ data.app.license }}
                </ColorLabel>
            </a>
            <b v-if="data.app.license === 'extended'" class="mr-4 inline-block">
                <span class="text-sm font-bold"> {{ $t('subscription') }}: </span>
                <ColorLabel color="purple">
                    {{ config.subscriptionType }}
                </ColorLabel>
            </b>-->
            <a
                href="https://golink.co"
                target="_blank"
                class="bg-theme-100 ml-8 hidden items-center whitespace-nowrap rounded-lg py-1.5 px-3 lg:flex lg:ml-4"
            >
                <thumbs-up-icon size="15" class="vue-feather text-theme mr-2.5" />
                <span class="text-theme text-sm font-bold">
                    GoLink.co - Decentralized Storage
                </span>
            </a>
        </div>
    </div>
</template>
<script>
import {ThumbsUpIcon} from 'vue-feather-icons'
import ColorLabel from '../UI/Labels/ColorLabel'
import {mapGetters} from "vuex";

export default {
    name: 'AppSpecification',
    components: {
		ThumbsUpIcon,
		ColorLabel,
	},
    props: {
        data: {},
    },
	computed: {
		...mapGetters(['config', 'isDarkMode']),
	},
}
</script>
