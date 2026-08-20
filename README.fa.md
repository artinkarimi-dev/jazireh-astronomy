<div align="center">
  <img src="frontend/public/brand/jazireh-logo.webp" width="148" alt="لوگوی جزیره نجوم" />

# جزیره نجوم

**پلتفرم فارسی و RTL نجوم برای یک مشتری واقعی که اکنون با معماری نهایی WordPress + React تحویل می‌شود.**

React · Vite · WordPress · PHP · MySQL

[English README](README.md) · [کانال یوتیوب](https://www.youtube.com/@Jazireh) · [معماری](docs/ARCHITECTURE.md) · [API](docs/API.md)
</div>

> [!IMPORTANT]
> این ریپازیتوری یک پروژه عمومی Source Available برای نمایش نمونه‌کار است و Open Source نیست. مشاهده سورس برای ارزیابی فنی مجاز است، اما نصب، اجرا، کپی، تغییر، استقرار، انتشار مجدد یا استفاده تجاری بدون اجازه کتبی مجاز نیست. متن کامل در [`LICENSE`](LICENSE) قرار دارد.

## معرفی

جزیره نجوم یک تجربه فارسی، واکنش‌گرا و راست‌چین برای اخبار علمی، APOD، ویدیوها، وضعیت آسمان، اکتشاف منظومه شمسی و خبرنامه است. معماری نهایی پروژه این است:

- فرانت‌اند React + Vite
- قالب سفارشی WordPress برای سرو React
- افزونه سفارشی `jazireh-core` برای مدل محتوا، تنظیمات، مدیریت و REST API
- WordPress به عنوان مرجع اصلی CMS و احراز هویت مدیریت

بک‌اند مستقل قدیمی دیگر بخشی از Runtime فعال نیست.

## معماری نهایی

```text
مرورگر
  |
  v
قالب وردپرس jazireh-theme
  |
  v
برنامه React
  |
  v
REST API افزونه jazireh-core
  |\
  | +--> دیتابیس وردپرس، منوها و رسانه‌ها
  |
  +----> NASA APOD / YouTube APIs
```

## ساختار پروژه

```text
jazireh-source/
├── docs/
├── frontend/
├── wordpress/wp-content/plugins/jazireh-core/
├── wordpress/wp-content/themes/jazireh-theme/
├── archive/backend-retired-2026-08-19/   بک‌اند قدیمی بازنشسته‌شده برای آرشیو/بازگشت
├── database/                       آرشیو SQL قدیمی
├── README.md
└── README.fa.md
```

## مسیرهای اصلی محصول

- خانه
- اخبار و جزئیات خبر
- APOD
- ویدیوها
- آسمان
- اکتشاف / اجرام آسمانی
- رادار شبیه‌سازی‌شده
- خبرنامه
- مدیریت و ورود بومی WordPress

## توسعه محلی

### پیش‌نیازها

- Node.js 20 یا جدیدتر
- npm 10 یا جدیدتر
- PHP 7.4 یا جدیدتر
- MySQL
- محیط WordPress محلی مانند XAMPP

### ساخت فرانت‌اند

از داخل `frontend/`:

```bash
npm ci
npm run build
npm run build:wordpress
```

خروجی نهایی قالب در این مسیر نوشته می‌شود:

```text
wordpress/wp-content/themes/jazireh-theme/dist
```

### همگام‌سازی با Runtime زنده

پس از Build موفق، این بخش‌ها باید به WordPress زنده همگام شوند:

- `wordpress/wp-content/themes/jazireh-theme`
- `wordpress/wp-content/plugins/jazireh-core`

Runtime فعال محلی در این محیط:

```text
C:\xampp\htdocs\wordpress
```

## API فعال

فرانت‌اند مسیرهای منطقی خودش را به این پایه فعال نگاشت می‌کند:

```text
http://localhost/wordpress/wp-json/jazireh/v1
```

جزئیات Endpointها در [`docs/API.md`](docs/API.md) قرار دارد.

## یکپارچه‌سازی‌های بیرونی

- NASA APOD به‌صورت سروری در WordPress
- YouTube به‌صورت سروری در WordPress
- Sky در نسخه فعلی از Snapshot سازگار استفاده می‌کند و Live OpenWeather نیست

## استقرار نهایی

برای هاست نهایی معمولاً این موارد لازم است:

- WordPress
- قالب `jazireh-theme`
- افزونه `jazireh-core`
- دیتابیس محتوای وردپرس
- uploads / media
- پیکربندی تولید و `wp-config.php`

و این موارد نباید در بسته استقرار فعال باشند:

- `frontend/node_modules`
- بک‌اند مستقل قدیمی به عنوان کد فعال
- Buildهای قدیمی Vite
- مسیرهای محلی XAMPP
- Dumpهای قدیمی دیتابیس مگر برای آرشیو

## محدودیت‌های فعلی

- Sky فعلاً Snapshot ثابت ارائه می‌کند.
- Radar شبیه‌سازی است و رهگیری زنده نیست.
- Explore از داده‌های محتوایی تهیه‌شده استفاده می‌کند.
- Lint هنوز چند مورد قدیمی و غیرمسدودکننده دارد که خارج از دامنه نهایی این مهاجرت هستند.
