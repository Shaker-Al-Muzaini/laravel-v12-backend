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
        h2 {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
        }
        .container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 20px;
        }
        .card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }
        .title {
            font-size: 20px;
            margin-bottom: 15px;
            color: #444;
            font-weight: bold;
        }
        .quote {
            font-size: 18px;
            color: #222;
            line-height: 1.7;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            text-align: center;
        }
        table td, table th {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .prayer-item {
            padding: 8px 0;
            font-size: 17px;
            border-bottom: 1px solid #eee;
        }
    </style>
</head>
<body>

<h2>لوحة معلومات متكاملة</h2>

<div class="container">

    <!-- 1) أقوال وحكم -->
    <div class="card">
        <div class="title">📜 حكمة اليوم</div>
        @if($quote)
            <div class="quote">
                "{{ $quote['q'] }}"
                <br><br>
                — {{ $quote['a'] }}
            </div>
        @else
            <p>لا يمكن تحميل الحكمة الآن</p>
        @endif
    </div>

    <!-- 2) أسعار العملات -->
    <div class="card">
        <div class="title">💰 أسعار العملات مقابل الشيكل</div>
        <table>
            <tr><th>العملة</th><th>القيمة لشيكل واحد</th></tr>
            <tr><td>الدولار (USD)</td><td>{{ number_format($rates['USD'] ?? 0, 4) }}</td></tr>
            <tr><td>اليورو (EUR)</td><td>{{ number_format($rates['EUR'] ?? 0, 4) }}</td></tr>
            <tr><td>الجنيه المصري (EGP)</td><td>{{ number_format($rates['EGP'] ?? 0, 4) }}</td></tr>
        </table>
    </div>

    <!-- 3) مواقيت الصلاة غزة -->
    <div class="card">
        <div class="title">🕌 مواقيت الصلاة – غزة</div>

        @foreach($prayer as $name => $time)
            <div class="prayer-item">
                <strong>{{ $name }}</strong> : {{ $time }}
            </div>
        @endforeach
    </div>

</div>

</body>
</html>
