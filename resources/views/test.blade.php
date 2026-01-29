<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>لوحة المعلومات</title>
    <style>
        body {
            font-family: "Tajawal", sans-serif;
            background: #f3f4f6;
            margin: 0;
            padding: 20px;
        }
        h2 { text-align: center; margin-bottom: 30px; }
        .container { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; }
        .card {
            background: white; padding: 20px; border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08); text-align: center;
        }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 10px; border-bottom: 1px solid #eee; }
        .prayer-item { padding: 10px; font-size: 18px; border-bottom: 1px solid #eee; }
    </style>
</head>
<body>

<h2>لوحة معلومات متكاملة</h2>

<div class="container">

    <!-- 1 حكمة -->
    <div class="card">
        <h3>📜 حكمة اليوم</h3>
        <p style="font-size:18px; color:#333;">{{ $quote }}</p>
    </div>

    <!-- 2 أسعار العملات -->
    <div class="card">
        <h3>💰 أسعار العملات مقابل الشيكل</h3>
        <table>
            <tr><th>العملة</th><th>قيمة 1 شيكل</th></tr>
            <tr><td>الدولار USD</td><td>{{ number_format($usd, 4) }}</td></tr>
            <tr><td>اليورو EUR</td><td>{{ number_format($eur, 4) }}</td></tr>
            <tr><td>الجنيه المصري EGP</td><td>{{ number_format($egp, 4) }}</td></tr>
        </table>
    </div>

    <!-- 3 مواقيت الصلاة -->
    <div class="card">
        <h3>🕌 مواقيت الصلاة – غزة</h3>

        @foreach($prayer as $name => $time)
            <div class="prayer-item">
                <strong>{{ $name }}</strong> : {{ $time }}
            </div>
        @endforeach
    </div>

</div>
 
</body>
</html>
