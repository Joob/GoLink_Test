<template>
<div class="landing-page">

    <!--Navigation-->
    <Navigation class="page-wrapper medium"/>

    <br><br>

    <AuthContentWrapper>
        <AuthContent :visible="true">
            <Headline
                :title="$t('page_email_successfully_send.title')"
                :description="$t('page_email_successfully_send.subtitle')"
            />

            <span class="block">
                <router-link :to="{ name: 'Homepage' }" class="text-theme font-bold">
                    {{ $t('go_home') }}
                </router-link>
            </span>
        </AuthContent>
    </AuthContentWrapper>

    <br><br>

    <!--Footer-->
    <PageFooter/>

    </div>
</template>

<script>
import AuthContentWrapper from '../../components/Layout/AuthPages/AuthContentWrapper'
import AuthContent from '../../components/Layout/AuthPages/AuthContent'
import PageFooter from '../../components/IndexPage/IndexPageFooter'
import Navigation from '../../components/IndexPage/IndexNavigation'
import AuthButton from '../../components/UI/Buttons/AuthButton'
import Headline from '../../components/UI/Labels/LogoHeadline'

export default {
    name: 'SuccessfullySendEmail',
    components: {
        AuthContentWrapper,
        AuthContent,
        PageFooter,
        Navigation,
        AuthButton,
        Headline,
    },
}
</script>
