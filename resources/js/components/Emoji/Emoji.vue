<template>
    <div v-if="emoji">
        <div
            v-if="config.defaultEmoji === 'twemoji'"
            v-html="transferEmoji"
            style="font-size: inherit; transform: scale(0.95)"
        ></div>
        <div v-if="config.defaultEmoji === 'applemoji'" style="font-size: inherit">
            {{ emoji.char }}
        </div>
    </div>
</template>

<script>
import twemoji from 'twemoji'
import { mapGetters } from 'vuex'

export default {
    name: 'Emoji',
    props: ['emoji'],
    data() {
        return {
            isApple: false,
            sizeClass: undefined,
        }
    },
    computed: {
        ...mapGetters(['config']),
        transferEmoji() {
            return twemoji.parse(this.emoji.char, {
                folder: 'svg',
                ext: '.svg',
                base: 'https://cdnjs.cloudflare.com/ajax/libs/twemoji/16.0.1/',
                attributes: () => ({
                    loading: 'lazy',
                }),
            })
        },
    },
}
</script>

<style lang="css">
.emoji {
    height: 1em;
    width: 1em;
    font-size: inherit;
}
</style>
