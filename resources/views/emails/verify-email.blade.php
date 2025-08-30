<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تأكيد البريد الإلكتروني</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            margin:0;
            padding:0;
            font-family: 'Tahoma', Arial, sans-serif;
            background-color:#f4f4f7;
        }
        .container {
            max-width:600px;
            margin:20px auto;
            background:#ffffff;
            border-radius:12px;
            overflow:hidden;
            box-shadow:0 2px 8px rgba(0,0,0,0.1);
        }
        .header {
            background:#1E3A8A;
            color:#fff;
            padding:25px;
            text-align:center;
            font-size:22px;
            font-weight:bold;
        }
        .body {
            padding:25px;
            color:#333;
            font-size:16px;
            line-height:1.6;
        }
        .btn {
            background:#2563EB;
            color:#fff !important;
            text-decoration:none;
            padding:14px 28px;
            border-radius:8px;
            font-size:16px;
            font-weight:bold;
            display:inline-block;
        }
        .btn:hover {
            background:#1D4ED8;
        }
        .footer {
            background:#f4f4f7;
            color:#999;
            text-align:center;
            font-size:12px;
            padding:15px;
        }
        /* ✅ Responsive */
        @media screen and (max-width: 600px) {
            .container {
                width:95% !important;
                margin:10px auto;
            }
            .body {
                padding:15px !important;
                font-size:14px !important;
            }
            .btn {
                display:block !important;
                width:100% !important;
                text-align:center;
                padding:12px 0 !important;
            }
        }
    </style>
</head>
<body>

    <table class="container" cellpadding="0" cellspacing="0" width="100%">
        <!-- Header -->
        <tr>
            <td class="header">
                مرحباً {{ $user->name }} 👋
            </td>
        </tr>

        <!-- Body -->
        <tr>
            <td class="body">
                <p>شكراً لتسجيلك في موقعنا.</p>
                <p>من فضلك اضغط على الزر بالأسفل لتأكيد بريدك الإلكتروني وتفعيل حسابك:</p>

                <p style="text-align:center; margin:30px 0;">
                    <a href="{{ $url }}" class="btn">✅ تأكيد البريد الإلكتروني</a>
                </p>

                <p style="font-size:14px; color:#777; margin-top:25px;">
                    إذا لم تقم بإنشاء حساب، يمكنك تجاهل هذه الرسالة.
                </p>
            </td>
        </tr>

        <!-- Footer -->
        <tr>
            <td class="footer">
                &copy; {{ date('Y') }} شركتك. جميع الحقوق محفوظة.
            </td>
        </tr>
    </table>

</body>
</html>
