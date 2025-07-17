@extends('mails.layout.mail')


@section('content')
    @include('mails.partials.image-hero', [
        'image' => ('https://images.golink.co/mail/user-code.png'),
        'style' => 'padding: 5px 0;',
    ])
    

    <tr>
        <td align="center" valign="top" style="padding-left:20px;padding-right:20px;" class="containtTable">

            <table border="0" cellpadding="0" cellspacing="0" width="100%" class="tableDescription">

                <tr>
                    <td align="center" valign="top" style="padding-bottom:5px;padding-left:20px;padding-right:20px;" class="mainTitle">
                        <!-- Info Text // -->
                        <h2 class="text" style="color:#000000; font-family:'Open Sans', Helvetica, Arial, sans-serif; font-size:28px; font-weight:600; font-style:normal; letter-spacing:normal; line-height:36px; text-transform:none; text-align:center; padding:0; margin:0">
                        @include('mails.partials.title', [
                            'title' => __t('mails.hello.name', ['name' => $name]),
                        ])
                        </h2>
                    </td>
                </tr>
                
                <tr>
                    <td align="center" valign="top" style="padding-bottom: 10px;" class="description">
                        <!-- Description Text// -->
                        <p class="text" style="color:#666666; font-family:'Open Sans', Helvetica, Arial, sans-serif; font-size:15px; font-weight:400; font-style:normal; letter-spacing:normal; line-height:22px; text-transform:none; text-align:center; padding:0; margin:0">
                        @include('mails.partials.subtitle', [
                            'subtitle' => __t('send_otp_code_line_1'),
                        ])
                        </p>
                    </td>
                </tr>

                <tr>
                    <td align="center" valign="top" style="padding-bottom:5px;padding-left:20px;padding-right:20px;" class="mainTitle">
                        <!-- Info Text // -->
                        <h2 class="text" style="color:#000000; font-family:'Open Sans', Helvetica, Arial, sans-serif; font-size:28px; font-weight:600; font-style:normal; letter-spacing:normal; line-height:36px; text-transform:none; text-align:center; padding:0; margin:0">
                        {{ __t('send_otp_code_line_2') }}
                        </h2>
                    </td>
                </tr>

                <tr>
                    <td align="center" valign="top" style="padding-bottom:30px;padding-left:20px;padding-right:20px;" class="subTitle">
                        <!-- Unlock Text // -->
                        <h4 class="text" style="color:#999999; font-family:'Open Sans', Helvetica, Arial, sans-serif; font-size:18px; font-weight:400; font-style:normal; letter-spacing:normal; line-height:26px; text-transform:none; text-align:center; padding:0; margin:0">
                        {{ __t('send_otp_code_line_3') }}
                        </h4>
                    </td>
                </tr>

                <tr>
                    <td align="center" valign="top" style="padding-left:20px;padding-right:20px;" class="containtTable">

                        <table border="0" cellpadding="0" cellspacing="0" width="100%" class="tableMediumTitle">
                            <tr>
                                <td align="center" valign="top" style="padding-bottom:20px;" class="mediumTitle">
                                    <!-- Code Text // -->
                                    <p class="text" style="color:#3f4b97; font-family:'Open Sans', Helvetica, Arial, sans-serif; font-size:34px; font-weight:300; font-style:normal; letter-spacing:normal; line-height:24px; text-transform:none; text-align:center; padding:0; margin:0">
                                        {!! __t('send_otp_code_line_4', ['code' => $code]) !!}
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <td align="center" valign="top" style="padding-bottom:30px;padding-left:20px;padding-right:20px;" class="subTitle">
                                    <!-- Expires Text // -->
                                    <h4 class="text" style="color:#999999; font-family:'Open Sans', Helvetica, Arial, sans-serif; font-size:18px; font-weight:400; font-style:normal; letter-spacing:normal; line-height:26px; text-transform:none; text-align:center; padding:0; margin:0">
                                    {{ __t('send_otp_code_line_5', ['expiration' => $expiration]) }}
                                    </h4>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <tr>
                    <td align="center" valign="top" style="padding-bottom:20px;" class="description">
                        <!-- Description Text// -->
                        <p class="text" style="color:#666666; font-family:'Open Sans', Helvetica, Arial, sans-serif; font-size:14px; font-weight:400; font-style:normal; letter-spacing:normal; line-height:22px; text-transform:none; text-align:center; padding:0; margin:0">
                        {{ __t('send_otp_code_line_6') }}
                        </p>
                    </td>
                </tr>

            </table>

        </td>
    </tr>

@endsection
