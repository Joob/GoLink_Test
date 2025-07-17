/*import Vue from 'vue'
import VueI18n from 'vue-i18n'

Vue.use(VueI18n)

import en from './lang/en.json'
import pt from './lang/pt.json'
import fr from './lang/fr.json'
import es from './lang/es.json'

Vue.use(VueI18n);

const i18n = new VueI18n({
    locale: config.locale,
    messages: {
        en,
        pt,
        fr,
        es
    },
});

/*const i18n = new VueI18n({
    locale: config.locale,
    silentTranslationWarn: true,
})

export default i18n*/

import Vue from 'vue'
import VueI18n from 'vue-i18n'

Vue.use(VueI18n)

const i18n = new VueI18n({
    locale: config.locale,
    silentTranslationWarn: true,
})

export default i18n

