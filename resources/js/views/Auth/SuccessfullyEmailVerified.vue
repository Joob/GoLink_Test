<template>
<div class="landing-page">

    <!--Navigation-->
    <Navigation class="page-wrapper medium"/>

    <br><br>

    <AuthContentWrapper>
        <AuthContent :visible="true">
            <Headline
                :title="$t('page_email_successfully_verified.title')"
                :description="$t('page_email_successfully_verified.subtitle')"
            />

            <router-link :to="{ name: 'SignIn' }">
                <AuthButton icon="chevron-right" :text="$t('log_in')" />
            </router-link>
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
    name: 'SuccessfullyEmailVerified',
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
