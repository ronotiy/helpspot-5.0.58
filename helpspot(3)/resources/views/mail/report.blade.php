<!DOCTYPE html>
<html lang="en" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="utf8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <title>{{ $report->sReport }}</title>
    <!--[if mso]>
    <xml>
        <o:OfficeDocumentSettings>
            <o:PixelsPerInch>96</o:PixelsPerInch>
        </o:OfficeDocumentSettings>
    </xml>
    <style>
        table {border-collapse: collapse;}
        td,th,div,p,a,h1,h2,h3,h4,h5,h6 {font-family: "Segoe UI", sans-serif; mso-line-height-rule: exactly;}
    </style>
    <![endif]-->
    <style>
        .hover-bg-gray-600:hover {
            background-color: #718096 !important;
        }
        @media screen {
            img {
                max-width: 100%;
            }
            .all-font-sans {
                font-family: -apple-system, "Segoe UI", sans-serif !important;
            }
        }
        @media (max-width: 600px) {
            u ~ div .wrapper {
                min-width: 100vw;
            }
            .sm-px-24 {
                padding-left: 24px !important;
                padding-right: 24px !important;
            }
            .sm-w-full {
                width: 100% !important;
            }
        }
    </style>
</head>

<body style="margin: 0; padding: 0; width: 100%; word-break: break-word; -webkit-font-smoothing: antialiased;">
<table class="wrapper all-font-sans" width="100%" cellpadding="0" cellspacing="0" role="presentation">
    <tr>
        <td align="center" style="background-color: #ffffff; vertical-align: middle;" bgcolor="#ffffff" valign="middle">
            <table class="sm-w-full" width="600" cellpadding="0" cellspacing="0" role="presentation">
                <tr>
                    <td align="center" class="sm-px-24">
                        <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                            <tr>
                                <td class="sm-px-24" style="background-color: #ffffff; border-radius: 8px; line-height: 24px; padding: 48px; color: #1a202c; font-size: 16px;" bgcolor="#ffffff">
                                    <table style="margin-top: 8px;" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                        <tr>
                                            <th style="mso-padding-alt: 12px 48px; background-color: #DFE5FF; margin-bottom: 8px; padding: 4px; text-align: left;" bgcolor="#DFE5FF" align="left">
                                                {{ $report->sReport }}
                                            </th>
                                        </tr>
                                    </table>
                                    <table width="100%" style="border-collapse: collapse; margin-top: 16px; margin-bottom: 32px;" cellpadding="0" cellspacing="0" role="presentation">
                                        @foreach ($tableData as $line)
                                            <tr style="background-color: {{ ($loop->even) ? '#FAFAFA' : '#ffffff' }} {{ ($loop->first) ? 'font-weight:"bold"' : '' }}">
                                                @foreach (str_getcsv($line, "\t") as $k => $cell)
                                                    <td style="padding: 4px; font-size: smaller;">
                                                        {{ $cell }}
                                                    </td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </table>
                                    <table style="margin-left: auto; margin-right: auto; margin-top: 8px;" cellpadding="0" cellspacing="0" role="presentation">
                                        <tr>
                                            <th class="hover-bg-gray-600" style="mso-padding-alt: 12px 48px; background-color: #3f3f3f; border-radius: 4px;" bgcolor="#3f3f3f">
                                                <a href="{{ action('Admin\AdminBaseController@adminFileCalled', ['pg' => $report->sPage, 'show' => $report->sShow, 'xReport' => $report->xReport]) }}" style="display: block; line-height: 100%; padding-top: 12px; padding-bottom: 12px; padding-left: 48px; padding-right: 48px; color: #ffffff; font-size: 14px; text-decoration: none;">View the full report</a>
                                            </th>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td height="32"></td>
                            </tr>
                            <tr>
                                <td style="padding: 32px; text-align: center; color: #718096; font-size: 12px;" align="center">
                                    <p style="margin: 0; margin-bottom: 4px; text-transform: uppercase;">Powered by <a href="https://www.helpspot.com">helpspot</a></p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
